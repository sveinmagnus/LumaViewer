<?php
/**
 * Block patterns for quick insertion.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Blocks;

defined( 'ABSPATH' ) || exit;

/**
 * Registers a few ready-made block patterns built on the Luma Calendar block.
 */
class Patterns {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_patterns' ), 11 );
	}

	/**
	 * Register the pattern category and patterns.
	 *
	 * @return void
	 */
	public function register_patterns() {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		if ( function_exists( 'register_block_pattern_category' ) ) {
			register_block_pattern_category( 'luma-viewer', array( 'label' => __( 'Luma Viewer', 'luma-viewer' ) ) );
		}

		$patterns = array(
			'upcoming-list' => array(
				'title'   => __( 'Upcoming events (list)', 'luma-viewer' ),
				'content' => '<!-- wp:luma-viewer/calendar {"view":"list","count":10} /-->',
			),
			'this-month'    => array(
				'title'   => __( 'This month', 'luma-viewer' ),
				'content' => '<!-- wp:luma-viewer/calendar {"view":"month"} /-->',
			),
			'photo-grid'    => array(
				'title'   => __( 'Event photo grid', 'luma-viewer' ),
				'content' => '<!-- wp:luma-viewer/calendar {"view":"photo","count":12} /-->',
			),
			'sidebar'       => array(
				'title'   => __( 'Compact sidebar list', 'luma-viewer' ),
				'content' => '<!-- wp:luma-viewer/calendar {"view":"list","layout":"minimal","count":5} /-->',
			),
		);

		foreach ( $patterns as $slug => $pattern ) {
			register_block_pattern(
				'luma-viewer/' . $slug,
				array(
					'title'      => $pattern['title'],
					'categories' => array( 'luma-viewer' ),
					'content'    => $pattern['content'],
				)
			);
		}
	}
}
