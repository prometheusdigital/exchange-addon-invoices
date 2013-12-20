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
	if ( ( ! is_preview() && ! it_exchange_is_page( 'product' ) ) || 'invoices-product-type' != it_exchange_get_product_type() )
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
			<a class="frontend-link" href="<?php echo add_query_arg( 'client', $hash, $permalink ); ?>"><?php _e( 'View Invoice', 'LION' ); ?></a>
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

	return add_query_arg( 'client', $data['hash'], $link );

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
		if ( window.jQuery ) {
			jQuery(function() {
				if ( jQuery('.it-exchange-invoice-sw .it-exchange-super-widget').length || itExchangeInvoiceNotProtected ) {
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

			$terms = it_exchange_invoice_addon_get_available_terms();
			$term_time = empty( $terms[$meta['terms']]['seconds'] ) ? 0 : $terms[$meta['terms']]['seconds'];

			$status = ( ( $date_issued + $term_time ) > time() ) ? 'unpaid' : 'late';
			$status = ( 'none' == $meta['terms'] || 'receipt' == $meta['terms'] ) ? 'due-now' : $status;
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
