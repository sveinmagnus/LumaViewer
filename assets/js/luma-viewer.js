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

	// Fetch a plugin REST endpoint, sending the nonce when we have one. If a stale
	// nonce is rejected (403), retry once as an anonymous read — the endpoint is
	// public, so logged-in users on long-lived pages still get their content.
	function restFetch( url ) {
		var nonce = config().nonce;
		var headers = { Accept: 'application/json' };
		if ( nonce ) {
			headers[ 'X-WP-Nonce' ] = nonce;
		}
		return fetch( url, {
			credentials: 'same-origin',
			headers: headers,
		} ).then( function ( response ) {
			if ( response.status === 403 && nonce ) {
				return fetch( url, {
					credentials: 'omit',
					headers: { Accept: 'application/json' },
				} );
			}
			return response;
		} );
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
		var quickview = container.getAttribute( 'data-lv-quickview' ) || '';
		var pagination = container.getAttribute( 'data-lv-pagination' ) || '';
		var order = container.getAttribute( 'data-lv-order' ) || '';
		var online = container.getAttribute( 'data-lv-online' ) || '';
		var free = container.getAttribute( 'data-lv-free' ) || '';
		var mtags = container.getAttribute( 'data-lv-mtags' ) || '';
		var words = container.getAttribute( 'data-lv-words' ) || '';
		var toggles = [
			'cover',
			'location',
			'host',
			'price',
			'excerpt',
			'tags',
			'relative',
		];

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
		if ( quickview ) {
			url.searchParams.set( 'quickview', quickview );
		}
		if ( pagination ) {
			url.searchParams.set( 'pagination', pagination );
		}
		if ( order ) {
			url.searchParams.set( 'order', order );
		}
		if ( online ) {
			url.searchParams.set( 'online', online );
		}
		if ( free ) {
			url.searchParams.set( 'free', free );
		}
		if ( mtags ) {
			url.searchParams.set( 'tags', mtags );
		}
		if ( words && words !== '0' ) {
			url.searchParams.set( 'excerpt_words', words );
		}
		// Preserve resolved element-visibility across re-renders.
		toggles.forEach( function ( key ) {
			var val = container.getAttribute( 'data-lv-show-' + key );
			if ( val === '0' || val === '1' ) {
				url.searchParams.set( 'show_' + key, val );
			}
		} );

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
		// Action buttons (past / online / free) are handled by the action listener.
		if ( ! chip || chip.hasAttribute( 'data-lv-action' ) ) {
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

		restFetch( url )
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

		var action = trigger.getAttribute( 'data-lv-action' );

		// "Include past" flips its state before the request is built.
		if ( 'past' === action ) {
			var on = container.getAttribute( 'data-lv-past' );
			container.setAttribute(
				'data-lv-past',
				on && on !== '' && on !== '0' ? '' : '1'
			);
		}

		// Online/in-person and free/paid cycle through their states on each click.
		if ( 'online' === action ) {
			var cycle = [ '', 'online', 'in_person' ];
			var cur = container.getAttribute( 'data-lv-online' ) || '';
			container.setAttribute(
				'data-lv-online',
				cycle[ ( cycle.indexOf( cur ) + 1 ) % cycle.length ]
			);
		}
		if ( 'free' === action ) {
			var fcycle = [ '', 'free', 'paid' ];
			var fcur = container.getAttribute( 'data-lv-free' ) || '';
			container.setAttribute(
				'data-lv-free',
				fcycle[ ( fcycle.indexOf( fcur ) + 1 ) % fcycle.length ]
			);
		}

		// Numbered pagination: jump to the page's offset.
		if ( 'page' === action ) {
			container.setAttribute(
				'data-lv-offset',
				trigger.getAttribute( 'data-lv-offset' ) || '0'
			);
		}

		var url = buildUrl( container, trigger );
		if ( ! url ) {
			return;
		}
		event.preventDefault();
		performFetch( container, url );
	} );

	// Live countdown(s).
	function tickCountdowns() {
		var els = document.querySelectorAll( '.luma-viewer__countdown[data-lv-start]' );
		var now = Date.now();
		els.forEach( function ( el ) {
			var start = Date.parse( el.getAttribute( 'data-lv-start' ) );
			if ( isNaN( start ) ) {
				return;
			}
			var diff = Math.floor( ( start - now ) / 1000 );
			if ( diff <= 0 ) {
				el.classList.add( 'is-live' );
				el.textContent = el.getAttribute( 'data-lv-live' ) || 'Happening now';
				return;
			}
			var d = Math.floor( diff / 86400 );
			var h = Math.floor( ( diff % 86400 ) / 3600 );
			var m = Math.floor( ( diff % 3600 ) / 60 );
			var s = diff % 60;
			el.textContent = d + 'd ' + h + 'h ' + m + 'm ' + s + 's';
		} );
	}
	setInterval( tickCountdowns, 1000 );
	tickCountdowns();
	document.addEventListener( 'luma-viewer:rendered', tickCountdowns );

	// Quick-view modal --------------------------------------------------------
	var modal = null;
	var modalBody = null;
	var lastFocus = null;

	function strings() {
		return config().i18n || {};
	}

	function ensureModal() {
		if ( modal ) {
			return modal;
		}
		modal = document.createElement( 'div' );
		modal.className = 'luma-viewer__modal';
		modal.setAttribute( 'hidden', '' );
		modal.innerHTML =
			'<div class="luma-viewer__modal-backdrop" data-lv-close></div>' +
			'<div class="luma-viewer__modal-box" role="dialog" aria-modal="true">' +
			'<button type="button" class="luma-viewer__modal-close" data-lv-close aria-label="' +
			( strings().close || 'Close' ) +
			'">&times;</button>' +
			'<div class="luma-viewer__modal-body" aria-live="polite"></div>' +
			'</div>';
		document.body.appendChild( modal );
		modalBody = modal.querySelector( '.luma-viewer__modal-body' );

		modal.addEventListener( 'click', function ( event ) {
			if ( event.target.closest( '[data-lv-close]' ) ) {
				closeModal();
			}
		} );
		return modal;
	}

	function closeModal() {
		if ( ! modal || modal.hasAttribute( 'hidden' ) ) {
			return;
		}
		modal.setAttribute( 'hidden', '' );
		document.body.classList.remove( 'luma-viewer-modal-open' );
		if ( lastFocus && typeof lastFocus.focus === 'function' ) {
			lastFocus.focus();
		}
	}

	function openQuickView( id, opener ) {
		var endpoint = config().restEvent;
		if ( ! endpoint || ! id ) {
			return;
		}
		lastFocus = opener || null;
		ensureModal();
		modalBody.textContent = strings().loading || 'Loading…';
		modal.removeAttribute( 'hidden' );
		document.body.classList.add( 'luma-viewer-modal-open' );
		var closeBtn = modal.querySelector( '.luma-viewer__modal-close' );
		if ( closeBtn ) {
			closeBtn.focus();
		}

		var url = new URL( endpoint, window.location.origin );
		url.searchParams.set( 'id', id );
		restFetch( url.toString() )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( data && data.html ) {
					modalBody.innerHTML = data.html;
				} else {
					closeModal();
				}
			} )
			.catch( closeModal );
	}

	// Intercept card clicks inside quick-view-enabled calendars.
	document.addEventListener( 'click', function ( event ) {
		var link = event.target.closest( '.luma-viewer__card a, .luma-viewer__cell-event' );
		if ( ! link || ! link.getAttribute( 'href' ) ) {
			return;
		}
		var container = link.closest( '.luma-viewer' );
		if ( ! container || container.getAttribute( 'data-lv-quickview' ) !== '1' ) {
			return;
		}
		var card = link.closest( '[data-lv-id]' );
		var id = card ? card.getAttribute( 'data-lv-id' ) : '';
		// Teaser cards link to the gate, not Luma — leave those alone.
		if ( ! id || ( card && card.classList.contains( 'luma-viewer__card--teaser' ) ) ) {
			return;
		}
		event.preventDefault();
		openQuickView( id, link );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key ) {
			closeModal();
		}
	} );

	// Carousel arrows scroll the track (local, no fetch).
	document.addEventListener( 'click', function ( event ) {
		var navBtn = event.target.closest( '[data-lv-carousel]' );
		if ( ! navBtn ) {
			return;
		}
		var container = navBtn.closest( '.luma-viewer' );
		var track = container
			? container.querySelector( '.luma-viewer__carousel-track' )
			: null;
		if ( ! track ) {
			return;
		}
		var dir = navBtn.getAttribute( 'data-lv-carousel' ) === 'prev' ? -1 : 1;
		var amount = Math.max( 240, Math.round( track.clientWidth * 0.8 ) );
		track.scrollBy( { left: dir * amount, behavior: 'smooth' } );
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
