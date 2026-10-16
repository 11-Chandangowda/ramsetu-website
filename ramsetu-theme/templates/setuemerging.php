<?php
/**
 * Template Name: SetuEmerging (Structured Finance & Advisory)
 */
defined( 'ABSPATH' ) || exit;
get_header();

rs_page_hero( __( 'Our Products', 'ramsetu' ), 'SetuEmerging (Prime)', rs_mod( 'se_tagline' ), 'blue', 'setuemerging' );
get_template_part( 'parts/product-brief', null, array(
	'prefix' => 'se',
	'tone'   => 'blue',
	'loans_for' => array( __( 'Loans For', 'ramsetu' ), 'se_loans_for' ),
) );

$industries = rs_lines( 'se_industries' ); // emptied in Round 2 (now part of Loans For); shown again if filled in.

// Solution boxes: anchors match the Our Products menu.
$solutions = rs_lines( 'se_solutions' );
$meta      = array(
	array( 'plant-machinery', 'spark', 'plant_machinery' ),
	array( 'schools', 'users', 'education' ),
	array( 'hotels', 'store', 'hospitality' ),
	array( 'lrd', 'home', 'lrd' ),
	array( 'other', 'compass', 'consulting' ),
);
?>

<?php if ( $industries ) : ?>
	<section class="section section--slim" id="industries">
		<div class="container collateral">
			<h3 class="collateral__h"><?php echo esc_html( rs_mod( 'se_industries_title' ) ); ?></h3>
			<ul class="collateral__list collateral__list--blue">
				<?php foreach ( $industries as $c ) : ?>
					<li><?php echo rs_icon( 'store' ); // phpcs:ignore ?><span><?php echo esc_html( $c ); ?></span></li>
				<?php endforeach; ?>
			</ul>
		</div>
	</section>
<?php endif; ?>

<section class="section section--tint" id="solutions">
	<div class="container">
		<div class="section__head">
			<p class="eyebrow eyebrow--blue"><?php esc_html_e( 'SetuEmerging', 'ramsetu' ); ?></p>
			<h2 class="h2"><?php esc_html_e( 'Solutions for growing businesses', 'ramsetu' ); ?></h2>
		</div>
		<div class="product__offers product__offers--<?php echo count( $solutions ) >= 5 ? '5' : '4'; ?>">
			<?php
			foreach ( $solutions as $k => $title ) {
				$m = $meta[ $k ] ?? array( 'solution-' . ( $k + 1 ), 'compass', 'consulting' );
				rs_product_card( array(
					'id'       => $m[0],
					'icon'     => $m[1],
					'tone'     => 'blue',
					'category' => $m[2],
					'title'    => $title,
					'text'     => '',
				) );
			}
			?>
		</div>
		<p class="disclaimer center"><?php echo esc_html( rs_mod( 'loans_disclaimer' ) ); ?></p>
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
