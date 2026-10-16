<?php
/**
 * Template Name: Apply Now
 * Full-page version of the Apply Now form (the header button opens it as a pop-up;
 * this page is the fallback and a shareable link for ads / WhatsApp).
 */
defined( 'ABSPATH' ) || exit;
get_header();

// phpcs:ignore WordPress.Security.NonceVerification
$service = isset( $_GET['service'] ) ? sanitize_key( $_GET['service'] ) : '';
?>

<section class="section apply-page">
	<div class="container apply-page__grid">
		<div class="apply-page__intro">
			<p class="eyebrow"><?php esc_html_e( 'Apply Now', 'ramsetu' ); ?></p>
			<h1 class="h1"><?php esc_html_e( 'Apply for a loan', 'ramsetu' ); ?></h1>
			<p class="lead"><?php esc_html_e( 'Fill in your details and our team will get in touch with you at the earliest. No documents needed at this stage.', 'ramsetu' ); ?></p>
			<ul class="checklist">
				<li><?php echo rs_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'Takes about two minutes', 'ramsetu' ); ?></li>
				<li><?php echo rs_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'A loan officer calls you back', 'ramsetu' ); ?></li>
				<li><?php echo rs_icon( 'check' ); // phpcs:ignore ?><?php esc_html_e( 'No obligation, no charges to apply', 'ramsetu' ); ?></li>
			</ul>
		</div>
		<div class="form-card form-card--lg">
			<h2 class="h3"><?php esc_html_e( 'Apply for Loan', 'ramsetu' ); ?></h2>
			<p class="muted small"><?php esc_html_e( '* denotes mandatory fields', 'ramsetu' ); ?></p>
			<?php echo rs_has_leads() ? do_shortcode( '[ramsetu_apply_form id="rsl-apply" category="' . esc_attr( $service ) . '"]' ) : ''; // phpcs:ignore ?>
		</div>
	</div>
</section>

<?php
get_footer();
