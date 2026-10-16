<?php
/**
 * Home page.
 */
defined( 'ABSPATH' ) || exit;
get_header();

$slide_icons = array( 1 => 'store', 2 => 'home', 3 => 'compass' );
$slide_tones = array( 1 => 'orange', 2 => 'green', 3 => 'blue' );
$slide_apply = array( 1 => 'business_loan', 2 => 'home_loan', 3 => 'consulting' );
$slide_photo = array(
	1 => array( 'slide-business', __( 'A smiling shop owner at the counter of his store', 'ramsetu' ) ),
	2 => array( 'slide-home', __( 'A family relaxing together in their home', 'ramsetu' ) ),
	3 => array( 'slide-advisory', __( 'A business owner shaking hands with a RamSetu advisor', 'ramsetu' ) ),
);
?>

<section class="hero hero--cover" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Highlights', 'ramsetu' ); ?>" data-slider>
	<div class="hero__track">
		<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
			<?php $tag = 1 === $i ? 'h1' : 'h2'; ?>
			<div class="slide slide--<?php echo esc_attr( $slide_tones[ $i ] ); ?><?php echo 1 === $i ? ' is-active' : ''; ?>" role="group" aria-roledescription="slide" aria-label="<?php echo esc_attr( $i . ' / 3' ); ?>" data-slide<?php echo 1 === $i ? '' : ' aria-hidden="true"'; ?>>
				<div class="slide__bg">
					<?php echo rs_photo( "slide{$i}_image", $slide_photo[ $i ][0], $slide_photo[ $i ][1], 'slide__img', '100vw', 1 === $i ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
				</div>
				<div class="container slide__inner">
					<div class="slide__copy">
						<p class="eyebrow"><?php echo esc_html( rs_mod( "slide{$i}_eyebrow" ) ); ?></p>
						<<?php echo $tag; // phpcs:ignore ?> class="slide__title"><?php echo esc_html( rs_mod( "slide{$i}_title" ) ); ?></<?php echo $tag; // phpcs:ignore ?>>
						<p class="slide__text"><?php echo esc_html( rs_mod( "slide{$i}_text" ) ); ?></p>
						<div class="slide__actions">
							<a class="btn btn--primary" href="<?php echo esc_url( rs_link( rs_mod( "slide{$i}_link" ) ) ); ?>"<?php echo 1 === $i ? '' : ' tabindex="-1"'; ?>><?php echo esc_html( rs_mod( "slide{$i}_cta" ) ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a>
							<a class="btn btn--outline-white" <?php echo rs_apply_attrs( $slide_apply[ $i ] ); // phpcs:ignore ?><?php echo 1 === $i ? '' : ' tabindex="-1"'; ?>><?php esc_html_e( 'Apply Now', 'ramsetu' ); ?></a>
						</div>
					</div>
				</div>
			</div>
		<?php endfor; ?>
	</div>
	<div class="container hero__controls">
		<div class="hero__dots" role="tablist" aria-label="<?php esc_attr_e( 'Choose slide', 'ramsetu' ); ?>">
			<?php for ( $i = 1; $i <= 3; $i++ ) : ?>
				<button type="button" role="tab" class="hero__dot<?php echo 1 === $i ? ' is-active' : ''; ?>" aria-selected="<?php echo 1 === $i ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( rs_mod( "slide{$i}_tab" ) ); ?>" data-dot="<?php echo (int) ( $i - 1 ); ?>"><span><?php echo esc_html( rs_mod( "slide{$i}_tab" ) ); ?></span></button>
			<?php endfor; ?>
		</div>
		<button type="button" class="hero__pause" data-pause aria-label="<?php esc_attr_e( 'Pause slideshow', 'ramsetu' ); ?>"><span aria-hidden="true">❚❚</span></button>
	</div>
</section>

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
			<div class="leader-home__quote" data-readmore>
				<?php rs_the_paras( 'home_founder_message' ); ?>
			</div>
			<p class="leader-home__sign"><strong><?php echo esc_html( $name ); ?></strong><span><?php echo esc_html( $role . ' — RamSetu' ); ?></span></p>
		</div>
	</div>
</section>

<section class="section section--tint" id="products">
	<div class="container">
		<div class="section__head section__head--row">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'Our Products', 'ramsetu' ); ?></p>
				<h2 class="h2"><?php echo esc_html( rs_mod( 'products_title' ) ); ?></h2>
			</div>
			<a class="link-arrow" <?php echo rs_apply_attrs(); // phpcs:ignore ?>><?php echo esc_html( rtrim( rs_mod( 'products_text' ), '.' ) ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="brands">
			<a class="brandcard brandcard--orange" href="<?php echo esc_url( rs_page_url( 'ramaffordable' ) ); ?>">
				<span class="brandcard__photo"><?php echo rs_photo( 'ra_brand_image', 'brand-ramaffordable', '', '', '(max-width: 760px) 92vw, 45vw' ); // phpcs:ignore ?></span>
				<span class="brandcard__icon"><?php echo rs_icon( 'home' ); // phpcs:ignore ?></span>
				<span class="brandcard__name">RamAffordable</span>
				<span class="brandcard__tag"><?php echo esc_html( rs_mod( 'ra_tagline' ) ); ?></span>
				<span class="brandcard__text"><?php echo esc_html( rs_mod( 'ra_home_card' ) ); ?></span>
				<ul class="brandcard__list">
					<li><?php esc_html_e( 'Affordable Home Loans', 'ramsetu' ); ?></li>
					<li><?php esc_html_e( 'Loans Against Property', 'ramsetu' ); ?></li>
					<li><?php esc_html_e( 'Balance Transfer & Top Up', 'ramsetu' ); ?></li>
				</ul>
				<span class="brandcard__cta"><?php esc_html_e( 'Explore More', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></span>
				<?php get_template_part( 'parts/bridge-art', null, array( 'class' => 'brandcard__art' ) ); ?>
			</a>
			<a class="brandcard brandcard--blue" href="<?php echo esc_url( rs_page_url( 'setuemerging' ) ); ?>">
				<span class="brandcard__photo"><?php echo rs_photo( 'se_brand_image', 'brand-setuemerging', '', '', '(max-width: 760px) 92vw, 45vw' ); // phpcs:ignore ?></span>
				<span class="brandcard__icon"><?php echo rs_icon( 'compass' ); // phpcs:ignore ?></span>
				<span class="brandcard__name">SetuEmerging</span>
				<span class="brandcard__tag"><?php echo esc_html( rs_mod( 'se_tagline' ) ); ?></span>
				<span class="brandcard__text"><?php echo esc_html( rs_mod( 'se_home_card' ) ); ?></span>
				<ul class="brandcard__list">
					<li><?php esc_html_e( 'Plant & Machinery', 'ramsetu' ); ?></li>
					<li><?php esc_html_e( 'Working Capital', 'ramsetu' ); ?></li>
					<li><?php esc_html_e( 'Hospitality & Education', 'ramsetu' ); ?></li>
				</ul>
				<span class="brandcard__cta"><?php esc_html_e( 'Explore More', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></span>
				<?php get_template_part( 'parts/bridge-art', null, array( 'class' => 'brandcard__art' ) ); ?>
			</a>
		</div>
	</div>
</section>

<?php $why = rs_pairs( 'why_items' ); $special = rs_pairs( 'special_items' ); ?>
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
