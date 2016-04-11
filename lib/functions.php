<?php

/**
 * Returns an array of terms available
 *
 * @since 1.0.0
 *
 * @return array
*/
function it_exchange_invoice_addon_get_available_terms() {

	$terms = array(
		'none'      => array(
					'title'       => __( 'No Terms', 'LION' ),
					'description' => false,
					'seconds'     => false,
					),
		'net-7'  => array(
					'title'       => __( 'Net 7', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due seven days after invoice', 'Description for Net 7 terms', 'LION' ),
					'seconds'     => 604800,
				   ),
		'net-10' => array(
					'title'       => __( 'Net 10', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due ten days after invoice', 'Description for Net 10 terms', 'LION' ),
					'seconds'     => 864000,
				   ),
		'net-30' => array(
					'title'       => __( 'Net 30', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due thirty days after invoice', 'Description for Net 30 terms', 'LION' ),
					'seconds'     => 2592000,
				   ),
		'net-60' => array(
					'title'       => __( 'Net 60', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due sixty days after invoice', 'Description for Net 60 terms', 'LION' ),
					'seconds'     => 5184000,
				   ),
		'net-90' => array(
					'title'       => __( 'Net 90', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due ninety days after invoice', 'Description for Net 10 terms', 'LION' ),
					'seconds'     => 7776000,
				   ),
		'receipt' => array(
					'title'       => __( 'Due on Receipt', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due upon receipt of the invoice', 'Description for Due On Receipt terms', 'LION' ),
					'seconds'     => 0,
				   ),
	);

	return (array) apply_filters( 'it_exchange_invoice_addon_get_available_terms', $terms );
}

/**
 * Is correct hash set for current invoice
 *
 * @since 1.0.0
 *
 * @return boolean
*/
function it_exchange_invoice_addon_is_hash_valid_for_invoice() {

	$hash    = empty( $_GET['client'] ) ? false : $_GET['client'];
	$product = it_exchange_get_product( false );

	if ( empty( $product->ID ) || ! it_exchange_is_page( 'product' ) || 'invoices-product-type' != it_exchange_get_product_type() )
		return false;

	$meta = it_exchange_get_product_feature( $product->ID, 'invoices' );
	if ( empty( $hash) || empty( $meta['hash'] ) || $meta['hash'] !== $hash )
		return false;

	return true;
}

/**
 * Returns the transaction ID associated with an invoice if it exists and if its published
 *
 * @since 1.0.0
 *
 * @param integer $invoice_id the post id of the invoice
 * @return boolean
*/
function it_exchange_invoice_addon_get_invoice_transaction_id( $invoice_id ) {
	$invoice_meta   = it_exchange_get_product_feature( $invoice_id, 'invoices' );
	$transaction_id = empty( $invoice_meta['transaction_id'] ) ? false : $invoice_meta['transaction_id'];
	$transaction_id = 'publish' == get_post_status( $transaction_id ) ? $transaction_id : false;
	return $transaction_id;
}

/**
 * Sends the invoice to the client
 *
 * @since 1.0.0
 *
 * @param integer $post_id the id of the invoice
 *
 * @return boolean
*/
function it_exchange_invoice_addon_send_invoice( $post_id ) {

	$product = it_exchange_get_product( $post_id );
	$meta    = $product->get_feature( 'invoices' );
	
	$customer = it_exchange_get_customer( $meta['client'] );
	
	if ( empty( $customer ) ) {
		return false;
	}

	$additional_emails = empty( $meta['additional_emails'] ) ? array() : explode(',', $meta['additional_emails'] );
	
	$recipient = new IT_Exchange_Email_Recipient_Customer( $customer );
	$notification = it_exchange_email_notifications()->get_notification( 'new-invoice' );

	if ( ! $notification->is_active() ) {
		return false;
	}
	
	$email = new IT_Exchange_Email( $recipient, $notification, array(
		'invoice'   => $product
	) );

	foreach ( $additional_emails as $additional_email ) {
		$email->add_cc( new IT_Exchange_Email_Recipient_Email( trim( $additional_email ) ) );
	}
	
	return it_exchange_send_email( $email );
}

/**
 * Replaces shortcode variables in invoice emails
 *
 * @since 1.0.0
 *
 * @return string
*/
function it_exchange_invoice_addon_parse_shortcode( $atts ) {

	$post_id = empty( $GLOBALS['it_exchange']['invoice-mail-id'] ) ? false : $GLOBALS['it_exchange']['invoice-mail-id']; // Hackity hack
	if ( empty( $post_id ) )
		return '';

	$defaults = array(
		'data' => false,
	);
	$atts = shortcode_atts( $defaults, $atts );

	$meta              = it_exchange_get_product_feature( $post_id, 'invoices' );
	$client_id         = empty( $meta['client'] ) ? 0 : $meta['client'];
	$client            = it_exchange_get_customer( $client_id );
	$exchange_settings = it_exchange_get_option( 'settings-general' );

	$invoice_number    = empty( $meta['number'] ) ? '' : $meta['number'];
	$po_number         = empty( $meta['po'] ) ? '' : $meta['po'];
	$client_name       = empty( $client->data->display_name ) ? '' : $client->data->display_name;
	$client_company    = empty( $meta['company'] ) ? '' : $meta['company'];
	$client_email      = empty( $client->data->user_email ) ? false : $client->data->user_email;
	$client_address    = empty( $meta['address'] ) ? '' : $meta['address'];
	$from_company      = empty( $exchange_settings['company-name'] ) ? get_bloginfo( 'name' ) : $exchange_settings['company-name'];
	$from_email        = empty( $exchange_settings['company-email'] ) ? get_bloginfo( 'admin_email' ) : $exchange_settings['company-email'];
	$from_address      = empty( $exchange_settings['company-address'] ) ? '' : $exchange_settings['company-address'];
	$date_issued       = empty( $meta['date_issued'] ) ? '' : date( get_option( 'date_format' ), $meta['date_issued'] );
	$total_due         = html_entity_decode( it_exchange_format_price( it_exchange_get_product_feature( $post_id, 'base-price' ) ), ENT_COMPAT, 'UTF-8' );
	$terms             = empty( $meta['terms'] ) ? '' : $meta['terms'];
	$available_terms   = it_exchange_invoice_addon_get_available_terms();
	$terms             = empty( $available_terms[$terms]['title'] ) ? '' : $available_terms[$terms]['title'];
	$description       = it_exchange_get_product_feature( $post_id, 'description' );
	$notes             = empty( $meta['notes'] ) ? '' : $meta['notes'];
	$payment_link      = add_query_arg( 'client', $meta['hash'], get_permalink( $post_id ) );
	$username          = empty( $client->data->user_login ) ? '' : $client->data->user_login;

	switch( $atts['data'] ) {
		case 'invoice-number' :
			return $invoice_number;
			break;
		case 'po-number' :
			return $po_number;
			break;
		case 'client-name' :
			return $client_name;
			break;
		case 'client-company' :
			return $client_company;
			break;
		case 'client-address' :
			return $client_address;
			break;
		case 'client-email' :
			return $client_email;
			break;
		case 'from-company' :
			return $from_company;
			break;
		case 'from-email' :
			return $from_email;
			break;
		case 'from-address' :
			return $from_address;
			break;
		case 'date-issued' :
			return $date_issued;
			break;
		case 'total-due' :
			return $total_due;
			break;
		case 'terms' :
			return $terms;
			break;
		case 'description' :
			return $description;
			break;
		case 'notes' :
			return $notes;
			break;
		case 'payment-link' :
			return esc_url_raw( $payment_link );
			break;
		case 'username' :
			return $username;
			break;
		default :
			return '';
	}
}
