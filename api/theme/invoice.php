<?php
/**
 * Invoice Product class for THEME API
 *
 * @since 1.0.0
*/
class IT_Theme_API_Invoice implements IT_Theme_API {

	/**
	 * API context
	 * @var string $_context
	 * @since 1.0.0
	*/
	private $_context = 'invoice';

	/**
	 * API context
	 * @var array $meta
	 * @since 1.0.0
	*/
	private $meta = array();

	/**
	 * Maps api tags to methods
	 * @var array $_tag_map
	 * @since 1.0.0
	*/
	var $_tag_map = array(
		'print'         => 'print_link',
		'to'            => 'to',
		'from'          => 'from',
		'issueddate'    => 'issued_date',
		'dateissued'    => 'issued_date',
		'paiddate'      => 'paid_date',
		'invoicenumber' => 'invoice_number',
		'ponumber'      => 'po_number',
		'title'         => 'title',
		'description'   => 'description',
		'notes'         => 'notes',
		'terms'         => 'terms',
		'datedue'       => 'date_due',
		'totaldue'      => 'total_due',
		'paymentamount' => 'payment_amount',
		'paymentstatus' => 'payment_status',
		'paymentmethod' => 'payment_method',
		'permalink'     => 'permalink',
	);

	/**
	 * Current product in ExchangeWP Global
	 * @var object $product
	 * @since 1.0.0
	*/
	private $product;

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function __construct() {
		// Set the current global product as a property
		$this->product = empty( $GLOBALS['it_exchange']['product'] ) ? false : $GLOBALS['it_exchange']['product'];
		$this->meta    = it_exchange_get_product_feature( $this->product->ID, 'invoices' );
		$this->client  = it_exchange_get_customer( $this->meta['client'] );
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 *
	 * @return void
	*/
	function IT_Theme_API_Invoice() {
		self::__construct();
	}

	/**
	 * Returns the context. Also helps to confirm we are an ExchangeWP theme API class
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function get_api_context() {
		return $this->_context;
	}

	/**
	 * Returns the print button
	 *
	 * @since 1.0.0
	 * @return string
	*/
	function print_link( $options=array() ) {
		$defaults = array(
			'label'  => __( 'Print Invoice', 'LION' ),
			'before' => '<a class="it-exchange-print-invoice-link" href="#">',
			'after' => '</a>',
		);

		$options = wp_parse_args( $options, $defaults );

		return $options['before'] . $options['label'] . $options['after'];
	}

	/**
	 * Returns the from data
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function from( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has'] )
			return true;

		// Parse options
		$defaults      = array(
			'format' => 'html',
			'class'  => false,
			'label'  => __( 'From', 'LION' ),
			'tax_label' => __( 'Tax ID: ', 'LION' ),
			'fields' => array(
				'name',
				'company',
				'address',
				'email',
			),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-from-block' : 'it-exchange-invoice-from-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];

		$from    = it_exchange_get_customer( $this->product->post_author );
		$name    = empty( $from->data->display_name ) ? false : $from->data->display_name;

		// Exchange General Settings
		$general = it_exchange_get_option( 'settings_general' );
		$company = empty( $general['company-name'] ) ? '' : $general['company-name'];
		$address = empty( $general['company-address'] ) ? '' : $general['company-address'];
		$email   = empty( $general['company-email'] ) ? '' : $general['company-email'];
		$tax_id  = empty( $general['company-tax-id'] ) ? '' : $general['company-tax-id'];


		// Build the Value
		$value   = array();
		if ( in_array( 'name', $options['fields'] ) && ! empty ( $name ) )
			$value[] = $name;
		if ( in_array( 'company', $options['fields'] ) && ! empty( $company ) )
			$value[] = $company;
		if ( in_array( 'tax-id', $options['fields'] ) && ! empty( $tax_id ) )
			$value[] = $options['tax_label'] . $tax_id;
		if ( in_array( 'address', $options['fields'] ) && ! empty( $address ) )
			$value[] = nl2br( $address );
		if ( in_array( 'email', $options['fields'] ) && ! empty( $email ) )
			$value[] = $email;
		$value = implode( $value, '<br />' );

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span>';
				$return .= '	<span class="value">' . $value . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function to( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has'] )
			return true;

		// Parse options
		$defaults      = array(
			'format' => 'html',
			'class'  => false,
			'label'  => __( 'To', 'LION' ),
			'fields' => array(
				'name',
				'company',
				'email',
				'address',
			),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-to-block' : 'it-exchange-invoice-to-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$name    = empty( $this->client->data->display_name ) ? false : $this->client->data->display_name;
		$email   = empty( $this->client->data->user_email ) ? false : $this->client->data->user_email;

		// Build the Value
		$value   = array();
		if ( in_array( 'name', $options['fields'] ) && ! empty ( $name ) )
			$value[] = $name;
		if ( in_array( 'company', $options['fields'] ) && ! empty( $this->meta['company'] ) )
			$value[] = $this->meta['company'];
		if ( in_array( 'email', $options['fields'] ) && ! empty( $email ) )
			$value[] = $email;
		if ( in_array( 'address', $options['fields'] ) && ! empty( $this->meta['address'] ) )
			$value[] = nl2br( $this->meta['address'] );
		$value = implode( $value, '<br />' );

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span>';
				$return .= '	<span class="value">' . $value . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function issued_date( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has'] )
			return true;

		// Parse options
		$defaults      = array(
			'format'      => 'html',
			'class'  => false,
			'label' => __( 'Issued', 'LION' ),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-date-issued-block' : 'it-exchange-invoice-date-issued-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$unix    = empty( $this->meta['date_issued'] ) ? '' : $this->meta['date_issued'];
		$value   = empty( $unix ) ? '' : date( get_option( 'date_format' ), $unix );

		switch( $options['format'] ) {
			case 'unix' :
				return $unix;
				break;
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span>';
				$return .= '	<span class="value">' . $value . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function paid_date( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has']  )
			return (bool) it_exchange_invoice_addon_get_invoice_transaction_id( $this->product->ID );

		// Parse options
		$defaults      = array(
			'format'      => 'html',
			'class'  => false,
			'label' => __( 'Paid', 'LION' ),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-date-paid-block' : 'it-exchange-invoice-date-paid-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$value   = it_exchange_invoice_addon_get_invoice_transaction_id( $this->product->ID );
		$value   = empty( $value ) ? false : it_exchange_get_transaction_date( $value );

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span>';
				$return .= '	<span class="value">' . $value . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the invoice title
	 *
	 * @since 1.2.0
	 *
	 * @return string
	*/
	function title( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has'] )
			return it_exchange_product_has_feature( $this->product->ID, 'title' );

		// Parse options
		$defaults      = array(
			'format' => 'html',
			'class'  => false,
			'label'  => false,
		);
		$options   = wp_parse_args( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-title-block' : 'it-exchange-invoice-title-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$value   = it_exchange_get_product_feature( $this->product->ID, 'title' );

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				if ( ! empty( $label ) ) {
					$return .= '	<span class="label">' . $label . '</span>';
				}
				$return .= '	<span class="value">' . $value . '</span>';
				$return .= '</div>';
				if ( empty( $value ) )
					$return = '';
		}
		return $return;
	}

	/**
	 * Returns the
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function description( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has'] )
			return it_exchange_product_has_feature( $this->product->ID, 'description' );

		// Parse options
		$defaults      = array(
			'format'      => 'html',
			'class'  => false,
			'label' => __( 'Description', 'LION' ),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-description-block' : 'it-exchange-invoice-description-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$value   = it_exchange_get_product_feature( $this->product->ID, 'description' );

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span>';
				$return .= '	<span class="value">' . $value . '</span>';
				$return .= '</div>';
				if ( empty( $value ) )
					$return = '';
		}
		return $return;
	}

	/**
	 * Returns the terms
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function terms( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has']  )
			return ! ( empty( $this->meta['terms'] ) || 'none' == $this->meta['terms'] );

		// Parse options
		$defaults      = array(
			'format'      => 'html',
			'class'  => false,
			'label' => __( 'Terms', 'LION' ),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-terms-block' : 'it-exchange-invoice-terms-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$value   = empty( $this->meta['terms'] ) ? '' : $this->meta['terms'];

		$terms = it_exchange_invoice_addon_get_available_terms();
		$value = empty( $terms[$value]['description'] ) ? '' : $terms[$value]['description'];

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span>';
				$return .= '	<span class="value">' . $value . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function invoice_number( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has']  )
			return ! empty( $this->meta['number'] );

		// Parse options
		$defaults      = array(
			'format' => 'html',
			'class'  => false,
			'label'  => __( 'Invoice #', 'LION' ),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-number-block' : 'it-exchange-invoice-number-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$value   = empty( $this->meta['number'] ) ? '' : $this->meta['number'];

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span><span class="value">' . $value . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function po_number( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has']  )
			return ! empty( $this->meta['po'] );

		// Parse options
		$defaults      = array(
			'format' => 'html',
			'class'  => false,
			'label'  => __( 'P.O. #', 'LION' ),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-po-block' : 'it-exchange-invoice-po-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$value   = empty( $this->meta['po'] ) ? '' : $this->meta['po'];

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span><span class="value">' . $value . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function notes( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has']  )
			return ! empty( $this->meta['notes'] );

		// Parse options
		$defaults      = array(
			'format'      => 'html',
			'class'  => false,
			'label' => __( 'Notes', 'LION' ),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-notes-block' : 'it-exchange-invoice-notes-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$value   = empty( $this->meta['notes'] ) ? '' : $this->meta['notes'];

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span>';
				$return .= '	<span class="value">' . $value . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the due date
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function date_due( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has']  )
			return ! ( empty( $this->meta['terms'] ) || 'none' == $this->meta['terms'] );

		// Parse options
		$defaults      = array(
			'format' => 'html',
			'class'  => false,
			'label'  => __( 'Due Date:', 'LION' ),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-due-date-block' : 'it-exchange-invoice-due-date-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$unix    = it_exchange_get_invoice_due_date( $this->product->ID );
		$value   = date( get_option( 'date_format' ), $unix );

		switch( $options['format'] ) {
			case 'unix' :
				$return = $unix;
				break;
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span>';
				$return .= '	<span class="value">' . $value . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the invoice total due
	 *
	 * @since 1.2.0
	 *
	 * @return string
	*/
	function total_due( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has']  )
			return it_exchange_product_has_feature( $this->product->ID, 'base-price' );

		// Parse options
		$defaults      = array(
			'format' => 'html',
			'class'  => false,
			'label'  => __( 'Total Due:', 'LION' ),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-total-due-block' : 'it-exchange-invoice-total-due-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$value   = it_exchange_get_product_feature( $this->product->ID, 'base-price' );
		$value   = it_exchange_format_price( $value );

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span> <span class="value">' . $value . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function payment_amount( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has']  )
			return ! empty( $this->meta['payment'] );

		// Parse options
		$defaults      = array(
			'format'      => 'html',
			'class'  => false,
			'label' => __( 'Payment', 'LION' ),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-payment-amount-block' : 'it-exchange-invoice-payment-amount-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];

		if ( 'publish' !== get_post_status( $this->product->ID ) ) {
			//If it's not a published product, then we don't have proper cart, so we need to grab the base price
			$value = it_exchange_get_product_feature( $this->product->ID, 'base-price' );
		} else {
			$value = it_exchange_get_cart_total( false );
		}

		$transaction_id = it_exchange_invoice_addon_get_invoice_transaction_id( $this->product->ID );
		$value   = ! empty( $transaction_id ) ? it_exchange_get_transaction_total( $transaction_id, false ) : $value;
		$value   = it_exchange_format_price( $value );

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :

				remove_filter( 'it_exchange_api_theme_product_base_price', 'it_exchange_add_sale_information_to_base_price_theme', 20 );
				add_filter( 'it_exchange_api_theme_product_base_price', array( $this, '_add_sale_price_to_theme' ), 20, 2 );

				$this->total = $value;

				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span>';
				$return .= '	<span class="value">' . apply_filters( 'it_exchange_api_theme_product_base_price', $value, $this->product->ID ) . '</span>';
				$return .= '</div>';

				remove_filter( 'it_exchange_api_theme_product_base_price', array( $this, '_add_sale_price_to_theme' ), 20 );
				add_filter( 'it_exchange_api_theme_product_base_price', 'it_exchange_add_sale_information_to_base_price_theme', 20, 2 );
		}

		return $return;
	}

	/**
	 * Add sale price to the theme for invoice products.
	 *
	 * @since 1.9.2
	 *
	 * @param string $price
	 * @param int $product_id
	 *
	 * @return string
	 */
	public function _add_sale_price_to_theme( $price, $product_id ) {

		if ( it_exchange_is_product_sale_active( $product_id ) ) {

			$original   = it_exchange_get_product_feature( $product_id, 'base-price' );

			$price = "<del>$original</del>&nbsp;";
			$price .= "<ins>{$this->total}</ins>";
		}

		return $price;
	}

	/**
	 * Returns the
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function payment_status( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has']  )
			return true;

		// Parse options
		$defaults      = array(
			'format' => 'html',
			'class'  => false,
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		// Get transaction ID
		$transaction_id = it_exchange_invoice_addon_get_invoice_transaction_id( $this->product->ID );

		// Set status if no transaction
		if ( empty( $transaction_id ) ) {
			$date_issued = it_exchange( 'invoice', 'get-issued-date', array( 'format' => 'unix' ) );

			$terms = it_exchange_invoice_addon_get_available_terms();
			$term_time = empty( $terms[$this->meta['terms']]['seconds'] ) ? 0 : $terms[$this->meta['terms']]['seconds'];

			$status = ( ( $date_issued + $term_time ) > time() ) ? 'unpaid' : 'late';
			$status = ( 'none' == $this->meta['terms'] || 'receipt' == $this->meta['terms'] ) ? 'due-now' : $status;
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

		$value   = $status;
		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-payment-status-block it-exchange-invoice-payment-status-block-' . esc_attr( $value ) : 'it-exchange-invoice-payment-status-block it-exchange-invoice-payment-status-block-' . esc_attr( $value ) . ' ' . $options['class'];
		$label   = empty( $labels[$value] ) ? __( 'Pending', 'LION' ) : $labels[$value];

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="value">' . $label . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the payment method
	 *
	 * @since 1.2.0
	 *
	 * @return string
	*/
	function payment_method( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has']  )
			return (bool) it_exchange_invoice_addon_get_invoice_transaction_id( $this->product->ID );

		// Parse options
		$defaults      = array(
			'format' => 'html',
			'class'  => false,
			'label'  => __( 'Payment Method', 'LION' ),
		);
		$options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-payment-method-block' : 'it-exchange-invoice-payment-method-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$slug    = it_exchange_invoice_addon_get_invoice_transaction_id( $this->product->ID );
		$slug    = empty( $slug ) ? false : it_exchange_get_transaction_method( $slug );

		$value   = it_exchange_get_addon( $slug );
		$value   = empty( $value['name'] ) ? ucwords( str_replace( '-', ' ', $slug ) ) : $value['name'];

		switch( $options['format'] ) {
			case 'label' :
				$return = $label;
				break;
			case 'slug' :
				$return = $slug;
				break;
			case 'value' :
				$return = $value;
				break;
			case 'html' :
			default :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="label">' . $label . '</span>';
				$return .= '	<span class="value">' . $value . '</span>';
				$return .= '</div>';
		}
		return $return;
	}

	/**
	 * Returns the permalink for the invoice
	 *
	 * @since 1.2.0
	 *
	 * @return string
	*/
	function permalink( $options=array() ) {
		// Return boolean if has flag was set.
		if ( $options['supports'] )
			return true;

		// Return boolean if has flag was set
		if ( $options['has']  )
			return true;

		// Parse options
		$defaults      = array(
			'format' => 'html',
			'class'  => false,
			'label'  => __( 'Pay Now', 'LION' ),
		);
		$options   = wp_parse_args( $options, $defaults );
		$value = add_query_arg( 'client', $this->meta['hash'], get_permalink( $this->product->ID ) );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-permalink-block' : 'it-exchange-invoice-permalink-block' . ' ' . $options['class'];

		switch( $options['format'] ) {
			case 'label':
				return $options['label'];
				break;
			case 'value':
				return $value;
				break;
			case 'html' :
			default     :
				$return  = '<div class="' . esc_attr( $classes ) . '">';
				$return .= '	<span class="value"><a href="' . esc_attr( $value ) . '">' . $options['label'] . '</a></span>';
				$return .= '</div>';
		}
		return $return;
	}
}
