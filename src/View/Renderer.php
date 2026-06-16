<?php
/**
 * Shared view renderer.
 *
 * @package LumaViewer
 */

namespace LumaViewer\View;

use LumaViewer\Events\Repository;
use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * The single render path used by shortcodes, blocks, and Elementor widgets.
 * Takes display args, pulls events from the {@see Repository}, and renders the
 * chosen (theme-overridable) view template.
 *
 * P1 implements `list` and `month`; `day`/`photo`/`summary` fall back to `list`
 * until later phases add their templates.
 */
class Renderer {

	const VIEWS       = array( 'list', 'month', 'day', 'photo', 'summary' );
	const IMPLEMENTED = array( 'list', 'month' );

	/** @var Repository */
	private $repo;

	/** @var TemplateLoader */
	private $loader;

	/** @var Formatter */
	private $formatter;

	/**
	 * Constructor.
	 *
	 * @param Repository     $repo      Event repository.
	 * @param TemplateLoader $loader    Template loader.
	 * @param Formatter      $formatter Date formatter.
	 */
	public function __construct( Repository $repo, TemplateLoader $loader, Formatter $formatter ) {
		$this->repo      = $repo;
		$this->loader    = $loader;
		$this->formatter = $formatter;
	}

	/**
	 * Render a calendar in the requested view.
	 *
	 * @param array $atts Display attributes: `view`, `tag`, `count`, `date`.
	 * @return string
	 */
	public function calendar( array $atts ) {
		$view = isset( $atts['view'] ) && in_array( $atts['view'], self::VIEWS, true )
			? $atts['view']
			: (string) Settings::get( 'default_view' );
		if ( ! in_array( $view, self::VIEWS, true ) ) {
			$view = 'list';
		}

		$template = in_array( $view, self::IMPLEMENTED, true ) ? $view : 'list';

		$args = array(
			'count' => isset( $atts['count'] ) ? (int) $atts['count'] : (int) Settings::get( 'per_page' ),
			'tag'   => isset( $atts['tag'] ) ? (string) $atts['tag'] : '',
		);

		$month = null;
		if ( 'month' === $template ) {
			list( $after, $before, $month ) = $this->month_bounds( isset( $atts['date'] ) ? (string) $atts['date'] : '' );
			$args['after']                  = $after;
			$args['before']                 = $before;
			$args['count']                  = 0;
		}

		$result = $this->repo->get_events( $args );

		wp_enqueue_style( 'luma-viewer' );

		$loader      = $this->loader;
		$formatter   = $this->formatter;
		$render_card = static function ( $event ) use ( $loader, $formatter ) {
			return $loader->capture(
				'partials/event-card',
				array(
					'event'     => $event,
					'formatter' => $formatter,
				)
			);
		};

		$body = $this->loader->capture(
			$template,
			array(
				'events'      => $result['events'],
				'error'       => $result['error'],
				'formatter'   => $this->formatter,
				'render_card' => $render_card,
				'month'       => $month,
				'atts'        => $atts,
			)
		);

		return sprintf( '<div class="luma-viewer luma-viewer--%s">%s</div>', esc_attr( $view ), $body );
	}

	/**
	 * Render a single event detail.
	 *
	 * @param array $atts Attributes: `id` (the event api_id).
	 * @return string
	 */
	public function event( array $atts ) {
		$id = isset( $atts['id'] ) ? (string) $atts['id'] : '';
		if ( '' === $id ) {
			return '';
		}

		$result = $this->repo->get_event( $id );

		wp_enqueue_style( 'luma-viewer' );

		$body = $this->loader->capture(
			'single',
			array(
				'event'     => $result['event'],
				'error'     => $result['error'],
				'formatter' => $this->formatter,
			)
		);

		return sprintf( '<div class="luma-viewer luma-viewer--single">%s</div>', $body );
	}

	/**
	 * Compute first/last instants of a month plus the anchor date.
	 *
	 * @param string $date Anchor (Y-m or Y-m-d), or empty for the current month.
	 * @return array{0:string,1:string,2:\DateTimeImmutable}
	 */
	private function month_bounds( $date ) {
		$tz = wp_timezone();
		try {
			$base = '' !== $date ? new \DateTimeImmutable( $date, $tz ) : new \DateTimeImmutable( 'now', $tz );
		} catch ( \Exception $e ) {
			$base = new \DateTimeImmutable( 'now', $tz );
		}

		$first = $base->modify( 'first day of this month' )->setTime( 0, 0, 0 );
		$last  = $base->modify( 'last day of this month' )->setTime( 23, 59, 59 );

		return array( $first->format( 'c' ), $last->format( 'c' ), $first );
	}
}
