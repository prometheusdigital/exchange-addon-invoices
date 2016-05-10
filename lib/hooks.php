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
 * Change single add product menu item to Add Invoice
 *
 * @since 1.1.1
 *
 * @param string $menu_title incoming from WP Filter
 * @param object $product_type the product type object
 * @return string
*/
function it_exchange_invoices_addon_modify_admin_menu_title( $menu_title, $product_type ) {
	if ( ! empty( $product_type['slug'] ) && 'invoices-product-type' == $product_type['slug'] ) {
		$menu_title = __( 'Add Invoice', 'LION' );
	}
	return $menu_title;
}
add_filter( 'it_exchange_admin_add_one_product_type_product_page_title', 'it_exchange_invoices_addon_modify_admin_menu_title', 10, 2 );


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
		'clientAddress'     => '',
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
	$data->clientAddress = empty( $meta['address'] ) ? $defaults['clientAddress'] : $meta['address'];
	$data->clientTerms   = empty( $meta['terms'] ) ? $defaults['clientTerms'] : $meta['terms'];

	echo json_encode( $data );
	die();
}
add_action( 'wp_ajax_it-exchange-invoices-get-client-data', 'it_exchange_invoicing_ajax_get_client_data' );

function it_exchange_invoicing_cancel_auto_invoicing() {
	$results = '';
	$invoice_id = empty( $_POST['invoiceID'] ) ? 0 : $_POST['invoiceID'];

	if ( !empty( $invoice_id ) ) {
		$invoice_data = it_exchange_get_product_feature( $invoice_id, 'invoices' );
		$invoice_data['recurring_enabled'] = false;
		it_exchange_update_product_feature( $invoice_id, 'invoices', $invoice_data );
		$results ='<p>' . __( 'Auto-invoicing has been disabled on the parent invoice.', 'LION' ) . '</p>';
	}

	die( $results );
}
add_action( 'wp_ajax_it-exchange-invoicing-cancel-auto-invoicing', 'it_exchange_invoicing_cancel_auto_invoicing' );

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
		$address = empty( $_POST['client_address'] ) ? '' : $_POST['client_address'];
		update_user_meta( $user_id, 'it-exchange-invoicing-meta', array( 'company' => $company, 'address' => $address ) );
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
	if ( ( ! is_preview() && ! it_exchange_is_page( 'product' ) && ! it_exchange_is_page( 'invoices' ) ) || ( it_exchange_is_page( 'product' ) && 'invoices-product-type' != it_exchange_get_product_type() ) )
		return $template_paths;

	// If content-invoice-product.php is in template_names, add our template path and return
	if ( in_array( 'content-invoice-product.php', (array) $template_names ) || in_array( 'content-invoices.php', (array) $template_names ) ) {
		$template_paths[] = dirname( __FILE__ ) . '/templates';
		return $template_paths;
	}

	// If any of the template_paths include content-invoice-product, return add our templates directory
	foreach( (array) $template_names as $name ) {
		if ( false !== strpos( $name, 'content-invoice-product' ) || false !== strpos( $name, 'content-invoices' ) ) {
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
	if ( ( ! is_preview() && ! it_exchange_is_page( 'product' ) ) || 'invoices-product-type' != it_exchange_get_product_type() || ! $template_part )
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
 * Change Product Price to Total Due on add/edit screen
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
 * Change Product Sale Price to Discount Price on add/edit screen
 *
 * @since 1.0.0
 *
 * @param string $label incoming from WP filter
 * @param mixed $post incoming post id/object from WP Filter
 * @return string
 */
function it_exchange_invoice_addon_change_admin_sale_price_label( $label, $post=false ) {
	$post = empty( $post ) ? $GLOBALS['post'] : $post;
	if ( 'invoices-product-type' == it_exchange_get_product_type( $post ) )
		$label = __( 'Discount Price', 'LION' );

	return $label;
}
add_filter( 'it_exchange_sale-price_addon_metabox_description', 'it_exchange_invoice_addon_change_admin_sale_price_label', 10, 2 );

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
		wp_enqueue_style( 'it-exchange-addon-product-public-print', ITUtility::get_url_from_file( dirname( __FILE__ ) . '/styles/exchange-invoices-print.css' ), array(), false, 'print' );
		wp_enqueue_script( 'it-exchange-addon-product-public-js', ITUtility::get_url_from_file( dirname( __FILE__ ) . '/js/exchange-invoices.js' ), array('jquery') );
	}

	if ( it_exchange_is_page( 'invoices' ) ) {
		wp_enqueue_style( 'it-exchange-customer-invoices', ITUtility::get_url_from_file( dirname( __FILE__ ) . '/styles/exchange-customer-invoices.css' ) );
	}
}
add_action( 'wp_enqueue_scripts', 'it_exchange_invoice_addon_load_public_scripts', 9 );

/**
 * Set the user ID for logged-out nonces if the action matches a whitelist.
 *
 * @since 1.9.1
 *
 * @param int|bool $uid
 * @param string   $action
 *
 * @return int|bool
 */
function it_exchange_invoice_set_user_id_for_nonce_verification( $uid, $action ) {

	if ( $uid ) {
		return $uid;
	}

	$whitelist = apply_filters( 'it_exchange_invoices_user_id_nonce_verification_whitelist', array(), $uid );

	if ( ! in_array( $action, $whitelist ) ) {
		return $uid;
	}

	if ( isset( $_REQUEST['_wp_http_referer'] ) ) {
		$referrer = $_REQUEST['_wp_http_referer'];

		$parsed = parse_url( $referrer );
		
		if ( ! isset( $parsed['query'], $parsed['path'] ) ) {
			return $uid;
		}

		parse_str( $parsed['query'], $query );

		$parts = explode( '/', trim( $parsed['path'], '/' ) );

		$products = it_exchange_get_products( array(
			'product_type'  => 'invoices-product-type',
			'name'          => array_pop( $parts ),
			'show_hidden'   => true
		) );

		if ( ! $products ) {
			return $uid;
		}

		$product = reset( $products );
		
		it_exchange_set_the_product_id( $product->ID );
		
		if ( ! it_exchange_invoice_addon_is_hash_valid_for_invoice( $query['client'] ) ) {
			return $uid;
		}

		$product = $product->ID;
	} else {
		$product = it_exchange_get_the_product_id();
	}

	if ( 'invoices-product-type' !== it_exchange_get_product_type( $product ) ) {
		return $uid;
	}

	$meta          = it_exchange_get_product_feature( $product, 'invoices' );
	$exchange_user = it_exchange_get_customer( $meta['client'] );
	$wp_user       = empty( $exchange_user->wp_user ) ? false : $exchange_user->wp_user;

	if ( empty( $wp_user->ID ) ) {
		return $uid;
	}

	$uid = $wp_user->ID;

	return $uid;
}

add_filter( 'nonce_user_logged_out', 'it_exchange_invoice_set_user_id_for_nonce_verification', 10, 2 );

/**
 * Logs the User in for invoice and transaction
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoice_addon_login_client() {
	// If user is already logged in, we don't need to do anything
	if ( is_user_logged_in() )
		return;

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
	$GLOBALS['it_exchange']['invoice_temp_user'] = true;
	$GLOBALS['current_user'] = $wp_user;

	// Remove taxes
	remove_filter( 'it_exchange_get_cart_total', 'it_exchange_addon_taxes_simple_modify_total' );

}
add_action( 'template_redirect', 'it_exchange_invoice_addon_login_client' );


/**
 * Log the client in during SuperWidget AJAX
 *
 * @since 1.0.0
 *
 * @return void
*/
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
	$GLOBALS['it_exchange']['invoice_temp_user'] = true;
	$GLOBALS['current_user'] = $wp_user;
	
	remove_filter( 'it_exchange_get_cart_total', 'it_exchange_addon_taxes_simple_modify_total' );
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
	return esc_url( $url );
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

	// Should we provide the template with the resend link or without the resend link?
	if ( ! it_exchange_invoice_addon_is_hash_valid_for_invoice() ) {
		// Hash is invalid. Don't show the invoice details. Replace with note.
		$transaction_id = it_exchange_invoice_addon_get_invoice_transaction_id( $product->ID );
		$part  = empty( $transaction_id ) ? 'resend-link' : 'invalid-link';
		$part  = ( apply_filters( 'it_exchange_invoices_include_resend_email_on_invlalid_frontend_link', true, $product ) && 'resend-link' == $part ) ? 'resend-link' : 'invalid-link';
		$parts = array( $part );
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

	// Don't send if it has been paid alrady or if the resend link was turned off by a filter
	$has_transaction = it_exchange_invoice_addon_get_invoice_transaction_id( $product->ID );
	if ( ! empty( $has_transaction ) || false === apply_filters( 'it_exchange_invoices_include_resend_email_on_invlalid_frontend_link', true, $product ) )
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
 * Don't allow admin to edit an invoice that was already paid for
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoice_addon_prevent_editing_paid_invoice() {
	$action       = empty( $_POST['action'] ) ? false : $_POST['action'];
	$post_type    = empty( $_POST['post_type'] ) ? false : $_POST['post_type'];
	$post_id      = empty( $_POST['post_ID'] ) ? false : $_POST['post_ID'];
	$product_type = empty( $_POST['it-exchange-product-type'] ) ? false : $_POST['it-exchange-product-type'];

	if ( 'editpost' != $action || 'it_exchange_prod' != $post_type || empty( $post_id ) || 'invoices-product-type' != $product_type )
		return;

	if ( ! $transaction_id = it_exchange_invoice_addon_get_invoice_transaction_id( $post_id ) )
		return;

	$url = admin_url() . 'post.php?post=' . $post_id . '&action=edit';

	wp_redirect( $url );
	die();
}
add_action( 'admin_init', 'it_exchange_invoice_addon_prevent_editing_paid_invoice' );

/**
 * Add invoice details to the Payment Details screen
 *
 * @since 1.0.0
 *
 * @param object $transaction_post
 * @param object $transaction_product the product for the current transaction
 * @return void
*/
function it_exchange_invoice_addon_add_details_to_payment_details( $transaction_post, $transaction_product ) {
	$product = empty( $transaction_product['product_id'] ) ? 0 : $transaction_product['product_id'];
	if ( 'invoices-product-type' != it_exchange_get_product_type( $product ) )
		return;

	$permalink  = get_permalink( $product );
	$admin_link = admin_url() . 'post.php?post=' . $product . '&action=edit';
	$meta       = it_exchange_get_product_feature( $product, 'invoices' );
	$hash       = empty( $meta['hash'] ) ? 0 : $meta['hash'];

	if ( it_exchange_product_has_feature( $product, 'description' ) ) :
	?>
	<div class="it-exchange-invoices-description">
		<?php echo apply_filters( 'wpautop', it_exchange_get_product_feature( $product, 'description' ) ); ?>
	</div>
	<?php endif; ?>

	<div class="it-exchange-invoice-addon-links">
		<p>
			<a class="frontend-link" href="<?php echo esc_url( add_query_arg( 'client', $hash, $permalink ) ); ?>"><?php _e( 'View Invoice', 'LION' ); ?></a>
			<a class="backend-link" href="<?php echo $admin_link; ?>"><?php _e( 'Edit Invoice', 'LION' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'it_exchange_transaction_details_end_product_details', 'it_exchange_invoice_addon_add_details_to_payment_details', 10, 2 );

/**
 * Remove product support for inventory and quantity
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoice_addon_remove_product_features_from_invoices() {
	it_exchange_remove_feature_support_for_product_type( 'extended-description', 'invoices-product-type' );
	it_exchange_remove_feature_support_for_product_type( 'purchase-quantity', 'invoices-product-type' );
	it_exchange_remove_feature_support_for_product_type( 'inventory', 'invoices-product-type' );
}
add_action( 'init', 'it_exchange_invoice_addon_remove_product_features_from_invoices' );

/**
 * Updates the Get Permalink for invoice to attach the client query arg
 *
 * @since 1.0.0
 *
 * @param string  $link link
 * @param id      $post post object
 * @return string link
*/
function it_exchange_invoice_addon_modify_invoice_permalink( $link, $post ) {
	if ( ! is_admin() || ! current_user_can( 'edit_others_posts' ) || 'invoices-product-type' != it_exchange_get_product_type( $post ) )
		return $link;

	if ( ! $data = it_exchange_get_product_feature( $post->ID, 'invoices' ) )
		return $link;

	if ( empty( $data['hash'] ) )
		return $link;

	return esc_url_raw( add_query_arg( 'client', $data['hash'], $link ) );

}
add_filter( 'post_type_link', 'it_exchange_invoice_addon_modify_invoice_permalink', 10, 2 );

/**
 * Hide non-product super widget
 *
 * @since 1.0.0
 *
 * @return void
*/
function it_exchange_invoice_addon_hide_sidebar_superwidget() {
	if ( ! it_exchange_is_page( 'product' ) || 'invoices-product-type' != it_exchange_get_product_type( false ) )
		return;

	$valid_hash = it_exchange_invoice_addon_is_hash_valid_for_invoice();
	?>
	<script type="text/javascript">
		var itExchangeInvoiceNotProtected = <?php echo empty( $valid_hash ) ? 'true' : 'false'; ?>;
		var itExchangeInvoicePaidFor      = <?php echo ( false == it_exchange_invoice_addon_get_invoice_transaction_id( $GLOBALS['post']->ID ) ) ? 'false' : 'true'; ?>;
		if ( window.jQuery ) {
			jQuery(function() {
				if ( jQuery('.it-exchange-invoice-sw .it-exchange-super-widget').length || itExchangeInvoiceNotProtected || itExchangeInvoicePaidFor ) {
					jQuery('.it-exchange-product-sw').show();
					jQuery('.it-exchange-super-widget:not(.it-exchange-invoice-sw .it-exchange-super-widget)').hide();
				}
			});
		}
	</script>
	<?php
}
add_action( 'wp_footer', 'it_exchange_invoice_addon_hide_sidebar_superwidget' );

/**
 * Filter purchase count for all products view for invoices
 *
 * @since 1.0.2
 *
 * @param string $column the column we're in
 * @return string
*/
function it_exchange_invoices_addon_filter_product_purchase_count( $existing ) {
	global $post;
	if ( 'invoices-product-type' != it_exchange_get_product_type( $post->ID ) )
		return $existing;

	if ( 'it_exchange_product_purchases' == $existing ) {
		// Get transaction ID
		$transaction_id = it_exchange_invoice_addon_get_invoice_transaction_id( $post->ID );

		// Set status if no transaction
		if ( empty( $transaction_id ) ) {
			$meta        = it_exchange_get_product_feature( $post->ID, 'invoices' );
			$date_issued = empty( $meta['date_issued'] ) ? time() : $meta['date_issued'];

			if ( empty( $meta['terms'] ) ) {
				// This is for drafts
				$status = 'unpaid';
			} else {
				$terms = it_exchange_invoice_addon_get_available_terms();
				$term_time = empty( $terms[$meta['terms']]['seconds'] ) ? 0 : $terms[$meta['terms']]['seconds'];

				$status = ( ( $date_issued + $term_time ) > time() ) ? 'unpaid' : 'late';
				$status = ( 'none' == $meta['terms'] || 'receipt' == $meta['terms'] ) ? 'due-now' : $status;
			}
		} else {
			$status = it_exchange_transaction_is_cleared_for_delivery( $transaction_id ) ? 'paid' : 'pending';
		}

		$labels = array(
			'unpaid'  => __( 'Unpaid', 'LION' ),
			'paid'    => __( 'Paid', 'LION' ),
			'pending' => __( 'Pending', 'LION' ),
			'late'    => __( 'Late', 'LION' ),
			'due-now' => __( 'Due Now', 'LION' ),
		);

		$value   = empty( $labels[$status] ) ? false : $labels[$status];
		if ( $value )
		echo '<span class="it-exchange-invoice-addon-status"> - ' . $value . '</span>';
	}
}
add_action( 'manage_it_exchange_prod_posts_custom_column', 'it_exchange_invoices_addon_filter_product_purchase_count', 11 );

/**
 * Alter the Transaction Order Number to display the Invoice Numeber
 *
 * @since 1.0.4
 *
 * @param  string $order_number the incoming order number from Exchange
 * @param  object $transaction the transaction object
 * @param  string $prefix the order number prefix
 * @return string
*/
function it_exchange_invoices_addon_filter_order_number( $order_number, $transaction, $prefix='' ) {
	$transaction_products = it_exchange_get_transaction_products( $transaction );
	if ( ! is_array( $transaction_products ) || count( $transaction_products ) > 1 )
		return $order_number;

	$product = reset( $transaction_products );
	if ( empty( $product['product_id'] ) || 'invoices-product-type' != it_exchange_get_product_type( $product['product_id'] ) )
		return $order_number;

	$meta = it_exchange_get_product_feature( $product['product_id'], 'invoices' );
	return empty( $meta['number'] ) ? $order_number : $prefix . $meta['number'];
}
add_filter( 'it_exchange_get_transaction_order_number', 'it_exchange_invoices_addon_filter_order_number', 10, 3 );

/**
 * If this was an offline payment, and it has been paid, print the message
 *
 * @since 1.0.4
 *
 * @return void
*/
function it_exchange_invoices_addon_print_offline_transaction_method_message() {
	if ( ! $product = it_exchange_get_product( false ) )
		return;

	if ( empty( $product->ID ) || empty( $product->product_type ) || 'invoices-product-type' != $product->product_type )
		return;

	if ( ! $transaction_id = it_exchange_invoice_addon_get_invoice_transaction_id( $product->ID ) )
		return;

	if ( ! 'offline-payments' == it_exchange_get_transaction_method( $transaction_id ) || ! it_exchange_transaction_is_cleared_for_delivery( $transaction_id ) )
		return;

	echo '<p class="it-exchange-invoice-offline-payments-transaction-instructions">' . it_exchange_get_transaction_instructions( $transaction_id ) . '</p>';
}
add_action( 'it_exchange_content_invoice_product_end_payment_wrap', 'it_exchange_invoices_addon_print_offline_transaction_method_message' );

/**
 * Filter the login/logout links to reflect the ability to log in
 *
 * Invoices tell WP that the user is logged in for a specific page view even though they aren't actually logged in (no cookie is set)
 * This means we have to manually flip the login/out link back to login because WP thinks the user is logged in.
 *
 * @since 1.0.5
 *
 * @param array $items
 * @return array
*/
function it_exchange_invoices_filter_loginout_nav_link( $items ) {
	if ( ! it_exchange_is_page( 'product' ) || 'invoices-product-type' != it_exchange_get_product_type() || empty( $GLOBALS['it_exchange']['invoice_temp_user'] ) )
		return $items;

    if ( is_user_logged_in() ) {
        foreach ( $items as $item ) {
            if ( $item->url == it_exchange_get_page_url( 'logout' ) || $item->url == it_exchange_get_page_url( 'login' ) ) {

				$item->url = it_exchange_get_page_url( 'login' );
				$item->title = it_exchange_get_page_name( 'login' );
            }
        }
    }
	return $items;
}
add_filter( 'it_exchange_wp_get_nav_menu_items_filter', 'it_exchange_invoices_filter_loginout_nav_link' );

/**
 * Filter an invoice out of the Manual Purchases list of products to choose from
 *
 * Only do this if the invoice was already paid for
 *
 * @since 1.0.8
 *
 * @param boolean $show_product passed through by WP filter
 * @param object  $product      the it_exchange_product object
 * @return boolean
*/
function it_exchange_invoices_maybe_remove_product_from_manual_purchases_list( $show_product, $product ) {
	if ( empty( $product->product_type ) || 'invoices-product-type' != $product->product_type )
		return $show_product;

	$transaction_id = it_exchange_invoice_addon_get_invoice_transaction_id( $product->ID );

	// Return false if already paid for, otherwise return default
	return it_exchange_transaction_is_cleared_for_delivery( $transaction_id ) ? false : $show_product;
}
add_filter( 'it_exchange_manual_purchases_addon_include_product_in_select', 'it_exchange_invoices_maybe_remove_product_from_manual_purchases_list', 10, 2 );

/**
 * Filter an invoice title on the Manual Purchases list of products to choose from
 *
 * Add the client name
 *
 * @since 1.0.8
 *
 * @param string  $title   passed through by WP filter
 * @param object  $product the it_exchange_product object
 * @return string
*/
function it_exchange_invoices_maybe_filter_product_title_in_manual_purchases_list( $title, $product ) {
	if ( empty( $product->product_type ) || 'invoices-product-type' != $product->product_type )
		return $title;

	$meta         = it_exchange_get_product_feature( $product->ID, 'invoices' );
	$date_issued  = empty( $meta['date_issued'] ) ? '' : $meta['date_issued'];
	$client_id    = empty( $meta['client'] ) ? 0 : absint( $meta['client'] );
	$user         = get_userdata( $client_id );
	$display_name = empty( $user->display_name ) ? false : ucwords( $user->display_name );

	return empty( $display_name ) ? $title : sprintf( _x( '%s. %sBilled to %s on %s%s', '[invoice title] for [customer]', 'LION' ), $title, '<br /><span class="invoice-details">', $display_name, date( get_option( 'date_format' ), $date_issued ), '</span>' );
}
add_filter( 'it_exchange_manual_purchases_addon_selected_product_title', 'it_exchange_invoices_maybe_filter_product_title_in_manual_purchases_list', 10, 2 );

/**
 * Don't bump cart activity if viewing an invoice
 *
 * Integration with the Abandoned Cart Addon
 *
 * @since 1.1.0
 *
 * @param boolean  $bump     Should we bump the cart activity. defaults to true
 * @param object   $customer IT_Exchange_Customer object
 * @param array    $cart     Cart Details
 * @return boolean
*/
function it_exchange_invoices_dont_bump_abandoned_carts( $bump, $customer, $cart ) {
    if ( empty( $cart['products'] ) )
        return $bump;

    foreach( (array) $cart['products'] as $product ) {
        if ( 'invoices-product-type' === it_exchange_get_product_type( $product['product_id'] ) )
            return false;
    }
    return $bump;
}
add_filter( 'it_exchange_abandoned_carts_bump_active_shopper', 'it_exchange_invoices_dont_bump_abandoned_carts', 10, 3 );

/**
 * Register Invoices page
 *
 * @since 1.2.0
 * @return void
*/
function it_exchange_invoices_addon_register_invoices_page() {
    // Purchases
    $options = array(
        'slug'          => 'invoices',
        'name'          => __( 'Invoices', 'LION' ),
        'rewrite-rules' => array( 130, 'it_exchange_invoices_addon_get_page_rewrites' ),
        'url'           => 'it_exchange_invoices_addon_get_page_url',
        'settings-name' => __( 'Invoices', 'LION' ),
        'type'          => 'exchange',
        'menu'          => true,
        'optional'      => true,
    );
    it_exchange_register_page( 'invoices', $options );
}
add_action( 'it_libraries_loaded', 'it_exchange_invoices_addon_register_invoices_page', 11 );

/**
 * Callback returns rewrite rules for invoices page
 *
 * @since 1.2.0
 *
 * @return array
*/
function it_exchange_invoices_addon_get_page_rewrites() {
	$slug         = it_exchange_get_page_slug( 'invoices' );
	$account_slug = it_exchange_get_page_slug( 'account' );

	// If we're using WP as acount page type, add the WP slug to rewrites and return.
	if ( 'wordpress' == it_exchange_get_page_type( 'account' ) ) {
		$account = get_page( it_exchange_get_page_wpid( 'account' ) );
		$account_slug = $account->post_name;
	}

	$rewrites = array(
		$account_slug  . '/([^/]+)/' . $slug . '$' => 'index.php?' . $account_slug . '=$matches[1]&' . $slug . '=1',
		$account_slug . '/' . $slug . '$' => 'index.php?' . $account_slug . '=1&' . $slug . '=1',
	);
	return $rewrites;
}

/**
 * Callback to return the url for the invoices page
 *
 * @since 1.2.0
 *
 * @return string
*/
function it_exchange_invoices_addon_get_page_url() {
    $slug       = it_exchange_get_page_slug( 'invoices' );
    $permalinks = (boolean) get_option( 'permalink_structure' );
    $base       = trailingslashit( get_home_url() );

    // Proccess superwidget links
    if ( it_exchange_in_superwidget() && $slug != 'transaction' && $page != 'confirmation' ) {
        // Get current URL without exchange query args
        $url = it_exchange_clean_query_args();
        return esc_url( add_query_arg( 'ite-sw-state', $slug, $url ) );
    }

	// Account Slug
	if ( 'wordpress' == it_exchange_get_page_type( 'account' ) ) {
		$account_page = get_page( it_exchange_get_page_wpid( 'account' ) );
		$account_slug = $account_page->post_name;
	} else {
		$account_slug = it_exchange_get_page_slug( 'account' );
	}

	// Replace account value with name if user is logged in
	if ( $permalinks )
		$base = trailingslashit( $base . $account_slug );
	else
		$base = add_query_arg( array( $account_slug => 1 ), $base );

	$account_name = get_query_var( 'account' );
	if ( $account_name && '1' != $account_name ) {
		if ( $permalinks ) {
			$base = trailingslashit( $base . $account_name );
		} else {
			$base = remove_query_arg( $account_slug, $base );
			$base = add_query_arg( array( $account_slug => $account_name ), $base );
		}
	}

	if ( $permalinks )
		return trailingslashit( esc_url( $base . $slug ) );
	else
		return esc_url( add_query_arg( array( $slug => 1 ), $base ) );
}

/**
 * Add invoices to customer menu page links (theme api)
 *
 * @since 1.2.0
 *
 * @param array $pages incomign from WP filter
 * @return array
*/
function it_exchange_invoices_addon_add_invoices_page_to_customer_menu_links( $pages ) {
	$pages[] = 'invoices';
	return $pages;
}
add_filter( 'it_exchange_customer_menu_pages', 'it_exchange_invoices_addon_add_invoices_page_to_customer_menu_links' );

/**
 * Flush rewrite rules after first pageload after the upgrade
 *
 * @since 1.2.0
 *
 * @return void
*/
function it_exchange_invoices_addon_flush_rewrites_for_frontend_invoices() {
	$settings = it_exchange_get_option( 'invoice-addon' );
	if ( ! empty( $settings['frontend-rewrites-flushed'] ) ) {
		return;
	}

	$settings['frontend-rewrites-flushed'] = true;
	it_exchange_save_option( 'invoice-addon', $settings );
	update_option('_it-exchange-flush-rewrites', true );
}
add_action( 'admin_init', 'it_exchange_invoices_addon_flush_rewrites_for_frontend_invoices' );

function it_exchange_invoices_addon_noindex_nofollow_headers() {
	global $post;
	if ( is_single() ) {
		$product_type = it_exchange_get_product_type( $post );
		if ( 'invoices-product-type' === $product_type ) {
			echo apply_filters( 'it_exchange_invoices_addon_noindex_nofollow_headers', '<META NAME="ROBOTS" CONTENT="NOINDEX, NOFOLLOW">'."\n" );
		}
	}
}
add_action( 'wp_head', 'it_exchange_invoices_addon_noindex_nofollow_headers' );

/**
 * Daily schedule use to call function for auto-invoices
 *
 * @since CHANGEME
 * @return void
*/
function it_exchange_invoice_add_daily_schedule() {
	it_exchange_invoice_addon_handle_auto_invoices();
}
add_action( 'it_exchange_invoice_add_daily_schedule', 'it_exchange_invoice_add_daily_schedule' );

/**
 * Gets all auto invoices that need to be sent out...
 *
 * @since CHANGEME
 * @return void
*/
function it_exchange_invoice_addon_handle_auto_invoices() {
	$args = array(
		'post_type' => 'it_exchange_prod',
		'post_parent' => 0, //we only want parents
		'meta_query' => array(
			array(
				'key' => '_it-exchange-invoice-recurring-data',
				'compare' => 'EXISTS',
			)
		)
	);
	$recurring_invoices = get_posts( $args );

	foreach ( $recurring_invoices as $invoice ) {
		$recurring_data = get_post_meta( $invoice->ID, '_it-exchange-invoice-recurring-data', true );

		$today = new DateTime( date_i18n( 'Y/m/d', strtotime( 'midnight' ) ) );
		$publish_date = new DateTime( date_i18n( 'Y/m/d', strtotime( 'midnight', strtotime( $invoice->post_date ) ) ) );
		$diff = $today->diff( $publish_date );

		$interval_count = $recurring_data['recurring_interval_count'];
		$interval = $recurring_data['recurring_interval'];

		$send_invoice = false;
		switch ( $interval ) {
		    case 'day':
		    	if ( 0 === $diff->days % $interval_count ) {
			    	$send_invoice = true;
		    	}
		    	break;
		    case 'week':
		    	$modulus = 7 * $interval_count;
		    	if ( 0 === $diff->days % $modulus ) {
			    	$send_invoice = true;
		    	}
		    	break;
		    case 'month':
				$months_since_publish = ( $diff->format( '%y' ) * 12 ) + $diff->format( '%m' );
		    	if ( 0 === $months_since_publish % $interval_count
		    			&& $today->format( 'j' ) === $publish_date->format( 'j' ) //Make sure it's the same day!
		    		) {
			    	$send_invoice = true;
		    	}
		    	break;
		    case 'year':
		    	if ( 0 === $diff->y % $interval_count
		    			&& $today->format( 'j' ) === $publish_date->format( 'j' ) //Make sure it's the same day!
						&& $today->format( 'n' ) === $publish_date->format( 'n' )  //Make sure it's the same month!
					) {
			    	$send_invoice = true;
		    	}
		    	break;
		}

		if ( $send_invoice ) {
			// we need to copy this invoice, create a new copy and send it!
			$new_invoice = clone $invoice;
			unset( $new_invoice->ID );
			$new_invoice->post_parent = $invoice->ID;
			unset( $new_invoice->post_date );
			unset( $new_invoice->post_date_gmt );

			$invoice_id = wp_insert_post( $new_invoice );
			if ( !empty( $invoice_id ) ) {
				$duplicate_product_post_meta = get_post_meta( $invoice->ID );
				foreach ( $duplicate_product_post_meta as $key => $values ) {
					foreach ( $values as $value ) {
						//We do not want to copy ALL of the post meta, some of it is specific to transaction history, etc.
						if ( in_array( $key, apply_filters( 'it_exchange_invoice_addon_duplicate_product_meta_invalid_keys', array( '_edit_lock', '_edit_last', '_it_exchange_transaction_id', '_it-exchange-invoice-data' ) ) ) ) {
							continue;
						}
						$value = maybe_unserialize( $value );
						add_post_meta( $invoice_id, $key, $value );
					}
				}

				$invoice_data = get_post_meta( $invoice->ID, '_it-exchange-invoice-data', true );
				$invoice_data['date_issued'] = date_i18n( 'U' ); //We want today's date!
				unset( $invoice_data['transaction_id'] );
				unset( $invoice_data['recurring_enabled'] );
				unset( $invoice_data['recurring_interval_count'] );
				unset( $invoice_data['recurring_interval'] );
				if ( !empty( $invoice_data['number'] ) ) {
					$invoice_data['number'] .= '-' . $invoice_id;
				}
				update_post_meta( $invoice_id, '_it-exchange-invoice-data', $invoice_data );
				if ( ! empty( $invoice_data['send_recurring_emails'] ) ) {
					it_exchange_invoice_addon_send_invoice( $invoice_id );
				}
			} else {
				if ( is_wp_error( $invoice_id ) ) {
					error_log( $invoice_id->get_error_message() );
				} else {
					error_log( 'wut' );
				}
			}
		}

	}
}
add_action( 'it_exchange_invoice_addon_daily_schedule', 'it_exchange_invoice_addon_handle_auto_invoices' );

/**
 * Add settings to the coupon form.
 *
 * @since 1.9.0
 *
 * @param ITForm $form
 */
function it_exchange_invoices_add_coupon_settings( $form ) {

	$post_id = empty( $_GET['post'] ) ? false : $_GET['post'];

	$form->set_option( 'invoices', get_post_meta( $post_id, '_it_exchange_use_invoices', true ) );
	?>

	<div class="field invoices">
		<p>
			<?php $form->add_check_box( 'invoices' ); ?>
			<label for="invoices">
				<?php _e( 'Allow to be used on invoices', 'LION' ); ?>
			</label>
		</p>
	</div>

<?php

}

add_action( 'it_exchange_basic_coupons_coupon_edit_tab_product', 'it_exchange_invoices_add_coupon_settings' );

/**
 * Save coupon settings.
 *
 * @since 1.9.0
 *
 * @param array $data
 *
 * @return array
 */
function it_exchange_invoices_save_coupon_settings( $data ) {

	$data['post_meta']['_it_exchange_use_invoices'] = $data['invoices'];

	return $data;
}

add_filter( 'it_exchange_basic_coupons_save_coupon', 'it_exchange_invoices_save_coupon_settings' );

/**
 * Validate the coupon for a particular product.
 *
 * @since 1.9.0
 *
 * @param bool                    $valid
 * @param array                   $cart_product
 * @param IT_Exchange_Cart_Coupon $coupon
 *
 * @return bool
 */
function it_exchange_invoices_validate_coupon( $valid, $cart_product, $coupon ) {

	$product_id = $cart_product['product_id'];

	if ( it_exchange_get_product_type( $product_id ) === 'invoices-product-type' ) {
		$valid = (bool) get_post_meta( $coupon->get_ID(), '_it_exchange_use_invoices', true );
	}

	return $valid;
}

add_filter( 'it_exchange_basic_coupons_valid_product_for_coupon', 'it_exchange_invoices_validate_coupon', 10, 3 );

/**
 * Get the total price of the invoice.
 *
 * @since 1.9.0
 */
function it_exchange_invoices_sw_ajax_get_total() {
	die( it_exchange_get_cart_total() );
}

add_action( 'it_exchange_processing_super_widget_ajax_invoices-get-total', 'it_exchange_invoices_sw_ajax_get_total' );

/**
 * Remove the coupon template part from the SuperWidget.
 *
 * @since 1.9.0
 *
 * @param array $parts
 *
 * @return array
 */
function it_exchange_invoices_remove_coupon_template_from_sw( $parts ) {

	if ( it_exchange_get_product_type( it_exchange_get_the_product_id() ) === 'invoices-product-type' ) {

		$coupons = it_exchange_get_coupons( array(
			'meta_query' => array(
				array(
					'key'       => '_it_exchange_use_invoices',
					'value'     => true
				)
			),
			'numberposts' => 1
		) );

		if ( empty( $coupons ) ) {
			$i = array_search( 'single-item-update-coupons', $parts );

			if ( $i !== false ) {
				unset( $parts[ $i ] );
			}
		}
	}

	return $parts;
}

add_filter( 'it_exchange_get_super-widget-checkout_single-item-cart-actions_elements', 'it_exchange_invoices_remove_coupon_template_from_sw' );