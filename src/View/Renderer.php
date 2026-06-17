<?php
/**
 * Shared view renderer.
 *
 * @package LumaViewer
 */

namespace LumaViewer\View;

use LumaViewer\Events\Repository;
use LumaViewer\Membership\Gate;
use LumaViewer\Model\Event;
use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * The single render path used by shortcodes, blocks, and Elementor widgets.
 * Takes display args, pulls events from the {@see Repository}, and renders the
 * chosen (theme-overridable) view template, wrapped with a toolbar that the
 * front-end script uses for AJAX view-switching and date navigation.
 */
class Renderer {

	const VIEWS = array( 'list', 'week', 'month', 'day', 'photo', 'summary' );

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

		$layout   = ( isset( $atts['layout'] ) && in_array( $atts['layout'], array( 'cards', 'compact', 'minimal' ), true ) )
			? $atts['layout']
			: 'cards';
		$group_by = ( isset( $atts['group_by'] ) && in_array( $atts['group_by'], array( 'day', 'month', 'none' ), true ) )
			? $atts['group_by']
			: 'day';

		$calendar     = isset( $atts['calendar'] ) ? (string) $atts['calendar'] : '';
		$show_filters = isset( $atts['filters'] ) && in_array( (string) $atts['filters'], array( '1', 'true', 'yes', 'on' ), true );

		$args   = array(
			'count'    => $count,
			'tag'      => $tag,
			'calendar' => $calendar,
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
		} elseif ( 'week' === $view ) {
			list( $after, $before, $anchor ) = $this->week_bounds( $date );
			$args['after']                   = $after;
			$args['before']                  = $before;
			$args['count']                   = 0;
			$nav                             = array(
				'prev' => $anchor->modify( '-7 days' )->format( 'Y-m-d' ),
				'next' => $anchor->modify( '+7 days' )->format( 'Y-m-d' ),
			);
		}

		/**
		 * Filters the repository query args before events are fetched.
		 *
		 * @param array $args Repository args (count, tag, calendar, after, before).
		 * @param array $atts The display attributes.
		 */
		$args = apply_filters( 'luma_viewer_event_args', $args, $atts );

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

		$render_card = static function ( $event, $teaser = false ) use ( $loader, $formatter, $cta_text, $cta_url, $layout ) {
			return $loader->capture(
				'partials/event-card',
				array(
					'event'         => $event,
					'formatter'     => $formatter,
					'teaser'        => (bool) $teaser,
					'layout'        => $layout,
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
				'layout'      => $layout,
				'group_by'    => $group_by,
				'atts'        => $atts,
			)
		);

		if ( $show_filters && in_array( $view, array( 'list', 'week', 'day', 'photo', 'summary' ), true ) ) {
			$body = $this->filter_bar( $events ) . $body;
		}

		$body .= $this->itemlist_jsonld( $events, $teaser_ids );

		$data = sprintf(
			' data-lv-view="%s" data-lv-tag="%s" data-lv-count="%s" data-lv-date="%s" data-lv-layout="%s" data-lv-group="%s" data-lv-calendar="%s" data-lv-filters="%s"',
			esc_attr( $view ),
			esc_attr( $tag ),
			esc_attr( (string) $count ),
			esc_attr( $date ),
			esc_attr( $layout ),
			esc_attr( $group_by ),
			esc_attr( $calendar ),
			esc_attr( $show_filters ? '1' : '' )
		);

		$html = sprintf(
			'<div class="luma-viewer luma-viewer--%1$s" role="region" aria-label="%2$s" tabindex="-1" aria-busy="false"%3$s>%4$s%5$s</div>',
			esc_attr( $view ),
			esc_attr__( 'Events', 'luma-viewer' ),
			$data,
			$this->toolbar( $view, $nav ),
			$body
		);

		/**
		 * Filters the rendered calendar HTML.
		 *
		 * @param string $html The calendar markup.
		 * @param string $view The resolved view.
		 * @param array  $atts The display attributes.
		 */
		return apply_filters( 'luma_viewer_calendar_html', $html, $view, $atts );
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
			'week'    => __( 'Week', 'luma-viewer' ),
			'month'   => __( 'Month', 'luma-viewer' ),
			'day'     => __( 'Day', 'luma-viewer' ),
			'photo'   => __( 'Photo', 'luma-viewer' ),
			'summary' => __( 'Summary', 'luma-viewer' ),
		);

		$tabs = '';
		foreach ( $labels as $key => $label ) {
			$tabs .= sprintf(
				'<button type="button" role="tab" class="luma-viewer__view-tab%1$s" data-lv-action="view" data-lv-view="%2$s" aria-selected="%3$s">%4$s</button>',
				$key === $view ? ' is-active' : '',
				esc_attr( $key ),
				$key === $view ? 'true' : 'false',
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
			'<div class="luma-viewer__toolbar"><nav class="luma-viewer__views" role="tablist" aria-label="%1$s">%2$s</nav>%3$s</div>',
			esc_attr__( 'Calendar views', 'luma-viewer' ),
			$tabs,
			$nav_html
		);
	}

	/**
	 * Build the optional filter bar (search + category chips). Filtering is done
	 * client-side over the already-rendered cards.
	 *
	 * @param Event[] $events Events in the current result set.
	 * @return string
	 */
	private function filter_bar( array $events ) {
		$names = array();
		foreach ( $events as $event ) {
			foreach ( $event->tags() as $tag ) {
				if ( '' !== $tag['name'] ) {
					$names[ $tag['name'] ] = true;
				}
			}
		}
		$names = array_keys( $names );
		sort( $names );

		$search = sprintf(
			'<input type="search" class="luma-viewer__search" placeholder="%1$s" aria-label="%1$s" />',
			esc_attr__( 'Search events…', 'luma-viewer' )
		);

		$chips = '';
		foreach ( $names as $name ) {
			$chips .= sprintf(
				'<button type="button" class="luma-viewer__chip" data-lv-chip="%1$s">%2$s</button>',
				esc_attr( strtolower( $name ) ),
				esc_html( $name )
			);
		}
		if ( '' !== $chips ) {
			$chips = sprintf( '<div class="luma-viewer__chips">%s</div>', $chips );
		}

		return sprintf( '<div class="luma-viewer__filters">%s%s</div>', $search, $chips );
	}

	/**
	 * Build an ItemList JSON-LD block for the visible (non-teaser) events.
	 *
	 * @param Event[]            $events     Events.
	 * @param array<string,bool> $teaser_ids Teaser event ids to exclude.
	 * @return string
	 */
	private function itemlist_jsonld( array $events, array $teaser_ids ) {
		/**
		 * Filters whether to emit ItemList JSON-LD for calendar listings.
		 *
		 * @param bool $enabled Default true.
		 */
		if ( ! apply_filters( 'luma_viewer_itemlist_schema', true ) ) {
			return '';
		}

		$items    = array();
		$position = 1;
		foreach ( $events as $event ) {
			if ( ! $event->has_start() || ! empty( $teaser_ids[ $event->id() ] ) ) {
				continue;
			}
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'item'     => array_filter(
					array(
						'@type'     => 'Event',
						'name'      => $event->name(),
						'startDate' => wp_date( 'c', $event->start()->getTimestamp(), $this->formatter->display_tz( $event ) ),
						'url'       => $event->luma_url(),
					)
				),
			);
			++$position;
			if ( $position > 50 ) {
				break;
			}
		}

		if ( empty( $items ) ) {
			return '';
		}

		$schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'ItemList',
			'itemListElement' => $items,
		);

		return '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE ) . '</script>';
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
	 * Compute the start/end of the week containing the anchor (honoring the
	 * site's start-of-week), plus the week's first day.
	 *
	 * @param string $date Anchor (Y-m-d), or empty for the current week.
	 * @return array{0:string,1:string,2:\DateTimeImmutable}
	 */
	private function week_bounds( $date ) {
		$base       = $this->parse_anchor( $date );
		$week_start = (int) get_option( 'start_of_week', 0 );
		$offset     = ( (int) $base->format( 'w' ) - $week_start + 7 ) % 7;
		$start      = $base->modify( '-' . $offset . ' days' )->setTime( 0, 0, 0 );
		$end        = $start->modify( '+6 days' )->setTime( 23, 59, 59 );

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
