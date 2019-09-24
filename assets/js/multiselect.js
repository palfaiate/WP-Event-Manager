/* global event_manager_select2_args */
jQuery( function( $ ) {
	if ( $.isFunction( $.fn.select2 ) && typeof event_manager_select2_args !== 'undefined' ) {
		$( '.event-manager-multiselect:visible' ).select2( event_manager_select2_args );
	}
} );
