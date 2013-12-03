jQuery( document ).ready( function($) {

    // Init tooltip code
    $( '.tip, .dice' ).tooltip();

    // Init date picker on coupon code start / end fields
    $( '#it-exchange-invoices-date-issued' ).datepicker({
        prevText: '',
        nextText: '',
        minDate: 0,
        onSelect: function( date ) {
            if ( ! $( '#' + $( this ).attr( 'data-append' ) ).val() )
                $( '#' + $( this ).attr( 'data-append' ) ).val( date );
        }
    });

    // Generate password when dice is clicked
    $( '.dice' ).on( 'click', function( event ) {
        event.preventDefault();
        $( '.it-exchange-invoices-password' ).attr( 'value', it_exchange_random_password() );
    });

	// Toggle invoice password
	$('#it-exchange-invoices-use-password').on('change', function() {
		$('.invoice-field-container-password').toggleClass('hide-if-js');
	});

	// Toggle use custom account username
	$('#it-exchange-client-type-new-use-custom-username').on('change', function() {
		$('#it-exchange-client-type-new-custom-username').toggleClass('hide-if-js');
	});

	// Toggle use custom account password
	$('#it-exchange-client-type-new-custom-password').on('change', function() {
		$('.it-exchange-client-type-new-custom-passwords').toggleClass('hide-if-js');
	});

	// Show Client Select
	$('.it-exchange-client-type').on('change', function(element) {
		var checked = $(this).val();
		$('.section-customer-select').removeClass('hide-if-js');
		$('.invoice-section').not('.section-customer-select').addClass('hide-if-js');
		$('.section-customer-new').addClass('hide-if-js');
		$('.section-customer-existing').addClass('hide-if-js');
		$('.section-customer-' + checked).removeClass('hide-if-js');
	});

	// Create New Client
	$('#it-exchange-invoicing-create-client').on('click', function(event) {
		event.preventDefault();
		var data = {
			action     : 'it-exchange-invoices-create-client',
			first_name : $('#it-exchange-client-type-new-first-name').val(),
			last_name  : $('#it-exchange-client-type-new-last-name').val(),
			company    : $('#it-exchange-client-type-new-company').val(),
			email      : $('#it-exchange-client-type-new-email').val(),
		};

		// Add username if its custom, otherwise, use email
		if ( $('#it-exchange-client-type-new-use-custom-username').is(':checked') ) {
			data.user_login = $('#it-exchange-client-type-new-custom-username').val();
		} else {
			data.user_login = data.email;
		}

		if ( $('#it-exchange-client-type-new-custom-password').is(':checked') ) {
			data.pass1 = $('#it-exchange-client-type-new-custom-pass1').val();
			data.pass2 = $('#it-exchange-client-type-new-custom-pass2').val();
		} else {
			data.pass1 = data.pass2 = it_exchange_random_password();
		}

		$.post(ajaxurl, data, function(response) {
			response = jQuery.parseJSON(response);
			if ( response.error ) {
				$('#it-exchange-invoices-new-client-error').find('.error-message').html( response.message ).end().removeClass('hide-if-js');
			} else { 
				itExchangeInvoicingUpdateClientData( response.id );
				$('.invoice-section').toggleClass('hide-if-js');
				$('.section-customer-new').addClass('hide-if-js');
				$('.section-customer-existing').addClass('hide-if-js');
			}
		});
	});

	// Select Existing Client
	$('#it-exchange-invoicing-existing-client').on('click', function(element) {
		//$('#it-exchange-invoices-client-id').val( $('#it-exchange-invoices-existing-customer-select').val() );
		itExchangeInvoicingUpdateClientData( $('#it-exchange-invoices-existing-customer-select').val() );
		$('.invoice-section').toggleClass('hide-if-js');
		$('.section-customer-new').addClass('hide-if-js');
		$('.section-customer-existing').addClass('hide-if-js');
	});

	// Edit Link
	$('#it-exchange-invoices-edit-client').on('click', function(element) {
		event.preventDefault();
		$('#it-exchange-client-type-existing').attr('checked', true).trigger('change');
		$('#it-exchange-invoices-existing-customer-select').val( $('#it-exchange-invoices-client-id').val() );
		//$('.invoice-section').toggleClass('hide-if-js');
	});

});

/**
 * Updates the client data for the submittable form
 *
 * @since 1.0.0
**/
function itExchangeInvoicingUpdateClientData( clientID ) {
	
	var data = {
		action:   'it-exchange-invoices-get-client-data',
		clientID: clientID
	};

	jQuery.post(ajaxurl, data, function(response) {
		response = jQuery.parseJSON(response);
		jQuery('#it-exchange-invoices-client-id').val( response.clientID );
		jQuery('.it-exchange-invoices-client-name').html( response.clientDisplayName );
		jQuery('#it-exchange-invoices-emails').val( response.clientEmail );
		jQuery('#it-exchange-invoices-company').val( response.clientCompany );
		jQuery('#it-exchange-invoices-terms').val( response.clientTerms );

		if ( jQuery("#it-exchange-invoices-existing-customer-select option[value='" + response.clientID + "']").length == 0 ) {
			jQuery('#it-exchange-invoices-existing-customer-select').append( jQuery("<option></option>").attr("value", response.clientID ).text( response.clientDisplayName ) );
		}
	});
}

/**
 * Generates a random password
**/
function it_exchange_random_password( number ) {
    if ( ! number ) {
        number = 12;
    }

    var password  = '';
    var possible  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        possible += 'abcdefghijklmnopqrstuvwxyz';
        possible += '0123456789!@#$%^&*';

    for ( var i = 0; i < number; i++ ) {
        password += possible.charAt( Math.floor( Math.random() * possible.length ) );
    }

    return password;
}
