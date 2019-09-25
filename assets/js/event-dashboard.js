/* global event_manager_event_dashboard */
jQuery(document).ready(function($) {

	$('.event-dashboard-action-delete').click(function() {
		return window.confirm( event_manager_event_dashboard.i18n_confirm_delete );
	});

});