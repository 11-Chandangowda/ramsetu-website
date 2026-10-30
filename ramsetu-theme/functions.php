<?php
/**
 * RamSetu theme.
 */

defined( 'ABSPATH' ) || exit;

define( 'RS_THEME_VERSION', '1.14.0' );

require_once get_template_directory() . '/inc/defaults.php';
require_once get_template_directory() . '/inc/helpers.php';
require_once get_template_directory() . '/inc/customizer.php';
require_once get_template_directory() . '/inc/people.php';
require_once get_template_directory() . '/inc/setup.php';

add_action( 'after_setup_theme', function () {
	load_theme_textdomain( 'ramsetu', get_template_directory() . '/languages' );
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'responsive-embeds' );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script', 'navigation-widgets' ) );
	add_theme_support( 'custom-logo', array( 'height' => 128, 'width' => 128, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'editor-styles' );
	add_editor_style( 'assets/css/main.css' );

	register_nav_menus( array(
		'primary' => __( 'Main menu', 'ramsetu' ),
		'footer'  => __( 'Footer – company links', 'ramsetu' ),
	) );

	add_image_size( 'rs-portrait', 480, 560, true );
	add_image_size( 'rs-slide', 1200, 900, true );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'rs-main', get_template_directory_uri() . '/assets/css/main.css', array(), RS_THEME_VERSION );
	wp_enqueue_script( 'rs-main', get_template_directory_uri() . '/assets/js/main.js', array(), RS_THEME_VERSION, true );
} );

add_action( 'wp_head', function () {
	printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_template_directory_uri() . '/assets/fonts/plus-jakarta-sans-latin-wght-normal.woff2' ) );
}, 1 );

// Favicon fallback when no Site Icon is set in the Customizer.
add_action( 'wp_head', function () {
	if ( ! has_site_icon() ) {
		printf( '<link rel="icon" href="%s" type="image/png">' . "\n", esc_url( get_template_directory_uri() . '/assets/img/logo-128.png' ) );
	}
	echo '<meta name="theme-color" content="#F26B1D">' . "\n";
}, 2 );

// Admin notice if the leads plugin is missing — the forms depend on it.
add_action( 'admin_notices', function () {
	if ( ! function_exists( 'rsl_states' ) && current_user_can( 'activate_plugins' ) ) {
		echo '<div class="notice notice-warning"><p><strong>RamSetu theme:</strong> ' . esc_html__( 'Activate the "RamSetu Leads" plugin so the Apply Now, Become a Partner and Call Back forms can save submissions.', 'ramsetu' ) . '</p></div>';
	}
} );

// Keep the WP admin bar from covering the sticky header.
add_action( 'wp_head', function () {
	if ( is_admin_bar_showing() ) {
		echo '<style>.site-header{top:32px}@media (max-width:782px){.site-header{top:46px}}@media (max-width:600px){.site-header{top:0}}</style>';
	}
} );
