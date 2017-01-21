<?php
/*
 * Plugin Name: iThemes Exchange - Invoices Add-on
 * Version: 2.0.0
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
*/

/**
 * Load the Invoices plugin.
 *
 * @since 2.0.0
 */
function it_exchange_load_invoices() {
	if ( ! function_exists( 'it_exchange_load_deprecated' ) || it_exchange_load_deprecated() ) {
		require_once dirname( __FILE__ ) . '/deprecated/exchange-addon-invoices.php';
	} else {
		require_once dirname( __FILE__ ) . '/plugin.php';
	}
}

add_action( 'plugins_loaded', 'it_exchange_load_invoices' );

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