<?php defined( 'ABSPATH' ) || exit; ?>
</main>

<footer class="site-footer">
	<div class="container site-footer__grid">
		<div class="site-footer__brand">
			<a class="brand brand--footer" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<img class="brand__logo" src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo.png' ); ?>" width="56" height="56" alt="">
				<span class="brand__text"><span class="wordmark"><span class="wordmark__ram">Ram</span><span class="wordmark__setu">Setu</span></span><span class="brand__sub">Financial Service Private Limited</span></span>
			</a>
			<p class="brand__tagline">&laquo;&laquo; A Solution-Providing Institution &raquo;&raquo;</p>
			<p><?php esc_html_e( 'Connecting MSMEs, entrepreneurs, self-employed professionals and salaried customers with suitable financial solutions from banks and NBFCs.', 'ramsetu' ); ?></p>
			<?php
			$social = array_filter( array(
				'linkedin'  => rs_mod( 'social_linkedin' ),
				'facebook'  => rs_mod( 'social_facebook' ),
				'instagram' => rs_mod( 'social_instagram' ),
				'youtube'   => rs_mod( 'social_youtube' ),
			) );
			if ( $social ) :
				?>
				<ul class="social">
					<?php foreach ( $social as $net => $url ) : ?>
						<li><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( ucfirst( $net ) ); ?>"><?php echo rs_icon( $net ); // phpcs:ignore ?></a></li>
					<?php endforeach; ?>
				</ul>
			<?php endif; ?>
		</div>

		<div>
			<h2 class="site-footer__h"><?php esc_html_e( 'About Us', 'ramsetu' ); ?></h2>
			<ul class="site-footer__links">
				<li><a href="<?php echo esc_url( rs_page_url( 'about' ) ); ?>"><?php esc_html_e( 'About RamSetu', 'ramsetu' ); ?></a></li>
				<li><a href="<?php echo esc_url( rs_page_url( 'about' ) . '#vision' ); ?>"><?php esc_html_e( 'Vision & Mission', 'ramsetu' ); ?></a></li>
				<li><a href="<?php echo esc_url( rs_page_url( 'about' ) . '#roadmap' ); ?>"><?php esc_html_e( 'Future Roadmap', 'ramsetu' ); ?></a></li>
				<li><a href="<?php echo esc_url( rs_page_url( 'about' ) . '#leadership' ); ?>"><?php esc_html_e( 'Our Leadership', 'ramsetu' ); ?></a></li>
				<li><a href="<?php echo esc_url( rs_page_url( 'about' ) . '#presence' ); ?>"><?php esc_html_e( 'Our Presence', 'ramsetu' ); ?></a></li>
			</ul>
		</div>

		<div>
			<h2 class="site-footer__h"><?php esc_html_e( 'Our Products', 'ramsetu' ); ?></h2>
			<ul class="site-footer__links">
				<li><a href="<?php echo esc_url( rs_page_url( 'ramaffordable' ) . '#lap' ); ?>"><?php esc_html_e( 'Affordable Loans Against Property', 'ramsetu' ); ?></a></li>
				<li><a href="<?php echo esc_url( rs_page_url( 'ramaffordable' ) . '#home-loan' ); ?>"><?php esc_html_e( 'Affordable Home Loans', 'ramsetu' ); ?></a></li>
				<li><a href="<?php echo esc_url( rs_page_url( 'setuemerging' ) . '#plant-machinery' ); ?>"><?php esc_html_e( 'Plant & Machinery Loans', 'ramsetu' ); ?></a></li>
				<li><a href="<?php echo esc_url( rs_page_url( 'setuemerging' ) . '#solutions' ); ?>"><?php esc_html_e( 'Working Capital Loans', 'ramsetu' ); ?></a></li>
				<li><a href="<?php echo esc_url( rs_page_url( 'setuemerging' ) . '#hotels' ); ?>"><?php esc_html_e( 'Hospitality Industry Funding', 'ramsetu' ); ?></a></li>
				<li><a href="<?php echo esc_url( rs_page_url( 'setuemerging' ) . '#schools' ); ?>"><?php esc_html_e( 'Education Industry Loans', 'ramsetu' ); ?></a></li>
			</ul>
		</div>

		<div>
			<h2 class="site-footer__h"><?php esc_html_e( 'Reach Us', 'ramsetu' ); ?></h2>
			<ul class="site-footer__links site-footer__contact">
				<li><a href="<?php echo esc_url( rs_page_url( 'branches' ) ); ?>"><?php echo rs_icon( 'pin' ); // phpcs:ignore ?><?php esc_html_e( 'Locate your nearest branch', 'ramsetu' ); ?></a></li>
				<li><a href="<?php echo esc_url( rs_page_url( 'partner' ) ); ?>"><?php echo rs_icon( 'users' ); // phpcs:ignore ?><?php esc_html_e( 'Become a Partner', 'ramsetu' ); ?></a></li>
				<li><a href="<?php echo esc_url( rs_portal_link() ); ?>"><?php echo rs_icon( 'door' ); // phpcs:ignore ?><?php esc_html_e( 'Partner / Employee Login', 'ramsetu' ); ?></a></li>
				<?php if ( rs_mod( 'phone' ) ) : ?>
					<li><a href="<?php echo esc_attr( rs_tel( rs_mod( 'phone' ) ) ); ?>"><?php echo rs_icon( 'phone' ); // phpcs:ignore ?><?php echo esc_html( rs_mod( 'phone' ) ); ?></a></li>
				<?php endif; ?>
				<?php if ( rs_mod( 'email' ) ) : ?>
					<li><a href="mailto:<?php echo esc_attr( rs_mod( 'email' ) ); ?>"><?php echo rs_icon( 'mail' ); // phpcs:ignore ?><?php echo esc_html( rs_mod( 'email' ) ); ?></a></li>
				<?php endif; ?>
				<li><a href="<?php echo esc_url( rs_page_url( 'contact' ) ); ?>"><?php echo rs_icon( 'mail' ); // phpcs:ignore ?><?php esc_html_e( 'Contact us', 'ramsetu' ); ?></a></li>
			</ul>
		</div>

		<div>
			<h2 class="site-footer__h"><?php esc_html_e( 'Headquarters', 'ramsetu' ); ?></h2>
			<address class="site-footer__address"><?php echo nl2br( esc_html( rs_mod( 'hq_address' ) ) ); ?></address>
			<ul class="site-footer__links">
				<li><a href="<?php echo esc_url( rs_page_url( 'policies' ) ); ?>"><?php echo rs_icon( 'file' ); // phpcs:ignore ?><?php esc_html_e( 'Policies Repository', 'ramsetu' ); ?></a></li>
			</ul>
		</div>
	</div>

	<div class="site-footer__legal">
		<div class="container">
			<p>&copy; <?php echo esc_html( gmdate( 'Y' ) ); ?> RamSetu Financial Service Private Limited</p>
			<p class="site-footer__ids">
				<?php if ( rs_mod( 'cin' ) ) : ?><span>CIN: <?php echo esc_html( rs_mod( 'cin' ) ); ?></span><?php endif; ?>
				<?php if ( rs_mod( 'gst' ) ) : ?><span>GST: <?php echo esc_html( rs_mod( 'gst' ) ); ?></span><?php endif; ?>
			</p>
		</div>
	</div>
</footer>

<div class="mobile-cta" data-mobile-cta>
	<?php if ( rs_mod( 'phone' ) ) : ?>
		<a class="btn btn--ghost" href="<?php echo esc_attr( rs_tel( rs_mod( 'phone' ) ) ); ?>"><?php echo rs_icon( 'phone' ); // phpcs:ignore ?><?php esc_html_e( 'Call', 'ramsetu' ); ?></a>
	<?php else : ?>
		<a class="btn btn--ghost" href="<?php echo esc_url( rs_page_url( 'branches' ) . '#callback' ); ?>"><?php echo rs_icon( 'phone' ); // phpcs:ignore ?><?php esc_html_e( 'Call back', 'ramsetu' ); ?></a>
	<?php endif; ?>
	<a class="btn btn--primary" <?php echo rs_apply_attrs(); // phpcs:ignore ?>><?php esc_html_e( 'Apply Now', 'ramsetu' ); ?></a>
</div>

<?php if ( ! is_page_template( 'templates/apply.php' ) ) { get_template_part( 'parts/apply-modal' ); } ?>

<?php wp_footer(); ?>
</body>
</html>
