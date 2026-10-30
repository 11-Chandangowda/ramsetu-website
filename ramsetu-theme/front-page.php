<?php
/**
 * Home page.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$hs_photo = array(
	1 => array( 'hs-home', __( 'A happy family at home', 'ramsetu' ) ),
	2 => array( 'hs-lap', __( 'A property owner in front of his building', 'ramsetu' ) ),
	3 => array( 'hs-business', __( 'A smiling kirana shop owner at his store', 'ramsetu' ) ),
	4 => array( 'hs-solar', __( 'A business owner in front of solar panels and equipment', 'ramsetu' ) ),
	5 => array( 'hs-machinery', __( 'A business owner on his factory floor with machinery', 'ramsetu' ) ),
	6 => array( 'hs-education', __( 'A business owner in front of a college and a hotel', 'ramsetu' ) ),
);
$hs_tones  = array( 1 => 'orange', 2 => 'blue', 3 => 'green', 4 => 'orange', 5 => 'blue', 6 => 'green' );
$hs_slides = array();
for ( $i = 1; $i <= 6; $i++ ) {
	if ( '' !== trim( (string) rs_mod( "hs{$i}_title" ) ) ) {
		$hs_slides[] = $i;
	}
}
$hs_total = count( $hs_slides );

/** Small symbol for a feature box, picked from its words. */
$hs_icon = function ( $text ) {
	$t = strtolower( $text );
	foreach ( array( 'tenure' => 'calendar', 'year' => 'calendar', 'door' => 'door', 'document' => 'file', 'interest' => 'percent', 'rate' => 'percent', 'loan' => 'rupee', '₹' => 'rupee', 'approv' => 'clock' ) as $word => $icon ) {
		if ( false !== strpos( $t, $word ) ) {
			return $icon;
		}
	}
	return 'check';
};
?>

<?php if ( $hs_total ) : ?>
<section class="hero hb-hero" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Our loans', 'ramsetu' ); ?>" data-slider>
	<div class="hero__track">
		<?php foreach ( $hs_slides as $k => $i ) : ?>
			<?php
			$first = 0 === $k;
			$tag   = $first ? 'h1' : 'h2';
			$tab   = $first ? '' : ' tabindex="-1"';
			$feats = array_slice( rs_lines( "hs{$i}_chips" ), 0, 4 );
			$photo = rs_photo( "hs{$i}_image", $hs_photo[ $i ][0], $hs_photo[ $i ][1], 'hb__img', '(max-width: 900px) 90vw, 460px', $first );
			?>
			<div class="slide hb hb--<?php echo esc_attr( $hs_tones[ $i ] ); ?><?php echo $first ? ' is-active' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( ( $k + 1 ) . ' / ' . $hs_total ); ?>" data-slide<?php echo $first ? '' : ' aria-hidden="true"'; ?>>
				<div class="container hb__inner">
					<div class="hb__copy slide__copy">
						<?php if ( rs_mod( "hs{$i}_brand" ) ) : ?>
							<p class="hb__brand"><?php echo esc_html( rs_mod( "hs{$i}_brand" ) ); ?></p>
						<?php endif; ?>
						<<?php echo $tag; // phpcs:ignore ?> class="hb__title"><?php echo nl2br( esc_html( trim( rs_mod( "hs{$i}_title" ) ) ) ); // phpcs:ignore ?></<?php echo $tag; // phpcs:ignore ?>>
						<?php if ( rs_mod( "hs{$i}_text" ) ) : ?>
							<p class="hb__text"><?php echo esc_html( rs_mod( "hs{$i}_text" ) ); ?></p>
						<?php endif; ?>
						<?php if ( $feats ) : ?>
							<ul class="hb__feats">
								<?php foreach ( $feats as $f ) : ?>
									<li><span class="hb__feat-icon"><?php echo rs_icon( $hs_icon( $f ) ); // phpcs:ignore ?></span><span><?php echo esc_html( $f ); ?></span></li>
								<?php endforeach; ?>
							</ul>
						<?php endif; ?>
						<div class="hb__actions">
							<a class="btn btn--primary" <?php echo rs_apply_attrs( rs_mod( "hs{$i}_apply" ) ); // phpcs:ignore ?><?php echo $tab; // phpcs:ignore ?>><?php esc_html_e( 'Apply Now', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a>
							<?php if ( rs_mod( "hs{$i}_link" ) ) : ?>
								<a class="btn btn--outline hb__more" href="<?php echo esc_url( rs_link( rs_mod( "hs{$i}_link" ) ) ); ?>"<?php echo $tab; // phpcs:ignore ?>><?php esc_html_e( 'Learn More', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a>
							<?php endif; ?>
						</div>
					</div>
					<div class="hb__visual">
						<span class="hb__shape hb__shape--a" aria-hidden="true"></span>
						<span class="hb__shape hb__shape--b" aria-hidden="true"></span>
						<div class="hb__frame">
							<?php if ( $photo ) : ?>
								<?php echo $photo; // phpcs:ignore ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php if ( $hs_total > 1 ) : ?>
		<div class="container hero__controls hb__controls">
			<div class="hero__dots" role="tablist" aria-label="<?php esc_attr_e( 'Choose a loan', 'ramsetu' ); ?>">
				<?php foreach ( $hs_slides as $k => $i ) : ?>
					<?php $label = rs_mod( "hs{$i}_tab" ) ? rs_mod( "hs{$i}_tab" ) : wp_strip_all_tags( rs_mod( "hs{$i}_title" ) ); ?>
					<button type="button" role="tab" class="hero__dot<?php echo 0 === $k ? ' is-active' : ''; ?>" aria-selected="<?php echo 0 === $k ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $label ); ?>" data-dot="<?php echo (int) $k; ?>"><span><?php echo esc_html( $label ); ?></span></button>
				<?php endforeach; ?>
			</div>
			<button type="button" class="hero__pause" data-pause aria-label="<?php esc_attr_e( 'Pause slideshow', 'ramsetu' ); ?>"><span aria-hidden="true">❚❚</span></button>
		</div>
	<?php endif; ?>
</section>
<?php endif; ?>

<section class="section">
	<div class="container home-about">
		<div class="home-about__head">
			<p class="eyebrow"><?php esc_html_e( 'About Us', 'ramsetu' ); ?></p>
			<h2 class="h2"><?php echo esc_html( rs_mod( 'home_about_title' ) ); ?></h2>
			<a class="link-arrow" href="<?php echo esc_url( rs_page_url( 'about' ) ); ?>"><?php esc_html_e( 'Learn More', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="home-about__body">
			<?php rs_the_paras( 'home_about_text', 'lead' ); ?>
		</div>
	</div>
</section>

<?php
$leaders = rs_team( 'leadership' );
$founder = $leaders ? $leaders[0] : null;
$name    = $founder ? $founder->post_title : 'Sanjeev Verma';
$role    = $founder ? get_post_meta( $founder->ID, '_rs_designation', true ) : 'Founder, MD & CEO';
?>
<section class="section section--dark" aria-labelledby="founder-h">
	<div class="container leader-home">
		<figure class="leader-home__photo">
			<?php if ( $founder && has_post_thumbnail( $founder ) ) : ?>
				<?php echo get_the_post_thumbnail( $founder, 'rs-portrait', array( 'alt' => esc_attr( $name ), 'loading' => 'lazy' ) ); ?>
			<?php else : ?>
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/founder.jpg' ); ?>" alt="<?php echo esc_attr( $name ); ?>" width="592" height="713" loading="lazy">
			<?php endif; ?>
		</figure>
		<div class="leader-home__text">
			<p class="eyebrow" id="founder-h"><?php esc_html_e( 'Our Leadership', 'ramsetu' ); ?></p>
			<div class="leader-home__quote">
				<?php rs_the_paras( 'home_founder_short' ); ?>
			</div>
			<a class="readmore-btn leader-home__more" href="<?php echo esc_url( rs_page_url( 'about' ) . '#leadership' ); ?>"><?php esc_html_e( 'Read More', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a>
			<p class="leader-home__sign"><strong><?php echo esc_html( $name ); ?></strong><span><?php echo esc_html( $role . ' — RamSetu' ); ?></span></p>
		</div>
	</div>
</section>

<section class="section section--tint" id="products">
	<div class="container">
		<div class="section__head section__head--row">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'Our Verticals', 'ramsetu' ); ?></p>
				<h2 class="h2"><?php echo esc_html( rs_mod( 'products_title' ) ); ?></h2>
			</div>
			<a class="link-arrow" <?php echo rs_apply_attrs(); // phpcs:ignore ?>><?php echo esc_html( rtrim( rs_mod( 'products_text' ), '.' ) ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="brands">
			<a class="brandcard brandcard--orange" href="<?php echo esc_url( rs_page_url( 'ramaffordable' ) ); ?>">
				<span class="brandcard__photo"><?php echo rs_photo( 'ra_brand_image', 'brand-ramaffordable', '', '', '(max-width: 760px) 92vw, 45vw' ); // phpcs:ignore ?></span>
				<span class="brandcard__icon"><?php echo rs_icon( 'home' ); // phpcs:ignore ?></span>
				<span class="brandcard__name"><span class="c-orange">Ram</span><span class="c-blue">Affordable</span></span>
				<span class="brandcard__tag"><?php echo esc_html( rs_mod( 'ra_vertical_sub' ) ); ?></span>
				<span class="brandcard__text"><?php echo esc_html( rs_mod( 'ra_home_card' ) ); ?></span>
				<ul class="brandcard__list">
					<?php foreach ( rs_lines( 'ra_home_list' ) as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?>
				</ul>
				<span class="brandcard__cta"><?php esc_html_e( 'Explore More', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></span>
			</a>
			<a class="brandcard brandcard--blue" href="<?php echo esc_url( rs_page_url( 'setuemerging' ) ); ?>">
				<span class="brandcard__photo"><?php echo rs_photo( 'se_brand_image', 'brand-setuemerging', '', '', '(max-width: 760px) 92vw, 45vw' ); // phpcs:ignore ?></span>
				<span class="brandcard__icon"><?php echo rs_icon( 'compass' ); // phpcs:ignore ?></span>
				<span class="brandcard__name"><span class="c-orange">Setu</span><span class="c-blue">Emerging</span> <span class="c-blue brandcard__prime">(Prime)</span></span>
				<span class="brandcard__tag"><?php echo esc_html( rs_mod( 'se_tagline' ) ); ?></span>
				<span class="brandcard__text"><?php echo esc_html( rs_mod( 'se_home_card' ) ); ?></span>
				<ul class="brandcard__list">
					<?php foreach ( rs_lines( 'se_home_list' ) as $item ) : ?><li><?php echo esc_html( $item ); ?></li><?php endforeach; ?>
				</ul>
				<span class="brandcard__cta"><?php esc_html_e( 'Explore More', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></span>
			</a>
		</div>
	</div>
</section>

<?php $why = rs_pairs( 'why_items' ); $special = array(); // RamSetu Special Features removed from the Home page (30 Oct). ?>
<?php if ( $why || $special ) : ?>
<section class="section" id="why">
	<div class="container">
		<?php if ( $why ) : ?>
		<div class="section__head">
			<p class="eyebrow"><?php esc_html_e( 'Why choose us', 'ramsetu' ); ?></p>
			<h2 class="h2"><?php echo esc_html( rs_mod( 'why_title' ) ); ?></h2>
		</div>
		<div class="why why--<?php echo (int) count( $why ); ?>">
			<?php
			$why_icons = array( 'shield', 'users', 'check', 'home', 'spark', 'door' );
			foreach ( $why as $k => $item ) :
				?>
				<div class="why__item">
					<span class="why__icon"><?php echo rs_icon( $why_icons[ $k % count( $why_icons ) ] ); // phpcs:ignore ?></span>
					<h3 class="why__title"><?php echo esc_html( $item[0] ); ?></h3>
					<?php if ( $item[1] ) : ?><p><?php echo esc_html( $item[1] ); ?></p><?php endif; ?>
				</div>
			<?php endforeach; ?>
		</div>
		<?php endif; ?>

		<?php if ( $special ) : ?>
		<h3 class="special__h"><?php echo esc_html( rs_mod( 'special_title' ) ); ?></h3>
		<ul class="special">
			<?php
			$special_icons = array( 'users', 'store', 'home', 'spark', 'rupee', 'calendar' );
			foreach ( $special as $k => $item ) :
				?>
				<li class="special__item">
					<span class="special__icon"><?php echo rs_icon( $special_icons[ $k % count( $special_icons ) ] ); // phpcs:ignore ?></span>
					<span><strong><?php echo esc_html( $item[0] ); ?></strong><?php if ( $item[1] ) : ?><small><?php echo esc_html( $item[1] ); ?></small><?php endif; ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php endif; ?>
	</div>
</section>
<?php endif; ?>

<?php get_template_part( 'parts/partners' ); ?>

<section class="cta-band">
	<div class="container cta-band__inner">
		<div>
			<h2 class="h3"><?php esc_html_e( 'Ready to take the next step?', 'ramsetu' ); ?></h2>
			<p><?php esc_html_e( 'Apply in two minutes, or ask us to call you back. We will come to your doorstep if you cannot visit a branch.', 'ramsetu' ); ?></p>
		</div>
		<div class="cta-band__actions">
			<a class="btn btn--white" <?php echo rs_apply_attrs(); // phpcs:ignore ?>><?php esc_html_e( 'Apply Now', 'ramsetu' ); ?></a>
			<a class="btn btn--outline-white" href="<?php echo esc_url( rs_page_url( 'branches' ) . '#callback' ); ?>"><?php esc_html_e( 'Request a Call Back', 'ramsetu' ); ?></a>
		</div>
	</div>
</section>

<?php
get_footer();
