<?php
/**
 * Elementor "Luma Event" widget.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use LumaViewer\View\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Thin Elementor wrapper around the shared {@see Renderer}'s single-event view.
 */
class EventWidget extends Widget_Base {

	/** @var Renderer|null */
	private static $renderer = null;

	/**
	 * Inject the shared renderer.
	 *
	 * @param Renderer $renderer Renderer.
	 * @return void
	 */
	public static function set_renderer( Renderer $renderer ) {
		self::$renderer = $renderer;
	}

	/**
	 * @return string
	 */
	public function get_name() {
		return 'luma_event';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'Luma Event', 'luma-viewer' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-ticket';
	}

	/**
	 * @return string[]
	 */
	public function get_categories() {
		return array( 'general' );
	}

	/**
	 * @return string[]
	 */
	public function get_keywords() {
		return array( 'luma', 'event' );
	}

	/**
	 * Register Elementor controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Event', 'luma-viewer' ) ) );
		$this->add_control(
			'id',
			array(
				'label'       => __( 'Event ID', 'luma-viewer' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'The Luma event api_id (e.g. evt-…).', 'luma-viewer' ),
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Render the widget.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! self::$renderer instanceof Renderer ) {
			return;
		}

		$settings = $this->get_settings_for_display();
		$id       = isset( $settings['id'] ) ? sanitize_text_field( (string) $settings['id'] ) : '';

		// Renderer output is escaped within its templates.
		echo self::$renderer->event( array( 'id' => $id ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
