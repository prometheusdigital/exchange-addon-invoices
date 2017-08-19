<?php
/**
 * Invoices class for THEME API
 *
 * @since 1.2.0
*/

class IT_Theme_API_Invoices implements IT_Theme_API {

	/**
	 * API context
	 * @var string $_context
	 * @since CHANGME
	*/
	private $_context = 'invoices';

	/**
	 * Maps api tags to methods
	 * @var array $_tag_map
	 * @since 1.2.0
	*/
	public $_tag_map = array(
		'found' => 'found',
		'exist' => 'exist',
		'reset' => 'reset',
	);

	/**
	 * Constructor
	 *
	 * @since 1.2.0
	 *
	 * @return void
	*/
	function __construct() {
	}

	/**
	 * Deprecated Constructor
	 *
	 * @since 1.2.0
	 *
	 * @return void
	*/
	function IT_Theme_API_Invoices() {
		self::__construct();
	}

	/**
	 * Returns the context. Also helps to confirm we are an ExchangeWP theme API class
	 *
	 * @since 1.2.0
	 *
	 * @return string
	*/
	function get_api_context() {
		return $this->_context;
	}

	/**
	 * Use this function to in a while loop to determine if there are any more invoices left to loop through.
	 * If there are no more invoices found, it will return false. Otherwise, it returns 'true'.
	 *
	 * @since 1.2.0
	 * @return string
	*/
	function found( $options=array() ) {
		// Return boolean if has flag was set
		if ( ! $customer = it_exchange_get_current_customer() )
			return;
		return count( it_exchange_invoices_addon_get_invoices_for_customer( $customer->id, $options ) ) > 0;
	}

	/**
	 * This loops through the invoices GLOBAL and updates the invoice global.
	 *
	 * It return false when it reaches the last invoice
	 *
	 * @since 1.2.0
	 * @return string
	*/
	function exist( $options=array() ) {
		// This will init/reset the invoices global and loop through them. the /api/theme/invoice.php file will handle individual invoices.
		if ( empty( $GLOBALS['it_exchange']['invoices'] ) ) {
			if ( ! $customer = it_exchange_get_current_customer() )
				return;
			$GLOBALS['it_exchange']['invoices'] = it_exchange_invoices_addon_get_invoices_for_customer( $customer->id, $options );
			$GLOBALS['it_exchange']['invoice'] = reset( $GLOBALS['it_exchange']['invoices'] );
			$GLOBALS['it_exchange']['product'] = $GLOBALS['it_exchange']['invoice'];
			return true;
		} else {
			if ( next( $GLOBALS['it_exchange']['invoices'] ) ) {
				$GLOBALS['it_exchange']['invoice'] = current( $GLOBALS['it_exchange']['invoices'] );
				$GLOBALS['it_exchange']['product'] = $GLOBALS['it_exchange']['invoice'];
				return true;
			} else {
				$GLOBALS['it_exchange']['invoices'] = array();
				end( $GLOBALS['it_exchange']['invoices'] );
				$GLOBALS['it_exchange']['invoice'] = false;
				$GLOBALS['it_exchange']['product'] = false;
				return false;
			}
		}
	}

	/**
	 * Resets the loop
	 *
	 * @since 1.2.0
	 *
	 * @return void
	*/
	function reset() {
		if ( isset( $GLOBALS['it_exchange']['invoices'] ) ) {
			unset( $GLOBALS['it_exchange']['invoices'] );
		}
		if ( isset( $GLOBALS['it_exchange']['invoice'] ) ) {
			unset( $GLOBALS['it_exchange']['invoice'] );
		}
		if ( isset( $GLOBALS['it_exchange']['product'] ) ) {
			unset( $GLOBALS['it_exchange']['product'] );
		}
	}
}
