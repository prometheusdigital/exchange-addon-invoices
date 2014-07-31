<?php
/**
 * The default template part for the invoice 'terms' field in
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
<?php do_action( 'it_exchange_content_invoices_before_terms_element' ); ?>
<span class="it-exchange-invoice-terms"><?php it_exchange( 'invoice', 'terms' ); ?></span>
<?php do_action( 'it_exchange_content_purchases_after_terms_element' ); ?>
