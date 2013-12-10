<?php

include_once( 'lib/product-features/class.invoices.php' );
include_once( 'lib/functions.php' );
include_once( 'lib/settings.php' );

if ( ! is_admin() )
	include_once( 'api/theme/invoice.php' );
