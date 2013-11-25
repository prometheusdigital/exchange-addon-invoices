jQuery( document ).ready( function($) {

    // Init tooltip code
    $( '.tip, .dice' ).tooltip();

    // Init date picker on coupon code start / end fields
    $( '.datepicker' ).datepicker({
        prevText: '',
        nextText: '',
        minDate: 0,
        onSelect: function( date ) {
            if ( ! $( '#' + $( this ).attr( 'data-append' ) ).val() )
                $( '#' + $( this ).attr( 'data-append' ) ).val( date );

            if ( $( this ).attr( 'id' ) == 'start-date' )
                $( '#end-date' ).datepicker( 'option', 'minDate', date );
        }
    });

    // Generate coupon code when dice is clicked
    $( '.dice' ).on( 'click', function( event ) {
        event.preventDefault();
        $( '.it-exchange-invoices-password' ).attr( 'value', it_exchange_random_password() );
    });

});

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
