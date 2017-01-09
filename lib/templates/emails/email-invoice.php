<?php
/**
 * This file contains the markup for the invoice email template.
 *
 * @since   2.0.0
 * @link    http://ithemes.com/codex/page/Exchange_Template_Updates
 * @package IT_Exchange
 *
 * WARNING: Do not edit this file directly. To use
 * this template in a theme, simply copy over this
 * file's content to the exchange directory located
 * at your templates root.
 *
 * Example: theme/exchange/emails/email-invoice.php
 */
?>
<?php it_exchange_get_template_part( 'emails/partials/head' ); ?>

	<!-- HIDDEN PREHEADER TEXT -->
	<div style="display: none; font-size: 1px; color: #fefefe; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden;">
		<?php printf( __( 'New invoice from %s.', 'LION' ), it_exchange( 'invoice', 'get-from', 'fields=name' ) ); ?>
	</div>

<?php it_exchange_get_template_part( 'emails/partials/header' ); ?>

	<!-- begin content heading -->
	<tr>
		<td align="center">
			<!--[if mso]>
			<center>
				<table>
					<tr>
						<td width="640">
			<![endif]-->
			<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 640px; background: <?php it_exchange( 'email', 'body-background-color' ); ?>;  margin: 0 auto; border-bottom: 1px solid <?php it_exchange( 'email', 'body-highlight-color' ); ?>;" class="wrapper border-highlight-color body-bkg-color">
				<tr>
					<td valign="top" style="padding: 20px 25px;">
						<table width="100%">
							<tr>
								<td style="font-weight: bold; ">
									<strong>
										<?php printf( __( 'Issued: %s', 'LION' ), it_exchange( 'invoice', 'get-issued-date', 'format=value' ) ); ?>
									</strong>
								</td>

								<?php if ( it_exchange( 'invoice', 'has-invoice-number' ) ) : ?>
									<td align="right" style="font-weight: bold; ">
										<strong>
											<?php it_exchange( 'invoice', 'invoice-number' ); ?>
										</strong>
									</td>
								<?php endif; ?>
							</tr>
						</table>
					</td>
				</tr>
			</table>
			<!--[if mso]>
			</td></tr></table>
			</center>
			<![endif]-->
		</td>
	</tr>
	<!-- end content heading -->

<?php if ( it_exchange( 'email', 'has-message' ) ): ?>
	<?php it_exchange_get_template_part( 'emails/partials/message' ); ?>
<?php endif; ?>

<?php it_exchange_get_template_part( 'emails/invoice/meta' ); ?>

<?php if ( it_exchange( 'invoice', 'has-description' ) || it_exchange( 'invoice', 'has-terms' ) ): ?>
	<?php it_exchange_get_template_part( 'emails/invoice/details' ); ?>
<?php endif; ?>

<?php it_exchange_get_template_part( 'emails/invoice/totals' ); ?>

<?php it_exchange_get_template_part( 'emails/partials/footer' ); ?>
<?php it_exchange_get_template_part( 'emails/partials/foot' ); ?>