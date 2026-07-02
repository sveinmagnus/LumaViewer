<?php
/**
 * Event repository: cache-backed access to Luma events.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Events;

use LumaViewer\Api\Endpoints;
use LumaViewer\Cache\Cache;
use LumaViewer\Model\Calendar;
use LumaViewer\Model\Event;
use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches events from the Luma API (through the cache) and returns normalized
 * {@see Event} objects. The cache stores the raw, membership-agnostic API
 * payload; normalization and any MemberPress filtering happen per request.
 */
class Repository {

	const NOT_FOUND   = '__luma_not_found__';
	const FETCH_ERROR = '__luma_fetch_error__';

	/** @var Endpoints */
	private $endpoints;

	/** @var Cache */
	private $cache;

	/**
	 * Constructor.
	 *
	 * @param Endpoints $endpoints API endpoints.
	 * @param Cache     $cache     Response cache.
	 */
	public function __construct( Endpoints $endpoints, Cache $cache ) {
		$this->endpoints = $endpoints;
		$this->cache     = $cache;
	}

	/**
	 * Get events for display.
	 *
	 * @param array $args {
	 *     Optional.
	 *     @type int    $count  Max events to return (0 = no limit).
	 *     @type string $tag    Filter by tag api_id or name (client-side).
	 *     @type string $after  ISO-8601 lower bound (defaults to start of the current hour).
	 *     @type string $before ISO-8601 upper bound.
	 * }
	 * @return array{events:Event[],error:\WP_Error|null,total:int}
	 */
	public function get_events( array $args = array() ) {
		$args = array_merge(
			array(
				'count'    => 0,
				'tag'      => '',
				'tags'     => array(),
				'after'    => '',
				'before'   => '',
				'calendar' => '',
				'offset'   => 0,
				'past'     => '',
				'from'     => '',
				'to'       => '',
				'order'    => '',
				'online'   => '',
				'free'     => '',
			),
			$args
		);

		$is_org   = ( 'organization' === (string) Settings::get( 'api_mode' ) );
		$calendar = (string) $args['calendar'];
		if ( '' === $calendar && $is_org ) {
			$calendar = (string) Settings::get( 'default_calendar' );
		}

		// Resolve the time window. Date-bounded views (month/day/week) pass an
		// explicit after+before; list-style views default to "upcoming", or to a
		// past window / explicit from–to range when requested. "Now" is rounded to
		// the hour so the cache key stays stable within the TTL.
		$after  = (string) $args['after'];
		$before = (string) $args['before'];
		if ( '' === $after && '' === $before ) {
			$past = in_array( (string) $args['past'], array( '1', 'true', 'yes', 'on' ), true );
			// from/to are attacker-reachable via REST, so they are validated and
			// clamped to a sane window (like the month/day anchors) — otherwise each
			// unique value is a cache miss that fans out to the Luma API.
			$from = $this->clamp_bound( (string) $args['from'], false );
			$to   = $this->clamp_bound( (string) $args['to'], true );
			if ( '' !== $from ) {
				$after = $from;
			} elseif ( $past ) {
				$after = gmdate( 'Y-m-d\T00:00:00\Z', strtotime( '-1 year' ) );
			} else {
				$after = gmdate( 'Y-m-d\TH:00:00\Z' );
			}
			if ( '' !== $to ) {
				$before = $to;
			}
		}

		$query = array( 'after' => $after );
		if ( '' !== $before ) {
			$query['before'] = $before;
		}

		// The full feed is cached once; calendar selection is applied per request
		// below, so the cache stays calendar-agnostic.
		$cache_key = $this->cache->key( array( $is_org ? 'org_events' : 'events', $query ) );
		$entries   = $this->cache->get( $cache_key );

		if ( self::FETCH_ERROR === $entries ) {
			// A recent fetch for this exact window failed; don't hammer the API
			// again until the short negative-cache window expires.
			return array(
				'events' => array(),
				'error'  => new \WP_Error( 'luma_viewer_unavailable', __( 'Events are temporarily unavailable.', 'luma-viewer' ) ),
				'total'  => 0,
			);
		}

		if ( null === $entries ) {
			$fetched = $is_org ? $this->endpoints->list_all_org_events( $query ) : $this->endpoints->list_all_events( $query );
			if ( is_wp_error( $fetched ) ) {
				// Briefly negative-cache the failure so a burst of unique/abusive
				// queries can't amplify into repeated upstream calls.
				$this->cache->set( $cache_key, self::FETCH_ERROR, MINUTE_IN_SECONDS );
				return array(
					'events' => array(),
					'error'  => $fetched,
					'total'  => 0,
				);
			}
			$entries = $fetched;
			$this->cache->set( $cache_key, $entries );
		}

		$events = array();
		foreach ( (array) $entries as $entry ) {
			if ( is_array( $entry ) ) {
				$events[] = Event::from_entry( $entry );
			}
		}

		if ( '' !== $calendar ) {
			$events = array_values(
				array_filter(
					$events,
					static function ( Event $event ) use ( $calendar ) {
						return $event->calendar_id() === $calendar;
					}
				)
			);
		}

		// Never surface events Luma marks non-public (private / unlisted). An empty
		// visibility is treated as public for backward compatibility.
		if ( apply_filters( 'luma_viewer_enforce_visibility', true ) ) {
			$events = array_values(
				array_filter(
					$events,
					static function ( Event $event ) {
						$visibility = strtolower( $event->visibility() );
						return '' === $visibility || 'public' === $visibility;
					}
				)
			);
		}

		// Site-wide allow / deny policy (Settings → Display), applied to every
		// surface so a viewer can never show events that aren't meant to be public.
		$allow = $this->parse_tag_list( (string) Settings::get( 'tag_allow' ) );
		if ( ! empty( $allow ) ) {
			$events = $this->keep_tagged( $events, $allow, true );
		}
		$deny = $this->parse_tag_list( (string) Settings::get( 'tag_deny' ) );
		if ( ! empty( $deny ) ) {
			$events = $this->keep_tagged( $events, $deny, false );
		}

		if ( ! (bool) Settings::get( 'show_cancelled', true ) ) {
			$events = array_values(
				array_filter(
					$events,
					static function ( Event $event ) {
						return ! $event->is_cancelled();
					}
				)
			);
		}

		if ( '' !== $args['tag'] ) {
			$events = $this->filter_by_tag( $events, (string) $args['tag'] );
		}

		$tags = array_filter( array_map( 'strval', (array) $args['tags'] ) );
		if ( ! empty( $tags ) ) {
			$events = $this->keep_tagged( $events, $tags, true );
		}

		if ( 'online' === $args['online'] || 'in_person' === $args['online'] ) {
			$want_online = ( 'online' === $args['online'] );
			$events      = array_values(
				array_filter(
					$events,
					static function ( Event $event ) use ( $want_online ) {
						return $event->location()->is_online() === $want_online;
					}
				)
			);
		}

		if ( 'free' === $args['free'] || 'paid' === $args['free'] ) {
			$want_free = ( 'free' === $args['free'] );
			$events    = array_values(
				array_filter(
					$events,
					static function ( Event $event ) use ( $want_free ) {
						return $event->is_free() === $want_free;
					}
				)
			);
		}

		// Per-request visibility filter (e.g. MemberPress gating) is applied BEFORE
		// pagination so counts, page links and "load more" reflect only the events
		// the current user can actually see.
		if ( isset( $args['filter'] ) && is_callable( $args['filter'] ) ) {
			$events = array_values( array_filter( $events, $args['filter'] ) );
		}

		usort(
			$events,
			static function ( Event $a, Event $b ) {
				$sa = $a->start() ? $a->start()->getTimestamp() : PHP_INT_MAX;
				$sb = $b->start() ? $b->start()->getTimestamp() : PHP_INT_MAX;
				return $sa <=> $sb;
			}
		);

		if ( 'desc' === $args['order'] ) {
			$events = array_reverse( $events );
		}

		$total  = count( $events );
		$offset = max( 0, (int) $args['offset'] );
		if ( $args['count'] > 0 ) {
			$events = array_slice( $events, $offset, (int) $args['count'] );
		} elseif ( $offset > 0 ) {
			$events = array_slice( $events, $offset );
		}

		return array(
			'events' => $events,
			'error'  => null,
			'total'  => $total,
		);
	}

	/**
	 * Get a single event by id.
	 *
	 * @param string $id Event api_id.
	 * @return array{event:Event|null,error:\WP_Error|null}
	 */
	public function get_event( $id ) {
		$id = (string) $id;
		// Reject obviously malformed ids before spending an API call — this is the
		// shared choke point for the single route and the quick-view REST endpoint.
		if ( '' === $id || ! preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $id ) ) {
			return array(
				'event' => null,
				'error' => null,
			);
		}

		$cache_key = $this->cache->key( array( 'event', $id ) );
		$data      = $this->cache->get( $cache_key );

		if ( self::NOT_FOUND === $data ) {
			return array(
				'event' => null,
				'error' => null,
			);
		}

		if ( null === $data ) {
			$resp = $this->endpoints->get_event( $id );
			if ( is_wp_error( $resp ) ) {
				// Negative-cache a definitive 404 so junk ids can't hammer the API.
				$error_data = $resp->get_error_data();
				if ( is_array( $error_data ) && isset( $error_data['status'] ) && 404 === (int) $error_data['status'] ) {
					$this->cache->set( $cache_key, self::NOT_FOUND, 5 * MINUTE_IN_SECONDS );
				}
				return array(
					'event' => null,
					'error' => $resp,
				);
			}
			$data = $resp;
			$this->cache->set( $cache_key, $data );
		}

		return array(
			'event' => Event::from_entry( is_array( $data ) ? $data : array() ),
			'error' => null,
		);
	}

	/**
	 * The organization's calendars (Organization mode only), cached.
	 *
	 * @return Calendar[]
	 */
	public function get_calendars() {
		if ( 'organization' !== (string) Settings::get( 'api_mode' ) ) {
			return array();
		}

		$cache_key = $this->cache->key( array( 'calendars' ) );
		$data      = $this->cache->get( $cache_key );

		if ( null === $data ) {
			$resp = $this->endpoints->list_calendars();
			if ( is_wp_error( $resp ) ) {
				return array();
			}
			$data = ( isset( $resp['entries'] ) && is_array( $resp['entries'] ) )
				? $resp['entries']
				: $resp;
			$this->cache->set( $cache_key, $data, 10 * MINUTE_IN_SECONDS );
		}

		$calendars = array();
		foreach ( (array) $data as $entry ) {
			if ( is_array( $entry ) ) {
				$calendars[] = Calendar::from_entry( $entry );
			}
		}
		return $calendars;
	}

	/**
	 * Filter events whose tags match a given id or (case-insensitive) name.
	 *
	 * @param Event[] $events Events.
	 * @param string  $tag    Tag api_id or name.
	 * @return Event[]
	 */
	private function filter_by_tag( array $events, $tag ) {
		return $this->keep_tagged( $events, array( $tag ), true );
	}

	/**
	 * Keep (or drop) events that carry any of the given tags. A token matches a
	 * tag by api_id or by case-insensitive name.
	 *
	 * @param Event[]  $events Events.
	 * @param string[] $tokens Tag api_ids or names.
	 * @param bool     $keep   True to keep matching events, false to drop them.
	 * @return Event[]
	 */
	private function keep_tagged( array $events, array $tokens, $keep ) {
		return array_values(
			array_filter(
				$events,
				function ( Event $event ) use ( $tokens, $keep ) {
					return $this->event_has_any_tag( $event, $tokens ) === $keep;
				}
			)
		);
	}

	/**
	 * Whether an event carries any of the given tag tokens.
	 *
	 * @param Event    $event  Event.
	 * @param string[] $tokens Tag api_ids or names.
	 * @return bool
	 */
	private function event_has_any_tag( Event $event, array $tokens ) {
		foreach ( $event->tags() as $event_tag ) {
			foreach ( $tokens as $token ) {
				if ( $event_tag['id'] === $token || 0 === strcasecmp( $event_tag['name'], (string) $token ) ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Validate a `Y-m-d` date bound and clamp it to a ±5-year UTC window so it
	 * can't be used to enumerate unbounded cache entries / API calls. Returns an
	 * ISO-8601 string, or '' when the input is not a plain date.
	 *
	 * @param string $date        Raw date (expected `Y-m-d`).
	 * @param bool   $end_of_day  Whether to anchor at 23:59:59 (upper bound).
	 * @return string
	 */
	private function clamp_bound( $date, $end_of_day ) {
		$date = trim( (string) $date );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return '';
		}
		$utc = new \DateTimeZone( 'UTC' );
		try {
			$dt = new \DateTimeImmutable( $date . ( $end_of_day ? 'T23:59:59' : 'T00:00:00' ), $utc );
		} catch ( \Exception $e ) {
			return '';
		}
		$min = new \DateTimeImmutable( '-5 years', $utc );
		$max = new \DateTimeImmutable( '+5 years', $utc );
		if ( $dt < $min ) {
			$dt = $min;
		} elseif ( $dt > $max ) {
			$dt = $max;
		}
		return $dt->format( 'c' );
	}

	/**
	 * Parse a comma-separated tag list into trimmed, non-empty tokens.
	 *
	 * @param string $csv Comma-separated tag ids or names.
	 * @return string[]
	 */
	private function parse_tag_list( $csv ) {
		$tokens = array();
		foreach ( explode( ',', (string) $csv ) as $token ) {
			$token = trim( $token );
			if ( '' !== $token ) {
				$tokens[] = $token;
			}
		}
		return $tokens;
	}
}
