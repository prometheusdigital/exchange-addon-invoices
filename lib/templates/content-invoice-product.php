<?php
/**
 * Default template for displaying the a single
 * invoice product.
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
<?php do_action( 'it_exchange_content_invoice_product_before_wrap' ); ?>
<div id="it-exchange-product">
	<div id="it-exchange-invoice-product" class="it-exchange-wrap">
		<?php it_exchange_get_template_part( 'messages' ); ?>
		<?php do_action( 'it_exchange_content_invoice_product_begin_wrap' ); ?>
		<?php
		$template_parts = array( 'print', 'header', 'to-from', 'description-terms', 'notes', 'product-images', 'payment' );
		foreach( it_exchange_get_template_part_elements( 'content_invoice_product', 'main', $template_parts ) as $part ) :
			it_exchange_get_template_part( 'content', 'invoice-product/elements/' . $part );
		endforeach;
		?>
		<?php do_action( 'it_exchange_content_invoice_product_end_wrap' ); ?>
	</div>
</div>
<?php do_action( 'it_exchange_content_invoice_product_after_wrap' ); ?>
