<?php

include_once( 'lib/product-features/class.invoices.php' );
include_once( 'lib/functions.php' );
include_once( 'lib/settings.php' );
include_once( 'lib/hooks.php' );
include_once( 'api/invoices.php' );

if ( ! is_admin() ) {
	include_once( 'api/theme/invoice.php' );
	include_once( 'api/theme/invoices.php' );
}
