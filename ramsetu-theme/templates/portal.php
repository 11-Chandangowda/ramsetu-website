<?php
/**
 * Template Name: Partner Portal
 * Login / registration for channel partners and employees, and their dashboard.
 * The portal lives in the RamSetu app; the plugin forwards this page there.
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="portal-page">
	<div class="container">
		<?php
		// Normally visitors never see this page: the RamSetu Website Forms plugin forwards it to the app.
		echo '<h1 class="h2">' . esc_html__( 'Partner & Employee Login', 'ramsetu' ) . '</h1>';
		echo '<p>' . esc_html__( 'The partner portal is opening soon. Please check back shortly.', 'ramsetu' ) . '</p>';
		rs_admin_hint( __( 'Connect the website to the RamSetu app in RamSetu → Connection; this page will then forward to the app login.', 'ramsetu' ) );
		?>
	</div>
</section>
<?php
get_footer();
