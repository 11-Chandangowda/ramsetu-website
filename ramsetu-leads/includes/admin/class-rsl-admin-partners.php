<?php
/**
 * Admin: RamSetu Leads → Channel Partners, and → Employees.
 */

defined( 'ABSPATH' ) || exit;

class RSL_Admin_Partners {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 20 );
		add_action( 'admin_post_rsl_partner_action', array( __CLASS__, 'handle_partner' ) );
		add_action( 'admin_post_rsl_employee_add', array( __CLASS__, 'handle_employee_add' ) );
		add_action( 'admin_post_rsl_employee_action', array( __CLASS__, 'handle_employee' ) );
	}

	public static function menu() {
		$pending = self::count( 'pending' );
		$badge   = $pending ? ' <span class="awaiting-mod"><span class="pending-count">' . (int) $pending . '</span></span>' : '';
		add_submenu_page( 'rsl-leads', __( 'Channel Partners', 'ramsetu-leads' ), __( 'Channel Partners', 'ramsetu-leads' ) . $badge, 'rsl_manage_leads', 'rsl-partners', array( __CLASS__, 'page_partners' ), 3 );
		add_submenu_page( 'rsl-leads', __( 'Employees', 'ramsetu-leads' ), __( 'Employees', 'ramsetu-leads' ), 'rsl_manage_leads', 'rsl-employees', array( __CLASS__, 'page_employees' ), 4 );
	}

	private static function count( $status = '' ) {
		global $wpdb;
		$t = RSL_DB::table( 'partner' );
		return (int) ( $status
			? $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE status = %s", $status ) )
			: $wpdb->get_var( "SELECT COUNT(*) FROM $t" ) );
	}

	private static function lead_count( $col, $val ) {
		global $wpdb;
		$t = RSL_DB::table( 'lead' );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE $col = %d", $val ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	private static function notice() {
		// phpcs:ignore WordPress.Security.NonceVerification
		$m = isset( $_GET['rsl_msg'] ) ? sanitize_key( $_GET['rsl_msg'] ) : '';
		// phpcs:ignore WordPress.Security.NonceVerification
		$err = isset( $_GET['rsl_err'] ) ? sanitize_text_field( wp_unslash( $_GET['rsl_err'] ) ) : '';
		$messages = array(
			'approved'  => __( 'Approved. The partner has been emailed their Partner ID and a link to set their password.', 'ramsetu-leads' ),
			'rejected'  => __( 'Application rejected. The applicant has been informed by email.', 'ramsetu-leads' ),
			'suspended' => __( 'Partner suspended. They can no longer log in.', 'ramsetu-leads' ),
			'reactivated' => __( 'Partner reactivated.', 'ramsetu-leads' ),
			'pan'       => __( 'PAN verification updated.', 'ramsetu-leads' ),
			'resent'    => __( 'Login email sent again.', 'ramsetu-leads' ),
			'saved'     => __( 'Saved.', 'ramsetu-leads' ),
			'emp_added' => __( 'Employee added. They have been emailed their Employee ID and a link to set their password.', 'ramsetu-leads' ),
			'emp_off'   => __( 'Employee deactivated. They can no longer log in.', 'ramsetu-leads' ),
			'emp_on'    => __( 'Employee reactivated.', 'ramsetu-leads' ),
		);
		if ( isset( $messages[ $m ] ) ) {
			printf( '<div class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $messages[ $m ] ) );
		}
		if ( $err ) {
			printf( '<div class="notice notice-error is-dismissible"><p>%s</p></div>', esc_html( $err ) );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Partners                                                            */
	/* ------------------------------------------------------------------ */

	public static function page_partners() {
		// phpcs:ignore WordPress.Security.NonceVerification
		$view = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
		echo '<div class="wrap rsl-wrap">';
		if ( $view ) {
			self::partner_detail( $view );
		} else {
			self::partner_list();
		}
		echo '</div>';
	}

	private static function partner_list() {
		// phpcs:disable WordPress.Security.NonceVerification
		$status = isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '';
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$paged  = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		// phpcs:enable
		$args = array( 'search' => $search, 'per_page' => 30, 'page' => $paged );
		if ( isset( rsl_partner_statuses()[ $status ] ) ) {
			$args['status'] = $status;
		}
		$res  = RSL_DB::query( 'partner', $args );
		$base = admin_url( 'admin.php?page=rsl-partners' );

		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Channel Partners', 'ramsetu-leads' ) . '</h1>';
		printf( ' <a href="%s" class="page-title-action" target="_blank">%s</a>', esc_url( rsl_portal_url( 'register' ) ), esc_html__( 'Open registration page', 'ramsetu-leads' ) );
		echo '<hr class="wp-header-end">';
		self::notice();
		echo '<p class="description">' . esc_html__( 'CAs, DSAs and agents register on the Partner Portal. Verify their PAN, then approve to issue their Partner ID and login.', 'ramsetu-leads' ) . '</p>';

		echo '<ul class="subsubsub">';
		$tabs = array( '' => __( 'All', 'ramsetu-leads' ) ) + rsl_partner_statuses();
		$i    = 0;
		foreach ( $tabs as $k => $label ) {
			printf( '<li>%s<a href="%s"%s>%s <span class="count">(%d)</span></a></li>', $i++ ? ' | ' : '', esc_url( $k ? add_query_arg( 'status', $k, $base ) : $base ), $status === $k ? ' class="current"' : '', esc_html( $label ), (int) self::count( $k ) );
		}
		echo '</ul>';
		printf( '<form method="get" class="search-form" style="float:right;margin:8px 0"><input type="hidden" name="page" value="rsl-partners"><input type="search" name="s" value="%s" placeholder="%s"> <button class="button">%s</button></form>', esc_attr( $search ), esc_attr__( 'Name, mobile, PAN, ID', 'ramsetu-leads' ), esc_html__( 'Search', 'ramsetu-leads' ) );

		echo '<table class="wp-list-table widefat fixed striped" style="clear:both"><thead><tr><th>' . esc_html__( 'Partner', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Type', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Mobile', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Location', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'PAN', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Partner ID', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Leads', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Status', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Registered', 'ramsetu-leads' ) . '</th></tr></thead><tbody>';
		if ( ! $res['rows'] ) {
			echo '<tr><td colspan="9">' . esc_html__( 'No partners here yet.', 'ramsetu-leads' ) . '</td></tr>';
		}
		foreach ( $res['rows'] as $r ) {
			printf(
				'<tr><td><a class="row-title" href="%s">%s</a>%s</td><td>%s</td><td>%s</td><td>%s</td><td><code>%s</code> %s</td><td>%s</td><td>%d</td><td><span class="rsl-badge rsl-badge--p-%s">%s</span></td><td>%s</td></tr>',
				esc_url( add_query_arg( 'view', (int) $r['id'], $base ) ),
				esc_html( $r['full_name'] ),
				$r['firm_name'] ? '<br><span class="rsl-muted">' . esc_html( $r['firm_name'] ) . '</span>' : '',
				esc_html( rsl_label( rsl_partner_types(), $r['partner_type'] ) ),
				esc_html( $r['mobile'] ),
				esc_html( $r['city'] . ', ' . $r['state'] ),
				esc_html( $r['pan'] ),
				$r['pan_verified'] ? '<span class="rsl-ok" title="' . esc_attr__( 'PAN verified', 'ramsetu-leads' ) . '">✓</span>' : '',
				$r['partner_code'] ? '<strong>' . esc_html( $r['partner_code'] ) . '</strong>' : '—',
				self::lead_count( 'partner_id', (int) $r['id'] ),
				esc_attr( $r['status'] ),
				esc_html( rsl_label( rsl_partner_statuses(), $r['status'] ) ),
				esc_html( date_i18n( 'd M Y', strtotime( $r['created_at'] ) ) )
			);
		}
		echo '</tbody></table>';
		$pages = (int) ceil( $res['total'] / 30 );
		if ( $pages > 1 ) {
			echo '<p>' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $paged, 'total' => $pages ) ) ) . '</p>';
		}
	}

	private static function action_form( $id, $do, $label, $class = 'button', $confirm = '', $extra = '' ) {
		printf( '<form method="post" action="%s" style="display:inline-block;margin:0 6px 6px 0"%s>', esc_url( admin_url( 'admin-post.php' ) ), $confirm ? ' data-confirm-form="' . esc_attr( $confirm ) . '"' : '' );
		wp_nonce_field( 'rsl_partner_' . $id );
		printf( '<input type="hidden" name="action" value="rsl_partner_action"><input type="hidden" name="id" value="%d"><input type="hidden" name="do" value="%s">%s<button class="%s">%s</button></form>', (int) $id, esc_attr( $do ), $extra, esc_attr( $class ), esc_html( $label ) ); // phpcs:ignore WordPress.Security.EscapeOutput
	}

	private static function partner_detail( $id ) {
		$r = RSL_DB::get( 'partner', $id );
		printf( '<p><a href="%s">&larr; %s</a></p>', esc_url( admin_url( 'admin.php?page=rsl-partners' ) ), esc_html__( 'All partners', 'ramsetu-leads' ) );
		if ( ! $r ) {
			echo '<h1>' . esc_html__( 'Not found', 'ramsetu-leads' ) . '</h1>';
			return;
		}
		printf( '<h1>%s <span class="rsl-badge rsl-badge--p-%s">%s</span>%s</h1>', esc_html( $r['full_name'] ), esc_attr( $r['status'] ), esc_html( rsl_label( rsl_partner_statuses(), $r['status'] ) ), $r['partner_code'] ? ' <code style="font-size:15px">' . esc_html( $r['partner_code'] ) . '</code>' : '' );
		self::notice();

		echo '<div class="rsl-detail"><div class="rsl-detail__main"><div class="rsl-card"><h2>' . esc_html__( 'Registration details', 'ramsetu-leads' ) . '</h2><table class="rsl-kv">';
		$rows = array(
			__( 'Registered', 'ramsetu-leads' ) => date_i18n( 'd M Y, g:i A', strtotime( $r['created_at'] ) ),
			__( 'Type', 'ramsetu-leads' )       => rsl_label( rsl_partner_types(), $r['partner_type'] ),
			__( 'Firm', 'ramsetu-leads' )       => $r['firm_name'] ?: '—',
			__( 'Mobile', 'ramsetu-leads' )     => $r['mobile'],
			__( 'Email', 'ramsetu-leads' )      => $r['email'],
			__( 'City', 'ramsetu-leads' )       => $r['city'],
			__( 'District', 'ramsetu-leads' )   => $r['district'],
			__( 'State', 'ramsetu-leads' )      => $r['state'],
			__( 'PAN', 'ramsetu-leads' )        => $r['pan'],
		);
		foreach ( $rows as $k => $v ) {
			printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html( $k ), esc_html( $v ) );
		}
		if ( $r['approved_at'] ) {
			printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html__( 'Approved', 'ramsetu-leads' ), esc_html( date_i18n( 'd M Y, g:i A', strtotime( $r['approved_at'] ) ) . ' · ' . get_the_author_meta( 'display_name', $r['approved_by'] ) ) );
		}
		echo '</table></div>';

		if ( $r['id'] ) {
			$n = self::lead_count( 'partner_id', (int) $r['id'] );
			printf( '<div class="rsl-card"><h2>%s</h2><p>%s</p><p><a class="button" href="%s">%s</a></p></div>', esc_html__( 'Leads', 'ramsetu-leads' ), esc_html( sprintf( _n( '%d lead submitted by this partner.', '%d leads submitted by this partner.', $n, 'ramsetu-leads' ), $n ) ), esc_url( admin_url( 'admin.php?page=rsl-leads&partner_id=' . (int) $r['id'] ) ), esc_html__( 'View their leads', 'ramsetu-leads' ) );
		}

		echo '<div class="rsl-card"><h2>' . esc_html__( 'Activity', 'ramsetu-leads' ) . '</h2><ol class="rsl-timeline">';
		foreach ( RSL_DB::activity( 'partner', $id ) as $a ) {
			printf( '<li><span class="rsl-timeline__when">%s</span> <strong>%s</strong> · %s</li>', esc_html( date_i18n( 'd M Y, g:i A', strtotime( $a['created_at'] ) ) ), esc_html( $a['user_id'] ? get_the_author_meta( 'display_name', $a['user_id'] ) : __( 'Website', 'ramsetu-leads' ) ), esc_html( $a['note'] ? $a['note'] : $a['action'] ) );
		}
		echo '</ol></div></div><div class="rsl-detail__side">';

		// Step 1: PAN
		echo '<div class="rsl-card"><h2>' . esc_html__( '1. Verify PAN', 'ramsetu-leads' ) . '</h2>';
		if ( $r['pan_verified'] ) {
			printf( '<p class="rsl-ok-line">✓ %s</p>', esc_html( sprintf( __( 'Verified on %1$s by %2$s', 'ramsetu-leads' ), date_i18n( 'd M Y', strtotime( $r['pan_verified_at'] ) ), get_the_author_meta( 'display_name', $r['pan_verified_by'] ) ) ) );
			if ( $r['pan_name'] ) {
				printf( '<p>%s <strong>%s</strong></p>', esc_html__( 'Name on PAN:', 'ramsetu-leads' ), esc_html( $r['pan_name'] ) );
			}
			self::action_form( $id, 'pan_unverify', __( 'Remove verification', 'ramsetu-leads' ), 'button-link' );
		} else {
			printf( '<p>%s <code>%s</code></p><p class="description">%s</p>', esc_html__( 'PAN:', 'ramsetu-leads' ), esc_html( $r['pan'] ), esc_html__( 'Check this PAN in your verification provider\'s dashboard (or the Income Tax portal). Confirm the name on PAN matches the applicant, then mark it verified.', 'ramsetu-leads' ) );
			self::action_form( $id, 'pan_verify', __( 'Mark PAN verified', 'ramsetu-leads' ), 'button', '', '<p><label>' . esc_html__( 'Name as per PAN', 'ramsetu-leads' ) . '<br><input type="text" name="pan_name" class="widefat" required value="' . esc_attr( $r['full_name'] ) . '"></label></p>' );
		}
		echo '</div>';

		// Step 2: decision
		echo '<div class="rsl-card"><h2>' . esc_html__( '2. Decision', 'ramsetu-leads' ) . '</h2>';
		switch ( $r['status'] ) {
			case 'pending':
				if ( $r['pan_verified'] ) {
					self::action_form( $id, 'approve', __( 'Approve & send login', 'ramsetu-leads' ), 'button button-primary' );
				} else {
					echo '<p class="description">' . esc_html__( 'Verify the PAN first to enable approval.', 'ramsetu-leads' ) . '</p>';
				}
				self::action_form( $id, 'reject', __( 'Reject', 'ramsetu-leads' ), 'button', __( 'Reject this application? The applicant will be emailed.', 'ramsetu-leads' ), '<p><label>' . esc_html__( 'Reason (included in the email, optional)', 'ramsetu-leads' ) . '<br><textarea name="note" rows="2" class="widefat"></textarea></label></p>' );
				break;
			case 'approved':
				printf( '<p>%s <strong>%s</strong></p>', esc_html__( 'Partner ID:', 'ramsetu-leads' ), esc_html( $r['partner_code'] ) );
				self::action_form( $id, 'resend', __( 'Resend login email', 'ramsetu-leads' ) );
				self::action_form( $id, 'suspend', __( 'Suspend', 'ramsetu-leads' ), 'button', __( 'Suspend this partner? They will be logged out and cannot log in.', 'ramsetu-leads' ) );
				break;
			case 'suspended':
				self::action_form( $id, 'reactivate', __( 'Reactivate', 'ramsetu-leads' ), 'button button-primary' );
				break;
			case 'rejected':
				echo '<p class="description">' . esc_html__( 'Rejected. The applicant can register again with the same PAN.', 'ramsetu-leads' ) . '</p>';
				break;
		}
		echo '</div>';

		// Notes
		echo '<form class="rsl-card" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><h2>' . esc_html__( 'Internal notes', 'ramsetu-leads' ) . '</h2>';
		wp_nonce_field( 'rsl_partner_' . $id );
		printf( '<input type="hidden" name="action" value="rsl_partner_action"><input type="hidden" name="id" value="%d"><input type="hidden" name="do" value="note"><textarea name="admin_note" rows="4" class="widefat">%s</textarea><p><button class="button">%s</button></p></form>', (int) $id, esc_textarea( (string) $r['admin_note'] ), esc_html__( 'Save notes', 'ramsetu-leads' ) );
		echo '</div></div>';
	}

	public static function handle_partner() {
		$id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		check_admin_referer( 'rsl_partner_' . $id );
		if ( ! RSL_Roles::can_manage() ) {
			wp_die( esc_html__( 'Not allowed.', 'ramsetu-leads' ), 403 );
		}
		$do   = sanitize_key( $_POST['do'] ?? '' );
		$back = admin_url( 'admin.php?page=rsl-partners&view=' . $id );
		$msg  = 'saved';
		$err  = '';
		$r    = RSL_DB::get( 'partner', $id );
		if ( ! $r ) {
			wp_die( esc_html__( 'Not found.', 'ramsetu-leads' ) );
		}
		switch ( $do ) {
			case 'pan_verify':
				RSL_Partners::set_pan_verified( $id, true, sanitize_text_field( wp_unslash( $_POST['pan_name'] ?? '' ) ) );
				$msg = 'pan';
				break;
			case 'pan_unverify':
				RSL_Partners::set_pan_verified( $id, false );
				$msg = 'pan';
				break;
			case 'approve':
				if ( ! $r['pan_verified'] ) {
					$err = __( 'Verify the PAN before approving.', 'ramsetu-leads' );
					break;
				}
				$res = RSL_Partners::approve( $id );
				if ( is_wp_error( $res ) ) {
					$err = $res->get_error_message();
				} else {
					$msg = 'approved';
				}
				break;
			case 'reject':
				RSL_Partners::set_status( $id, 'rejected', sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) ) );
				$msg = 'rejected';
				break;
			case 'suspend':
				RSL_Partners::set_status( $id, 'suspended' );
				$msg = 'suspended';
				break;
			case 'reactivate':
				RSL_Partners::set_status( $id, 'approved' );
				$msg = 'reactivated';
				break;
			case 'resend':
				if ( $r['user_id'] ) {
					RSL_Partners::send_welcome( (int) $r['user_id'], 'partner' );
					$msg = 'resent';
				}
				break;
			case 'note':
				RSL_DB::update( 'partner', $id, array( 'admin_note' => sanitize_textarea_field( wp_unslash( $_POST['admin_note'] ?? '' ) ) ) );
				break;
		}
		wp_safe_redirect( $err ? add_query_arg( 'rsl_err', rawurlencode( $err ), $back ) : add_query_arg( 'rsl_msg', $msg, $back ) );
		exit;
	}

	/* ------------------------------------------------------------------ */
	/* Employees                                                           */
	/* ------------------------------------------------------------------ */

	public static function page_employees() {
		$users = get_users( array( 'role' => 'rs_employee', 'orderby' => 'login' ) );
		echo '<div class="wrap rsl-wrap"><h1>' . esc_html__( 'Employees', 'ramsetu-leads' ) . '</h1>';
		self::notice();
		echo '<p class="description">' . esc_html__( 'Employees log in to the Partner Portal with their Employee ID to submit leads daily. Leads they submit appear under Leads with source "Employee".', 'ramsetu-leads' ) . '</p>';
		echo '<div class="rsl-detail"><div class="rsl-detail__main"><div class="rsl-card"><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Employee ID', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Name', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Email / mobile', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Leads', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Status', 'ramsetu-leads' ) . '</th><th></th></tr></thead><tbody>';
		if ( ! $users ) {
			echo '<tr><td colspan="6">' . esc_html__( 'No employees yet. Add one using the form.', 'ramsetu-leads' ) . '</td></tr>';
		}
		foreach ( $users as $u ) {
			$off = (bool) get_user_meta( $u->ID, 'rsl_suspended', true );
			echo '<tr><td><strong>' . esc_html( RSL_Partners::user_code( $u->ID ) ) . '</strong></td><td>' . esc_html( $u->display_name ) . '</td><td>' . esc_html( $u->user_email ) . '<br><span class="rsl-muted">' . esc_html( get_user_meta( $u->ID, 'rsl_mobile', true ) ) . '</span></td>';
			printf( '<td><a href="%s">%d</a></td>', esc_url( admin_url( 'admin.php?page=rsl-leads&submitted_by=' . $u->ID ) ), self::lead_count( 'submitted_by', $u->ID ) );
			echo '<td>' . ( $off ? esc_html__( 'Deactivated', 'ramsetu-leads' ) : esc_html__( 'Active', 'ramsetu-leads' ) ) . '</td><td>';
			foreach ( ( $off ? array( 'on' => __( 'Reactivate', 'ramsetu-leads' ) ) : array( 'resend' => __( 'Resend login email', 'ramsetu-leads' ), 'off' => __( 'Deactivate', 'ramsetu-leads' ) ) ) as $do => $label ) {
				printf( '<form method="post" action="%s" style="display:inline-block;margin-right:6px">', esc_url( admin_url( 'admin-post.php' ) ) );
				wp_nonce_field( 'rsl_emp_' . $u->ID );
				printf( '<input type="hidden" name="action" value="rsl_employee_action"><input type="hidden" name="uid" value="%d"><input type="hidden" name="do" value="%s"><button class="button-link">%s</button></form>', (int) $u->ID, esc_attr( $do ), esc_html( $label ) );
			}
			echo '</td></tr>';
		}
		echo '</tbody></table></div></div><div class="rsl-detail__side">';
		echo '<form class="rsl-card" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><h2>' . esc_html__( 'Add employee', 'ramsetu-leads' ) . '</h2>';
		wp_nonce_field( 'rsl_employee_add' );
		echo '<input type="hidden" name="action" value="rsl_employee_add">';
		foreach ( array( 'name' => __( 'Full name', 'ramsetu-leads' ), 'email' => __( 'Work email', 'ramsetu-leads' ), 'mobile' => __( 'Mobile', 'ramsetu-leads' ) ) as $k => $label ) {
			printf( '<p><label for="emp-%1$s"><strong>%2$s</strong></label><input class="widefat" id="emp-%1$s" name="%1$s" type="%3$s"%4$s></p>', esc_attr( $k ), esc_html( $label ), 'email' === $k ? 'email' : 'text', 'mobile' === $k ? '' : ' required' );
		}
		echo '<p class="description">' . esc_html__( 'An Employee ID (e.g. RSEMP0001) is created and emailed with a link to set a password.', 'ramsetu-leads' ) . '</p>';
		submit_button( __( 'Add employee', 'ramsetu-leads' ), 'primary', 'submit', false );
		echo '</form></div></div></div>';
	}

	public static function handle_employee_add() {
		check_admin_referer( 'rsl_employee_add' );
		if ( ! RSL_Roles::can_manage() ) {
			wp_die( esc_html__( 'Not allowed.', 'ramsetu-leads' ), 403 );
		}
		$res  = RSL_Partners::create_employee(
			sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) ),
			sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			rsl_normalize_phone( wp_unslash( $_POST['mobile'] ?? '' ) )
		);
		$back = admin_url( 'admin.php?page=rsl-employees' );
		wp_safe_redirect( is_wp_error( $res ) ? add_query_arg( 'rsl_err', rawurlencode( $res->get_error_message() ), $back ) : add_query_arg( 'rsl_msg', 'emp_added', $back ) );
		exit;
	}

	public static function handle_employee() {
		$uid = isset( $_POST['uid'] ) ? absint( $_POST['uid'] ) : 0;
		check_admin_referer( 'rsl_emp_' . $uid );
		$u = get_userdata( $uid );
		if ( ! RSL_Roles::can_manage() || ! $u || ! in_array( 'rs_employee', (array) $u->roles, true ) ) {
			wp_die( esc_html__( 'Not allowed.', 'ramsetu-leads' ), 403 );
		}
		$do  = sanitize_key( $_POST['do'] ?? '' );
		$msg = 'saved';
		if ( 'off' === $do ) {
			update_user_meta( $uid, 'rsl_suspended', 1 );
			WP_Session_Tokens::get_instance( $uid )->destroy_all();
			$msg = 'emp_off';
		} elseif ( 'on' === $do ) {
			delete_user_meta( $uid, 'rsl_suspended' );
			$msg = 'emp_on';
		} elseif ( 'resend' === $do ) {
			RSL_Partners::send_welcome( $uid, 'employee' );
			$msg = 'resent';
		}
		wp_safe_redirect( add_query_arg( 'rsl_msg', $msg, admin_url( 'admin.php?page=rsl-employees' ) ) );
		exit;
	}
}
