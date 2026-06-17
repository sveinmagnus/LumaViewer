<?php
/**
 * Elementor "Luma Calendar" widget.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Elementor\Widgets;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;
use LumaViewer\View\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Thin Elementor wrapper around the shared {@see Renderer}, so its output
 * matches the shortcode and block. The renderer is injected statically because
 * Elementor re-instantiates widgets on its own during rendering.
 */
class CalendarWidget extends Widget_Base {

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
		return 'luma_calendar';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'Luma Calendar', 'luma-viewer' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-calendar';
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
		return array( 'luma', 'calendar', 'events' );
	}

	/**
	 * Register Elementor controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->start_controls_section( 'content', array( 'label' => __( 'Calendar', 'luma-viewer' ) ) );

		$this->add_control(
			'view',
			array(
				'label'   => __( 'View', 'luma-viewer' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''        => __( 'Site default', 'luma-viewer' ),
					'list'    => __( 'List', 'luma-viewer' ),
					'week'    => __( 'Week', 'luma-viewer' ),
					'month'   => __( 'Month', 'luma-viewer' ),
					'day'     => __( 'Day', 'luma-viewer' ),
					'photo'   => __( 'Photo', 'luma-viewer' ),
					'summary' => __( 'Summary', 'luma-viewer' ),
				),
			)
		);
		$this->add_control(
			'tag',
			array(
				'label' => __( 'Category (tag)', 'luma-viewer' ),
				'type'  => Controls_Manager::TEXT,
			)
		);
		$this->add_control(
			'count',
			array(
				'label'   => __( 'Number of events', 'luma-viewer' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
			)
		);
		$this->add_control(
			'date',
			array(
				'label'       => __( 'Anchor date', 'luma-viewer' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'YYYY-MM for Month, YYYY-MM-DD for Week/Day.', 'luma-viewer' ),
			)
		);
		$this->add_control(
			'layout',
			array(
				'label'       => __( 'List layout', 'luma-viewer' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''        => __( 'Cards', 'luma-viewer' ),
					'compact' => __( 'Compact', 'luma-viewer' ),
					'minimal' => __( 'Minimal', 'luma-viewer' ),
				),
				'description' => __( 'Applies to List, Week and Day views.', 'luma-viewer' ),
			)
		);
		$this->add_control(
			'group_by',
			array(
				'label'       => __( 'Group list by', 'luma-viewer' ),
				'type'        => Controls_Manager::SELECT,
				'default'     => '',
				'options'     => array(
					''      => __( 'Day', 'luma-viewer' ),
					'month' => __( 'Month', 'luma-viewer' ),
					'none'  => __( 'No grouping', 'luma-viewer' ),
				),
				'description' => __( 'Applies to the List view.', 'luma-viewer' ),
			)
		);
		$this->add_control(
			'calendar',
			array(
				'label'       => __( 'Calendar ID', 'luma-viewer' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Organization mode only: limit to one calendar (its api_id).', 'luma-viewer' ),
			)
		);
		$this->add_control(
			'filters',
			array(
				'label'       => __( 'Search & category filters', 'luma-viewer' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Adds a search box and category chips to list-style views.', 'luma-viewer' ),
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
		$atts     = array(
			'view'     => isset( $settings['view'] ) ? sanitize_key( (string) $settings['view'] ) : '',
			'tag'      => isset( $settings['tag'] ) ? sanitize_text_field( (string) $settings['tag'] ) : '',
			'date'     => isset( $settings['date'] ) ? sanitize_text_field( (string) $settings['date'] ) : '',
			'layout'   => isset( $settings['layout'] ) ? sanitize_key( (string) $settings['layout'] ) : '',
			'group_by' => isset( $settings['group_by'] ) ? sanitize_key( (string) $settings['group_by'] ) : '',
			'calendar' => isset( $settings['calendar'] ) ? sanitize_text_field( (string) $settings['calendar'] ) : '',
			'filters'  => ( isset( $settings['filters'] ) && 'yes' === $settings['filters'] ) ? 'true' : '',
		);
		if ( ! empty( $settings['count'] ) ) {
			$atts['count'] = absint( $settings['count'] );
		}

		// Renderer output is escaped within its templates.
		echo self::$renderer->calendar( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
