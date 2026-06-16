<?php
/**
 * Response cache (transient + object cache).
 *
 * @package LumaViewer
 */

namespace LumaViewer\Cache;

defined( 'ABSPATH' ) || exit;

/**
 * Caches the membership-agnostic Luma payloads so the API is never hit on a
 * normal page view. Keys are a hash of the query args. Uses the Transients API,
 * which transparently uses a persistent object cache when one is present.
 */
class Cache {

	const PREFIX = 'luma_viewer_';

	/**
	 * Default TTL in seconds.
	 *
	 * @var int
	 */
	private $ttl;

	/**
	 * Constructor.
	 *
	 * @param int $ttl Default TTL in seconds.
	 */
	public function __construct( $ttl = 900 ) {
		$this->ttl = max( 60, (int) $ttl );
	}

	/**
	 * Build a stable cache key from arbitrary args.
	 *
	 * @param array $args Query args.
	 * @return string
	 */
	public function key( array $args ) {
		return self::PREFIX . md5( (string) wp_json_encode( $args ) );
	}

	/**
	 * Read a cached value.
	 *
	 * @param string $key Cache key.
	 * @return mixed|null Null on miss.
	 */
	public function get( $key ) {
		$value = get_transient( $key );
		return false === $value ? null : $value;
	}

	/**
	 * Store a value.
	 *
	 * @param string   $key   Cache key.
	 * @param mixed    $value Value.
	 * @param int|null $ttl   Optional TTL override.
	 * @return void
	 */
	public function set( $key, $value, $ttl = null ) {
		set_transient( $key, $value, null === $ttl ? $this->ttl : max( 60, (int) $ttl ) );
	}

	/**
	 * Delete one key.
	 *
	 * @param string $key Cache key.
	 * @return void
	 */
	public function delete( $key ) {
		delete_transient( $key );
	}

	/**
	 * Flush all of the plugin's transients (DB-backed fallback). With a
	 * persistent object cache this is a best-effort no-op for non-DB stores;
	 * prefer targeted {@see Cache::delete()} on webhook invalidation.
	 *
	 * @return void
	 */
	public function flush() {
		global $wpdb;

		$like = $wpdb->esc_like( '_transient_' . self::PREFIX ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );

		$like = $wpdb->esc_like( '_transient_timeout_' . self::PREFIX ) . '%';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $like ) );
	}
}
