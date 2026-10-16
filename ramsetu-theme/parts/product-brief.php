<?php
/**
 * Product page (Round 2 layout):
 *   1. Heading in bold on the left, brief text on the right.
 *   2. Three columns of boxes: Loans For | Loans Against Collateral of | Key Features.
 * $args: prefix ('ra' | 'se'), tone ('orange' | 'blue'),
 *        loans_for: [ heading, mod key ].
 */
defined( 'ABSPATH' ) || exit;
$prefix    = $args['prefix'];
$tone      = $args['tone'] ?? 'orange';
$loans_for = $args['loans_for'] ?? array( __( 'Loans For', 'ramsetu' ), $prefix . '_loans_for' );
$icons     = array( 'rupee', 'calendar', 'spark', 'door', 'shield', 'users', 'store', 'home' );

$columns = array(
	array( 'loans-for', $loans_for[0], rs_lines( $loans_for[1] ), 'users' ),
	array( 'collateral', rs_mod( 'collateral_title' ), rs_lines( 'collateral_items' ), 'home' ),
	array( 'features', __( 'Key Features', 'ramsetu' ), rs_lines( $prefix . '_features' ), '' ),
);
$columns = array_values( array_filter( $columns, function ( $c ) {
	return (bool) $c[2];
} ) );
?>
<section class="section product-intro">
	<div class="container brief-row">
		<h2 class="brief-row__title"><?php echo esc_html( rs_mod( $prefix . '_brief_title' ) ); ?></h2>
		<div class="brief-row__text">
			<?php rs_the_paras( $prefix . '_brief', 'lead' ); ?>
		</div>
	</div>

	<?php if ( $columns ) : ?>
		<div class="container product-cols product-cols--<?php echo (int) count( $columns ); ?>">
			<?php foreach ( $columns as $col ) : ?>
				<div class="product-col" id="<?php echo esc_attr( $col[0] ); ?>">
					<h3 class="product-col__h"><?php echo esc_html( $col[1] ); ?></h3>
					<ul class="feature-grid feature-grid--<?php echo esc_attr( $tone ); ?> feature-grid--stack">
						<?php foreach ( $col[2] as $k => $item ) : ?>
							<li class="feature-grid__item">
								<span class="feature-grid__icon"><?php echo rs_icon( $col[3] ? $col[3] : $icons[ $k % count( $icons ) ] ); // phpcs:ignore ?></span>
								<span><?php echo esc_html( $item ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
