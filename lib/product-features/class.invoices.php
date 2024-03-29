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
	function __construct() {
		if ( is_admin() ) {
			add_action( 'load-post-new.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'load-post.php', array( $this, 'init_feature_metaboxes' ) );
			add_action( 'it_exchange_save_product', array( $this, 'save_feature_on_product_save' ) );
			add_action( 'admin_notices', array( $this, 'admin_notice_disabled' ) );
		}
		add_action( 'it_exchange_enabled_addons_loaded', array( $this, 'register_feature_support' ) );
		add_action( 'it_exchange_enabled_addons_loaded', array( $this, 'add_feature_support_to_invoices' ) );
		add_action( 'it_exchange_update_product_feature_invoices', array( $this, 'save_feature' ), 9, 3 );
		add_filter( 'it_exchange_get_product_feature_invoices', array( $this, 'get_feature' ), 9, 3 );
		add_filter( 'it_exchange_product_has_feature_invoices', array( $this, 'product_has_feature') , 9, 2 );
		add_filter( 'it_exchange_product_supports_feature_invoices', array( $this, 'product_supports_feature') , 9, 2 );
	}

	/**
	 * Constructor. Registers hooks
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function IT_Exchange_Product_Feature_Invoices() {
		self::__construct();
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
		$screen             = get_current_screen();
		$is_new_invoice     = ! empty( $screen->action ) && 'add' == $screen->action;

        $date_format        = get_option( 'date_format' );
        $jquery_date_format = it_exchange_php_date_format_to_jquery_datepicker_format( $date_format );

		// Grab the ExchangeWP Product object from the WP $post object
		$product = it_exchange_get_product( $post );

		// Set the value of the feature for this product
		$invoice_data = it_exchange_get_product_feature( $product->ID, 'invoices' );

	    $interval_types = array(
		    'day'   => __( 'Day(s)', 'LION' ),
		    'week'  => __( 'Week(s)', 'LION' ),
		    'month' => __( 'Month(s)', 'LION' ),
		    'year'  => __( 'Year(s)', 'LION' ),
		);
		$interval_types = apply_filters( 'it_exchange_invoices_recurring_interval_types', $interval_types );

		// Defaults
		$defaults = array(
			'client'            => 0,
			'date_issued'       => date_i18n( 'U' ),
			'company'           => '',
			'number'            => '',
			'emails'            => '',
			'additional_emails' => '',
			'address'           => '',
			'po'                => '',
			'send_emails'       => 0,
			'terms'             => 0,
			'notes'             => '',
			'use_password'      => 0,
			'password'          => '',
			'hash'              => false,
			'recurring_enabled' => false,
			'recurring_interval_count' => 1,
			'recurring_interval' => 'month',
			'send_recurring_emails' => false,
		);
		$invoice_data  = ITUtility::merge_defaults( $invoice_data, $defaults );
		$client_info   = it_exchange_get_customer( $invoice_data['client'] );
		$paid_readonly = it_exchange_invoice_addon_get_invoice_transaction_id( $product->ID ) ? 'disabled="disabled"' : false;
		if ( empty( $invoice_data['hash'] ) ) {
			?>
			<style type="text/css">
				a#post-preview{display:none;}
			</style>
			<?php
		}
		if ( ! empty( $paid_readonly ) ) :
		?>
		<script type="text/javascript">
			jQuery( document ).ready( function(){
				jQuery('#it-exchange-product-description-field').attr('disabled','disabled');
			});
		</script>
		<?php endif; ?>
		<label for="it-exchange-invoice-details-field"><?php _e( 'Invoice Details', 'LION' ); ?></label>
		<div class="sections-wrapper">
			<div class="invoice-section section-customer-select <?php echo empty( $invoice_data['client'] ) ? '' : 'hide-if-js'; ?>">
				<div class="invoice-field-container invoice-field-container-client-type">
					<label for="it-exchange-invoices-client-type" class="invoice-field-label">
						<?php _e( 'New or Existing Client', 'LION' ); ?>
					</label>
					<label for="it-exchange-client-type-new"><input type="radio" id="it-exchange-client-type-new" checked="checked" class="it-exchange-client-type" name="it-exchange-client-type" value="new" />&nbsp;<?php _e( 'New Client', 'LION' ); ?></label>
					<label for="it-exchange-client-type-existing"><input type="radio" id="it-exchange-client-type-existing" class="it-exchange-client-type" name="it-exchange-client-type" value="existing" />&nbsp;<?php _e( 'Existing Client', 'LION' ); ?></label>
				</div>
			</div>
			<div class="invoice-section section-customer-new <?php echo empty( $invoice_data['client'] ) ? '' : 'hide-if-js'; ?>">
				<div class="invoice-field-container invoice-field-container-client-type-new">
					<label for="it-exchange-invoices-client-type-new" class="invoice-field-label">
						<?php _e( 'New Client', 'LION' ); ?>
					</label>
					<div id="it-exchange-invoices-new-client-error" class="hide-if-js"><span class="error-message"></span></div>
				</div>
				<div class="invoice-field-container invoice-field-container-left invoice-field-container-client-type-new-first-name">
					<input type="text" id="it-exchange-client-type-new-first-name" placeholder="<?php _e( 'First Name', 'LION' ); ?>" />
				</div>
				<div class="invoice-field-container invoice-field-container-right invoice-field-container-client-type-new-last-name">
					<input type="text" id="it-exchange-client-type-new-last-name" placeholder="<?php _e( 'Last Name', 'LION' ); ?>" />
				</div>
				<div class="invoice-field-container invoice-field-container-client-type-new-company">
					<input type="text" id="it-exchange-client-type-new-company" placeholder="<?php _e( 'Company Name', 'LION' ); ?>"/>
				</div>
				<div class="invoice-field-container invoice-field-container-client-type-new-email">
					<input type="text" id="it-exchange-client-type-new-email" placeholder="<?php esc_attr_e( __( 'Email Address', 'LION' ) ); ?> <?php esc_attr_e( __( '(Required)', 'LION' ) ); ?>" />
				</div>
				<div class="invoice-field-container invoice-field-container-client-type-new-use-client-address">
					<label for="it-exchange-client-type-new-use-client-address">
						<input type="checkbox" id="it-exchange-client-type-new-use-client-address" />&nbsp;<?php _e( 'Include client\'s address', 'LION' ); ?>
					</label>
					<textarea id="it-exchange-client-type-new-client-address" class="hide-if-js" placeholder="123 Client Ave..." ></textarea>
				</div>
				<div class="invoice-field-container invoice-field-container-client-type-new-custom-username">
					<label for="it-exchange-client-type-new-use-custom-username">
						<input type="checkbox" id="it-exchange-client-type-new-use-custom-username" />&nbsp;<?php _e( 'Select custom username', 'LION' ); ?>
					</label>
					<input type="text" id="it-exchange-client-type-new-custom-username" class="hide-if-js" placeholder="username" />
				</div>
				<div class="invoice-field-container invoice-field-container-client-type-new-custom-password">
					<label for="it-exchange-client-type-new-custom-password">
						<input type="checkbox" id="it-exchange-client-type-new-custom-password" />&nbsp;<?php _e( 'Select custom password', 'LION' ); ?>
					</label>
					<input type="password" class="it-exchange-client-type-new-custom-passwords hide-if-js" id="it-exchange-client-type-new-custom-pass1" placeholder="password" />
					<input type="password" class="it-exchange-client-type-new-custom-passwords hide-if-js" id="it-exchange-client-type-new-custom-pass2" placeholder="password again" />
				</div>
				<div class="invoice-field-container invoice-field-container-client-type-new-first-name clear">
					<?php wp_nonce_field( 'it-exchange-register-customer', '_exchange_register_nonce' ); ?>
					<input type="button" id="it-exchange-invoicing-create-client" class="button" value="<?php _e( 'Create Client', 'LION' ); ?>" />
				</div>
				<div class="clear"></div>
			</div>
			<div class="invoice-section section-customer-existing <?php echo ! empty( $is_new_invoice ) || ( empty( $is_new_invoice ) && ! empty( $invoice_data['client'] ) ) ? 'hide-if-js' : ''; ?>">
				<div class="invoice-field-container invoice-field-container-right invoice-field-container-client-type-existing">
					<label for="it-exchange-invoices-client-type-existing" class="invoice-field-label">
						<?php _e( 'Existing Client', 'LION' ); ?>
					</label>
					<select id="it-exchange-invoices-existing-customer-select" name="it-exchange-invoices-existing-customer-select">
						<?php $this->print_existing_client_select_options( $invoice_data['client'] ); ?>
					</select><br />
					<input type="button" id="it-exchange-invoicing-existing-client" class="button" value="<?php _e( 'Select Client', 'LION' ); ?>" />
				</div>
				<div class="clear"></div>
			</div>
			<div class="invoice-section section-one <?php echo empty( $invoice_data['client'] ) ? 'hide-if-js' : ''; ?>">
				<div class="invoice-field-container invoice-field-container-left invoice-field-container-client-id">
					<label for="it-exchange-invoices-client-id" class="invoice-field-label">
						<?php _e( 'Client', 'LION' ); ?>
					</label>
					<input type="text" class="it-exchange-invoices-client-name" value="<?php esc_attr_e( empty( $client_info->data->display_name ) ? '' : $client_info->data->display_name ); ?>" disabled />
					<?php if ( ! $paid_readonly ) : ?>
						<a id="it-exchange-invoices-edit-client" href=""><?php _e( 'Edit' ); ?></a>
					<?php endif; ?>
					<input type="hidden" id="it-exchange-invoices-client-id" name="it-exchange-invoices-client-id" value="<?php esc_attr_e( $invoice_data['client'] ); ?>" />
				</div>
				<div class="invoice-field-container invoice-field-container-right invoice-field-container-date-issued">
					<label for="it-exchange-invoices-date-issued" class="invoice-field-label">
						<?php _e( 'Date Issued', 'LION' ); ?>
					</label>
					<input <?php echo $paid_readonly; ?> type="text" id="it-exchange-invoices-date-issued" name="it-exchange-invoices-date-issued" data-jquery-date-format="<?php esc_attr_e( $jquery_date_format ); ?>" value="<?php esc_attr_e( date( $date_format, $invoice_data['date_issued'] ) ); ?>" />
				</div>
				<div class="invoice-field-container invoice-field-container-left invoice-field-container-company">
					<label for="it-exchange-invoices-company" class="invoice-field-label">
						<?php _e( 'Company', 'LION' ); ?>
					</label>
					<input <?php echo $paid_readonly; ?> type="text" id="it-exchange-invoices-company" name="it-exchange-invoices-company" value="<?php esc_attr_e( $invoice_data['company'] ); ?>" />
				</div>
				<div class="invoice-field-container invoice-field-container-right invoice-field-container-number">
					<label for="it-exchange-invoices-number" class="invoice-field-label">
						<?php _e( 'Invoice #', 'LION' ); ?>
					</label>
					<input <?php echo $paid_readonly; ?> type="text" id="it-exchange-invoices-number" name="it-exchange-invoices-number" value="<?php esc_attr_e( $invoice_data['number'] ); ?>" />
				</div>
				<div class="invoice-field-container invoice-field-container-emails">
					<label for="it-exchange-invoices-emails" class="invoice-field-label">
						<?php _e( 'Client Email Address', 'LION' ); ?>
					</label>
					<input readonly="readonly" type="text" id="it-exchange-invoices-emails" name="it-exchange-invoices-emails" value="<?php esc_attr_e( $invoice_data['emails'] ); ?>" />
				</div>
				<div class="invoice-field-container invoice-field-container-additional-emails">
					<label for="it-exchange-invoices-additional-emails" class="invoice-field-label">
						<?php _e( 'CC Email Addresses', 'LION' ); ?> <span class="tip" title="<?php esc_attr_e( __( 'A comma separated list of additional email addresses you want to receive this invoice.', 'LION' ) ); ?>">i</span>
					</label>
					<input <?php echo $paid_readonly; ?> type="text" id="it-exchange-invoices-additional-emails" name="it-exchange-invoices-additional-emails" value="<?php esc_attr_e( $invoice_data['additional_emails'] ); ?>" />
				</div>
				<div class="invoice-field-container invoice-field-container-left invoice-field-container-client-address">
					<label for="it-exchange-invoices-client-address" class="invoice-field-label">
						<?php _e( 'Client Address', 'LION' ); ?>
					</label>
					<textarea <?php echo $paid_readonly; ?> type="text" id="it-exchange-invoices-client-address" name="it-exchange-invoices-client-address"><?php echo esc_html( $invoice_data['address'] ); ?></textarea>
				</div>
				<div class="invoice-field-container invoice-field-container-right invoice-field-container-po">
					<label for="it-exchange-invoices-po" class="invoice-field-label">
						<?php _e( 'P.O. Number', 'LION' ); ?>
					</label>
					<input <?php echo $paid_readonly; ?> type="text" id="it-exchange-invoices-po" name="it-exchange-invoices-po" value="<?php esc_attr_e( $invoice_data['po'] ); ?>" />
				</div>
				<div class="invoice-field-container invoice-field-container-send-emails">
					<?php if ( empty( $invoice_data['hash'] ) ) : ?>
						<input id="it-exchange-invoices-send-emails" type="checkbox" value="1" name="it-exchange-invoices-send-emails" <?php checked( ! empty( $invoice_data['send_emails'] ) ); ?>/>&nbsp;
						<label for="it-exchange-invoices-send-emails" class="invoice-field-label"><?php _e( 'Send email automatically when invoice is published?', 'LION' ); ?></label>
					<?php else: ?>
						<label><?php _e( 'Client Link', 'LION' ); ?></label>
						<?php echo '<input id="disabled-client-link" type="text" readonly="readonly" value="' . esc_attr( add_query_arg( 'client', $invoice_data['hash'], get_permalink( $post ) ) ) . '" />'; ?>
						<br /><a id="it-exchange-invoice-resend-link" href="#" class="button" data-invoice-id="<?php esc_attr_e( $post->ID ); ?>"><?php _e( 'Resend email to client', 'LION' ); ?></a>
						<span id="it-exchange-client-link-message" class="hide-if-js"><?php _e( 'Email Sent', 'LION' ); ?></span>
					<?php endif; ?>
				</div>
			</div>
			<div class="invoice-section section-two <?php echo empty( $invoice_data['client'] ) ? 'hide-if-js' : ''; ?>">
				<div class="invoice-field-container invoice-field-container-terms">
					<label for="it-exchange-invoices-terms" class="invoice-field-label">
						<?php _e( 'Terms', 'LION' ); ?>
					</label>
					<select <?php echo $paid_readonly; ?> id="it-exchange-invoices-terms" name="it-exchange-invoices-terms">
						<?php $this->print_term_select_options( $invoice_data['terms'] ); ?>
					</select>
					<div class="it-exchange-invoice-term-descriptions">
						<?php foreach( (array) it_Exchange_invoice_addon_get_available_terms() as $term => $termdata ) : ?>
							<?php
							if ( empty( $termdata['description'] ) )
								continue;
							$visibility = $term == $invoice_data['terms'] ? '' : 'hide-if-js';
							?>
							<p class="it-exchange-invoice-term-description it-exchange-invoice-term-description-<?php esc_attr_e( $term ); ?> <?php echo $visibility; ?>"><?php echo $termdata['description']; ?></p>
						<?php endforeach; ?>
					</div>
				</div>
			</div>
			<div class="invoice-section section-three <?php echo empty( $invoice_data['client'] ) ? 'hide-if-js' : ''; ?>">
				<div class="invoice-field-container invoice-field-container-notes">
					<label for="it-exchange-invoices-notes" class="invoice-field-label">
						<?php _e( 'Notes', 'LION' ); ?> <span class="tip" title="<?php esc_attr_e( __( 'Additional notes for your client', 'LION' ) ); ?>">i</span>
					</label>
					<textarea <?php echo $paid_readonly; ?> id="it-exchange-invoices-notes" name="it-exchange-invoices-notes"><?php esc_attr_e( $invoice_data['notes'] ); ?></textarea>
				</div>
			</div>
			<?php
			$ancestors = get_post_ancestors( $post );
			if ( empty( $ancestors ) ) {
			?>
			<div class="invoice-section section-four <?php echo empty( $invoice_data['client'] ) ? 'hide-if-js' : ''; ?>">
				<div class="invoice-field-container invoice-field-container-recurring-options">
					<label for="it-exchange-invoices-recurring-options-enabled" class="invoice-field-label">
						<?php _e( 'Auto-Invoicing Options', 'LION' ); ?> <span class="tip" title="<?php esc_attr_e( __( 'When set, a new copy of this invoice to be sent to your client on the set schedule.', 'LION' ) ); ?>">i</span>
					</label>
					<label for="invoice-field-container-recurring-options-enabled">
						<input type="checkbox" id="invoice-field-container-recurring-options-enabled" name="it-exchange-invoices-recurring-options-enabled" <?php checked( $invoice_data['recurring_enabled'] ); ?> /> &nbsp; <?php _e( 'Enable Auto-Invoicing', 'LION' ); ?>
					</label>
					<?php
					if ( $invoice_data['recurring_enabled'] ) {
						$hidden = '';
					} else {
						$hidden = 'hidden';
					}
					?>
					<div id="recurring-options-enabled-div" class="<?php echo $hidden; ?>">
				        <p>
				        <label for="it-exchange-invoices-recurring-options-interval">
					        <?php _e( 'Send this client a new copy of this invoice every...', 'LION' ); ?>
				        </label>
				        &nbsp;
				        <input id="it-exchange-invoices-recurring-options-interval-count" type="number" class="small-input" name="it-exchange-invoices-recurring-options-interval-count" value="<?php echo $invoice_data['recurring_interval_count']; ?>" placeholder="#" />
				        <select id="it-exchange-invoices-recurring-options-interval" name="it-exchange-invoices-recurring-options-interval">
					        <?php
						    foreach( $interval_types as $name => $label ) {
							    echo '<option value="' . $name . '" ' . selected( $invoice_data['recurring_interval'], $name, false ) . '>' . $label . '</option>';
						    }
							?>
				        </select>
				        </p>
				        <p>
						<input id="it-exchange-invoices-send-recurring-emails" type="checkbox" value="1" name="it-exchange-invoices-recurring-emails" <?php checked( ! empty( $invoice_data['send_recurring_emails'] ) ); ?>/>&nbsp;
						<label for="it-exchange-invoices-send-recurring-emails" class="invoice-field-label"><?php _e( 'Send email automatically when invoice is automatically published?', 'LION' ); ?></label>
				        </p>
					</div>
				</div>
				<div class="invoice-field-container invoice-field-container-child-invoices">
					<p>
					<label for="it-exchange-invoices-child-invoices" class="invoice-field-label">
						<?php _e( 'Child Invoices', 'LION' ); ?>
					</label>
					<?php
					$args = array(
						'post_parent'    => $post->ID,
						'post_type'      => 'it_exchange_prod',
						'posts_per_page' => -1,
						'post_status'    => 'any'
					);
					$children = get_children( $args );
					if ( !empty( $children ) ) {
						foreach( $children as $child ) {
							$transaction_id = it_exchange_invoice_addon_get_invoice_transaction_id( $child->ID );

							// Set status if no transaction
							if ( empty( $transaction_id ) ) {
								$meta        = it_exchange_get_product_feature( $child->ID, 'invoices' );
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

							$child_invoice_data = it_exchange_get_product_feature( $child->ID, 'invoices' );
							$child_invoice_data  = ITUtility::merge_defaults( $child_invoice_data, $defaults );
							$payment_link = add_query_arg( 'client', $child_invoice_data['hash'], get_permalink( $child ) );

							$value   = empty( $labels[$status] ) ? false : $labels[$status];
							if ( $value ) {
								$payment_status = '<span class="it-exchange-invoice-addon-status">' . $value . '</span>';
							} else {
								$payment_status = '';
							}
							?>
							<p><a href="<?php echo get_edit_post_link( $child->ID ); ?>"><?php echo get_the_title( $child ); ?></a> | <?php echo date_i18n( get_option( 'date_format' ), strtotime( $child->post_date ) ); ?> | <?php echo $payment_status ?> | <a href="<?php echo $payment_link; ?>"><?php _e( 'Payment Link', 'LION' ); ?></a></p>
						<?php
						}
					} else {
						?>
						<p><?php _e( 'No Child Invoices Found.', 'LION' ); ?></p>
						<?php
					}
					?>
					</p>
				</div>
			</div>
			<?php } else { ?>
			<div class="invoice-section section-four <?php echo empty( $invoice_data['client'] ) ? 'hide-if-js' : ''; ?>">
				<div class="invoice-field-container invoice-field-container-parent-invoice">
					<label for="it-exchange-invoices-parent-invoice" class="invoice-field-label">
						<?php _e( 'Parent Invoice', 'LION' ); ?>
					</label>
					<?php
					foreach( $ancestors as $ancestor_id ) { //should only be one
						?>
						<p><a href="<?php echo get_edit_post_link( $ancestor_id ); ?>"><?php echo get_the_title( $ancestor_id ); ?></a> | <?php echo date_i18n( get_option( 'date_format' ), strtotime( get_the_date( '', $ancestor_id ) ) ); ?></p>
						<div class="it-exchange-invoice-field-container-parent-invoice-list">
						<?php
						$ancestor_invoice_data = it_exchange_get_product_feature( $ancestor_id, 'invoices' );
						if ( $ancestor_invoice_data['recurring_enabled'] ) {
							?>
							<input type="button" id="it-exchange-invoicing-cancel-auto-invoicing" class="button" data-invoice-id="<?php echo $ancestor_id; ?>" value="<?php _e( 'Stop Auto-Invoicing', 'LION' ); ?>" />
							<?php
						} else {
							echo '<p>' . __( 'Auto-invoicing has been disabled on the parent invoice.', 'LION' ) . '</p>';
						}
						?>
						</div>
						<?php
					}
					?>
					</div>
				</div>
			</div>
			<?php } ?>
		</div>
		<?php
	}

	/**
	 * Genrates the options for the terms select box
	 *
	 * @since 1.0.0
	 *
	 * @param string $selected the selected value
	 * @return string html
	*/
	function print_term_select_options( $selected=0 ) {
		$terms = it_exchange_invoice_addon_get_available_terms();

		?><option value="0" <?php selected( 0, $selected ); ?>><?php _ex( 'Select a term', 'terms for an invoice payment', 'LION' ); ?></option><?php
		foreach( $terms as $key => $props ) {
			$key = empty( $key ) ? false : $key;
			$title = empty( $props['title'] ) ? false : $props['title'];
			if ( empty( $key ) || empty( $title ) )
				continue;
			?>
			<option value="<?php esc_attr_e( $key ); ?>" <?php selected( $selected, $key ); ?>><?php echo $title; ?></option>
			<?php
		}
	}

	/**
	 * Genrates the options for the Existing Client select box
	 *
	 * @since 1.0.0
	 *
	 * @param string $selected the selected value
	 * @return string html
	*/
	function print_existing_client_select_options( $selected=0 ) {

		$args = array(
			'fields' => array( 'ID', 'display_name' )
		);
		$users = get_users( $args );

		$options = array();
		foreach( (array) $users as $user ) {
			if ( empty( $user->ID ) || empty( $user->display_name) )
				continue;
			$options[$user->ID] = $user->display_name;
		}
		$clients = apply_filters( 'it_exchange_invoices_get_existing_client_select_options', $options );

		foreach( $clients as $value => $option ) {
			?>
			<option value="<?php esc_attr_e( $value ); ?>" <?php selected( $selected, $value ); ?>><?php echo $option; ?></option>
			<?php
		}
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

		// Get the user's option set in WP General Settings
		$wp_date_format = get_option( 'date_format' );

		// strtotime requires formats starting with day to be separated by - and month separated by /
		if ( 'd' == substr( $wp_date_format, 0, 1 ) || 'j' == substr( $wp_date_format, 0, 1 ) ) {
			$date_issued = str_replace( '/', '-', $date_issued );
			$date_issued = str_replace( ' ', '-', $date_issued );
			$date_issued = str_replace( ',', '-', $date_issued );
		}

		// Transfer to epoch
		if ( $epoch = strtotime( $date_issued ) ) {
			 // Returns an array with values of each date segment
			 $date = date_parse( $date_issued );

			 // Confirms we have a legitimate date
			 if ( checkdate( $date['month'], $date['day'], $date['year'] ) )
				 $date_issued = $epoch;
		} else {
			$date_issued = time();
		}

		// Update Invoice Company
		$company = empty( $_POST['it-exchange-invoices-company'] ) ? '' : $_POST['it-exchange-invoices-company'];

		// Update Invoice Client Address
		$address = ! isset( $_POST['it-exchange-invoices-client-address'] ) ? '' : $_POST['it-exchange-invoices-client-address'];

		// Update Invoice Number
		$number = empty( $_POST['it-exchange-invoices-number'] ) ? '' : $_POST['it-exchange-invoices-number'];

		// Update Invoice Client Email Addresses
		$emails = empty( $_POST['it-exchange-invoices-emails'] ) ? '' : $_POST['it-exchange-invoices-emails'];

		// Update Invoice Additional Email Addresses
		$additional_emails = empty( $_POST['it-exchange-invoices-additional-emails'] ) ? '' : $_POST['it-exchange-invoices-additional-emails'];

		// Update Invoice PO Number
		$po= empty( $_POST['it-exchange-invoices-po'] ) ? '' : $_POST['it-exchange-invoices-po'];

		// Update Invoice Send Email on Creation
		$send_emails = ! empty( $_POST['it-exchange-invoices-send-emails'] );

		// Update Invoice Terms
		$terms = isset( $_POST['it-exchange-invoices-terms'] ) ? $_POST['it-exchange-invoices-terms'] : 0;

		// Update Invoice Notes
		$notes = empty( $_POST['it-exchange-invoices-notes'] ) ? '' : $_POST['it-exchange-invoices-notes'];

		// Update Invoice Use Password Boolean
		$use_password = ! empty( $_POST['it-exchange-invoices-use-password'] );

		// Update Invoice Password
		$password = empty( $_POST['it-exchange-invoices-password'] ) ? '' : $_POST['it-exchange-invoices-password'];

		// Update Invoice Status
		$status = empty( $_POST['it-exchange-invoices-status'] ) ? 0 : $_POST['it-exchange-invoices-status'];

		// Generate HASH to sign client in if not already generated
		$existing_settings = it_exchange_get_product_feature( $product_id, 'invoices', true );
		$hash = empty( $existing_settings['hash'] ) ? it_exchange_create_unique_hash() : $existing_settings['hash'];

		// Update Recurring Options
		$recurring_enabled = empty( $_POST['it-exchange-invoices-recurring-options-enabled'] ) ? false : true;
		$recurring_interval_count = empty( $_POST['it-exchange-invoices-recurring-options-interval-count'] ) ? 1 : $_POST['it-exchange-invoices-recurring-options-interval-count'];
		$recurring_interval = empty( $_POST['it-exchange-invoices-recurring-options-interval'] ) ? 'month' : $_POST['it-exchange-invoices-recurring-options-interval'];
		$send_recurring_emails = !empty( $_POST['it-exchange-invoices-recurring-emails'] );

		$data = compact( 'client', 'date_issued', 'company', 'address', 'number', 'emails', 'additional_emails', 'po', 'send_emails', 'terms', 'notes', 'use_password', 'password', 'status', 'hash', 'recurring_enabled', 'recurring_interval_count', 'recurring_interval', 'send_recurring_emails' );
		$data = apply_filters( 'it_exchange_invoices_save_feature_on_product_save', $data );

		it_exchange_update_product_feature( $product_id, 'invoices', $data );

		// Update Client meta data
		$client_meta =  get_user_meta( $data['client'], 'it-exchange-invoicing-meta', true );
		$client_meta['company'] = $data['company'];
		$client_meta['address'] = $data['address'];
		$client_meta['terms']   = $data['terms'];
		update_user_meta( $data['client'], 'it-exchange-invoicing-meta', $client_meta );

		// Send email to client if checked.
		if ( ! empty( $send_emails ) ) {
			it_exchange_invoice_addon_send_invoice( $product_id );
			remove_action( 'it_exchange_save_product', array( $this, 'save_feature_on_product_save' ) );
		}
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

		// A second place to save the recurring data, but it'll make it easier and faster when doing auto-invoicing.
		if ( !empty( $data['recurring_enabled'] ) && !empty( $data['recurring_interval_count'] ) && !empty( $data['recurring_interval'] ) ) {
			$recurring_data['recurring_interval_count'] = $data['recurring_interval_count'];
			$recurring_data['recurring_interval'] = $data['recurring_interval'];
			$recurring_data['send_recurring_emails'] = $data['send_recurring_emails'];
			update_post_meta( $product_id, '_it-exchange-invoice-recurring-data', $recurring_data );
		} else {
			delete_post_meta( $product_id, '_it-exchange-invoice-recurring-data' );
		}

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

	/**
	 * Display admin notice when on Edit invoice sceen that has already been paid for by the client
	 *
	 * @since 1.0.0
	 * @return void
	*/
	function admin_notice_disabled() {
		$current_screen = get_current_screen();
		if ( empty( $current_screen->base ) || 'post' != $current_screen->base )
			return;

		$product = it_exchange_get_product( false );
		$product_id = empty( $product->ID ) ? 0 : $product->ID;
		if ( empty( $current_screen->base ) || 'post' != $current_screen->base || 'invoices-product-type' != it_exchange_get_product_type( $product_id ) || ! it_exchange_invoice_addon_get_invoice_transaction_id( $product_id ) )
			return;
		?>
		<div id="it-exchange-invoices-paid-nag" class="it-exchange-nag hide-if-js">
			<?php printf( __( 'This invoice has already been paid and may no longer be edited. %sView Payment%s', 'LION' ), '<a href="' . admin_url() . 'post.php?post=' . it_exchange_invoice_addon_get_invoice_transaction_id( $product_id ) . '&action=edit">', '</a>' ); ?>
		</div>
		<script type="text/javascript">
			jQuery( document ).ready( function() {
				if ( jQuery( '.wrap > h2' ).length == '1' ) {
					jQuery("#it-exchange-invoices-paid-nag").insertAfter( '.wrap > h2' ).addClass( 'after-h2' ).fadeIn();
				}
			});
		</script>
		<?php
	}
}
$IT_Exchange_Product_Feature_Invoices = new IT_Exchange_Product_Feature_Invoices();
