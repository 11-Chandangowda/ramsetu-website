<?php
/**
 * Admin screens: Overview, Leads, Referral Log, Call-back Log, Branches, Settings.
 */

defined( 'ABSPATH' ) || exit;

class RSL_Admin {

	const PAGES = array( 'lead' => 'rsl-leads', 'referral' => 'rsl-referrals', 'callback' => 'rsl-callbacks' );

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_rsl_update', array( __CLASS__, 'handle_update' ) );
		add_action( 'admin_post_rsl_convert', array( __CLASS__, 'handle_convert' ) );
		add_action( 'admin_post_rsl_add_lead', array( __CLASS__, 'handle_add_lead' ) );
		add_action( 'admin_post_rsl_export', array( __CLASS__, 'handle_export' ) );
		add_action( 'admin_post_rsl_save_branches', array( __CLASS__, 'handle_branches' ) );
		add_action( 'admin_post_rsl_save_settings', array( __CLASS__, 'handle_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'handle_bulk' ) );
		add_filter( 'set-screen-option', function ( $status, $option, $value ) {
			return 'rsl_per_page' === $option ? (int) $value : $status;
		}, 10, 3 );
	}

	public static function menu() {
		$new   = RSL_Roles::can_manage() ? RSL_DB::count_new( 'lead' ) + RSL_DB::count_new( 'referral' ) + RSL_DB::count_new( 'callback' ) : 0;
		$badge = $new ? ' <span class="awaiting-mod"><span class="pending-count">' . (int) $new . '</span></span>' : '';

		add_menu_page( __( 'RamSetu Leads', 'ramsetu-leads' ), __( 'RamSetu Leads', 'ramsetu-leads' ) . $badge, 'rsl_work_leads', 'rsl-leads', array( __CLASS__, 'page_leads' ), 'dashicons-id-alt', 3 );

		$hooks   = array();
		$hooks[] = add_submenu_page( 'rsl-leads', __( 'Leads', 'ramsetu-leads' ), self::sub_label( __( 'Leads', 'ramsetu-leads' ), 'lead' ), 'rsl_work_leads', 'rsl-leads', array( __CLASS__, 'page_leads' ) );
		$hooks[] = add_submenu_page( 'rsl-leads', __( 'Referral Log', 'ramsetu-leads' ), self::sub_label( __( 'Referral Log', 'ramsetu-leads' ), 'referral' ), 'rsl_manage_leads', 'rsl-referrals', array( __CLASS__, 'page_referrals' ) );
		$hooks[] = add_submenu_page( 'rsl-leads', __( 'Call-back Log', 'ramsetu-leads' ), self::sub_label( __( 'Call-back Log', 'ramsetu-leads' ), 'callback' ), 'rsl_manage_leads', 'rsl-callbacks', array( __CLASS__, 'page_callbacks' ) );
		add_submenu_page( 'rsl-leads', __( 'Overview', 'ramsetu-leads' ), __( 'Overview', 'ramsetu-leads' ), 'rsl_manage_leads', 'rsl-overview', array( __CLASS__, 'page_overview' ) );
		add_submenu_page( 'rsl-leads', __( 'Branches', 'ramsetu-leads' ), __( 'Branches', 'ramsetu-leads' ), 'rsl_manage_leads', 'rsl-branches', array( __CLASS__, 'page_branches' ) );
		add_submenu_page( 'rsl-leads', __( 'Settings', 'ramsetu-leads' ), __( 'Settings', 'ramsetu-leads' ), 'rsl_manage_leads', 'rsl-settings', array( __CLASS__, 'page_settings' ) );

		foreach ( $hooks as $h ) {
			add_action( 'load-' . $h, function () {
				add_screen_option( 'per_page', array( 'label' => __( 'Rows per page', 'ramsetu-leads' ), 'default' => 20, 'option' => 'rsl_per_page' ) );
			} );
		}
	}

	private static function sub_label( $label, $object ) {
		if ( ! RSL_Roles::can_manage() ) {
			return $label;
		}
		$n = RSL_DB::count_new( $object );
		return $n ? $label . ' <span class="awaiting-mod"><span class="pending-count">' . (int) $n . '</span></span>' : $label;
	}

	public static function assets( $hook ) {
		if ( false === strpos( $hook, 'rsl-' ) ) {
			return;
		}
		wp_enqueue_style( 'rsl-admin', RSL_URL . 'assets/admin.css', array(), RSL_VERSION );
		wp_enqueue_script( 'rsl-admin', RSL_URL . 'assets/admin.js', array(), RSL_VERSION, true );
	}

	/* ------------------------------------------------------------------ */
	/* Pages                                                               */
	/* ------------------------------------------------------------------ */

	public static function page_leads() {
		self::list_or_view( 'lead', __( 'Leads', 'ramsetu-leads' ) );
	}

	public static function page_referrals() {
		self::list_or_view( 'referral', __( 'Referral Log', 'ramsetu-leads' ) );
	}

	public static function page_callbacks() {
		self::list_or_view( 'callback', __( 'Call-back Log', 'ramsetu-leads' ) );
	}

	private static function notice() {
		// phpcs:ignore WordPress.Security.NonceVerification
		$m = isset( $_GET['rsl_msg'] ) ? sanitize_key( $_GET['rsl_msg'] ) : '';
		$messages = array(
			'saved'     => __( 'Saved.', 'ramsetu-leads' ),
			'deleted'   => __( 'Deleted.', 'ramsetu-leads' ),
			'converted' => __( 'Converted into a lead. You can now assign it to a Sales Officer.', 'ramsetu-leads' ),
			'added'     => __( 'Lead added.', 'ramsetu-leads' ),
			'invalid'   => __( 'Please fill in name and a valid 10-digit mobile number.', 'ramsetu-leads' ),
		);
		if ( isset( $messages[ $m ] ) ) {
			printf( '<div class="notice notice-%s is-dismissible"><p>%s</p></div>', 'invalid' === $m ? 'error' : 'success', esc_html( $messages[ $m ] ) );
		}
	}

	private static function list_or_view( $object, $title ) {
		// phpcs:ignore WordPress.Security.NonceVerification
		$view = isset( $_GET['view'] ) ? absint( $_GET['view'] ) : 0;
		// phpcs:ignore WordPress.Security.NonceVerification
		$add  = 'lead' === $object && isset( $_GET['add'] ) && RSL_Roles::can_manage();

		echo '<div class="wrap rsl-wrap">';
		if ( $view ) {
			self::render_detail( $object, $view );
		} elseif ( $add ) {
			self::render_add_lead();
		} else {
			echo '<h1 class="wp-heading-inline">' . esc_html( $title ) . '</h1>';
			if ( 'lead' === $object && RSL_Roles::can_manage() ) {
				printf( ' <a href="%s" class="page-title-action">%s</a>', esc_url( admin_url( 'admin.php?page=rsl-leads&add=1' ) ), esc_html__( 'Add lead manually', 'ramsetu-leads' ) );
			}
			if ( ! RSL_Roles::can_manage() ) {
				echo '<p class="description">' . esc_html__( 'Leads assigned to you.', 'ramsetu-leads' ) . '</p>';
			}
			echo '<hr class="wp-header-end">';
			self::notice();
			// phpcs:disable WordPress.Security.NonceVerification
			if ( 'lead' === $object && RSL_Roles::can_manage() && ( ! empty( $_GET['partner_id'] ) || ! empty( $_GET['submitted_by'] ) ) ) {
				$who = '';
				if ( ! empty( $_GET['partner_id'] ) ) {
					$pp  = RSL_DB::get( 'partner', absint( $_GET['partner_id'] ) );
					$who = $pp ? $pp['full_name'] . ' (' . $pp['partner_code'] . ')' : '';
				} else {
					$uu  = get_userdata( absint( $_GET['submitted_by'] ) );
					$who = $uu ? $uu->display_name . ' (' . RSL_Partners::user_code( $uu->ID ) . ')' : '';
				}
				printf( '<div class="notice notice-info"><p>%s <strong>%s</strong> · <a href="%s">%s</a></p></div>', esc_html__( 'Showing leads submitted by', 'ramsetu-leads' ), esc_html( $who ), esc_url( admin_url( 'admin.php?page=rsl-leads' ) ), esc_html__( 'Show all', 'ramsetu-leads' ) );
			}
			// phpcs:enable
			$table = new RSL_List_Table( $object, self::PAGES[ $object ] );
			$table->prepare_items();
			$table->views();
			echo '<form method="get">';
			printf( '<input type="hidden" name="page" value="%s">', esc_attr( self::PAGES[ $object ] ) );
			wp_nonce_field( 'rsl_bulk', 'rsl_bulk_nonce', false );
			$table->search_box( __( 'Search name / phone', 'ramsetu-leads' ), 'rsl-search' );
			$table->display();
			echo '</form>';
		}
		echo '</div>';
	}

	private static function render_detail( $object, $id ) {
		$row = RSL_DB::get( $object, $id );
		if ( ! $row || ! RSL_Roles::can_view_row( $row ) ) {
			echo '<h1>' . esc_html__( 'Not found', 'ramsetu-leads' ) . '</h1><p>' . esc_html__( 'This record does not exist or is not assigned to you.', 'ramsetu-leads' ) . '</p>';
			return;
		}
		$page     = self::PAGES[ $object ];
		$statuses = 'lead' === $object ? rsl_lead_statuses() : rsl_simple_statuses();
		$manage   = RSL_Roles::can_manage();
		$back     = admin_url( 'admin.php?page=' . $page );

		$titles = array( 'lead' => $row['full_name'] ?? '', 'referral' => $row['referred_name'] ?? '', 'callback' => $row['full_name'] ?? '' );
		printf( '<p><a href="%s">&larr; %s</a></p>', esc_url( $back ), esc_html__( 'Back to list', 'ramsetu-leads' ) );
		printf( '<h1>%s <span class="rsl-badge rsl-badge--%s">%s</span></h1>', esc_html( $titles[ $object ] ), esc_attr( $row['status'] ), esc_html( rsl_label( $statuses, $row['status'] ) ) );
		self::notice();

		echo '<div class="rsl-detail"><div class="rsl-detail__main">';

		// Submitted details.
		echo '<div class="rsl-card"><h2>' . esc_html__( 'Submitted details', 'ramsetu-leads' ) . '</h2><table class="rsl-kv">';
		foreach ( self::detail_rows( $object, $row ) as $label => $value ) {
			printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html( $label ), $value ); // phpcs:ignore WordPress.Security.EscapeOutput -- values escaped in detail_rows()
		}
		echo '</table></div>';

		// Activity.
		echo '<div class="rsl-card"><h2>' . esc_html__( 'Activity', 'ramsetu-leads' ) . '</h2><ol class="rsl-timeline">';
		foreach ( RSL_DB::activity( $object, $id ) as $a ) {
			$who = $a['user_id'] ? get_the_author_meta( 'display_name', $a['user_id'] ) : __( 'Website', 'ramsetu-leads' );
			printf(
				'<li><span class="rsl-timeline__when">%s</span> <strong>%s</strong> · %s%s</li>',
				esc_html( date_i18n( 'd M Y, g:i A', strtotime( $a['created_at'] ) ) ),
				esc_html( $who ),
				esc_html( self::action_label( $a['action'] ) ),
				$a['note'] ? '<div class="rsl-timeline__note">' . nl2br( esc_html( $a['note'] ) ) . '</div>' : ''
			);
		}
		echo '</ol></div>';

		echo '</div><div class="rsl-detail__side">';

		// Update form.
		echo '<form class="rsl-card" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<h2>' . esc_html__( 'Update', 'ramsetu-leads' ) . '</h2>';
		wp_nonce_field( 'rsl_update_' . $object . '_' . $id );
		printf( '<input type="hidden" name="action" value="rsl_update"><input type="hidden" name="object" value="%s"><input type="hidden" name="id" value="%d">', esc_attr( $object ), (int) $id );

		echo '<p><label for="rsl-status"><strong>' . esc_html__( 'Status', 'ramsetu-leads' ) . '</strong></label><select id="rsl-status" name="status" class="widefat">';
		foreach ( $statuses as $k => $v ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $row['status'], $k, false ), esc_html( $v ) );
		}
		echo '</select></p>';

		if ( $manage ) {
			echo '<p><label for="rsl-assign"><strong>' . esc_html( 'lead' === $object ? __( 'Assign to Sales Officer', 'ramsetu-leads' ) : __( 'Handled by', 'ramsetu-leads' ) ) . '</strong></label><select id="rsl-assign" name="assigned_to" class="widefat"><option value="">' . esc_html__( '— Not assigned —', 'ramsetu-leads' ) . '</option>';
			foreach ( RSL_Roles::assignable_users() as $u ) {
				printf( '<option value="%d"%s>%s</option>', (int) $u->ID, selected( (int) $row['assigned_to'], (int) $u->ID, false ), esc_html( $u->display_name ) );
			}
			echo '</select>';
			if ( ! get_users( array( 'role' => 'rs_sales_officer', 'number' => 1, 'fields' => 'ID' ) ) ) {
				printf( '<span class="description">%s <a href="%s">%s</a></span>', esc_html__( 'No Sales Officers yet.', 'ramsetu-leads' ), esc_url( admin_url( 'user-new.php' ) ), esc_html__( 'Add a user with the "RamSetu Sales Officer" role.', 'ramsetu-leads' ) );
			}
			echo '</p>';
		}

		if ( 'lead' === $object ) {
			$portal_lead = in_array( $row['source'], array( 'partner', 'employee' ), true );
			echo '<details class="rsl-tracking"' . ( $row['lender_assigned'] || $row['credit_status'] || $portal_lead ? ' open' : '' ) . '><summary>' . esc_html__( 'Loan tracking', 'ramsetu-leads' ) . '</summary>';
			printf( '<p><label for="rsl-lender_assigned">%s</label><input type="text" class="widefat" id="rsl-lender_assigned" name="lender_assigned" value="%s" placeholder="%s"></p>', esc_html__( 'Lender assigned', 'ramsetu-leads' ), esc_attr( $row['lender_assigned'] ), esc_attr__( 'Bank / NBFC name', 'ramsetu-leads' ) );
			foreach ( array(
				'credit_status'       => array( __( 'Credit status', 'ramsetu-leads' ), rsl_credit_statuses() ),
				'document_status'     => array( __( 'Document verification', 'ramsetu-leads' ), rsl_document_statuses() ),
				'disbursement_status' => array( __( 'Loan disbursement', 'ramsetu-leads' ), rsl_disbursement_statuses() ),
			) as $k => $cfg ) {
				printf( '<p><label for="rsl-%1$s">%2$s</label><select class="widefat" id="rsl-%1$s" name="%1$s">', esc_attr( $k ), esc_html( $cfg[0] ) );
				$opts = $cfg[1];
				if ( $row[ $k ] && ! isset( $opts[ $row[ $k ] ] ) ) {
					$opts[ $row[ $k ] ] = $row[ $k ]; // keep older free-text values
				}
				foreach ( $opts as $v => $l ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( $row[ $k ], $v, false ), esc_html( $l ) );
				}
				echo '</select></p>';
			}
			if ( $manage && 'partner' === $row['source'] ) {
				echo '<p><strong>' . esc_html__( 'Partner incentive', 'ramsetu-leads' ) . '</strong></p>';
				printf( '<p><label for="rsl-incentive_amount">%s</label><input type="text" inputmode="numeric" class="widefat" id="rsl-incentive_amount" name="incentive_amount" value="%s" placeholder="e.g. 5000"></p>', esc_html__( 'Amount (₹)', 'ramsetu-leads' ), esc_attr( null !== $row['incentive_amount'] ? (string) (float) $row['incentive_amount'] : '' ) );
				printf( '<p><label for="rsl-incentive_status">%s</label><select class="widefat" id="rsl-incentive_status" name="incentive_status">', esc_html__( 'Incentive status', 'ramsetu-leads' ) );
				foreach ( rsl_incentive_statuses() as $v => $l ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( $v ), selected( $row['incentive_status'], $v, false ), esc_html( $l ) );
				}
				echo '</select></p><p class="description">' . esc_html__( 'The partner sees the amount and status in their portal.', 'ramsetu-leads' ) . '</p>';
			}
			echo '</details>';
		}

		echo '<p><label for="rsl-note"><strong>' . esc_html__( 'Add a remark', 'ramsetu-leads' ) . '</strong></label><textarea id="rsl-note" name="note" rows="3" class="widefat" placeholder="' . esc_attr__( 'e.g. Called, customer will share documents on Monday', 'ramsetu-leads' ) . '"></textarea></p>';
		submit_button( __( 'Save', 'ramsetu-leads' ), 'primary', 'submit', false );
		echo '</form>';

		if ( $manage && 'lead' !== $object ) {
			if ( ! empty( $row['lead_id'] ) ) {
				printf( '<div class="rsl-card"><p>%s <a href="%s">#%d</a></p></div>', esc_html__( 'Already converted to lead', 'ramsetu-leads' ), esc_url( admin_url( 'admin.php?page=rsl-leads&view=' . (int) $row['lead_id'] ) ), (int) $row['lead_id'] );
			} else {
				echo '<form class="rsl-card" method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
				wp_nonce_field( 'rsl_convert_' . $object . '_' . $id );
				printf( '<input type="hidden" name="action" value="rsl_convert"><input type="hidden" name="object" value="%s"><input type="hidden" name="id" value="%d">', esc_attr( $object ), (int) $id );
				echo '<p>' . esc_html__( 'Customer is interested? Turn this into a lead so it can be assigned and tracked.', 'ramsetu-leads' ) . '</p>';
				submit_button( __( 'Convert to lead', 'ramsetu-leads' ), 'secondary', 'submit', false );
				echo '</form>';
			}
		}

		if ( $manage ) {
			$del = wp_nonce_url( admin_url( 'admin.php?page=' . $page . '&rsl_delete=' . (int) $id ), 'rsl_delete_' . $object . '_' . $id );
			printf( '<p><a class="rsl-delete" href="%s" data-confirm="%s">%s</a></p>', esc_url( $del ), esc_attr__( 'Delete this record permanently? This cannot be undone.', 'ramsetu-leads' ), esc_html__( 'Delete permanently', 'ramsetu-leads' ) );
		}

		echo '</div></div>';
	}

	/** " · by Partner Name (RSCP00001)" for portal leads. */
	private static function submitted_by( $r ) {
		if ( ! empty( $r['partner_id'] ) ) {
			$p = RSL_DB::get( 'partner', (int) $r['partner_id'] );
			if ( $p ) {
				return ' · ' . sprintf( '<a href="%s">%s (%s)</a>', esc_url( admin_url( 'admin.php?page=rsl-partners&view=' . (int) $p['id'] ) ), esc_html( $p['full_name'] ), esc_html( $p['partner_code'] ) );
			}
		}
		if ( 'employee' === $r['source'] && ! empty( $r['submitted_by'] ) ) {
			$u = get_userdata( (int) $r['submitted_by'] );
			if ( $u ) {
				return ' · ' . esc_html( $u->display_name . ' (' . RSL_Partners::user_code( $u->ID ) . ')' );
			}
		}
		return '';
	}

	/** Masked Aadhaar; managers can reveal the full number (each view is logged in the history). */
	private static function aadhaar_cell( $r ) {
		if ( empty( $r['aadhaar_last4'] ) ) {
			return '—';
		}
		$masked = esc_html( rsl_mask_aadhaar( $r['aadhaar_last4'] ) );
		if ( empty( $r['aadhaar_enc'] ) || ! RSL_Roles::can_manage() ) {
			return $masked;
		}
		// phpcs:ignore WordPress.Security.NonceVerification
		if ( isset( $_GET['show_aadhaar'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'rsl_aadhaar_' . (int) $r['id'] ) ) {
			$full = rsl_decrypt( $r['aadhaar_enc'] );
			if ( '' !== $full ) {
				RSL_DB::log( 'lead', (int) $r['id'], 'aadhaar_viewed', sprintf( 'Full Aadhaar viewed by %s', wp_get_current_user()->display_name ) );
				return '<strong>' . esc_html( trim( chunk_split( $full, 4, ' ' ) ) ) . '</strong>';
			}
			return $masked . ' <em>' . esc_html__( '(could not be decrypted)', 'ramsetu-leads' ) . '</em>';
		}
		$url = wp_nonce_url( add_query_arg( array( 'page' => 'rsl-leads', 'view' => (int) $r['id'], 'show_aadhaar' => 1 ), admin_url( 'admin.php' ) ), 'rsl_aadhaar_' . (int) $r['id'] );
		return $masked . ' · <a href="' . esc_url( $url ) . '">' . esc_html__( 'Show full number', 'ramsetu-leads' ) . '</a>';
	}

	private static function detail_rows( $object, $r ) {
		$tel = function ( $p ) {
			return $p ? sprintf( '<a href="tel:+91%1$s">%1$s</a> · <a href="https://wa.me/91%1$s" target="_blank" rel="noopener">WhatsApp</a>', esc_html( $p ) ) : '—';
		};
		$e   = function ( $v ) {
			return '' === (string) $v || null === $v ? '—' : esc_html( $v );
		};
		$out = array();
		switch ( $object ) {
			case 'lead':
				$out = array(
					__( 'Lead ID', 'ramsetu-leads' )       => '#' . (int) $r['id'],
					__( 'Received', 'ramsetu-leads' )      => esc_html( date_i18n( 'd M Y, g:i A', strtotime( $r['created_at'] ) ) ),
					__( 'Lead reference', 'ramsetu-leads' ) => esc_html( rsl_lead_code( $r['id'] ) ),
					__( 'Source', 'ramsetu-leads' )        => esc_html( rsl_label( rsl_sources(), $r['source'] ) ) . self::submitted_by( $r ),
					__( 'Full name', 'ramsetu-leads' )     => $e( $r['full_name'] ),
					__( 'Phone', 'ramsetu-leads' )         => $tel( $r['phone'] ),
					__( 'Email', 'ramsetu-leads' )         => $r['email'] ? '<a href="mailto:' . esc_attr( $r['email'] ) . '">' . esc_html( $r['email'] ) . '</a>' : '—',
					__( 'Address', 'ramsetu-leads' )       => $e( $r['address'] ),
					__( 'City', 'ramsetu-leads' )          => $e( $r['city'] ),
					__( 'State', 'ramsetu-leads' )         => $e( $r['state'] ),
					__( 'Pincode', 'ramsetu-leads' )       => $e( $r['pincode'] ),
					__( 'Profile', 'ramsetu-leads' )       => esc_html( rsl_label( rsl_professions(), $r['profession'] ) ),
					__( 'Monthly income', 'ramsetu-leads' ) => $r['monthly_income'] ? esc_html( rsl_format_inr( $r['monthly_income'] ) ) : '—',
					__( 'Business / industry', 'ramsetu-leads' ) => $e( $r['business_type'] ),
					__( 'Loan product', 'ramsetu-leads' )  => esc_html( rsl_label( rsl_product_labels(), $r['loan_category'] ) ),
					__( 'Loan amount', 'ramsetu-leads' )   => esc_html( rsl_format_inr( $r['loan_amount'] ) ),
					__( 'Collateral property', 'ramsetu-leads' ) => esc_html( rsl_label( rsl_collaterals(), $r['collateral'] ) ),
					__( 'Collateral value', 'ramsetu-leads' ) => $r['collateral_value'] ? esc_html( rsl_format_inr( $r['collateral_value'] ) ) : '—',
					__( 'PAN', 'ramsetu-leads' )           => $e( $r['pan'] ),
					__( 'Aadhaar', 'ramsetu-leads' )       => self::aadhaar_cell( $r ),
					__( 'Consent given', 'ramsetu-leads' ) => $r['consent'] ? esc_html__( 'Yes', 'ramsetu-leads' ) : esc_html__( 'No', 'ramsetu-leads' ),
				);
				if ( in_array( $r['source'], array( 'partner', 'employee' ), true ) ) {
					foreach ( array( 'Address', 'Pincode', 'Monthly income', 'PAN' ) as $drop ) {
						unset( $out[ __( $drop, 'ramsetu-leads' ) ] ); // phpcs:ignore WordPress.WP.I18n
					}
				}
				// Phase 2 fields: only shown when filled.
				foreach ( array(
					'district'               => __( 'District', 'ramsetu-leads' ),
					'co_applicant_name'      => __( 'Co-applicant', 'ramsetu-leads' ),
					'co_applicant_phone'     => __( 'Co-applicant phone', 'ramsetu-leads' ),
					'co_applicant_email'     => __( 'Co-applicant email', 'ramsetu-leads' ),
					'business_vintage_years' => __( 'Business vintage (years)', 'ramsetu-leads' ),
					'product'                => __( 'Product', 'ramsetu-leads' ),
					'property_type'          => __( 'Property type', 'ramsetu-leads' ),
					'property_value'         => __( 'Property value', 'ramsetu-leads' ),
					'property_location'      => __( 'Property location', 'ramsetu-leads' ),
					'existing_loans'         => __( 'Existing loans', 'ramsetu-leads' ),
				) as $k => $label ) {
					if ( ! empty( $r[ $k ] ) ) {
						$out[ $label ] = 'property_value' === $k ? esc_html( rsl_format_inr( $r[ $k ] ) ) : esc_html( $r[ $k ] );
					}
				}
				break;
			case 'referral':
				$out = array(
					__( 'Received', 'ramsetu-leads' )        => esc_html( date_i18n( 'd M Y, g:i A', strtotime( $r['created_at'] ) ) ),
					__( 'Referred by', 'ramsetu-leads' )     => $e( $r['referrer_name'] ),
					__( 'Referrer phone', 'ramsetu-leads' )  => $tel( $r['referrer_phone'] ),
					__( 'Applicant name', 'ramsetu-leads' )  => $e( $r['referred_name'] ),
					__( 'Applicant phone', 'ramsetu-leads' ) => $tel( $r['referred_phone'] ),
					__( 'Referring for', 'ramsetu-leads' )   => esc_html( rsl_label( rsl_referral_labels(), $r['loan_type'] ) ),
				);
				break;
			default:
				$out = array(
					__( 'Received', 'ramsetu-leads' )       => esc_html( date_i18n( 'd M Y, g:i A', strtotime( $r['created_at'] ) ) ),
					__( 'Name', 'ramsetu-leads' )           => $e( $r['full_name'] ),
					__( 'Phone', 'ramsetu-leads' )          => $tel( $r['phone'] ),
					__( 'City', 'ramsetu-leads' )           => $e( $r['city'] ),
					__( 'State', 'ramsetu-leads' )          => $e( $r['state'] ),
					__( 'Service looking for', 'ramsetu-leads' ) => nl2br( $e( $r['support_needed'] ) ),
				);
		}
		if ( ! empty( $r['page_url'] ) ) {
			$out[ __( 'Submitted from', 'ramsetu-leads' ) ] = '<a href="' . esc_url( $r['page_url'] ) . '" target="_blank">' . esc_html( wp_parse_url( $r['page_url'], PHP_URL_PATH ) ?: '/' ) . '</a>';
		}
		return $out;
	}

	private static function action_label( $a ) {
		$map = array(
			'created'   => __( 'Received', 'ramsetu-leads' ),
			'status'    => __( 'Changed status', 'ramsetu-leads' ),
			'assigned'  => __( 'Assigned', 'ramsetu-leads' ),
			'note'      => __( 'Added a remark', 'ramsetu-leads' ),
			'tracking'  => __( 'Updated loan tracking', 'ramsetu-leads' ),
			'incentive' => __( 'Updated partner incentive', 'ramsetu-leads' ),
			'converted' => __( 'Converted to lead', 'ramsetu-leads' ),
			'aadhaar_viewed' => __( 'Viewed full Aadhaar', 'ramsetu-leads' ),
		);
		return $map[ $a ] ?? $a;
	}

	private static function render_add_lead() {
		printf( '<p><a href="%s">&larr; %s</a></p>', esc_url( admin_url( 'admin.php?page=rsl-leads' ) ), esc_html__( 'Back to list', 'ramsetu-leads' ) );
		echo '<h1>' . esc_html__( 'Add lead manually', 'ramsetu-leads' ) . '</h1>';
		self::notice();
		echo '<p class="description">' . esc_html__( 'For walk-ins and phone enquiries, so every lead is tracked in one place.', 'ramsetu-leads' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="rsl-card rsl-addform">';
		wp_nonce_field( 'rsl_add_lead' );
		echo '<input type="hidden" name="action" value="rsl_add_lead"><table class="form-table">';
		$text = function ( $n, $l, $req = false, $type = 'text' ) {
			printf( '<tr><th><label for="a-%1$s">%2$s%4$s</label></th><td><input class="regular-text" type="%3$s" id="a-%1$s" name="%1$s"%5$s></td></tr>', esc_attr( $n ), esc_html( $l ), esc_attr( $type ), $req ? ' *' : '', $req ? ' required' : '' );
		};
		$sel = function ( $n, $l, $opts ) {
			printf( '<tr><th><label for="a-%1$s">%2$s</label></th><td><select id="a-%1$s" name="%1$s"><option value="">—</option>', esc_attr( $n ), esc_html( $l ) );
			foreach ( $opts as $k => $v ) {
				printf( '<option value="%s">%s</option>', esc_attr( $k ), esc_html( $v ) );
			}
			echo '</select></td></tr>';
		};
		$text( 'full_name', __( 'Full name', 'ramsetu-leads' ), true );
		$text( 'phone', __( 'Phone', 'ramsetu-leads' ), true, 'tel' );
		$text( 'email', __( 'Email', 'ramsetu-leads' ), false, 'email' );
		$text( 'address', __( 'Address', 'ramsetu-leads' ) );
		$text( 'city', __( 'City', 'ramsetu-leads' ) );
		$sel( 'state', __( 'State', 'ramsetu-leads' ), array_combine( rsl_states(), rsl_states() ) );
		$text( 'pincode', __( 'Pincode', 'ramsetu-leads' ) );
		$sel( 'loan_category', __( 'Loan product', 'ramsetu-leads' ), rsl_product_labels() );
		$text( 'loan_amount', __( 'Loan amount (₹)', 'ramsetu-leads' ) );
		$sel( 'profession', __( 'Profile', 'ramsetu-leads' ), rsl_professions() );
		$text( 'business_type', __( 'Business / industry', 'ramsetu-leads' ) );
		$sel( 'collateral', __( 'Collateral property', 'ramsetu-leads' ), rsl_collaterals() );
		$text( 'collateral_value', __( 'Collateral value (₹)', 'ramsetu-leads' ) );
		echo '</table>';
		submit_button( __( 'Add lead', 'ramsetu-leads' ) );
		echo '</form>';
	}

	public static function page_overview() {
		global $wpdb;
		$t     = RSL_DB::table( 'lead' );
		$today = current_time( 'Y-m-d' );
		$week  = gmdate( 'Y-m-d', strtotime( $today . ' -6 days' ) );
		$stats = array(
			__( 'Leads today', 'ramsetu-leads' )          => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE created_at >= %s", $today . ' 00:00:00' ) ),
			__( 'Leads, last 7 days', 'ramsetu-leads' )   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $t WHERE created_at >= %s", $week . ' 00:00:00' ) ),
			__( 'Waiting to be assigned', 'ramsetu-leads' ) => (int) $wpdb->get_var( "SELECT COUNT(*) FROM $t WHERE assigned_to IS NULL AND status NOT IN ('closed','not_eligible','converted')" ),
			__( 'New referrals', 'ramsetu-leads' )        => RSL_DB::count_new( 'referral' ),
			__( 'New call-back requests', 'ramsetu-leads' ) => RSL_DB::count_new( 'callback' ),
		);
		$links = array(
			__( 'Leads today', 'ramsetu-leads' )            => admin_url( 'admin.php?page=rsl-leads&date_from=' . $today ),
			__( 'Leads, last 7 days', 'ramsetu-leads' )     => admin_url( 'admin.php?page=rsl-leads&date_from=' . $week ),
			__( 'Waiting to be assigned', 'ramsetu-leads' ) => admin_url( 'admin.php?page=rsl-leads&assigned_to=none' ),
			__( 'New referrals', 'ramsetu-leads' )          => admin_url( 'admin.php?page=rsl-referrals&status=new' ),
			__( 'New call-back requests', 'ramsetu-leads' ) => admin_url( 'admin.php?page=rsl-callbacks&status=new' ),
		);
		echo '<div class="wrap rsl-wrap"><h1>' . esc_html__( 'Overview', 'ramsetu-leads' ) . '</h1><div class="rsl-stats">';
		foreach ( $stats as $label => $n ) {
			printf( '<a class="rsl-stat" href="%s"><span class="rsl-stat__n">%d</span><span class="rsl-stat__l">%s</span></a>', esc_url( $links[ $label ] ), (int) $n, esc_html( $label ) );
		}
		echo '</div>';

		$by_cat = $wpdb->get_results( "SELECT loan_category k, COUNT(*) n, SUM(loan_amount) amt FROM $t GROUP BY loan_category", ARRAY_A );
		$by_so  = $wpdb->get_results( "SELECT assigned_to k, COUNT(*) n, SUM(status='converted') won FROM $t WHERE assigned_to IS NOT NULL GROUP BY assigned_to", ARRAY_A );

		echo '<div class="rsl-two"><div class="rsl-card"><h2>' . esc_html__( 'All leads by service', 'ramsetu-leads' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Service', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Leads', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Total amount asked', 'ramsetu-leads' ) . '</th></tr></thead><tbody>';
		foreach ( $by_cat as $r ) {
			printf( '<tr><td>%s</td><td>%d</td><td>%s</td></tr>', esc_html( rsl_label( rsl_product_labels(), $r['k'] ) ), (int) $r['n'], esc_html( rsl_format_inr( $r['amt'] ) ) );
		}
		if ( ! $by_cat ) {
			echo '<tr><td colspan="3">' . esc_html__( 'No leads yet.', 'ramsetu-leads' ) . '</td></tr>';
		}
		echo '</tbody></table></div>';

		echo '<div class="rsl-card"><h2>' . esc_html__( 'Sales Officers', 'ramsetu-leads' ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html__( 'Officer', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Assigned', 'ramsetu-leads' ) . '</th><th>' . esc_html__( 'Converted', 'ramsetu-leads' ) . '</th></tr></thead><tbody>';
		foreach ( $by_so as $r ) {
			printf( '<tr><td><a href="%s">%s</a></td><td>%d</td><td>%d</td></tr>', esc_url( admin_url( 'admin.php?page=rsl-leads&assigned_to=' . (int) $r['k'] ) ), esc_html( get_the_author_meta( 'display_name', $r['k'] ) ), (int) $r['n'], (int) $r['won'] );
		}
		if ( ! $by_so ) {
			echo '<tr><td colspan="3">' . esc_html__( 'No leads assigned yet.', 'ramsetu-leads' ) . '</td></tr>';
		}
		echo '</tbody></table></div></div></div>';
	}

	public static function page_branches() {
		$branches = rsl_get_branches();
		echo '<div class="wrap rsl-wrap"><h1>' . esc_html__( 'Branches', 'ramsetu-leads' ) . '</h1>';
		self::notice();
		echo '<p class="description">' . esc_html__( 'These power the "Locate Branch Near You" page (State → City) and the call-back form. Add a row for each branch.', 'ramsetu-leads' ) . '</p>';
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'rsl_branches' );
		echo '<input type="hidden" name="action" value="rsl_save_branches"><div id="rsl-branches">';
		$branches[] = array(); // blank row template
		foreach ( $branches as $i => $b ) {
			$tpl = $i === count( $branches ) - 1;
			printf( '<fieldset class="rsl-card rsl-branch"%s>', $tpl ? ' data-template hidden disabled' : '' );
			echo '<div class="rsl-branch__grid">';
			foreach ( array(
				'state'   => __( 'State', 'ramsetu-leads' ),
				'city'    => __( 'City', 'ramsetu-leads' ),
				'name'    => __( 'Branch name', 'ramsetu-leads' ),
				'contact' => __( 'Contact person', 'ramsetu-leads' ),
				'phone'   => __( 'Phone', 'ramsetu-leads' ),
				'email'   => __( 'Email', 'ramsetu-leads' ),
				'map'     => __( 'Google Maps link', 'ramsetu-leads' ),
			) as $k => $label ) {
				$idx = $tpl ? '__i__' : $i;
				if ( 'state' === $k ) {
					printf( '<label>%s<select name="branches[%s][state]"><option value="">—</option>', esc_html( $label ), esc_attr( $idx ) );
					foreach ( rsl_states() as $s ) {
						printf( '<option%s>%s</option>', selected( $b['state'] ?? '', $s, false ), esc_html( $s ) );
					}
					echo '</select></label>';
				} else {
					printf( '<label>%s<input type="text" name="branches[%s][%s]" value="%s"></label>', esc_html( $label ), esc_attr( $idx ), esc_attr( $k ), esc_attr( $b[ $k ] ?? '' ) );
				}
			}
			printf( '<label class="rsl-branch__wide">%s<textarea rows="2" name="branches[%s][address]">%s</textarea></label>', esc_html__( 'Address', 'ramsetu-leads' ), esc_attr( $tpl ? '__i__' : $i ), esc_textarea( $b['address'] ?? '' ) );
			echo '</div><button type="button" class="button-link rsl-branch__remove">' . esc_html__( 'Remove branch', 'ramsetu-leads' ) . '</button></fieldset>';
		}
		echo '</div><p><button type="button" class="button" id="rsl-add-branch">+ ' . esc_html__( 'Add branch', 'ramsetu-leads' ) . '</button></p>';
		submit_button( __( 'Save branches', 'ramsetu-leads' ) );
		echo '</form></div>';
	}

	public static function page_settings() {
		$s = rsl_settings();
		echo '<div class="wrap rsl-wrap"><h1>' . esc_html__( 'Settings', 'ramsetu-leads' ) . '</h1>';
		self::notice();
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		wp_nonce_field( 'rsl_settings' );
		echo '<input type="hidden" name="action" value="rsl_save_settings"><table class="form-table">';
		printf( '<tr><th><label for="rsl-emails">%s</label></th><td><input class="large-text" id="rsl-emails" name="notify_emails" value="%s"><p class="description">%s</p></td></tr>', esc_html__( 'Send new submissions to', 'ramsetu-leads' ), esc_attr( $s['notify_emails'] ), esc_html__( 'One or more email addresses, separated by commas.', 'ramsetu-leads' ) );
		echo '<tr><th>' . esc_html__( 'Email me about', 'ramsetu-leads' ) . '</th><td><fieldset>';
		foreach ( array( 'notify_leads' => __( 'New Apply Now leads', 'ramsetu-leads' ), 'notify_referrals' => __( 'New referrals', 'ramsetu-leads' ), 'notify_callbacks' => __( 'New call-back requests', 'ramsetu-leads' ) ) as $k => $l ) {
			printf( '<label><input type="checkbox" name="%s" value="1"%s> %s</label><br>', esc_attr( $k ), checked( $s[ $k ], 1, false ), esc_html( $l ) );
		}
		echo '</fieldset></td></tr>';
		printf( '<tr><th><label for="rsl-hours">%s</label></th><td><input class="regular-text" id="rsl-hours" name="walkin_hours" value="%s"><p class="description">%s</p></td></tr>', esc_html__( 'Branch walk-in hours', 'ramsetu-leads' ), esc_attr( $s['walkin_hours'] ), esc_html__( 'Shown on the branch locator.', 'ramsetu-leads' ) );
		echo '</table>';
		submit_button();
		echo '</form>';

		echo '<div class="rsl-card"><h2>' . esc_html__( 'Team access', 'ramsetu-leads' ) . '</h2><ul class="ul-disc">';
		echo '<li><strong>RamSetu Lead Manager</strong> — ' . esc_html__( 'sees all leads, referrals and call-backs; assigns leads; manages branches.', 'ramsetu-leads' ) . '</li>';
		echo '<li><strong>RamSetu Sales Officer</strong> — ' . esc_html__( 'sees only leads assigned to them; can update status and add remarks. Gets an email when a lead is assigned.', 'ramsetu-leads' ) . '</li>';
		echo '<li><strong>Channel Partner / Employee (Phase 2)</strong> — ' . esc_html__( 'roles exist so accounts can be prepared; the lead-submission portals come in Phase 2.', 'ramsetu-leads' ) . '</li>';
		printf( '</ul><p><a class="button" href="%s">%s</a></p></div>', esc_url( admin_url( 'user-new.php' ) ), esc_html__( 'Add a team member', 'ramsetu-leads' ) );
		echo '</div>';
	}

	/* ------------------------------------------------------------------ */
	/* Actions                                                             */
	/* ------------------------------------------------------------------ */

	private static function object_from_request() {
		$o = isset( $_REQUEST['object'] ) ? sanitize_key( $_REQUEST['object'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		if ( ! isset( self::PAGES[ $o ] ) ) {
			wp_die( esc_html__( 'Invalid request.', 'ramsetu-leads' ) );
		}
		return $o;
	}

	private static function go( $object, $args ) {
		wp_safe_redirect( add_query_arg( $args, admin_url( 'admin.php?page=' . self::PAGES[ $object ] ) ) );
		exit;
	}

	public static function handle_update() {
		$object = self::object_from_request();
		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		check_admin_referer( 'rsl_update_' . $object . '_' . $id );
		$row = RSL_DB::get( $object, $id );
		if ( ! $row || ! RSL_Roles::can_view_row( $row ) ) {
			wp_die( esc_html__( 'You cannot edit this record.', 'ramsetu-leads' ), 403 );
		}

		$data     = array();
		$statuses = 'lead' === $object ? rsl_lead_statuses() : rsl_simple_statuses();
		$status   = sanitize_key( $_POST['status'] ?? $row['status'] );
		if ( isset( $statuses[ $status ] ) && $status !== $row['status'] ) {
			$data['status'] = $status;
			RSL_DB::log( $object, $id, 'status', rsl_label( $statuses, $row['status'] ) . ' → ' . $statuses[ $status ] );
		}

		if ( RSL_Roles::can_manage() && isset( $_POST['assigned_to'] ) ) {
			$to = absint( $_POST['assigned_to'] );
			if ( $to !== (int) $row['assigned_to'] ) {
				$data['assigned_to'] = $to ? $to : null;
				if ( 'lead' === $object ) {
					$data['assigned_at'] = $to ? current_time( 'mysql' ) : null;
					if ( $to && 'new' === ( $data['status'] ?? $row['status'] ) ) {
						$data['status'] = 'assigned';
					}
				}
				RSL_DB::log( $object, $id, 'assigned', $to ? get_the_author_meta( 'display_name', $to ) : __( 'Unassigned', 'ramsetu-leads' ) );
				if ( $to ) {
					do_action( 'rsl_assigned', $object, $id, $to );
				}
			}
		}

		if ( 'lead' === $object ) {
			$changes = array();
			$allowed = array(
				'credit_status'       => rsl_credit_statuses(),
				'document_status'     => rsl_document_statuses(),
				'disbursement_status' => rsl_disbursement_statuses(),
			);
			if ( RSL_Roles::can_manage() && 'partner' === $row['source'] && isset( $_POST['incentive_status'] ) ) {
				$amt = preg_replace( '/[^\d.]/', '', (string) wp_unslash( $_POST['incentive_amount'] ?? '' ) );
				$amt = '' === $amt ? null : (float) $amt;
				$ist = sanitize_key( $_POST['incentive_status'] );
				$ist = isset( rsl_incentive_statuses()[ $ist ] ) ? $ist : '';
				if ( $amt !== ( null === $row['incentive_amount'] ? null : (float) $row['incentive_amount'] ) || $ist !== (string) $row['incentive_status'] ) {
					$data['incentive_amount'] = $amt;
					$data['incentive_status'] = $ist;
					RSL_DB::log( $object, $id, 'incentive', ( null === $amt ? '—' : rsl_format_inr( $amt ) ) . ' · ' . rsl_label( rsl_incentive_statuses(), $ist ) );
				}
			}
			foreach ( array( 'lender_assigned', 'credit_status', 'document_status', 'disbursement_status' ) as $k ) {
				$v = sanitize_text_field( wp_unslash( $_POST[ $k ] ?? '' ) );
				if ( isset( $allowed[ $k ] ) && ! isset( $allowed[ $k ][ $v ] ) && $v !== (string) $row[ $k ] ) {
					continue;
				}
				if ( $v !== (string) $row[ $k ] ) {
					$data[ $k ] = $v;
					$changes[]  = str_replace( '_', ' ', $k ) . ': ' . ( $v ? $v : '—' );
				}
			}
			if ( $changes ) {
				RSL_DB::log( $object, $id, 'tracking', implode( "\n", $changes ) );
			}
		}

		$note = sanitize_textarea_field( wp_unslash( $_POST['note'] ?? '' ) );
		if ( '' !== $note ) {
			RSL_DB::log( $object, $id, 'note', $note );
			$data['remarks'] = trim( ( $row['remarks'] ? $row['remarks'] . "\n" : '' ) . '[' . current_time( 'd M' ) . '] ' . $note );
		}

		if ( $data ) {
			RSL_DB::update( $object, $id, $data );
		}
		self::go( $object, array( 'view' => $id, 'rsl_msg' => 'saved' ) );
	}

	public static function handle_convert() {
		$object = self::object_from_request();
		$id     = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
		check_admin_referer( 'rsl_convert_' . $object . '_' . $id );
		if ( ! RSL_Roles::can_manage() || 'lead' === $object ) {
			wp_die( esc_html__( 'Not allowed.', 'ramsetu-leads' ), 403 );
		}
		$r = RSL_DB::get( $object, $id );
		if ( ! $r || $r['lead_id'] ) {
			self::go( $object, array( 'view' => $id ) );
		}
		if ( 'referral' === $object ) {
			$cat  = isset( rsl_loan_categories()[ $r['loan_type'] ] ) ? $r['loan_type'] : '';
			$lead = array(
				'source'        => 'referral',
				'full_name'     => $r['referred_name'],
				'phone'         => $r['referred_phone'],
				'profession'    => $r['customer_type'],
				'loan_category' => $cat,
				'remarks'       => sprintf( 'Referred by %s (%s), referral #%d', $r['referrer_name'], $r['referrer_phone'], $id ),
			);
		} else {
			$lead = array(
				'source'    => 'callback',
				'full_name' => $r['full_name'],
				'phone'     => $r['phone'],
				'city'      => $r['city'],
				'state'     => $r['state'],
				'remarks'   => 'From call-back request #' . $id . ': ' . $r['support_needed'],
			);
		}
		$lead_id = RSL_DB::insert( 'lead', $lead );
		RSL_DB::update( $object, $id, array( 'lead_id' => $lead_id, 'status' => 'converted' ) );
		RSL_DB::log( $object, $id, 'converted', 'Lead #' . $lead_id );
		wp_safe_redirect( admin_url( 'admin.php?page=rsl-leads&view=' . $lead_id . '&rsl_msg=converted' ) );
		exit;
	}

	public static function handle_add_lead() {
		check_admin_referer( 'rsl_add_lead' );
		if ( ! RSL_Roles::can_manage() ) {
			wp_die( esc_html__( 'Not allowed.', 'ramsetu-leads' ), 403 );
		}
		$p     = wp_unslash( $_POST );
		$phone = rsl_normalize_phone( $p['phone'] ?? '' );
		$name  = sanitize_text_field( $p['full_name'] ?? '' );
		if ( ! $phone || ! $name ) {
			wp_safe_redirect( admin_url( 'admin.php?page=rsl-leads&add=1&rsl_msg=invalid' ) );
			exit;
		}
		$amount = preg_replace( '/[^\d.]/', '', (string) ( $p['loan_amount'] ?? '' ) );
		$id     = RSL_DB::insert( 'lead', array(
			'source'        => 'manual',
			'submitted_by'  => get_current_user_id(),
			'full_name'     => $name,
			'phone'         => $phone,
			'email'         => sanitize_email( $p['email'] ?? '' ),
			'city'          => sanitize_text_field( $p['city'] ?? '' ),
			'state'         => in_array( $p['state'] ?? '', rsl_states(), true ) ? $p['state'] : '',
			'loan_category' => isset( rsl_product_labels()[ $p['loan_category'] ?? '' ] ) ? $p['loan_category'] : '',
			'loan_amount'   => '' === $amount ? null : (float) $amount,
			'profession'    => isset( rsl_professions()[ $p['profession'] ?? '' ] ) ? $p['profession'] : '',
			'business_type' => sanitize_text_field( $p['business_type'] ?? '' ),
			'collateral'    => isset( rsl_collaterals()[ $p['collateral'] ?? '' ] ) ? $p['collateral'] : '',
			'address'       => sanitize_text_field( $p['address'] ?? '' ),
			'pincode'       => preg_match( '/^[1-9]\d{5}$/', (string) ( $p['pincode'] ?? '' ) ) ? $p['pincode'] : '',
			'collateral_value' => '' === preg_replace( '/[^\d.]/', '', (string) ( $p['collateral_value'] ?? '' ) ) ? null : (float) preg_replace( '/[^\d.]/', '', (string) $p['collateral_value'] ),
		) );
		wp_safe_redirect( admin_url( 'admin.php?page=rsl-leads&view=' . $id . '&rsl_msg=added' ) );
		exit;
	}

	/** Bulk actions + single delete, handled on admin_init so we can redirect/stream. */
	public static function handle_bulk() {
		// phpcs:disable WordPress.Security.NonceVerification
		$page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
		$obj  = array_search( $page, self::PAGES, true );
		if ( ! $obj || ! RSL_Roles::can_manage() ) {
			return;
		}

		if ( isset( $_GET['rsl_delete'] ) ) {
			$id = absint( $_GET['rsl_delete'] );
			check_admin_referer( 'rsl_delete_' . $obj . '_' . $id );
			RSL_DB::delete( $obj, $id );
			self::go( $obj, array( 'rsl_msg' => 'deleted' ) );
		}

		$action = isset( $_GET['action'] ) && '-1' !== $_GET['action'] ? sanitize_key( $_GET['action'] ) : ( isset( $_GET['action2'] ) ? sanitize_key( $_GET['action2'] ) : '' );
		$ids    = isset( $_GET['ids'] ) ? array_map( 'absint', (array) $_GET['ids'] ) : array();
		// phpcs:enable
		if ( ! $ids || ! in_array( $action, array( 'delete', 'export' ), true ) ) {
			return;
		}
		check_admin_referer( 'rsl_bulk', 'rsl_bulk_nonce' );
		if ( 'delete' === $action ) {
			foreach ( $ids as $id ) {
				RSL_DB::delete( $obj, $id );
			}
			self::go( $obj, array( 'rsl_msg' => 'deleted' ) );
		}
		$rows = array();
		foreach ( $ids as $id ) {
			$r = RSL_DB::get( $obj, $id );
			if ( $r ) {
				$rows[] = $r;
			}
		}
		self::stream_csv( $obj, $rows );
	}

	public static function handle_export() {
		check_admin_referer( 'rsl_export' );
		if ( ! RSL_Roles::can_manage() ) {
			wp_die( esc_html__( 'Not allowed.', 'ramsetu-leads' ), 403 );
		}
		$obj = self::object_from_request();
		$f   = array();
		foreach ( array( 'status', 'loan_category', 'source', 'assigned_to', 'search', 'date_from', 'date_to' ) as $k ) {
			if ( isset( $_GET[ $k ] ) ) {
				$f[ $k ] = sanitize_text_field( wp_unslash( $_GET[ $k ] ) );
			}
		}
		$res = RSL_DB::query( $obj, $f + array( 'no_limit' => true ) );
		self::stream_csv( $obj, $res['rows'] );
	}

	private static function stream_csv( $object, array $rows ) {
		$labels = array(
			'loan_category' => rsl_product_labels(),
			'profession'    => rsl_professions(),
			'customer_type' => rsl_professions(),
			'collateral'    => rsl_collaterals(),
			'loan_type'     => rsl_referral_labels(),
			'source'        => rsl_sources(),
			'status'        => 'lead' === $object ? rsl_lead_statuses() : rsl_simple_statuses(),
		);
		$skip = array( 'ip_address', 'user_agent', 'partner_id', 'submitted_by', 'aadhaar_enc' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=ramsetu-' . $object . 's-' . current_time( 'Y-m-d' ) . '.csv' );
		$out = fopen( 'php://output', 'w' );
		fwrite( $out, "\xEF\xBB\xBF" ); // Excel-friendly UTF-8
		if ( $rows ) {
			$cols = array_values( array_diff( array_keys( $rows[0] ), $skip ) );
			fputcsv( $out, array_map( function ( $c ) {
				return 'assigned_to' === $c ? 'Assigned to' : ucwords( str_replace( '_', ' ', $c ) );
			}, $cols ) );
			foreach ( $rows as $r ) {
				$line = array();
				foreach ( $cols as $c ) {
					$v = $r[ $c ];
					if ( isset( $labels[ $c ] ) ) {
						$v = '' === (string) $v ? '' : rsl_label( $labels[ $c ], $v );
					} elseif ( 'assigned_to' === $c ) {
						$v = $v ? get_the_author_meta( 'display_name', $v ) : '';
					}
					// Guard against spreadsheet formula injection.
					if ( is_string( $v ) && '' !== $v && in_array( $v[0], array( '=', '+', '-', '@' ), true ) ) {
						$v = "'" . $v;
					}
					$line[] = $v;
				}
				fputcsv( $out, $line );
			}
		}
		fclose( $out );
		exit;
	}

	public static function handle_branches() {
		check_admin_referer( 'rsl_branches' );
		if ( ! RSL_Roles::can_manage() ) {
			wp_die( esc_html__( 'Not allowed.', 'ramsetu-leads' ), 403 );
		}
		$in  = isset( $_POST['branches'] ) ? (array) wp_unslash( $_POST['branches'] ) : array();
		$out = array();
		foreach ( $in as $k => $b ) {
			if ( '__i__' === $k || empty( $b['state'] ) || empty( $b['city'] ) ) {
				continue;
			}
			$out[] = array(
				'state'   => in_array( $b['state'], rsl_states(), true ) ? $b['state'] : '',
				'city'    => sanitize_text_field( $b['city'] ),
				'name'    => sanitize_text_field( $b['name'] ?? '' ),
				'address' => sanitize_textarea_field( $b['address'] ?? '' ),
				'contact' => sanitize_text_field( $b['contact'] ?? '' ),
				'phone'   => sanitize_text_field( $b['phone'] ?? '' ),
				'email'   => sanitize_email( $b['email'] ?? '' ),
				'map'     => esc_url_raw( $b['map'] ?? '' ),
			);
		}
		update_option( 'rsl_branches', $out );
		wp_safe_redirect( admin_url( 'admin.php?page=rsl-branches&rsl_msg=saved' ) );
		exit;
	}

	public static function handle_settings() {
		check_admin_referer( 'rsl_settings' );
		if ( ! RSL_Roles::can_manage() ) {
			wp_die( esc_html__( 'Not allowed.', 'ramsetu-leads' ), 403 );
		}
		$emails = array_filter( array_map( 'sanitize_email', array_map( 'trim', explode( ',', (string) wp_unslash( $_POST['notify_emails'] ?? '' ) ) ) ), 'is_email' );
		update_option( 'rsl_settings', array(
			'notify_emails'    => implode( ', ', $emails ),
			'notify_leads'     => empty( $_POST['notify_leads'] ) ? 0 : 1,
			'notify_referrals' => empty( $_POST['notify_referrals'] ) ? 0 : 1,
			'notify_callbacks' => empty( $_POST['notify_callbacks'] ) ? 0 : 1,
			'walkin_hours'     => sanitize_text_field( wp_unslash( $_POST['walkin_hours'] ?? '' ) ),
		) );
		wp_safe_redirect( admin_url( 'admin.php?page=rsl-settings&rsl_msg=saved' ) );
		exit;
	}
}
