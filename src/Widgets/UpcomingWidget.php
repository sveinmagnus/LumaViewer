<?php
/**
 * "Upcoming events" classic widget.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Widgets;

use LumaViewer\View\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * A compact upcoming-events list for classic (non-block) sidebars. Renders
 * through the shared {@see Renderer} (minimal list layout). The renderer is
 * injected statically because WordPress instantiates widgets itself.
 */
class UpcomingWidget extends \WP_Widget {

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
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'luma_viewer_upcoming',
			__( 'Luma: Upcoming Events', 'luma-viewer' ),
			array( 'description' => __( 'A compact list of upcoming Lu.ma events.', 'luma-viewer' ) )
		);
	}

	/**
	 * Front-end output.
	 *
	 * @param array $args     Sidebar args.
	 * @param array $instance Widget settings.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		if ( ! self::$renderer instanceof Renderer ) {
			return;
		}

		$title = isset( $instance['title'] ) ? (string) $instance['title'] : '';
		$count = isset( $instance['count'] ) ? max( 1, (int) $instance['count'] ) : 5;

		// Renderer output is escaped within its templates.
		$list = self::$renderer->calendar(
			array(
				'view'   => 'list',
				'layout' => 'minimal',
				'count'  => $count,
				'chrome' => '0',
			)
		);

		// $args wrappers are provided by the theme/sidebar registration.
		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		if ( '' !== $title ) {
			echo $args['before_title'] . esc_html( $title ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		echo $list; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Settings form.
	 *
	 * @param array $instance Current settings.
	 * @return string
	 */
	public function form( $instance ) {
		$title = isset( $instance['title'] ) ? (string) $instance['title'] : '';
		$count = isset( $instance['count'] ) ? (int) $instance['count'] : 5;

		printf(
			'<p><label for="%1$s">%2$s</label><input class="widefat" id="%1$s" name="%3$s" type="text" value="%4$s" /></p>',
			esc_attr( $this->get_field_id( 'title' ) ),
			esc_html__( 'Title:', 'luma-viewer' ),
			esc_attr( $this->get_field_name( 'title' ) ),
			esc_attr( $title )
		);
		printf(
			'<p><label for="%1$s">%2$s</label> <input class="tiny-text" id="%1$s" name="%3$s" type="number" min="1" max="100" value="%4$d" /></p>',
			esc_attr( $this->get_field_id( 'count' ) ),
			esc_html__( 'Number of events:', 'luma-viewer' ),
			esc_attr( $this->get_field_name( 'count' ) ),
			(int) $count
		);

		return '';
	}

	/**
	 * Persist settings.
	 *
	 * @param array $new_instance New settings.
	 * @param array $old_instance Previous settings.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		unset( $old_instance );
		return array(
			'title' => sanitize_text_field( isset( $new_instance['title'] ) ? $new_instance['title'] : '' ),
			'count' => max( 1, min( 100, (int) ( isset( $new_instance['count'] ) ? $new_instance['count'] : 5 ) ) ),
		);
	}
}
