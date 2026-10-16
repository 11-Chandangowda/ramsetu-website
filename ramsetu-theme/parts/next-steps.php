<?php
/**
 * "How it works" + call-to-action, used at the end of product pages.
 */
defined( 'ABSPATH' ) || exit;
$steps = array(
	array( 'file', __( 'Apply online', 'ramsetu' ), __( 'Share a few basic details — it takes two minutes.', 'ramsetu' ) ),
	array( 'phone', __( 'We call you', 'ramsetu' ), __( 'A RamSetu officer understands your need and documents.', 'ramsetu' ) ),
	array( 'users', __( 'Matched with a lender', 'ramsetu' ), __( 'We connect you with a suitable bank or NBFC partner.', 'ramsetu' ) ),
	array( 'rupee', __( 'Approval & disbursement', 'ramsetu' ), __( 'The lender assesses and approves your loan, and we guide you through it.', 'ramsetu' ) ),
);
?>
<section class="section">
	<div class="container">
		<div class="section__head">
			<p class="eyebrow"><?php esc_html_e( 'How it works', 'ramsetu' ); ?></p>
			<h2 class="h2"><?php esc_html_e( 'From application to disbursement', 'ramsetu' ); ?></h2>
		</div>
		<ol class="steps">
			<?php foreach ( $steps as $n => $s ) : ?>
				<li class="steps__item">
					<span class="steps__n"><?php echo (int) $n + 1; ?></span>
					<?php echo rs_icon( $s[0] ); // phpcs:ignore ?>
					<h3><?php echo esc_html( $s[1] ); ?></h3>
					<p><?php echo esc_html( $s[2] ); ?></p>
				</li>
			<?php endforeach; ?>
		</ol>
		<p class="center"><a class="btn btn--primary btn--lg" <?php echo rs_apply_attrs(); // phpcs:ignore ?>><?php esc_html_e( 'Apply Now', 'ramsetu' ); ?> <?php echo rs_icon( 'arrow' ); // phpcs:ignore ?></a></p>
	</div>
</section>
