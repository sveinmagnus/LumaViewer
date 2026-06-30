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
					''         => __( 'Site default', 'luma-viewer' ),
					'list'     => __( 'List', 'luma-viewer' ),
					'week'     => __( 'Week', 'luma-viewer' ),
					'month'    => __( 'Month', 'luma-viewer' ),
					'day'      => __( 'Day', 'luma-viewer' ),
					'photo'    => __( 'Photo', 'luma-viewer' ),
					'summary'  => __( 'Summary', 'luma-viewer' ),
					'map'      => __( 'Map', 'luma-viewer' ),
					'carousel' => __( 'Carousel', 'luma-viewer' ),
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
		$this->add_control(
			'past',
			array(
				'label'       => __( 'Include past events', 'luma-viewer' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Show recent past events as well as upcoming ones.', 'luma-viewer' ),
			)
		);
		$this->add_control(
			'pagination',
			array(
				'label'   => __( 'Pagination', 'luma-viewer' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''        => __( 'Site default', 'luma-viewer' ),
					'more'    => __( 'Load more', 'luma-viewer' ),
					'numbers' => __( 'Numbered pages', 'luma-viewer' ),
				),
			)
		);
		$this->add_control(
			'quickview',
			array(
				'label'       => __( 'Quick view', 'luma-viewer' ),
				'type'        => Controls_Manager::SWITCHER,
				'description' => __( 'Open an event summary in a popup instead of leaving the page.', 'luma-viewer' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'filtering', array( 'label' => __( 'Filtering & order', 'luma-viewer' ) ) );

		$this->add_control(
			'order',
			array(
				'label'   => __( 'Order', 'luma-viewer' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''     => __( 'Default', 'luma-viewer' ),
					'asc'  => __( 'Soonest first', 'luma-viewer' ),
					'desc' => __( 'Latest first', 'luma-viewer' ),
				),
			)
		);
		$this->add_control(
			'online',
			array(
				'label'   => __( 'Location', 'luma-viewer' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''          => __( 'Any', 'luma-viewer' ),
					'online'    => __( 'Online only', 'luma-viewer' ),
					'in_person' => __( 'In person only', 'luma-viewer' ),
				),
			)
		);
		$this->add_control(
			'free',
			array(
				'label'   => __( 'Price', 'luma-viewer' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''     => __( 'Any', 'luma-viewer' ),
					'free' => __( 'Free only', 'luma-viewer' ),
					'paid' => __( 'Paid only', 'luma-viewer' ),
				),
			)
		);
		$this->add_control(
			'tags',
			array(
				'label'       => __( 'Tags', 'luma-viewer' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Comma-separated; matches events with any of these tags.', 'luma-viewer' ),
			)
		);
		$this->add_control(
			'offset',
			array(
				'label'   => __( 'Skip first (offset)', 'luma-viewer' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 0,
			)
		);
		$this->add_control(
			'from',
			array(
				'label'       => __( 'From date', 'luma-viewer' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'YYYY-MM-DD lower bound (list-style views).', 'luma-viewer' ),
			)
		);
		$this->add_control(
			'to',
			array(
				'label'       => __( 'To date', 'luma-viewer' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'YYYY-MM-DD upper bound (list-style views).', 'luma-viewer' ),
			)
		);

		$this->end_controls_section();

		$this->start_controls_section( 'elements', array( 'label' => __( 'Card elements', 'luma-viewer' ) ) );

		foreach ( $this->element_controls() as $key => $label ) {
			$this->add_control(
				$key,
				array(
					'label'   => $label,
					'type'    => Controls_Manager::SELECT,
					'default' => '',
					'options' => array(
						''  => __( 'Default', 'luma-viewer' ),
						'1' => __( 'Show', 'luma-viewer' ),
						'0' => __( 'Hide', 'luma-viewer' ),
					),
				)
			);
		}
		$this->add_control(
			'excerpt_words',
			array(
				'label'       => __( 'Excerpt length (words)', 'luma-viewer' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'description' => __( '0 uses the site default.', 'luma-viewer' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * The card-element visibility controls (key => label).
	 *
	 * @return array<string,string>
	 */
	private function element_controls() {
		return array(
			'show_cover'    => __( 'Cover image', 'luma-viewer' ),
			'show_location' => __( 'Location', 'luma-viewer' ),
			'show_host'     => __( 'Hosts', 'luma-viewer' ),
			'show_price'    => __( 'Price / free badge', 'luma-viewer' ),
			'show_excerpt'  => __( 'Description excerpt', 'luma-viewer' ),
			'show_tags'     => __( 'Tags', 'luma-viewer' ),
			'show_relative' => __( 'Relative date', 'luma-viewer' ),
		);
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
			'view'          => isset( $settings['view'] ) ? sanitize_key( (string) $settings['view'] ) : '',
			'tag'           => isset( $settings['tag'] ) ? sanitize_text_field( (string) $settings['tag'] ) : '',
			'date'          => isset( $settings['date'] ) ? sanitize_text_field( (string) $settings['date'] ) : '',
			'layout'        => isset( $settings['layout'] ) ? sanitize_key( (string) $settings['layout'] ) : '',
			'group_by'      => isset( $settings['group_by'] ) ? sanitize_key( (string) $settings['group_by'] ) : '',
			'calendar'      => isset( $settings['calendar'] ) ? sanitize_text_field( (string) $settings['calendar'] ) : '',
			'filters'       => ( isset( $settings['filters'] ) && 'yes' === $settings['filters'] ) ? 'true' : '',
			'past'          => ( isset( $settings['past'] ) && 'yes' === $settings['past'] ) ? 'true' : '',
			'pagination'    => isset( $settings['pagination'] ) ? sanitize_key( (string) $settings['pagination'] ) : '',
			'quickview'     => ( isset( $settings['quickview'] ) && 'yes' === $settings['quickview'] ) ? 'true' : '',
			'order'         => isset( $settings['order'] ) ? sanitize_key( (string) $settings['order'] ) : '',
			'online'        => isset( $settings['online'] ) ? sanitize_key( (string) $settings['online'] ) : '',
			'free'          => isset( $settings['free'] ) ? sanitize_key( (string) $settings['free'] ) : '',
			'tags'          => isset( $settings['tags'] ) ? sanitize_text_field( (string) $settings['tags'] ) : '',
			'offset'        => isset( $settings['offset'] ) ? absint( $settings['offset'] ) : 0,
			'from'          => isset( $settings['from'] ) ? sanitize_text_field( (string) $settings['from'] ) : '',
			'to'            => isset( $settings['to'] ) ? sanitize_text_field( (string) $settings['to'] ) : '',
			'show_cover'    => isset( $settings['show_cover'] ) ? sanitize_text_field( (string) $settings['show_cover'] ) : '',
			'show_location' => isset( $settings['show_location'] ) ? sanitize_text_field( (string) $settings['show_location'] ) : '',
			'show_host'     => isset( $settings['show_host'] ) ? sanitize_text_field( (string) $settings['show_host'] ) : '',
			'show_price'    => isset( $settings['show_price'] ) ? sanitize_text_field( (string) $settings['show_price'] ) : '',
			'show_excerpt'  => isset( $settings['show_excerpt'] ) ? sanitize_text_field( (string) $settings['show_excerpt'] ) : '',
			'show_tags'     => isset( $settings['show_tags'] ) ? sanitize_text_field( (string) $settings['show_tags'] ) : '',
			'show_relative' => isset( $settings['show_relative'] ) ? sanitize_text_field( (string) $settings['show_relative'] ) : '',
		);
		if ( ! empty( $settings['count'] ) ) {
			$atts['count'] = absint( $settings['count'] );
		}
		if ( ! empty( $settings['excerpt_words'] ) ) {
			$atts['excerpt_words'] = absint( $settings['excerpt_words'] );
		}

		// Renderer output is escaped within its templates.
		echo self::$renderer->calendar( $atts ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
