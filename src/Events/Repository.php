<?php
/**
 * Event repository: cache-backed access to Luma events.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Events;

use LumaViewer\Api\Endpoints;
use LumaViewer\Cache\Cache;
use LumaViewer\Model\Event;

defined( 'ABSPATH' ) || exit;

/**
 * Fetches events from the Luma API (through the cache) and returns normalized
 * {@see Event} objects. The cache stores the raw, membership-agnostic API
 * payload; normalization and any MemberPress filtering happen per request.
 */
class Repository {

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
				'count'  => 0,
				'tag'    => '',
				'after'  => '',
				'before' => '',
			),
			$args
		);

		// Round "now" to the hour so the cache key is stable within the TTL.
		$query = array(
			'after' => '' !== $args['after'] ? $args['after'] : gmdate( 'Y-m-d\TH:00:00\Z' ),
		);
		if ( '' !== $args['before'] ) {
			$query['before'] = $args['before'];
		}

		$cache_key = $this->cache->key( array( 'events', $query ) );
		$entries   = $this->cache->get( $cache_key );

		if ( null === $entries ) {
			$fetched = $this->endpoints->list_all_events( $query );
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

		if ( null === $data ) {
			$resp = $this->endpoints->get_event( $id );
			if ( is_wp_error( $resp ) ) {
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
