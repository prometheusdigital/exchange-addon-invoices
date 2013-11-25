<?php
/**
 * This will associate invoices with any product types who register invoice support.
 * By default, it registers a metabox on the product's add/edit screen and provides HTML / data for the frontend.
 *
 * @since 1.0.0
 * @package IT_Exchange
*/


class IT_Exchange_Product_Feature_Invoices {

	/**
	 * Constructor. Registers hooks
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Product_Feature_Invoices() {
		if ( is_admin() ) {
			add_action( 'load-post-new.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'load-post.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'it_exchange_save_product', array( $this, 'save_feature_on_product_save' ) );
		}
		add_action( 'it_exchange_enabled_addons_loaded', array( $this, 'register_feature_support' ) );
		add_action( 'it_exchange_enabled_addons_loaded', array( $this, 'add_feature_support_to_invoices' ) );
		add_action( 'it_exchange_update_product_feature_invoices', array( $this, 'save_feature' ), 9, 3 );
		add_filter( 'it_exchange_get_product_feature_invoices', array( $this, 'get_feature' ), 9, 3 );
		add_filter( 'it_exchange_product_has_feature_invoices', array( $this, 'product_has_feature') , 9, 2 );
		add_filter( 'it_exchange_product_supports_feature_invoices', array( $this, 'product_supports_feature') , 9, 2 );
	}

	/**
	 * Register the product feature and add it to enabled product-type addons
	 *
	 * @since 1.0.0
	*/
	function register_feature_support() {
		// Register the product feature
		$slug        = 'invoices';
		$description = 'Registers the product features assoiciated with Invoices';
		it_exchange_register_product_feature( $slug, $description );
	}

	/**
	 * Register invoices to the Digital invoices add-on by default
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function add_feature_support_to_invoices() {
		if ( it_exchange_is_addon_enabled( 'invoices-product-type' ) )
			it_exchange_add_feature_support_to_product_type( 'invoices', 'invoices-product-type' );
	}

	/**
	 * Register's the metabox for any product type that supports the feature
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function init_feature_metaboxes() {
		global $post;

		if ( isset( $_REQUEST['post_type'] ) ) {
			$post_type = $_REQUEST['post_type'];
		} else {
			if ( isset( $_REQUEST['post'] ) )
				$post_id = (int) $_REQUEST['post'];
			elseif ( isset( $_REQUEST['post_ID'] ) )
				$post_id = (int) $_REQUEST['post_ID'];
			else
				$post_id = 0;

			if ( $post_id )
				$post = get_post( $post_id );

			if ( isset( $post ) && !empty( $post ) )
				$post_type = $post->post_type;
		}

		if ( !empty( $_REQUEST['it-exchange-product-type'] ) )
			$product_type = $_REQUEST['it-exchange-product-type'];
		else
			$product_type = it_exchange_get_product_type( $post );

		if ( !empty( $post_type ) && 'it_exchange_prod' === $post_type ) {
			if ( !empty( $product_type ) &&  it_exchange_product_type_supports_feature( $product_type, 'invoices' ) )
				add_action( 'it_exchange_product_metabox_callback_' . $product_type, array( $this, 'register_metabox' ) );
		}
	}

	/**
	 * Registers the feature metabox for a specific product type
	 *
	 * Hooked to it_exchange_product_metabox_callback_[product-type] where product type supports this feature
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function register_metabox() {
		add_meta_box( 'it-exchange-product-invoices', __( 'Invoice Details', 'LION' ), array( $this, 'print_metabox' ), 'it_exchange_prod', 'it_exchange_normal', 'low' );
	}

	/**
	 * This echos the feature metabox.
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function print_metabox( $post ) {
		// Grab the iThemes Exchange Product object from the WP $post object
		$product = it_exchange_get_product( $post );

		// Set the value of the feature for this product
		$invoice_data = it_exchange_get_product_feature( $product->ID, 'invoices' );

		// Defaults
		$defaults = array(
			'client' => 0,
			'date_issued'  => date( 'Y-m-d' ),
			'company'      => '',
			'number'       => '',
			'emails'       => '',
			'po'           => '',
			'send_emails'  => 0,
			'terms'        => 0,
			'notes'        => '',
			'use_password' => 0,
			'password'     => '',
		);
		$invoice_data = ITUtility::merge_defaults( $invoice_data, $defaults );
		?>
		<label for="it-exchange-invoice-details-field"><?php _e( 'Invoice Details', 'LION' ); ?> <span class="tip" title="">i</span></label>
		<div class="sections-wrapper">
			<div class="invoice-section section-one">
				<div class="invoice-field-container invoice-field-container-left invoice-field-container-client-id">
					<label class="invoice-field-label">
						<?php _e( 'Client', 'LION' ); ?>
					</label>
					<input type="text" name="it-exchange-invoices-client-id" />
				</div>
				<div class="invoice-field-container invoice-field-container-right invoice-field-container-date-issued">
					<label class="invoice-field-label">
						<?php _e( 'Date Issued', 'LION' ); ?>
					</label>
					<input type="text" name="it-exchange-invoices-date-issued" />
				</div>
				<div class="invoice-field-container invoice-field-container-left invoice-field-container-company">
					<label class="invoice-field-label">
						<?php _e( 'Company', 'LION' ); ?>
					</label>
					<input type="text" name="it-exchange-invoices-company" />
				</div>
				<div class="invoice-field-container invoice-field-container-right invoice-field-container-number">
					<label class="invoice-field-label">
						<?php _e( 'Invoice #', 'LION' ); ?>
					</label>
					<input type="text" name="it-exchange-invoices-number" />
				</div>
				<div class="invoice-field-container invoice-field-container-left invoice-field-container-emails">
					<label class="invoice-field-label">
						<?php _e( 'Client Email Address', 'LION' ); ?>
					</label>
					<input type="text" name="it-exchange-invoices-emails" />
				</div>
				<div class="invoice-field-container invoice-field-container-right invoice-field-container-po">
					<label class="invoice-field-label">
						<?php _e( 'P.O. Number', 'LION' ); ?>
					</label>
					<input type="text" name="it-exchange-invoices-po" />
				</div>
				<div class="invoice-field-container invoice-field-container-send-emails">
					<input type="checkbox" value="1" name="it-exchange-invoices-send-emails" />&nbsp;
					<label class="invoice-field-label"><?php _e( 'Send email automatically when invoice is published?', 'LION' ); ?></label>
				</div>
			</div>
			<div class="invoice-section section-two">
				<div class="invoice-field-container invoice-field-container-terms">
					<label class="invoice-field-label">
						<?php _e( 'Terms', 'LION' ); ?>
					</label>
					<select name="it-exchange-invoices-terms">
						<option value="0"><?php _e( 'Select a term', 'LION' ); ?>
					</select>
				</div>
			</div>
			<div class="invoice-section section-three">
				<div class="invoice-field-container invoice-field-container-notes">
					<label class="invoice-field-label">
						<?php _e( 'Notes', 'LION' ); ?>
					</label>
					<textarea name="it-exchange-invoices-notes"></textarea>
				</div>
			</div>
			<div class="invoice-section section-four">
				<div class="invoice-field-container invoice-field-container-use-password">
					<input type="checkbox" value="1" class="it-exchange-checkbox-enable" name="it-exchange-invoices-use-password" />&nbsp;
					<label class="invoice-field-label"><?php _e( 'Password protect this invoice?', 'LION' ); ?></label>
				</div>
				<div class="invoice-field-container invoice-field-container-password it-exchange-invoices-use-password hide-if-js">
					<input type="text" name="it-exchange-invoices-password" />
					<a href class="dice" title="Generate a random password."><img src="<?php echo esc_attr( ITUtility::get_url_from_file( dirname( dirname( __FILE__ ) ) ) ); ?>/images/dice-t.png" /></a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * This saves the invoices value
	 *
	 * @since 1.0.0
	 * @param object $post wp post object
	 * @return void
	*/
	function save_feature_on_product_save() {
		// Abort if we can't determine a product type
		if ( ! $product_type = it_exchange_get_product_type() )
			return;

		// Abort if we don't have a product ID
		$product_id = empty( $_POST['ID'] ) ? false : $_POST['ID'];
		if ( ! $product_id )
			return;

		// Abort if this product type doesn't support invoices
		if ( ! it_exchange_product_type_supports_feature( $product_type, 'invoices' ) )
			return;

		// Update Invoice Client
		$client = isset( $_POST['it-exchange-invoices-client-id'] ) ? $_POST['it-exchange-invoices-client-id'] : 0;

		// Update Invoice Date Issued
		$date_issued = empty ( $_POST['it-exchange-invoices-date-issued'] ) ? date( 'Y-m-d' ) : $_POST['it-exchange-invoices-date-issued'];

		// Update Invoice Company
		$company = empty( $_POST['it-exchange-invoices-company'] ) ? '' : $_POST['it-exchange-invoices-company'];

		// Update Invoice Number
		$number = empty( $_POST['it-exchange-invoices-number'] ) ? '' : $_POST['it-exchange-invoices-number'];

		// Update Invoice Client Email Addresses
		$emails = empty( $_POST['it-exchange-invoices-emails'] ) ? '' : $_POST['it-exchange-invoices-emails'];

		// Update Invoice PO Number 
		$po= empty( $_POST['it-exchange-invoices-po'] ) ? '' : $_POST['it-exchange-invoices-po'];

		// Update Invoice Send Email on Creation
		$send_emails = ! empty( $_POST['it-exchange-invoices-send-emails'] );

		// Update Invoice Terms
		$terms = empty( $_POST['it-exchange-invoices-terms'] ) ? 0 : $_POST['it-exchange-invoices-terms'];

		// Update Invoice Notes
		$notes = empty( $_POST['it-exchange-invoices-notes'] ) ? '' : $_POST['it-exchange-invoices-notes'];

		// Update Invoice Use Password Boolean
		$use_password = ! empty( $_POST['it-exchange-invoices-use-password'] );

		// Update Invoice Password
		$password = empty( $_POST['it-exchange-invoices-password'] ) ? '' : $_POST['it-exchange-invoices-password'];

		// Update Invoice Status
		$status = empty( $_POST['it-exchange-invoices-status'] ) ? 0 : $_POST['it-exchange-invoices-status'];

		$data = compact( 'client', 'date_issued', 'company', 'number', 'emails', 'po', 'send_emails', 'terms', 'notes', 'use_password', 'password', 'status' );
		$data = apply_filters( 'it_exchange_invoices_save_feature_on_product_save', $data );

		it_exchange_update_product_feature( $product_id, 'invoices', $data );
	}

	/**
	 * This updates the feature for a product
	 *
	 * @since 1.0.0
	 *
	 * @param integer $product_id the product id
	 * @param mixed $new_value the new value
	 * @return bolean
	*/
	function save_feature( $product_id, $new_value, $options=array() ) {

		if ( ! it_exchange_get_product( $product_id ) )
			return false;

        // Using options to determine if we're setting the invoice limit or adding/updating files
        $defaults = array();
        $options = ITUtility::merge_defaults( $options, $defaults );

		$existing_data = get_post_meta( $product_id, '_it-exchange-invoice-data', true );
		$data = ITUtility::merge_defaults( $new_value, $existing_data );

		update_post_meta( $product_id, '_it-exchange-invoice-data', $data );
	}

	/**
	 * Return the product's features
	 *
	 * @since 1.0.0
	 * @param mixed $existing the values passed in by the WP Filter API. Ignored here.
	 * @param integer product_id the WordPress post ID
	 * @return string product feature
	*/
	function get_feature( $existing, $product_id, $options=array() ) {

        // Using options to determine if we're getting the invoice limit or adding/updating files
        $defaults = array();
        $options = ITUtility::merge_defaults( $options, $defaults );

		return  get_post_meta( $product_id, '_it-exchange-invoice-data', true );
	}

	/**
	 * Does the product have the feature?
	 *
	 * @since 1.0.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @return boolean
	*/
	function product_has_feature( $result, $product_id, $options=array() ) {
		// Does this product type support this feature?
		if ( false === $this->product_supports_feature( false, $product_id, $options ) )
			return false;
		return (boolean) $this->get_feature( false, $product_id, $options );
	}

	/**
	 * Does the product support this feature?
	 *
	 * This is different than if it has the feature, a product can
	 * support a feature but might not have the feature set.
	 *
	 * @since 1.0.0
	 * @param mixed $result Not used by core
	 * @param integer $product_id
	 * @return boolean
	*/
	function product_supports_feature( $result, $product_id ) {
		// Does this product type support this feature?
		$product_type = it_exchange_get_product_type( $product_id );
		return it_exchange_product_type_supports_feature( $product_type, 'invoices' );
	}
}
$IT_Exchange_Product_Feature_Invoices = new IT_Exchange_Product_Feature_Invoices();
