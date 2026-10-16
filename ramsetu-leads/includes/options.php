<?php
/**
 * Shared option lists and helpers. The theme calls these too, so every form
 * and every admin screen uses the same values.
 */

defined( 'ABSPATH' ) || exit;

function rsl_states() {
	return array(
		'Andhra Pradesh', 'Arunachal Pradesh', 'Assam', 'Bihar', 'Chhattisgarh', 'Goa', 'Gujarat',
		'Haryana', 'Himachal Pradesh', 'Jharkhand', 'Karnataka', 'Kerala', 'Madhya Pradesh',
		'Maharashtra', 'Manipur', 'Meghalaya', 'Mizoram', 'Nagaland', 'Odisha', 'Punjab',
		'Rajasthan', 'Sikkim', 'Tamil Nadu', 'Telangana', 'Tripura', 'Uttar Pradesh',
		'Uttarakhand', 'West Bengal', 'Andaman and Nicobar Islands', 'Chandigarh',
		'Dadra and Nagar Haveli and Daman and Diu', 'Delhi', 'Jammu and Kashmir', 'Ladakh',
		'Lakshadweep', 'Puducherry',
	);
}

/** Loan Product Required (Apply Now) / Product (Phase 2 portals). */
function rsl_loan_categories() {
	return array(
		'home_loan'       => __( 'Home Loan', 'ramsetu-leads' ),
		'lap'             => __( 'Loan Against Property', 'ramsetu-leads' ),
		'bt_topup'        => __( 'Balance Transfer & Top Up', 'ramsetu-leads' ),
		'business_loan'   => __( 'Business Loan', 'ramsetu-leads' ),
		'working_capital' => __( 'Working Capital Loan', 'ramsetu-leads' ),
		'plant_machinery' => __( 'Plant & Machinery Loan', 'ramsetu-leads' ),
		'education'       => __( 'School & College Funding', 'ramsetu-leads' ),
		'hospitality'     => __( 'Hotel & Restaurant Funding', 'ramsetu-leads' ),
		'lrd'             => __( 'Lease Rental / Warehousing Discounting', 'ramsetu-leads' ),
		'consulting'      => __( 'Other / Customized Solutions', 'ramsetu-leads' ),
	);
}

/** Loan products customers can pick on the Apply Now form (Business Loan removed, Round 2). Old leads keep their label. */
function rsl_public_loan_categories() {
	$c = rsl_loan_categories();
	unset( $c['business_loan'] );
	return $c;
}

/* ---------- Aadhaar: full number is stored encrypted, shown masked ---------- */

function rsl_crypt_key() {
	return hash( 'sha256', 'rsl-pii|' . wp_salt( 'auth' ), true );
}

function rsl_encrypt( $plain ) {
	$plain = (string) $plain;
	if ( '' === $plain || ! function_exists( 'openssl_encrypt' ) ) {
		return '';
	}
	$iv  = random_bytes( 12 );
	$tag = '';
	$ct  = openssl_encrypt( $plain, 'aes-256-gcm', rsl_crypt_key(), OPENSSL_RAW_DATA, $iv, $tag );
	return false === $ct ? '' : 'v1:' . base64_encode( $iv . $tag . $ct ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
}

function rsl_decrypt( $stored ) {
	$stored = (string) $stored;
	if ( 0 !== strpos( $stored, 'v1:' ) ) {
		return '';
	}
	$raw = base64_decode( substr( $stored, 3 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions
	if ( false === $raw || strlen( $raw ) < 29 ) {
		return '';
	}
	$out = openssl_decrypt( substr( $raw, 28 ), 'aes-256-gcm', rsl_crypt_key(), OPENSSL_RAW_DATA, substr( $raw, 0, 12 ), substr( $raw, 12, 16 ) );
	return false === $out ? '' : $out;
}

/** 12 digits, not starting with 0 or 1, valid Verhoeff check digit (as UIDAI issues them). */
function rsl_aadhaar_valid( $n ) {
	if ( ! preg_match( '/^[2-9]\d{11}$/', (string) $n ) ) {
		return false;
	}
	$d = array(
		array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ), array( 1, 2, 3, 4, 0, 6, 7, 8, 9, 5 ), array( 2, 3, 4, 0, 1, 7, 8, 9, 5, 6 ),
		array( 3, 4, 0, 1, 2, 8, 9, 5, 6, 7 ), array( 4, 0, 1, 2, 3, 9, 5, 6, 7, 8 ), array( 5, 9, 8, 7, 6, 0, 4, 3, 2, 1 ),
		array( 6, 5, 9, 8, 7, 1, 0, 4, 3, 2 ), array( 7, 6, 5, 9, 8, 2, 1, 0, 4, 3 ), array( 8, 7, 6, 5, 9, 3, 2, 1, 0, 4 ),
		array( 9, 8, 7, 6, 5, 4, 3, 2, 1, 0 ),
	);
	$p = array(
		array( 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 ), array( 1, 5, 7, 6, 2, 8, 3, 0, 9, 4 ), array( 5, 8, 0, 3, 7, 9, 6, 1, 4, 2 ),
		array( 8, 9, 1, 6, 0, 4, 3, 5, 2, 7 ), array( 9, 4, 5, 3, 1, 2, 6, 8, 7, 0 ), array( 4, 2, 8, 6, 5, 7, 3, 9, 0, 1 ),
		array( 2, 7, 9, 3, 8, 0, 6, 4, 1, 5 ), array( 7, 0, 4, 6, 9, 1, 3, 2, 5, 8 ),
	);
	$c   = 0;
	$rev = strrev( (string) $n );
	for ( $i = 0; $i < 12; $i++ ) {
		$c = $d[ $c ][ $p[ $i % 8 ][ (int) $rev[ $i ] ] ];
	}
	return 0 === $c;
}

function rsl_mask_aadhaar( $last4 ) {
	return '' === (string) $last4 ? '' : 'XXXX XXXX ' . $last4;
}

/** "What service are you looking for?" on the Request a Call Back form. */
function rsl_callback_services() {
	return array(
		'Home Loans'                       => __( 'Home Loans', 'ramsetu-leads' ),
		'Loans Against Property'           => __( 'Loans Against Property', 'ramsetu-leads' ),
		'Working Capital Loans'            => __( 'Working Capital Loans', 'ramsetu-leads' ),
		'Plant and Machinery Loans'        => __( 'Plant and Machinery Loans', 'ramsetu-leads' ),
		'School and College Funding Loans' => __( 'School and College Funding Loans', 'ramsetu-leads' ),
		'Hotel and Restaurant Loans'       => __( 'Hotel and Restaurant Loans', 'ramsetu-leads' ),
		'Others'                           => __( 'Others', 'ramsetu-leads' ),
	);
}

/** Profile options shown on the Apply Now form. */
function rsl_profiles() {
	return array(
		'salaried' => __( 'Salaried', 'ramsetu-leads' ),
		'sep'      => __( 'Self Employed Professional (SEP)', 'ramsetu-leads' ),
		'senp'     => __( 'Self Employed Non-Professional (SENP)', 'ramsetu-leads' ),
		'msme'     => __( 'MSME', 'ramsetu-leads' ),
		'business' => __( 'Business', 'ramsetu-leads' ),
		'others'   => __( 'Others', 'ramsetu-leads' ),
	);
}

/** All profile labels, including ones used by the Phase 2 portals and older entries. */
function rsl_professions() {
	return rsl_profiles() + array(
		'self_employed'              => __( 'Self Employed', 'ramsetu-leads' ),
		'self_employed_professional' => __( 'Self Employed Professional', 'ramsetu-leads' ),
		'self_employed_business'     => __( 'Self Employed Businessman', 'ramsetu-leads' ),
	);
}

/** Collateral Property. Edit this list if RamSetu wants different options. */
function rsl_collaterals() {
	return apply_filters( 'rsl_collaterals', array(
		'residential' => __( 'Residential Property', 'ramsetu-leads' ),
		'commercial'  => __( 'Commercial Property', 'ramsetu-leads' ),
		'industrial'  => __( 'Industrial Property', 'ramsetu-leads' ),
		'plot'        => __( 'Plot / Land', 'ramsetu-leads' ),
		'other'       => __( 'Other', 'ramsetu-leads' ),
	) );
}

/** "Referring For" on the Refer & Earn form. */
function rsl_referral_loan_types() {
	return array(
		'home_loan'  => __( 'Home Loan', 'ramsetu-leads' ),
		'lap'        => __( 'Loan Against Property', 'ramsetu-leads' ),
		'consulting' => __( 'Consulting Services', 'ramsetu-leads' ),
	);
}

/** Labels for every referral value ever used (older entries included). */
function rsl_referral_labels() {
	return rsl_referral_loan_types() + array(
		'business_loan' => __( 'Business Loan', 'ramsetu-leads' ),
		'not_sure'      => __( 'Not sure yet', 'ramsetu-leads' ),
	);
}

/** Where a lead came from. Partner and employee are for Phase 2. */
function rsl_sources() {
	return array(
		'website'  => __( 'Website – Apply Now', 'ramsetu-leads' ),
		'referral' => __( 'Referral', 'ramsetu-leads' ),
		'callback' => __( 'Call-back request', 'ramsetu-leads' ),
		'partner'  => __( 'Channel Partner', 'ramsetu-leads' ),
		'employee' => __( 'Employee', 'ramsetu-leads' ),
		'manual'   => __( 'Added manually', 'ramsetu-leads' ),
	);
}

function rsl_lead_statuses() {
	return array(
		'new'         => __( 'New', 'ramsetu-leads' ),
		'assigned'    => __( 'Assigned', 'ramsetu-leads' ),
		'contacted'   => __( 'Contacted', 'ramsetu-leads' ),
		'in_progress' => __( 'In Progress', 'ramsetu-leads' ),
		'converted'   => __( 'Converted', 'ramsetu-leads' ),
		'not_eligible'=> __( 'Not Eligible', 'ramsetu-leads' ),
		'closed'      => __( 'Closed / Lost', 'ramsetu-leads' ),
	);
}

function rsl_simple_statuses() {
	return array(
		'new'       => __( 'New', 'ramsetu-leads' ),
		'contacted' => __( 'Contacted', 'ramsetu-leads' ),
		'converted' => __( 'Converted to Lead', 'ramsetu-leads' ),
		'closed'    => __( 'Closed', 'ramsetu-leads' ),
	);
}

function rsl_label( $list, $key ) {
	return isset( $list[ $key ] ) ? $list[ $key ] : ( $key ? $key : '—' );
}

/**
 * Branches. Edited under RamSetu → Branches. Details confirmed by RamSetu (Oct 2026).
 */
function rsl_default_branches() {
	return array(
		array(
			'state'   => 'Maharashtra',
			'city'    => 'Navi Mumbai',
			'name'    => 'Head Office – Navi Mumbai',
			'address' => '2501, Plan S – Business Park, Navi Mumbai – 400706',
			'contact' => 'Aaditya Somani',
			'phone'   => '9828582261',
			'email'   => 'aaditya.s@ramsetufin.com',
			'map'     => '',
		),
		array(
			'state'   => 'Karnataka',
			'city'    => 'Tumakuru',
			'name'    => 'Tumakuru Branch',
			'address' => '2nd Floor, Annapoorneshwari Building, B.H. Road, Batawadi Circle, Near Anjaneya Temple, Tumakuru – 572104',
			'contact' => 'Thimmaraya H',
			'phone'   => '7259494245',
			'email'   => 'thimmaraya@ramsetufin.com',
			'map'     => '',
		),
		array(
			'state'   => 'Karnataka',
			'city'    => 'Sira',
			'name'    => 'Sira Branch',
			'address' => 'Ground Floor, 4th Cross Road, Santepete South Side, Sira Town – 572137',
			'contact' => 'Shivakumar RM',
			'phone'   => '8105490612',
			'email'   => 'branch.id@ramsetufin.com',
			'map'     => '',
		),
	);
}

function rsl_get_branches() {
	$branches = get_option( 'rsl_branches' );
	return is_array( $branches ) ? $branches : rsl_default_branches();
}

function rsl_settings() {
	return wp_parse_args( get_option( 'rsl_settings', array() ), array(
		'notify_emails' => get_option( 'admin_email' ),
		'notify_leads'     => 1,
		'notify_referrals' => 1,
		'notify_callbacks' => 1,
		'walkin_hours'     => 'Monday to Saturday, 9:00 AM – 6:00 PM',
	) );
}

/** Indian mobile: 10 digits starting 6–9, optional +91 / 0 prefix. */
function rsl_normalize_phone( $raw ) {
	$digits = preg_replace( '/\D+/', '', (string) $raw );
	if ( strlen( $digits ) === 12 && 0 === strpos( $digits, '91' ) ) {
		$digits = substr( $digits, 2 );
	} elseif ( strlen( $digits ) === 11 && 0 === strpos( $digits, '0' ) ) {
		$digits = substr( $digits, 1 );
	}
	return preg_match( '/^[6-9]\d{9}$/', $digits ) ? $digits : '';
}

function rsl_format_inr( $amount ) {
	if ( '' === $amount || null === $amount ) {
		return '—';
	}
	$amount = (string) (int) round( (float) $amount );
	$last3  = substr( $amount, -3 );
	$rest   = substr( $amount, 0, -3 );
	if ( '' !== $rest ) {
		$rest  = preg_replace( '/\B(?=(\d{2})+(?!\d))/', ',', $rest );
		$last3 = $rest . ',' . $last3;
	}
	return '₹' . $last3;
}

/** Show only the last 4 characters of an ID number (for emails). */
function rsl_mask( $value ) {
	$value = (string) $value;
	return strlen( $value ) > 4 ? str_repeat( '•', strlen( $value ) - 4 ) . substr( $value, -4 ) : $value;
}

/* ---------------------------------------------------------------------- */
/* Phase 2: partner & employee portal                                      */
/* ---------------------------------------------------------------------- */

/** "Product Applying for" on the partner / employee lead form. */
function rsl_portal_products() {
	return array(
		'home_loan'     => __( 'Home Loan', 'ramsetu-leads' ),
		'lap'           => __( 'Loan Against Property (LAP)', 'ramsetu-leads' ),
		'bt'            => __( 'Balance Transfer (BT)', 'ramsetu-leads' ),
		'bt_topup'      => __( 'Balance Transfer & Top Up', 'ramsetu-leads' ),
		'new_purchase'  => __( 'New Purchase Loan', 'ramsetu-leads' ),
		'business_loan' => __( 'Business Loan', 'ramsetu-leads' ),
		'consulting'    => __( 'Consulting Services', 'ramsetu-leads' ),
	);
}

/** Labels for every product value, from any form. */
function rsl_product_labels() {
	return rsl_loan_categories() + rsl_portal_products();
}

/** Customer Profile on the partner / employee lead form. */
function rsl_portal_profiles() {
	return array(
		'salaried'                   => __( 'Salaried', 'ramsetu-leads' ),
		'self_employed_professional' => __( 'Self Employed Professional', 'ramsetu-leads' ),
		'self_employed_business'     => __( 'Self Employed Businessman', 'ramsetu-leads' ),
	);
}

/** Nature of Business (Industry / Segment). */
function rsl_business_segments() {
	return apply_filters( 'rsl_business_segments', array(
		'Retail / Kirana'            => __( 'Retail / Kirana', 'ramsetu-leads' ),
		'Trading / Wholesale'        => __( 'Trading / Wholesale', 'ramsetu-leads' ),
		'Manufacturing'              => __( 'Manufacturing', 'ramsetu-leads' ),
		'Construction / Real Estate' => __( 'Construction / Real Estate', 'ramsetu-leads' ),
		'Dairy / Agriculture'        => __( 'Dairy / Agriculture', 'ramsetu-leads' ),
		'Hospitality / Food'         => __( 'Hospitality / Food', 'ramsetu-leads' ),
		'Education'                  => __( 'Education', 'ramsetu-leads' ),
		'Healthcare / Pharmacy'      => __( 'Healthcare / Pharmacy', 'ramsetu-leads' ),
		'Transport / Logistics'      => __( 'Transport / Logistics', 'ramsetu-leads' ),
		'Textiles / Boutique'        => __( 'Textiles / Boutique', 'ramsetu-leads' ),
		'Beauty / Personal Care'     => __( 'Beauty / Personal Care', 'ramsetu-leads' ),
		'Professional Services'      => __( 'Professional Services (CA, Doctor, Lawyer…)', 'ramsetu-leads' ),
		'Services – Other'           => __( 'Services – Other', 'ramsetu-leads' ),
		'Not applicable (Salaried)'  => __( 'Not applicable (Salaried)', 'ramsetu-leads' ),
		'Other'                      => __( 'Other', 'ramsetu-leads' ),
	) );
}

function rsl_credit_statuses() {
	return array(
		''           => __( '—', 'ramsetu-leads' ),
		'pending'    => __( 'Pending', 'ramsetu-leads' ),
		'in_review'  => __( 'Under credit review', 'ramsetu-leads' ),
		'approved'   => __( 'Credit approved', 'ramsetu-leads' ),
		'rejected'   => __( 'Credit rejected', 'ramsetu-leads' ),
	);
}

function rsl_document_statuses() {
	return array(
		''            => __( '—', 'ramsetu-leads' ),
		'pending'     => __( 'Documents pending', 'ramsetu-leads' ),
		'partial'     => __( 'Partially received', 'ramsetu-leads' ),
		'received'    => __( 'Received, under verification', 'ramsetu-leads' ),
		'verified'    => __( 'Verified', 'ramsetu-leads' ),
		'discrepancy' => __( 'Discrepancy found', 'ramsetu-leads' ),
	);
}

function rsl_disbursement_statuses() {
	return array(
		''          => __( '—', 'ramsetu-leads' ),
		'logged_in' => __( 'File logged in with lender', 'ramsetu-leads' ),
		'sanctioned'=> __( 'Sanctioned', 'ramsetu-leads' ),
		'partial'   => __( 'Partly disbursed', 'ramsetu-leads' ),
		'disbursed' => __( 'Disbursed', 'ramsetu-leads' ),
		'cancelled' => __( 'Cancelled', 'ramsetu-leads' ),
	);
}

function rsl_incentive_statuses() {
	return array(
		''         => __( '—', 'ramsetu-leads' ),
		'eligible' => __( 'Eligible', 'ramsetu-leads' ),
		'approved' => __( 'Approved', 'ramsetu-leads' ),
		'paid'     => __( 'Paid', 'ramsetu-leads' ),
		'na'       => __( 'Not applicable', 'ramsetu-leads' ),
	);
}

function rsl_partner_types() {
	return array(
		'ca'        => __( 'Chartered Accountant (CA)', 'ramsetu-leads' ),
		'dsa'       => __( 'DSA', 'ramsetu-leads' ),
		'connector' => __( 'Loan Agent / Connector', 'ramsetu-leads' ),
		'other'     => __( 'Other', 'ramsetu-leads' ),
	);
}

function rsl_partner_statuses() {
	return array(
		'pending'   => __( 'Pending approval', 'ramsetu-leads' ),
		'approved'  => __( 'Approved', 'ramsetu-leads' ),
		'rejected'  => __( 'Rejected', 'ramsetu-leads' ),
		'suspended' => __( 'Suspended', 'ramsetu-leads' ),
	);
}

/** Lead status as shown to partners / employees (simpler than the internal one). */
function rsl_portal_status( $status ) {
	$map = array(
		'new'          => __( 'Submitted', 'ramsetu-leads' ),
		'assigned'     => __( 'In progress', 'ramsetu-leads' ),
		'contacted'    => __( 'In progress', 'ramsetu-leads' ),
		'in_progress'  => __( 'In progress', 'ramsetu-leads' ),
		'converted'    => __( 'Converted', 'ramsetu-leads' ),
		'not_eligible' => __( 'Not eligible', 'ramsetu-leads' ),
		'closed'       => __( 'Closed', 'ramsetu-leads' ),
	);
	return $map[ $status ] ?? $status;
}

/** Human-friendly lead reference, e.g. RSL000123. */
function rsl_lead_code( $id ) {
	return 'RSL' . str_pad( (string) (int) $id, 6, '0', STR_PAD_LEFT );
}

function rsl_valid_pan( $pan ) {
	return (bool) preg_match( '/^[A-Z]{5}\d{4}[A-Z]$/', (string) $pan );
}

/** URL of the portal page (created by the theme), with an optional view. */
function rsl_portal_url( $view = '', $args = array() ) {
	$page = (int) get_option( 'rsl_portal_page' );
	$url  = $page ? get_permalink( $page ) : home_url( '/portal/' );
	if ( $view ) {
		$args = array( 'view' => $view ) + $args;
	}
	return $args ? add_query_arg( $args, $url ) : $url;
}
