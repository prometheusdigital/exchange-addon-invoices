<?php
/**
 * The default template part for the invoice 'summary' field in
 * the content-invoices template part's invoice-info loop
 *
 * @since CHANGEME
 * @version CHANGEME
 * @package IT_Exchange
 *
 * WARNING: Do not edit this file directly. To use
 * this template in a theme, copy over this file
 * to the exchange/content-invoices/elements/ directory
 * located in your theme.
*/
?>
<?php do_action( 'it_exchange_content_invoices_before_summary_element' ); ?>
<span class="it-exchange-invoice-summary">
	<?php 
	if ( it_exchange( 'invoice', 'has-invoice-number' ) ) :
		_ex( '#', 'label for invoice number', 'LION' );
		it_exchange( 'invoice', 'invoice-number', array( 'format' => 'value' ) );
		_ex( ' | ', '[invoicenumber] for [invoiceamount]', 'LION' );
	endif;

	it_exchange( 'invoice', 'total-due', array( 'format' => 'value' ) );
	_e( '| Due on ', 'LION' );
	it_exchange( 'invoice', 'date-due', array( 'format' => 'value' ) );

	if ( 'late' == it_exchange( 'invoice', 'get-payment-status', array( 'format' => 'value' ) ) ) :
		_e( ' (Late)', 'LION' );
	endif;

	_e( '| ', 'LION' );
	$permalink_label = ( 'paid' != it_exchange( 'invoice', 'get-payment-status', array( 'format' => 'value' ) ) ) ? __( 'Pay Now', 'LION' ) : __( 'View Invoice', 'LION' );
	echo '<a href="'; it_exchange( 'invoice', 'permalink', array( 'format' => 'value' ) ); echo '">' . $permalink_label . '</a>';
	?>
</span>
<?php do_action( 'it_exchange_content_purchases_after_summary_element' ); ?>
