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
function it_exchange_invoice_addon_change_admin_price_label( $label, $post=false ) {
	$post = empty( $post ) ? $GLOBALS['post'] : $post;
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
 * Logs the User in for invoice and transaction
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoice_addon_login_client() {

	// Abandon if not on invoice page, if not doing transaction, or if hash is not correct for product
	if ( ( is_admin() || ! it_exchange_invoice_addon_is_hash_valid_for_invoice() ) && ! it_exchange_is_page( 'transaction' ) )
		return;

	// If doing a transaction, we will log the user in based on the product... if the product is a invoice
	if ( it_exchange_is_page( 'transaction' ) ) {

		// Only use the first product in the cart because invoices should not be purchased with anything else
		$products   = (array) it_exchange_get_cart_products();
		$products   = reset( $products );
		$product_id = empty( $products['product_id'] ) ? 0 : $products['product_id'];
		$product    = it_exchange_get_product( $product_id );

	} else {
		// If not on transaction
		$product       = it_exchange_get_product( false );
		$product_id    = empty( $product->ID ) ? 0 : $product->ID;
	}

	// Abandon if product is not an invoice
	if ( 'invoices-product-type' != it_exchange_get_product_type( $product_id ) )
		return;

	$meta          = it_exchange_get_product_feature( $product_id, 'invoices' );
	$exchange_user = it_exchange_get_customer( $meta['client'] );
	$wp_user       = empty( $exchange_user->wp_user ) ? false : $exchange_user->wp_user;

	if ( empty( $wp_user->ID ) )
		return;

	// Log client in
	$GLOBALS['current_user'] = $wp_user;

	// Remove taxes
	remove_filter( 'it_exchange_get_cart_total', 'it_exchange_addon_taxes_simple_modify_total' );

}
add_action( 'template_redirect', 'it_exchange_invoice_addon_login_client' );


function it_exchange_invoice_log_client_in_for_superwidget() {
	// If user is already logged in, we don't need to do anything
	if ( is_user_logged_in() )
		return;

	// If product in cart is an invoice, we're going to log in the user the invoice was sent to for the duration of this script.
	$products   = (array) it_exchange_get_cart_products();
	$products   = reset( $products );
	$product_id = empty( $products['product_id'] ) ? 0 : $products['product_id'];
	$product    = it_exchange_get_product( $product_id );

	// Abandon if product is not an invoice
	if ( 'invoices-product-type' != it_exchange_get_product_type( $product_id ) )
		return;

	$meta          = it_exchange_get_product_feature( $product_id, 'invoices' );
	$exchange_user = it_exchange_get_customer( $meta['client'] );
	$wp_user       = empty( $exchange_user->wp_user ) ? false : $exchange_user->wp_user;

	// Abandon if no WP user was found
	if ( empty( $wp_user->ID ) )
		return;

	// Log client in
	$GLOBALS['current_user'] = $wp_user;
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

/**
 * Automaically add invoice to cart when landing on the page
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoice_addon_auto_add_remove_invoice_cart_items() {

	if ( ! it_exchange_is_page( 'product' ) && ! it_exchange_is_page( 'cart' ) && ! it_exchange_is_page( 'checkout' ) )
		return;

	if ( ! it_exchange_is_page( 'product' ) || 'invoices-product-type' != it_exchange_get_product_type() ) {
		if ( $products = it_exchange_get_cart_products() ) {
			foreach( $products as $product ) {
				if ( 'invoices-product-type' == it_exchange_get_product_type( $product['product_id'] ) )
					it_exchange_delete_cart_product( $product['product_cart_id'] );
			}
		}
		return;
	}

	$product = it_exchange_get_product( false );
	if ( empty( $product->ID ) )
		return;

	if ( ! it_exchange_invoice_addon_is_hash_valid_for_invoice() || it_exchange_invoice_addon_get_invoice_transaction_id( $product->ID ) )
		return;

	// Empty Cart
	it_exchange_empty_shopping_cart();
	it_exchange_add_product_to_shopping_cart( $product->ID );
}
add_action( 'template_redirect', 'it_exchange_invoice_addon_auto_add_remove_invoice_cart_items' );

/**
 * Sends the invoice to the client
 *
 * @since 1.0.0
 *
 * @param integer $post_id the id of the invoice
 *
 * @return boolean
*/
function it_exchange_invoice_addon_send_invoice( $post_id ) {

	add_shortcode( 'it-exchange-invoice-email', 'it_exchange_invoice_addon_parse_shortcode' );
	$GLOBALS['it_exchange']['invoice-mail-id'] = $post_id; // Hackity hack

	$meta      = it_exchange_get_product_feature( $post_id, 'invoices' );
	$client_id = empty( $meta['client'] ) ? 0 : $meta['client'];
	$client    = it_exchange_get_customer( $client_id );
	$email     = empty( $client->data->user_email ) ? false : $client->data->user_email;

	$email_settings    = it_exchange_get_option( 'invoice-addon' );
	$exchange_settings = it_exchange_get_option( 'settings-general' );
	$subject           = empty( $email_settings['client-subject-line'] ) ? false : do_shortcode( $email_settings['client-subject-line'] );
	$message           = empty( $email_settings['client-message'] ) ? false : do_shortcode( $email_settings['client-message'] );
	$company_name      = empty( $exchange_settings['company-name'] ) ? get_bloginfo( 'name' ) : $exchange_settings['company-name'];
	$company_email     = empty( $exchange_settings['company-email'] ) ? get_bloginfo( 'admin_email' ) : $exchange_settings['company-email'];
	$headers           = 'From: ' . $company_name . ' <' . $company_email . '>';

	unset( $GLOBALS['it_exchange']['invoice-mail-id'] ); // Hackity hack
	remove_shortcode( 'it-exchange-invoice-email' );

	if ( empty( $email ) || empty( $subject ) || empty( $message ) )
		return false;

	return wp_mail( $email, $subject, $message, $headers );
}

/**
 * Replaces shortcode variables in invoice emails
 *
 * @since 1.0.0
 *
 * @return string
*/
function it_exchange_invoice_addon_parse_shortcode( $atts ) {

	$post_id           = empty( $GLOBALS['it_exchange']['invoice-mail-id'] ) ? false : $GLOBALS['it_exchange']['invoice-mail-id']; // Hackity hack
	if ( empty( $post_id ) )
		return '';

	$defaults = array(
		'data' => false,
	);
	$atts = shortcode_atts( $defaults, $atts );

	$meta              = it_exchange_get_product_feature( $post_id, 'invoices' );
	$client_id         = empty( $meta['client'] ) ? 0 : $meta['client'];
	$client            = it_exchange_get_customer( $client_id );
	$exchange_settings = it_exchange_get_option( 'settings-general' );

	$client_name       = empty( $client->data->display_name ) ? '' : $client->data->display_name;
	$client_company    = empty( $meta['company'] ) ? '' : $meta['company'];
	$client_email      = empty( $client->data->user_email ) ? false : $client->data->user_email;
	$from_company      = empty( $exchange_settings['company-name'] ) ? get_bloginfo( 'name' ) : $exchange_settings['company-name'];
	$from_email        = empty( $exchange_settings['company-email'] ) ? get_bloginfo( 'admin_email' ) : $exchange_settings['company-email'];
	$from_address      = empty( $exchange_settings['company-address'] ) ? '' : $exchange_settings['company-address'];
	$date_issued       = empty( $meta['date_issued'] ) ? '' : $meta['date_issued'];
	$total_due         = it_exchange_format_price( it_exchange_get_product_feature( $post_id, 'base-price' ) );
	$terms             = empty( $meta['terms'] ) ? '' : $meta['terms'];
	$available_terms   = it_exchange_invoice_addon_get_available_terms();
	$terms             = empty( $available_terms[$terms]['title'] ) ? '' : $available_terms[$terms]['title'];
	$description       = it_exchange_get_product_feature( $post_id, 'description' );
	$notes             = empty( $meta['notes'] ) ? '' : $meta['notes'];
	$payment_link      = add_query_arg( 'client', $meta['hash'], get_permalink( $post_id ) );

	switch( $atts['data'] ) {
		case 'client-name' :
			return $client_name;
			break;
		case 'client-company' :
			return $client_company;
			break;
		case 'client-email' :
			return $client_email;
			break;
		case 'from-company' :
			return $from_company;
			break;
		case 'from-email' :
			return $from_email;
			break;
		case 'from-address' :
			return $from_address;
			break;
		case 'date-issued' :
			return $date_issued;
			break;
		case 'total-due' :
			return $total_due;
			break;
		case 'terms' :
			return $terms;
			break;
		case 'description' :
			return $description;
			break;
		case 'notes' :
			return $notes;
			break;
		case 'payment-link' :
			return $payment_link;
			break;
		default :
			return '';
	}

}

/**
 * Resend email via AJAX
 *
 * @since 1.0.0
 *
 * @return boolean
*/
function it_exchange_invoice_ajax_resend_client_email() {
	$post_id = empty( $_POST['invoiceID'] ) ? 0 : $_POST['invoiceID'];

	it_exchange_invoice_addon_send_invoice( $post_id );
	die('1');
}
add_action( 'wp_ajax_it-exchange-invoice-resend-email', 'it_exchange_invoice_ajax_resend_client_email' );

/**
 * Remove template parts if they aren't being used
 *
 * @since 1.0.0
 *
 * @param array $parts default template parts
 * @return array
*/
function it_exchange_invoice_addon_remove_unsued_template_parts( $parts ) {
	if ( is_admin() || ! it_exchange_is_page( 'product' ) || 'invoices-product-type' != it_exchange_get_product_type() )
		return $parts;

	$product = it_exchange_get_product( false );
	if ( empty( $product->ID ) )
		return $parts;

	$meta = it_exchange_get_product_feature( $product->ID, 'invoices' );

	// Unset the Notes template part if it's empty
	if ( empty( $meta['notes'] ) ) {
		$i = array_search( 'notes', $parts );
		if ( false !== $i)
			unset( $parts[$i] );
	}

	if ( ! it_exchange_invoice_addon_is_hash_valid_for_invoice() ) {
		$parts = array( 'resend-link' );
	}

	return $parts;
}
add_filter( 'it_exchange_get_content_invoice_product_main_elements', 'it_exchange_invoice_addon_remove_unsued_template_parts' );

/**
 * Resend Email if requested
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoice_addon_resend_email_on_request() {
	if ( empty( $_GET['it-exchange-invoice-resend'] ) || is_admin() || ! it_exchange_is_page( 'product' ) || 'invoices-product-type' != it_exchange_get_product_type() )
		return;

	$product = it_exchange_get_product( false );
	if ( empty( $product->ID ) )
		return;

	it_exchange_invoice_addon_send_invoice( $product->ID );
	it_exchange_add_message( 'notice', __( 'Email sent', 'LION' ) );
}
add_action( 'template_redirect', 'it_exchange_invoice_addon_resend_email_on_request', 12 );

/**
 * Modify the View and Preview product buttons for invoices
 *
 * @since 1.0.0
 *
 * @param string $label incoming from WP filter
 * @param object $post  incoming WP post from WP filter
 * @return string
*/
function it_exchange_invoice_addon_filter_preview_view_product_button_labels( $label, $post ) {

	if ( 'invoices-product-type' != it_exchange_get_product_type( $post ) )
		return $label;

	$preview = __( 'Preview Invoice', 'LION' );
	$view    = __( 'View Invoice', 'LION' );

	$current = current_filter();

	if ( 'it_exchange_preview_product_button_label' == $current )
		return $preview;
	if ( 'it_exchange_view_product_button_label' == $current )
		return $view;

	return $label;
}
add_filter( 'it_exchange_preview_product_button_label', 'it_exchange_invoice_addon_filter_preview_view_product_button_labels', 10, 2 );
add_filter( 'it_exchange_view_product_button_label', 'it_exchange_invoice_addon_filter_preview_view_product_button_labels', 10, 2 );

/**
 * Modify the View and Preview product button URLs for invoices
 *
 * @since 1.0.0
 *
 * @param string $url  incoming from WP filter
 * @param object $post incoming WP post from WP filter
 * @return string
*/
function it_exchange_invoice_addon_filter_preview_view_product_button_urls( $url, $post ) {

	if ( 'invoices-product-type' != it_exchange_get_product_type( $post ) )
		return $url;

	$invoice_meta = it_exchange_get_product_feature( $post->ID, 'invoices' );
	$client_hash  = empty( $invoice_meta['hash'] ) ? '': $invoice_meta['hash'];

	$url = add_query_arg( 'client', $client_hash, $url );

	return $url;
}
add_filter( 'it_exchange_preview_product_button_link', 'it_exchange_invoice_addon_filter_preview_view_product_button_urls', 10, 2 );
add_filter( 'it_exchange_view_product_button_link', 'it_exchange_invoice_addon_filter_preview_view_product_button_urls', 10, 2 );
