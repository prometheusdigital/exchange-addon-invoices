<?php
/**
 * Default template for displaying the a single
 * invoice product description and terms template part.
 *
 * @since 1.0.0
 * @version 1.0.0
 * @link http://ithemes.com/codex/page/Exchange_Template_Updates
 * @package IT_Exchange
 *
 * WARNING: Do not edit this file directly. To use
 * this template in a theme, simply copy over this
 * file's content to the exchange directory located
 * in your theme.
*/
?>
<?php do_action( 'it_exchange_content_invoice_product_before_description-terms_wrap' ); ?>
<div class="it-exchange-invoice-section it-exchange-invoice-description-terms">
	<?php do_action( 'it_exchange_content_invoice_product_begin_description-terms_wrap' ); ?>
	<div class="it-exchange-invoice-description">
		<?php it_exchange( 'invoice', 'description' ); ?>
	</div>
	<div class="it-exchange-invoice-terms">
		<?php it_exchange( 'invoice', 'terms' ); ?>
	</div>
	<?php do_action( 'it_exchange_content_invoice_product_end_description-terms_wrap' ); ?>
</div>
<?php do_action( 'it_exchange_content_invoice_product_after_description-terms_wrap' ); ?>
