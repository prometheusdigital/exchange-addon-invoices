<?php
/**
 * Contains the invoices email tags.
 *
 * @since   1.10.0
 * @license GPLv2
 */

new IT_Exchange_Invoices_Email_Register_Tags();

/**
 * Class IT_Exchange_Invoices_Email_Register_Tags
 */
class IT_Exchange_Invoices_Email_Register_Tags {

	/**
	 * IT_Exchange_Invoices_Email_Register_Tags constructor.
	 */
	public function __construct() {
		add_action( 'it_exchange_email_notifications_register_tags', array( $this, 'register' ) );
	}

	/**
	 * Register the default tags.
	 *
	 * @since 1.10
	 *
	 * @param IT_Exchange_Email_Tag_Replacer $replacer
	 */
	public function register( IT_Exchange_Email_Tag_Replacer $replacer ) {

		$tags = array(
			'invoice_number'    => array(
				'name'      => __( 'Invoice Number', 'LION' ),
				'desc'      => __( 'The invoice number.', 'LION' ),
				'context'   => array( 'invoice' ),
				'available' => array( 'new-invoice' ),
				'meta'      => 'number'
			),
			'invoice_po_number' => array(
				'name'      => __( 'PO Number', 'LION' ),
				'desc'      => __( 'The PO number of the invoice.', 'LION' ),
				'context'   => array( 'invoice' ),
				'available' => array( 'new-invoice' ),
				'meta'      => 'po'
			),
			'client_name'       => array(
				'name'      => __( 'Client Name', 'LION' ),
				'desc'      => __( "The client's display name.", 'LION' ),
				'context'   => array( 'invoice' ),
				'available' => array( 'new-invoice' )
			),
			'client_email'      => array(
				'name'      => __( 'Client Email', 'LION' ),
				'desc'      => __( "The client's email address.", 'LION' ),
				'context'   => array( 'invoice' ),
				'available' => array( 'new-invoice' )
			),
			'client_username'   => array(
				'name'      => __( 'Client Username', 'LION' ),
				'desc'      => __( "The username your client needs to log in to your site.", 'LION' ),
				'context'   => array( 'invoice' ),
				'available' => array( 'new-invoice' )
			),
			'client_company'    => array(
				'name'      => __( 'Client Company', 'LION' ),
				'desc'      => __( "The client's company's name.", 'LION' ),
				'context'   => array( 'invoice' ),
				'available' => array( 'new-invoice' ),
				'meta'      => 'company'
			),
			'invoice_notes'     => array(
				'name'      => __( 'Invoice Notes', 'LION' ),
				'desc'      => __( 'The notes field for the invoice.', 'LION' ),
				'context'   => array( 'invoice' ),
				'available' => array( 'new-invoice' ),
				'meta'      => 'notes'
			),
			'invoice_total'     => array(
				'name'      => __( 'Total Due', 'LION' ),
				'desc'      => __( "The total amount due on the invoice.", 'LION' ),
				'context'   => array( 'invoice' ),
				'available' => array( 'new-invoice' )
			),
		);

		foreach ( $tags as $tag => $config ) {

			if ( ! empty( $config['meta'] ) ) {
				$method = "meta_{$config['meta']}";
			} else {
				$method = $tag;
			}

			$obj = new IT_Exchange_Email_Tag_Base( $tag, $config['name'], $config['desc'], array( $this, $method ) );

			foreach ( $config['context'] as $context ) {
				$obj->add_required_context( $context );
			}

			foreach ( $config['available'] as $notification ) {
				$obj->add_available_for( $notification );
			}

			$replacer->add_tag( $obj );
		}
	}

	/**
	 * Replace the client name tag.
	 *
	 * @since 1.10
	 *
	 * @param array $context
	 *
	 * @return string
	 */
	public function client_name( $context ) {

		$meta = $context['invoice']->get_feature( 'invoices' );

		if ( empty( $meta['client'] ) ) {
			return '';
		}

		return it_exchange_get_customer( $meta['client'] )->data->display_name;
	}

	/**
	 * Replace the client email tag.
	 *
	 * @since 1.36
	 *
	 * @param array $context
	 *
	 * @return string
	 */
	public function client_email( $context ) {

		$meta = $context['invoice']->get_feature( 'invoices' );

		if ( empty( $meta['client'] ) ) {
			return '';
		}

		return it_exchange_get_customer( $meta['client'] )->data->user_email;
	}

	/**
	 * Replace the client username tag.
	 *
	 * @since 1.36
	 *
	 * @param array $context
	 *
	 * @return string
	 */
	public function client_username( $context ) {

		$meta = $context['invoice']->get_feature( 'invoices' );

		if ( empty( $meta['client'] ) ) {
			return '';
		}

		return it_exchange_get_customer( $meta['client'] )->data->user_login;
	}

	/**
	 * Replace the invoice total tag.
	 *
	 * @since 1.10
	 *
	 * @param array $context
	 *
	 * @return string
	 */
	public function invoice_total( $context ) {
		return html_entity_decode( it_exchange_format_price( $context['invoice']->get_feature( 'base-price' ) ), ENT_COMPAT, 'UTF-8' );
	}

	/**
	 * Magic method for undefined functions.
	 *
	 * Used to simplify tags that are just calling meta.
	 *
	 * @since 1.10.0
	 *
	 * @param string $name
	 * @param array  $arguments
	 *
	 * @return bool|string
	 */
	public function __call( $name, $arguments ) {

		if ( substr( $name, 0, 5 ) === 'meta_' ) {
			$meta = substr( $name, 5 );

			/** @var IT_Exchange_Product $invoice */
			$invoice = $arguments[0]['invoice'];

			$feature = $invoice->get_feature( 'invoices' );

			return empty( $feature[ $meta ] ) ? '' : $feature[ $meta ];
		}

		return false;
	}
}