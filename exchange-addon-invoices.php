<?php
/*
 * Plugin Name: ExchangeWP - Invoices Add-on
 * Version: 1.9.4
 * Description: Allows you to invoice clients for services.
 * Plugin URI: https://exchangewp.com/downloads/invoices/
 * Author: ExchangeWP
 * Author URI: https://exchangewp.com/
 * ExchangeWP Package: exchange-addon-invoices

 * Installation:
 * 1. Download and unzip the latest release zip file.
 * 2. If you use the WordPress plugin uploader to install this plugin skip to step 4.
 * 3. Upload the entire plugin directory to your `/wp-content/plugins/` directory.
 * 4. Activate the plugin through the 'Plugins' menu in WordPress Administration.
 * 5. Add license key to settings page.
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
		'author'            => 'ExchangeWP',
		'author_url'        => 'https://exchangewp.com/downloads/invoices/',
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

/**
 * Adds the Updater Class for ExchangeWP
 *
 * @since 1.9.3
 */
 function exchange_invoices_plugin_updater() {

 	$license_check = get_transient( 'exchangewp_license_check' );

 	if ($license_check->license == 'valid' ) {
 		$license_key = it_exchange_get_option( 'exchangewp_licenses' );
 		$license = $license_key['exchange_license'];

 		$edd_updater = new EDD_SL_Plugin_Updater( 'https://exchangewp.com', __FILE__, array(
 				'version' 		=> '1.9.4', 				// current version number
 				'license' 		=> $license, 				// license key (used get_option above to retrieve from DB)
 				'item_id' 		=> 362,					 	  // name of this plugin
 				'author' 	  	=> 'ExchangeWP',    // author of this plugin
 				'url'       	=> home_url(),
 				'wp_override' => true,
 				'beta'		  	=> false
 			)
 		);
 	}

 }

 add_action( 'admin_init', 'exchange_invoices_plugin_updater', 0 );
