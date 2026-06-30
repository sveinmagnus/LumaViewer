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
			'calendar'  => array( $this, 'render_calendar' ),
			'event'     => array( $this, 'render_event' ),
			'featured'  => array( $this, 'render_featured' ),
			'countdown' => array( $this, 'render_countdown' ),
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
			'view'          => isset( $attributes['view'] ) ? sanitize_key( (string) $attributes['view'] ) : '',
			'tag'           => isset( $attributes['tag'] ) ? sanitize_text_field( (string) $attributes['tag'] ) : '',
			'date'          => isset( $attributes['date'] ) ? sanitize_text_field( (string) $attributes['date'] ) : '',
			'layout'        => isset( $attributes['layout'] ) ? sanitize_key( (string) $attributes['layout'] ) : '',
			'group_by'      => isset( $attributes['group_by'] ) ? sanitize_key( (string) $attributes['group_by'] ) : '',
			'calendar'      => isset( $attributes['calendar'] ) ? sanitize_text_field( (string) $attributes['calendar'] ) : '',
			'filters'       => empty( $attributes['filters'] ) ? '' : 'true',
			'past'          => empty( $attributes['past'] ) ? '' : 'true',
			'pagination'    => isset( $attributes['pagination'] ) ? sanitize_key( (string) $attributes['pagination'] ) : '',
			'offset'        => isset( $attributes['offset'] ) ? absint( $attributes['offset'] ) : 0,
			'from'          => isset( $attributes['from'] ) ? sanitize_text_field( (string) $attributes['from'] ) : '',
			'to'            => isset( $attributes['to'] ) ? sanitize_text_field( (string) $attributes['to'] ) : '',
			'order'         => isset( $attributes['order'] ) ? sanitize_key( (string) $attributes['order'] ) : '',
			'online'        => isset( $attributes['online'] ) ? sanitize_key( (string) $attributes['online'] ) : '',
			'free'          => isset( $attributes['free'] ) ? sanitize_key( (string) $attributes['free'] ) : '',
			'tags'          => isset( $attributes['tags'] ) ? sanitize_text_field( (string) $attributes['tags'] ) : '',
			'show_cover'    => isset( $attributes['show_cover'] ) ? sanitize_text_field( (string) $attributes['show_cover'] ) : '',
			'show_location' => isset( $attributes['show_location'] ) ? sanitize_text_field( (string) $attributes['show_location'] ) : '',
			'show_host'     => isset( $attributes['show_host'] ) ? sanitize_text_field( (string) $attributes['show_host'] ) : '',
			'show_price'    => isset( $attributes['show_price'] ) ? sanitize_text_field( (string) $attributes['show_price'] ) : '',
			'show_excerpt'  => isset( $attributes['show_excerpt'] ) ? sanitize_text_field( (string) $attributes['show_excerpt'] ) : '',
			'show_tags'     => isset( $attributes['show_tags'] ) ? sanitize_text_field( (string) $attributes['show_tags'] ) : '',
			'show_relative' => isset( $attributes['show_relative'] ) ? sanitize_text_field( (string) $attributes['show_relative'] ) : '',
		);
		if ( ! empty( $attributes['count'] ) ) {
			$atts['count'] = absint( $attributes['count'] );
		}
		if ( ! empty( $attributes['excerpt_words'] ) ) {
			$atts['excerpt_words'] = absint( $attributes['excerpt_words'] );
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

	/**
	 * Render the featured-event block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_featured( $attributes ) {
		$attributes = (array) $attributes;
		$id         = isset( $attributes['id'] ) ? sanitize_text_field( (string) $attributes['id'] ) : '';

		return $this->renderer->featured( array( 'id' => $id ) );
	}

	/**
	 * Render the countdown block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_countdown( $attributes ) {
		$attributes = (array) $attributes;
		$id         = isset( $attributes['id'] ) ? sanitize_text_field( (string) $attributes['id'] ) : '';

		return $this->renderer->countdown( array( 'id' => $id ) );
	}
}
