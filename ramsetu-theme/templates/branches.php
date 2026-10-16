<?php
/**
 * Template Name: Branch Locator
 */
defined( 'ABSPATH' ) || exit;
get_header();

$branches = rs_branches();
$states   = array();
foreach ( $branches as $b ) {
	$states[ $b['state'] ][] = $b['city'];
}
ksort( $states );

rs_page_hero( __( 'Branch Locator', 'ramsetu' ), __( 'Locate a branch near you', 'ramsetu' ), '', 'orange', 'branches' );
?>

<section class="section">
	<div class="container">
		<div class="locator" data-locator>
			<div class="locator__bar">
				<p class="locator__hours"><?php echo rs_icon( 'clock' ); // phpcs:ignore ?><span><strong><?php esc_html_e( 'Branch walk-in hours:', 'ramsetu' ); ?></strong> <?php echo esc_html( rs_walkin_hours() ); ?></span></p>
				<div class="locator__filters">
					<label class="field">
						<span><?php esc_html_e( 'State', 'ramsetu' ); ?></span>
						<select data-loc-state>
							<option value=""><?php esc_html_e( 'All states', 'ramsetu' ); ?></option>
							<?php foreach ( array_keys( $states ) as $s ) : ?>
								<option value="<?php echo esc_attr( $s ); ?>"><?php echo esc_html( $s ); ?></option>
							<?php endforeach; ?>
						</select>
					</label>
					<label class="field">
						<span><?php esc_html_e( 'City', 'ramsetu' ); ?></span>
						<select data-loc-city disabled>
							<option value=""><?php esc_html_e( 'Select state first', 'ramsetu' ); ?></option>
						</select>
					</label>
				</div>
			</div>

			<p class="locator__status" role="status" aria-live="polite" data-loc-status>
				<?php
				/* translators: %d: number of branches */
				echo esc_html( sprintf( _n( 'Showing %d branch', 'Showing all %d branches', count( $branches ), 'ramsetu' ), count( $branches ) ) );
				?>
			</p>

			<div class="locator__body">
				<div class="branches">
					<?php foreach ( $branches as $b ) : ?>
						<article class="branch" data-state="<?php echo esc_attr( $b['state'] ); ?>" data-city="<?php echo esc_attr( $b['city'] ); ?>" data-map-q="<?php echo esc_attr( rs_branch_map_query( $b ) ); ?>">
							<p class="branch__place"><?php echo esc_html( $b['city'] . ', ' . $b['state'] ); ?></p>
							<h2 class="branch__name"><?php echo esc_html( $b['name'] ? $b['name'] : $b['city'] ); ?></h2>
							<?php get_template_part( 'parts/branch-info', null, array( 'b' => $b ) ); ?>
							<div class="branch__actions">
								<button type="button" class="link-arrow branch__show" data-show-map><?php esc_html_e( 'Show on map', 'ramsetu' ); ?></button>
								<?php if ( ! empty( $b['map'] ) ) : ?>
									<a class="link-arrow" href="<?php echo esc_url( $b['map'] ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Get directions', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a>
								<?php endif; ?>
							</div>
						</article>
					<?php endforeach; ?>
				</div>
				<?php if ( $branches ) : ?>
					<div class="locator__map">
						<iframe data-loc-map title="<?php esc_attr_e( 'Branch map', 'ramsetu' ); ?>" src="<?php echo esc_url( rs_map_embed_url( rs_branch_map_query( $branches[0] ) ) ); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
					</div>
				<?php endif; ?>
			</div>
			<?php if ( ! $branches ) { rs_admin_hint( __( 'Add branches under RamSetu Leads → Branches.', 'ramsetu' ) ); } ?>
		</div>
	</div>
</section>

<section class="section section--tint" id="callback">
	<div class="container split split--form">
		<div>
			<p class="eyebrow"><?php esc_html_e( 'Doorstep service', 'ramsetu' ); ?></p>
			<h2 class="h2"><?php echo esc_html( rs_mod( 'doorstep_title' ) ); ?></h2>
			<p class="lead"><?php echo esc_html( rs_mod( 'doorstep_text' ) ); ?></p>
			<div class="doorstep-art"><?php echo rs_icon( 'door' ); // phpcs:ignore ?></div>
		</div>
		<div class="form-card">
			<h3 class="h3"><?php esc_html_e( 'Request a Call Back', 'ramsetu' ); ?></h3>
			<?php echo rs_has_leads() ? do_shortcode( '[ramsetu_callback_form id="rsl-callback"]' ) : ''; // phpcs:ignore ?>
		</div>
	</div>
</section>

<?php
get_footer();
