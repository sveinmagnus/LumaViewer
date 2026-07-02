/**
 * Luma-viewer map view: renders event venues with Leaflet + OpenStreetMap.
 *
 * Leaflet is lazy-loaded from a CDN only when a map container is present, so it
 * never weighs down non-map pages. Re-runs after AJAX view switches.
 */
( function () {
	'use strict';

	var loading = false;
	var clusterLoading = false;

	function config() {
		return ( window.lumaViewerMap && window.lumaViewerMap.leaflet ) || {};
	}

	function clusterReady() {
		return (
			typeof window.L !== 'undefined' &&
			typeof window.L.markerClusterGroup === 'function'
		);
	}

	function parseCenter( value ) {
		if ( ! value ) {
			return null;
		}
		var parts = value.split( ',' );
		if ( parts.length !== 2 ) {
			return null;
		}
		var lat = parseFloat( parts[ 0 ] );
		var lng = parseFloat( parts[ 1 ] );
		if ( isNaN( lat ) || isNaN( lng ) ) {
			return null;
		}
		return [ lat, lng ];
	}

	function ensureCluster( done ) {
		if ( clusterReady() ) {
			done();
			return;
		}
		if ( clusterLoading ) {
			return;
		}
		var urls = config();
		if ( ! urls.cluster_js ) {
			done();
			return;
		}
		clusterLoading = true;
		[ urls.cluster_css, urls.cluster_css_default ].forEach( function ( href ) {
			if ( ! href ) {
				return;
			}
			var link = document.createElement( 'link' );
			link.rel = 'stylesheet';
			link.href = href;
			document.head.appendChild( link );
		} );
		var script = document.createElement( 'script' );
		script.src = urls.cluster_js;
		script.onload = function () {
			clusterLoading = false;
			done();
		};
		document.head.appendChild( script );
	}

	function escapeHtml( value ) {
		return String( value ).replace( /[&<>"']/g, function ( c ) {
			return {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#39;',
			}[ c ];
		} );
	}

	function renderMap( el ) {
		if ( el.dataset.lvMapReady || typeof window.L === 'undefined' ) {
			return;
		}
		var wantsCluster = el.getAttribute( 'data-lv-cluster' ) === '1';
		// Defer until the cluster plugin has loaded, then re-render.
		if ( wantsCluster && ! clusterReady() ) {
			ensureCluster( renderAll );
			return;
		}
		var markers;
		try {
			markers = JSON.parse( el.getAttribute( 'data-lv-markers' ) || '[]' );
		} catch ( e ) {
			markers = [];
		}
		if ( ! markers.length ) {
			return;
		}
		el.dataset.lvMapReady = '1';

		var map = window.L.map( el );
		window.L.tileLayer( 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
			maxZoom: 19,
			attribution: '&copy; OpenStreetMap contributors',
		} ).addTo( map );

		var group = wantsCluster && clusterReady() ? window.L.markerClusterGroup() : null;
		var bounds = [];
		markers.forEach( function ( m ) {
			if ( typeof m.lat !== 'number' || typeof m.lng !== 'number' ) {
				return;
			}
			var html = '<strong>' + escapeHtml( m.name ) + '</strong>';
			if ( m.where ) {
				html += '<br>' + escapeHtml( m.where );
			}
			if ( m.url && /^https?:\/\//i.test( m.url ) ) {
				html +=
					'<br><a href="' +
					encodeURI( m.url ) +
					'" target="_blank" rel="noopener noreferrer">Luma</a>';
			}
			var marker = window.L.marker( [ m.lat, m.lng ] ).bindPopup( html );
			if ( group ) {
				group.addLayer( marker );
			} else {
				marker.addTo( map );
			}
			bounds.push( [ m.lat, m.lng ] );
		} );
		if ( group ) {
			map.addLayer( group );
		}

		// A configured center/zoom overrides the automatic fit-to-markers.
		var center = parseCenter( el.getAttribute( 'data-lv-center' ) );
		var zoom = parseInt( el.getAttribute( 'data-lv-zoom' ) || '0', 10 ) || 0;
		if ( center ) {
			map.setView( center, zoom || 13 );
		} else if ( bounds.length ) {
			map.fitBounds( bounds, { padding: [ 30, 30 ], maxZoom: zoom || 15 } );
		}
	}

	function renderAll() {
		document.querySelectorAll( '.luma-viewer__map' ).forEach( renderMap );
	}

	function ensureLeaflet() {
		if ( ! document.querySelector( '.luma-viewer__map:not([data-lv-map-ready])' ) ) {
			return;
		}
		if ( typeof window.L !== 'undefined' ) {
			renderAll();
			return;
		}
		if ( loading ) {
			return;
		}
		var urls = config();
		if ( ! urls.js ) {
			return;
		}
		loading = true;
		if ( urls.css ) {
			var link = document.createElement( 'link' );
			link.rel = 'stylesheet';
			link.href = urls.css;
			document.head.appendChild( link );
		}
		var script = document.createElement( 'script' );
		script.src = urls.js;
		script.onload = function () {
			loading = false;
			renderAll();
		};
		document.head.appendChild( script );
	}

	if ( document.readyState !== 'loading' ) {
		ensureLeaflet();
	} else {
		document.addEventListener( 'DOMContentLoaded', ensureLeaflet );
	}
	document.addEventListener( 'luma-viewer:rendered', ensureLeaflet );
} )();
