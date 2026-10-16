<?php
/**
 * Generic list table for leads, referrals and call-backs.
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class RSL_List_Table extends WP_List_Table {

	private $object;
	private $page_slug;
	private $users = array();

	public function __construct( $object, $page_slug ) {
		$this->object    = $object;
		$this->page_slug = $page_slug;
		parent::__construct( array( 'singular' => $object, 'plural' => $object . 's', 'ajax' => false ) );
		foreach ( RSL_Roles::assignable_users() as $u ) {
			$this->users[ $u->ID ] = $u->display_name;
		}
	}

	public function get_columns() {
		$cols = array( 'cb' => '<input type="checkbox">' );
		switch ( $this->object ) {
			case 'lead':
				$cols += array(
					'full_name'     => __( 'Applicant', 'ramsetu-leads' ),
					'loan_category' => __( 'Service', 'ramsetu-leads' ),
					'loan_amount'   => __( 'Amount', 'ramsetu-leads' ),
					'location'      => __( 'Location', 'ramsetu-leads' ),
					'profession'    => __( 'Profile', 'ramsetu-leads' ),
					'source'        => __( 'Source', 'ramsetu-leads' ),
					'assigned_to'   => __( 'Sales Officer', 'ramsetu-leads' ),
					'status'        => __( 'Status', 'ramsetu-leads' ),
					'created_at'    => __( 'Received', 'ramsetu-leads' ),
				);
				break;
			case 'referral':
				$cols += array(
					'referrer'      => __( 'Referred by', 'ramsetu-leads' ),
					'referred'      => __( 'Applicant', 'ramsetu-leads' ),
					'loan_type'     => __( 'Referring for', 'ramsetu-leads' ),
					'assigned_to'   => __( 'Handled by', 'ramsetu-leads' ),
					'status'        => __( 'Status', 'ramsetu-leads' ),
					'created_at'    => __( 'Received', 'ramsetu-leads' ),
				);
				break;
			default:
				$cols += array(
					'full_name'      => __( 'Name', 'ramsetu-leads' ),
					'location'       => __( 'Location', 'ramsetu-leads' ),
					'support_needed' => __( 'Support needed', 'ramsetu-leads' ),
					'assigned_to'    => __( 'Handled by', 'ramsetu-leads' ),
					'status'         => __( 'Status', 'ramsetu-leads' ),
					'created_at'     => __( 'Received', 'ramsetu-leads' ),
				);
		}
		return $cols;
	}

	protected function get_sortable_columns() {
		$s = array( 'created_at' => array( 'created_at', true ), 'status' => array( 'status', false ) );
		if ( 'lead' === $this->object ) {
			$s['loan_amount'] = array( 'loan_amount', false );
			$s['full_name']   = array( 'full_name', false );
		}
		return $s;
	}

	protected function get_bulk_actions() {
		if ( ! RSL_Roles::can_manage() ) {
			return array();
		}
		return array(
			'export' => __( 'Export to CSV', 'ramsetu-leads' ),
			'delete' => __( 'Delete permanently', 'ramsetu-leads' ),
		);
	}

	private function statuses() {
		return 'lead' === $this->object ? rsl_lead_statuses() : rsl_simple_statuses();
	}

	public function current_filters() {
		// phpcs:disable WordPress.Security.NonceVerification
		$f = array(
			'status'        => isset( $_GET['status'] ) ? sanitize_key( $_GET['status'] ) : '',
			'loan_category' => isset( $_GET['loan_category'] ) ? sanitize_key( $_GET['loan_category'] ) : '',
			'source'        => isset( $_GET['source'] ) ? sanitize_key( $_GET['source'] ) : '',
			'assigned_to'   => isset( $_GET['assigned_to'] ) ? sanitize_text_field( wp_unslash( $_GET['assigned_to'] ) ) : '',
			'partner_id'    => isset( $_GET['partner_id'] ) ? absint( $_GET['partner_id'] ) : '',
			'submitted_by'  => isset( $_GET['submitted_by'] ) ? absint( $_GET['submitted_by'] ) : '',
			'search'        => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'date_from'     => isset( $_GET['date_from'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET['date_from'] ) ? $_GET['date_from'] : '',
			'date_to'       => isset( $_GET['date_to'] ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $_GET['date_to'] ) ? $_GET['date_to'] : '',
			'orderby'       => isset( $_GET['orderby'] ) ? sanitize_key( $_GET['orderby'] ) : 'created_at',
			'order'         => isset( $_GET['order'] ) ? sanitize_key( $_GET['order'] ) : 'desc',
		);
		// phpcs:enable
		if ( ! RSL_Roles::can_manage() ) {
			$f['assigned_to'] = (string) get_current_user_id();
		}
		return $f;
	}

	public function prepare_items() {
		$per_page = $this->get_items_per_page( 'rsl_per_page', 20 );
		$args     = $this->current_filters() + array( 'per_page' => $per_page, 'page' => $this->get_pagenum() );
		$res      = RSL_DB::query( $this->object, $args );

		$this->items           = $res['rows'];
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->set_pagination_args( array( 'total_items' => $res['total'], 'per_page' => $per_page ) );
	}

	protected function get_views() {
		$counts  = RSL_DB::count_by_status( $this->object );
		$current = $this->current_filters()['status'];
		$base    = admin_url( 'admin.php?page=' . $this->page_slug );
		$views   = array(
			'all' => sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>', esc_url( $base ), '' === $current ? ' class="current"' : '', esc_html__( 'All', 'ramsetu-leads' ), array_sum( $counts ) ),
		);
		if ( ! RSL_Roles::can_manage() ) {
			return array(); // counts would leak other officers' totals
		}
		foreach ( $this->statuses() as $k => $label ) {
			if ( empty( $counts[ $k ] ) ) {
				continue;
			}
			$views[ $k ] = sprintf( '<a href="%s"%s>%s <span class="count">(%d)</span></a>', esc_url( add_query_arg( 'status', $k, $base ) ), $current === $k ? ' class="current"' : '', esc_html( $label ), $counts[ $k ] );
		}
		return $views;
	}

	protected function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}
		$f = $this->current_filters();
		echo '<div class="alignleft actions rsl-filters">';
		if ( 'lead' === $this->object ) {
			echo '<select name="loan_category"><option value="">' . esc_html__( 'All services', 'ramsetu-leads' ) . '</option>';
			foreach ( rsl_product_labels() as $k => $v ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $f['loan_category'], $k, false ), esc_html( $v ) );
			}
			echo '</select>';
			echo '<select name="source"><option value="">' . esc_html__( 'All sources', 'ramsetu-leads' ) . '</option>';
			foreach ( rsl_sources() as $k => $v ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $k ), selected( $f['source'], $k, false ), esc_html( $v ) );
			}
			echo '</select>';
		}
		if ( RSL_Roles::can_manage() ) {
			echo '<select name="assigned_to"><option value="">' . esc_html__( 'Anyone', 'ramsetu-leads' ) . '</option>';
			printf( '<option value="none"%s>%s</option>', selected( $f['assigned_to'], 'none', false ), esc_html__( '— Not assigned —', 'ramsetu-leads' ) );
			foreach ( $this->users as $id => $name ) {
				printf( '<option value="%d"%s>%s</option>', (int) $id, selected( $f['assigned_to'], (string) $id, false ), esc_html( $name ) );
			}
			echo '</select>';
		}
		printf( '<label class="screen-reader-text" for="rsl-from">%s</label><input type="date" id="rsl-from" name="date_from" value="%s" title="%s">', esc_html__( 'From', 'ramsetu-leads' ), esc_attr( $f['date_from'] ), esc_attr__( 'From date', 'ramsetu-leads' ) );
		printf( '<label class="screen-reader-text" for="rsl-to">%s</label><input type="date" id="rsl-to" name="date_to" value="%s" title="%s">', esc_html__( 'To', 'ramsetu-leads' ), esc_attr( $f['date_to'] ), esc_attr__( 'To date', 'ramsetu-leads' ) );
		submit_button( __( 'Filter', 'ramsetu-leads' ), '', 'filter_action', false );
		if ( RSL_Roles::can_manage() ) {
			$export = wp_nonce_url( add_query_arg( array_filter( $f ) + array( 'action' => 'rsl_export', 'object' => $this->object ), admin_url( 'admin-post.php' ) ), 'rsl_export' );
			printf( ' <a class="button" href="%s">%s</a>', esc_url( $export ), esc_html__( 'Export all (CSV)', 'ramsetu-leads' ) );
		}
		echo '</div>';
	}

	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="ids[]" value="%d">', (int) $item['id'] );
	}

	private function view_link( $item, $text ) {
		return sprintf( '<a class="row-title" href="%s">%s</a>', esc_url( admin_url( 'admin.php?page=' . $this->page_slug . '&view=' . (int) $item['id'] ) ), esc_html( $text ) );
	}

	private function tel( $phone ) {
		return $phone ? sprintf( '<a href="tel:+91%1$s">%1$s</a>', esc_html( $phone ) ) : '';
	}

	protected function column_default( $item, $col ) {
		switch ( $col ) {
			case 'full_name':
				return $this->view_link( $item, $item['full_name'] ) . '<br>' . $this->tel( $item['phone'] );
			case 'referrer':
				return $this->view_link( $item, $item['referrer_name'] ) . '<br>' . $this->tel( $item['referrer_phone'] );
			case 'referred':
				return esc_html( $item['referred_name'] ) . ( $item['referred_phone'] ? '<br>' . $this->tel( $item['referred_phone'] ) : '' );
			case 'loan_category':
				return esc_html( rsl_label( rsl_product_labels(), $item['loan_category'] ) );
			case 'loan_amount':
				return esc_html( rsl_format_inr( $item['loan_amount'] ) );
			case 'location':
				return esc_html( trim( $item['city'] . ', ' . $item['state'], ', ' ) ) . ( ! empty( $item['pincode'] ) ? '<br><span class="rsl-muted">' . esc_html( $item['pincode'] ) . '</span>' : '' );
			case 'profession':
				return esc_html( rsl_label( rsl_professions(), $item['profession'] ) );
			case 'customer_type':
				return esc_html( rsl_label( rsl_professions(), $item['customer_type'] ) );
			case 'loan_type':
				return esc_html( rsl_label( rsl_referral_labels(), $item['loan_type'] ) );
			case 'source':
				return esc_html( rsl_label( rsl_sources(), $item['source'] ) );
			case 'support_needed':
				return esc_html( wp_trim_words( $item['support_needed'], 14 ) );
			case 'assigned_to':
				return $item['assigned_to'] && isset( $this->users[ $item['assigned_to'] ] )
					? esc_html( $this->users[ $item['assigned_to'] ] )
					: '<span class="rsl-muted">' . esc_html__( 'Not assigned', 'ramsetu-leads' ) . '</span>';
			case 'status':
				return sprintf( '<span class="rsl-badge rsl-badge--%s">%s</span>', esc_attr( $item['status'] ), esc_html( rsl_label( $this->statuses(), $item['status'] ) ) );
			case 'created_at':
				$t = strtotime( $item['created_at'] );
				return esc_html( date_i18n( 'd M Y', $t ) ) . '<br><span class="rsl-muted">' . esc_html( date_i18n( 'g:i A', $t ) ) . '</span>';
		}
		return isset( $item[ $col ] ) ? esc_html( $item[ $col ] ) : '';
	}

	public function no_items() {
		esc_html_e( 'Nothing here yet. New submissions from the website will appear here.', 'ramsetu-leads' );
	}
}
