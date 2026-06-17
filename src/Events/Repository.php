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

	const NOT_FOUND = '__luma_not_found__';

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
	 * @return array{events:Event[],error:\WP_Error|null}
	 */
	public function get_events( array $args = array() ) {
		$args = array_merge(
			array(
				'count'    => 0,
				'tag'      => '',
				'after'    => '',
				'before'   => '',
				'calendar' => '',
			),
			$args
		);

		$is_org   = ( 'organization' === (string) Settings::get( 'api_mode' ) );
		$calendar = (string) $args['calendar'];
		if ( '' === $calendar && $is_org ) {
			$calendar = (string) Settings::get( 'default_calendar' );
		}

		// Round "now" to the hour so the cache key is stable within the TTL.
		$query = array(
			'after' => '' !== $args['after'] ? $args['after'] : gmdate( 'Y-m-d\TH:00:00\Z' ),
		);
		if ( '' !== $args['before'] ) {
			$query['before'] = $args['before'];
		}

		// The full feed is cached once; calendar selection is applied per request
		// below, so the cache stays calendar-agnostic.
		$cache_key = $this->cache->key( array( $is_org ? 'org_events' : 'events', $query ) );
		$entries   = $this->cache->get( $cache_key );

		if ( null === $entries ) {
			$fetched = $is_org ? $this->endpoints->list_all_org_events( $query ) : $this->endpoints->list_all_events( $query );
			if ( is_wp_error( $fetched ) ) {
				return array(
					'events' => array(),
					'error'  => $fetched,
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

		if ( '' !== $args['tag'] ) {
			$events = $this->filter_by_tag( $events, (string) $args['tag'] );
		}

		usort(
			$events,
			static function ( Event $a, Event $b ) {
				$sa = $a->start() ? $a->start()->getTimestamp() : PHP_INT_MAX;
				$sb = $b->start() ? $b->start()->getTimestamp() : PHP_INT_MAX;
				return $sa <=> $sb;
			}
		);

		if ( $args['count'] > 0 ) {
			$events = array_slice( $events, 0, (int) $args['count'] );
		}

		return array(
			'events' => $events,
			'error'  => null,
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
		if ( '' === $id ) {
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
		return array_values(
			array_filter(
				$events,
				static function ( Event $event ) use ( $tag ) {
					foreach ( $event->tags() as $event_tag ) {
						if ( $event_tag['id'] === $tag || 0 === strcasecmp( $event_tag['name'], $tag ) ) {
							return true;
						}
					}
					return false;
				}
			)
		);
	}
}
