<?php
/**
 * Customizer: Appearance → Customize → RamSetu.
 * Every text on the site that is not a page/post lives here, grouped by page.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'customize_register', function ( WP_Customize_Manager $wp ) {

	$wp->add_panel( 'rs', array(
		'title'       => __( 'RamSetu', 'ramsetu' ),
		'description' => __( 'Website text, contact details and product information.', 'ramsetu' ),
		'priority'    => 20,
	) );

	$sections = array(
		'rs_contact' => array(
			'title'  => __( 'Contact & company details', 'ramsetu' ),
			'fields' => array(
				'phone'            => array( __( 'Main phone number', 'ramsetu' ), 'text' ),
				'email'            => array( __( 'Main email', 'ramsetu' ), 'email' ),
				'whatsapp'         => array( __( 'WhatsApp number (10 digits)', 'ramsetu' ), 'text' ),
				'hq_address'       => array( __( 'Headquarter address', 'ramsetu' ), 'textarea' ),
				'cin'              => array( __( 'CIN', 'ramsetu' ), 'text' ),
				'gst'              => array( __( 'GST', 'ramsetu' ), 'text' ),
				'grievance_name'   => array( __( 'Grievance Redressal Officer – name', 'ramsetu' ), 'text' ),
				'grievance_phone'  => array( __( 'Grievance Officer – phone', 'ramsetu' ), 'text' ),
				'grievance_email'  => array( __( 'Grievance Officer – email', 'ramsetu' ), 'email' ),
				'grievance_note'   => array( __( 'Grievance – note', 'ramsetu' ), 'textarea' ),
				'social_linkedin'  => array( __( 'LinkedIn URL', 'ramsetu' ), 'url' ),
				'social_facebook'  => array( __( 'Facebook URL', 'ramsetu' ), 'url' ),
				'social_instagram' => array( __( 'Instagram URL', 'ramsetu' ), 'url' ),
				'social_youtube'   => array( __( 'YouTube URL', 'ramsetu' ), 'url' ),
			),
		),
		'rs_home' => array(
			'title'       => __( 'Home page', 'ramsetu' ),
			'description' => __( 'Slide links can be a full URL, or a page name like "ramaffordable#home-loan", "ramaffordable#lap", "setuemerging", "about", "partner", "branches", "contact". Paragraphs: leave a blank line between them.', 'ramsetu' ),
			'fields'      => array(),
		),
		'rs_products' => array(
			'title'       => __( 'Products', 'ramsetu' ),
			'description' => __( 'Loan size, tenure and interest rate each stay hidden until filled in. Example: "Up to 15 years", "Starting 12% p.a." Key features: one per line. Paragraphs: leave a blank line between them.', 'ramsetu' ),
			'fields'      => array(
				'loans_disclaimer'   => array( __( 'Lending disclaimer (under product cards and on About Us)', 'ramsetu' ), 'textarea' ),
				'ra_tagline'         => array( __( 'RamAffordable – tagline', 'ramsetu' ), 'text' ),
				'ra_brief_title'     => array( __( 'RamAffordable – brief heading', 'ramsetu' ), 'text' ),
				'ra_brief'           => array( __( 'RamAffordable – brief', 'ramsetu' ), 'textarea' ),
				'ra_features'        => array( __( 'RamAffordable – key features (one per line)', 'ramsetu' ), 'textarea' ),
				'ra_loans_for'       => array( __( 'RamAffordable – Loans For (one per line)', 'ramsetu' ), 'textarea' ),
				'collateral_title'   => array( __( 'Both product pages – collateral heading', 'ramsetu' ), 'text' ),
				'collateral_items'   => array( __( 'Both product pages – collateral types (one per line)', 'ramsetu' ), 'textarea' ),
				'ra_business_title'  => array( __( 'Loan Against Property card – title', 'ramsetu' ), 'text' ),
				'ra_business_text'   => array( __( 'Loan Against Property card – text (optional)', 'ramsetu' ), 'textarea' ),
				'ra_business_size'   => array( __( 'Loan Against Property – loan size', 'ramsetu' ), 'text' ),
				'ra_business_tenure' => array( __( 'Loan Against Property – tenure', 'ramsetu' ), 'text' ),
				'ra_business_rate'   => array( __( 'Loan Against Property – interest rate', 'ramsetu' ), 'text' ),
				'ra_home_title'      => array( __( 'Home Loan – title', 'ramsetu' ), 'text' ),
				'ra_home_text'       => array( __( 'Home Loan card – text (optional)', 'ramsetu' ), 'textarea' ),
				'ra_home_size'       => array( __( 'Home Loan – loan size', 'ramsetu' ), 'text' ),
				'ra_home_tenure'     => array( __( 'Home Loan – tenure', 'ramsetu' ), 'text' ),
				'ra_home_rate'       => array( __( 'Home Loan – interest rate', 'ramsetu' ), 'text' ),
				'ra_business_image'  => array( __( 'Loan Against Property card – photo (empty = built-in photo)', 'ramsetu' ), 'image' ),
				'ra_home_image'      => array( __( 'Home Loan card – photo (empty = built-in photo)', 'ramsetu' ), 'image' ),
				'se_tagline'         => array( __( 'SetuEmerging – tagline', 'ramsetu' ), 'text' ),
				'se_brief_title'     => array( __( 'SetuEmerging – brief heading', 'ramsetu' ), 'text' ),
				'se_brief'           => array( __( 'SetuEmerging – brief', 'ramsetu' ), 'textarea' ),
				'se_features'        => array( __( 'SetuEmerging – key features (one per line)', 'ramsetu' ), 'textarea' ),
				'se_loans_for'       => array( __( 'SetuEmerging – Loans For (one per line)', 'ramsetu' ), 'textarea' ),
				'se_industries_title'=> array( __( 'SetuEmerging – industries heading', 'ramsetu' ), 'text' ),
				'se_industries'      => array( __( 'SetuEmerging – industries (one per line)', 'ramsetu' ), 'textarea' ),
				'se_solutions'       => array( __( 'SetuEmerging – solution boxes (one per line, max 4)', 'ramsetu' ), 'textarea' ),
			),
		),
		'rs_about' => array(
			'title'       => __( 'About Us page', 'ramsetu' ),
			'description' => __( 'Leadership and advisors are managed under "Team & Advisors"; partner logos under "Lending Partners" in the dashboard menu. Roadmap: one phase per line, as "Phase title | description".', 'ramsetu' ),
			'fields'      => array(
				'about_hero'     => array( __( 'Page heading', 'ramsetu' ), 'text' ),
				'about_intro'    => array( __( 'About RamSetu', 'ramsetu' ), 'textarea' ),
				'about_vision'   => array( __( 'Our Vision', 'ramsetu' ), 'textarea' ),
				'about_mission'  => array( __( 'Our Mission', 'ramsetu' ), 'textarea' ),
				'about_presence' => array( __( 'Our Presence', 'ramsetu' ), 'textarea' ),
				'about_live'     => array( __( 'Live markets (comma separated)', 'ramsetu' ), 'text' ),
				'about_upcoming' => array( __( 'Upcoming locations (comma separated)', 'ramsetu' ), 'text' ),
				'roadmap_intro'  => array( __( 'Future Roadmap – intro', 'ramsetu' ), 'textarea' ),
				'roadmap'        => array( __( 'Future Roadmap – phases', 'ramsetu' ), 'textarea' ),
			),
		),
		'rs_partner' => array(
			'title'  => __( 'Become a Partner page', 'ramsetu' ),
			'fields' => array(
				'partner_title'      => array( __( 'Heading', 'ramsetu' ), 'textarea' ),
				'partner_text'       => array( __( 'Intro', 'ramsetu' ), 'textarea' ),
				'partner_steps'      => array( __( 'How it works – one step per line', 'ramsetu' ), 'textarea' ),
				'partner_disclaimer' => array( __( 'Disclaimer under the form', 'ramsetu' ), 'textarea' ),
			),
		),
		'rs_banners' => array(
			'title'       => __( 'Page banner photos', 'ramsetu' ),
			'description' => __( 'Background photo at the top of each page. Leave empty to keep the built-in photo; wide (3:1) photos work best.', 'ramsetu' ),
			'fields'      => array(
				'hero_ramaffordable_image' => array( __( 'RamAffordable', 'ramsetu' ), 'image' ),
				'hero_setuemerging_image'  => array( __( 'SetuEmerging', 'ramsetu' ), 'image' ),
				'hero_about_image'         => array( __( 'About Us', 'ramsetu' ), 'image' ),
				'hero_partner_image'       => array( __( 'Become a Partner', 'ramsetu' ), 'image' ),
				'hero_branches_image'      => array( __( 'Branch Locator', 'ramsetu' ), 'image' ),
				'hero_contact_image'       => array( __( 'Contact Us', 'ramsetu' ), 'image' ),
			),
		),
		'rs_branches' => array(
			'title'       => __( 'Branch locator page', 'ramsetu' ),
			'description' => __( 'Branch addresses, contact persons and walk-in hours are managed under RamSetu Leads → Branches / Settings.', 'ramsetu' ),
			'fields'      => array(
				'doorstep_title' => array( __( 'Doorstep heading', 'ramsetu' ), 'textarea' ),
				'doorstep_text'  => array( __( 'Doorstep text', 'ramsetu' ), 'text' ),
			),
		),
	);

	for ( $i = 1; $i <= 6; $i++ ) {
		/* translators: %d slide number */
		$n = sprintf( __( 'Slide %d', 'ramsetu' ), $i );
		$sections['rs_home']['fields'] += array(
			"hs{$i}_tab"    => array( $n . ' – short label (slide switcher)', 'text' ),
			"hs{$i}_brand"  => array( $n . ' – small heading (RamAffordable / SetuEmerging)', 'text' ),
			"hs{$i}_title"  => array( $n . ' – main heading (Enter for a new line; leave empty to hide this slide)', 'textarea' ),
			"hs{$i}_text"   => array( $n . ' – sub heading', 'textarea' ),
			"hs{$i}_chips"  => array( $n . ' – feature boxes (one per line, up to 4)', 'textarea' ),
			"hs{$i}_link"   => array( $n . ' – "Learn More" link (page slug, e.g. ramaffordable#lap)', 'text' ),
			"hs{$i}_apply"  => array( $n . ' – Apply Now loan type (home_loan, lap, plant_machinery, working_capital, hospitality, education, lrd)', 'text' ),
			"hs{$i}_image"  => array( $n . ' – photo (leave empty to keep the built-in one; portrait, about 1000 x 1080 px, person in the centre)', 'image' ),
		);
	}
	$sections['rs_home']['fields'] += array(
		'home_about_title'     => array( __( 'About section – heading', 'ramsetu' ), 'text' ),
		'home_about_text'      => array( __( 'About section – text', 'ramsetu' ), 'textarea' ),
		'home_founder_message' => array( __( 'Our Leadership – message', 'ramsetu' ), 'textarea' ),
		'products_title'       => array( __( 'Products section – heading', 'ramsetu' ), 'text' ),
		'products_text'        => array( __( 'Products section – text', 'ramsetu' ), 'text' ),
		'ra_home_card'         => array( __( 'RamAffordable card text', 'ramsetu' ), 'textarea' ),
		'se_home_card'         => array( __( 'SetuEmerging card text', 'ramsetu' ), 'textarea' ),
		'ra_vertical_sub'      => array( __( 'RamAffordable card – sub text', 'ramsetu' ), 'text' ),
		'ra_home_list'         => array( __( 'RamAffordable card – buttons (one per line)', 'ramsetu' ), 'textarea' ),
		'se_home_list'         => array( __( 'SetuEmerging card – buttons (one per line)', 'ramsetu' ), 'textarea' ),
		'home_founder_short'   => array( __( 'Our Leadership – short message on the Home page (**text** = bold; Read More opens the full message)', 'ramsetu' ), 'textarea' ),
		'ra_brand_image'       => array( __( 'RamAffordable card – photo (empty = built-in photo)', 'ramsetu' ), 'image' ),
		'se_brand_image'       => array( __( 'SetuEmerging card – photo (empty = built-in photo)', 'ramsetu' ), 'image' ),
		'why_title'            => array( __( 'Why RamSetu – heading', 'ramsetu' ), 'text' ),
		'why_items'            => array( __( 'Why RamSetu – one per line, as "Title | text"', 'ramsetu' ), 'textarea' ),
		'special_title'        => array( __( 'Special Features – heading', 'ramsetu' ), 'text' ),
		'special_items'        => array( __( 'Special Features – one per line, as "Title | text" (text optional)', 'ramsetu' ), 'textarea' ),
	);

	$priority = 10;
	foreach ( $sections as $sid => $s ) {
		$wp->add_section( $sid, array(
			'title'       => $s['title'],
			'description' => $s['description'] ?? '',
			'panel'       => 'rs',
			'priority'    => $priority++,
		) );
		foreach ( $s['fields'] as $key => $f ) {
			list( $label, $type ) = $f;
			$sanitize = array(
				'text'     => 'sanitize_text_field',
				'email'    => 'sanitize_email',
				'url'      => 'esc_url_raw',
				'textarea' => 'sanitize_textarea_field',
				'image'    => 'absint',
			)[ $type ];
			$wp->add_setting( 'rs_' . $key, array(
				'default'           => rs_defaults()[ $key ] ?? '',
				'sanitize_callback' => $sanitize,
				'transport'         => 'refresh',
			) );
			if ( 'image' === $type ) {
				$wp->add_control( new WP_Customize_Media_Control( $wp, 'rs_' . $key, array(
					'label'     => $label,
					'section'   => $sid,
					'mime_type' => 'image',
				) ) );
			} else {
				$wp->add_control( 'rs_' . $key, array(
					'label'   => $label,
					'section' => $sid,
					'type'    => $type,
				) );
			}
		}
	}
} );
