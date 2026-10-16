<?php
/**
 * Editable site content: Team members (leadership + board of advisors) and Partners.
 * Photos/logos use the Featured Image. Order with the "Order" field.
 */

defined( 'ABSPATH' ) || exit;

class RSL_Content {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post', array( __CLASS__, 'save_meta' ), 10, 2 );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
	}

	public static function register() {
		register_post_type( 'rs_team', array(
			'labels'       => array(
				'name'          => __( 'Team', 'ramsetu-leads' ),
				'singular_name' => __( 'Team Member', 'ramsetu-leads' ),
				'add_new_item'  => __( 'Add Team Member', 'ramsetu-leads' ),
				'edit_item'     => __( 'Edit Team Member', 'ramsetu-leads' ),
				'menu_name'     => __( 'Team & Advisors', 'ramsetu-leads' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-groups',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'page-attributes' ),
		) );

		register_taxonomy( 'rs_team_group', 'rs_team', array(
			'labels'            => array(
				'name'          => __( 'Groups', 'ramsetu-leads' ),
				'singular_name' => __( 'Group', 'ramsetu-leads' ),
			),
			'public'            => false,
			'show_ui'           => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'hierarchical'      => true,
		) );

		register_post_type( 'rs_partner', array(
			'labels'       => array(
				'name'          => __( 'Partners', 'ramsetu-leads' ),
				'singular_name' => __( 'Partner', 'ramsetu-leads' ),
				'add_new_item'  => __( 'Add Partner', 'ramsetu-leads' ),
				'edit_item'     => __( 'Edit Partner', 'ramsetu-leads' ),
				'menu_name'     => __( 'Lending Partners', 'ramsetu-leads' ),
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-networking',
			'supports'     => array( 'title', 'thumbnail', 'page-attributes' ),
		) );

		if ( ! get_option( 'rsl_terms_seeded' ) ) {
			foreach ( array( 'leadership' => 'Leadership Team', 'advisor' => 'Board of Advisors' ) as $slug => $name ) {
				if ( ! term_exists( $slug, 'rs_team_group' ) ) {
					wp_insert_term( $name, 'rs_team_group', array( 'slug' => $slug ) );
				}
			}
			update_option( 'rsl_terms_seeded', 1 );
		}
	}

	public static function title_placeholder( $text, $post ) {
		if ( 'rs_team' === $post->post_type ) {
			return __( 'Full name', 'ramsetu-leads' );
		}
		if ( 'rs_partner' === $post->post_type ) {
			return __( 'Partner name (e.g. bank or NBFC)', 'ramsetu-leads' );
		}
		return $text;
	}

	public static function meta_boxes() {
		add_meta_box( 'rsl_team_meta', __( 'Details', 'ramsetu-leads' ), array( __CLASS__, 'team_box' ), 'rs_team', 'side', 'high' );
		add_meta_box( 'rsl_partner_meta', __( 'Details', 'ramsetu-leads' ), array( __CLASS__, 'partner_box' ), 'rs_partner', 'side', 'high' );
	}

	public static function team_box( $post ) {
		wp_nonce_field( 'rsl_content_meta', 'rsl_content_nonce' );
		$designation = get_post_meta( $post->ID, '_rs_designation', true );
		$linkedin    = get_post_meta( $post->ID, '_rs_linkedin', true );
		?>
		<p><label for="rs_designation"><strong><?php esc_html_e( 'Designation', 'ramsetu-leads' ); ?></strong></label><br>
		<input type="text" class="widefat" id="rs_designation" name="rs_designation" value="<?php echo esc_attr( $designation ); ?>" placeholder="Founder, MD &amp; CEO"></p>
		<p><label for="rs_linkedin"><strong><?php esc_html_e( 'LinkedIn URL (optional)', 'ramsetu-leads' ); ?></strong></label><br>
		<input type="url" class="widefat" id="rs_linkedin" name="rs_linkedin" value="<?php echo esc_attr( $linkedin ); ?>"></p>
		<p class="description"><?php esc_html_e( 'The main text box holds past experience / a short bio. For the founder, it is also shown as the "Story and a Message" on the home page.', 'ramsetu-leads' ); ?></p>
		<?php
	}

	public static function partner_box( $post ) {
		wp_nonce_field( 'rsl_content_meta', 'rsl_content_nonce' );
		$url = get_post_meta( $post->ID, '_rs_url', true );
		?>
		<p><label for="rs_url"><strong><?php esc_html_e( 'Website (optional)', 'ramsetu-leads' ); ?></strong></label><br>
		<input type="url" class="widefat" id="rs_url" name="rs_url" value="<?php echo esc_attr( $url ); ?>"></p>
		<p class="description"><?php esc_html_e( 'Set the logo as the Featured Image.', 'ramsetu-leads' ); ?></p>
		<?php
	}

	public static function save_meta( $post_id, $post ) {
		if ( ! isset( $_POST['rsl_content_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['rsl_content_nonce'] ), 'rsl_content_meta' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( 'rs_team' === $post->post_type ) {
			update_post_meta( $post_id, '_rs_designation', sanitize_text_field( wp_unslash( $_POST['rs_designation'] ?? '' ) ) );
			update_post_meta( $post_id, '_rs_linkedin', esc_url_raw( wp_unslash( $_POST['rs_linkedin'] ?? '' ) ) );
		}
		if ( 'rs_partner' === $post->post_type ) {
			update_post_meta( $post_id, '_rs_url', esc_url_raw( wp_unslash( $_POST['rs_url'] ?? '' ) ) );
		}
	}

	/** Used by the theme. $group: leadership | advisor */
	public static function team( $group ) {
		return get_posts( array(
			'post_type'      => 'rs_team',
			'posts_per_page' => 50,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
			'tax_query'      => array( array( 'taxonomy' => 'rs_team_group', 'field' => 'slug', 'terms' => $group ) ),
		) );
	}

	public static function partners() {
		return get_posts( array(
			'post_type'      => 'rs_partner',
			'posts_per_page' => 50,
			'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'ASC' ),
		) );
	}
}
