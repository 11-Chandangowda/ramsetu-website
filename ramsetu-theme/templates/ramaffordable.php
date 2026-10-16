<?php
/**
 * Template Name: RamAffordable (Home & Business Loans)
 */
defined( 'ABSPATH' ) || exit;
get_header();

rs_page_hero( __( 'Our Products', 'ramsetu' ), 'RamAffordable', rs_mod( 'ra_tagline' ), 'orange', 'ramaffordable' );
get_template_part( 'parts/product-brief', null, array(
	'prefix' => 'ra',
	'tone'   => 'orange',
	'loans_for' => array( __( 'Affordable Loans For', 'ramsetu' ), 'ra_loans_for' ),
) );
?>

<section class="section section--tint" id="loans">
	<div class="container">
		<div class="section__head">
			<p class="eyebrow"><?php esc_html_e( 'RamAffordable', 'ramsetu' ); ?></p>
			<h2 class="h2"><?php esc_html_e( 'Choose the loan you need', 'ramsetu' ); ?></h2>
		</div>
		<div class="product__offers">
			<?php
			rs_product_card( array(
				'id'       => 'lap',
				'icon'     => 'home',
				'tone'     => 'orange',
				'category' => 'lap',
				'photo'    => array( 'ra_business_image', 'card-business', __( 'A shopkeeper serving a customer at her store', 'ramsetu' ) ),
				'title'    => rs_mod( 'ra_business_title' ),
				'text'     => rs_mod( 'ra_business_text' ),
				'size'     => rs_mod( 'ra_business_size' ),
				'tenure'   => rs_mod( 'ra_business_tenure' ),
				'rate'     => rs_mod( 'ra_business_rate' ),
			) );
			rs_product_card( array(
				'id'       => 'home-loan',
				'icon'     => 'home',
				'tone'     => 'green',
				'category' => 'home_loan',
				'photo'    => array( 'ra_home_image', 'card-home', __( 'A family resting together in their living room', 'ramsetu' ) ),
				'title'    => rs_mod( 'ra_home_title' ),
				'text'     => rs_mod( 'ra_home_text' ),
				'size'     => rs_mod( 'ra_home_size' ),
				'tenure'   => rs_mod( 'ra_home_tenure' ),
				'rate'     => rs_mod( 'ra_home_rate' ),
			) );
			?>
		</div>
		<p class="disclaimer"><?php echo esc_html( rs_mod( 'loans_disclaimer' ) ); ?></p>
	</div>
</section>

<?php
while ( have_posts() ) {
	the_post();
	if ( trim( get_the_content() ) ) {
		echo '<section class="section"><div class="container prose">';
		the_content();
		echo '</div></section>';
	}
}
get_template_part( 'parts/next-steps' );
get_footer();
