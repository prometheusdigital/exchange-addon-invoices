// Create a jquery plugin that prints the given element.
jQuery.fn.itExchangePrintInvoice = function () {

	// NOTE: We are trimming the jQuery collection down to the
	// first element in the collection.
	if ( this.size() > 1 ) {
		this.eq( 0 ).print();
		return;
	} else if ( ! this.size() ) {
		return;
	}

	// ASSERT: At this point, we know that the current jQuery
	// collection (as defined by THIS), contains only one
	// printable element.

	// Create a random name for the print frame.
	var strFrameName = ("printer-" + (new Date()).getTime());

	// Create an iFrame with the new name.
	var jFrame = jQuery( "<iframe name='" + strFrameName + "'>" );

	// Set Style Link hrefs
	var invoicesStylesLink = jQuery( '#it-exchange-addon-product-public-css-css' ).attr( 'href' );
	var invoicesPrintStylesLink = jQuery( '#it-exchange-addon-product-public-print-css' ).attr( 'href' );
	var exchangeStylesLink = jQuery( '#it-exchange-public-css-css' ).attr( 'href' );

	// Hide the frame (sort of) and attach to the body.
	jFrame
		.css( "width", "1px" )
		.css( "height", "1px" )
		.css( "position", "absolute" )
		.css( "left", "-9999px" )
		.appendTo( jQuery( "body:first" ) )
	;

	// Get a FRAMES reference to the new frame.
	var objFrame = window.frames[ strFrameName ];

	// Get a reference to the DOM in the new frame.
	var objDoc = objFrame.document;

	// Grab all the style tags and copy to the new
	// document so that we capture look and feel of
	// the current document.

	// Create a temp document DIV to hold the style tags.
	// This is the only way I could find to get the style
	// tags into IE.
	var jStyleDiv = jQuery( "<div>" ).append(
		jQuery( "style" ).clone()
	);

	// Strip superwidget and any other JS that got included
	var clonedHTML = jQuery( 'html' ).clone();
	clonedHTML.find( '.it-exchange-super-widget' ).remove();
	clonedHTML.find( 'script' ).remove();


	// Write the HTML for the document. In this, we will
	// write out the HTML of the current element.
	objDoc.open();
	objDoc.write( "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.0 Transitional//EN\" \"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd\">" );
	objDoc.write( "<html>" );
	objDoc.write( "<body>" );
	objDoc.write( "<head>" );
	objDoc.write( "<title>" );
	objDoc.write( document.title );
	objDoc.write( "</title>" );
	objDoc.write( jStyleDiv.html() );
	objDoc.write( '<link rel="stylesheet" id="it-exchange-addon-product-public-css-css" type="text/css" href="' + invoicesStylesLink + '">' );
	objDoc.write( '<link rel="stylesheet" id="it-exchange-addon-product-public-print-css" type="text/css" href="' + invoicesPrintStylesLink + '">' );
	objDoc.write( '<link rel="stylesheet" id="it-exchange-public-css-css" type="text/css" href="' + exchangeStylesLink + '">' );
	objDoc.write( "</head>" );
	objDoc.write( clonedHTML.html() );
	objDoc.write( "</body>" );
	objDoc.write( "</html>" );
	objDoc.close();

	// Print the document.
	objFrame.focus();
	objFrame.print();

	// Have the frame remove itself in about a minute so that
	// we don't build up too many of these frames.
	setTimeout(
		function () {
			jFrame.remove();
		},
		(60 * 1000)
	);
}
jQuery( function ( $ ) {
	$( '.it-exchange-print-invoice-link' ).click( function ( event ) {
		event.preventDefault();
		$( '#it-exchange-invoice-product' ).itExchangePrintInvoice();
	} );
} );


jQuery( document ).ready( function ( $ ) {

	itExchange.hooks.addAction( 'itExchangeSW.applyCoupon', function ( coupon ) {
		jQuery.get( itExchangeSWAjaxURL + '&sw-action=invoices-get-total', function ( result ) {

			if ( $('.it-exchange-invoice-payment-amount-block .value ins').length > 0 ) {
				$('.it-exchange-invoice-payment-amount-block .value ins').text(result);
			} else {
				$( '.it-exchange-invoice-payment-amount-block .value' ).text( result );
			}
		} );

	} );
} );