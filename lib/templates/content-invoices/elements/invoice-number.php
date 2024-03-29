<?php
/**
 * The default template part for the invoice number in
 * the content-invoices template part's invoice-info loop
 *
 * @since 1.2.0
 * @version 1.2.0
 * @package IT_Exchange
 *
 * WARNING: Do not edit this file directly. To use
 * this template in a theme, copy over this file
 * to the exchange/content-invoices/elements/ directory
 * located in your theme.
*/
?>
<?php do_action( 'it_exchange_content_invoices_before_invoice_number_element' ); ?>
<span class="it-exchange-invoice-number">#<?php it_exchange( 'invoice', 'invoice-number', array( 'format' => 'value' ) ); ?></span>
<?php do_action( 'it_exchange_content_purchases_after_invoice_number_element' ); ?>
