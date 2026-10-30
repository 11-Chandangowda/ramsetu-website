<?php
/**
 * Template Name: About Us
 */
defined( 'ABSPATH' ) || exit;
get_header();

rs_page_hero( __( 'About Us', 'ramsetu' ), rs_mod( 'about_hero' ), '', 'green', 'about' );
?>

<section class="section">
	<div class="container about-intro">
		<?php
		$paras = rs_paras( 'about_intro' );
		foreach ( $paras as $k => $p ) {
			printf( '<p class="%s">%s</p>', 0 === $k ? 'lead lead--xl' : 'lead', esc_html( $p ) );
		}
		?>
		<p class="disclaimer"><?php echo esc_html( rs_mod( 'loans_disclaimer' ) ); ?></p>
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
</section>

<section class="section section--tint" id="vision">
	<div class="container vm">
		<div class="vm__card vm__card--orange">
			<?php echo rs_icon( 'spark' ); // phpcs:ignore ?>
			<h2 class="h3"><?php esc_html_e( 'Our Vision', 'ramsetu' ); ?></h2>
			<p><?php echo esc_html( rs_mod( 'about_vision' ) ); ?></p>
		</div>
		<div class="vm__card vm__card--blue" id="mission">
			<?php echo rs_icon( 'compass' ); // phpcs:ignore ?>
			<h2 class="h3"><?php esc_html_e( 'Our Mission', 'ramsetu' ); ?></h2>
			<p><?php echo esc_html( rs_mod( 'about_mission' ) ); ?></p>
		</div>
	</div>
</section>

<section class="section" id="leadership">
	<div class="container">
		<div class="section__head">
			<p class="eyebrow"><?php esc_html_e( 'Our Leadership', 'ramsetu' ); ?></p>
			<h2 class="h2"><?php esc_html_e( 'The people behind RamSetu', 'ramsetu' ); ?></h2>
		</div>
		<?php $leaders = rs_team( 'leadership' ); ?>
		<?php if ( $leaders ) : ?>
			<div class="leaders">
				<?php foreach ( $leaders as $p ) : ?>
					<article class="leader">
						<?php rs_person( $p, 'lg' ); ?>
						<?php if ( trim( $p->post_content ) && false === stripos( $p->post_content, 'to be added' ) ) : ?>
							<div class="leader__bio"><?php echo wp_kses_post( wpautop( $p->post_content ) ); ?></div>
						<?php elseif ( 0 === array_search( $p, $leaders, true ) ) : ?>
							<div class="leader__bio"><?php rs_the_paras( 'home_founder_message' ); ?></div>
						<?php endif; ?>
						<?php $li = get_post_meta( $p->ID, '_rs_linkedin', true ); ?>
						<?php if ( $li ) : ?><a class="leader__li" href="<?php echo esc_url( $li ); ?>" target="_blank" rel="noopener"><?php echo rs_icon( 'linkedin' ); // phpcs:ignore ?> LinkedIn</a><?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<?php rs_admin_hint( __( 'Add people under Team & Advisors, in the "Leadership Team" group.', 'ramsetu' ) ); ?>
		<?php endif; ?>

		<?php $advisors = rs_team( 'advisor' ); ?>
		<?php if ( $advisors || rs_show_placeholders() ) : ?>
			<h3 class="h3 subhead" id="advisors"><?php esc_html_e( 'Our Advisors', 'ramsetu' ); ?></h3>
			<?php if ( $advisors ) : ?>
				<div class="leaders leaders--advisors">
					<?php foreach ( $advisors as $p ) : ?>
						<article class="leader leader--advisor">
							<?php rs_person( $p, 'md' ); ?>
							<?php if ( trim( $p->post_content ) ) : ?>
								<div class="leader__bio"><?php echo wp_kses_post( wpautop( $p->post_content ) ); ?></div>
							<?php endif; ?>
						</article>
					<?php endforeach; ?>
				</div>
			<?php else : ?>
				<?php rs_admin_hint( __( 'Hidden from visitors until you add people under Team & Advisors in the "Advisors" group.', 'ramsetu' ) ); ?>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</section>

<?php $supporters = rs_team( 'supporter' ); ?>
<?php if ( $supporters ) : ?>
<section class="section section--tint" id="supporters">
	<div class="container">
		<div class="section__head">
			<p class="eyebrow"><?php esc_html_e( 'With gratitude', 'ramsetu' ); ?></p>
			<h2 class="h2"><?php esc_html_e( 'Our Steadfast Supporters & Partners', 'ramsetu' ); ?></h2>
		</div>
		<div class="supporters">
			<?php foreach ( $supporters as $p ) : ?>
				<?php $category = get_post_meta( $p->ID, '_rs_designation', true ); ?>
				<article class="supporter">
					<?php if ( $category ) : ?><h3 class="supporter__category"><?php echo esc_html( $category ); ?></h3><?php endif; ?>
					<div class="leader leader--advisor leader--supporter">
						<div class="person person--md">
							<div class="person__photo">
								<?php if ( has_post_thumbnail( $p ) ) : ?>
									<?php echo get_the_post_thumbnail( $p, 'rs-portrait', array( 'alt' => esc_attr( $p->post_title ) ) ); ?>
								<?php else : ?>
									<span class="person__initials" aria-hidden="true"><?php echo esc_html( rs_initials( $p->post_title ) ); ?></span>
								<?php endif; ?>
							</div>
							<div class="person__body"><h4 class="person__name"><?php echo esc_html( $p->post_title ); ?></h4></div>
						</div>
						<?php if ( trim( $p->post_content ) ) : ?>
							<div class="leader__bio"><?php echo wp_kses_post( wpautop( $p->post_content ) ); ?></div>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	</div>
</section>
<?php endif; ?>

<?php $team = apply_filters( 'rs_show_team', false ) ? rs_team( 'team' ) : array(); // Meet RamSetu Team hidden for now (25 Oct). ?>
<?php if ( $team ) : ?>
<section class="section" id="team">
	<div class="container">
		<div class="section__head">
			<p class="eyebrow"><?php esc_html_e( 'Our People', 'ramsetu' ); ?></p>
			<h2 class="h2"><?php esc_html_e( 'Meet RamSetu Team', 'ramsetu' ); ?></h2>
		</div>
		<ul class="team-grid">
			<?php foreach ( $team as $p ) : ?>
				<li class="team-card">
					<div class="team-card__photo">
						<?php if ( has_post_thumbnail( $p ) ) : ?>
							<?php echo get_the_post_thumbnail( $p, 'rs-portrait', array( 'alt' => esc_attr( $p->post_title ) ) ); ?>
						<?php else : ?>
							<span class="person__initials" aria-hidden="true"><?php echo esc_html( rs_initials( $p->post_title ) ); ?></span>
						<?php endif; ?>
					</div>
					<h3 class="team-card__name"><?php echo esc_html( $p->post_title ); ?></h3>
					<?php $role = get_post_meta( $p->ID, '_rs_designation', true ); ?>
					<?php if ( $role ) : ?><p class="team-card__role"><?php echo esc_html( $role ); ?></p><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</div>
</section>
<?php endif; ?>

<?php get_template_part( 'parts/partners', null, array( 'id' => 'partners' ) ); ?>

<section class="section section--tint" id="presence">
	<div class="container split split--even">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'Our Presence', 'ramsetu' ); ?></p>
			<h2 class="h2"><?php esc_html_e( 'Growing with emerging markets', 'ramsetu' ); ?></h2>
			<p class="lead"><?php echo esc_html( rs_mod( 'about_presence' ) ); ?></p>
			<a class="link-arrow" href="<?php echo esc_url( rs_page_url( 'branches' ) ); ?>"><?php esc_html_e( 'Find your nearest branch', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a>
		</div>
		<div class="roadmap">
			<div class="roadmap__group">
				<h3 class="roadmap__h"><span class="dot dot--live"></span><?php esc_html_e( 'Live today', 'ramsetu' ); ?></h3>
				<ul class="chips">
					<?php foreach ( rs_list( 'about_live' ) as $c ) : ?>
						<li class="chip chip--live"><?php echo rs_icon( 'pin' ); // phpcs:ignore ?><?php echo esc_html( $c ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="roadmap__group">
				<h3 class="roadmap__h"><span class="dot dot--soon"></span><?php esc_html_e( 'Coming soon', 'ramsetu' ); ?></h3>
				<ul class="chips">
					<?php foreach ( rs_list( 'about_upcoming' ) as $c ) : ?>
						<li class="chip"><?php echo esc_html( $c ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</div>
</section>

<?php $phases = rs_pairs( 'roadmap' ); ?>
<?php if ( $phases ) : ?>
<section class="section" id="roadmap">
	<div class="container">
		<div class="section__head">
			<p class="eyebrow"><?php esc_html_e( 'Future Roadmap', 'ramsetu' ); ?></p>
			<h2 class="h2"><?php esc_html_e( 'Where we are headed', 'ramsetu' ); ?></h2>
			<p class="lead"><?php echo esc_html( rs_mod( 'roadmap_intro' ) ); ?></p>
		</div>
		<ol class="timeline">
			<?php foreach ( $phases as $k => $ph ) : ?>
				<?php
				$parts = array_map( 'trim', preg_split( '/\s[—–-]\s/u', $ph[0], 2 ) );
				?>
				<li class="timeline__item<?php echo 0 === $k ? ' is-current' : ''; ?>">
					<span class="timeline__phase"><?php echo esc_html( $parts[0] ); ?></span>
					<h3 class="timeline__title"><?php echo esc_html( $parts[1] ?? $parts[0] ); ?></h3>
					<p><?php echo esc_html( $ph[1] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
	</div>
</section>
<?php endif; ?>

<?php
get_footer();
