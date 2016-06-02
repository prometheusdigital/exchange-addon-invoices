<?php
/*
 * Plugin Name: iThemes Exchange - Invoices Add-on
 * Version: 1.9.2
 * Description: Allows you to invoice clients for services.
 * Plugin URI: http://ithemes.com/exchange/invoices/
 * Author: iThemes
 * Author URI: http://ithemes.com
 * iThemes Package: exchange-addon-invoices

 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 *
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
		'settings-callback' => 'it_exchange_invoice_addon_settings_callback',
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

/**
 * Registers Plugin with iThemes updater class
 *
 * @since 1.0.0
 *
 * @param object $updater ithemes updater object
 * @return void
*/
function ithemes_exchange_addon_invoices_updater_register( $updater ) {
	    $updater->register( 'exchange-addon-invoices', __FILE__ );
}
add_action( 'ithemes_updater_register', 'ithemes_exchange_addon_invoices_updater_register' );
require( dirname( __FILE__ ) . '/lib/updater/load.php' );

/**
 * Sets options on activation if they're empty
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoice_addon_activation() {
	include_once( 'lib/settings.php' );
	it_exchange_invoice_addon_set_default_options();
	if ( ! wp_next_scheduled( 'it_exchange_invoice_addon_daily_schedule' ) ) {
		wp_schedule_event( strtotime( get_gmt_from_date( date( 'Y-m-d H:i:s', strtotime( 'Tomorrow 6AM' ) ) ) ), 'daily', 'it_exchange_invoice_addon_daily_schedule' );
	}
}
register_activation_hook( __FILE__, 'it_exchange_invoice_addon_activation' );

/**
 * On deactivation, remove all functions from the scheduled action hook.
 *
 * @since 1.0.0
 */
function it_exchange_invoice_addon_deactivation() {
	wp_clear_scheduled_hook( 'it_exchange_invoice_addon_daily_schedule' );
}
register_deactivation_hook( __FILE__, 'it_exchange_invoice_addon_deactivation' );

//Since we're supporting auto-invoicing, I want to make child invoices look proper...
add_filter( 'ithemes_exchange_products_post_type_hierarchical', '__return_true' );
