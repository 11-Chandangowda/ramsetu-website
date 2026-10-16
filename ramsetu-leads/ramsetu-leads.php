<?php
/**
 * Plugin Name:       RamSetu Leads
 * Description:       Lead capture and management for RamSetu Financial Services — Apply Now leads, partner referrals, call-back requests, branches, team and partners. Built Phase-2-ready for channel partner and employee portals.
 * Version:           1.4.1
 * Requires at least: 6.2
 * Requires PHP:      7.4
 * Author:            Liveon Technolabs
 * Text Domain:       ramsetu-leads
 */

defined( 'ABSPATH' ) || exit;

define( 'RSL_VERSION', '1.4.1' );
define( 'RSL_DB_VERSION', '1.4.0' );
define( 'RSL_FILE', __FILE__ );
define( 'RSL_DIR', plugin_dir_path( __FILE__ ) );
define( 'RSL_URL', plugin_dir_url( __FILE__ ) );

require_once RSL_DIR . 'includes/options.php';
require_once RSL_DIR . 'includes/class-rsl-db.php';
require_once RSL_DIR . 'includes/class-rsl-roles.php';
require_once RSL_DIR . 'includes/class-rsl-content.php';
require_once RSL_DIR . 'includes/class-rsl-forms.php';
require_once RSL_DIR . 'includes/class-rsl-notify.php';
require_once RSL_DIR . 'includes/class-rsl-partners.php';
require_once RSL_DIR . 'includes/class-rsl-portal.php';

if ( is_admin() ) {
	require_once RSL_DIR . 'includes/admin/class-rsl-admin.php';
	require_once RSL_DIR . 'includes/admin/class-rsl-list-table.php';
	require_once RSL_DIR . 'includes/admin/class-rsl-admin-partners.php';
}

register_activation_hook( __FILE__, 'rsl_activate' );
function rsl_activate() {
	RSL_DB::install();
	RSL_Roles::install();
	RSL_Content::register();
	if ( false === get_option( 'rsl_branches' ) ) {
		update_option( 'rsl_branches', rsl_default_branches() );
	}
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );

// Database / role upgrades after a plugin update (on init, so role names can be translated).
add_action( 'init', function () {
	if ( get_option( 'rsl_db_version' ) !== RSL_DB_VERSION ) {
		RSL_DB::install();
		RSL_Roles::install();
		// 1.3.0: real branch details replace the placeholders (only if nobody edited them yet).
		$saved = get_option( 'rsl_branches' );
		if ( is_array( $saved ) ) {
			foreach ( $saved as $b ) {
				if ( 'Address to be confirmed' === ( $b['address'] ?? '' ) || false !== strpos( (string) ( $b['address'] ?? '' ), '400072' ) ) {
					update_option( 'rsl_branches', rsl_default_branches() );
					break;
				}
			}
		}
	}
}, 0 );

add_action( 'plugins_loaded', function () {
	RSL_Content::init();
	RSL_Forms::init();
	RSL_Partners::init();
	RSL_Portal::init();
	if ( is_admin() ) {
		RSL_Admin::init();
		RSL_Admin_Partners::init();
	}
} );

// Sales officers land on their leads (not their profile page) after logging in.
add_filter( 'login_redirect', function ( $url, $requested, $user ) {
	if ( $user instanceof WP_User && in_array( 'rs_sales_officer', (array) $user->roles, true ) && ( ! $requested || false !== strpos( $requested, 'wp-admin/' ) && false === strpos( $requested, 'page=' ) ) ) {
		return admin_url( 'admin.php?page=rsl-leads' );
	}
	return $url;
}, 10, 3 );
