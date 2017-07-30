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

		$exchangewp_invoice_options = get_option( 'it-storage-exchange_invoice-addon' );
		$license = trim( $exchangewp_invoice_options['invoice-license-key'] );
		// var_dump($license);
		$exstatus = trim( get_option( 'exchange_invoice_license_status' ) );
		//  var_dump($exstatus);

		$after_license = wp_nonce_field( 'exchange_invoice_nonce', 'exchange_invoice_nonce' );

		if( $exstatus !== false && $exstatus == 'valid' ) {

			$after_license .= '<span style="color:green;">active</span>';
			$after_license .= '<input type="submit" class="button-secondary" name="exchange_invoice_license_deactivate" value="Deactivate License"/>';
		} else {
			$after_license .= '<input type="submit" class="button-secondary" name="exchange_invoice_license_activate" value="Activate License"/>';
		}

		$options = array(
			'prefix'      => 'invoice-addon',
			'form-fields' => array(
				array(
					'type'	=> 'heading',
					'label' => __('License Key', 'LION' ),
					'slug' => 'invoice-license-key-heading',
				),
				array(
					'type' => 'text_box',
					'label' => __('Enter License Key', 'LION'),
					'slug' => 'invoice-license-key',
					'after' => $after_license,
				),
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

function exchange_invoice_license_activate() {

	if( isset( $_POST['exchange_invoice_license_activate'] ) ) {

			// run a quick security check
		 	if( ! check_admin_referer( 'exchange_invoice_nonce', 'exchange_invoice_nonce' ) )
				return; // get out if we didn't click the Activate button

			// retrieve the license from the database
			// $license = trim( get_option( 'exchange_invoice_license_key' ) );
	   $exchangewp_invoice_options = get_option( 'it-storage-exchange_invoice-addon' );
	   $license = trim( $exchangewp_invoice_options['invoice-license-key'] );

			// 	var_dump($license);
			// data to send in our API request
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_name'  => urlencode( 'invoices' ), // the name of our product in EDD
				'url'        => home_url()
			);

			// Call the custom API.
			$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

				if ( is_wp_error( $response ) ) {
					$message = $response->get_error_message();
				} else {
					$message = __( 'An error occurred, please try again.' );
				}

			} else {

				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				if ( false === $license_data->success ) {

					switch( $license_data->error ) {

						case 'expired' :

							$message = sprintf(
								__( 'Your license key expired on %s.' ),
								date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
							);
							break;

						case 'revoked' :

							$message = __( 'Your license key has been disabled.' );
							break;

						case 'missing' :

							$message = __( 'Invalid license.' );
							break;

						case 'invalid' :
						case 'site_inactive' :

							$message = __( 'Your license is not active for this URL.' );
							break;

						case 'item_name_mismatch' :

							$message = sprintf( __( 'This appears to be an invalid license key for %s.' ), 'invoice' );
							break;

						case 'no_activations_left':

							$message = __( 'Your license key has reached its activation limit.' );
							break;

						default :

							$message = __( 'An error occurred, please try again.' );
							break;
					}

				}

			}

			// Check if anything passed on a message constituting a failure
			if ( ! empty( $message ) ) {
				$base_url = admin_url( 'admin.php?page=' . 'invoice-product-type' );
				$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

				wp_redirect( $redirect );
				exit();
			}

			//$license_data->license will be either "valid" or "invalid"
			update_option( 'exchange_invoice_license_status', $license_data->license );
			wp_redirect( admin_url( 'admin.php?page=it-exchange-addons&add-on-settings=invoices-product-type' ) );
			exit();
		}

}
add_action('admin_init', 'exchange_invoice_license_deactivate');
add_action('admin_init', 'exchange_invoice_license_activate');

function exchange_invoice_license_deactivate() {

	 // deactivate here
	 // listen for our activate button to be clicked
		if( isset( $_POST['exchange_invoice_license_deactivate'] ) ) {

			// run a quick security check
		 	if( ! check_admin_referer( 'exchange_invoice_nonce', 'exchange_invoice_nonce' ) )
				return; // get out if we didn't click the Activate button

			// retrieve the license from the database
			// $license = trim( get_option( 'exchange_invoice_license_key' ) );

			$exchangewp_invoice_options = get_option( 'it-storage-exchange_invoice-addon' );
 	    $license = trim( $exchangewp_invoice_options['invoice-license-key'] );



			// data to send in our API request
			$api_params = array(
				'edd_action' => 'deactivate_license',
				'license'    => $license,
				'item_name'  => urlencode( 'invoices' ), // the name of our product in EDD
				'url'        => home_url()
			);
			// Call the custom API.
			$response = wp_remote_post( 'https://exchangewp.com', array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );

			// make sure the response came back okay
			if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

				if ( is_wp_error( $response ) ) {
					$message = $response->get_error_message();
				} else {
					$message = __( 'An error occurred, please try again.' );
				}

				// $base_url = admin_url( 'admin.php?page=' . 'invoice-license' );
				// $redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

				wp_redirect( 'admin.php?page=it-exchange-addons&add-on-settings=invoices-product-type' );
				exit();
			}

			// decode the license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
			// $license_data->license will be either "deactivated" or "failed"
			if( $license_data->license == 'deactivated' ) {
				delete_option( 'exchange_invoice_license_status' );
			}

			wp_redirect( admin_url( 'admin.php?page=it-exchange-addons&add-on-settings=invoices-product-type' ) );
			exit();

		}
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
