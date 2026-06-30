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

	const VIEWS = array( 'list', 'week', 'month', 'day', 'photo', 'summary', 'map' );

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
			: (string) Settings::get( 'default_layout', 'cards' );
		$group_by = ( isset( $atts['group_by'] ) && in_array( $atts['group_by'], array( 'day', 'month', 'none' ), true ) )
			? $atts['group_by']
			: (string) Settings::get( 'default_group_by', 'day' );

		$display    = $this->display_config( $atts, $layout );
		$tag_colors = $this->tag_colors();

		$calendar     = isset( $atts['calendar'] ) ? (string) $atts['calendar'] : '';
		$show_filters = isset( $atts['filters'] ) && in_array( (string) $atts['filters'], array( '1', 'true', 'yes', 'on' ), true );

		$offset = isset( $atts['offset'] ) ? max( 0, (int) $atts['offset'] ) : 0;
		$past   = isset( $atts['past'] ) ? (string) $atts['past'] : '';
		$from   = isset( $atts['from'] ) ? (string) $atts['from'] : '';
		$to     = isset( $atts['to'] ) ? (string) $atts['to'] : '';

		$online = ( isset( $atts['online'] ) && in_array( $atts['online'], array( 'online', 'in_person' ), true ) ) ? (string) $atts['online'] : '';
		$free   = ( isset( $atts['free'] ) && in_array( $atts['free'], array( 'free', 'paid' ), true ) ) ? (string) $atts['free'] : '';
		$order  = ( isset( $atts['order'] ) && in_array( $atts['order'], array( 'asc', 'desc' ), true ) ) ? (string) $atts['order'] : '';

		$tags = isset( $atts['tags'] ) ? $atts['tags'] : '';
		if ( is_string( $tags ) ) {
			$tags = explode( ',', $tags );
		}
		$tags     = array_values( array_filter( array_map( 'trim', array_map( 'strval', (array) $tags ) ) ) );
		$tags_csv = implode( ',', $tags );

		$past_on        = $this->truthy( $past );
		$resolved_order = '' !== $order ? $order : ( $past_on ? 'desc' : (string) Settings::get( 'default_order', 'asc' ) );

		$args   = array(
			'count'    => $count,
			'tag'      => $tag,
			'tags'     => $tags,
			'calendar' => $calendar,
			'offset'   => $offset,
			'past'     => $past,
			'from'     => $from,
			'to'       => $to,
			'order'    => $resolved_order,
			'online'   => $online,
			'free'     => $free,
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
		} elseif ( 'map' === $view ) {
			$args['count'] = 0;
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

		// Tiny helper; it lazy-loads Leaflet only when a map view is actually shown
		// (including after an AJAX view switch), so non-map pages stay light.
		wp_enqueue_script( 'luma-viewer-map' );

		$loader    = $this->loader;
		$formatter = $this->formatter;
		$cta_text  = (string) Settings::get( 'gate_cta_text' );
		$cta_url   = $this->cta_url();

		$render_card = static function ( $event, $teaser = false ) use ( $loader, $formatter, $cta_text, $cta_url, $layout, $display, $tag_colors ) {
			return $loader->capture(
				'partials/event-card',
				array(
					'event'         => $event,
					'formatter'     => $formatter,
					'teaser'        => (bool) $teaser,
					'layout'        => $layout,
					'display'       => $display,
					'tag_colors'    => $tag_colors,
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
				'display'     => $display,
				'tag_colors'  => $tag_colors,
				'empty'       => $this->empty_message(),
				'atts'        => $atts,
			)
		);

		$list_style = in_array( $view, array( 'list', 'week', 'day', 'photo', 'summary' ), true );

		if ( $show_filters && $list_style ) {
			$body = $this->filter_bar( $events, $past, $from, $to, $online, $free ) . $body;
		}

		$total = (int) $result['total'];
		if ( $list_style && $count > 0 && ( $offset + $count ) < $total ) {
			$body .= sprintf(
				'<div class="luma-viewer__more-wrap"><button type="button" class="luma-viewer__button luma-viewer__more" data-lv-action="more">%s</button></div>',
				esc_html__( 'Load more', 'luma-viewer' )
			);
		}

		$body .= $this->itemlist_jsonld( $events, $teaser_ids );

		$data = sprintf(
			' data-lv-view="%s" data-lv-tag="%s" data-lv-count="%s" data-lv-date="%s" data-lv-layout="%s" data-lv-group="%s" data-lv-calendar="%s" data-lv-filters="%s" data-lv-offset="%s" data-lv-past="%s" data-lv-from="%s" data-lv-to="%s" data-lv-step="%s"',
			esc_attr( $view ),
			esc_attr( $tag ),
			esc_attr( (string) $count ),
			esc_attr( $date ),
			esc_attr( $layout ),
			esc_attr( $group_by ),
			esc_attr( $calendar ),
			esc_attr( $show_filters ? '1' : '' ),
			esc_attr( (string) $offset ),
			esc_attr( $past ),
			esc_attr( $from ),
			esc_attr( $to ),
			esc_attr( (string) (int) Settings::get( 'per_page' ) )
		);

		// Filter + display state, threaded so AJAX re-renders (view switch, nav,
		// load-more) preserve the configured options.
		$data .= sprintf(
			' data-lv-order="%s" data-lv-online="%s" data-lv-free="%s" data-lv-mtags="%s" data-lv-words="%s" data-lv-show-cover="%s" data-lv-show-location="%s" data-lv-show-host="%s" data-lv-show-price="%s" data-lv-show-excerpt="%s" data-lv-show-tags="%s" data-lv-show-relative="%s"',
			esc_attr( $resolved_order ),
			esc_attr( $online ),
			esc_attr( $free ),
			esc_attr( $tags_csv ),
			esc_attr( (string) $display['excerpt_words'] ),
			esc_attr( ! empty( $display['cover'] ) ? '1' : '0' ),
			esc_attr( ! empty( $display['location'] ) ? '1' : '0' ),
			esc_attr( ! empty( $display['host'] ) ? '1' : '0' ),
			esc_attr( ! empty( $display['price'] ) ? '1' : '0' ),
			esc_attr( ! empty( $display['excerpt'] ) ? '1' : '0' ),
			esc_attr( ! empty( $display['tags'] ) ? '1' : '0' ),
			esc_attr( ! empty( $display['relative'] ) ? '1' : '0' )
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
	 * Render a single "featured" event (hero).
	 *
	 * @param array $atts Attributes; supports `id` (optional event id, defaults to the next upcoming event).
	 * @return string
	 */
	public function featured( array $atts ) {
		$lead = $this->lead_event( $atts );

		wp_enqueue_style( 'luma-viewer' );

		$body = $this->loader->capture(
			'featured',
			array(
				'event'     => $lead['event'],
				'error'     => $lead['error'],
				'formatter' => $this->formatter,
			)
		);

		return sprintf( '<div class="luma-viewer luma-viewer--featured">%s</div>', $body );
	}

	/**
	 * Render a countdown to a single event's start.
	 *
	 * @param array $atts Attributes; supports `id` (optional event id, defaults to the next upcoming event).
	 * @return string
	 */
	public function countdown( array $atts ) {
		$lead = $this->lead_event( $atts );

		wp_enqueue_style( 'luma-viewer' );
		wp_enqueue_script( 'luma-viewer' );

		$body = $this->loader->capture(
			'countdown',
			array(
				'event'     => $lead['event'],
				'error'     => $lead['error'],
				'formatter' => $this->formatter,
			)
		);

		return sprintf( '<div class="luma-viewer luma-viewer--countdown">%s</div>', $body );
	}

	/**
	 * Resolve the lead event for the featured/countdown views: an explicit id or
	 * the next upcoming event. Gated (teaser/hidden) events are not featured.
	 *
	 * @param array $atts Attributes.
	 * @return array{event:Event|null,error:\WP_Error|null}
	 */
	private function lead_event( array $atts ) {
		$id = isset( $atts['id'] ) ? (string) $atts['id'] : '';
		if ( '' !== $id ) {
			$result = $this->repo->get_event( $id );
			$event  = $result['event'];
			$error  = $result['error'];
		} else {
			$result = $this->repo->get_events( array( 'count' => 1 ) );
			$error  = $result['error'];
			$event  = empty( $result['events'] ) ? null : $result['events'][0];
		}

		if ( $event && '' !== $event->id() && $this->gate->is_enabled() ) {
			$decision = $this->gate->resolve( $event, get_current_user_id() );
			if ( Gate::VISIBLE !== $decision ) {
				$event = null;
			}
		}

		return array(
			'event' => $event,
			'error' => $error,
		);
	}

	/**
	 * Resolve which card elements are shown, the excerpt length, given the
	 * layout and per-instance attributes. Each element's visibility is the
	 * per-instance attribute when set, otherwise the layout baseline AND the
	 * global default — so the global toggle can hide an element site-wide while
	 * a block attribute can force it on or off for one instance.
	 *
	 * @param array  $atts   Display attributes.
	 * @param string $layout cards | compact | minimal.
	 * @return array<string,bool|int>
	 */
	private function display_config( array $atts, $layout ) {
		$baseline = array(
			'cover'    => ( 'cards' === $layout ),
			'location' => ( 'minimal' !== $layout ),
			'host'     => ( 'cards' === $layout ),
			'price'    => ( 'minimal' !== $layout ),
			'excerpt'  => ( 'cards' === $layout ),
			'tags'     => ( 'cards' === $layout ),
			'relative' => true,
		);

		$config = array();
		foreach ( $baseline as $key => $layout_default ) {
			$att = isset( $atts[ 'show_' . $key ] ) ? (string) $atts[ 'show_' . $key ] : '';
			if ( '' !== $att ) {
				$config[ $key ] = $this->truthy( $att );
			} else {
				$config[ $key ] = $layout_default && (bool) Settings::get( 'show_' . $key, true );
			}
		}

		$words                   = isset( $atts['excerpt_words'] ) ? (int) $atts['excerpt_words'] : 0;
		$config['excerpt_words'] = $words > 0 ? $words : max( 1, (int) Settings::get( 'excerpt_words', 25 ) );

		return $config;
	}

	/**
	 * The configured tag → color map (tag api_id => hex), dropping blanks.
	 *
	 * @return array<string,string>
	 */
	private function tag_colors() {
		$map = array();
		foreach ( (array) Settings::get( 'category_colors' ) as $id => $hex ) {
			$hex = (string) $hex;
			if ( '' !== $hex ) {
				$map[ (string) $id ] = $hex;
			}
		}
		return $map;
	}

	/**
	 * The "no events" message (custom, or the default).
	 *
	 * @return string
	 */
	private function empty_message() {
		$message = (string) Settings::get( 'empty_message' );
		return '' !== $message ? $message : __( 'No upcoming events.', 'luma-viewer' );
	}

	/**
	 * Interpret a truthy string attribute.
	 *
	 * @param string $value Raw value.
	 * @return bool
	 */
	private function truthy( $value ) {
		return in_array( strtolower( (string) $value ), array( '1', 'true', 'yes', 'on' ), true );
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
			'map'     => __( 'Map', 'luma-viewer' ),
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
	 * @param string  $past   Current "include past" value.
	 * @param string  $from   Current from-date value.
	 * @param string  $to     Current to-date value.
	 * @param string  $online Current online filter (''|online|in_person).
	 * @param string  $free   Current price filter (''|free|paid).
	 * @return string
	 */
	private function filter_bar( array $events, $past = '', $from = '', $to = '', $online = '', $free = '' ) {
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

		$past_active = in_array( (string) $past, array( '1', 'true', 'yes', 'on' ), true );
		$controls    = sprintf(
			'<button type="button" class="luma-viewer__chip luma-viewer__past%1$s" data-lv-action="past" aria-pressed="%2$s">%3$s</button>',
			$past_active ? ' is-active' : '',
			$past_active ? 'true' : 'false',
			esc_html__( 'Include past', 'luma-viewer' )
		);

		// Online/in-person and free/paid cycle buttons (each click advances to the
		// next state and re-fetches; the JS owns the cycling).
		$online_labels = array(
			''          => __( 'Any location', 'luma-viewer' ),
			'online'    => __( 'Online only', 'luma-viewer' ),
			'in_person' => __( 'In person only', 'luma-viewer' ),
		);
		$free_labels   = array(
			''     => __( 'Any price', 'luma-viewer' ),
			'free' => __( 'Free only', 'luma-viewer' ),
			'paid' => __( 'Paid only', 'luma-viewer' ),
		);
		$online        = isset( $online_labels[ $online ] ) ? $online : '';
		$free          = isset( $free_labels[ $free ] ) ? $free : '';
		$controls     .= sprintf(
			'<button type="button" class="luma-viewer__chip luma-viewer__online%1$s" data-lv-action="online" data-lv-value="%2$s">%3$s</button>',
			'' !== $online ? ' is-active' : '',
			esc_attr( $online ),
			esc_html( $online_labels[ $online ] )
		);
		$controls     .= sprintf(
			'<button type="button" class="luma-viewer__chip luma-viewer__free%1$s" data-lv-action="free" data-lv-value="%2$s">%3$s</button>',
			'' !== $free ? ' is-active' : '',
			esc_attr( $free ),
			esc_html( $free_labels[ $free ] )
		);
		$controls     .= sprintf(
			'<input type="date" class="luma-viewer__date luma-viewer__date--from" value="%1$s" aria-label="%2$s" />',
			esc_attr( (string) $from ),
			esc_attr__( 'From date', 'luma-viewer' )
		);
		$controls     .= sprintf(
			'<input type="date" class="luma-viewer__date luma-viewer__date--to" value="%1$s" aria-label="%2$s" />',
			esc_attr( (string) $to ),
			esc_attr__( 'To date', 'luma-viewer' )
		);

		return sprintf(
			'<div class="luma-viewer__filters">%s%s<div class="luma-viewer__dates">%s</div></div>',
			$search,
			$chips,
			$controls
		);
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
