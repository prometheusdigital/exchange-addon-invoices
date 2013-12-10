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

	// Set default Term
	$terms = array_keys( it_exchange_invoice_addon_get_available_terms() );
	$default_term = reset( $terms );

	$defaults = array(
		'clientID'          => 0,
		'clientDisplayName' => '',
		'clientEmail'       => '',
		'clientCompany'     => '',
		'clientTerms'       => $default_term,
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

/**
 * Change Product Title to Invoice Title on add/edit screen
 *
 * @since 1.0.0
 *
 * @param string $label incoming from WP filter
 * @param mixed $post incoming post id/object from WP Filter
 * @return string
*/
function it_exchange_invoice_addon_change_admin_title_label( $label, $post ) {
	if ( 'invoices-product-type' == it_exchange_get_product_type( $post ) )
		$label = __( 'Invoice Title', 'LION' );

	return $label;
}
add_filter( 'it_exchange_add_edit_product_title_label', 'it_exchange_invoice_addon_change_admin_title_label', 10, 2 );

/**
 * Change Product Title Tooltip on add/edit screen
 *
 * @since 1.0.0
 *
 * @param string $tooltip incoming from WP filter
 * @param mixed $post incoming post id/object from WP Filter
 * @return string
*/
function it_exchange_invoice_addon_change_admin_title_tooltip( $tooltip, $post ) {
	if ( 'invoices-product-type' == it_exchange_get_product_type( $post ) )
		$tooltip = __( 'Name your invoice something descriptive for future reference.', 'LION' );

	return $tooltip;
}
add_filter( 'it_exchange_add_edit_product_title_tooltip', 'it_exchange_invoice_addon_change_admin_title_tooltip', 10, 2 );

/**
 * Change Product Description to Invoice Description on add/edit screen
 *
 * @since 1.0.0
 *
 * @param string $label incoming from WP filter
 * @param mixed $post incoming post id/object from WP Filter
 * @return string
*/
function it_exchange_invoice_addon_change_admin_description_label( $label, $post ) {
	if ( 'invoices-product-type' == it_exchange_get_product_type( $post ) )
		$label = __( 'Invoice Description', 'LION' );

	return $label;
}
add_filter( 'it_exchange_add_edit_product_description_label', 'it_exchange_invoice_addon_change_admin_description_label', 10, 2 );

/**
 * Change Product Description Tooltip on add/edit screen
 *
 * @since 1.0.0
 *
 * @param string $tooltip incoming from WP filter
 * @param mixed $post incoming post id/object from WP Filter
 * @return string
*/
function it_exchange_invoice_addon_change_admin_description_tooltip( $tooltip, $post ) {
	if ( 'invoices-product-type' == it_exchange_get_product_type( $post ) )
		$tooltip = __( 'This is a quick, descriptive summary of your invoice. It is usually 3-5 sentences long. To add additional info, use the Notes area below.', 'LION' );

	return $tooltip;
}
add_filter( 'it_exchange_add_edit_product_description_tooltip', 'it_exchange_invoice_addon_change_admin_description_tooltip', 10, 2 );

/**
 * Change Product Price to Total Do on add/edit screen
 *
 * @since 1.0.0
 *
 * @param string $label incoming from WP filter
 * @param mixed $post incoming post id/object from WP Filter
 * @return string
*/
function it_exchange_invoice_addon_change_admin_price_label( $label, $post ) {
	if ( 'invoices-product-type' == it_exchange_get_product_type( $post ) )
		$label = __( 'Total Due', 'LION' );

	return $label;
}
add_filter( 'it_exchange_base-price_addon_metabox_description', 'it_exchange_invoice_addon_change_admin_price_label', 10, 2 );

/**
 * Loads the frontend CSS on all exchange pages
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoice_addon_load_public_scripts() {
	// Frontend Product CSS
	if ( is_singular( 'it_exchange_prod' ) && it_exchange_get_product_type() == 'invoices-product-type' ) {
		wp_enqueue_style( 'it-exchange-addon-product-public-css', ITUtility::get_url_from_file( dirname( __FILE__ ) . '/styles/exchange-invoices.css' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'it_exchange_invoice_addon_load_public_scripts' );

/**
 * Returns an array of terms available
 *
 * @since 1.0.0
 *
 * @return array
*/
function it_exchange_invoice_addon_get_available_terms() {

	$terms = array(
		'net-7'  => array(
					'title'       => __( 'Net 7', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due seven days after invoice', 'Description for Net 7 terms', 'LION' ),
					'seconds'     => 604800,
				   ),
		'net-10' => array(
					'title'       => __( 'Net 10', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due ten days after invoice', 'Description for Net 10 terms', 'LION' ),
					'seconds'     => 864000,
				   ),
		'net-30' => array(
					'title'       => __( 'Net 30', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due thirty days after invoice', 'Description for Net 30 terms', 'LION' ),
					'seconds'     => 2592000,
				   ),
		'net-60' => array(
					'title'       => __( 'Net 60', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due sixty days after invoice', 'Description for Net 60 terms', 'LION' ),
					'seconds'     => 5184000,
				   ),
		'net-90' => array(
					'title'       => __( 'Net 90', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due ninety days after invoice', 'Description for Net 10 terms', 'LION' ),
					'seconds'     => 7776000,
				   ),
		'receipt' => array(
					'title'       => __( 'Due on Receipt', 'Title of invoice terms', 'LION' ),
					'description' => __( 'Payment is due upon receipt of the invoice', 'Description for Due On Receipt terms', 'LION' ),
					'seconds'     => 0,
				   ),
	);

	return (array) apply_filters( 'it_exchange_invoice_addon_get_available_terms', $terms );
}

/**
 * Logs the User in on an invoice page
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoice_addon_login_client() {

	// Is hash correct for post
	if ( ( is_admin() || ! it_exchange_invoice_addon_is_hash_valid_for_invoice() ) && ! it_exchange_is_page( 'transaction' ) )
		return;
	
	if ( it_exchange_is_page( 'transaction' ) ) {
		$referal = wp_get_referer();
		if ( empty( $referal ) )
			return;

		$query_parts = parse_url( wp_get_referer(), PHP_URL_QUERY );
		$query_parts = wp_parse_args( $query_parts );

		$product_id  = empty( $query_parts['sw-product'] ) ? false: $query_parts['sw-product'];
		$product     = empty( $product_id ) ? false : it_exchange_get_product( $product_id );
	} else {
		$product       = it_exchange_get_product( false );
		$product_id    = empty( $product->ID ) ? 0 : $product->ID;
	}

	$meta          = it_exchange_get_product_feature( $product_id, 'invoices' );
	$exchange_user = it_exchange_get_customer( $meta['client'] );
	$wp_user       = empty( $exchange_user->wp_user ) ? false : $exchange_user->wp_user;

	if ( empty( $wp_user->ID ) )
		return;

	// Log client in
	$GLOBALS['current_user'] = $wp_user;
}
add_action( 'template_redirect', 'it_exchange_invoice_addon_login_client' );


function it_exchange_invoice_log_client_in_for_superwidget() {
	$query_parts = parse_url( wp_get_referer(), PHP_URL_QUERY );
	$query_parts = wp_parse_args( $query_parts );
	$hash = empty( $query_parts['client'] ) ? false : $query_parts['client'];

	if ( empty( $hash ) || is_user_logged_in() )
		return;

	$product =  empty( $_REQUEST['sw-product'] ) ? 0 : $_REQUEST['sw-product'];
	$meta    = it_exchange_get_product_feature( $product, 'invoices' );
	if ( empty( $meta['client'] ) || empty( $meta['hash'] ) || $meta['hash'] != $hash )
		return;

	$client = it_exchange_get_customer( $meta['client'] );
	$GLOBALS['current_user'] = $client->wp_user;
}
add_action('it_exchange_super_widget_ajax_top', 'it_exchange_invoice_log_client_in_for_superwidget');

/**
 * Disables multi item carts if viewing an invoice product-type
 *
 * @since 1.0.0
 * @param bool $allowed Current status of multi-cart being allowed
 * @return bool True or False if multi-cart is allowed
*/
function it_exchange_invoice_addon_multi_item_cart_allowed( $allowed ) {
    if ( ! $allowed )
        return $allowed;

	$product = it_exchange_get_product( false );
	if ( empty( $product->ID ) || ! it_exchange_is_page( 'product' ) || 'invoices-product-type' != it_exchange_get_product_type() )
		return $allowed;

	return false;
}
add_filter( 'it_exchange_multi_item_cart_allowed', 'it_exchange_invoice_addon_multi_item_cart_allowed', 15 );

/**
 * Is correct hash set for current invoice
 *
 * @since 1.0.0
 *
 * @return boolean
*/
function it_exchange_invoice_addon_is_hash_valid_for_invoice() {

	$hash    = empty( $_GET['client'] ) ? false : $_GET['client'];
	$product = it_exchange_get_product( false );

	if ( empty( $product->ID ) || ! it_exchange_is_page( 'product' ) || 'invoices-product-type' != it_exchange_get_product_type() )
		return false;

	$meta = it_exchange_get_product_feature( $product->ID, 'invoices' );
	if ( empty( $hash) || empty( $meta['hash'] ) || $meta['hash'] !== $hash )
		return false;

	return true;
}

/**
 * Intercept confirmation URL and send back to invoice page
 *
 * @since 1.0.0
 *
 * @param string $url            the confirmation page URL
 * @param int    $transaction_id the id of the transaction
 * @return string $url
*/
function it_exchange_invoice_addon_intercept_confirmation_page_url( $url, $transaction_id ) {
	if ( $products = it_exchange_get_transaction_products( $transaction_id ) ) {
		foreach( $products as $product ) {
			if ( 'invoices-product-type' == it_exchange_get_product_type( $product['product_id'] ) ) {
				$meta = it_exchange_get_product_feature( $product['product_id'], 'invoices' );
				$url = add_query_arg( array( 'client' => $meta['hash'], 'paid' => it_exchange_get_transaction_status( $transaction_id ) ), get_permalink( $product['product_id'] ) );
			}
		}
	}
	return $url;
}
add_filter( 'it_exchange_get_transaction_confirmation_url', 'it_exchange_invoice_addon_intercept_confirmation_page_url', 10, 2 );

/**
 * Updates the invoice as paid
 *
 * @since 1.0.0
 *
 * @param integer $transaction_id the transaction ID
 * @return void
*/
function it_exchange_invoice_addon_attach_transaction_to_product( $transaction_id ) {
	if ( $products = it_exchange_get_transaction_products( $transaction_id ) ) {
		foreach( $products as $product ) {
			if ( 'invoices-product-type' == it_exchange_get_product_type( $product['product_id'] ) ) {
				$meta = it_exchange_get_product_feature( $product['product_id'], 'invoices' );
				$meta['transaction_id'] = $transaction_id;
				update_post_meta( $product['product_id'], '_it-exchange-invoice-data', $meta );
			}
		}
	}
}
add_action( 'it_exchange_add_transaction_success', 'it_exchange_invoice_addon_attach_transaction_to_product' );

/**
 * Returns the transaction ID associated with an invoice if it exists and if its published
 *
 * @since 1.0.0
 *
 * @param integer $invoice_id the post id of the invoice
 * @return boolean
*/
function it_exchange_invoice_addon_get_invoice_transaction_id( $invoice_id ) {
	$invoice_meta   = it_exchange_get_product_feature( $invoice_id, 'invoices' );
	$transaction_id = empty( $invoice_meta['transaction_id'] ) ? false : $invoice_meta['transaction_id'];
	$transaction_id = 'publish' == get_post_status( $transaction_id ) ? $transaction_id : false;
	return $transaction_id;
}
