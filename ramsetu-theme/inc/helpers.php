<?php
/**
 * Template helpers.
 */

defined( 'ABSPATH' ) || exit;

/** URL of the page using templates/{$slug}.php (falls back to /{$slug}/). */
function rs_page_url( $slug ) {
	static $cache = array();
	if ( isset( $cache[ $slug ] ) ) {
		return $cache[ $slug ];
	}
	$pages = get_posts( array(
		'post_type'      => 'page',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		'meta_key'       => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_value'     => 'templates/' . $slug . '.php', // phpcs:ignore WordPress.DB.SlowDBQuery
		'fields'         => 'ids',
	) );
	$cache[ $slug ] = $pages ? get_permalink( $pages[0] ) : home_url( '/' . $slug . '/' );
	return $cache[ $slug ];
}

/** Resolve a link token ("ramaffordable#home-loan", "/path", "https://…"). */
function rs_link( $token ) {
	$token = trim( (string) $token );
	if ( '' === $token ) {
		return '';
	}
	if ( preg_match( '#^(https?:)?//|^/|^\#|^tel:|^mailto:#', $token ) ) {
		return $token;
	}
	$parts = explode( '#', $token, 2 );
	return rs_page_url( $parts[0] ) . ( isset( $parts[1] ) ? '#' . $parts[1] : '' );
}

/** Apply Now link; the header script turns it into the modal. */
function rs_apply_attrs( $category = '' ) {
	$url = rs_page_url( 'apply' );
	if ( $category ) {
		$url = add_query_arg( 'service', $category, $url );
	}
	return sprintf( 'href="%s" data-apply%s', esc_url( $url ), $category ? ' data-apply-category="' . esc_attr( $category ) . '"' : '' );
}

function rs_has_leads() {
	return function_exists( 'rsl_states' );
}

function rs_branches() {
	return rs_has_leads() ? rsl_get_branches() : array();
}

function rs_branches_for_js() {
	$out = array();
	foreach ( rs_branches() as $b ) {
		$out[] = array(
			'state'   => $b['state'],
			'city'    => $b['city'],
			'name'    => $b['name'],
			'address' => $b['address'],
			'phone'   => $b['phone'],
			'email'   => $b['email'],
			'map'     => $b['map'],
		);
	}
	return $out;
}

function rs_walkin_hours() {
	return rs_has_leads() ? rsl_settings()['walkin_hours'] : 'Monday to Saturday, 9:00 AM – 6:00 PM';
}

/** Small note only logged-in editors see, pointing at where to fill something in. */
/**
 * "Fill this in" notes for logged-in admins. Switched off for go-live (they made finished
 * pages look incomplete during review). To show them again while editing, add
 * define( 'RS_SHOW_ADMIN_HINTS', true ); to wp-config.php.
 */
function rs_admin_hint( $text ) {
	if ( defined( 'RS_SHOW_ADMIN_HINTS' ) && RS_SHOW_ADMIN_HINTS && current_user_can( 'edit_theme_options' ) ) {
		printf( '<p class="rs-hint">%s</p>', esc_html( $text ) );
	}
}

/** Whether admin-only placeholder sections should be shown (same switch as the hints). */
function rs_show_placeholders() {
	return defined( 'RS_SHOW_ADMIN_HINTS' ) && RS_SHOW_ADMIN_HINTS && current_user_can( 'edit_posts' );
}

function rs_initials( $name ) {
	$words = preg_split( '/\s+/', trim( preg_replace( '/^(mr|mrs|ms|dr|prof)\.?\s+/i', '', trim( $name ) ) ) );
	$i     = '';
	foreach ( array_slice( $words, 0, 2 ) as $w ) {
		$i .= mb_substr( $w, 0, 1 );
	}
	return mb_strtoupper( $i );
}

function rs_tel( $phone ) {
	return 'tel:' . preg_replace( '/[^\d+]/', '', (string) $phone );
}

/** Inline icons (stroke, 24x24). */
function rs_icon( $name, $class = '' ) {
	$p = array(
		'arrow'    => '<path d="M5 12h14M13 6l6 6-6 6"/>',
		'phone'    => '<path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/>',
		'mail'     => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
		'pin'      => '<path d="M12 21s-7-6.2-7-11a7 7 0 0 1 14 0c0 4.8-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
		'clock'    => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
		'check'    => '<path d="m5 12 5 5L20 7"/>',
		'home'     => '<path d="m3 11 9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
		'store'    => '<path d="M4 9h16l-1-5H5L4 9z"/><path d="M4 9a2.7 2.7 0 0 0 5.3 0 2.7 2.7 0 0 0 5.4 0 2.7 2.7 0 0 0 5.3 0"/><path d="M5 11v9h14v-9"/><path d="M10 20v-5h4v5"/>',
		'compass'  => '<circle cx="12" cy="12" r="9"/><path d="m15.5 8.5-2 5-5 2 2-5 5-2z"/>',
		'users'    => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M21.5 20a6.5 6.5 0 0 0-4-6"/>',
		'shield'   => '<path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3z"/><path d="m9 12 2 2 4-4"/>',
		'door'     => '<path d="M5 21V4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v17"/><path d="M3 21h18"/><circle cx="15" cy="12" r="1"/>',
		'spark'    => '<path d="M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6"/>',
		'rupee'    => '<path d="M7 5h10M7 9h10M7 5c4.5 0 6 1.5 6 4s-2 4-6 4l7 6"/>',
		'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4M16 3v4"/>',
		'percent'  => '<path d="M19 5 5 19"/><circle cx="7" cy="7" r="2.5"/><circle cx="17" cy="17" r="2.5"/>',
		'chevron'  => '<path d="m6 9 6 6 6-6"/>',
		'close'    => '<path d="M6 6l12 12M18 6 6 18"/>',
		'menu'     => '<path d="M4 7h16M4 12h16M4 17h16"/>',
		'linkedin' => '<rect x="3" y="3" width="18" height="18" rx="3"/><path d="M8 10v7M8 7v.01M12 17v-4a2 2 0 0 1 4 0v4M12 10v7"/>',
		'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v3H7v4h3v6h4v-6h3l1-4h-4V8z"/>',
		'instagram'=> '<rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><path d="M17.5 6.5v.01"/>',
		'youtube'  => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="m10 9 5 3-5 3V9z"/>',
		'whatsapp' => '<path d="M4 20l1.3-4A8 8 0 1 1 8 18.7L4 20z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1-1.5-2-1-1 1a4 4 0 0 1-2-2l1-1-1-2L9 9.5z"/>',
		'file'     => '<path d="M14 3H6a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V8l-5-5z"/><path d="M14 3v5h5M9 13h6M9 17h6"/>',
	);
	if ( ! isset( $p[ $name ] ) ) {
		return '';
	}
	return '<svg class="icon ' . esc_attr( $class ) . '" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $p[ $name ] . '</svg>';
}

/**
 * Product card. $a: title, text, size, tenure, rate, category, id, icon, tone (orange|blue|green).
 */
function rs_product_card( $a ) {
	$facts = array_filter( array(
		array( 'rupee', __( 'Loan size', 'ramsetu' ), $a['size'] ?? '' ),
		array( 'calendar', __( 'Tenure', 'ramsetu' ), $a['tenure'] ?? '' ),
		array( 'percent', __( 'Interest rate', 'ramsetu' ), $a['rate'] ?? '' ),
	), function ( $f ) {
		return '' !== trim( $f[2] );
	} );
	?>
	<?php $photo = ! empty( $a['photo'] ) ? rs_photo( $a['photo'][0], $a['photo'][1], $a['photo'][2] ?? '', '', '(max-width: 640px) 92vw, 480px' ) : ''; ?>
	<article class="offer offer--<?php echo esc_attr( $a['tone'] ?? 'orange' ); ?><?php echo $photo ? ' offer--photo' : ''; ?>"<?php echo ! empty( $a['id'] ) ? ' id="' . esc_attr( $a['id'] ) . '"' : ''; ?>>
		<?php if ( $photo ) : ?><div class="offer__photo"><?php echo $photo; // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?>
		<div class="offer__icon"><?php echo rs_icon( $a['icon'] ?? 'home' ); // phpcs:ignore WordPress.Security.EscapeOutput ?></div>
		<h3 class="offer__title"><?php echo esc_html( $a['title'] ); ?></h3>
		<?php if ( '' !== trim( (string) ( $a['text'] ?? '' ) ) ) : ?><p class="offer__text"><?php echo esc_html( $a['text'] ); ?></p><?php endif; ?>
		<?php if ( $facts ) : ?>
			<dl class="offer__facts">
				<?php foreach ( $facts as $f ) : ?>
					<div><dt><?php echo rs_icon( $f[0] ); // phpcs:ignore ?><?php echo esc_html( $f[1] ); ?></dt><dd><?php echo esc_html( $f[2] ); ?></dd></div>
				<?php endforeach; ?>
			</dl>
		<?php endif; ?>
		<a class="btn btn--light offer__cta" <?php echo rs_apply_attrs( $a['category'] ?? '' ); // phpcs:ignore ?>><?php esc_html_e( 'Apply Now', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a>
	</article>
	<?php
}

/**
 * Page banner used on inner pages.
 * $photo: a bundled backdrop (assets/img/photos/hero-*.webp); it can be replaced in
 * Customize → RamSetu → Page banners ("hero_<name>_image").
 */
function rs_page_hero( $eyebrow, $title, $text = '', $tone = 'orange', $photo = '' ) {
	$bg = $photo ? rs_photo( 'hero_' . $photo . '_image', 'hero-' . $photo, '', '', '100vw', true ) : '';
	?>
	<section class="page-hero page-hero--<?php echo esc_attr( $tone ); ?><?php echo $bg ? ' page-hero--photo page-hero--bg-' . esc_attr( $photo ) : ''; ?>">
		<?php if ( $bg ) : ?><div class="page-hero__bg" aria-hidden="true"><?php echo $bg; // phpcs:ignore WordPress.Security.EscapeOutput ?></div><?php endif; ?>
		<div class="container">
			<?php if ( $eyebrow ) : ?><p class="eyebrow"><?php echo esc_html( $eyebrow ); ?></p><?php endif; ?>
			<h1 class="page-hero__title"><?php echo esc_html( $title ); ?></h1>
			<?php if ( $text ) : ?><p class="page-hero__text"><?php echo esc_html( $text ); ?></p><?php endif; ?>
		</div>
		<?php get_template_part( 'parts/bridge-art', null, array( 'class' => 'page-hero__art' ) ); ?>
	</section>
	<?php
}

/** Team member card. */
function rs_person( WP_Post $p, $size = 'md' ) {
	$designation = get_post_meta( $p->ID, '_rs_designation', true );
	?>
	<div class="person person--<?php echo esc_attr( $size ); ?>">
		<div class="person__photo">
			<?php if ( has_post_thumbnail( $p ) ) : ?>
				<?php echo get_the_post_thumbnail( $p, 'rs-portrait', array( 'alt' => esc_attr( $p->post_title ) ) ); ?>
			<?php else : ?>
				<span class="person__initials" aria-hidden="true"><?php echo esc_html( rs_initials( $p->post_title ) ); ?></span>
			<?php endif; ?>
		</div>
		<div class="person__body">
			<h3 class="person__name"><?php echo esc_html( $p->post_title ); ?></h3>
			<?php if ( $designation ) : ?><p class="person__role"><?php echo esc_html( $designation ); ?></p><?php endif; ?>
		</div>
	</div>
	<?php
}

function rs_team( $group ) {
	return class_exists( 'RSL_Content' ) ? RSL_Content::team( $group ) : array();
}

function rs_partners() {
	return class_exists( 'RSL_Content' ) ? RSL_Content::partners() : array();
}

/** What to search for on the map: the address if known, otherwise city + state. */
function rs_branch_map_query( array $b ) {
	$addr = trim( (string) ( $b['address'] ?? '' ) );
	if ( '' === $addr || false !== stripos( $addr, 'to be confirmed' ) ) {
		return trim( ( $b['city'] ?? '' ) . ', ' . ( $b['state'] ?? '' ), ', ' );
	}
	return preg_replace( '/\s+/', ' ', $addr . ', ' . ( $b['city'] ?? '' ) . ', ' . ( $b['state'] ?? '' ) );
}

/** Google Maps embed (no API key needed). */
function rs_map_embed_url( $query ) {
	return 'https://maps.google.com/maps?q=' . rawurlencode( $query ) . '&z=13&output=embed';
}

/**
 * Photo with a Customizer override.
 * Uses the media-library image picked in Customize ($mod_key) when set,
 * otherwise the photo bundled with the theme (assets/img/photos/$file.webp
 * plus a smaller "$file-NNN.webp" for phones).
 */
function rs_photo( $mod_key, $file, $alt, $class = '', $sizes = '100vw', $eager = false ) {
	$id   = $mod_key ? (int) rs_mod( $mod_key ) : 0;
	$attr = array(
		'class'    => $class,
		'alt'      => $alt,
		'loading'  => $eager ? 'eager' : 'lazy',
		'decoding' => 'async',
		'sizes'    => $sizes,
	);
	if ( $eager ) {
		$attr['fetchpriority'] = 'high';
	}
	if ( $id && wp_attachment_is_image( $id ) ) {
		if ( get_post_meta( $id, '_wp_attachment_image_alt', true ) ) {
			unset( $attr['alt'] ); // keep the alt text written in the Media Library
		}
		return wp_get_attachment_image( $id, 'large', false, $attr );
	}

	$dir   = get_template_directory() . '/assets/img/photos/';
	$url   = get_template_directory_uri() . '/assets/img/photos/';
	$large = $dir . $file . '.webp';
	if ( ! file_exists( $large ) ) {
		return '';
	}
	$dim    = getimagesize( $large );
	$srcset = array( $url . $file . '.webp?v=' . RS_THEME_VERSION . ' ' . $dim[0] . 'w' );
	foreach ( glob( $dir . $file . '-*.webp' ) ?: array() as $small ) {
		if ( preg_match( '/-(\d+)\.webp$/', $small, $m ) ) {
			$srcset[] = $url . basename( $small ) . '?v=' . RS_THEME_VERSION . ' ' . $m[1] . 'w';
		}
	}
	$attr['src']    = $url . $file . '.webp?v=' . RS_THEME_VERSION;
	$attr['srcset'] = implode( ', ', $srcset );
	$attr['width']  = $dim[0];
	$attr['height'] = $dim[1];

	$html = '<img';
	foreach ( $attr as $k => $v ) {
		if ( '' !== $v ) {
			$html .= ' ' . $k . '="' . esc_attr( $v ) . '"';
		}
	}
	return $html . '>';
}

/** Partner / employee login (or registration) in the RamSetu app. */
function rs_portal_link( $view = '' ) {
	if ( function_exists( 'rsl_portal_url' ) ) {
		return rsl_portal_url( $view );
	}
	return rs_page_url( 'portal' );
}
