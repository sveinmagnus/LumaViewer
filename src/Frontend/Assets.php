<?php
/**
 * Front-end asset registration.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Frontend;

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
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ) );
	}

	/**
	 * Register (not enqueue) styles.
	 *
	 * @return void
	 */
	public function register_assets() {
		wp_register_style(
			'luma-viewer',
			LUMA_VIEWER_URL . 'assets/css/luma-viewer.css',
			array(),
			LUMA_VIEWER_VERSION
		);

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
				'rest'  => esc_url_raw( rest_url( 'lumaviewer/v1/events' ) ),
				'nonce' => wp_create_nonce( 'wp_rest' ),
			)
		);
	}
}
