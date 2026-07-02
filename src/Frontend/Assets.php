<?php
/**
 * Front-end asset registration.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Frontend;

use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the front-end stylesheet. The renderer enqueues it on demand, so
 * pages without a calendar pay no cost.
 */
class Assets {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		// The stylesheet is registered on `init` so it's available both on the
		// front end and inside the block editor (referenced as each block's
		// `style` handle), giving server-rendered block previews their styling.
		add_action( 'init', array( $this, 'register_style' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * Register the front-end stylesheet + accent inline style.
	 *
	 * @return void
	 */
	public function register_style() {
		wp_register_style(
			'luma-viewer',
			LUMA_VIEWER_URL . 'assets/css/luma-viewer.css',
			array(),
			LUMA_VIEWER_VERSION
		);

		$accent = sanitize_hex_color( (string) Settings::get( 'accent_color' ) );
		if ( $accent ) {
			wp_add_inline_style( 'luma-viewer', '.luma-viewer{--lv-accent:' . $accent . ';}' );
		}
	}

	/**
	 * Register the front-end scripts.
	 *
	 * @return void
	 */
	public function register_scripts() {
		wp_register_script(
			'luma-viewer',
			LUMA_VIEWER_URL . 'assets/js/luma-viewer.js',
			array(),
			LUMA_VIEWER_VERSION,
			true
		);

		wp_localize_script(
			'luma-viewer',
			'lumaViewer',
			array(
				'rest'      => esc_url_raw( rest_url( 'lumaviewer/v1/events' ) ),
				'restEvent' => esc_url_raw( rest_url( 'lumaviewer/v1/event' ) ),
				// Only logged-in users get (and need) a REST nonce. An anonymous
				// visitor must NOT send a nonce, or a stale page-cached one would be
				// rejected with 403 and break AJAX navigation entirely.
				'nonce'     => is_user_logged_in() ? wp_create_nonce( 'wp_rest' ) : '',
				'i18n'      => array(
					'loading' => __( 'Loading…', 'luma-viewer' ),
					'close'   => __( 'Close', 'luma-viewer' ),
					// Single-letter countdown unit labels.
					'cdDay'   => _x( 'd', 'countdown days', 'luma-viewer' ),
					'cdHour'  => _x( 'h', 'countdown hours', 'luma-viewer' ),
					'cdMin'   => _x( 'm', 'countdown minutes', 'luma-viewer' ),
					'cdSec'   => _x( 's', 'countdown seconds', 'luma-viewer' ),
				),
			)
		);

		wp_register_script(
			'luma-viewer-map',
			LUMA_VIEWER_URL . 'assets/js/luma-viewer-map.js',
			array( 'luma-viewer' ),
			LUMA_VIEWER_VERSION,
			true
		);
		/**
		 * Filters the Leaflet asset URLs used by the map view.
		 *
		 * @param array $urls { 'css' => string, 'js' => string }
		 */
		$leaflet = apply_filters(
			'luma_viewer_leaflet_assets',
			array(
				'css'                 => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
				'js'                  => 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
				'cluster_css'         => 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css',
				'cluster_css_default' => 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css',
				'cluster_js'          => 'https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js',
			)
		);
		wp_localize_script( 'luma-viewer-map', 'lumaViewerMap', array( 'leaflet' => $leaflet ) );
	}
}
