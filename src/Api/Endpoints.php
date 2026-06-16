<?php
/**
 * Typed Luma API endpoint methods.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Friendly, named methods over {@see Client}. Knows the specific Luma paths and
 * the cursor-pagination convention so callers work in terms of "events" and
 * "tags" rather than raw HTTP.
 */
class Endpoints {

	/**
	 * HTTP client.
	 *
	 * @var Client
	 */
	private $client;

	/**
	 * Constructor.
	 *
	 * @param Client $client HTTP client.
	 */
	public function __construct( Client $client ) {
		$this->client = $client;
	}

	/**
	 * Underlying client (e.g. to check for a key).
	 *
	 * @return Client
	 */
	public function client() {
		return $this->client;
	}

	/**
	 * Get the calendar this key belongs to. Doubles as a connection test.
	 *
	 * @return array|\WP_Error
	 */
	public function get_calendar() {
		return $this->client->get( '/v1/calendars/get' );
	}

	/**
	 * List one page of calendar events.
	 *
	 * @param array $args Query args (after, before, sort_column, sort_direction,
	 *                    pagination_limit, pagination_cursor).
	 * @return array|\WP_Error Raw response with `entries`, `has_more`, `next_cursor`.
	 */
	public function list_events( array $args = array() ) {
		$args = wp_parse_args( $args, array( 'pagination_limit' => 50 ) );
		return $this->client->get( '/v1/calendars/events/list', $args );
	}

	/**
	 * List every event across all pages (bounded by $max_pages).
	 *
	 * @param array $args      Query args (without pagination_cursor).
	 * @param int   $max_pages Safety cap on page count.
	 * @return array|\WP_Error Array of raw entries, or error if the first page fails.
	 */
	public function list_all_events( array $args = array(), $max_pages = 20 ) {
		$entries = array();
		$cursor  = null;
		$page    = 0;

		do {
			$query = $args;
			if ( $cursor ) {
				$query['pagination_cursor'] = $cursor;
			}

			$resp = $this->list_events( $query );
			if ( is_wp_error( $resp ) ) {
				if ( empty( $entries ) ) {
					return $resp;
				}
				break;
			}

			if ( ! empty( $resp['entries'] ) && is_array( $resp['entries'] ) ) {
				$entries = array_merge( $entries, $resp['entries'] );
			}

			$cursor = ! empty( $resp['has_more'] ) && isset( $resp['next_cursor'] ) ? $resp['next_cursor'] : null;
			++$page;
		} while ( $cursor && $page < $max_pages );

		return $entries;
	}

	/**
	 * Get a single event by its Luma api_id.
	 *
	 * @param string $event_api_id Event id (evt-...).
	 * @return array|\WP_Error
	 */
	public function get_event( $event_api_id ) {
		return $this->client->get( '/v1/events/get', array( 'event_api_id' => $event_api_id ) );
	}

	/**
	 * List the calendar's event tags (used as "categories" for gating).
	 *
	 * @return array|\WP_Error
	 */
	public function list_event_tags() {
		return $this->client->get( '/v1/calendars/event-tags/list' );
	}

	/**
	 * List ticket types for an event (for price/free badge).
	 *
	 * @param string $event_api_id Event id.
	 * @return array|\WP_Error
	 */
	public function list_ticket_types( $event_api_id ) {
		return $this->client->get( '/v1/events/ticket-types/list', array( 'event_api_id' => $event_api_id ) );
	}
}
