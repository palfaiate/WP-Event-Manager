/* global event_manager_ajax_filters */
jQuery( document ).ready( function( $ ) {
	var session_storage_prefix = 'event_listing_';

	/**
	 * Check if we should maintain the state.
	 */
	function is_state_storage_enabled( $target ) {
		if ( ! supports_html5_session_storage() ) {
			return false;
		}

		// Check to see if it is globally disabled.
		if ( $( document.body ).hasClass( 'disable-event-manager-form-state-storage' ) ) {
			return false;
		}

		// Check if it is disabled on this specific element.
		if ( $target.data( 'disable-form-state-storage' ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if the sessionStorage object is available.
	 */
	function supports_html5_session_storage() {
		return window.sessionStorage && typeof window.sessionStorage.setItem === 'function';
	}

	/**
	 * Get the session storage key for the event listings instance.
	 */
	function get_session_storage_key( $target ) {
		var index          = $( 'div.event_listings' ).index( $target );
		var unique_page_id = $target.data( 'post_id' );

		if ( typeof unique_page_id === 'undefined' || ! unique_page_id ) {
			unique_page_id = window.location.href.replace( location.hash, '' );
		}

		return session_storage_prefix + unique_page_id + '_' + index;
	}

	/**
	 * Store the filter form values and possibly the rendered results in sessionStorage.
	 */
	function store_state( $target, state ) {
		if ( ! is_state_storage_enabled( $target ) ) {
			return false;
		}

		if ( typeof state !== 'object' ) {
			state = {};
		}

		var session_storage_key = get_session_storage_key( $target );

		try {
			return window.sessionStorage.setItem( session_storage_key, JSON.stringify( state ) );
		} catch ( e ) {
			// If the usage is full or the browser has denied us access, continue gracefully.
		}

		return false;
	}

	/**
	 * Retrieve the stored form values and maybe the rendered results from sessionStorage.
	 */
	function get_state( $target ) {
		if ( ! is_state_storage_enabled( $target ) ) {
			return false;
		}

		var session_storage_key = get_session_storage_key( $target );

		try {
			var state = window.sessionStorage.getItem( session_storage_key );
			if ( state ) {
				return JSON.parse( state );
			}
		} catch ( e ) {
			// If the browser has denied us access, continue gracefully as if there wasn't any stored state.
		}

		return false;
	}

	/**
	 * Toggle the `persist_results` boolean based on whether we not the rendered results to persist when moving away from page.
	 */
	function persist_results( $target, persist ) {
		if ( ! is_state_storage_enabled( $target ) || ! $target ) {
			return false;
		}

		var state = get_state( $target );
		if ( ! state ) {
			return false;
		}

		state.persist_results = persist;

		return store_state( $target, state );
	}

	/**
	 * Persist the state of the form.
	 */
	function persist_form( $target ) {
		if ( ! is_state_storage_enabled( $target ) || ! $target ) {
			return false;
		}

		var state = get_state( $target );
		if ( ! state ) {
			return false;
		}

		var $form = $target.find( '.event_filters' );
		state.form = $form.serialize();

		return store_state( $target, state );
	}

	/**
	 * Store the rendered results with the state in sessionStorage.
	 */
	function save_results( $target, results ) {
		if ( ! is_state_storage_enabled( $target ) ) {
			return false;
		}

		var state = get_state( $target );
		if ( ! state ) {
			state = {
				persist_results: false
			};
		}

		var $results = $target.find( '.event_listings' );

		// Cache all loaded $results.
		results.html = $results.html();

		state.results = results;

		return store_state( $target, state );
	}

	/**
	 * Clear the stored state of the form values and possibly the rendered results from sessionStorage.
	 */
	function clear_state( $target ) {
		if ( ! is_state_storage_enabled( $target ) ) {
			return false;
		}

		var session_storage_key = get_session_storage_key( $target );

		try {
			window.sessionStorage.removeItem( session_storage_key );
		} catch ( e ) {
			return false;
		}

		return true;
	}

	/**
	 * Clear the rendered results from the stored state in sessionStorage.
	 */
	function clear_results( $target ) {
		if ( ! is_state_storage_enabled( $target ) ) {
			return false;
		}

		var state = get_state( $target );
		if ( ! state ) {
			state = {};
		}

		state.results = null;

		return store_state( $target, state );
	}

	/**
	 * Clear the form from the stored state in sessionStorage.
	 */
	function clear_form( $target ) {
		if ( ! is_state_storage_enabled( $target ) ) {
			return false;
		}

		var state = get_state( $target );
		if ( ! state ) {
			state = {};
		}

		state.form = null;

		return store_state( $target, state );
	}

	/**
	 * Handle restoring the results from sessionStorage or the Ajax call.
	 */
	function handle_result( $target, result, append ) {
		var $results = $target.find( '.event_listings' );
		var $showing = $target.find( '.showing_events' );

		if ( typeof append !== 'boolean' ) {
			append = false;
		}

		if ( typeof result.showing === 'string' && result.showing ) {
			var $showing_el = jQuery( '<span>' ).html( result.showing );
			$showing
				.show()
				.html( '' )
				.html( result.showing_links )
				.prepend( $showing_el );
		} else {
			$showing.hide();
		}

		if ( result.showing_all ) {
			$showing.addClass( 'wp-event-manager-showing-all' );
		} else {
			$showing.removeClass( 'wp-event-manager-showing-all' );
		}

		if ( result.html ) {
			if ( append ) {
				$results.append( result.html );
			} else {
				$results.html( result.html );
			}
		}

		if ( true === $target.data( 'show_pagination' ) ) {
			$target.find( '.event-manager-pagination' ).remove();

			if ( result.pagination ) {
				$target.append( result.pagination );
			}
		} else {
			if ( ! result.found_events || result.max_num_pages <= result.data.page ) {
				$( '.load_more_events:not(.load_previous)', $target ).hide();
			} else {
				$( '.load_more_events', $target ).show();
			}
			$( '.load_more_events', $target )
				.removeClass( 'loading' )
				.data( 'page', result.data.page );
			$( 'li.event_listing', $results ).css( 'visibility', 'visible' );
		}

		return true;
	}

	// Preserve form when not refreshing page.
	$(document).on( 'click', 'a', function() {
		// We're moving away to another page. Let's make sure the form persist.
		$( 'div.event_listings' ).each( function() {
			persist_form( $( this ) );
		} );
	} );

	$(document).on( 'submit', 'form', function() {
		// We're moving away from current page from another form. Let's make sure the form persist.
		$( 'div.event_listings' ).each( function() {
			persist_form( $( this ) );
		} );

	} );

	var xhr = [];
	$( 'div.event_listings' )
		.on( 'click', 'li.event_listing a', function() {
			var $target = $( this ).closest( 'div.event_listings' );

			// We're moving away to a event listing. Let's make sure the results persist.
			persist_results( $target, true );
		} )
		.on( 'click', '.event-manager-pagination a', function() {
			var $target = $( this ).closest( 'div.event_listings' );
			var page = $( this ).data( 'page' );

			$target.triggerHandler( 'update_results', [ page, false ] );

			$( 'body, html' ).animate(
				{
					scrollTop: $target.offset().top,
				},
				600
			);

			return false;
		} )
		.on( 'update_results', function( event, page, append ) {
			var data = '';
			var $target = $( this );
			var $form = $target.find( '.event_filters' );
			var $results = $target.find( '.event_listings' );
			var per_page = $target.data( 'per_page' );
			var orderby = $target.data( 'orderby' );
			var order = $target.data( 'order' );
			var featured = $target.data( 'featured' );
			var filled = $target.data( 'filled' );
			var event_types = $target.data( 'event_types' );
			var post_status = $target.data( 'post_status' );
			var index = $( 'div.event_listings' ).index( this );
			var categories, keywords, location;

			if ( index < 0 ) {
				return;
			}

			clear_state( $target );

			if ( xhr[ index ] ) {
				xhr[ index ].abort();
			}

			if ( ! append || 1 === page ) {
				$( 'li.event_listing, li.no_event_listings_found', $results ).css( 'visibility', 'hidden' );
				$results.addClass('loading');
			}

			$target.find( '.load_more_events' ).data( 'page', page );

			if ( true === $target.data( 'show_filters' ) ) {
				var filter_event_type = [];

				$(
					':input[name="filter_event_type[]"]:checked, :input[name="filter_event_type[]"][type="hidden"], :input[name="filter_event_type"]',
					$form
				).each( function() {
					filter_event_type.push( $( this ).val() );
				} );

				categories = $form
					.find( ':input[name^="search_categories"]' )
					.map( function() {
						return $( this ).val();
					} )
					.get();

				keywords = '';
				location = '';
				var $keywords = $form.find( ':input[name="search_keywords"]' );
				var $location = $form.find( ':input[name="search_location"]' );

				// Workaround placeholder scripts
				if ( $keywords.val() !== $keywords.attr( 'placeholder' ) ) {
					keywords = $keywords.val();
				}

				if ( $location.val() !== $location.attr( 'placeholder' ) ) {
					location = $location.val();
				}

				data = {
					lang: event_manager_ajax_filters.lang,
					search_keywords: keywords,
					search_location: location,
					search_categories: categories,
					filter_event_type: filter_event_type,
					filter_post_status: post_status,
					per_page: per_page,
					orderby: orderby,
					order: order,
					page: page,
					featured: featured,
					filled: filled,
					show_pagination: $target.data( 'show_pagination' ),
					form_data: $form.serialize(),
				};
			} else {
				categories = $target.data( 'categories' );
				keywords = $target.data( 'keywords' );
				location = $target.data( 'location' );

				if ( categories ) {
					if ( typeof categories !== 'string' ) {
						categories = String( categories );
					}
					categories = categories.split( ',' );
				}

				data = {
					lang: event_manager_ajax_filters.lang,
					search_categories: categories,
					search_keywords: keywords,
					search_location: location,
					filter_post_status: post_status,
					filter_event_type: event_types,
					per_page: per_page,
					orderby: orderby,
					order: order,
					page: page,
					featured: featured,
					filled: filled,
					show_pagination: $target.data( 'show_pagination' ),
				};
			}

			xhr[ index ] = $.ajax( {
				type: 'POST',
				url: event_manager_ajax_filters.ajax_url.toString().replace( '%%endpoint%%', 'get_listings' ),
				data: data,
				success: function( result ) {
					if ( result ) {
						try {
							result.data = data;

							handle_result( $target, result, append );

							$results.removeClass( 'loading' );
							$target.triggerHandler( 'updated_results', result );

							save_results( $target, result );
						} catch ( err ) {
							if ( window.console ) {
								window.console.log( err );
							}
						}
					}
				},
				error: function( jqXHR, textStatus, error ) {
					if ( window.console && 'abort' !== textStatus ) {
						window.console.log( textStatus + ': ' + error );
					}
				},
				statusCode: {
					404: function() {
						if ( window.console ) {
							window.console.log(
								'Error 404: Ajax Endpoint cannot be reached. Go to Settings > Permalinks and save to resolve.'
							);
						}
					},
				},
			} );
		} );

	$(
		'#search_keywords, #search_location, .event_types :input, #search_categories, .event-manager-filter'
	)
		.change( function() {
			var $target = $( this ).closest( 'div.event_listings' );
			$target.triggerHandler( 'update_results', [ 1, false ] );
			store_state( $target );
		} )
		.on( 'keyup', function( e ) {
			if ( e.which === 13 ) {
				$( this ).trigger( 'change' );
			}
		} );

	$( '.event_filters' )
		.on( 'click', '.reset', function() {
			var $target = $( this ).closest( 'div.event_listings' );
			var $form = $( this ).closest( 'form' );

			$form
				.find(
					':input[name="search_keywords"], :input[name="search_location"], .event-manager-filter'
				)
				.not( ':input[type="hidden"]' )
				.val( '' )
				.trigger( 'change.select2' );
			$form
				.find( ':input[name^="search_categories"]' )
				.not( ':input[type="hidden"]' )
				.val( '' )
				.trigger( 'change.select2' );
			$( ':input[name="filter_event_type[]"]', $form )
				.not( ':input[type="hidden"]' )
				.attr( 'checked', 'checked' );

			$target.triggerHandler( 'reset' );
			$target.triggerHandler( 'update_results', [ 1, false ] );
			store_state( $target );

			return false;
		} )
		.on( 'submit', function() {
			return false;
		} );

	$( document.body ).on( 'click', '.load_more_events', function() {
		var $target = $( this ).closest( 'div.event_listings' );
		var page = parseInt( $( this ).data( 'page' ) || 1, 10 );

		$( this ).addClass( 'loading' );

		page = page + 1;
		$( this ).data( 'page', page );

		$target.triggerHandler( 'update_results', [ page, true ] );
		return false;
	} );

	if ( $.isFunction( $.fn.select2 ) && typeof event_manager_select2_args !== 'undefined' ) {
		var select2_args = event_manager_select2_args;
		select2_args[ 'allowClear' ] = true;
		select2_args[ 'minimumResultsForSearch' ] = 10;

		$( 'select[name^="search_categories"]:visible' ).select2( select2_args );
	}

	// Initial event and $form population
	$( window ).on( 'load', function() {
		$( 'div.event_listings' ).each( function() {
			var $target = $( this );
			var $form = $target.find( '.event_filters' );
			var results_loaded = false;
			var state = get_state( $target );

			if ( state ) {
				// Restore the results from cache.
				if ( state.results ) {
					results_loaded = handle_result( $target, state.results );

					// We don't want this to continue to persist unless we click on another link.
					persist_results( $target, false );
					clear_form( $target );
				}

				// Restore the form state.
				if ( typeof state.form === 'string' && '' !== state.form ) {
					// When deserializing a form, we need to first uncheck the checkboxes that are by default checked.
					$form.find('input[type=checkbox]').prop('checked', false);
					$form.deserialize(state.form);
					$form
						.find(':input[name^="search_categories"]')
						.not(':input[type="hidden"]')
						.trigger('change.select2');
				}
			}

			if ( ! results_loaded && $form.length > 0 ) {
				// If we didn't load results from cache, load page 1.
				$target.triggerHandler( 'update_results', [ 1, false ] );
			}
		} );
	} );

	$( window ).on( 'unload', function() {
		$( 'div.event_listings' ).each( function() {
			var state = get_state( $( this ) );
			if ( state && ! state.persist_results ) {
				clear_results( $( this ) );
			}
		} );

		return true;
	} );
} );
