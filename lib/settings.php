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
		$after  = '<br /><strong>Possible data keys</strong><ul>';
		$after .= '<li><em>invoice-number</em> - ' . __( 'The invoice number', 'LION' ) . '</li>';
		$after .= '<li><em>po-number</em> - ' . __( 'The PO number of the invoice', 'LION' ) . '</li>';
		$after .= '<li><em>client-name</em> - ' . __( 'The WordPress display_name for the user associated with the invoice', 'LION' ) . '</li>';
		$after .= '<li><em>client-company</em> - ' . __( 'The Client Company field for the current invoice', 'LION' ) . '</li>';
		$after .= '<li><em>client-address</em> - ' . __( 'The Client Address field for the current invoice', 'LION' ) . '</li>';
		$after .= '<li><em>client-email</em> - ' . __( 'The WordPress user_email for the user associated with the invoice', 'LION' ) . '</li>';
		$after .= '<li><em>client-username</em> - ' . __( 'The username your client needs to log in to your site.', 'LION' ) . '</li>';
		$after .= '<li><em>from-company</em> - ' . __( 'The company name in Exchange settings', 'LION' ) . '</li>';
		$after .= '<li><em>from-email</em> - ' . __( 'The company email in Exchange settings', 'LION' ) . '</li>';
		$after .= '<li><em>from-address</em> - ' . __( 'The company address in Exchange settings', 'LION' ) . '</li>';
		$after .= '<li><em>date-issued</em> - ' . __( 'The Date Issued field for the current invoice', 'LION' ) . '</li>';
		$after .= '<li><em>total-due</em> - ' . __( 'The Total Due field for the current invoice', 'LION' ) . '</li>';
		$after .= '<li><em>terms</em> - ' . __( 'The Terms field for the current invoice', 'LION' ) . '</li>';
		$after .= '<li><em>description</em> - ' . __( 'The Description field for the current invoice', 'LION' ) . '</li>';
		$after .= '<li><em>notes</em> - ' . __( 'The Notes field for the current invoice', 'LION' ) . '</li>';
		$after .= '<li><em>payment-link</em> - ' . __( 'The Unique link with client hash the current invoice', 'LION' ) . '</li>';
		$after .= '</ul>';

		$defaults = it_exchange_invoice_addon_get_default_settings();

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
					'default' => $defaults['client-subject-line'],
					'options' => array( 'class' => 'large-text', ),
				),
				array(
					'type'    => 'text_area',
					'label'   => __( 'Invoice Email Message', 'LION' ),
					'slug'    => 'client-message',
					'tooltip' => __( 'The content of the message', 'LION' ),
					'options' => array( 'class' => 'large-text', 'rows' => 10 ),
					'default' => $defaults['client-message'],
					'after'   => $after,
				),
			),
		);
		it_exchange_print_admin_settings_form( $options );
		?>
	</div>
	<?php
}

/**
 * Default Form Settings
 *
 * @since 1.0.0
 *
 * @return array
*/
function it_exchange_invoice_addon_get_default_settings() {
	$default_settings = array(
		'client-subject-line' => __( 'Invoice from [it-exchange-invoice-email data="from-company"]', 'LION' ),
		'client-message'      => '
Hi [it-exchange-invoice-email data="client-name"],
[it-exchange-invoice-email data="from-company"] has sent you an invoice for [it-exchange-invoice-email data="total-due"].
Please review and pay here: [it-exchange-invoice-email data="payment-link"]

Thank you,
[it-exchange-invoice-email data="from-company"],
[it-exchange-invoice-email data="from-email"]
',
	);
	return $default_settings;
}

/**
 * Set default settings if empty
 *
 * @since 1.0.0
 *
 * @return
*/
function it_exchange_invoice_addon_set_default_options() {
	$defaults = it_exchange_invoice_addon_get_default_settings();
	$current  = it_exchange_get_option( 'invoice-addon' );

	if ( empty( $current ) ) {
		it_exchange_save_option( 'invoice-addon', $defaults );
	}
}
