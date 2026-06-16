<?php
/**
 * Gutenberg block registration.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Blocks;

use LumaViewer\View\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Luma Calendar and Luma Event blocks. Both are dynamic
 * (server-rendered) and reuse the shared {@see Renderer}, so the ServerSideRender
 * editor preview and the front end produce identical markup.
 *
 * Block bundles build to /build/<name>/. If the build is missing (deps not
 * compiled), the blocks are simply not registered — the shortcodes still work.
 */
class Blocks {

	/** @var Renderer */
	private $renderer;

	/**
	 * Constructor.
	 *
	 * @param Renderer $renderer Shared renderer.
	 */
	public function __construct( Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register the blocks from their built metadata.
	 *
	 * @return void
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$blocks = array(
			'calendar' => array( $this, 'render_calendar' ),
			'event'    => array( $this, 'render_event' ),
		);

		foreach ( $blocks as $name => $callback ) {
			$dir = LUMA_VIEWER_DIR . 'build/' . $name;
			if ( is_readable( $dir . '/block.json' ) ) {
				register_block_type( $dir, array( 'render_callback' => $callback ) );
			}
		}
	}

	/**
	 * Render the calendar block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_calendar( $attributes ) {
		$attributes = (array) $attributes;

		$atts = array(
			'view'     => isset( $attributes['view'] ) ? sanitize_key( (string) $attributes['view'] ) : '',
			'tag'      => isset( $attributes['tag'] ) ? sanitize_text_field( (string) $attributes['tag'] ) : '',
			'date'     => isset( $attributes['date'] ) ? sanitize_text_field( (string) $attributes['date'] ) : '',
			'layout'   => isset( $attributes['layout'] ) ? sanitize_key( (string) $attributes['layout'] ) : '',
			'group_by' => isset( $attributes['group_by'] ) ? sanitize_key( (string) $attributes['group_by'] ) : '',
		);
		if ( ! empty( $attributes['count'] ) ) {
			$atts['count'] = absint( $attributes['count'] );
		}

		return $this->renderer->calendar( $atts );
	}

	/**
	 * Render the single-event block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_event( $attributes ) {
		$attributes = (array) $attributes;
		$id         = isset( $attributes['id'] ) ? sanitize_text_field( (string) $attributes['id'] ) : '';

		return $this->renderer->event( array( 'id' => $id ) );
	}
}
