<?php
/**
 * Load the Invoices plugin.
 *
 * @since   2.0.0
 * @license GPLv2
 */

/**
 * This registers our plugin as a invoices addon
 *
 * @since 1.0.0
 *
 * @return void
 */
function it_exchange_register_invoices_addon() {
	$options = array(
		'name'              => __( 'Invoices', 'LION' ),
		'description'       => __( 'Allows you to invoice clients for services.', 'LION' ),
		'author'            => 'iThemes',
		'author_url'        => 'http://ithemes.com/exchange/invoices/',
		'icon'              => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/invoices50.png' ),
		'wizard-icon'       => ITUtility::get_url_from_file( dirname( __FILE__ ) . '/lib/images/wizard-invoices.png' ),
		'file'              => dirname( __FILE__ ) . '/init.php',
		'category'          => 'product-type',
		'basename'          => plugin_basename( __FILE__ ),
		'labels'      => array(
			'singular_name' => __( 'Invoice', 'LION' ),
		),
	);
	it_exchange_register_addon( 'invoices-product-type', $options );

	//Reschedule the cron if it doesn't exist!
	if ( ! wp_next_scheduled( 'it_exchange_invoice_addon_daily_schedule' ) ) {
		wp_schedule_event( strtotime( get_gmt_from_date( date( 'Y-m-d H:i:s', strtotime( 'Tomorrow 6AM' ) ) ) ), 'daily', 'it_exchange_invoice_addon_daily_schedule' );
	}
}
add_action( 'it_exchange_register_addons', 'it_exchange_register_invoices_addon' );

/**
 * Loads the translation data for WordPress
 *
 * @uses load_plugin_textdomain()
 * @since 1.0.0
 * @return void
 */
function it_exchange_invoices_set_textdomain() {
	load_plugin_textdomain( 'LION', false, dirname( plugin_basename( __FILE__  ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'it_exchange_invoices_set_textdomain' );