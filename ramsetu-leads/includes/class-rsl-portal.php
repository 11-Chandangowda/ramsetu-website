<?php
/**
 * Partner & Employee Portal — shortcode [ramsetu_portal] (the theme's "Partner Portal" page).
 *
 * Logged out:  login (ID + password) · register as channel partner · forgot / set password
 * Logged in:   dashboard · submit lead · my leads · lead details · profile
 *
 * All form posts go to the portal page itself and are handled on template_redirect,
 * before any output, so they can redirect (post/redirect/get) or re-show errors.
 */

defined( 'ABSPATH' ) || exit;

class RSL_Portal {

	private static $errors = array();
	private static $old    = array();
	private static $notice = '';

	public static function init() {
		add_shortcode( 'ramsetu_portal', array( __CLASS__, 'render' ) );
		add_shortcode( 'ramsetu_partner_form', array( __CLASS__, 'render_partner_form' ) );
		add_action( 'template_redirect', array( __CLASS__, 'handle' ), 5 );
	}

	public static function is_portal_page() {
		$page = (int) get_option( 'rsl_portal_page' );
		return $page && is_page( $page );
	}

	private static function view() {
		// phpcs:ignore WordPress.Security.NonceVerification
		return isset( $_GET['view'] ) ? sanitize_key( $_GET['view'] ) : '';
	}

	private static function ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	private static function go( $view = '', $args = array() ) {
		wp_safe_redirect( rsl_portal_url( $view, $args ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/* Request handling                                                    */
	/* ------------------------------------------------------------------ */

	/** True when the "Become a Partner" registration form was posted on a normal page. */
	private static function is_embedded_register() {
		return ! self::is_portal_page() && is_singular() && 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) // phpcs:ignore
			&& isset( $_POST['rsl_portal_action'] ) && 'register' === $_POST['rsl_portal_action']; // phpcs:ignore WordPress.Security.NonceVerification
	}

	public static function handle() {
		if ( self::is_embedded_register() ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			// This page may be served from cache with an expired token, like the other public
			// forms; the honeypot, timing and rate-limit checks in do_register() still apply.
			self::$old = wp_unslash( $_POST );
			self::do_register( self::$old );
			return;
		}
		if ( ! self::is_portal_page() ) {
			return;
		}
		// Never cache portal pages (logins, personal data, one-time forms).
		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
		nocache_headers();
		header( 'X-Robots-Tag: noindex, nofollow', true );

		if ( 'logout' === self::view() ) {
			if ( isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'rsl_logout' ) ) {
				wp_logout();
			}
			self::go( '', array( 'msg' => 'logged_out' ) );
		}

		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['rsl_portal_action'] ) ) {
			// Logged-in staff visiting the portal go to their dashboard.
			if ( is_user_logged_in() && ! RSL_Roles::is_portal_user() && current_user_can( 'rsl_work_leads' ) && ! self::view() ) {
				wp_safe_redirect( admin_url( 'admin.php?page=rsl-leads' ) );
				exit;
			}
			return;
		}

		$action = sanitize_key( $_POST['rsl_portal_action'] );
		if ( ! isset( $_POST['_rslp'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_rslp'] ) ), 'rsl_portal_' . $action ) ) {
			self::$errors['_form'] = __( 'Your session expired. Please try again.', 'ramsetu-leads' );
			return;
		}
		$p          = wp_unslash( $_POST );
		self::$old  = $p;
		$method     = 'do_' . str_replace( '-', '_', $action );
		if ( method_exists( __CLASS__, $method ) ) {
			call_user_func( array( __CLASS__, $method ), $p );
		}
	}

	private static function do_login( array $p ) {
		$login = sanitize_text_field( $p['login'] ?? '' );
		$login = is_email( $login ) ? $login : strtoupper( $login );
		$key   = 'rsl_login_' . md5( self::ip() );
		$fails = (int) get_transient( $key );
		if ( $fails >= 5 ) {
			self::$errors['_form'] = __( 'Too many failed attempts. Please wait 15 minutes, or use "Forgot password".', 'ramsetu-leads' );
			return;
		}
		if ( '' === $login || empty( $p['password'] ) ) {
			self::$errors['_form'] = __( 'Enter your ID and password.', 'ramsetu-leads' );
			return;
		}
		$user = wp_signon( array(
			'user_login'    => $login,
			'user_password' => (string) $p['password'],
			'remember'      => ! empty( $p['remember'] ),
		), is_ssl() );
		if ( is_wp_error( $user ) ) {
			set_transient( $key, $fails + 1, 15 * MINUTE_IN_SECONDS );
			self::$errors['_form'] = 'rsl_suspended' === $user->get_error_code()
				? $user->get_error_message()
				: __( 'The ID or password is incorrect.', 'ramsetu-leads' );
			return;
		}
		delete_transient( $key );
		if ( ! RSL_Roles::is_portal_user( $user ) ) {
			wp_safe_redirect( user_can( $user, 'rsl_work_leads' ) ? admin_url( 'admin.php?page=rsl-leads' ) : admin_url() );
			exit;
		}
		self::go();
	}

	private static function do_register( array $p ) {
		// Bot checks, same as the public forms.
		$ts = (int) ( $p['rsl_ts'] ?? 0 );
		if ( ! empty( $p['rsl_website'] ) || ( $ts && time() - $ts < 3 ) ) {
			self::register_done_redirect();
		}
		$rl = 'rsl_reg_' . md5( self::ip() );
		if ( (int) get_transient( $rl ) >= 3 ) {
			self::$errors['_form'] = __( 'Too many registrations from your connection. Please try again later.', 'ramsetu-leads' );
			return;
		}
		list( $d, $e ) = RSL_Partners::validate_registration( $p );
		if ( $e ) {
			self::$errors = $e;
			return;
		}
		$id = RSL_Partners::create_application( $d, self::ip() );
		if ( ! $id ) {
			self::$errors['_form'] = __( 'We could not save your application. Please try again.', 'ramsetu-leads' );
			return;
		}
		set_transient( $rl, (int) get_transient( $rl ) + 1, HOUR_IN_SECONDS );
		self::register_done_redirect();
	}

	private static function register_done_redirect() {
		if ( self::is_portal_page() ) {
			self::go( 'register-done' );
		}
		wp_safe_redirect( add_query_arg( 'registered', '1', get_permalink() ) . '#partner-form' );
		exit;
	}

	private static function do_forgot( array $p ) {
		$login = trim( sanitize_text_field( $p['login'] ?? '' ) );
		$rl    = 'rsl_forgot_' . md5( self::ip() );
		if ( (int) get_transient( $rl ) < 5 && '' !== $login ) {
			$user = is_email( $login ) ? get_user_by( 'email', $login ) : get_user_by( 'login', strtoupper( $login ) );
			if ( $user && RSL_Roles::is_portal_user( $user ) && ! get_user_meta( $user->ID, 'rsl_suspended', true ) ) {
				RSL_Partners::send_reset( $user );
			}
			set_transient( $rl, (int) get_transient( $rl ) + 1, HOUR_IN_SECONDS );
		}
		// Same answer whether or not the account exists.
		self::go( '', array( 'msg' => 'reset_sent' ) );
	}

	private static function do_set_password( array $p ) {
		$login = sanitize_text_field( $p['login'] ?? '' );
		$key   = sanitize_text_field( $p['key'] ?? '' );
		$user  = check_password_reset_key( $key, $login );
		if ( is_wp_error( $user ) ) {
			self::$errors['_form'] = __( 'This link has expired or was already used. Use "Forgot password" to get a new one.', 'ramsetu-leads' );
			return;
		}
		$err = self::password_problem( (string) ( $p['pass1'] ?? '' ), (string) ( $p['pass2'] ?? '' ) );
		if ( $err ) {
			self::$errors['pass1'] = $err;
			return;
		}
		reset_password( $user, (string) $p['pass1'] );
		self::go( '', array( 'msg' => 'password_set', 'id' => rawurlencode( $user->user_login ) ) );
	}

	private static function do_change_password( array $p ) {
		$user = wp_get_current_user();
		if ( ! RSL_Roles::is_portal_user( $user ) ) {
			return;
		}
		if ( ! wp_check_password( (string) ( $p['current'] ?? '' ), $user->user_pass, $user->ID ) ) {
			self::$errors['current'] = __( 'Your current password is incorrect.', 'ramsetu-leads' );
			return;
		}
		$err = self::password_problem( (string) ( $p['pass1'] ?? '' ), (string) ( $p['pass2'] ?? '' ) );
		if ( $err ) {
			self::$errors['pass1'] = $err;
			return;
		}
		wp_set_password( (string) $p['pass1'], $user->ID );
		// wp_set_password logs the user out everywhere; sign them back in here.
		wp_set_auth_cookie( $user->ID, false, is_ssl() );
		self::go( 'profile', array( 'msg' => 'password_changed' ) );
	}

	private static function password_problem( $a, $b ) {
		if ( strlen( $a ) < 8 || ! preg_match( '/[A-Za-z]/', $a ) || ! preg_match( '/\d/', $a ) ) {
			return __( 'Use at least 8 characters, with letters and numbers.', 'ramsetu-leads' );
		}
		if ( $a !== $b ) {
			return __( 'The two passwords do not match.', 'ramsetu-leads' );
		}
		return '';
	}

	private static function do_submit_lead( array $p ) {
		$user = wp_get_current_user();
		if ( ! RSL_Roles::is_portal_user( $user ) ) {
			return;
		}
		$partner = RSL_Partners::for_user( $user->ID );
		$is_cp   = in_array( 'rs_channel_partner', (array) $user->roles, true );
		if ( $is_cp && ( ! $partner || 'approved' !== $partner['status'] ) ) {
			self::$errors['_form'] = __( 'Your partner account is not active.', 'ramsetu-leads' );
			return;
		}

		$e = array();
		$d = array(
			'full_name'              => sanitize_text_field( $p['full_name'] ?? '' ),
			'phone'                  => rsl_normalize_phone( $p['phone'] ?? '' ),
			'email'                  => sanitize_email( $p['email'] ?? '' ),
			'co_applicant_name'      => sanitize_text_field( $p['co_applicant_name'] ?? '' ),
			'co_applicant_phone'     => '',
			'co_applicant_email'     => sanitize_email( $p['co_applicant_email'] ?? '' ),
			'city'                   => sanitize_text_field( $p['city'] ?? '' ),
			'district'               => sanitize_text_field( $p['district'] ?? '' ),
			'state'                  => sanitize_text_field( $p['state'] ?? '' ),
			'profession'             => sanitize_key( $p['profession'] ?? '' ),
			'business_type'          => sanitize_text_field( $p['business_type'] ?? '' ),
			'business_vintage_years' => '' === trim( (string) ( $p['business_vintage_years'] ?? '' ) ) ? null : (int) $p['business_vintage_years'],
			'loan_category'          => sanitize_key( $p['loan_category'] ?? '' ),
			'collateral'             => sanitize_key( $p['collateral'] ?? '' ),
			'property_location'      => mb_substr( sanitize_text_field( $p['property_location'] ?? '' ), 0, 255 ),
			'existing_loans'         => sanitize_textarea_field( $p['existing_loans'] ?? '' ),
		);
		$money = function ( $v ) {
			return (float) preg_replace( '/[^\d.]/', '', (string) $v );
		};
		$d['loan_amount']      = $money( $p['loan_amount'] ?? '' );
		$d['collateral_value'] = $money( $p['collateral_value'] ?? '' );

		if ( mb_strlen( $d['full_name'] ) < 2 ) {
			$e['full_name'] = __( "Enter the customer's name.", 'ramsetu-leads' );
		}
		if ( ! $d['phone'] ) {
			$e['phone'] = __( 'Enter a valid 10-digit mobile number.', 'ramsetu-leads' );
		}
		if ( ! empty( $p['email'] ) && ! is_email( $d['email'] ) ) {
			$e['email'] = __( 'This email address does not look right.', 'ramsetu-leads' );
		}
		if ( ! empty( $p['co_applicant_phone'] ) ) {
			$d['co_applicant_phone'] = rsl_normalize_phone( $p['co_applicant_phone'] );
			if ( ! $d['co_applicant_phone'] ) {
				$e['co_applicant_phone'] = __( 'Enter a valid 10-digit mobile number, or leave it blank.', 'ramsetu-leads' );
			}
		}
		if ( ! empty( $p['co_applicant_email'] ) && ! is_email( $d['co_applicant_email'] ) ) {
			$e['co_applicant_email'] = __( 'This email address does not look right.', 'ramsetu-leads' );
		}
		if ( '' === $d['city'] ) {
			$e['city'] = __( 'Enter the city.', 'ramsetu-leads' );
		}
		if ( '' === $d['district'] ) {
			$e['district'] = __( 'Enter the district.', 'ramsetu-leads' );
		}
		if ( ! in_array( $d['state'], rsl_states(), true ) ) {
			$e['state'] = __( 'Select the state.', 'ramsetu-leads' );
		}
		if ( ! isset( rsl_portal_profiles()[ $d['profession'] ] ) ) {
			$e['profession'] = __( 'Select the customer profile.', 'ramsetu-leads' );
		}
		if ( ! isset( rsl_business_segments()[ $d['business_type'] ] ) ) {
			$e['business_type'] = __( 'Select the nature of business.', 'ramsetu-leads' );
		}
		if ( 'salaried' !== $d['profession'] && ( null === $d['business_vintage_years'] || $d['business_vintage_years'] < 0 || $d['business_vintage_years'] > 99 ) ) {
			$e['business_vintage_years'] = __( 'Enter how many years the business has been running.', 'ramsetu-leads' );
		}
		if ( ! isset( rsl_portal_products()[ $d['loan_category'] ] ) ) {
			$e['loan_category'] = __( 'Select the product.', 'ramsetu-leads' );
		}
		if ( $d['loan_amount'] < 10000 || $d['loan_amount'] > 9999999999 ) {
			$e['loan_amount'] = __( 'Enter the loan amount in rupees.', 'ramsetu-leads' );
		}
		if ( ! isset( rsl_collaterals()[ $d['collateral'] ] ) ) {
			$e['collateral'] = __( 'Select the collateral property type.', 'ramsetu-leads' );
		}
		if ( $d['collateral_value'] < 10000 || $d['collateral_value'] > 9999999999 ) {
			$e['collateral_value'] = __( 'Enter the property value in rupees.', 'ramsetu-leads' );
		}
		if ( mb_strlen( $d['property_location'] ) < 3 ) {
			$e['property_location'] = __( 'Enter where the property is located.', 'ramsetu-leads' );
		}
		if ( empty( $p['consent'] ) ) {
			$e['consent'] = __( 'Please confirm the customer has agreed to share these details.', 'ramsetu-leads' );
		}

		// Duplicate protection: same customer mobile registered in the last 90 days.
		if ( ! $e ) {
			global $wpdb;
			$t    = RSL_DB::table( 'lead' );
			$dupe = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $t WHERE phone = %s AND created_at >= %s LIMIT 1", $d['phone'], gmdate( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) . ' -90 days' ) ) ) );
			if ( $dupe ) {
				$e['phone'] = __( 'A lead for this mobile number is already registered with RamSetu. Please contact RamSetu if you believe this is a mistake.', 'ramsetu-leads' );
			}
		}
		if ( $e ) {
			self::$errors = $e;
			return;
		}

		$d['source']       = $is_cp ? 'partner' : 'employee';
		$d['partner_id']   = $is_cp ? (int) $partner['id'] : null;
		$d['submitted_by'] = $user->ID;
		$d['consent']      = 1;
		$d['product']      = rsl_label( rsl_portal_products(), $d['loan_category'] );
		$d['page_url']     = rsl_portal_url( 'submit' );
		$d['ip_address']   = self::ip();
		$remarks           = sanitize_textarea_field( $p['remarks'] ?? '' );
		if ( $remarks ) {
			$d['remarks'] = '[' . RSL_Partners::user_code( $user->ID ) . '] ' . $remarks;
		}

		$id = RSL_DB::insert( 'lead', $d );
		if ( ! $id ) {
			self::$errors['_form'] = __( 'We could not save this lead. Please try again.', 'ramsetu-leads' );
			return;
		}
		do_action( 'rsl_new_submission', 'lead', $id, $d );
		self::go( 'lead', array( 'id' => $id, 'msg' => 'lead_submitted' ) );
	}

	/* ------------------------------------------------------------------ */
	/* Rendering                                                           */
	/* ------------------------------------------------------------------ */

	public static function render() {
		ob_start();
		echo '<div class="pt" data-portal>';
		if ( ! is_user_logged_in() ) {
			switch ( self::view() ) {
				case 'register':
					self::view_register();
					break;
				case 'register-done':
					self::view_register_done();
					break;
				case 'forgot':
					self::view_forgot();
					break;
				case 'set-password':
					self::view_set_password();
					break;
				default:
					self::view_login();
			}
		} elseif ( ! RSL_Roles::is_portal_user() ) {
			echo '<div class="pt-card pt-card--narrow"><h2>' . esc_html__( 'You are logged in as RamSetu staff', 'ramsetu-leads' ) . '</h2><p>' . esc_html__( 'Staff manage leads, partners and employees in the dashboard.', 'ramsetu-leads' ) . '</p><p><a class="btn btn--primary" href="' . esc_url( admin_url( 'admin.php?page=rsl-leads' ) ) . '">' . esc_html__( 'Open dashboard', 'ramsetu-leads' ) . '</a></p></div>';
		} else {
			self::shell_open();
			switch ( self::view() ) {
				case 'submit':
					self::view_submit();
					break;
				case 'leads':
					self::view_leads();
					break;
				case 'lead':
					self::view_lead();
					break;
				case 'profile':
					self::view_profile();
					break;
				default:
					self::view_dashboard();
			}
			echo '</div></div>';
		}
		echo '</div>';
		return ob_get_clean();
	}

	private static function msg() {
		// phpcs:ignore WordPress.Security.NonceVerification
		$m = isset( $_GET['msg'] ) ? sanitize_key( $_GET['msg'] ) : '';
		$messages = array(
			'logged_out'       => __( 'You have been logged out.', 'ramsetu-leads' ),
			'reset_sent'       => __( 'If that ID or email is registered, we have emailed a link to set a new password.', 'ramsetu-leads' ),
			'password_set'     => __( 'Your password is set. Log in with your ID and new password.', 'ramsetu-leads' ),
			'password_changed' => __( 'Your password has been changed.', 'ramsetu-leads' ),
			'lead_submitted'   => __( 'Lead submitted. The RamSetu team will take it forward and you can track it here.', 'ramsetu-leads' ),
		);
		if ( isset( $messages[ $m ] ) ) {
			printf( '<div class="pt-alert pt-alert--ok" role="status">%s</div>', esc_html( $messages[ $m ] ) );
		}
		if ( ! empty( self::$errors['_form'] ) ) {
			printf( '<div class="pt-alert pt-alert--error" role="alert">%s</div>', esc_html( self::$errors['_form'] ) );
		} elseif ( self::$errors ) {
			printf( '<div class="pt-alert pt-alert--error" role="alert">%s</div>', esc_html__( 'Please check the highlighted fields.', 'ramsetu-leads' ) );
		}
	}

	private static function form_open( $action, $class = '' ) {
		$url = self::view() ? add_query_arg( 'view', self::view(), get_permalink() ) : get_permalink();
		if ( ! self::is_portal_page() ) {
			$url = remove_query_arg( 'registered', $url ) . '#partner-form';
		}
		printf( '<form method="post" action="%s" class="rsl-form pt-form %s" novalidate data-rsl-enhance>', esc_url( $url ), esc_attr( $class ) );
		printf( '<input type="hidden" name="rsl_portal_action" value="%s">', esc_attr( $action ) );
		wp_nonce_field( 'rsl_portal_' . $action, '_rslp', false );
	}

	private static $uid = 0;

	/** Field helper that matches the public forms' markup (so the same styles and scripts apply). */
	private static function field( $name, $label, $a = array() ) {
		$id       = 'pt-' . $name . '-' . ( ++self::$uid );
		$type     = $a['type'] ?? 'text';
		$required = ! empty( $a['required'] );
		$err      = self::$errors[ $name ] ?? '';
		$val      = array_key_exists( 'value', $a ) ? $a['value'] : ( self::$old[ $name ] ?? '' );
		printf( '<div class="rsl-field%s%s" data-field="%s">', ! empty( $a['wide'] ) ? ' rsl-field--wide' : '', $err ? ' has-error' : '', esc_attr( $name ) );
		if ( 'checkbox' !== $type ) {
			printf( '<label for="%s">%s%s</label>', esc_attr( $id ), esc_html( $label ), $required ? ' <span class="rsl-req" aria-hidden="true">*</span>' : '' );
		}
		$extra = '';
		foreach ( array( 'placeholder', 'autocomplete', 'inputmode', 'maxlength', 'min', 'max', 'data-rsl-money', 'data-rsl-pan', 'autocapitalize' ) as $k ) {
			if ( isset( $a[ $k ] ) ) {
				$extra .= sprintf( ' %s="%s"', esc_attr( $k ), esc_attr( $a[ $k ] ) );
			}
		}
		$extra .= $required ? ' required aria-required="true"' : '';
		$extra .= $err ? ' aria-invalid="true" aria-describedby="' . esc_attr( $id ) . '-err"' : '';

		if ( 'select' === $type ) {
			printf( '<select id="%s" name="%s"%s><option value="">%s</option>', esc_attr( $id ), esc_attr( $name ), $extra, esc_html( $a['placeholder'] ?? __( 'Select', 'ramsetu-leads' ) ) );
			foreach ( $a['options'] as $k => $v ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( (string) $val, (string) $k, false ), esc_html( $v ) );
			}
			echo '</select>';
		} elseif ( 'textarea' === $type ) {
			printf( '<textarea id="%s" name="%s" rows="3"%s>%s</textarea>', esc_attr( $id ), esc_attr( $name ), $extra, esc_textarea( $val ) );
		} elseif ( 'checkbox' === $type ) {
			printf( '<label class="rsl-check"><input type="checkbox" id="%s" name="%s" value="1"%s%s><span>%s</span></label>', esc_attr( $id ), esc_attr( $name ), checked( ! empty( $val ), true, false ), $extra, wp_kses( $label, array( 'a' => array( 'href' => true, 'target' => true, 'rel' => true ) ) ) );
		} else {
			$show_val = in_array( $type, array( 'password' ), true ) ? '' : $val;
			printf( '<input id="%s" name="%s" type="%s" value="%s"%s>', esc_attr( $id ), esc_attr( $name ), esc_attr( $type ), esc_attr( $show_val ), $extra );
		}
		printf( '<p class="rsl-error" id="%s-err"%s>%s</p></div>', esc_attr( $id ), $err ? '' : ' hidden', esc_html( $err ) );
	}

	/* ---------- logged out ---------- */

	private static function auth_layout_open( $title, $sub ) {
		echo '<div class="pt-auth"><div class="pt-auth__side"><p class="eyebrow">' . esc_html__( 'RamSetu Partner Portal', 'ramsetu-leads' ) . '</p>';
		echo '<h2 class="pt-auth__lead">' . esc_html__( 'Refer customers. Track every lead. Earn on every successful loan.', 'ramsetu-leads' ) . '</h2>';
		echo '<ul class="pt-auth__points"><li>' . esc_html__( 'Submit leads in two minutes', 'ramsetu-leads' ) . '</li><li>' . esc_html__( 'See live status: credit, documents, disbursement', 'ramsetu-leads' ) . '</li><li>' . esc_html__( 'Track your incentives', 'ramsetu-leads' ) . '</li></ul></div>';
		echo '<div class="pt-auth__main"><h1 class="pt-auth__title">' . esc_html( $title ) . '</h1>';
		if ( $sub ) {
			echo '<p class="pt-muted">' . esc_html( $sub ) . '</p>';
		}
		self::msg();
	}

	private static function auth_layout_close() {
		echo '</div></div>';
	}

	private static function view_login() {
		self::auth_layout_open( __( 'Log in', 'ramsetu-leads' ), __( 'Channel partners and RamSetu employees', 'ramsetu-leads' ) );
		// phpcs:ignore WordPress.Security.NonceVerification
		$prefill = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : '';
		self::form_open( 'login' );
		self::field( 'login', __( 'Partner / Employee ID', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'username', 'placeholder' => 'e.g. RSCP00012', 'autocapitalize' => 'characters', 'value' => self::$old['login'] ?? $prefill, 'wide' => true ) );
		self::field( 'password', __( 'Password', 'ramsetu-leads' ), array( 'type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'wide' => true ) );
		echo '<div class="pt-row"><label class="rsl-check"><input type="checkbox" name="remember" value="1"><span>' . esc_html__( 'Keep me logged in', 'ramsetu-leads' ) . '</span></label>';
		echo '<a href="' . esc_url( rsl_portal_url( 'forgot' ) ) . '">' . esc_html__( 'Forgot password?', 'ramsetu-leads' ) . '</a></div>';
		echo '<button type="submit" class="btn btn--primary pt-btn-full">' . esc_html__( 'Log in', 'ramsetu-leads' ) . '</button></form>';
		echo '<div class="pt-auth__alt"><p><strong>' . esc_html__( 'New here?', 'ramsetu-leads' ) . '</strong> ' . esc_html__( 'CAs, DSAs and loan agents can register as a RamSetu channel partner.', 'ramsetu-leads' ) . '</p>';
		echo '<a class="btn btn--outline" href="' . esc_url( rsl_portal_url( 'register' ) ) . '">' . esc_html__( 'Register as Channel Partner', 'ramsetu-leads' ) . '</a></div>';
		self::auth_layout_close();
	}

	private static function view_register() {
		self::auth_layout_open( __( 'Register as a Channel Partner', 'ramsetu-leads' ), __( 'Your application is reviewed by RamSetu. Once approved, your unique Partner ID and a link to set your password are emailed to you.', 'ramsetu-leads' ) );
		self::register_form();
		echo '<p class="pt-auth__switch">' . esc_html__( 'Already registered?', 'ramsetu-leads' ) . ' <a href="' . esc_url( rsl_portal_url() ) . '">' . esc_html__( 'Log in', 'ramsetu-leads' ) . '</a></p>';
		self::auth_layout_close();
	}

	/** [ramsetu_partner_form] — the Become a Partner page. */
	public static function render_partner_form() {
		ob_start();
		echo '<div class="pt pt--embed" id="partner-form">';
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['registered'] ) ) {
			echo '<div class="pt-done pt-done--embed" role="status"><span class="pt-done__icon" aria-hidden="true">✓</span>';
			echo '<h3>' . esc_html__( 'Registered successfully!', 'ramsetu-leads' ) . '</h3>';
			echo '<p>' . esc_html__( 'Thank you for registering as a RamSetu partner. Our team will verify your details and get in touch.', 'ramsetu-leads' ) . '</p>';
			echo '<p>' . esc_html__( 'Once approved, you will receive an email with your unique Partner ID and a link to set your password.', 'ramsetu-leads' ) . '</p></div>';
		} else {
			self::msg();
			self::register_form();
		}
		echo '</div>';
		return ob_get_clean();
	}

	private static function register_form() {
		self::form_open( 'register' );
		echo '<input type="hidden" name="rsl_ts" value="' . esc_attr( time() ) . '"><div class="rsl-hp" aria-hidden="true"><label>Leave empty<input type="text" name="rsl_website" tabindex="-1" autocomplete="off"></label></div>';
		echo '<div class="rsl-grid">';
		self::field( 'full_name', __( 'Full name (as on PAN)', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'name' ) );
		self::field( 'mobile', __( 'Mobile number', 'ramsetu-leads' ), array( 'type' => 'tel', 'required' => true, 'autocomplete' => 'tel-national', 'inputmode' => 'numeric' ) );
		self::field( 'email', __( 'Email ID', 'ramsetu-leads' ), array( 'type' => 'email', 'required' => true, 'autocomplete' => 'email' ) );
		self::field( 'partner_type', __( 'You are a', 'ramsetu-leads' ), array( 'type' => 'select', 'required' => true, 'options' => rsl_partner_types() ) );
		self::field( 'firm_name', __( 'Firm / company name', 'ramsetu-leads' ), array( 'placeholder' => __( 'Optional', 'ramsetu-leads' ), 'autocomplete' => 'organization', 'wide' => true ) );
		self::field( 'city', __( 'City', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'address-level2' ) );
		self::field( 'district', __( 'District', 'ramsetu-leads' ), array( 'required' => true ) );
		self::field( 'state', __( 'State', 'ramsetu-leads' ), array( 'type' => 'select', 'required' => true, 'options' => array_combine( rsl_states(), rsl_states() ) ) );
		self::field( 'pan', __( 'PAN number', 'ramsetu-leads' ), array( 'required' => true, 'placeholder' => 'ABCDE1234F', 'maxlength' => 10, 'data-rsl-pan' => '1', 'autocapitalize' => 'characters', 'autocomplete' => 'off' ) );
		echo '</div>';
		self::field( 'consent', __( 'I confirm these details are correct and I agree to RamSetu verifying my PAN and contacting me about the channel partner programme. Incentives are paid as per RamSetu\'s partner terms, only on loans that are disbursed.', 'ramsetu-leads' ), array( 'type' => 'checkbox', 'required' => true ) );
		echo '<button type="submit" class="btn btn--primary pt-btn-full">' . esc_html__( 'Register as a Partner', 'ramsetu-leads' ) . '</button></form>';
	}

	private static function view_register_done() {
		self::auth_layout_open( __( 'Application received', 'ramsetu-leads' ), '' );
		echo '<div class="pt-done"><p>' . esc_html__( 'Thank you for registering as a RamSetu channel partner. Our team will verify your details and get in touch.', 'ramsetu-leads' ) . '</p>';
		echo '<p>' . esc_html__( 'Once approved, you will receive an email with your unique Partner ID and a link to set your password.', 'ramsetu-leads' ) . '</p>';
		echo '<a class="btn btn--outline" href="' . esc_url( home_url( '/' ) ) . '">' . esc_html__( 'Back to website', 'ramsetu-leads' ) . '</a></div>';
		self::auth_layout_close();
	}

	private static function view_forgot() {
		self::auth_layout_open( __( 'Forgot password', 'ramsetu-leads' ), __( 'Enter your Partner / Employee ID or registered email. We will email you a link to set a new password.', 'ramsetu-leads' ) );
		self::form_open( 'forgot' );
		self::field( 'login', __( 'ID or email', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'username', 'wide' => true ) );
		echo '<button type="submit" class="btn btn--primary pt-btn-full">' . esc_html__( 'Email me a link', 'ramsetu-leads' ) . '</button></form>';
		echo '<p class="pt-auth__switch"><a href="' . esc_url( rsl_portal_url() ) . '">' . esc_html__( 'Back to log in', 'ramsetu-leads' ) . '</a></p>';
		self::auth_layout_close();
	}

	private static function view_set_password() {
		// phpcs:disable WordPress.Security.NonceVerification
		$login = isset( $_GET['login'] ) ? sanitize_text_field( wp_unslash( $_GET['login'] ) ) : ( self::$old['login'] ?? '' );
		$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ( self::$old['key'] ?? '' );
		// phpcs:enable
		$user = check_password_reset_key( $key, $login );
		self::auth_layout_open( __( 'Set your password', 'ramsetu-leads' ), is_wp_error( $user ) ? '' : sprintf( __( 'ID: %s', 'ramsetu-leads' ), $login ) );
		if ( is_wp_error( $user ) ) {
			echo '<div class="pt-alert pt-alert--error">' . esc_html__( 'This link has expired or was already used.', 'ramsetu-leads' ) . '</div>';
			echo '<a class="btn btn--primary" href="' . esc_url( rsl_portal_url( 'forgot' ) ) . '">' . esc_html__( 'Get a new link', 'ramsetu-leads' ) . '</a>';
		} else {
			self::form_open( 'set-password' );
			printf( '<input type="hidden" name="login" value="%s"><input type="hidden" name="key" value="%s">', esc_attr( $login ), esc_attr( $key ) );
			echo '<input type="text" name="username" value="' . esc_attr( $login ) . '" autocomplete="username" hidden>';
			self::field( 'pass1', __( 'New password', 'ramsetu-leads' ), array( 'type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'wide' => true ) );
			self::field( 'pass2', __( 'Confirm new password', 'ramsetu-leads' ), array( 'type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'wide' => true ) );
			echo '<p class="pt-muted pt-small">' . esc_html__( 'At least 8 characters, with letters and numbers.', 'ramsetu-leads' ) . '</p>';
			echo '<button type="submit" class="btn btn--primary pt-btn-full">' . esc_html__( 'Save password', 'ramsetu-leads' ) . '</button></form>';
		}
		self::auth_layout_close();
	}

	/* ---------- logged in ---------- */

	private static function shell_open() {
		$user    = wp_get_current_user();
		$is_cp   = in_array( 'rs_channel_partner', (array) $user->roles, true );
		$current = self::view() ? self::view() : 'dashboard';
		if ( 'lead' === $current ) {
			$current = 'leads';
		}
		$nav = array(
			'dashboard' => __( 'Dashboard', 'ramsetu-leads' ),
			'submit'    => __( 'Submit Lead', 'ramsetu-leads' ),
			'leads'     => __( 'My Leads', 'ramsetu-leads' ),
			'profile'   => __( 'Profile', 'ramsetu-leads' ),
		);
		echo '<div class="pt-shell"><aside class="pt-nav"><div class="pt-nav__who"><span class="pt-nav__avatar" aria-hidden="true">' . esc_html( mb_strtoupper( mb_substr( $user->display_name, 0, 1 ) ) ) . '</span><div><strong>' . esc_html( $user->display_name ) . '</strong><span>' . esc_html( ( $is_cp ? __( 'Channel Partner', 'ramsetu-leads' ) : __( 'Employee', 'ramsetu-leads' ) ) . ' · ' . RSL_Partners::user_code( $user->ID ) ) . '</span></div></div><nav aria-label="' . esc_attr__( 'Portal', 'ramsetu-leads' ) . '"><ul>';
		foreach ( $nav as $k => $label ) {
			printf( '<li><a href="%s"%s>%s</a></li>', esc_url( rsl_portal_url( 'dashboard' === $k ? '' : $k ) ), $k === $current ? ' aria-current="page"' : '', esc_html( $label ) );
		}
		printf( '<li><a href="%s">%s</a></li>', esc_url( wp_nonce_url( rsl_portal_url( 'logout' ), 'rsl_logout' ) ), esc_html__( 'Log out', 'ramsetu-leads' ) );
		echo '</ul></nav></aside><div class="pt-main">';
		self::msg();
	}

	/** Leads visible to the current portal user. */
	private static function scope() {
		$user = wp_get_current_user();
		if ( in_array( 'rs_channel_partner', (array) $user->roles, true ) ) {
			$p = RSL_Partners::for_user( $user->ID );
			return array( 'partner_id' => $p ? (int) $p['id'] : -1 );
		}
		return array( 'submitted_by' => $user->ID );
	}

	private static function is_cp() {
		return in_array( 'rs_channel_partner', (array) wp_get_current_user()->roles, true );
	}

	private static function view_dashboard() {
		global $wpdb;
		$t     = RSL_DB::table( 'lead' );
		$scope = self::scope();
		$col   = key( $scope );
		$val   = (int) current( $scope );
		$row   = $wpdb->get_row( $wpdb->prepare(
			"SELECT COUNT(*) total,
				SUM(status IN ('assigned','contacted','in_progress')) progress,
				SUM(disbursement_status IN ('disbursed','partial')) disbursed,
				SUM(CASE WHEN incentive_status IN ('eligible','approved','paid') THEN incentive_amount ELSE 0 END) earned,
				SUM(CASE WHEN incentive_status = 'paid' THEN incentive_amount ELSE 0 END) paid
			FROM $t WHERE $col = %d", $val ), ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		echo '<div class="pt-head"><div><h1 class="pt-title">' . esc_html( sprintf( __( 'Welcome, %s', 'ramsetu-leads' ), wp_get_current_user()->first_name ?: wp_get_current_user()->display_name ) ) . '</h1><p class="pt-muted">' . esc_html__( 'Here is how your leads are doing.', 'ramsetu-leads' ) . '</p></div>';
		echo '<a class="btn btn--primary" href="' . esc_url( rsl_portal_url( 'submit' ) ) . '">+ ' . esc_html__( 'Submit a lead', 'ramsetu-leads' ) . '</a></div>';

		$stats = array(
			array( __( 'Leads submitted', 'ramsetu-leads' ), (int) $row['total'] ),
			array( __( 'In progress', 'ramsetu-leads' ), (int) $row['progress'] ),
			array( __( 'Disbursed', 'ramsetu-leads' ), (int) $row['disbursed'] ),
		);
		if ( self::is_cp() ) {
			$stats[] = array( __( 'Incentives earned', 'ramsetu-leads' ), rsl_format_inr( (float) $row['earned'] ), sprintf( __( '%s paid', 'ramsetu-leads' ), rsl_format_inr( (float) $row['paid'] ) ) );
		}
		echo '<div class="pt-stats">';
		foreach ( $stats as $s ) {
			printf( '<div class="pt-stat"><span class="pt-stat__n">%s</span><span class="pt-stat__l">%s</span>%s</div>', esc_html( $s[1] ), esc_html( $s[0] ), isset( $s[2] ) ? '<span class="pt-stat__s">' . esc_html( $s[2] ) . '</span>' : '' );
		}
		echo '</div>';

		$res = RSL_DB::query( 'lead', $scope + array( 'per_page' => 5 ) );
		echo '<div class="pt-card"><div class="pt-card__head"><h2>' . esc_html__( 'Recent leads', 'ramsetu-leads' ) . '</h2><a href="' . esc_url( rsl_portal_url( 'leads' ) ) . '">' . esc_html__( 'View all', 'ramsetu-leads' ) . ' →</a></div>';
		self::leads_table( $res['rows'] );
		echo '</div>';
	}

	private static function leads_table( array $rows ) {
		if ( ! $rows ) {
			echo '<p class="pt-empty">' . esc_html__( 'No leads yet. Submitted leads will appear here with their live status.', 'ramsetu-leads' ) . '</p>';
			return;
		}
		$cp = self::is_cp();
		echo '<div class="pt-table-wrap"><table class="pt-table"><thead><tr><th>' . esc_html__( 'Lead ID', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Customer', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Product', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Amount', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Submitted', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Status', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Loan stage', 'ramsetu-leads' ) . '</th>' . ( $cp ? '<th>' . esc_html__( 'Incentive', 'ramsetu-leads' ) . '</th>' : '' ) . '</tr></thead><tbody>';
		foreach ( $rows as $r ) {
			printf(
				'<tr><td data-label="%9$s"><a href="%1$s">%2$s</a></td><td data-label="%10$s">%3$s</td><td data-label="%11$s">%4$s</td><td data-label="%12$s">%5$s</td><td data-label="%13$s">%6$s</td><td data-label="%14$s"><span class="pt-badge pt-badge--%7$s">%8$s</span></td>',
				esc_url( rsl_portal_url( 'lead', array( 'id' => (int) $r['id'] ) ) ),
				esc_html( rsl_lead_code( $r['id'] ) ),
				esc_html( $r['full_name'] ),
				esc_html( rsl_label( rsl_product_labels(), $r['loan_category'] ) ),
				esc_html( rsl_format_inr( $r['loan_amount'] ) ),
				esc_html( date_i18n( 'd M Y', strtotime( $r['created_at'] ) ) ),
				esc_attr( $r['status'] ),
				esc_html( rsl_portal_status( $r['status'] ) ),
				esc_attr__( 'Lead ID', 'ramsetu-leads' ),
				esc_attr__( 'Customer', 'ramsetu-leads' ),
				esc_attr__( 'Product', 'ramsetu-leads' ),
				esc_attr__( 'Amount', 'ramsetu-leads' ),
				esc_attr__( 'Submitted', 'ramsetu-leads' ),
				esc_attr__( 'Status', 'ramsetu-leads' )
			);
			printf( '<td data-label="%s">%s</td>', esc_attr__( 'Loan stage', 'ramsetu-leads' ), esc_html( $r['disbursement_status'] ? rsl_label( rsl_disbursement_statuses(), $r['disbursement_status'] ) : '—' ) );
			if ( $cp ) {
				printf( '<td data-label="%s">%s</td>', esc_attr__( 'Incentive', 'ramsetu-leads' ), esc_html( $r['incentive_amount'] && ! in_array( $r['incentive_status'], array( '', 'na' ), true ) ? rsl_format_inr( $r['incentive_amount'] ) . ' · ' . rsl_label( rsl_incentive_statuses(), $r['incentive_status'] ) : '—' ) );
			}
			echo '</tr>';
		}
		echo '</tbody></table></div>';
	}

	private static function view_leads() {
		// phpcs:disable WordPress.Security.NonceVerification
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
		$search = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
		$page   = isset( $_GET['pg'] ) ? max( 1, (int) $_GET['pg'] ) : 1;
		// phpcs:enable
		$args = self::scope() + array( 'per_page' => 20, 'page' => $page, 'search' => $search );
		if ( isset( rsl_lead_statuses()[ $status ] ) ) {
			$args['status'] = $status;
		}
		$res = RSL_DB::query( 'lead', $args );

		echo '<div class="pt-head"><h1 class="pt-title">' . esc_html__( 'My Leads', 'ramsetu-leads' ) . '</h1><a class="btn btn--primary" href="' . esc_url( rsl_portal_url( 'submit' ) ) . '">+ ' . esc_html__( 'Submit a lead', 'ramsetu-leads' ) . '</a></div>';
		echo '<form class="pt-filters" method="get" action="' . esc_url( get_permalink() ) . '"><input type="hidden" name="view" value="leads">';
		echo '<label class="screen-reader-text" for="pt-q">' . esc_html__( 'Search', 'ramsetu-leads' ) . '</label><input id="pt-q" type="search" name="q" value="' . esc_attr( $search ) . '" placeholder="' . esc_attr__( 'Search name or mobile', 'ramsetu-leads' ) . '">';
		echo '<label class="screen-reader-text" for="pt-st">' . esc_html__( 'Status', 'ramsetu-leads' ) . '</label><select id="pt-st" name="status"><option value="">' . esc_html__( 'All statuses', 'ramsetu-leads' ) . '</option>';
		$seen = array();
		foreach ( rsl_lead_statuses() as $k => $v ) {
			$label = rsl_portal_status( $k );
			if ( isset( $seen[ $label ] ) ) {
				continue;
			}
			$seen[ $label ] = 1;
			printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $status, $k, false ), esc_html( $label ) );
		}
		echo '</select><button class="btn btn--outline" type="submit">' . esc_html__( 'Filter', 'ramsetu-leads' ) . '</button></form>';

		echo '<div class="pt-card">';
		self::leads_table( $res['rows'] );
		$pages = (int) ceil( $res['total'] / 20 );
		if ( $pages > 1 ) {
			echo '<nav class="pt-pages" aria-label="' . esc_attr__( 'Pages', 'ramsetu-leads' ) . '">';
			for ( $i = 1; $i <= $pages; $i++ ) {
				printf( '<a href="%s"%s>%d</a>', esc_url( rsl_portal_url( 'leads', array_filter( array( 'pg' => $i, 'status' => $status, 'q' => $search ) ) ) ), $i === $page ? ' aria-current="page"' : '', (int) $i );
			}
			echo '</nav>';
		}
		echo '</div>';
	}

	private static function view_lead() {
		// phpcs:ignore WordPress.Security.NonceVerification
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$r  = $id ? RSL_DB::get( 'lead', $id ) : null;
		$sc = self::scope();
		if ( ! $r || (int) $r[ key( $sc ) ] !== (int) current( $sc ) ) {
			echo '<div class="pt-card"><h1 class="pt-title">' . esc_html__( 'Lead not found', 'ramsetu-leads' ) . '</h1><p><a href="' . esc_url( rsl_portal_url( 'leads' ) ) . '">← ' . esc_html__( 'My Leads', 'ramsetu-leads' ) . '</a></p></div>';
			return;
		}
		echo '<p><a href="' . esc_url( rsl_portal_url( 'leads' ) ) . '">← ' . esc_html__( 'My Leads', 'ramsetu-leads' ) . '</a></p>';
		echo '<div class="pt-head"><div><p class="pt-muted pt-small">' . esc_html( rsl_lead_code( $r['id'] ) ) . '</p><h1 class="pt-title">' . esc_html( $r['full_name'] ) . '</h1></div><span class="pt-badge pt-badge--' . esc_attr( $r['status'] ) . ' pt-badge--lg">' . esc_html( rsl_portal_status( $r['status'] ) ) . '</span></div>';

		$stages = array(
			__( 'Credit', 'ramsetu-leads' )       => rsl_label( rsl_credit_statuses(), $r['credit_status'] ),
			__( 'Documents', 'ramsetu-leads' )    => rsl_label( rsl_document_statuses(), $r['document_status'] ),
			__( 'Loan stage', 'ramsetu-leads' )   => rsl_label( rsl_disbursement_statuses(), $r['disbursement_status'] ),
		);
		if ( self::is_cp() ) {
			$stages[ __( 'Incentive', 'ramsetu-leads' ) ] = $r['incentive_amount'] && ! in_array( $r['incentive_status'], array( '', 'na' ), true )
				? rsl_format_inr( $r['incentive_amount'] ) . ' · ' . rsl_label( rsl_incentive_statuses(), $r['incentive_status'] )
				: ( 'na' === $r['incentive_status'] ? rsl_label( rsl_incentive_statuses(), 'na' ) : __( 'Decided on disbursement', 'ramsetu-leads' ) );
		}
		echo '<div class="pt-stages">';
		foreach ( $stages as $k => $v ) {
			printf( '<div class="pt-stage"><span>%s</span><strong>%s</strong></div>', esc_html( $k ), esc_html( '' === $v || '—' === $v ? __( 'Not started', 'ramsetu-leads' ) : $v ) );
		}
		echo '</div>';

		$rows = array(
			__( 'Mobile', 'ramsetu-leads' )            => $r['phone'],
			__( 'Email', 'ramsetu-leads' )             => $r['email'],
			__( 'Co-applicant', 'ramsetu-leads' )      => trim( $r['co_applicant_name'] . ( $r['co_applicant_phone'] ? ' · ' . $r['co_applicant_phone'] : '' ) ),
			__( 'Location', 'ramsetu-leads' )          => trim( implode( ', ', array_filter( array( $r['city'], $r['district'], $r['state'] ) ) ) ),
			__( 'Customer profile', 'ramsetu-leads' )  => rsl_label( rsl_professions(), $r['profession'] ),
			__( 'Nature of business', 'ramsetu-leads' )=> $r['business_type'],
			__( 'Business vintage', 'ramsetu-leads' )  => null !== $r['business_vintage_years'] && '' !== $r['business_vintage_years'] ? sprintf( _n( '%d year', '%d years', (int) $r['business_vintage_years'], 'ramsetu-leads' ), (int) $r['business_vintage_years'] ) : '',
			__( 'Product', 'ramsetu-leads' )           => rsl_label( rsl_product_labels(), $r['loan_category'] ),
			__( 'Loan amount', 'ramsetu-leads' )       => rsl_format_inr( $r['loan_amount'] ),
			__( 'Collateral', 'ramsetu-leads' )        => rsl_label( rsl_collaterals(), $r['collateral'] ),
			__( 'Property value', 'ramsetu-leads' )    => $r['collateral_value'] ? rsl_format_inr( $r['collateral_value'] ) : '',
			__( 'Property location', 'ramsetu-leads' ) => $r['property_location'],
			__( 'Existing loans', 'ramsetu-leads' )    => $r['existing_loans'],
			__( 'Submitted on', 'ramsetu-leads' )      => date_i18n( 'd M Y, g:i A', strtotime( $r['created_at'] ) ),
			__( 'Last updated', 'ramsetu-leads' )      => date_i18n( 'd M Y, g:i A', strtotime( $r['updated_at'] ) ),
		);
		echo '<div class="pt-card"><h2>' . esc_html__( 'Details submitted', 'ramsetu-leads' ) . '</h2><dl class="pt-dl">';
		foreach ( $rows as $k => $v ) {
			if ( '' === (string) $v || '—' === $v ) {
				continue;
			}
			printf( '<div><dt>%s</dt><dd>%s</dd></div>', esc_html( $k ), nl2br( esc_html( $v ) ) );
		}
		echo '</dl></div>';
	}

	private static function view_submit() {
		echo '<div class="pt-head"><div><h1 class="pt-title">' . esc_html__( 'Submit a Lead', 'ramsetu-leads' ) . '</h1><p class="pt-muted">' . esc_html__( 'Share the customer\'s details. The RamSetu team takes it forward and you can track progress under My Leads.', 'ramsetu-leads' ) . '</p></div></div>';
		self::form_open( 'submit_lead', 'pt-card' );

		echo '<fieldset class="pt-fs"><legend>' . esc_html__( 'Customer', 'ramsetu-leads' ) . '</legend><div class="rsl-grid rsl-grid--3">';
		self::field( 'full_name', __( 'Customer name', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'off' ) );
		self::field( 'phone', __( 'Mobile number', 'ramsetu-leads' ), array( 'type' => 'tel', 'required' => true, 'inputmode' => 'numeric', 'autocomplete' => 'off' ) );
		self::field( 'email', __( 'Email ID', 'ramsetu-leads' ), array( 'type' => 'email', 'placeholder' => __( 'Optional', 'ramsetu-leads' ), 'autocomplete' => 'off' ) );
		echo '</div></fieldset>';

		echo '<fieldset class="pt-fs"><legend>' . esc_html__( 'Co-applicant (optional)', 'ramsetu-leads' ) . '</legend><div class="rsl-grid rsl-grid--3">';
		self::field( 'co_applicant_name', __( 'Name', 'ramsetu-leads' ), array( 'autocomplete' => 'off' ) );
		self::field( 'co_applicant_phone', __( 'Mobile number', 'ramsetu-leads' ), array( 'type' => 'tel', 'inputmode' => 'numeric', 'autocomplete' => 'off' ) );
		self::field( 'co_applicant_email', __( 'Email ID', 'ramsetu-leads' ), array( 'type' => 'email', 'autocomplete' => 'off' ) );
		echo '</div></fieldset>';

		echo '<fieldset class="pt-fs"><legend>' . esc_html__( 'Location', 'ramsetu-leads' ) . '</legend><div class="rsl-grid rsl-grid--3">';
		self::field( 'city', __( 'City', 'ramsetu-leads' ), array( 'required' => true ) );
		self::field( 'district', __( 'District', 'ramsetu-leads' ), array( 'required' => true ) );
		self::field( 'state', __( 'State', 'ramsetu-leads' ), array( 'type' => 'select', 'required' => true, 'options' => array_combine( rsl_states(), rsl_states() ) ) );
		echo '</div></fieldset>';

		echo '<fieldset class="pt-fs"><legend>' . esc_html__( 'Profile & loan', 'ramsetu-leads' ) . '</legend><div class="rsl-grid rsl-grid--3">';
		self::field( 'profession', __( 'Customer profile', 'ramsetu-leads' ), array( 'type' => 'select', 'required' => true, 'options' => rsl_portal_profiles() ) );
		self::field( 'business_type', __( 'Nature of business', 'ramsetu-leads' ), array( 'type' => 'select', 'required' => true, 'options' => rsl_business_segments(), 'placeholder' => __( 'Industry / segment', 'ramsetu-leads' ) ) );
		self::field( 'business_vintage_years', __( 'Business vintage (years)', 'ramsetu-leads' ), array( 'type' => 'number', 'min' => 0, 'max' => 99, 'inputmode' => 'numeric', 'placeholder' => __( 'Not needed for salaried', 'ramsetu-leads' ) ) );
		self::field( 'loan_category', __( 'Product applying for', 'ramsetu-leads' ), array( 'type' => 'select', 'required' => true, 'options' => rsl_portal_products() ) );
		self::field( 'loan_amount', __( 'Loan amount (₹)', 'ramsetu-leads' ), array( 'required' => true, 'inputmode' => 'numeric', 'data-rsl-money' => '1', 'placeholder' => 'e.g. 25,00,000' ) );
		echo '</div></fieldset>';

		echo '<fieldset class="pt-fs"><legend>' . esc_html__( 'Collateral property', 'ramsetu-leads' ) . '</legend><div class="rsl-grid rsl-grid--3">';
		self::field( 'collateral', __( 'Property type', 'ramsetu-leads' ), array( 'type' => 'select', 'required' => true, 'options' => rsl_collaterals() ) );
		self::field( 'collateral_value', __( 'Property value (₹)', 'ramsetu-leads' ), array( 'required' => true, 'inputmode' => 'numeric', 'data-rsl-money' => '1', 'placeholder' => 'e.g. 60,00,000' ) );
		self::field( 'property_location', __( 'Property location', 'ramsetu-leads' ), array( 'required' => true, 'placeholder' => __( 'Area, city', 'ramsetu-leads' ) ) );
		echo '</div></fieldset>';

		echo '<fieldset class="pt-fs"><legend>' . esc_html__( 'Other details', 'ramsetu-leads' ) . '</legend><div class="rsl-grid">';
		self::field( 'existing_loans', __( 'Existing loans', 'ramsetu-leads' ), array( 'type' => 'textarea', 'placeholder' => __( 'Lender, loan type, outstanding amount, EMI (or "None")', 'ramsetu-leads' ) ) );
		self::field( 'remarks', __( 'Remarks for RamSetu', 'ramsetu-leads' ), array( 'type' => 'textarea', 'placeholder' => __( 'Optional', 'ramsetu-leads' ) ) );
		echo '</div></fieldset>';

		self::field( 'consent', __( 'I confirm that the customer has agreed to share these details with RamSetu Financial Services to be contacted about this loan requirement.', 'ramsetu-leads' ), array( 'type' => 'checkbox', 'required' => true ) );
		echo '<div class="rsl-actions"><button type="submit" class="btn btn--primary">' . esc_html__( 'Submit lead', 'ramsetu-leads' ) . ' →</button></div></form>';
	}

	private static function view_profile() {
		$user = wp_get_current_user();
		$p    = RSL_Partners::for_user( $user->ID );
		echo '<div class="pt-head"><h1 class="pt-title">' . esc_html__( 'Profile', 'ramsetu-leads' ) . '</h1></div>';
		$rows = array(
			__( 'ID', 'ramsetu-leads' )     => RSL_Partners::user_code( $user->ID ),
			__( 'Name', 'ramsetu-leads' )   => $user->display_name,
			__( 'Email', 'ramsetu-leads' )  => $user->user_email,
			__( 'Mobile', 'ramsetu-leads' ) => $p ? $p['mobile'] : get_user_meta( $user->ID, 'rsl_mobile', true ),
		);
		if ( $p ) {
			$rows[ __( 'Partner type', 'ramsetu-leads' ) ] = rsl_label( rsl_partner_types(), $p['partner_type'] );
			$rows[ __( 'Firm', 'ramsetu-leads' ) ]         = $p['firm_name'];
			$rows[ __( 'Location', 'ramsetu-leads' ) ]     = $p['city'] . ', ' . $p['district'] . ', ' . $p['state'];
			$rows[ __( 'PAN', 'ramsetu-leads' ) ]          = rsl_mask( $p['pan'] ) . ( $p['pan_verified'] ? ' · ' . __( 'Verified', 'ramsetu-leads' ) : '' );
		}
		echo '<div class="pt-two"><div class="pt-card"><h2>' . esc_html__( 'Your details', 'ramsetu-leads' ) . '</h2><dl class="pt-dl pt-dl--1">';
		foreach ( $rows as $k => $v ) {
			if ( '' !== (string) $v ) {
				printf( '<div><dt>%s</dt><dd>%s</dd></div>', esc_html( $k ), esc_html( $v ) );
			}
		}
		echo '</dl><p class="pt-muted pt-small">' . esc_html__( 'To change these details, please contact RamSetu.', 'ramsetu-leads' ) . '</p></div>';
		echo '<div class="pt-card"><h2>' . esc_html__( 'Change password', 'ramsetu-leads' ) . '</h2>';
		self::form_open( 'change_password' );
		self::field( 'current', __( 'Current password', 'ramsetu-leads' ), array( 'type' => 'password', 'required' => true, 'autocomplete' => 'current-password', 'wide' => true ) );
		self::field( 'pass1', __( 'New password', 'ramsetu-leads' ), array( 'type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'wide' => true ) );
		self::field( 'pass2', __( 'Confirm new password', 'ramsetu-leads' ), array( 'type' => 'password', 'required' => true, 'autocomplete' => 'new-password', 'wide' => true ) );
		echo '<button type="submit" class="btn btn--primary">' . esc_html__( 'Change password', 'ramsetu-leads' ) . '</button></form></div></div>';
	}
}
