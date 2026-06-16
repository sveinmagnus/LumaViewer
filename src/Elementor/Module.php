<?php
/**
 * Elementor integration module.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Elementor;

use Elementor\Widgets_Manager;
use LumaViewer\Elementor\Widgets\CalendarWidget;
use LumaViewer\Elementor\Widgets\EventWidget;
use LumaViewer\View\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Elementor widgets. The `elementor/widgets/register` action only
 * fires when Elementor is active, so the widget classes (which extend Elementor
 * base classes) are loaded only when it is safe to do so.
 */
class Module {

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
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * Register widgets with Elementor.
	 *
	 * @param Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( Widgets_Manager $widgets_manager ) {
		CalendarWidget::set_renderer( $this->renderer );
		EventWidget::set_renderer( $this->renderer );

		$widgets_manager->register( new CalendarWidget() );
		$widgets_manager->register( new EventWidget() );
	}
}
