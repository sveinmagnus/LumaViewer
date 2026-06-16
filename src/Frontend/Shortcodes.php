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
				'view'  => '',
				'tag'   => '',
				'count' => '',
				'date'  => '',
			),
			$atts,
			'luma_calendar'
		);

		$clean = array(
			'view' => sanitize_key( $atts['view'] ),
			'tag'  => sanitize_text_field( $atts['tag'] ),
			'date' => sanitize_text_field( $atts['date'] ),
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
}
