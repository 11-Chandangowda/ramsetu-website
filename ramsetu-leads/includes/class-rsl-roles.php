<?php
/**
 * Roles and capabilities.
 *
 * rsl_manage_leads  – see and edit everything, assign leads (Administrator, Lead Manager)
 * rsl_work_leads    – see and update only leads assigned to you (Sales Officer)
 *
 * Channel Partner and Employee roles are created now so user accounts can be
 * set up ahead of Phase 2; they get no admin access yet.
 */

defined( 'ABSPATH' ) || exit;

class RSL_Roles {

	public static function install() {
		$admin = get_role( 'administrator' );
		if ( $admin ) {
			$admin->add_cap( 'rsl_manage_leads' );
			$admin->add_cap( 'rsl_work_leads' );
		}

		add_role( 'rs_lead_manager', __( 'RamSetu Lead Manager', 'ramsetu-leads' ), array(
			'read'             => true,
			'rsl_manage_leads' => true,
			'rsl_work_leads'   => true,
		) );
		add_role( 'rs_sales_officer', __( 'RamSetu Sales Officer', 'ramsetu-leads' ), array(
			'read'           => true,
			'rsl_work_leads' => true,
		) );
		// Portal users: log in at the Partner Portal page only, never wp-admin.
		remove_role( 'rs_channel_partner' );
		remove_role( 'rs_employee' );
		add_role( 'rs_channel_partner', __( 'RamSetu Channel Partner', 'ramsetu-leads' ), array( 'read' => true, 'rsl_portal' => true ) );
		add_role( 'rs_employee', __( 'RamSetu Employee', 'ramsetu-leads' ), array( 'read' => true, 'rsl_portal' => true ) );
	}

	/** People a lead can be assigned to. */
	public static function assignable_users() {
		return get_users( array(
			'role__in' => array( 'rs_sales_officer', 'rs_lead_manager', 'administrator' ),
			'orderby'  => 'display_name',
			'fields'   => array( 'ID', 'display_name', 'user_email' ),
		) );
	}

	/** True for users who only use the portal (partners, employees). */
	public static function is_portal_user( $user = null ) {
		$user = $user ? $user : wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return false;
		}
		$roles = (array) $user->roles;
		return (bool) array_intersect( $roles, array( 'rs_channel_partner', 'rs_employee' ) ) && ! user_can( $user, 'edit_posts' ) && ! user_can( $user, 'rsl_work_leads' );
	}

	public static function can_manage() {
		return current_user_can( 'rsl_manage_leads' );
	}

	public static function can_view_row( array $row ) {
		if ( self::can_manage() ) {
			return true;
		}
		return current_user_can( 'rsl_work_leads' ) && (int) $row['assigned_to'] === get_current_user_id();
	}
}
