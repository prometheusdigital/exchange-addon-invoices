<?php
/**
 * Callback function for add-on settings
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoice_addon_settings_callback() {
	// Store Owners should never arrive here. Add a link just in case the do somehow
	?>
	<div class="wrap">
		<?php ITUtility::screen_icon( 'it-exchange' ); ?>
		<h2><?php _e( 'Invoice Settings', 'LION' ); ?></h2>
		<?php do_action( 'it_exchange_addon_settings_page_top' ); ?>

		<?php
		$options = array(
			'prefix'      => 'invoice-addon',
			'form-fields' => array(
				array(
					'type'    => 'heading',
					'label'   => __( 'Client Email Settings', 'LION' ),
					'slug'    => 'client-email-settings',
				),
				array( 
					'type'    => 'text_box',
					'label'   => __( 'Invoice Email Subject Line', 'LION' ),
					'slug'    => 'client-subject-line',
					'tooltip' => __( 'Subject line of the email that contains the Invoice Details?', 'LION' ),
					'default' => 'Invoice from [it-exchange-invoice-email data="from-company"]',
				),
				array( 
					'type'    => 'text_area',
					'label'   => __( 'Invoice Email Message', 'LION' ),
					'slug'    => 'client-message',
					'tooltip' => __( 'The content of the message', 'LION' ),
					'default' => '
To:
[it-exchange-invoice-email data="client-name"]
[it-exchange-invoice-email data="client-company"]
[it-exchange-invoice-email data="client-email"]

From:
[it-exchange-invoice-email data="from-name"]
[it-exchange-invoice-email data="from-company"]
[it-exchange-invoice-email data="from-email"]
[it-exchange-invoice-email data="from-address"]

Date Issued: [it-exchange-invoice-email data="date-issued"]
Total Due: [it-exchange-invoice-email data="total-due"]
Terms: [it-exchange-invoice-email data="terms"]

Description:
[it-exchange-invoice-email data="description"]

Additional Notes:
[it-exchange-invoice-email data="notes"]

Thank you for your prompt payment:
[it-exchange-invoice-email data="payment-link"]
					',
					'after'   => 'test',
				),
			),
		);
		it_exchange_print_admin_settings_form( $options );
		?>
	</div>
	<?php
}
