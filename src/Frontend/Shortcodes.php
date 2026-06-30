<?php
/**
 * Shortcodes: the universal front-end surface.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Frontend;

use LumaViewer\View\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Registers `[luma_calendar]` and `[luma_event]`. Both delegate to the shared
 * {@see Renderer}, so they share markup with the blocks and Elementor widgets.
 */
class Shortcodes {

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
		add_shortcode( 'luma_calendar', array( $this, 'calendar' ) );
		add_shortcode( 'luma_event', array( $this, 'event' ) );
		add_shortcode( 'luma_featured', array( $this, 'featured' ) );
		add_shortcode( 'luma_countdown', array( $this, 'countdown' ) );
	}

	/**
	 * `[luma_calendar view="" tag="" count="" date=""]`
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function calendar( $atts ) {
		$atts = shortcode_atts(
			array(
				'view'          => '',
				'tag'           => '',
				'count'         => '',
				'date'          => '',
				'layout'        => '',
				'group_by'      => '',
				'calendar'      => '',
				'filters'       => '',
				'offset'        => '',
				'past'          => '',
				'from'          => '',
				'to'            => '',
				'show_cover'    => '',
				'show_location' => '',
				'show_host'     => '',
				'show_price'    => '',
				'show_excerpt'  => '',
				'show_tags'     => '',
				'show_relative' => '',
				'excerpt_words' => '',
			),
			$atts,
			'luma_calendar'
		);

		$clean = array(
			'view'          => sanitize_key( $atts['view'] ),
			'tag'           => sanitize_text_field( $atts['tag'] ),
			'date'          => sanitize_text_field( $atts['date'] ),
			'layout'        => sanitize_key( $atts['layout'] ),
			'group_by'      => sanitize_key( $atts['group_by'] ),
			'calendar'      => sanitize_text_field( $atts['calendar'] ),
			'filters'       => sanitize_text_field( $atts['filters'] ),
			'offset'        => absint( $atts['offset'] ),
			'past'          => sanitize_text_field( $atts['past'] ),
			'from'          => sanitize_text_field( $atts['from'] ),
			'to'            => sanitize_text_field( $atts['to'] ),
			'show_cover'    => sanitize_text_field( $atts['show_cover'] ),
			'show_location' => sanitize_text_field( $atts['show_location'] ),
			'show_host'     => sanitize_text_field( $atts['show_host'] ),
			'show_price'    => sanitize_text_field( $atts['show_price'] ),
			'show_excerpt'  => sanitize_text_field( $atts['show_excerpt'] ),
			'show_tags'     => sanitize_text_field( $atts['show_tags'] ),
			'show_relative' => sanitize_text_field( $atts['show_relative'] ),
			'excerpt_words' => absint( $atts['excerpt_words'] ),
		);
		if ( '' !== $atts['count'] ) {
			$clean['count'] = absint( $atts['count'] );
		}

		return $this->renderer->calendar( $clean );
	}

	/**
	 * `[luma_event id=""]`
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function event( $atts ) {
		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'luma_event' );
		return $this->renderer->event( array( 'id' => sanitize_text_field( $atts['id'] ) ) );
	}

	/**
	 * `[luma_featured id=""]` — hero for the next (or a chosen) event.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function featured( $atts ) {
		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'luma_featured' );
		return $this->renderer->featured( array( 'id' => sanitize_text_field( $atts['id'] ) ) );
	}

	/**
	 * `[luma_countdown id=""]` — live countdown to the next (or a chosen) event.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public function countdown( $atts ) {
		$atts = shortcode_atts( array( 'id' => '' ), $atts, 'luma_countdown' );
		return $this->renderer->countdown( array( 'id' => sanitize_text_field( $atts['id'] ) ) );
	}
}
