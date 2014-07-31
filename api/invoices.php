<?php 
/**
 * API functions for invoices
*/

/**
 * Get all Invoices for a specified user
 *
 * @since CHANGEME
 *
 * @param mixed $customer
 * @param array $opitons
 * @return array
*/
function it_exchange_invoices_addon_get_invoices_for_customer( $customer=false, $options=false ) {

	// Determine the customer
	if ( ! is_object( $customer ) || 'IT_Exchange_Customer' != get_class( $customer ) ) {
		$customer = empty ( $customer ) ? it_exchange_get_current_customer() : it_exchange_get_customer( $customer );
	}

	// Return empty if no customer was found
	if ( empty( $customer ) ) {
		return array();
	}
	
	// Parse options
	$defaults = array(
		'invoice_status'  => 'any',
	);
	$options = wp_parse_args( $options, $defaults );

	// Add customer to options
	$options['customer'] = $customer->id;

	// Grab requested invoices
	$invoices = it_exchange_invoices_addon_get_invoices( $options );

	return $invoices;
};

/**
 * Grabs invoices
 *
 * @since CHANGEME
 *
 * @param array $options
 * @return array
*/
function it_exchange_invoices_addon_get_invoices( $options=array() ) {
	$defaults = array(
		'invoice_status' => 'any',
		'customer'       => false,
		'post_status'    => 'publish',
	);

	$options = wp_parse_args( $options, $defaults );

	$options['product_type'] = 'invoices-product-type';
	$options['show_hidden']  = true;

	$invoices = it_exchange_get_products( $options );

	foreach( (array) $invoices as $key => $invoice ) {
		// Grab the data
		$data = it_exchange_get_product_feature( $invoice->ID, 'invoices' );

		// Unset any invoices not for this user
		if ( empty( $data['client'] ) || $data['client'] != $options['customer'] ) {
			unset( $invoices[$key] );
		}

		// Unset any invoices not matching the requested status
		$status = it_exchange_invoices_addon_get_invoice_status( $invoice->ID );
		if ( $options['invoice_status'] != 'any'  ) {
			if ( is_array( $options['invoice_status'] ) ) {
				if ( ! in_array( $status, $options['invoice_status'] ) ) {
					unset( $invoices[$key] );
				}
			} else { 
				if ( $status != $options['invoice_status'] ) {
					unset( $invoices[$key] );
				}
			}
		}
	}
	$invoices = array_values( $invoices );
	return $invoices;
}

/**
 * Grab a given invoice's status
 *
 * @since CHANGEME
 *
 * @param  integer $invoice_id  the wp post id for the invoice
 * @return string
*/
function it_exchange_invoices_addon_get_invoice_status( $invoice_id ) {

	// Get transaction ID
	$transaction_id = it_exchange_invoice_addon_get_invoice_transaction_id( $invoice_id );

	// Get invoice data
	$data = it_exchange_get_product_feature( $invoice_id, 'invoices' );

	// Set status if no transaction
	if ( empty( $transaction_id ) ) { 

		// When was the invoice issued?
		$date_issued = it_exchange_get_date_issued_for_invoice( $invoice_id );

		// Get array of term data
		$terms       = it_exchange_invoice_addon_get_available_terms();

		// Get the length of the term for this invoice in seconds
		$term_time   = empty( $terms[$data['terms']]['seconds'] ) ? 0 : $terms[$data['terms']]['seconds'];

		// Initially set status as unpaid or late based on date issued and length of term
		$status      = ( ( $date_issued + $term_time ) > time() ) ? 'unpaid' : 'late';

		// IF the term is missing or set to receipt, set as due now.
		$status      = ( 'none' == $data['terms'] || 'receipt' == $data['terms'] ) ? 'due-now' : $status;
	} else {
		// If we have a transaction, set it as paid or pending
		$status = it_exchange_transaction_is_cleared_for_delivery( $transaction_id ) ? 'paid' : 'pending';
	}   

	return $status;
}

/**
 * Get the due date for the invoice.
 *
 * Return the issued date if the terms are missing or due on receipt
 *
 * @since CHANGEME
 *
 * @param  integer $invoice_id
 * @return string
*/
function it_exchange_get_invoice_due_date( $invoice_id ) {
	// Get invoice data
	$data = it_exchange_get_product_feature( $invoice_id, 'invoices' );

	// Get array of term data
	$terms       = it_exchange_invoice_addon_get_available_terms();

	// If invoice term was empty or due on receipt, return creation date
	if ( empty( $terms[$data['terms']]['seconds'] ) )
		return it_exchange_get_date_issued_for_invoice( $invoice_id );

	// When was the invoice issued?
	$date_issued = it_exchange_get_date_issued_for_invoice( $invoice_id );

	// Get the length of the term for this invoice in seconds
	$term_time   = empty( $terms[$data['terms']]['seconds'] ) ? 0 : $terms[$data['terms']]['seconds'];

	// Return the issue date + the lenght in time for the terms
	return $date_issued + $term_time;
}

/**
 * Get date an invoice was issued
 *
 * @since CHANGEME
 *
 * @param  integer $invoice_id  the invoice id
 * @param  array   $options     options
 * @return mixed
*/
function it_exchange_get_date_issued_for_invoice( $invoice_id ) {
		$data        = it_exchange_get_product_feature( $invoice_id, 'invoices' );
		$date_issued = empty( $data['date_issued'] ) ? '' : $data['date_issued'];
		return $date_issued;
}
