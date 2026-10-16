<?php
defined( 'ABSPATH' ) || exit;
get_header();
?>
<section class="section notfound">
	<div class="container narrow center">
		<p class="eyebrow">404</p>
		<h1 class="h1"><?php esc_html_e( "This page doesn't exist", 'ramsetu' ); ?></h1>
		<p class="lead"><?php esc_html_e( 'The link may be old or mistyped. Here are some useful places instead:', 'ramsetu' ); ?></p>
		<p class="notfound__links">
			<a class="btn btn--primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'ramsetu' ); ?></a>
			<a class="btn btn--outline" <?php echo rs_apply_attrs(); // phpcs:ignore ?>><?php esc_html_e( 'Apply Now', 'ramsetu' ); ?></a>
			<a class="btn btn--outline" href="<?php echo esc_url( rs_page_url( 'branches' ) ); ?>"><?php esc_html_e( 'Branch Locator', 'ramsetu' ); ?></a>
		</p>
	</div>
</section>
<?php
get_footer();
