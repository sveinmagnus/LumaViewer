<?php
/**
 * Shared view renderer.
 *
 * @package LumaViewer
 */

namespace LumaViewer\View;

use LumaViewer\Events\Repository;
use LumaViewer\Membership\Gate;
use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * The single render path used by shortcodes, blocks, and Elementor widgets.
 * Takes display args, pulls events from the {@see Repository}, and renders the
 * chosen (theme-overridable) view template, wrapped with a toolbar that the
 * front-end script uses for AJAX view-switching and date navigation.
 */
class Renderer {

	const VIEWS = array( 'list', 'month', 'day', 'photo', 'summary' );

	/** @var Repository */
	private $repo;

	/** @var TemplateLoader */
	private $loader;

	/** @var Formatter */
	private $formatter;

	/** @var Gate */
	private $gate;

	/**
	 * Constructor.
	 *
	 * @param Repository     $repo      Event repository.
	 * @param TemplateLoader $loader    Template loader.
	 * @param Formatter      $formatter Date formatter.
	 * @param Gate           $gate      Membership visibility gate.
	 */
	public function __construct( Repository $repo, TemplateLoader $loader, Formatter $formatter, Gate $gate ) {
		$this->repo      = $repo;
		$this->loader    = $loader;
		$this->formatter = $formatter;
		$this->gate      = $gate;
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

		$tag   = isset( $atts['tag'] ) ? (string) $atts['tag'] : '';
		$count = isset( $atts['count'] ) ? (int) $atts['count'] : (int) Settings::get( 'per_page' );
		$date  = isset( $atts['date'] ) ? (string) $atts['date'] : '';

		$args   = array(
			'count' => $count,
			'tag'   => $tag,
		);
		$anchor = null;
		$nav    = null;

		if ( 'month' === $view ) {
			list( $after, $before, $anchor ) = $this->month_bounds( $date );
			$args['after']                   = $after;
			$args['before']                  = $before;
			$args['count']                   = 0;
			$nav                             = array(
				'prev' => $anchor->modify( '-1 month' )->format( 'Y-m' ),
				'next' => $anchor->modify( '+1 month' )->format( 'Y-m' ),
			);
		} elseif ( 'day' === $view ) {
			list( $after, $before, $anchor ) = $this->day_bounds( $date );
			$args['after']                   = $after;
			$args['before']                  = $before;
			$args['count']                   = 0;
			$nav                             = array(
				'prev' => $anchor->modify( '-1 day' )->format( 'Y-m-d' ),
				'next' => $anchor->modify( '+1 day' )->format( 'Y-m-d' ),
			);
		}

		$result = $this->repo->get_events( $args );
		$events = $result['events'];

		$teaser_ids = array();
		if ( $this->gate->is_enabled() ) {
			$user_id = get_current_user_id();
			$visible = array();
			foreach ( $events as $event ) {
				$decision = $this->gate->resolve( $event, $user_id );
				if ( Gate::HIDDEN === $decision ) {
					continue;
				}
				if ( Gate::TEASER === $decision ) {
					$teaser_ids[ $event->id() ] = true;
				}
				$visible[] = $event;
			}
			$events = $visible;
			// Visibility varies per logged-in user, so keep those responses out of
			// shared / full-page caches. Anonymous visitors all see the same
			// teaser/public result, which stays cacheable.
			if ( is_user_logged_in() ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- standard cache-plugin bypass constant.
				if ( ! defined( 'DONOTCACHEPAGE' ) ) {
					define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
				}
				if ( ! headers_sent() ) {
					nocache_headers();
				}
			}
		}

		wp_enqueue_style( 'luma-viewer' );
		wp_enqueue_script( 'luma-viewer' );

		$loader    = $this->loader;
		$formatter = $this->formatter;
		$cta_text  = (string) Settings::get( 'gate_cta_text' );
		$cta_url   = $this->cta_url();

		$render_card = static function ( $event, $teaser = false ) use ( $loader, $formatter, $cta_text, $cta_url ) {
			return $loader->capture(
				'partials/event-card',
				array(
					'event'         => $event,
					'formatter'     => $formatter,
					'teaser'        => (bool) $teaser,
					'gate_cta_text' => $cta_text,
					'gate_cta_url'  => $cta_url,
				)
			);
		};

		$body = $this->loader->capture(
			$view,
			array(
				'events'      => $events,
				'error'       => $result['error'],
				'formatter'   => $this->formatter,
				'render_card' => $render_card,
				'teaser_ids'  => $teaser_ids,
				'anchor'      => $anchor,
				'atts'        => $atts,
			)
		);

		$data = sprintf(
			' data-lv-view="%s" data-lv-tag="%s" data-lv-count="%s" data-lv-date="%s"',
			esc_attr( $view ),
			esc_attr( $tag ),
			esc_attr( (string) $count ),
			esc_attr( $date )
		);

		return sprintf(
			'<div class="luma-viewer luma-viewer--%1$s" role="region" aria-label="%2$s" tabindex="-1" aria-busy="false"%3$s>%4$s%5$s</div>',
			esc_attr( $view ),
			esc_attr__( 'Events', 'luma-viewer' ),
			$data,
			$this->toolbar( $view, $nav ),
			$body
		);
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
		$event  = $result['event'];

		wp_enqueue_style( 'luma-viewer' );

		$teaser  = false;
		$blocked = false;
		if ( $event && '' !== $event->id() && $this->gate->is_enabled() ) {
			$decision = $this->gate->resolve( $event, get_current_user_id() );
			$blocked  = Gate::HIDDEN === $decision;
			$teaser   = Gate::TEASER === $decision;
			if ( ( $blocked || $teaser ) && is_user_logged_in() ) {
				if ( ! defined( 'DONOTCACHEPAGE' ) ) {
					define( 'DONOTCACHEPAGE', true ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound
				}
				if ( ! headers_sent() ) {
					nocache_headers();
				}
			}
		}

		$body = $this->loader->capture(
			'single',
			array(
				'event'         => $event,
				'error'         => $result['error'],
				'formatter'     => $this->formatter,
				'teaser'        => $teaser,
				'blocked'       => $blocked,
				'gate_cta_text' => (string) Settings::get( 'gate_cta_text' ),
				'gate_cta_url'  => $this->cta_url(),
			)
		);

		return sprintf( '<div class="luma-viewer luma-viewer--single">%s</div>', $body );
	}

	/**
	 * The URL for the "join / log in" call to action on gated events.
	 *
	 * @return string
	 */
	private function cta_url() {
		$url = (string) Settings::get( 'gate_cta_url' );
		return '' !== $url ? $url : wp_login_url();
	}

	/**
	 * Build the toolbar (view tabs + optional prev/next navigation).
	 *
	 * @param string     $view Current view.
	 * @param array|null $nav  Navigation targets ('prev'/'next'), or null.
	 * @return string
	 */
	private function toolbar( $view, $nav ) {
		$labels = array(
			'list'    => __( 'List', 'luma-viewer' ),
			'month'   => __( 'Month', 'luma-viewer' ),
			'day'     => __( 'Day', 'luma-viewer' ),
			'photo'   => __( 'Photo', 'luma-viewer' ),
			'summary' => __( 'Summary', 'luma-viewer' ),
		);

		$tabs = '';
		foreach ( $labels as $key => $label ) {
			$tabs .= sprintf(
				'<button type="button" class="luma-viewer__view-tab%1$s" data-lv-action="view" data-lv-view="%2$s"%3$s>%4$s</button>',
				$key === $view ? ' is-active' : '',
				esc_attr( $key ),
				$key === $view ? ' aria-pressed="true"' : '',
				esc_html( $label )
			);
		}

		$nav_html = '';
		if ( is_array( $nav ) ) {
			$nav_html = sprintf(
				'<div class="luma-viewer__nav"><button type="button" class="luma-viewer__nav-prev" data-lv-action="nav" data-lv-date="%1$s" aria-label="%2$s">&#8249;</button><button type="button" class="luma-viewer__nav-next" data-lv-action="nav" data-lv-date="%3$s" aria-label="%4$s">&#8250;</button></div>',
				esc_attr( $nav['prev'] ),
				esc_attr__( 'Previous', 'luma-viewer' ),
				esc_attr( $nav['next'] ),
				esc_attr__( 'Next', 'luma-viewer' )
			);
		}

		return sprintf(
			'<div class="luma-viewer__toolbar"><nav class="luma-viewer__views" aria-label="%1$s">%2$s</nav>%3$s</div>',
			esc_attr__( 'Calendar views', 'luma-viewer' ),
			$tabs,
			$nav_html
		);
	}

	/**
	 * Compute first/last instants of a month plus the anchor date.
	 *
	 * @param string $date Anchor (Y-m or Y-m-d), or empty for the current month.
	 * @return array{0:string,1:string,2:\DateTimeImmutable}
	 */
	private function month_bounds( $date ) {
		$base  = $this->parse_anchor( $date );
		$first = $base->modify( 'first day of this month' )->setTime( 0, 0, 0 );
		$last  = $base->modify( 'last day of this month' )->setTime( 23, 59, 59 );

		return array( $first->format( 'c' ), $last->format( 'c' ), $first );
	}

	/**
	 * Compute start/end instants of a single day plus the anchor date.
	 *
	 * @param string $date Anchor (Y-m-d), or empty for today.
	 * @return array{0:string,1:string,2:\DateTimeImmutable}
	 */
	private function day_bounds( $date ) {
		$base  = $this->parse_anchor( $date );
		$start = $base->setTime( 0, 0, 0 );
		$end   = $base->setTime( 23, 59, 59 );

		return array( $start->format( 'c' ), $end->format( 'c' ), $start );
	}

	/**
	 * Parse an anchor date string in the site time zone, falling back to now and
	 * clamping to a sane window so date navigation can't be used to enumerate
	 * unbounded cache entries / API calls.
	 *
	 * @param string $date Date string.
	 * @return \DateTimeImmutable
	 */
	private function parse_anchor( $date ) {
		$tz = wp_timezone();
		try {
			$base = '' !== $date ? new \DateTimeImmutable( $date, $tz ) : new \DateTimeImmutable( 'now', $tz );
		} catch ( \Exception $e ) {
			$base = new \DateTimeImmutable( 'now', $tz );
		}

		$min = new \DateTimeImmutable( '-5 years', $tz );
		$max = new \DateTimeImmutable( '+5 years', $tz );
		if ( $base < $min ) {
			$base = $min;
		} elseif ( $base > $max ) {
			$base = $max;
		}

		return $base;
	}
}
