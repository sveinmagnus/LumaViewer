/**
 * Luma Viewer front-end: AJAX view-switching and date navigation.
 *
 * Uses event delegation so it keeps working after a container is replaced.
 * Each .luma-viewer carries its current state in data-lv-* attributes; toolbar
 * controls carry data-lv-action ("view" | "nav") plus the target view/date.
 */
( function () {
	'use strict';

	function config() {
		return window.lumaViewer || {};
	}

	function buildUrl( container, trigger ) {
		var rest = config().rest;
		if ( ! rest ) {
			return null;
		}

		var url = new URL( rest, window.location.origin );
		var view =
			trigger.getAttribute( 'data-lv-view' ) ||
			container.getAttribute( 'data-lv-view' ) ||
			'';
		var date =
			trigger.getAttribute( 'data-lv-date' ) ||
			container.getAttribute( 'data-lv-date' ) ||
			'';
		var tag = container.getAttribute( 'data-lv-tag' ) || '';
		var count = container.getAttribute( 'data-lv-count' ) || '';
		var layout = container.getAttribute( 'data-lv-layout' ) || '';
		var group = container.getAttribute( 'data-lv-group' ) || '';
		var calendar = container.getAttribute( 'data-lv-calendar' ) || '';
		var filters = container.getAttribute( 'data-lv-filters' ) || '';
		var offsetAttr = container.getAttribute( 'data-lv-offset' ) || '0';
		var past = container.getAttribute( 'data-lv-past' ) || '';
		var from = container.getAttribute( 'data-lv-from' ) || '';
		var to = container.getAttribute( 'data-lv-to' ) || '';

		// "Load more" re-renders the view with a larger count.
		if ( 'more' === trigger.getAttribute( 'data-lv-action' ) ) {
			var step = parseInt( container.getAttribute( 'data-lv-step' ) || '0', 10 ) || 0;
			count = String( ( parseInt( count, 10 ) || 0 ) + step );
		}

		if ( view ) {
			url.searchParams.set( 'view', view );
		}
		// A view change resets the date anchor unless the trigger sets one.
		if ( date && ! ( trigger.getAttribute( 'data-lv-action' ) === 'view' ) ) {
			url.searchParams.set( 'date', date );
		}
		if ( tag ) {
			url.searchParams.set( 'tag', tag );
		}
		if ( count && count !== '0' ) {
			url.searchParams.set( 'count', count );
		}
		if ( layout && layout !== 'cards' ) {
			url.searchParams.set( 'layout', layout );
		}
		if ( group && group !== 'day' ) {
			url.searchParams.set( 'group_by', group );
		}
		if ( calendar ) {
			url.searchParams.set( 'calendar', calendar );
		}
		if ( filters ) {
			url.searchParams.set( 'filters', filters );
		}
		if ( past ) {
			url.searchParams.set( 'past', past );
		}
		if ( from ) {
			url.searchParams.set( 'from', from );
		}
		if ( to ) {
			url.searchParams.set( 'to', to );
		}
		if ( offsetAttr && offsetAttr !== '0' ) {
			url.searchParams.set( 'offset', offsetAttr );
		}

		return url.toString();
	}

	// Client-side search + category filtering over the rendered cards.
	function applyFilter( container ) {
		var searchEl = container.querySelector( '.luma-viewer__search' );
		var query = searchEl ? searchEl.value.trim().toLowerCase() : '';
		var activeChip = container.querySelector( '.luma-viewer__chip.is-active' );
		var tag = activeChip ? activeChip.getAttribute( 'data-lv-chip' ) : '';

		container.querySelectorAll( '.luma-viewer__card' ).forEach( function ( card ) {
			var title = card.getAttribute( 'data-lv-title' ) || '';
			var tags = card.getAttribute( 'data-lv-tags' ) || '';
			var match =
				( ! query || title.indexOf( query ) !== -1 ) &&
				( ! tag || tags.indexOf( tag ) !== -1 );
			card.style.display = match ? '' : 'none';
		} );

		// Hide group/day sections that no longer have any visible cards.
		container
			.querySelectorAll( '.luma-viewer__group, .luma-viewer__week-day' )
			.forEach( function ( section ) {
				var visible = Array.prototype.some.call(
					section.querySelectorAll( '.luma-viewer__card' ),
					function ( card ) {
						return card.style.display !== 'none';
					}
				);
				section.style.display = visible ? '' : 'none';
			} );
	}

	document.addEventListener( 'input', function ( event ) {
		if ( ! event.target.classList.contains( 'luma-viewer__search' ) ) {
			return;
		}
		var container = event.target.closest( '.luma-viewer' );
		if ( container ) {
			applyFilter( container );
		}
	} );

	document.addEventListener( 'click', function ( event ) {
		var chip = event.target.closest( '.luma-viewer__chip' );
		if ( ! chip ) {
			return;
		}
		var container = chip.closest( '.luma-viewer' );
		if ( ! container ) {
			return;
		}
		event.preventDefault();
		var wasActive = chip.classList.contains( 'is-active' );
		container.querySelectorAll( '.luma-viewer__chip' ).forEach( function ( other ) {
			other.classList.remove( 'is-active' );
		} );
		if ( ! wasActive ) {
			chip.classList.add( 'is-active' );
		}
		applyFilter( container );
	} );

	function performFetch( container, url ) {
		container.classList.add( 'is-loading' );
		container.setAttribute( 'aria-busy', 'true' );

		function reset() {
			container.classList.remove( 'is-loading' );
			container.setAttribute( 'aria-busy', 'false' );
		}

		var headers = { Accept: 'application/json' };
		if ( config().nonce ) {
			headers[ 'X-WP-Nonce' ] = config().nonce;
		}

		fetch( url, { credentials: 'same-origin', headers: headers } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( ! data || ! data.html ) {
					reset();
					return;
				}
				var tmp = document.createElement( 'div' );
				tmp.innerHTML = data.html.trim();
				var next = tmp.firstElementChild;
				if ( ! next ) {
					reset();
					return;
				}
				container.replaceWith( next );
				next.setAttribute( 'aria-busy', 'false' );
				// Move focus to the refreshed region so keyboard and screen-reader
				// users follow the content change instead of losing their place.
				if ( typeof next.focus === 'function' ) {
					next.focus();
				}
				// Let view-specific scripts (e.g. the map) re-initialise.
				next.dispatchEvent(
					new CustomEvent( 'luma-viewer:rendered', { bubbles: true } )
				);
			} )
			.catch( reset );
	}

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest( '[data-lv-action]' );
		if ( ! trigger ) {
			return;
		}
		var container = trigger.closest( '.luma-viewer' );
		if ( ! container ) {
			return;
		}

		// "Include past" flips its state before the request is built.
		if ( 'past' === trigger.getAttribute( 'data-lv-action' ) ) {
			var on = container.getAttribute( 'data-lv-past' );
			container.setAttribute(
				'data-lv-past',
				on && on !== '' && on !== '0' ? '' : '1'
			);
		}

		var url = buildUrl( container, trigger );
		if ( ! url ) {
			return;
		}
		event.preventDefault();
		performFetch( container, url );
	} );

	// Date-range inputs re-fetch on change.
	document.addEventListener( 'change', function ( event ) {
		if ( ! event.target.classList.contains( 'luma-viewer__date' ) ) {
			return;
		}
		var container = event.target.closest( '.luma-viewer' );
		if ( ! container ) {
			return;
		}
		var fromEl = container.querySelector( '.luma-viewer__date--from' );
		var toEl = container.querySelector( '.luma-viewer__date--to' );
		container.setAttribute( 'data-lv-from', fromEl ? fromEl.value : '' );
		container.setAttribute( 'data-lv-to', toEl ? toEl.value : '' );
		var url = buildUrl( container, container );
		if ( url ) {
			performFetch( container, url );
		}
	} );
} )();
