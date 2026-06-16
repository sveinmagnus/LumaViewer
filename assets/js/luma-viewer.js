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

		return url.toString();
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

		var url = buildUrl( container, trigger );
		if ( ! url ) {
			return;
		}

		event.preventDefault();
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
			} )
			.catch( reset );
	} );
} )();
