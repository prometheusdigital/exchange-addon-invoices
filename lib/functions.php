<?php

/**
 * Enqueues styles for add-edit product page
 *
 * @since 1.0.0
 * @param string $hook_suffix WordPress Hook Suffix
 * @param string $post_type WordPress Post Type
*/
function it_exchange_invoices_addon_admin_wp_enqueue_styles( $hook_suffix, $post_type ) {
	if ( empty( $hook_suffix ) || ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) )
		return;

    if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		if ( 'invoices-product-type' == it_exchange_get_product_type() )
			wp_enqueue_style( 'it-exchange-invoices-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/styles/add-edit-product.css' );
    }
}
add_action( 'it_exchange_admin_wp_enqueue_styles', 'it_exchange_invoices_addon_admin_wp_enqueue_styles', 10, 2 );

/**
 * Enqueues JS on add/edit product page
 *
 * @since 1.0.0
 * @param string $hook_suffix WordPress Hook Suffix
 * @param string $post_type WordPress Post Type
*/
function it_exchange_invoices_addon_admin_wp_enqueue_scripts( $hook_suffix, $post_type ) {
	if ( empty( $hook_suffix ) || ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) )
		return;

    if ( isset( $post_type ) && 'it_exchange_prod' === $post_type ) {
		if ( 'invoices-product-type' == it_exchange_get_product_type() ) {
			$deps = array( 'jquery', 'jquery-ui-tooltip', 'jquery-ui-datepicker' );
			wp_enqueue_script( 'it-exchange-invoices-addon-add-edit-product', ITUtility::get_url_from_file( dirname( __FILE__ ) ) . '/js/add-edit-product.js', $deps );
		}
    }
}
add_action( 'it_exchange_admin_wp_enqueue_scripts', 'it_exchange_invoices_addon_admin_wp_enqueue_scripts', 10, 2 );

/**
 * Sets product visibility to false by default in add/edit product screen
 *
 * @since 1.0.0
 *
 * @param  boolean $visibility  default passed through by WP filter
 * @param  integer $post_id     the post id
 * @return boolean
*/
function it_exchange_invoices_addon_set_default_visibility_to_false( $visibility, $post_id ) {
	$current_screen = get_current_screen();
	$product_type   = it_exchange_get_product_type();

	if ( ! empty( $current_screen->action ) && 'add' == $current_screen->action && 'invoices-product-type' == $product_type )
		$visibility = 'hidden';

	return $visibility;
}
add_filter( 'it_exchange_add_ediit_product_visibility', 'it_exchange_invoices_addon_set_default_visibility_to_false', 10, 2 );

/**
 * Processes AJAX request to get client data
 *
 * @since 1.0.0
 *
 *
*/
function it_exchange_invoicing_ajax_get_client_data() {

	$defaults = array(
		'clientID'          => 0,
		'clientDisplayName' => '',
		'clientEmail'       => '',
		'clientCompany'     => '',
		'clientTerms'       => 1,
	);

	$userid = empty( $_POST['clientID'] ) ? 0 :  $_POST['clientID'];

	// Get client
	$userdata = get_userdata( $userid );

	$data = new stdClass();
	$data->clientID          = empty( $userdata->data->ID ) ? $defaults['clientID'] : $userdata->data->ID;
	$data->clientDisplayName = empty( $userdata->data->display_name ) ? $defaults['clientDisplayName'] : $userdata->data->display_name;
	$data->clientEmail       = empty( $userdata->data->user_email ) ? $defaults['clientEmail'] : $userdata->data->user_email;
	
	$meta = get_user_meta( $data->clientID, 'it-exchange-invoicing-meta', true );

	$data->clientCompany = empty( $meta['company'] ) ? $defaults['clientCompany'] : $meta['company'];
	$data->clientTerms   = empty( $meta['terms'] ) ? $defaults['clientTerms'] : $meta['terms'];

	echo json_encode( $data );
	die();
}
add_action( 'wp_ajax_it-exchange-invoices-get-client-data', 'it_exchange_invoicing_ajax_get_client_data' );

/**
 * Add Client to WordPress Users
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoices_ajax_create_client() {
	$return = new stdClass();

	// Custom errors
	if ( empty( $_POST['first_name'] ) ) {
		$user_id = new WP_Error( 'empty-first-name', __( 'Error: Please include a First Name', 'LION' ) );
	} else if ( empty( $_POST['last_name'] ) ) {
		$user_id = new WP_Error( 'empty-last-name', __( 'Error: Please include a Last Name', 'LION' ) );
	} else if ( empty( $_POST['email'] ) ) {
		$user_id = new WP_Error( 'empty-email', __( 'Error: Please include an Email Address', 'LION' ) );
	} else {
		$user_id = it_exchange_register_user();
	}

	if ( is_wp_error( $user_id ) ) {
		$return->error = 1;
		$return->message = $user_id->get_error_message();
	} else {
		$return->error = 0;
		$return->id = $user_id;

		$company = empty( $_POST['company'] ) ? '' : $_POST['company'];
		update_user_meta( $user_id, 'it-exchange-invoicing-meta', array( 'company' => $company ) );
	}
	echo json_encode( $return );
	die();
}
add_action( 'wp_ajax_it-exchange-invoices-create-client', 'it_exchange_invoices_ajax_create_client' );

/**
 * This function tells Exchange to look in a directory in our add-on for template parts
 *
 * @since 1.0.0
 *
 * @param array $template_paths existing template paths. Exchange core paths will be added after this filter.
 * @param array $template_names the template part names we're looking for right now.
 * @return array
*/
function it_exchange_invoices_add_template_directory( $template_paths, $template_names ) { 

	// Return if not an invoice product type
	if ( ! it_exchange_is_page( 'product' ) || 'invoices-product-type' != it_exchange_get_product_type() )
		return $template_paths;

	// If content-invoice-product.php is in template_names, add our template path and return
	if ( in_array( 'content-invoice-product.php', (array) $template_names ) ) {
		$template_paths[] = dirname( __FILE__ ) . '/templates';
		return $template_paths;
	}

	// If any of the template_paths include content-invoice-product, return add our templates directory
	foreach( (array) $template_names as $name ) {
		if ( false !== strpos( $name, 'content-invoice-product' ) ) {
			$template_paths[] = dirname( __FILE__ ) . '/templates';
			return $template_paths;
		}
	}

	// We shouldn't make it here but return just in case we do.
    return $template_paths;
}
add_filter( 'it_exchange_possible_template_paths', 'it_exchange_invoices_add_template_directory', 10, 2 );

/**
 * Hijacks requests for content-product.php and replaces with content-invoice-product.php in on a single invoice
 *
*/
function it_exchange_invoices_hijack_product_template( $template_names, $load, $require_once, $template_part ) {
	if ( ! it_exchange_is_page( 'product' ) || 'invoices-product-type' != it_exchange_get_product_type() || ! $template_part )
		return $template_names;

	foreach( (array) $template_names as $key => $name ) {
		if ( 'content-product.php' == $name )
			$template_names[$key] = 'content-invoice-product.php';
	}
	return $template_names;
}
add_action( 'it_exchange_locate_template_template_names', 'it_exchange_invoices_hijack_product_template', 99, 4 );
