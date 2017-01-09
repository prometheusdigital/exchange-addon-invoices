<?php
/**
 * This file contains the markup for the email invoice meta.
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
 * Example: theme/exchange/emails/email/invoice/meta.php
 */
?>
<tr>
	<td align="center">
		<!--[if mso]>
		<center>
			<table>
				<tr>
					<td width="640">
		<![endif]-->
		<table border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 640px; background: <?php it_exchange( 'email', 'body-background-color' ); ?>; margin: 0 auto;" class="wrapper body-bkg-color">
			<tr>
				<td valign="top" style="padding: 20px 25px; ">
					<table width="100%">
						<tr>
							<td style="line-height: 1.4; vertical-align: top;">
								<strong><?php _e( 'To', 'LION' ); ?></strong><br>
								<?php it_exchange( 'invoice', 'to' ); ?>
							</td>

							<td style="line-height: 1.4; vertical-align: top;">
								<strong><?php _e( 'From', 'LION' ) ?></strong><br>
								<?php it_exchange( 'invoice', 'from' ); ?>
							</td>
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
