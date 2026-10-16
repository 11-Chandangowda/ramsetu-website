<?php
/**
 * Database tables and data access.
 *
 * rs_leads      – every loan lead (website now; channel partner / employee in Phase 2)
 * rs_referrals  – "Become a Partner" referral log
 * rs_callbacks  – "Request a Call Back" log
 * rs_activity   – history of assignments, status changes and remarks
 */

defined( 'ABSPATH' ) || exit;

class RSL_DB {

	const OBJECTS = array( 'lead' => 'rs_leads', 'referral' => 'rs_referrals', 'callback' => 'rs_callbacks', 'partner' => 'rs_partners' );

	public static function table( $object ) {
		global $wpdb;
		return $wpdb->prefix . self::OBJECTS[ $object ];
	}

	public static function install() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$c = $wpdb->get_charset_collate();

		// Columns marked "Phase 2" are unused by the public site today; they are here so
		// channel partner and employee submissions land in the same table later.
		dbDelta( "CREATE TABLE {$wpdb->prefix}rs_leads (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			source varchar(20) NOT NULL DEFAULT 'website',
			status varchar(20) NOT NULL DEFAULT 'new',
			full_name varchar(150) NOT NULL DEFAULT '',
			phone varchar(20) NOT NULL DEFAULT '',
			email varchar(150) NOT NULL DEFAULT '',
			address varchar(255) NOT NULL DEFAULT '',
			city varchar(100) NOT NULL DEFAULT '',
			district varchar(100) NOT NULL DEFAULT '',
			state varchar(60) NOT NULL DEFAULT '',
			pincode varchar(6) NOT NULL DEFAULT '',
			loan_category varchar(30) NOT NULL DEFAULT '',
			loan_amount decimal(15,2) DEFAULT NULL,
			profession varchar(40) NOT NULL DEFAULT '',
			business_type varchar(150) NOT NULL DEFAULT '',
			collateral varchar(40) NOT NULL DEFAULT '',
			collateral_value decimal(15,2) DEFAULT NULL,
			monthly_income decimal(15,2) DEFAULT NULL,
			pan varchar(10) NOT NULL DEFAULT '',
			aadhaar_last4 varchar(4) NOT NULL DEFAULT '',
			aadhaar_enc varchar(120) NOT NULL DEFAULT '',
			consent tinyint(1) NOT NULL DEFAULT 0,
			assigned_to bigint(20) unsigned DEFAULT NULL,
			assigned_at datetime DEFAULT NULL,
			submitted_by bigint(20) unsigned DEFAULT NULL,
			partner_id bigint(20) unsigned DEFAULT NULL,
			co_applicant_name varchar(150) NOT NULL DEFAULT '',
			co_applicant_phone varchar(20) NOT NULL DEFAULT '',
			co_applicant_email varchar(150) NOT NULL DEFAULT '',
			business_vintage_years smallint(5) unsigned DEFAULT NULL,
			product varchar(40) NOT NULL DEFAULT '',
			property_type varchar(40) NOT NULL DEFAULT '',
			property_value decimal(15,2) DEFAULT NULL,
			property_location varchar(255) NOT NULL DEFAULT '',
			existing_loans text,
			lender_assigned varchar(150) NOT NULL DEFAULT '',
			credit_status varchar(40) NOT NULL DEFAULT '',
			document_status varchar(40) NOT NULL DEFAULT '',
			disbursement_status varchar(40) NOT NULL DEFAULT '',
			incentive_amount decimal(15,2) DEFAULT NULL,
			incentive_status varchar(20) NOT NULL DEFAULT '',
			remarks text,
			page_url varchar(255) NOT NULL DEFAULT '',
			ip_address varchar(45) NOT NULL DEFAULT '',
			user_agent varchar(255) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY status (status),
			KEY source (source),
			KEY assigned_to (assigned_to),
			KEY created_at (created_at),
			KEY phone (phone),
			KEY partner_id (partner_id),
			KEY submitted_by (submitted_by)
		) $c;" );

		// Channel partner applications / accounts. A WordPress user (login = partner_code)
		// is created only when RamSetu approves the application.
		dbDelta( "CREATE TABLE {$wpdb->prefix}rs_partners (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'pending',
			partner_code varchar(20) DEFAULT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			full_name varchar(150) NOT NULL DEFAULT '',
			mobile varchar(20) NOT NULL DEFAULT '',
			email varchar(150) NOT NULL DEFAULT '',
			firm_name varchar(150) NOT NULL DEFAULT '',
			partner_type varchar(20) NOT NULL DEFAULT '',
			city varchar(100) NOT NULL DEFAULT '',
			district varchar(100) NOT NULL DEFAULT '',
			state varchar(60) NOT NULL DEFAULT '',
			pan varchar(10) NOT NULL DEFAULT '',
			pan_verified tinyint(1) NOT NULL DEFAULT 0,
			pan_verified_by bigint(20) unsigned DEFAULT NULL,
			pan_verified_at datetime DEFAULT NULL,
			pan_name varchar(150) NOT NULL DEFAULT '',
			approved_by bigint(20) unsigned DEFAULT NULL,
			approved_at datetime DEFAULT NULL,
			admin_note text,
			ip_address varchar(45) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			UNIQUE KEY partner_code (partner_code),
			KEY status (status),
			KEY mobile (mobile),
			KEY pan (pan),
			KEY user_id (user_id)
		) $c;" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}rs_referrals (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'new',
			referrer_name varchar(150) NOT NULL DEFAULT '',
			referrer_phone varchar(20) NOT NULL DEFAULT '',
			referred_name varchar(150) NOT NULL DEFAULT '',
			referred_phone varchar(20) NOT NULL DEFAULT '',
			customer_type varchar(40) NOT NULL DEFAULT '',
			loan_type varchar(40) NOT NULL DEFAULT '',
			lead_id bigint(20) unsigned DEFAULT NULL,
			assigned_to bigint(20) unsigned DEFAULT NULL,
			remarks text,
			page_url varchar(255) NOT NULL DEFAULT '',
			ip_address varchar(45) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY status (status),
			KEY referrer_phone (referrer_phone),
			KEY created_at (created_at)
		) $c;" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}rs_callbacks (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL,
			updated_at datetime NOT NULL,
			status varchar(20) NOT NULL DEFAULT 'new',
			full_name varchar(150) NOT NULL DEFAULT '',
			phone varchar(20) NOT NULL DEFAULT '',
			state varchar(60) NOT NULL DEFAULT '',
			city varchar(100) NOT NULL DEFAULT '',
			support_needed text,
			lead_id bigint(20) unsigned DEFAULT NULL,
			assigned_to bigint(20) unsigned DEFAULT NULL,
			remarks text,
			page_url varchar(255) NOT NULL DEFAULT '',
			ip_address varchar(45) NOT NULL DEFAULT '',
			PRIMARY KEY  (id),
			KEY status (status),
			KEY created_at (created_at)
		) $c;" );

		dbDelta( "CREATE TABLE {$wpdb->prefix}rs_activity (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			created_at datetime NOT NULL,
			object_type varchar(20) NOT NULL,
			object_id bigint(20) unsigned NOT NULL,
			user_id bigint(20) unsigned DEFAULT NULL,
			action varchar(40) NOT NULL,
			note text,
			PRIMARY KEY  (id),
			KEY object (object_type,object_id)
		) $c;" );

		update_option( 'rsl_db_version', RSL_DB_VERSION );
	}

	/* ---------- generic CRUD ---------- */

	public static function insert( $object, array $data ) {
		global $wpdb;
		$now                = current_time( 'mysql' );
		$data['created_at'] = $now;
		$data['updated_at'] = $now;
		$ok = $wpdb->insert( self::table( $object ), $data );
		if ( ! $ok ) {
			return 0;
		}
		$id = (int) $wpdb->insert_id;
		self::log( $object, $id, 'created', isset( $data['source'] ) ? 'Source: ' . $data['source'] : '' );
		return $id;
	}

	public static function get( $object, $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( $object ) . ' WHERE id = %d', $id ), ARRAY_A );
	}

	public static function update( $object, $id, array $data ) {
		global $wpdb;
		$data['updated_at'] = current_time( 'mysql' );
		return false !== $wpdb->update( self::table( $object ), $data, array( 'id' => (int) $id ) );
	}

	public static function delete( $object, $id ) {
		global $wpdb;
		$wpdb->delete( $wpdb->prefix . 'rs_activity', array( 'object_type' => $object, 'object_id' => (int) $id ) );
		return (bool) $wpdb->delete( self::table( $object ), array( 'id' => (int) $id ) );
	}

	/**
	 * Filtered list. $args: status, source, loan_category, assigned_to, search,
	 * date_from, date_to, orderby, order, per_page, page.
	 * Returns array( rows, total ).
	 */
	public static function query( $object, array $args = array() ) {
		global $wpdb;
		$t     = self::table( $object );
		$where = array( '1=1' );
		$vals  = array();

		foreach ( array( 'status', 'source', 'loan_category' ) as $col ) {
			if ( ! empty( $args[ $col ] ) && self::has_column( $object, $col ) ) {
				$where[] = "$col = %s";
				$vals[]  = $args[ $col ];
			}
		}
		foreach ( array( 'partner_id', 'submitted_by' ) as $col ) {
			if ( ! empty( $args[ $col ] ) && self::has_column( $object, $col ) ) {
				$where[] = "$col = %d";
				$vals[]  = (int) $args[ $col ];
			}
		}
		if ( isset( $args['assigned_to'] ) && '' !== $args['assigned_to'] ) {
			if ( 'none' === $args['assigned_to'] ) {
				$where[] = 'assigned_to IS NULL';
			} else {
				$where[] = 'assigned_to = %d';
				$vals[]  = (int) $args['assigned_to'];
			}
		}
		if ( ! empty( $args['date_from'] ) ) {
			$where[] = 'created_at >= %s';
			$vals[]  = $args['date_from'] . ' 00:00:00';
		}
		if ( ! empty( $args['date_to'] ) ) {
			$where[] = 'created_at <= %s';
			$vals[]  = $args['date_to'] . ' 23:59:59';
		}
		if ( ! empty( $args['search'] ) ) {
			$like  = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$cols  = self::search_columns( $object );
			$parts = array();
			foreach ( $cols as $col ) {
				$parts[] = "$col LIKE %s";
				$vals[]  = $like;
			}
			$where[] = '(' . implode( ' OR ', $parts ) . ')';
		}

		$sql_where = implode( ' AND ', $where );
		$orderby   = ( ! empty( $args['orderby'] ) && self::has_column( $object, $args['orderby'] ) ) ? $args['orderby'] : 'created_at';
		$order     = ( isset( $args['order'] ) && 'asc' === strtolower( $args['order'] ) ) ? 'ASC' : 'DESC';
		$per_page  = isset( $args['per_page'] ) ? max( 1, (int) $args['per_page'] ) : 20;
		$page      = isset( $args['page'] ) ? max( 1, (int) $args['page'] ) : 1;
		$offset    = ( $page - 1 ) * $per_page;

		$count_sql = "SELECT COUNT(*) FROM $t WHERE $sql_where";
		$rows_sql  = "SELECT * FROM $t WHERE $sql_where ORDER BY $orderby $order, id DESC";
		if ( empty( $args['no_limit'] ) ) {
			$rows_sql .= $wpdb->prepare( ' LIMIT %d OFFSET %d', $per_page, $offset );
		}

		if ( $vals ) {
			$count_sql = $wpdb->prepare( $count_sql, $vals );
			$rows_sql  = $wpdb->prepare( $rows_sql, $vals );
		}

		return array(
			'rows'  => $wpdb->get_results( $rows_sql, ARRAY_A ),
			'total' => (int) $wpdb->get_var( $count_sql ),
		);
	}

	public static function count_by_status( $object ) {
		global $wpdb;
		$out = array();
		foreach ( $wpdb->get_results( 'SELECT status, COUNT(*) n FROM ' . self::table( $object ) . ' GROUP BY status', ARRAY_A ) as $r ) {
			$out[ $r['status'] ] = (int) $r['n'];
		}
		return $out;
	}

	public static function count_new( $object ) {
		$c = self::count_by_status( $object );
		return isset( $c['new'] ) ? $c['new'] : 0;
	}

	/* ---------- activity log ---------- */

	public static function log( $object, $id, $action, $note = '' ) {
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'rs_activity', array(
			'created_at'  => current_time( 'mysql' ),
			'object_type' => $object,
			'object_id'   => (int) $id,
			'user_id'     => get_current_user_id() ? get_current_user_id() : null,
			'action'      => $action,
			'note'        => $note,
		) );
	}

	public static function activity( $object, $id ) {
		global $wpdb;
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}rs_activity WHERE object_type = %s AND object_id = %d ORDER BY created_at DESC, id DESC",
			$object, $id
		), ARRAY_A );
	}

	/* ---------- helpers ---------- */

	private static function search_columns( $object ) {
		switch ( $object ) {
			case 'referral':
				return array( 'referrer_name', 'referrer_phone', 'referred_name', 'referred_phone' );
			case 'callback':
				return array( 'full_name', 'phone', 'city', 'support_needed' );
			case 'partner':
				return array( 'full_name', 'mobile', 'email', 'firm_name', 'partner_code', 'pan', 'city' );
			default:
				return array( 'full_name', 'phone', 'email', 'city', 'pincode', 'business_type' );
		}
	}

	private static $columns = array();

	public static function has_column( $object, $col ) {
		global $wpdb;
		if ( ! isset( self::$columns[ $object ] ) ) {
			self::$columns[ $object ] = $wpdb->get_col( 'SHOW COLUMNS FROM ' . self::table( $object ) );
		}
		return in_array( $col, self::$columns[ $object ], true );
	}
}
