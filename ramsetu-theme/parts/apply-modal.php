<?php defined( 'ABSPATH' ) || exit; ?>
<dialog class="apply-modal" id="apply-modal" aria-labelledby="apply-modal-title" data-apply-modal>
	<div class="apply-modal__panel">
		<header class="apply-modal__head">
			<div>
				<h2 id="apply-modal-title"><?php esc_html_e( 'Apply for Loan', 'ramsetu' ); ?></h2>
				<p><?php esc_html_e( 'Fill in your details and our team will get in touch with you at the earliest.', 'ramsetu' ); ?> <span class="muted"><?php esc_html_e( '* denotes mandatory fields', 'ramsetu' ); ?></span></p>
			</div>
			<button type="button" class="apply-modal__close" data-apply-close aria-label="<?php esc_attr_e( 'Close', 'ramsetu' ); ?>"><?php echo rs_icon( 'close' ); // phpcs:ignore ?></button>
		</header>
		<div class="apply-modal__body">
			<?php
			if ( rs_has_leads() ) {
				echo do_shortcode( '[ramsetu_apply_form id="rsl-apply-modal"]' );
			} else {
				rs_admin_hint( __( 'Activate the RamSetu Leads plugin to show the form.', 'ramsetu' ) );
			}
			?>
		</div>
	</div>
</dialog>
