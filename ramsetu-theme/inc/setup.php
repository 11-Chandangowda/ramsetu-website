<?php
/**
 * One-time site setup when the theme is activated:
 * creates the pages (with the right templates), sets the home page,
 * builds the main menu and seeds the founder profile.
 * Safe to re-run: existing pages and menus are left alone.
 */

defined( 'ABSPATH' ) || exit;

function rs_setup_pages() {
	return array(
		'home'          => array( 'Home', '' ),
		'about'         => array( 'About Us', 'about' ),
		'ramaffordable' => array( 'RamAffordable', 'ramaffordable' ),
		'setuemerging'  => array( 'SetuEmerging', 'setuemerging' ),
		'become-a-partner' => array( 'Become a Partner', 'partner' ),
		'locate-branch' => array( 'Branch Locator', 'branches' ),
		'contact-us'    => array( 'Contact Us', 'contact' ),
		'apply-now'     => array( 'Apply Now', 'apply' ),
		'portal'        => array( 'Partner Portal', 'portal' ),
		'policies'      => array( 'Policies', 'policies' ),
	);
}

add_action( 'after_switch_theme', 'rs_run_setup' );

function rs_run_setup() {
	if ( get_option( 'rs_setup_done' ) ) {
		return;
	}

	if ( ! get_option( 'permalink_structure' ) ) {
		update_option( 'permalink_structure', '/%postname%/' );
	}

	$ids = array();
	foreach ( rs_setup_pages() as $slug => $p ) {
		list( $title, $tpl ) = $p;
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			$ids[ $slug ] = $existing->ID;
			if ( $tpl && in_array( (string) get_post_meta( $existing->ID, '_wp_page_template', true ), array( '', 'default' ), true ) ) {
				update_post_meta( $existing->ID, '_wp_page_template', 'templates/' . $tpl . '.php' );
			}
			continue;
		}
		$ids[ $slug ] = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => 'policies' === $slug ? rs_policies_placeholder() : '',
			'page_template'=> $tpl ? 'templates/' . $tpl . '.php' : '',
		) );
	}

	// Privacy Policy under Policies — the Apply Now consent box links to it.
	$privacy = (int) get_option( 'wp_page_for_privacy_policy' );
	$pp      = $privacy ? get_post( $privacy ) : null;
	if ( ! $pp || 'trash' === $pp->post_status ) {
		$privacy = wp_insert_post( array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => 'Privacy Policy',
			'post_name'    => 'privacy-policy',
			'post_parent'  => $ids['policies'],
			'post_content' => '<!-- wp:paragraph --><p><strong>Draft — to be replaced with RamSetu\'s approved Privacy Policy before launch.</strong></p><!-- /wp:paragraph -->',
		) );
		update_option( 'wp_page_for_privacy_policy', $privacy );
	} else {
		wp_update_post( array( 'ID' => $privacy, 'post_parent' => $ids['policies'], 'post_status' => 'publish', 'post_content' => '<!-- wp:paragraph --><p><strong>Draft — to be replaced with RamSetu\'s approved Privacy Policy before launch.</strong></p><!-- /wp:paragraph -->' ) );
	}

	update_option( 'rsl_portal_page', $ids['portal'] );
	update_option( 'show_on_front', 'page' );
	update_option( 'page_on_front', $ids['home'] );
	update_option( 'blogname', 'RamSetu Financial Services' );
	update_option( 'blogdescription', 'Affordable home and business loans for emerging India' );
	update_option( 'timezone_string', 'Asia/Kolkata' );
	update_option( 'date_format', 'd M Y' );

	rs_build_menus( $ids );

	flush_rewrite_rules();
	update_option( 'rs_setup_done', 1 );
	update_option( 'rs_theme_version', RS_THEME_VERSION );
}

// Seed the founder profile once the leads plugin (which owns Team) is active.
add_action( 'init', function () {
	if ( get_option( 'rs_setup_done' ) && ! get_option( 'rs_founder_seeded' ) && post_type_exists( 'rs_team' ) && taxonomy_exists( 'rs_team_group' ) && term_exists( 'leadership', 'rs_team_group' ) ) {
		if ( ! get_posts( array( 'post_type' => 'rs_team', 'posts_per_page' => 1, 'fields' => 'ids', 'post_status' => 'any' ) ) ) {
			$founder = wp_insert_post( array(
				'post_type'   => 'rs_team',
				'post_status' => 'publish',
				'post_title'  => 'Sanjeev Verma',
				'post_content'=> 'Past experience — to be added.',
			) );
			update_post_meta( $founder, '_rs_designation', 'Founder, MD & CEO' );
			wp_set_object_terms( $founder, 'leadership', 'rs_team_group' );
		}
		update_option( 'rs_founder_seeded', 1 );
	}
}, 20 );

function rs_build_menus( $ids ) {
	$locations = get_theme_mod( 'nav_menu_locations', array() );

	if ( empty( $locations['primary'] ) && ! wp_get_nav_menu_object( 'Main menu' ) ) {
		$menu = wp_create_nav_menu( 'Main menu' );
		$page = function ( $slug, $title, $parent = 0, $anchor = '' ) use ( $menu, $ids ) {
			if ( $anchor ) {
				return wp_update_nav_menu_item( $menu, 0, array(
					'menu-item-title'     => $title,
					'menu-item-url'       => get_permalink( $ids[ $slug ] ) . '#' . $anchor,
					'menu-item-type'      => 'custom',
					'menu-item-status'    => 'publish',
					'menu-item-parent-id' => $parent,
				) );
			}
			return wp_update_nav_menu_item( $menu, 0, array(
				'menu-item-title'     => $title,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $ids[ $slug ],
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-parent-id' => $parent,
			) );
		};

		// Order and names follow RamSetu's website tree (Round 1 review, Oct 2026).
		$products = wp_update_nav_menu_item( $menu, 0, array(
			'menu-item-title'  => 'Our Products',
			'menu-item-url'    => get_permalink( $ids['ramaffordable'] ),
			'menu-item-type'   => 'custom',
			'menu-item-status' => 'publish',
		) );
		$ra = $page( 'ramaffordable', 'RamAffordable', $products );
		$page( 'ramaffordable', 'Affordable Loans Against Property', $ra, 'lap' );
		$page( 'ramaffordable', 'Affordable Home Loans', $ra, 'home-loan' );
		$se = $page( 'setuemerging', 'SetuEmerging', $products );
		$page( 'setuemerging', 'Plant and Machinery Loans', $se, 'plant-machinery' );
		$page( 'setuemerging', 'Working Capital Loans', $se, 'solutions' );
		$page( 'setuemerging', 'Loan Funding for Hospitality Industry', $se, 'hotels' );
		$page( 'setuemerging', 'Loans for Education Industry', $products, 'schools' );

		$about = $page( 'about', 'About Us' );
		$page( 'about', 'About RamSetu', $about );
		$page( 'about', 'Vision & Mission', $about, 'vision' );
		$page( 'about', 'Our Leadership', $about, 'leadership' );
		$page( 'about', 'Our Presence', $about, 'presence' );
		$page( 'about', 'Future Roadmap', $about, 'roadmap' );

		$page( 'become-a-partner', 'Become a Partner' );
		$page( 'locate-branch', 'Branch Locator' );
		$page( 'contact-us', 'Contact Us' );

		$locations['primary'] = $menu;
	}

	set_theme_mod( 'nav_menu_locations', $locations );
}

/**
 * 1.2.0: adds the Partner Portal page (channel partner / employee login).
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_setup_done' ) || version_compare( (string) get_option( 'rs_theme_version', '1.0.0' ), '1.2.0', '>=' ) ) {
		return;
	}
	if ( version_compare( (string) get_option( 'rs_theme_version', '1.0.0' ), '1.1.0', '<' ) ) {
		return; // the 1.1.0 upgrade below runs first
	}
	$page = get_page_by_path( 'portal' );
	if ( ! $page ) {
		$id = wp_insert_post( array(
			'post_type'     => 'page',
			'post_status'   => 'publish',
			'post_title'    => 'Partner Portal',
			'post_name'     => 'portal',
			'page_template' => 'templates/portal.php',
		) );
	} else {
		$id = $page->ID;
		update_post_meta( $id, '_wp_page_template', 'templates/portal.php' );
	}
	update_option( 'rsl_portal_page', (int) $id );
	flush_rewrite_rules( false );
	update_option( 'rs_theme_version', '1.2.0' );
}, 31 );

/**
 * One-time upgrades for sites that already ran the setup with an older theme version.
 * 1.1.0: "Become a Partner" becomes "Refer & Earn", the menu follows RamSetu's website tree.
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_setup_done' ) || version_compare( (string) get_option( 'rs_theme_version', '1.0.0' ), '1.1.0', '>=' ) ) {
		return;
	}

	// (1.1.0 renamed "Become a Partner" to "Refer & Earn"; Round 2 changed it back, see 1.7.0.)
	$branch = get_page_by_path( 'locate-branch' );
	if ( $branch && 'Locate Branch Near You' === $branch->post_title ) {
		wp_update_post( array( 'ID' => $branch->ID, 'post_title' => 'Branch Locator' ) );
	}

	// Rebuild the menu only if it is still the one the theme created (no custom items added).
	$menu = wp_get_nav_menu_object( 'Main menu' );
	if ( $menu && (int) $menu->count <= 13 ) {
		wp_delete_nav_menu( $menu->term_id );
		$locations = get_theme_mod( 'nav_menu_locations', array() );
		unset( $locations['primary'] );
		set_theme_mod( 'nav_menu_locations', $locations );

		$ids    = array();
		$needed = array_diff_key( rs_setup_pages(), array( 'portal' => 1 ) ); // portal page arrives in 1.2.0
		foreach ( $needed as $slug => $p ) {
			$pg = get_page_by_path( $slug );
			if ( $pg ) {
				$ids[ $slug ] = $pg->ID;
			}
		}
		if ( count( $ids ) === count( $needed ) ) {
			rs_build_menus( $ids );
		}
	}

	flush_rewrite_rules( false );
	update_option( 'rs_theme_version', '1.1.0' );
}, 30 );

function rs_policies_placeholder() {
	return '<!-- wp:paragraph --><p>Our policies and regulatory disclosures. Documents will be added here.</p><!-- /wp:paragraph -->'
		. "\n\n" . '<!-- wp:paragraph --><p>To add a policy: create a new page with this "Policies" page as its parent, or upload a PDF and link it below.</p><!-- /wp:paragraph -->';
}

/**
 * 1.5.0: details confirmed by RamSetu (Oct 2026) and the founder's photo.
 * - Company details: clears saved values that are still empty / the old placeholder, so the new defaults show.
 * - Founder photo: attached to the founder's Team profile if it has none (replaceable in Team & Advisors).
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_setup_done' ) || get_option( 'rs_150_done' ) ) {
		return;
	}
	foreach ( array( 'cin', 'gst', 'grievance_name', 'grievance_phone', 'grievance_email' ) as $k ) {
		if ( '' === (string) get_theme_mod( 'rs_' . $k, '' ) ) {
			remove_theme_mod( 'rs_' . $k );
		}
	}
	if ( false !== strpos( (string) get_theme_mod( 'rs_hq_address', '' ), '400072' ) ) {
		remove_theme_mod( 'rs_hq_address' );
	}
	if ( ! post_type_exists( 'rs_team' ) ) {
		return; // plugin not active yet; try again on a later page load
	}
	$founder = get_posts( array( 'post_type' => 'rs_team', 'title' => 'Sanjeev Verma', 'posts_per_page' => 1, 'post_status' => 'any' ) );
	if ( $founder && ! has_post_thumbnail( $founder[0] ) ) {
		$id = rs_import_bundled_image( 'founder.jpg', 'Sanjeev Verma, Founder, MD & CEO, RamSetu', $founder[0]->ID );
		if ( $id ) {
			set_post_thumbnail( $founder[0], $id );
		}
	}
	update_option( 'rs_150_done', 1 );
}, 32 );

/** Copy an image shipped in assets/img/ into the Media Library. Returns the attachment ID or 0. */
function rs_import_bundled_image( $file, $alt, $parent = 0 ) {
	$src = get_template_directory() . '/assets/img/' . $file;
	if ( ! file_exists( $src ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( $file );
	if ( ! $tmp || ! copy( $src, $tmp ) ) {
		return 0;
	}
	$id = media_handle_sideload( array( 'name' => basename( $file ), 'tmp_name' => $tmp ), $parent, $alt );
	if ( is_wp_error( $id ) ) {
		@unlink( $tmp ); // phpcs:ignore
		return 0;
	}
	update_post_meta( $id, '_wp_attachment_image_alt', $alt );
	return (int) $id;
}

/**
 * 1.6.0 (Round 1 review): new Our Products menu, and the reviewed texts.
 * - Texts: a Customizer value that is still the OLD default is cleared so the new
 *   default shows; anything RamSetu typed themselves is kept.
 * - Menu: rebuilt only if it is still the one the theme created (no custom items).
 * - Founder bio and Board of Advisors added to Team & Advisors (only if missing).
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_setup_done' ) || get_option( 'rs_160_done' ) ) {
		return;
	}
	$old = array(
		'slide1_title' => '3c5eb7c778134ea8dd6681a72b09f265',
		'home_about_text' => '9f52aa7eeb0635ea809a1266c7b952e3',
		'home_founder_message' => 'edef2d72ac11d6145cef277009e0a3a7',
		'why_items' => '6faae39bcd93cf7379d038064c98dbef',
		'ra_brief' => '3a20c60eea64cb1c66f9a6a365097439',
		'ra_features' => '353550c73574746b6ecb2bbc4e830d84',
		'ra_business_title' => 'bf007783eb3e8fd64dff260f571bc522',
		'ra_business_text' => '0ee3c5b2c22d7b169e69cc7769151c6f',
		'ra_home_text' => 'fc40d3c86dd2eafb2363dd4034d814cb',
		'ra_business_size' => '8faea6a141803492d3fcac3267bb7c66',
		'ra_home_size' => '8faea6a141803492d3fcac3267bb7c66',
		'se_features' => 'b76165317998a54f4927fbc9c8bd95c1',
		'se_card_title' => '62db3a2f1ce8c315058faac49b19d476',
		'se_card_text' => '08f5ec30f7547116486cd8ef4b5b24f5',
		'se_size' => '4519f2fff65be6466dd3ebd05f54ce7b',
		'about_intro' => '80381358925447669b03d304dcc22bce',
		'about_vision' => '4839e83c8a21edd16a32891a25f1ca38',
		'about_mission' => '143447068593c3bf612c187fdbde08b8',
		'about_presence' => '3d941d49c5401da32d03f3789525d913',
		'about_live' => '550b116e625c0708b40aaadabd168e58',
		'about_upcoming' => 'ecda51bb3b10fdb83709f17263875d3a',
		'slide1_link' => 'f3c5554a6741893ea35be3687d4e7bc3',
	);
	foreach ( $old as $key => $hash ) {
		$saved = get_theme_mod( 'rs_' . $key, null );
		if ( null !== $saved && md5( (string) $saved ) === $hash ) {
			remove_theme_mod( 'rs_' . $key );
		}
	}

	$menu = wp_get_nav_menu_object( 'Main menu' );
	if ( $menu && rs_menu_is_theme_made( $menu ) ) {
		$ids    = array();
		$needed = array_diff_key( rs_setup_pages(), array( 'portal' => 1 ) );
		foreach ( $needed as $slug => $p ) {
			$pg = get_page_by_path( $slug );
			if ( $pg ) {
				$ids[ $slug ] = $pg->ID;
			}
		}
		if ( count( $ids ) === count( $needed ) ) {
			wp_delete_nav_menu( $menu->term_id );
			$locations = get_theme_mod( 'nav_menu_locations', array() );
			unset( $locations['primary'] );
			set_theme_mod( 'nav_menu_locations', $locations );
			rs_build_menus( $ids );
		}
	}

	if ( ! post_type_exists( 'rs_team' ) || ! taxonomy_exists( 'rs_team_group' ) ) {
		return; // plugin not active yet: the rest runs on a later page load
	}
	rs_seed_team_160();
	update_option( 'rs_160_done', 1 );
}, 33 );

function rs_seed_team_160() {
	$founder = get_posts( array( 'post_type' => 'rs_team', 'title' => 'Sanjeev Verma', 'posts_per_page' => 1, 'post_status' => 'any' ) );
	if ( $founder && in_array( trim( wp_strip_all_tags( $founder[0]->post_content ) ), array( '', 'Past experience — to be added.' ), true ) ) {
		wp_update_post( array( 'ID' => $founder[0]->ID, 'post_content' => rs_paras_to_blocks( RS_FOUNDER_BIO ) ) );
		update_post_meta( $founder[0]->ID, '_rs_designation', 'Founder - MD & CEO' );
	}
	if ( ! term_exists( 'advisor', 'rs_team_group' ) ) {
		wp_insert_term( 'Board of Advisors', 'rs_team_group', array( 'slug' => 'advisor' ) );
	}
	foreach ( rs_default_advisors() as $i => $a ) {
		if ( get_posts( array( 'post_type' => 'rs_team', 'title' => $a[0], 'posts_per_page' => 1, 'post_status' => 'any', 'fields' => 'ids' ) ) ) {
			continue;
		}
		$id = wp_insert_post( array(
			'post_type'    => 'rs_team',
			'post_status'  => 'publish',
			'post_title'   => $a[0],
			'post_content' => rs_paras_to_blocks( $a[1] ),
			'menu_order'   => $i + 1,
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_rs_designation', 'Board of Advisors' );
			wp_set_object_terms( $id, 'advisor', 'rs_team_group' );
		}
	}
}

/** Plain paragraphs (blank-line separated) → block-editor paragraphs. */
function rs_paras_to_blocks( $text ) {
	$out = array();
	foreach ( preg_split( '/\n\s*\n/', $text ) as $p ) {
		$out[] = '<!-- wp:paragraph --><p>' . esc_html( trim( $p ) ) . '</p><!-- /wp:paragraph -->';
	}
	return implode( "\n\n", $out );
}

/** True when every item in the menu is one the theme itself created (so rebuilding loses nothing). */
function rs_menu_is_theme_made( $menu ) {
	$known = array(
		'Our Products', 'RamAffordable – Home & Business Loans', 'Affordable Home Loans', 'Affordable Business Loans',
		'SetuEmerging – Structured Finance & Advisory', 'About Us', 'About RamSetu', 'Vision & Mission', 'Our Leadership',
		'Our Presence', 'Future Roadmap', 'Meet RamSetu Team', 'Refer & Earn', 'Become a Partner', 'Branch Locator', 'Locate Branch Near You',
		'Contact Us', 'RamAffordable', 'SetuEmerging', 'Home', 'Apply Now', 'Policies',
	);
	$items = wp_get_nav_menu_items( $menu );
	if ( ! $items ) {
		return true;
	}
	foreach ( $items as $item ) {
		if ( ! in_array( html_entity_decode( $item->title, ENT_QUOTES, 'UTF-8' ), $known, true ) ) {
			return false;
		}
	}
	return true;
}

/**
 * 1.6.1: pages that already existed before the theme (e.g. the sample "About" page that
 * WordPress.com creates) keep the "Default" template and show the sample text. Give each
 * RamSetu page its proper layout, and remove WordPress's sample text.
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_setup_done' ) || get_option( 'rs_161_done' ) ) {
		return;
	}
	foreach ( rs_setup_pages() as $slug => $p ) {
		$tpl = $p[1];
		$pg  = get_page_by_path( $slug );
		if ( ! $tpl || ! $pg ) {
			continue;
		}
		$current = (string) get_post_meta( $pg->ID, '_wp_page_template', true );
		if ( in_array( $current, array( '', 'default' ), true ) ) {
			update_post_meta( $pg->ID, '_wp_page_template', 'templates/' . $tpl . '.php' );
		}
		if ( rs_is_sample_content( $pg->post_content ) ) {
			wp_update_post( array( 'ID' => $pg->ID, 'post_content' => '' ) );
		}
	}
	update_option( 'rs_161_done', 1 );
}, 34 );

/** WordPress / WordPress.com starter text that should never show on the RamSetu site. */
function rs_is_sample_content( $content ) {
	$content = wp_strip_all_tags( (string) $content );
	foreach ( array( 'This is an example of a page', 'This is an example page', 'Click the Edit link to make changes' ) as $needle ) {
		if ( false !== stripos( $content, $needle ) ) {
			return true;
		}
	}
	return false;
}

// Never show WordPress's starter text on the site, even before the cleanup above has run.
add_filter( 'the_content', function ( $content ) {
	return rs_is_sample_content( $content ) ? '' : $content;
}, 1 );


/**
 * 1.6.3: loan tenure is "up to 15 years" everywhere (confirmed by RamSetu, 13 Oct).
 * Also fixes the feature lists if they were saved in the Customizer with "20 Years".
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_setup_done' ) || get_option( 'rs_163_done' ) ) {
		return;
	}
	foreach ( array( 'rs_ra_features', 'rs_se_features', 'rs_special_items', 'rs_why_items' ) as $mod ) {
		$saved = get_theme_mod( $mod, null );
		if ( is_string( $saved ) && preg_match( '/\b20\s+Years\b/i', $saved ) ) {
			set_theme_mod( $mod, preg_replace( '/\b20(\s+Years)\b/i', '15$1', $saved ) );
		}
	}
	update_option( 'rs_163_done', 1 );
}, 35 );


/**
 * 1.7.0 (Round 2 review, 13 Oct): reviewed texts, "Refer & Earn" is "Become a Partner" again,
 * and Loans Against Property comes before Home Loans in the menu.
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_setup_done' ) || get_option( 'rs_170_done' ) ) {
		return;
	}
	// Texts still at the previous default follow the new default.
	$old = array(
		'ra_loans_for'   => 'f165706504b69042b2c2c0629fbacbe9',
		'se_features'    => 'd791a545c18fcf8a760fa2b49a1b73d5',
		'se_loans_for'   => '7127f37284f18631e98c82e1f4d8581b',
		'se_industries'  => '479f6a0f9ebbf4d916a8d6e74c845653',
		'se_solutions'   => 'e727cae37f2eb30fe694ce6bb9b84686',
		'about_presence' => 'f0524a1c705dad2e37e79ebad482de0f',
		'about_upcoming' => '4fe1e3c47b8d38b9a0ff4aaa8ed160b2',
		'partner_steps'  => '06f08603fdbcb9eed8844cf0daab2167',
	);
	foreach ( $old as $key => $hash ) {
		$saved = get_theme_mod( 'rs_' . $key, null );
		if ( null !== $saved && md5( (string) $saved ) === $hash ) {
			remove_theme_mod( 'rs_' . $key );
		}
	}

	$page = get_page_by_path( 'refer-and-earn' );
	if ( $page && ! get_page_by_path( 'become-a-partner' ) ) {
		wp_update_post( array( 'ID' => $page->ID, 'post_title' => 'Become a Partner', 'post_name' => 'become-a-partner' ) );
		update_option( 'rs_partner_old_slug', 'refer-and-earn' );
	}

	$menu  = wp_get_nav_menu_object( 'Main menu' );
	$items = $menu ? wp_get_nav_menu_items( $menu ) : array();
	$by    = array();
	foreach ( (array) $items as $item ) {
		$title = html_entity_decode( $item->title, ENT_QUOTES, 'UTF-8' );
		if ( 'Refer & Earn' === $title ) {
			wp_update_post( array( 'ID' => $item->ID, 'post_title' => 'Become a Partner' ) );
		}
		$by[ $title ] = $item;
	}
	// Swap Home Loans / LAP when Home Loans is still listed first under the same parent.
	if ( isset( $by['Affordable Home Loans'], $by['Affordable Loans Against Property'] ) ) {
		$hl  = $by['Affordable Home Loans'];
		$lap = $by['Affordable Loans Against Property'];
		if ( $hl->menu_item_parent === $lap->menu_item_parent && $hl->menu_order < $lap->menu_order ) {
			wp_update_post( array( 'ID' => $lap->ID, 'menu_order' => $hl->menu_order ) );
			wp_update_post( array( 'ID' => $hl->ID, 'menu_order' => $lap->menu_order ) );
		}
	}
	update_option( 'rs_170_done', 1 );
}, 36 );

// Old "Refer & Earn" address keeps working.
add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' ); // phpcs:ignore
	if ( 'refer-and-earn' === $path || 'become-a-partner' === $path ) {
		$url = rs_page_url( 'partner' );
		if ( $url && false === strpos( $url, '/' . $path . '/' ) ) {
			wp_safe_redirect( $url, 301 );
			exit;
		}
	}
} );


/**
 * 1.8.0 (About Us update, 17 Oct): "Our Advisors" with designations, Our Steadfast Supporters
 * & Partners, Meet RamSetu Team, and the team link in the About Us menu.
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_setup_done' ) || get_option( 'rs_180_done' ) ) {
		return;
	}
	if ( ! post_type_exists( 'rs_team' ) || ! taxonomy_exists( 'rs_team_group' ) ) {
		return; // plugin not active yet: runs on a later page load
	}
	foreach ( array( 'supporter' => 'Supporters & Partners', 'team' => 'RamSetu Team' ) as $slug => $name ) {
		if ( ! term_exists( $slug, 'rs_team_group' ) ) {
			wp_insert_term( $name, 'rs_team_group', array( 'slug' => $slug ) );
		}
	}

	// Advisors: no "Board" wording; designations as RamSetu gave them.
	$designations = rs_advisor_designations();
	foreach ( get_posts( array( 'post_type' => 'rs_team', 'posts_per_page' => 50, 'post_status' => 'any', 'tax_query' => array( array( 'taxonomy' => 'rs_team_group', 'field' => 'slug', 'terms' => 'advisor' ) ) ) ) as $p ) {
		$current = (string) get_post_meta( $p->ID, '_rs_designation', true );
		if ( in_array( $current, array( '', 'Board of Advisors' ), true ) ) {
			update_post_meta( $p->ID, '_rs_designation', $designations[ $p->post_title ] ?? 'Advisor' );
		}
	}
	// Aarti Kapoor is confirmed again (17 Oct); Bipin Oberoi and Ajay Shah stay in draft until RamSetu confirms.
	$aarti = get_posts( array( 'post_type' => 'rs_team', 'title' => 'Aarti Kapoor', 'posts_per_page' => 1, 'post_status' => 'draft' ) );
	if ( $aarti ) {
		wp_update_post( array( 'ID' => $aarti[0]->ID, 'post_status' => 'publish' ) );
	}
	$group = get_term_by( 'slug', 'advisor', 'rs_team_group' );
	if ( $group && 'Board of Advisors' === $group->name ) {
		wp_update_term( $group->term_id, 'rs_team_group', array( 'name' => 'Advisors' ) );
	}

	$add = function ( $name, $designation, $bio, $group_slug, $order ) {
		if ( get_posts( array( 'post_type' => 'rs_team', 'title' => $name, 'posts_per_page' => 1, 'post_status' => 'any', 'fields' => 'ids' ) ) ) {
			return;
		}
		$id = wp_insert_post( array(
			'post_type'    => 'rs_team',
			'post_status'  => 'publish',
			'post_title'   => $name,
			'post_content' => $bio ? rs_paras_to_blocks( $bio ) : '',
			'menu_order'   => $order,
		) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_rs_designation', $designation );
			wp_set_object_terms( $id, $group_slug, 'rs_team_group' );
		}
	};
	foreach ( rs_default_supporters() as $i => $s ) {
		$add( $s[0], $s[1], $s[2], 'supporter', $i + 1 );
	}
	foreach ( rs_default_team() as $i => $t ) {
		$add( $t[0], $t[1], '', 'team', $i + 1 );
	}

	// "Meet RamSetu Team" under About Us, right after Our Leadership (theme-made menu items only).
	$menu  = wp_get_nav_menu_object( 'Main menu' );
	$items = $menu ? wp_get_nav_menu_items( $menu ) : array();
	$about = null;
	$lead  = null;
	$has   = false;
	foreach ( (array) $items as $item ) {
		$title = html_entity_decode( $item->title, ENT_QUOTES, 'UTF-8' );
		if ( 'About Us' === $title && ! (int) $item->menu_item_parent ) {
			$about = $item;
		}
		if ( 'Our Leadership' === $title ) {
			$lead = $item;
		}
		if ( 'Meet RamSetu Team' === $title ) {
			$has = true;
		}
	}
	$about_page = get_page_by_path( 'about' );
	if ( $menu && $about && $lead && ! $has && $about_page ) {
		foreach ( $items as $item ) {
			if ( (int) $item->menu_order > (int) $lead->menu_order ) {
				wp_update_post( array( 'ID' => $item->ID, 'menu_order' => (int) $item->menu_order + 1 ) );
			}
		}
		wp_update_nav_menu_item( $menu->term_id, 0, array(
			'menu-item-title'     => 'Meet RamSetu Team',
			'menu-item-url'       => get_permalink( $about_page ) . '#team',
			'menu-item-type'      => 'custom',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $about->ID,
			'menu-item-position'  => (int) $lead->menu_order + 1,
		) );
	}
	update_option( 'rs_180_done', 1 );
}, 37 );

/**
 * Photos for Supporters & Partners and the Team, shipped in assets/img/people/.
 * Runs once per theme version, so photos added in a later version are picked up;
 * only fills a profile that has no photo yet (a photo set in Team & Advisors is kept).
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_180_done' ) || RS_THEME_VERSION === get_option( 'rs_people_photos' ) ) {
		return;
	}
	$list = array();
	foreach ( rs_default_supporters() as $s ) {
		$list[] = array( $s[0], $s[3], $s[1] );
	}
	foreach ( rs_default_team() as $t ) {
		$list[] = array( $t[0], $t[2], $t[1] );
	}
	foreach ( $list as $row ) {
		if ( ! file_exists( get_template_directory() . '/assets/img/people/' . $row[1] ) ) {
			continue;
		}
		$p = get_posts( array( 'post_type' => 'rs_team', 'title' => $row[0], 'posts_per_page' => 1, 'post_status' => 'any' ) );
		if ( $p && ! has_post_thumbnail( $p[0] ) ) {
			$id = rs_import_bundled_image( 'people/' . $row[1], $row[0] . ', ' . $row[2], $p[0]->ID );
			if ( $id ) {
				set_post_thumbnail( $p[0], $id );
			}
		}
	}
	update_option( 'rs_people_photos', RS_THEME_VERSION );
}, 38 );

/**
 * 1.9.0 (website changes, 20 Oct): "Secured Business Loans", Phase 1 roadmap text without the
 * ₹10 crore target, three team members removed (kept as drafts), and the Siddaganga
 * entry renamed to include "Tumkur, Karnataka".
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_180_done' ) || get_option( 'rs_190_done' ) ) {
		return;
	}
	$old = array(
		'ra_tagline' => '48beb72f9549f1c7e9bba6fb9e86be70',
		'roadmap'    => '5672338638276a9ea89b36db7b0cd801',
	);
	foreach ( $old as $key => $hash ) {
		$saved = get_theme_mod( 'rs_' . $key, null );
		if ( null !== $saved && md5( (string) $saved ) === $hash ) {
			remove_theme_mod( 'rs_' . $key );
		}
	}
	foreach ( array( 'Ranganatha K L', 'Ravichandra H R', 'Nandish C P' ) as $name ) {
		foreach ( get_posts( array( 'post_type' => 'rs_team', 'title' => $name, 'posts_per_page' => 5, 'post_status' => 'publish' ) ) as $p ) {
			wp_update_post( array( 'ID' => $p->ID, 'post_status' => 'draft' ) );
		}
	}
	$sit = get_posts( array( 'post_type' => 'rs_team', 'title' => 'Siddaganga Institute of Technology', 'posts_per_page' => 1, 'post_status' => 'any' ) );
	if ( $sit ) {
		wp_update_post( array( 'ID' => $sit[0]->ID, 'post_title' => 'Siddaganga Institute of Technology, Tumkur, Karnataka' ) );
	}
	update_option( 'rs_190_done', 1 );
}, 37 );

/** 1.11.1 (25 Oct): "Meet RamSetu Team" hidden for now; remove its menu link. Team profiles are kept. */
add_action( 'init', function () {
	if ( ! get_option( 'rs_setup_done' ) || get_option( 'rs_1111_done' ) ) {
		return;
	}
	foreach ( wp_get_nav_menus() as $menu ) {
		foreach ( (array) wp_get_nav_menu_items( $menu ) as $item ) {
			if ( 'Meet RamSetu Team' === html_entity_decode( $item->title, ENT_QUOTES, 'UTF-8' ) ) {
				wp_delete_post( $item->ID, true );
			}
		}
	}
	update_option( 'rs_1111_done', 1 );
}, 39 );

/**
 * 1.13.0 (Home page review, 30 Oct): new About and Verticals texts follow the new defaults if the
 * old ones were never edited; Ajay Shah and Bipin Oberoi join the Advisors (order: Aarti > Ajay > Bipin).
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_setup_done' ) || get_option( 'rs_1130_done' ) ) {
		return;
	}
	$old = array(
		'home_about_text' => '9c6496e3c6e33ee9578d46674b43bfb1',
		'products_title'  => '490875f21168e77e8c00ef1ea104583d',
		'ra_home_card'    => '52cdb186c73746339f3eaa125b191107',
		'se_home_card'    => '28e1ec5381bd1e721d0e9908ef2aaca1',
	);
	foreach ( $old as $key => $hash ) {
		$saved = get_theme_mod( 'rs_' . $key, null );
		if ( null !== $saved && md5( (string) $saved ) === $hash ) {
			remove_theme_mod( 'rs_' . $key );
		}
	}
	if ( post_type_exists( 'rs_team' ) ) {
		$order = array( 'Aarti Kapoor' => 1, 'Mr. Ajay Shah' => 2, 'Mr. Bipin Oberoi' => 3 );
		foreach ( $order as $name => $pos ) {
			$p = get_posts( array( 'post_type' => 'rs_team', 'title' => $name, 'posts_per_page' => 1, 'post_status' => 'any' ) );
			if ( ! $p ) {
				foreach ( rs_default_advisors() as $a ) {
					if ( $a[0] === $name ) {
						$id = wp_insert_post( array( 'post_type' => 'rs_team', 'post_status' => 'publish', 'post_title' => $name, 'post_content' => rs_paras_to_blocks( $a[1] ), 'menu_order' => $pos ) );
						if ( $id && ! is_wp_error( $id ) ) {
							wp_set_object_terms( $id, 'advisor', 'rs_team_group' );
						}
					}
				}
				$p = get_posts( array( 'post_type' => 'rs_team', 'title' => $name, 'posts_per_page' => 1, 'post_status' => 'any' ) );
				if ( ! $p ) {
					continue;
				}
			}
			wp_update_post( array( 'ID' => $p[0]->ID, 'post_status' => 'publish', 'menu_order' => $pos ) );
			if ( '' === (string) get_post_meta( $p[0]->ID, '_rs_designation', true ) || 'Board of Advisors' === get_post_meta( $p[0]->ID, '_rs_designation', true ) ) {
				update_post_meta( $p[0]->ID, '_rs_designation', 'Advisor' );
			}
		}
	}
	update_option( 'rs_1130_done', 1 );
}, 39 );

/**
 * 1.14.0 (30 Oct): make sure Ajay Shah and Bipin Oberoi show under Our Advisors, whatever their
 * saved title, status or group (Aarti > Ajay > Bipin).
 */
add_action( 'init', function () {
	if ( ! get_option( 'rs_setup_done' ) || get_option( 'rs_1140_done' ) || ! post_type_exists( 'rs_team' ) ) {
		return;
	}
	$people = array(
		array( 'Aarti', 'Aarti Kapoor', 1 ),
		array( 'Ajay', 'Mr. Ajay Shah', 2 ),
		array( 'Bipin', 'Mr. Bipin Oberoi', 3 ),
	);
	$all = get_posts( array( 'post_type' => 'rs_team', 'posts_per_page' => 200, 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future', 'trash' ) ) );
	foreach ( $people as $row ) {
		list( $word, $name, $pos ) = $row;
		$found = null;
		foreach ( $all as $p ) {
			if ( false !== stripos( $p->post_title, $word ) ) {
				$found = $p;
				break;
			}
		}
		if ( ! $found ) {
			foreach ( rs_default_advisors() as $a ) {
				if ( $a[0] === $name ) {
					$id = wp_insert_post( array( 'post_type' => 'rs_team', 'post_status' => 'publish', 'post_title' => $name, 'post_content' => rs_paras_to_blocks( $a[1] ), 'menu_order' => $pos ) );
					$found = $id && ! is_wp_error( $id ) ? get_post( $id ) : null;
				}
			}
		}
		if ( ! $found ) {
			continue;
		}
		if ( 'trash' === $found->post_status ) {
			wp_untrash_post( $found->ID );
		}
		wp_update_post( array( 'ID' => $found->ID, 'post_status' => 'publish', 'menu_order' => $pos ) );
		wp_set_object_terms( $found->ID, 'advisor', 'rs_team_group' );
		$d = (string) get_post_meta( $found->ID, '_rs_designation', true );
		if ( '' === $d || 'Board of Advisors' === $d ) {
			update_post_meta( $found->ID, '_rs_designation', 'Advisor' );
		}
	}
	update_option( 'rs_1140_done', 1 );
}, 40 );
