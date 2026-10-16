<?php
/**
 * Public forms: markup (shortcodes) + submission handling.
 *
 *   [ramsetu_apply_form]     – Apply Now (creates a Lead)
 *   [ramsetu_referral_form]  – Become a Partner (creates a Referral log entry)
 *   [ramsetu_callback_form]  – Request a Call Back (creates a Call-back log entry)
 *
 * Submissions go through admin-ajax (JS) with a normal POST fallback, so forms
 * still work if JavaScript fails.
 */

defined( 'ABSPATH' ) || exit;

class RSL_Forms {

	const TYPES = array( 'apply', 'referral', 'callback' );

	public static function init() {
		add_shortcode( 'ramsetu_apply_form', function ( $atts ) {
			return self::render( 'apply', shortcode_atts( array( 'category' => '', 'id' => 'rsl-apply' ), $atts ) );
		} );
		add_shortcode( 'ramsetu_referral_form', function ( $atts ) {
			return self::render( 'referral', shortcode_atts( array( 'id' => 'rsl-referral' ), $atts ) );
		} );
		add_shortcode( 'ramsetu_callback_form', function ( $atts ) {
			return self::render( 'callback', shortcode_atts( array( 'id' => 'rsl-callback' ), $atts ) );
		} );

		add_action( 'wp_ajax_rsl_submit', array( __CLASS__, 'handle' ) );
		add_action( 'wp_ajax_nopriv_rsl_submit', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_rsl_submit', array( __CLASS__, 'handle' ) );
		add_action( 'admin_post_nopriv_rsl_submit', array( __CLASS__, 'handle' ) );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );
	}

	public static function assets() {
		wp_register_script( 'rsl-forms', RSL_URL . 'assets/forms.js', array(), RSL_VERSION, true );
		wp_localize_script( 'rsl-forms', 'RSL', array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'i18n'    => array(
				'sending' => __( 'Submitting…', 'ramsetu-leads' ),
				'error'   => __( 'Something went wrong. Please try again or call us.', 'ramsetu-leads' ),
				'fix'     => __( 'Please check the highlighted fields.', 'ramsetu-leads' ),
			),
		) );
		wp_enqueue_script( 'rsl-forms' );
	}

	/* ------------------------------------------------------------------ */
	/* Markup                                                              */
	/* ------------------------------------------------------------------ */

	public static function render( $type, $atts = array() ) {
		$id      = sanitize_html_class( $atts['id'] ?? 'rsl-' . $type );
		$success = isset( $_GET['rsl_done'] ) && sanitize_key( $_GET['rsl_done'] ) === $type; // phpcs:ignore WordPress.Security.NonceVerification
		ob_start();
		?>
		<form class="rsl-form rsl-form--<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" novalidate data-rsl-form>
			<input type="hidden" name="action" value="rsl_submit">
			<input type="hidden" name="rsl_type" value="<?php echo esc_attr( $type ); ?>">
			<input type="hidden" name="rsl_nonce" value="<?php echo esc_attr( wp_create_nonce( 'rsl_submit_' . $type ) ); ?>">
			<input type="hidden" name="rsl_ts" value="<?php echo esc_attr( time() ); ?>">
			<input type="hidden" name="page_url" value="">
			<div class="rsl-hp" aria-hidden="true"><label>Leave this empty<input type="text" name="rsl_website" tabindex="-1" autocomplete="off"></label></div>

			<div class="rsl-alert" role="status" aria-live="polite"<?php echo $success ? '' : ' hidden'; ?>>
				<?php if ( $success ) { self::success_html( $type ); } ?>
			</div>

			<div class="rsl-grid">
			<?php
			switch ( $type ) {
				case 'apply':
					self::field( 'full_name', __( 'Full Name', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'name', 'placeholder' => __( 'Enter full name', 'ramsetu-leads' ) ) );
					self::field( 'phone', __( 'Mobile Number', 'ramsetu-leads' ), array( 'required' => true, 'type' => 'tel', 'autocomplete' => 'tel-national', 'placeholder' => __( '10-digit mobile number', 'ramsetu-leads' ), 'inputmode' => 'numeric' ) );
					self::field( 'email', __( 'Email ID', 'ramsetu-leads' ), array( 'type' => 'email', 'autocomplete' => 'email', 'placeholder' => __( 'Optional', 'ramsetu-leads' ) ) );
					self::field( 'address', __( 'Address', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'street-address', 'placeholder' => __( 'House / street / area', 'ramsetu-leads' ), 'wide' => true ) );
					self::field( 'city', __( 'City', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'address-level2', 'placeholder' => __( 'e.g. Tumakuru', 'ramsetu-leads' ) ) );
					self::select( 'state', __( 'State', 'ramsetu-leads' ), array_combine( rsl_states(), rsl_states() ), array( 'required' => true, 'placeholder' => __( 'Select state', 'ramsetu-leads' ), 'autocomplete' => 'address-level1' ) );
					self::field( 'pincode', __( 'Pincode', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'postal-code', 'inputmode' => 'numeric', 'placeholder' => __( '6-digit pincode', 'ramsetu-leads' ), 'data-rsl-digits' => '6' ) );
					self::select( 'profession', __( 'Profile', 'ramsetu-leads' ), rsl_profiles(), array( 'required' => true, 'placeholder' => __( 'Select profile', 'ramsetu-leads' ) ) );
					self::field( 'monthly_income', __( 'Monthly Income (₹)', 'ramsetu-leads' ), array( 'required' => true, 'inputmode' => 'numeric', 'placeholder' => __( 'e.g. 35,000', 'ramsetu-leads' ), 'data-rsl-money' => '1', 'show_if' => 'profession=salaried' ) );
					self::field( 'business_type', __( 'Business / Industry', 'ramsetu-leads' ), array( 'required' => true, 'placeholder' => __( 'e.g. Construction, Dairy, Manufacturing, Boutique', 'ramsetu-leads' ) ) );
					self::field( 'pan', __( 'PAN Number', 'ramsetu-leads' ), array( 'required' => true, 'placeholder' => __( 'e.g. ABCDE1234F', 'ramsetu-leads' ), 'autocomplete' => 'off', 'data-rsl-pan' => '1', 'maxlength' => 10, 'autocapitalize' => 'characters', 'spellcheck' => 'false' ) );
					self::field( 'aadhaar', __( 'Aadhaar Number', 'ramsetu-leads' ), array( 'placeholder' => __( 'Optional, 12-digit Aadhaar number', 'ramsetu-leads' ), 'autocomplete' => 'off', 'inputmode' => 'numeric', 'data-rsl-digits' => '12', 'data-rsl-aadhaar' => '1', 'maxlength' => 12 ) );
					self::select( 'loan_category', __( 'Loan Product Required', 'ramsetu-leads' ), rsl_public_loan_categories(), array( 'required' => true, 'placeholder' => __( 'Select loan product', 'ramsetu-leads' ), 'selected' => $atts['category'] ?? '' ) );
					self::field( 'loan_amount', __( 'Loan Amount Required (₹)', 'ramsetu-leads' ), array( 'required' => true, 'inputmode' => 'numeric', 'placeholder' => __( 'e.g. 15,00,000', 'ramsetu-leads' ), 'data-rsl-money' => '1' ) );
					self::select( 'collateral', __( 'Collateral Property', 'ramsetu-leads' ), rsl_collaterals(), array( 'required' => true, 'placeholder' => __( 'Select property type', 'ramsetu-leads' ) ) );
					self::field( 'collateral_value', __( 'Tentative Value of Collateral (₹)', 'ramsetu-leads' ), array( 'required' => true, 'inputmode' => 'numeric', 'placeholder' => __( 'e.g. 40,00,000', 'ramsetu-leads' ), 'data-rsl-money' => '1' ) );
					break;

				case 'referral':
					self::field( 'referrer_name', __( 'Your Name', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'name' ) );
					self::field( 'referrer_phone', __( 'Your Mobile Number', 'ramsetu-leads' ), array( 'required' => true, 'type' => 'tel', 'autocomplete' => 'tel-national', 'inputmode' => 'numeric' ) );
					self::field( 'referred_name', __( "Referring (Applicant's Name)", 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'off' ) );
					self::field( 'referred_phone', __( "Applicant's Mobile Number", 'ramsetu-leads' ), array( 'type' => 'tel', 'autocomplete' => 'off', 'inputmode' => 'numeric', 'placeholder' => __( 'Optional', 'ramsetu-leads' ) ) );
					self::select( 'loan_type', __( 'Referring For', 'ramsetu-leads' ), rsl_referral_loan_types(), array( 'required' => true, 'placeholder' => __( 'Select', 'ramsetu-leads' ), 'wide' => true ) );
					break;

				case 'callback':
					$branch_states = array_values( array_unique( wp_list_pluck( rsl_get_branches(), 'state' ) ) );
					self::field( 'full_name', __( 'Name', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'name' ) );
					self::field( 'phone', __( 'Number', 'ramsetu-leads' ), array( 'required' => true, 'type' => 'tel', 'autocomplete' => 'tel-national', 'inputmode' => 'numeric' ) );
					self::select( 'state', __( 'State', 'ramsetu-leads' ), array_combine( rsl_states(), rsl_states() ), array( 'required' => true, 'placeholder' => __( 'Select state', 'ramsetu-leads' ), 'priority' => $branch_states ) );
					self::field( 'city', __( 'City', 'ramsetu-leads' ), array( 'required' => true, 'autocomplete' => 'address-level2' ) );
					self::select( 'support_needed', __( 'What service are you looking for?', 'ramsetu-leads' ), rsl_callback_services(), array( 'required' => true, 'placeholder' => __( 'Select a service', 'ramsetu-leads' ), 'wide' => true ) );
					break;
			}
			?>
			</div>

			<?php if ( 'apply' === $type ) : ?>
				<?php $privacy = get_privacy_policy_url(); ?>
				<div class="rsl-field rsl-field--check" data-field="consent">
					<label class="rsl-check">
						<input type="checkbox" name="consent" value="1" required>
						<span>
						<?php
						esc_html_e( 'I agree to RamSetu Financial Services contacting me regarding my enquiry/application and sharing relevant information about its financial products and services. I confirm that the information provided by me is accurate and complete. I understand that submission of this form does not guarantee loan approval, which is subject to the eligibility, assessment and approval of the respective bank/NBFC.', 'ramsetu-leads' );
						if ( $privacy ) {
							echo ' <a href="' . esc_url( $privacy ) . '" target="_blank" rel="noopener">' . esc_html__( 'Privacy Policy', 'ramsetu-leads' ) . '</a>';
						}
						?>
						</span>
					</label>
					<p class="rsl-error" hidden></p>
				</div>
			<?php else : ?>
				<p class="rsl-note"><?php echo esc_html( 'referral' === $type ? __( '*RamSetu Team will reach out to you soon for further discussion.', 'ramsetu-leads' ) : __( '*RamSetu Team will reach out to you soon.', 'ramsetu-leads' ) ); ?></p>
			<?php endif; ?>

			<div class="rsl-actions">
				<button type="submit" class="rsl-submit btn btn--primary"><?php echo esc_html( 'callback' === $type ? __( 'Request a Call Back', 'ramsetu-leads' ) : __( 'Submit', 'ramsetu-leads' ) ); ?> <span aria-hidden="true">→</span></button>
			</div>
		</form>
		<?php
		return ob_get_clean();
	}

	private static function attrs( array $a ) {
		$out = '';
		foreach ( $a as $k => $v ) {
			if ( in_array( $k, array( 'required', 'type', 'placeholder', 'selected', 'priority', 'wide', 'show_if' ), true ) || '' === $v || null === $v ) {
				continue;
			}
			$out .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
		}
		return $out;
	}

	private static $n = 0;

	private static function uid( $name ) {
		self::$n++;
		return 'rsl-' . $name . '-' . self::$n;
	}

	private static function open( $name, $label, $required, $id, $wide = false, $show_if = '' ) {
		printf(
			'<div class="rsl-field%1$s" data-field="%2$s"%3$s>',
			$wide ? ' rsl-field--wide' : '',
			esc_attr( $name ),
			$show_if ? ' data-show-if="' . esc_attr( $show_if ) . '" hidden' : ''
		);
		printf( '<label for="%1$s">%2$s%3$s</label>', esc_attr( $id ), esc_html( $label ), $required ? ' <span class="rsl-req" aria-hidden="true">*</span>' : '' );
	}

	private static function close() {
		echo '<p class="rsl-error" hidden></p></div>';
	}

	private static function field( $name, $label, $a = array() ) {
		$id  = self::uid( $name );
		$req = ! empty( $a['required'] );
		self::open( $name, $label, $req, $id, ! empty( $a['wide'] ), $a['show_if'] ?? '' );
		printf(
			'<input id="%1$s" name="%2$s" type="%3$s" placeholder="%4$s"%5$s%6$s>',
			esc_attr( $id ),
			esc_attr( $name ),
			esc_attr( $a['type'] ?? 'text' ),
			esc_attr( $a['placeholder'] ?? '' ),
			$req ? ' required aria-required="true"' : '',
			self::attrs( $a ) // phpcs:ignore WordPress.Security.EscapeOutput
		);
		self::close();
	}

	private static function textarea( $name, $label, $a = array() ) {
		$id  = self::uid( $name );
		$req = ! empty( $a['required'] );
		self::open( $name, $label, $req, $id, true );
		printf(
			'<textarea id="%1$s" name="%2$s" rows="3" placeholder="%3$s"%4$s></textarea>',
			esc_attr( $id ), esc_attr( $name ), esc_attr( $a['placeholder'] ?? '' ), $req ? ' required aria-required="true"' : ''
		);
		self::close();
	}

	private static function select( $name, $label, array $options, $a = array() ) {
		$id       = self::uid( $name );
		$req      = ! empty( $a['required'] );
		$selected = $a['selected'] ?? '';
		self::open( $name, $label, $req, $id, ! empty( $a['wide'] ) );
		printf( '<select id="%1$s" name="%2$s"%3$s%4$s>', esc_attr( $id ), esc_attr( $name ), $req ? ' required aria-required="true"' : '', ! empty( $a['autocomplete'] ) ? ' autocomplete="' . esc_attr( $a['autocomplete'] ) . '"' : '' );
		printf( '<option value="">%s</option>', esc_html( $a['placeholder'] ?? __( 'Select', 'ramsetu-leads' ) ) );
		if ( ! empty( $a['priority'] ) ) {
			echo '<optgroup label="' . esc_attr__( 'Where we have branches', 'ramsetu-leads' ) . '">';
			foreach ( $a['priority'] as $p ) {
				printf( '<option value="%1$s">%1$s</option>', esc_attr( $p ) );
			}
			echo '</optgroup><optgroup label="' . esc_attr__( 'All states', 'ramsetu-leads' ) . '">';
		}
		foreach ( $options as $value => $text ) {
			printf( '<option value="%1$s"%3$s>%2$s</option>', esc_attr( $value ), esc_html( $text ), selected( $selected, $value, false ) );
		}
		if ( ! empty( $a['priority'] ) ) {
			echo '</optgroup>';
		}
		echo '</select>';
		self::close();
	}

	/* ------------------------------------------------------------------ */
	/* Submission                                                          */
	/* ------------------------------------------------------------------ */

	/** Returns array( title, message ). */
	public static function success( $type ) {
		$messages = array(
			'apply'    => array(
				__( 'Thank You for Choosing RamSetu', 'ramsetu-leads' ),
				__( 'Our team will get in touch with you shortly regarding your application. Thank you for considering RamSetu as your trusted financial partner. We look forward to assisting you on your financial journey.', 'ramsetu-leads' ),
			),
			'referral' => array(
				__( 'Thank you for the referral!', 'ramsetu-leads' ),
				__( 'RamSetu Team will reach out to you soon for further discussion.', 'ramsetu-leads' ),
			),
			'callback' => array(
				__( 'Thank you!', 'ramsetu-leads' ),
				__( 'We have noted your request and will call you back soon.', 'ramsetu-leads' ),
			),
		);
		return $messages[ $type ] ?? array( '', '' );
	}

	public static function success_html( $type ) {
		list( $title, $msg ) = self::success( $type );
		printf( '<strong class="rsl-alert__title">%s</strong><span>%s</span>', esc_html( $title ), esc_html( $msg ) );
	}

	public static function handle() {
		$type = isset( $_POST['rsl_type'] ) ? sanitize_key( $_POST['rsl_type'] ) : '';
		if ( ! in_array( $type, self::TYPES, true ) ) {
			self::respond( false, $type, array( '_form' => __( 'Invalid form.', 'ramsetu-leads' ) ) );
		}

		// Nonce is checked but a stale one (cached page) is not fatal on its own:
		// the honeypot, timing and rate-limit checks below still apply.
		$nonce_ok = isset( $_POST['rsl_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rsl_nonce'] ) ), 'rsl_submit_' . $type );

		// Bot checks: honeypot filled, or submitted within 2s of page load.
		$ts = isset( $_POST['rsl_ts'] ) ? (int) $_POST['rsl_ts'] : 0;
		if ( ! empty( $_POST['rsl_website'] ) || ( $ts && time() - $ts < 2 ) ) {
			self::respond( true, $type ); // pretend success, store nothing
		}

		$ip = self::ip();
		if ( self::rate_limited( $type, $ip ) ) {
			self::respond( false, $type, array( '_form' => __( 'Too many submissions from your connection. Please wait a few minutes or call us directly.', 'ramsetu-leads' ) ) );
		}

		$p = wp_unslash( $_POST );
		list( $data, $errors ) = call_user_func( array( __CLASS__, 'validate_' . $type ), $p );

		if ( $errors ) {
			self::respond( false, $type, $errors );
		}

		$data['page_url']   = esc_url_raw( substr( (string) ( $p['page_url'] ?? wp_get_referer() ), 0, 255 ) );
		$data['ip_address'] = $ip;

		$object = array( 'apply' => 'lead', 'referral' => 'referral', 'callback' => 'callback' )[ $type ];
		if ( 'lead' === $object ) {
			$data['user_agent'] = substr( sanitize_text_field( $_SERVER['HTTP_USER_AGENT'] ?? '' ), 0, 255 );
			$data['source']     = 'website';
		}
		if ( ! $nonce_ok ) {
			$data['remarks'] = '[Auto] Submitted from a cached page (security token expired).';
		}

		$id = RSL_DB::insert( $object, $data );
		if ( ! $id ) {
			self::respond( false, $type, array( '_form' => __( 'We could not save your details. Please call us instead.', 'ramsetu-leads' ) ) );
		}

		self::bump_rate( $type, $ip );
		do_action( 'rsl_new_submission', $object, $id, $data );

		self::respond( true, $type );
	}

	/** Parse an Indian-formatted rupee amount ("15,00,000") to a number. */
	private static function money( $raw ) {
		return (float) preg_replace( '/[^\d.]/', '', (string) $raw );
	}

	private static function validate_apply( array $p ) {
		$e = array();
		$d = array();

		$d['full_name'] = sanitize_text_field( $p['full_name'] ?? '' );
		if ( mb_strlen( $d['full_name'] ) < 2 ) {
			$e['full_name'] = __( 'Please enter your full name.', 'ramsetu-leads' );
		}
		$d['phone'] = rsl_normalize_phone( $p['phone'] ?? '' );
		if ( ! $d['phone'] ) {
			$e['phone'] = __( 'Enter a valid 10-digit mobile number.', 'ramsetu-leads' );
		}
		$d['email'] = sanitize_email( $p['email'] ?? '' );
		if ( ! empty( $p['email'] ) && ! is_email( $d['email'] ) ) {
			$e['email'] = __( 'This email address does not look right.', 'ramsetu-leads' );
		}
		$d['address'] = mb_substr( sanitize_text_field( $p['address'] ?? '' ), 0, 255 );
		if ( mb_strlen( $d['address'] ) < 5 ) {
			$e['address'] = __( 'Please enter your address.', 'ramsetu-leads' );
		}
		$d['city'] = sanitize_text_field( $p['city'] ?? '' );
		if ( '' === $d['city'] ) {
			$e['city'] = __( 'Please enter your city.', 'ramsetu-leads' );
		}
		$d['state'] = sanitize_text_field( $p['state'] ?? '' );
		if ( ! in_array( $d['state'], rsl_states(), true ) ) {
			$e['state'] = __( 'Please select your state.', 'ramsetu-leads' );
		}
		$d['pincode'] = preg_replace( '/\D/', '', (string) ( $p['pincode'] ?? '' ) );
		if ( ! preg_match( '/^[1-9]\d{5}$/', $d['pincode'] ) ) {
			$e['pincode'] = __( 'Enter a valid 6-digit pincode.', 'ramsetu-leads' );
		}
		$d['profession'] = sanitize_key( $p['profession'] ?? '' );
		if ( ! isset( rsl_profiles()[ $d['profession'] ] ) ) {
			$e['profession'] = __( 'Please select your profile.', 'ramsetu-leads' );
		}
		$d['monthly_income'] = null;
		if ( 'salaried' === $d['profession'] ) {
			$income = self::money( $p['monthly_income'] ?? '' );
			if ( $income < 1000 ) {
				$e['monthly_income'] = __( 'Please enter your monthly income in rupees.', 'ramsetu-leads' );
			}
			$d['monthly_income'] = $income;
		}
		$d['business_type'] = mb_substr( sanitize_text_field( $p['business_type'] ?? '' ), 0, 150 );
		if ( mb_strlen( $d['business_type'] ) < 2 ) {
			$e['business_type'] = __( 'Please enter your business or industry.', 'ramsetu-leads' );
		}
		$d['loan_category'] = sanitize_key( $p['loan_category'] ?? '' );
		if ( ! isset( rsl_public_loan_categories()[ $d['loan_category'] ] ) ) {
			$e['loan_category'] = __( 'Please select the loan product.', 'ramsetu-leads' );
		}
		$amount = self::money( $p['loan_amount'] ?? '' );
		if ( $amount < 10000 || $amount > 9999999999 ) {
			$e['loan_amount'] = __( 'Enter the loan amount in rupees (minimum ₹10,000).', 'ramsetu-leads' );
		}
		$d['loan_amount'] = $amount;
		$d['collateral']  = sanitize_key( $p['collateral'] ?? '' );
		if ( ! isset( rsl_collaterals()[ $d['collateral'] ] ) ) {
			$e['collateral'] = __( 'Please select the collateral property type.', 'ramsetu-leads' );
		}
		$value = self::money( $p['collateral_value'] ?? '' );
		if ( $value < 10000 || $value > 9999999999 ) {
			$e['collateral_value'] = __( 'Enter the approximate property value in rupees.', 'ramsetu-leads' );
		}
		$d['collateral_value'] = $value;
		$d['pan']              = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) ( $p['pan'] ?? '' ) ) );
		if ( '' === $d['pan'] ) {
			$e['pan'] = __( 'Please enter your PAN number.', 'ramsetu-leads' );
		} elseif ( ! preg_match( '/^[A-Z]{5}\d{4}[A-Z]$/', $d['pan'] ) ) {
			$e['pan'] = __( 'PAN should look like ABCDE1234F.', 'ramsetu-leads' );
		}
		// Full Aadhaar: kept only in encrypted form; the last 4 digits are kept for display.
		$aadhaar            = preg_replace( '/\D/', '', (string) ( $p['aadhaar'] ?? '' ) );
		$d['aadhaar_last4'] = '';
		$d['aadhaar_enc']   = '';
		if ( '' === $aadhaar ) {
			// Optional (RamSetu, 13 Oct).
		} elseif ( ! rsl_aadhaar_valid( $aadhaar ) ) {
			$e['aadhaar'] = __( 'Please enter a valid 12-digit Aadhaar number.', 'ramsetu-leads' );
		} else {
			$d['aadhaar_enc']   = rsl_encrypt( $aadhaar );
			$d['aadhaar_last4'] = substr( $aadhaar, -4 );
			if ( '' === $d['aadhaar_enc'] ) {
				$e['_form'] = __( 'We could not save your application securely. Please call us instead.', 'ramsetu-leads' );
			}
		}
		$d['consent'] = empty( $p['consent'] ) ? 0 : 1;
		if ( ! $d['consent'] ) {
			$e['consent'] = __( 'Please accept to continue.', 'ramsetu-leads' );
		}
		return array( $d, $e );
	}

	private static function validate_referral( array $p ) {
		$e = array();
		$d = array(
			'referrer_name'  => sanitize_text_field( $p['referrer_name'] ?? '' ),
			'referrer_phone' => rsl_normalize_phone( $p['referrer_phone'] ?? '' ),
			'referred_name'  => sanitize_text_field( $p['referred_name'] ?? '' ),
			'referred_phone' => '',
			'loan_type'      => sanitize_key( $p['loan_type'] ?? '' ),
		);
		if ( mb_strlen( $d['referrer_name'] ) < 2 ) {
			$e['referrer_name'] = __( 'Please enter your name.', 'ramsetu-leads' );
		}
		if ( ! $d['referrer_phone'] ) {
			$e['referrer_phone'] = __( 'Enter a valid 10-digit mobile number.', 'ramsetu-leads' );
		}
		if ( mb_strlen( $d['referred_name'] ) < 2 ) {
			$e['referred_name'] = __( "Please enter the applicant's name.", 'ramsetu-leads' );
		}
		if ( ! empty( $p['referred_phone'] ) ) {
			$d['referred_phone'] = rsl_normalize_phone( $p['referred_phone'] );
			if ( ! $d['referred_phone'] ) {
				$e['referred_phone'] = __( 'Enter a valid 10-digit mobile number, or leave it blank.', 'ramsetu-leads' );
			} elseif ( $d['referred_phone'] === $d['referrer_phone'] ) {
				$e['referred_phone'] = __( 'This is the same as your number.', 'ramsetu-leads' );
			}
		}
		if ( ! isset( rsl_referral_loan_types()[ $d['loan_type'] ] ) ) {
			$e['loan_type'] = __( 'Please select what you are referring for.', 'ramsetu-leads' );
		}
		return array( $d, $e );
	}

	private static function validate_callback( array $p ) {
		$e = array();
		$d = array(
			'full_name'      => sanitize_text_field( $p['full_name'] ?? '' ),
			'phone'          => rsl_normalize_phone( $p['phone'] ?? '' ),
			'state'          => sanitize_text_field( $p['state'] ?? '' ),
			'city'           => sanitize_text_field( $p['city'] ?? '' ),
			'support_needed' => sanitize_text_field( $p['support_needed'] ?? '' ),
		);
		if ( mb_strlen( $d['full_name'] ) < 2 ) {
			$e['full_name'] = __( 'Please enter your name.', 'ramsetu-leads' );
		}
		if ( ! $d['phone'] ) {
			$e['phone'] = __( 'Enter a valid 10-digit mobile number.', 'ramsetu-leads' );
		}
		if ( ! in_array( $d['state'], rsl_states(), true ) ) {
			$e['state'] = __( 'Please select your state.', 'ramsetu-leads' );
		}
		if ( '' === $d['city'] ) {
			$e['city'] = __( 'Please enter your city.', 'ramsetu-leads' );
		}
		if ( ! isset( rsl_callback_services()[ $d['support_needed'] ] ) ) {
			$e['support_needed'] = __( 'Please select the service you are looking for.', 'ramsetu-leads' );
		}
		return array( $d, $e );
	}

	private static function respond( $ok, $type, $errors = array() ) {
		if ( wp_doing_ajax() ) {
			if ( $ok ) {
				list( $title, $msg ) = self::success( $type );
				wp_send_json_success( array( 'title' => $title, 'message' => $msg ) );
			}
			wp_send_json_error( array( 'errors' => $errors ), 422 );
		}
		// No-JS fallback.
		$back = wp_get_referer() ? wp_get_referer() : home_url( '/' );
		$back = remove_query_arg( array( 'rsl_done', 'rsl_err' ), $back );
		if ( $ok ) {
			wp_safe_redirect( add_query_arg( 'rsl_done', $type, $back ) . '#rsl-' . $type );
		} else {
			wp_safe_redirect( add_query_arg( 'rsl_err', $type, $back ) );
		}
		exit;
	}

	/* ---------- rate limiting: 5 per form per IP per 10 minutes ---------- */

	private static function ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	private static function rate_key( $type, $ip ) {
		return 'rsl_rl_' . $type . '_' . md5( $ip );
	}

	private static function rate_limited( $type, $ip ) {
		return $ip && (int) get_transient( self::rate_key( $type, $ip ) ) >= (int) apply_filters( 'rsl_rate_limit', 5, $type );
	}

	private static function bump_rate( $type, $ip ) {
		if ( ! $ip ) {
			return;
		}
		$k = self::rate_key( $type, $ip );
		set_transient( $k, (int) get_transient( $k ) + 1, 10 * MINUTE_IN_SECONDS );
	}
}
