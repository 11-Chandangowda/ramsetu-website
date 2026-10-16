<?php
/**
 * Channel partner and employee accounts.
 *
 * Partner flow:
 *   1. Registers on the portal (status "pending"); PAN format is checked.
 *   2. RamSetu verifies the PAN (provider dashboard for now) and ticks "PAN verified".
 *   3. RamSetu approves → a WordPress user is created with login = partner code
 *      (e.g. RSCP00001) and a "set your password" link is emailed.
 *   4. Partner logs in on the portal with that ID and password.
 *
 * Employees are added by RamSetu (code e.g. RSEMP0001) and get the same email.
 */

defined( 'ABSPATH' ) || exit;

class RSL_Partners {

	public static function init() {
		// Portal users never see wp-admin or the admin bar.
		add_action( 'admin_init', array( __CLASS__, 'block_admin' ) );
		add_filter( 'show_admin_bar', function ( $show ) {
			return RSL_Roles::is_portal_user() ? false : $show;
		} );
		// Suspended accounts cannot log in anywhere.
		add_filter( 'authenticate', array( __CLASS__, 'block_suspended' ), 30, 1 );
		// Portal users who end up on wp-login.php are sent back to the portal after login.
		add_filter( 'login_redirect', function ( $url, $requested, $user ) {
			return ( $user instanceof WP_User && RSL_Roles::is_portal_user( $user ) ) ? rsl_portal_url() : $url;
		}, 20, 3 );
		// Don't email the site admin every time a partner / employee sets their password.
		add_filter( 'wp_password_change_notification_email', function ( $email, $user ) {
			if ( $user instanceof WP_User && RSL_Roles::is_portal_user( $user ) ) {
				$email['to'] = '';
			}
			return $email;
		}, 10, 2 );
		// Password-reset emails from wp-login.php point portal users to the portal.
		add_filter( 'retrieve_password_message', array( __CLASS__, 'reset_message' ), 10, 4 );
	}

	public static function block_admin() {
		global $pagenow;
		if ( wp_doing_ajax() || ( defined( 'DOING_CRON' ) && DOING_CRON ) || 'admin-post.php' === $pagenow ) {
			return;
		}
		if ( RSL_Roles::is_portal_user() ) {
			wp_safe_redirect( rsl_portal_url() );
			exit;
		}
	}

	public static function block_suspended( $user ) {
		if ( $user instanceof WP_User && get_user_meta( $user->ID, 'rsl_suspended', true ) ) {
			return new WP_Error( 'rsl_suspended', __( 'This account is not active. Please contact RamSetu.', 'ramsetu-leads' ) );
		}
		return $user;
	}

	/* ------------------------------------------------------------------ */
	/* Registration                                                        */
	/* ------------------------------------------------------------------ */

	/** Validates a registration. Returns array( data, errors ). */
	public static function validate_registration( array $p ) {
		$e = array();
		$d = array(
			'full_name'    => sanitize_text_field( $p['full_name'] ?? '' ),
			'mobile'       => rsl_normalize_phone( $p['mobile'] ?? '' ),
			'email'        => sanitize_email( $p['email'] ?? '' ),
			'firm_name'    => sanitize_text_field( $p['firm_name'] ?? '' ),
			'partner_type' => sanitize_key( $p['partner_type'] ?? '' ),
			'city'         => sanitize_text_field( $p['city'] ?? '' ),
			'district'     => sanitize_text_field( $p['district'] ?? '' ),
			'state'        => sanitize_text_field( $p['state'] ?? '' ),
			'pan'          => strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) ( $p['pan'] ?? '' ) ) ),
		);
		if ( mb_strlen( $d['full_name'] ) < 3 ) {
			$e['full_name'] = __( 'Enter your full name as on PAN.', 'ramsetu-leads' );
		}
		if ( ! $d['mobile'] ) {
			$e['mobile'] = __( 'Enter a valid 10-digit mobile number.', 'ramsetu-leads' );
		}
		if ( ! is_email( $d['email'] ) ) {
			$e['email'] = __( 'Enter a valid email address. Your login details are sent here.', 'ramsetu-leads' );
		}
		if ( ! isset( rsl_partner_types()[ $d['partner_type'] ] ) ) {
			$e['partner_type'] = __( 'Select what best describes you.', 'ramsetu-leads' );
		}
		if ( '' === $d['city'] ) {
			$e['city'] = __( 'Enter your city.', 'ramsetu-leads' );
		}
		if ( '' === $d['district'] ) {
			$e['district'] = __( 'Enter your district.', 'ramsetu-leads' );
		}
		if ( ! in_array( $d['state'], rsl_states(), true ) ) {
			$e['state'] = __( 'Select your state.', 'ramsetu-leads' );
		}
		if ( ! rsl_valid_pan( $d['pan'] ) ) {
			$e['pan'] = __( 'Enter a valid PAN, e.g. ABCDE1234F.', 'ramsetu-leads' );
		}
		if ( empty( $p['consent'] ) ) {
			$e['consent'] = __( 'Please accept to continue.', 'ramsetu-leads' );
		}

		// One application per PAN / mobile (unless the earlier one was rejected).
		if ( ! $e ) {
			global $wpdb;
			$t   = RSL_DB::table( 'partner' );
			$dup = $wpdb->get_row( $wpdb->prepare( "SELECT id, status FROM $t WHERE ( pan = %s OR mobile = %s ) AND status <> 'rejected' LIMIT 1", $d['pan'], $d['mobile'] ), ARRAY_A );
			if ( $dup ) {
				$e['_form'] = 'approved' === $dup['status']
					? __( 'You are already registered as a RamSetu partner. Please log in, or use "Forgot password".', 'ramsetu-leads' )
					: __( 'An application with this PAN or mobile number is already under review. Our team will contact you soon.', 'ramsetu-leads' );
			}
		}
		return array( $d, $e );
	}

	public static function create_application( array $d, $ip = '' ) {
		$d['status']     = 'pending';
		$d['ip_address'] = $ip;
		$id              = RSL_DB::insert( 'partner', $d );
		if ( $id ) {
			self::notify_team( sprintf( 'New channel partner registration: %s (%s)', $d['full_name'], rsl_label( rsl_partner_types(), $d['partner_type'] ) ), array(
				'Name'   => $d['full_name'],
				'Type'   => rsl_label( rsl_partner_types(), $d['partner_type'] ),
				'Firm'   => $d['firm_name'] ?: '—',
				'Mobile' => $d['mobile'],
				'Email'  => $d['email'],
				'Place'  => $d['city'] . ', ' . $d['district'] . ', ' . $d['state'],
				'PAN'    => rsl_mask( $d['pan'] ),
			), admin_url( 'admin.php?page=rsl-partners&view=' . $id ), __( 'Review application', 'ramsetu-leads' ) );
		}
		return $id;
	}

	/* ------------------------------------------------------------------ */
	/* Approval                                                            */
	/* ------------------------------------------------------------------ */

	public static function partner_code( $id ) {
		return 'RSCP' . str_pad( (string) (int) $id, 5, '0', STR_PAD_LEFT );
	}

	/** Approves an application: creates the login and emails the set-password link. */
	public static function approve( $id ) {
		$p = RSL_DB::get( 'partner', $id );
		if ( ! $p || 'approved' === $p['status'] ) {
			return new WP_Error( 'rsl_state', __( 'This application cannot be approved.', 'ramsetu-leads' ) );
		}
		$code    = $p['partner_code'] ? $p['partner_code'] : self::partner_code( $id );
		$user_id = (int) $p['user_id'];

		if ( ! $user_id ) {
			$existing = get_user_by( 'email', $p['email'] );
			if ( $existing && ! RSL_Roles::is_portal_user( $existing ) ) {
				return new WP_Error( 'rsl_email', __( 'This email already belongs to a website user (e.g. staff). Ask the partner for a different email address.', 'ramsetu-leads' ) );
			}
			$user_id = $existing ? $existing->ID : wp_insert_user( array(
				'user_login'   => $code,
				'user_email'   => $p['email'],
				'user_pass'    => wp_generate_password( 32, true, true ),
				'display_name' => $p['full_name'],
				'first_name'   => $p['full_name'],
				'role'         => 'rs_channel_partner',
			) );
			if ( is_wp_error( $user_id ) ) {
				return $user_id;
			}
		}
		update_user_meta( $user_id, 'rsl_partner_id', (int) $id );
		update_user_meta( $user_id, 'rsl_code', $code );
		update_user_meta( $user_id, 'rsl_mobile', $p['mobile'] );
		delete_user_meta( $user_id, 'rsl_suspended' );

		RSL_DB::update( 'partner', $id, array(
			'status'       => 'approved',
			'partner_code' => $code,
			'user_id'      => $user_id,
			'approved_by'  => get_current_user_id(),
			'approved_at'  => current_time( 'mysql' ),
		) );
		RSL_DB::log( 'partner', $id, 'approved', 'Partner ID ' . $code );
		self::send_welcome( $user_id, 'partner' );
		return $user_id;
	}

	public static function set_status( $id, $status, $note = '' ) {
		$p = RSL_DB::get( 'partner', $id );
		if ( ! $p || ! isset( rsl_partner_statuses()[ $status ] ) ) {
			return false;
		}
		RSL_DB::update( 'partner', $id, array( 'status' => $status ) );
		RSL_DB::log( 'partner', $id, 'status', rsl_label( rsl_partner_statuses(), $status ) . ( $note ? ": $note" : '' ) );
		if ( $p['user_id'] ) {
			if ( 'suspended' === $status || 'rejected' === $status ) {
				update_user_meta( $p['user_id'], 'rsl_suspended', 1 );
				WP_Session_Tokens::get_instance( (int) $p['user_id'] )->destroy_all();
			} elseif ( 'approved' === $status ) {
				delete_user_meta( $p['user_id'], 'rsl_suspended' );
			}
		}
		if ( 'rejected' === $status && is_email( $p['email'] ) ) {
			self::mail( $p['email'], __( 'Your RamSetu partner application', 'ramsetu-leads' ),
				'<p>' . sprintf( esc_html__( 'Dear %s,', 'ramsetu-leads' ), esc_html( $p['full_name'] ) ) . '</p>'
				. '<p>' . esc_html__( 'Thank you for your interest in partnering with RamSetu. We are unable to approve your application at this time.', 'ramsetu-leads' ) . '</p>'
				. ( $note ? '<p>' . nl2br( esc_html( $note ) ) . '</p>' : '' )
				. '<p>' . esc_html__( 'For any questions, please contact your nearest RamSetu branch.', 'ramsetu-leads' ) . '</p>' );
		}
		return true;
	}

	public static function set_pan_verified( $id, $verified, $name_on_pan = '' ) {
		RSL_DB::update( 'partner', $id, array(
			'pan_verified'    => $verified ? 1 : 0,
			'pan_verified_by' => $verified ? get_current_user_id() : null,
			'pan_verified_at' => $verified ? current_time( 'mysql' ) : null,
			'pan_name'        => $verified ? $name_on_pan : '',
		) );
		RSL_DB::log( 'partner', $id, 'pan', $verified ? 'PAN marked verified' . ( $name_on_pan ? " (name on PAN: $name_on_pan)" : '' ) : 'PAN verification removed' );
	}

	/** Partner row for a logged-in user (or null). */
	public static function for_user( $user_id ) {
		$pid = (int) get_user_meta( $user_id, 'rsl_partner_id', true );
		return $pid ? RSL_DB::get( 'partner', $pid ) : null;
	}

	/* ------------------------------------------------------------------ */
	/* Employees                                                           */
	/* ------------------------------------------------------------------ */

	public static function create_employee( $name, $email, $mobile ) {
		if ( mb_strlen( $name ) < 2 || ! is_email( $email ) ) {
			return new WP_Error( 'rsl_invalid', __( 'Enter a name and a valid email.', 'ramsetu-leads' ) );
		}
		if ( email_exists( $email ) ) {
			return new WP_Error( 'rsl_email', __( 'A user with this email already exists.', 'ramsetu-leads' ) );
		}
		$n = (int) get_option( 'rsl_employee_seq', 0 ) + 1;
		do {
			$code = 'RSEMP' . str_pad( (string) $n, 4, '0', STR_PAD_LEFT );
			$n++;
		} while ( username_exists( $code ) );
		update_option( 'rsl_employee_seq', $n - 1 );

		$user_id = wp_insert_user( array(
			'user_login'   => $code,
			'user_email'   => $email,
			'user_pass'    => wp_generate_password( 32, true, true ),
			'display_name' => $name,
			'first_name'   => $name,
			'role'         => 'rs_employee',
		) );
		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}
		update_user_meta( $user_id, 'rsl_code', $code );
		update_user_meta( $user_id, 'rsl_mobile', $mobile );
		self::send_welcome( $user_id, 'employee' );
		return $user_id;
	}

	public static function user_code( $user_id ) {
		$code = get_user_meta( $user_id, 'rsl_code', true );
		if ( ! $code ) {
			$u    = get_userdata( $user_id );
			$code = $u ? $u->user_login : '';
		}
		return $code;
	}

	/* ------------------------------------------------------------------ */
	/* Emails                                                              */
	/* ------------------------------------------------------------------ */

	public static function set_password_url( WP_User $user ) {
		$key = get_password_reset_key( $user );
		if ( is_wp_error( $key ) ) {
			return '';
		}
		return rsl_portal_url( 'set-password', array( 'login' => rawurlencode( $user->user_login ), 'key' => $key ) );
	}

	/** Welcome email with the unique ID and a link to set the password. */
	public static function send_welcome( $user_id, $kind ) {
		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return;
		}
		$link  = self::set_password_url( $user );
		$code  = self::user_code( $user_id );
		$title = 'partner' === $kind ? __( 'Welcome to the RamSetu Partner Portal', 'ramsetu-leads' ) : __( 'Your RamSetu employee login', 'ramsetu-leads' );
		$html  = '<p>' . sprintf( esc_html__( 'Dear %s,', 'ramsetu-leads' ), esc_html( $user->display_name ) ) . '</p>'
			. '<p>' . ( 'partner' === $kind
				? esc_html__( 'Your channel partner registration has been approved. Your unique Partner ID is:', 'ramsetu-leads' )
				: esc_html__( 'An account has been created for you on the RamSetu portal. Your unique Employee ID is:', 'ramsetu-leads' ) ) . '</p>'
			. '<p style="font-size:22px;font-weight:bold;letter-spacing:1px;color:#1A45A5">' . esc_html( $code ) . '</p>'
			. '<p>' . esc_html__( 'Set your password using the button below. The link can be used once and expires in 24 hours.', 'ramsetu-leads' ) . '</p>'
			. '<p><a href="' . esc_url( $link ) . '" style="background:#B95A26;color:#fff;padding:12px 20px;border-radius:6px;text-decoration:none;display:inline-block">' . esc_html__( 'Set my password', 'ramsetu-leads' ) . '</a></p>'
			. '<p>' . sprintf( esc_html__( 'After that, log in any time at %s with your ID and password.', 'ramsetu-leads' ), '<a href="' . esc_url( rsl_portal_url() ) . '">' . esc_html( rsl_portal_url() ) . '</a>' ) . '</p>'
			. '<p style="color:#666;font-size:12px">' . esc_html__( 'RamSetu will never ask for your password by phone, SMS or email.', 'ramsetu-leads' ) . '</p>';
		self::mail( $user->user_email, $title, $html );
	}

	public static function send_reset( WP_User $user ) {
		$link = self::set_password_url( $user );
		$html = '<p>' . sprintf( esc_html__( 'Dear %s,', 'ramsetu-leads' ), esc_html( $user->display_name ) ) . '</p>'
			. '<p>' . sprintf( esc_html__( 'We received a request to reset the password for ID %s.', 'ramsetu-leads' ), '<strong>' . esc_html( self::user_code( $user->ID ) ) . '</strong>' ) . '</p>'
			. '<p><a href="' . esc_url( $link ) . '" style="background:#B95A26;color:#fff;padding:12px 20px;border-radius:6px;text-decoration:none;display:inline-block">' . esc_html__( 'Reset my password', 'ramsetu-leads' ) . '</a></p>'
			. '<p style="color:#666;font-size:12px">' . esc_html__( 'If you did not ask for this, you can ignore this email. The link expires in 24 hours.', 'ramsetu-leads' ) . '</p>';
		self::mail( $user->user_email, __( 'Reset your RamSetu portal password', 'ramsetu-leads' ), $html );
	}

	public static function reset_message( $message, $key, $login, $user ) {
		if ( $user instanceof WP_User && RSL_Roles::is_portal_user( $user ) ) {
			return sprintf( "%s\n\n%s\n", __( 'To set a new password for your RamSetu portal account, open this link:', 'ramsetu-leads' ), rsl_portal_url( 'set-password', array( 'login' => rawurlencode( $login ), 'key' => $key ) ) );
		}
		return $message;
	}

	public static function notify_team( $subject, array $rows, $link, $link_text ) {
		$to = array_filter( array_map( 'trim', explode( ',', (string) rsl_settings()['notify_emails'] ) ), 'is_email' );
		if ( ! $to ) {
			return;
		}
		$html = '<table cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%">';
		foreach ( $rows as $k => $v ) {
			$html .= '<tr><td style="border-bottom:1px solid #eee;color:#666;width:35%">' . esc_html( $k ) . '</td><td style="border-bottom:1px solid #eee"><strong>' . nl2br( esc_html( (string) $v ) ) . '</strong></td></tr>';
		}
		$html .= '</table><p style="margin:20px 0"><a href="' . esc_url( $link ) . '" style="background:#B95A26;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none">' . esc_html( $link_text ) . '</a></p>';
		self::mail( $to, $subject, $html );
	}

	public static function mail( $to, $subject, $inner_html ) {
		$html = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#222;max-width:560px;line-height:1.5">'
			. '<p style="font-size:20px;font-weight:bold;margin:0 0 16px"><span style="color:#F17934">Ram</span><span style="color:#1A45A5">Setu</span></p>'
			. $inner_html
			. '<p style="color:#999;font-size:12px;margin-top:24px">RamSetu Financial Service Private Limited</p></div>';
		wp_mail( $to, '[RamSetu] ' . $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}
}
