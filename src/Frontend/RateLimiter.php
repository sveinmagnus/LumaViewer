<?php
/**
 * Fixed-window per-key rate limiter.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * A small transient-backed fixed-window limiter used to throttle the public
 * REST surface. Transients work everywhere (and use the object cache when one
 * is present); the window is tracked in the stored value so it doesn't slide.
 */
class RateLimiter {

	/**
	 * Record a hit and report whether it is within the allowance.
	 *
	 * @param string $bucket Identifier (e.g. "rest_<ip>").
	 * @param int    $limit  Max hits per window (<= 0 disables limiting).
	 * @param int    $window Window length in seconds.
	 * @return bool True if the request is allowed.
	 */
	public function allow( $bucket, $limit, $window ) {
		$limit  = (int) $limit;
		$window = max( 1, (int) $window );
		if ( $limit <= 0 ) {
			return true;
		}

		$key  = 'luma_viewer_rl_' . md5( (string) $bucket );
		$now  = time();
		$data = get_transient( $key );

		if ( ! is_array( $data ) || empty( $data['reset'] ) || $data['reset'] <= $now ) {
			$data = array(
				'count' => 0,
				'reset' => $now + $window,
			);
		}

		++$data['count'];
		set_transient( $key, $data, max( 1, $data['reset'] - $now ) );

		return $data['count'] <= $limit;
	}

	/**
	 * Best-effort client IP (REMOTE_ADDR only; X-Forwarded-* is spoofable).
	 * Sites behind a trusted proxy/CDN can override via the filter.
	 *
	 * @return string
	 */
	public static function client_ip() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		/**
		 * Filters the client IP used for rate limiting.
		 *
		 * @param string $ip The detected REMOTE_ADDR.
		 */
		return (string) apply_filters( 'luma_viewer_client_ip', $ip );
	}
}
