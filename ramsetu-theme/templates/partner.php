<?php
/**
 * Template Name: Become a Partner
 */
defined( 'ABSPATH' ) || exit;
get_header();

rs_page_hero( __( 'Become a Partner', 'ramsetu' ), __( 'Refer. Help someone. Earn.', 'ramsetu' ), '', 'green', 'partner' );
?>

<section class="section">
	<div class="container split split--form">
		<div>
			<h2 class="h2"><?php echo esc_html( rs_mod( 'partner_title' ) ); ?></h2>
			<p class="lead"><?php echo esc_html( rs_mod( 'partner_text' ) ); ?></p>
			<ol class="numbered">
				<?php foreach ( rs_lines( 'partner_steps' ) as $s ) : ?>
					<li><?php echo esc_html( $s ); ?></li>
				<?php endforeach; ?>
			</ol>
			<p class="partner-login">
				<?php esc_html_e( 'Already a partner?', 'ramsetu' ); ?>
				<a class="link-arrow" href="<?php echo esc_url( rs_portal_link() ); ?>"><?php esc_html_e( 'Log in with your Partner ID', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a>
			</p>
			<?php
			while ( have_posts() ) {
				the_post();
				if ( trim( get_the_content() ) ) {
					echo '<div class="prose">';
					the_content();
					echo '</div>';
				}
			}
			?>
		</div>
		<div class="form-card form-card--partner">
			<h2 class="h3"><?php esc_html_e( 'Register as a Partner', 'ramsetu' ); ?></h2>
			<p class="form-card__sub"><?php esc_html_e( 'RamSetu reviews every registration. Once approved, your Partner ID and a link to set your password are emailed to you.', 'ramsetu' ); ?></p>
			<?php echo shortcode_exists( 'ramsetu_partner_form' ) ? do_shortcode( '[ramsetu_partner_form]' ) : ''; // phpcs:ignore ?>
			<?php if ( rs_mod( 'partner_disclaimer' ) ) : ?>
				<p class="disclaimer disclaimer--form"><strong><?php esc_html_e( 'Disclaimer:', 'ramsetu' ); ?></strong> <?php echo esc_html( rs_mod( 'partner_disclaimer' ) ); ?></p>
			<?php endif; ?>
		</div>
	</div>
</section>

<?php
get_footer();
