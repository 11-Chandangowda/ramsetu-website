<?php
/**
 * Template Name: Contact Us
 */
defined( 'ABSPATH' ) || exit;
get_header();

rs_page_hero( __( 'Contact Us', 'ramsetu' ), __( "We're here to help", 'ramsetu' ), __( 'Reach any of our offices, or request a call back and we will reach out to you.', 'ramsetu' ), 'blue', 'contact' );
?>

<section class="section">
	<div class="container contact">
		<div class="contact__offices">
			<h2 class="h3"><?php esc_html_e( 'Our Offices', 'ramsetu' ); ?></h2>
			<?php if ( rs_mod( 'phone' ) || rs_mod( 'email' ) ) : ?>
				<ul class="contact__quick">
					<?php if ( rs_mod( 'phone' ) ) : ?><li><?php echo rs_icon( 'phone' ); // phpcs:ignore ?><a href="<?php echo esc_attr( rs_tel( rs_mod( 'phone' ) ) ); ?>"><?php echo esc_html( rs_mod( 'phone' ) ); ?></a></li><?php endif; ?>
					<?php if ( rs_mod( 'email' ) ) : ?><li><?php echo rs_icon( 'mail' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( rs_mod( 'email' ) ); ?>"><?php echo esc_html( rs_mod( 'email' ) ); ?></a></li><?php endif; ?>
					<?php if ( rs_mod( 'whatsapp' ) ) : ?><li><?php echo rs_icon( 'whatsapp' ); // phpcs:ignore ?><a href="https://wa.me/91<?php echo esc_attr( preg_replace( '/\D/', '', rs_mod( 'whatsapp' ) ) ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Chat on WhatsApp', 'ramsetu' ); ?></a></li><?php endif; ?>
				</ul>
			<?php endif; ?>

			<div class="offices">
				<?php foreach ( rs_branches() as $b ) : ?>
					<article class="office">
						<h3><?php echo esc_html( $b['name'] ? $b['name'] : $b['city'] ); ?></h3>
						<p class="office__place"><?php echo esc_html( $b['city'] . ', ' . $b['state'] ); ?></p>
						<?php get_template_part( 'parts/branch-info', null, array( 'b' => $b ) ); ?>
					</article>
				<?php endforeach; ?>
			</div>
		</div>

		<aside class="contact__side">
			<div class="grievance">
				<h2 class="h3"><?php echo rs_icon( 'shield' ); // phpcs:ignore ?><?php esc_html_e( 'Grievance Redressal', 'ramsetu' ); ?></h2>
				<p><?php echo esc_html( rs_mod( 'grievance_note' ) ); ?></p>
				<?php if ( rs_mod( 'grievance_name' ) || rs_mod( 'grievance_phone' ) || rs_mod( 'grievance_email' ) ) : ?>
					<dl class="grievance__dl">
						<?php if ( rs_mod( 'grievance_name' ) ) : ?><div><dt><?php esc_html_e( 'Officer', 'ramsetu' ); ?></dt><dd><?php echo esc_html( rs_mod( 'grievance_name' ) ); ?></dd></div><?php endif; ?>
						<?php if ( rs_mod( 'grievance_phone' ) ) : ?><div><dt><?php esc_html_e( 'Phone', 'ramsetu' ); ?></dt><dd><a href="<?php echo esc_attr( rs_tel( rs_mod( 'grievance_phone' ) ) ); ?>"><?php echo esc_html( rs_mod( 'grievance_phone' ) ); ?></a></dd></div><?php endif; ?>
						<?php if ( rs_mod( 'grievance_email' ) ) : ?><div><dt><?php esc_html_e( 'Email', 'ramsetu' ); ?></dt><dd><a href="mailto:<?php echo esc_attr( rs_mod( 'grievance_email' ) ); ?>"><?php echo esc_html( rs_mod( 'grievance_email' ) ); ?></a></dd></div><?php endif; ?>
					</dl>
				<?php else : ?>
					<?php rs_admin_hint( __( 'Add the Grievance Redressal Officer details in Customize → RamSetu → Contact & company details.', 'ramsetu' ) ); ?>
				<?php endif; ?>
			</div>

			<div class="form-card">
				<h2 class="h3"><?php esc_html_e( 'Request a Call Back', 'ramsetu' ); ?></h2>
				<?php echo rs_has_leads() ? do_shortcode( '[ramsetu_callback_form id="rsl-callback"]' ) : ''; // phpcs:ignore ?>
			</div>
		</aside>
	</div>
</section>

<?php
get_footer();
