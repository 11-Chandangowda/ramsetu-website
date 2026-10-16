<?php
/**
 * Partner logos slider. Hidden for visitors until at least one partner is added.
 */
defined( 'ABSPATH' ) || exit;

$partners = rs_partners();
if ( ! $partners && ! rs_show_placeholders() ) {
	return;
}
$id = $args['id'] ?? 'partners';
?>
<section class="section partners" id="<?php echo esc_attr( $id ); ?>" aria-labelledby="<?php echo esc_attr( $id ); ?>-h">
	<div class="container">
		<div class="section__head section__head--row">
			<div>
				<p class="eyebrow"><?php esc_html_e( 'Our Partners', 'ramsetu' ); ?></p>
				<h2 class="h2" id="<?php echo esc_attr( $id ); ?>-h"><?php esc_html_e( 'Backed by trusted lenders', 'ramsetu' ); ?></h2>
			</div>
		</div>
		<?php if ( ! $partners ) : ?>
			<?php rs_admin_hint( __( 'This section is hidden from visitors until you add partner logos under Lending Partners → Add Partner (logo = Featured Image).', 'ramsetu' ) ); ?>
		<?php else : ?>
			<div class="marquee<?php echo count( $partners ) < 5 ? ' marquee--static' : ''; ?>">
				<ul class="marquee__track">
					<?php
					$loops = count( $partners ) < 5 ? 1 : 2; // duplicate list for seamless scroll
					for ( $l = 0; $l < $loops; $l++ ) :
						foreach ( $partners as $p ) :
							$url = get_post_meta( $p->ID, '_rs_url', true );
							?>
							<li class="marquee__item"<?php echo $l ? ' aria-hidden="true"' : ''; ?>>
								<?php if ( $url ) : ?><a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener"<?php echo $l ? ' tabindex="-1"' : ''; ?>><?php endif; ?>
								<?php if ( has_post_thumbnail( $p ) ) : ?>
									<?php echo get_the_post_thumbnail( $p, 'medium', array( 'alt' => esc_attr( $p->post_title ) ) ); ?>
								<?php else : ?>
									<span class="marquee__name"><?php echo esc_html( $p->post_title ); ?></span>
								<?php endif; ?>
								<?php if ( $url ) : ?></a><?php endif; ?>
							</li>
							<?php
						endforeach;
					endfor;
					?>
				</ul>
			</div>
		<?php endif; ?>
	</div>
</section>
