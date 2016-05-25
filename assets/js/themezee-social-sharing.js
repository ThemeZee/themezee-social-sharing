/*! jQuery Social Sharing
  Javascript for Social Sharing Plugin
  Author: Thomas W (themezee.com)
*/

(function($) {

	$( document ).ready( function() {
		
		$( '.themezee-social-sharing .tzss-link').click( function() {
			
			var spacing_top = ( $( window ).height() / 2 ) - ( 450 / 2 );
			var spacing_left = ( $( window ).width() / 2 ) - ( 550 / 2 );
			
			new_window = window.open( $( this ).attr( 'href' ), '', 'scrollbars=1, height=450, width=550, top=' + spacing_top + ', left=' + spacing_left );

			if ( window.focus ) {
				new_window.focus();
			}

			return false;
		
		} );
		
	} );

}(jQuery));