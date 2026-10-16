<?php
/**
 * Address / contact person / phone / email list for one branch.
 * $args['b']: branch array.
 */
defined( 'ABSPATH' ) || exit;
$b = $args['b'];
?>
<ul class="branch__info">
	<?php if ( ! empty( $b['address'] ) ) : ?><li><?php echo rs_icon( 'pin' ); // phpcs:ignore ?><span><?php echo nl2br( esc_html( $b['address'] ) ); ?></span></li><?php endif; ?>
	<?php if ( ! empty( $b['contact'] ) ) : ?><li><?php echo rs_icon( 'users' ); // phpcs:ignore ?><span><?php echo esc_html( $b['contact'] ); ?></span></li><?php endif; ?>
	<?php if ( ! empty( $b['phone'] ) ) : ?><li><?php echo rs_icon( 'phone' ); // phpcs:ignore ?><a href="<?php echo esc_attr( rs_tel( $b['phone'] ) ); ?>"><?php echo esc_html( $b['phone'] ); ?></a></li><?php endif; ?>
	<?php if ( ! empty( $b['email'] ) ) : ?><li><?php echo rs_icon( 'mail' ); // phpcs:ignore ?><a href="mailto:<?php echo esc_attr( $b['email'] ); ?>"><?php echo esc_html( $b['email'] ); ?></a></li><?php endif; ?>
</ul>
