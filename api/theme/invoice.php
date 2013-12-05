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
		'to'            => 'to',
		'from'          => 'from',
		'issueddate'    => 'issued_date',
		'paiddate'      => 'paid_date',
		'invoicenumber' => 'invoice_number',
		'ponumber'      => 'po_number',
		'description'   => 'description',
		'notes'         => 'notes',
		'terms'         => 'terms',
		'paymentamount' => 'payment_amount',
		'paymentstatus' => 'payment_status',
	);

	/**
	 * Current product in iThemes Exchange Global
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
	function IT_Theme_API_Invoice() {
		// Set the current global product as a property
		$this->product = empty( $GLOBALS['it_exchange']['product'] ) ? false : $GLOBALS['it_exchange']['product'];
		$this->meta    = it_exchange_get_product_feature( $this->product->ID, 'invoices' );
		$this->client  = it_exchange_get_customer( $this->meta['client'] );
	}

	/**
	 * Returns the context. Also helps to confirm we are an iThemes Exchange theme API class
	 *
	 * @since 1.0.0
	 *
	 * @return string
	*/
	function get_api_context() {
		return $this->_context;
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
			'fields' => array(
				'name',
				'company',
				'address',
			),
        );   
        $options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-from-block' : 'it-exchange-invoice-from-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];

		$from    = it_exchange_get_customer( $this->product->post_author );
		$name    = empty( $from->data->display_name ) ? false : $from->data->display_name;

		/*****
		 * TEMP ADDRESS DATA
		*****/
		$this->meta['address'] = '123 Main St.<br />Oklahoma City, OK 12345';
		$this->meta['company'] = 'My Store';

		// Build the Value
		$value   = array();
		if ( in_array( 'name', $options['fields'] ) && ! empty ( $name ) )
			$value[] = $name;
		if ( in_array( 'company', $options['fields'] ) && ! empty( $this->meta['company'] ) )
			$value[] = $this->meta['company'];
		if ( in_array( 'address', $options['fields'] ) && ! empty( $this->meta['address'] ) )
			$value[] = $this->meta['address'];
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
				'address',
			),
        );   
        $options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-to-block' : 'it-exchange-invoice-to-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$name    = empty( $this->client->data->display_name ) ? false : $this->client->data->display_name;

		/*****
		 * TEMP ADDRESS DATA
		*****/
		$this->meta['address'] = '123 Main St.<br />Wake Forest, NC 23456';

		// Build the Value
		$value   = array();
		if ( in_array( 'name', $options['fields'] ) && ! empty ( $name ) )
			$value[] = $name;
		if ( in_array( 'company', $options['fields'] ) && ! empty( $this->meta['company'] ) )
			$value[] = $this->meta['company'];
		if ( in_array( 'address', $options['fields'] ) && ! empty( $this->meta['address'] ) )
			$value[] = $this->meta['address'];
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
		$value   = empty( $this->meta['date_issued'] ) ? '' : $this->meta['date_issued'];

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
	function paid_date( $options=array() ) {
        // Return boolean if has flag was set.
        if ( $options['supports'] )
            return true;

        // Return boolean if has flag was set
        if ( $options['has']  )
			return ! empty( $this->meta['date_paid'] );

        // Parse options
        $defaults      = array(
            'format'      => 'html',
			'class'  => false,
			'label' => __( 'Paid', 'LION' ),
        );   
        $options   = ITUtility::merge_defaults( $options, $defaults );

		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-date-paid-block' : 'it-exchange-invoice-date-paid-block ' . $options['class'];
		$label   = empty( $options['label'] ) ? '' : $options['label'];
		$value   = empty( $this->meta['date_paid'] ) ? '' : $this->meta['date_paid'];

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
	function terms( $options=array() ) {
        // Return boolean if has flag was set.
        if ( $options['supports'] )
            return true;

        // Return boolean if has flag was set
        if ( $options['has']  )
			return ! empty( $this->meta['terms'] );

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

		$value   = 'This is some temp txt to show what the terms section will look like. It will be a sentance.';

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
            'format'      => 'html',
			'class'  => false,
			'label' => __( 'Invoice Number', 'LION' ),
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
	function po_number( $options=array() ) {
        // Return boolean if has flag was set.
        if ( $options['supports'] )
            return true;

        // Return boolean if has flag was set
        if ( $options['has']  )
			return ! empty( $this->meta['po'] );

        // Parse options
        $defaults      = array(
            'format'      => 'html',
			'class'  => false,
			'label' => __( 'P.O. Number', 'LION' ),
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
		$value   = empty( $this->meta['payment'] ) ? '' : $this->meta['payment'];

		$value   = it_exchange_format_price( '30' );

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
	function payment_status( $options=array() ) {
        // Return boolean if has flag was set.
        if ( $options['supports'] )
            return true;

        // Return boolean if has flag was set
        if ( $options['has']  )
			return ! empty( $this->meta['asdfas'] );

        // Parse options
        $defaults      = array(
            'format' => 'html',
			'class'  => false,
        );   
        $options   = ITUtility::merge_defaults( $options, $defaults );

		$value   = empty( $this->meta['terms'] ) ? '' : $this->meta['terms'];
		$classes = empty( $options['class'] ) ? 'it-exchange-invoice-payment-status-block it-exchange-invoice-payment-status-block-' . esc_attr( $value ) : 'it-exchange-invoice-payment-status-block it-exchange-invoice-payment-status-block-' . esc_attr( $value ) . ' ' . $options['class'];
		$label   = 'Paid';

		$value   = 'paid';

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
}