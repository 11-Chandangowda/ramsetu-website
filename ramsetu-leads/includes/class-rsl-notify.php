<?php
/**
 * Email notifications.
 * - New lead / referral / call-back → addresses in RamSetu Leads → Settings.
 * - Lead assigned → the Sales Officer it was assigned to.
 *
 * Production sites need working outgoing mail (an SMTP plugin such as
 * WP Mail SMTP). On Local, messages appear in the Mailpit tab.
 */

defined( 'ABSPATH' ) || exit;

class RSL_Notify {

	public static function init() {
		add_action( 'rsl_new_submission', array( __CLASS__, 'new_submission' ), 10, 3 );
		add_action( 'rsl_assigned', array( __CLASS__, 'assigned' ), 10, 3 );
	}

	public static function new_submission( $object, $id, $data ) {
		$s   = rsl_settings();
		$key = array( 'lead' => 'notify_leads', 'referral' => 'notify_referrals', 'callback' => 'notify_callbacks' )[ $object ];
		if ( empty( $s[ $key ] ) ) {
			return;
		}
		$to = array_filter( array_map( 'trim', explode( ',', (string) $s['notify_emails'] ) ), 'is_email' );
		if ( ! $to ) {
			return;
		}

		switch ( $object ) {
			case 'lead':
				$g       = function ( $k ) use ( $data ) {
					return isset( $data[ $k ] ) ? $data[ $k ] : '';
				};
				$from    = '';
				if ( ! empty( $data['partner_id'] ) ) {
					$p    = RSL_DB::get( 'partner', (int) $data['partner_id'] );
					$from = $p ? ' via ' . $p['full_name'] . ' (' . $p['partner_code'] . ')' : '';
				} elseif ( 'employee' === $g( 'source' ) && ! empty( $data['submitted_by'] ) ) {
					$from = ' via ' . RSL_Partners::user_code( (int) $data['submitted_by'] );
				}
				$subject = sprintf( 'New %s lead: %s, %s%s', rsl_label( rsl_product_labels(), $g( 'loan_category' ) ), $g( 'full_name' ), $g( 'city' ), $from );
				$rows    = array(
					'Reference'        => rsl_lead_code( $id ),
					'Source'           => rsl_label( rsl_sources(), $g( 'source' ) ) . $from,
					'Name'             => $g( 'full_name' ),
					'Mobile'           => $g( 'phone' ),
					'Email'            => $g( 'email' ) ?: '—',
					'Location'         => trim( implode( ', ', array_filter( array( $g( 'address' ), $g( 'city' ), $g( 'district' ), $g( 'state' ), $g( 'pincode' ) ) ) ) ),
					'Profile'          => rsl_label( rsl_professions(), $g( 'profession' ) ),
					'Monthly income'   => $g( 'monthly_income' ) ? rsl_format_inr( $g( 'monthly_income' ) ) : '—',
					'Business'         => $g( 'business_type' ) ?: '—',
					'Business vintage' => '' !== (string) $g( 'business_vintage_years' ) ? $g( 'business_vintage_years' ) . ' years' : '—',
					'Loan product'     => rsl_label( rsl_product_labels(), $g( 'loan_category' ) ),
					'Loan amount'      => rsl_format_inr( $g( 'loan_amount' ) ),
					'Collateral'       => rsl_label( rsl_collaterals(), $g( 'collateral' ) ),
					'Collateral value' => $g( 'collateral_value' ) ? rsl_format_inr( $g( 'collateral_value' ) ) : '—',
					'Property location'=> $g( 'property_location' ) ?: '—',
					'Co-applicant'     => $g( 'co_applicant_name' ) ? $g( 'co_applicant_name' ) . ( $g( 'co_applicant_phone' ) ? ' (' . $g( 'co_applicant_phone' ) . ')' : '' ) : '—',
					'Existing loans'   => $g( 'existing_loans' ) ?: '—',
					'PAN'              => $g( 'pan' ) ? rsl_mask( $g( 'pan' ) ) : '—',
					'Aadhaar'          => $g( 'aadhaar_last4' ) ? rsl_mask_aadhaar( $g( 'aadhaar_last4' ) ) : '—',
				);
				$rows    = array_filter( $rows, function ( $v ) {
					return '—' !== $v && '' !== $v;
				} );
				break;
			case 'referral':
				$subject = sprintf( 'New referral from %s: %s', $data['referrer_name'], $data['referred_name'] );
				$rows    = array(
					'Referred by'   => $data['referrer_name'] . ' (' . $data['referrer_phone'] . ')',
					'Applicant'     => $data['referred_name'] . ( $data['referred_phone'] ? ' (' . $data['referred_phone'] . ')' : '' ),
					'Referring for' => rsl_label( rsl_referral_labels(), $data['loan_type'] ),
				);
				break;
			default:
				$subject = sprintf( 'Call-back request: %s, %s', $data['full_name'], $data['city'] );
				$rows    = array(
					'Name'    => $data['full_name'],
					'Phone'   => $data['phone'],
					'City'    => $data['city'] . ', ' . $data['state'],
					'Service' => $data['support_needed'],
				);
		}

		$link = admin_url( 'admin.php?page=' . self::page_for( $object ) . '&view=' . $id );
		self::send( $to, $subject, $rows, $link, __( 'Open in dashboard', 'ramsetu-leads' ) );
	}

	public static function assigned( $object, $id, $user_id ) {
		$user = get_userdata( $user_id );
		$row  = RSL_DB::get( $object, $id );
		if ( ! $user || ! $row || (int) $user_id === get_current_user_id() ) {
			return;
		}
		$name = $row['full_name'] ?? $row['referred_name'] ?? '';
		$rows = array( 'Customer' => $name );
		if ( 'lead' === $object ) {
			$rows['Phone']   = $row['phone'];
			$rows['City']    = $row['city'] . ', ' . $row['state'];
			$rows['Service'] = rsl_label( rsl_product_labels(), $row['loan_category'] );
			$rows['Amount']  = rsl_format_inr( $row['loan_amount'] );
		}
		$link = admin_url( 'admin.php?page=' . self::page_for( $object ) . '&view=' . $id );
		self::send( array( $user->user_email ), sprintf( 'Lead assigned to you: %s', $name ), $rows, $link, __( 'Open lead', 'ramsetu-leads' ) );
	}

	private static function page_for( $object ) {
		return array( 'lead' => 'rsl-leads', 'referral' => 'rsl-referrals', 'callback' => 'rsl-callbacks' )[ $object ];
	}

	private static function send( array $to, $subject, array $rows, $link, $link_text ) {
		$html  = '<div style="font-family:Arial,sans-serif;font-size:14px;color:#222;max-width:560px">';
		$html .= '<h2 style="color:#E8692A;font-size:18px;margin:0 0 12px">' . esc_html( $subject ) . '</h2>';
		$html .= '<table cellpadding="8" cellspacing="0" style="border-collapse:collapse;width:100%">';
		foreach ( $rows as $k => $v ) {
			$html .= '<tr><td style="border-bottom:1px solid #eee;color:#666;width:35%">' . esc_html( $k ) . '</td><td style="border-bottom:1px solid #eee"><strong>' . nl2br( esc_html( (string) $v ) ) . '</strong></td></tr>';
		}
		$html .= '</table><p style="margin:20px 0"><a href="' . esc_url( $link ) . '" style="background:#E8692A;color:#fff;padding:10px 18px;border-radius:6px;text-decoration:none">' . esc_html( $link_text ) . '</a></p>';
		$html .= '<p style="color:#999;font-size:12px">' . esc_html( get_bloginfo( 'name' ) ) . '</p></div>';

		wp_mail( $to, '[RamSetu] ' . $subject, $html, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}
}

RSL_Notify::init();
