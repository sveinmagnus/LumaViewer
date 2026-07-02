<?php
/**
 * Low-level Luma HTTP client.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Api;

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrapper over the WordPress HTTP API that talks to the Luma public API:
 * adds the `x-luma-api-key` header, builds query strings, decodes JSON, and
 * retries on HTTP 429 with backoff. Never call this on a front-end page view —
 * results must be cached (see the wordpress-plugin skill).
 */
class Client {

	const BASE_URL = 'https://public-api.luma.com';

	/**
	 * API key.
	 *
	 * @var string
	 */
	private $api_key;

	/**
	 * Max retry attempts on 429.
	 *
	 * @var int
	 */
	private $max_retries = 2;

	/**
	 * Constructor.
	 *
	 * @param string $api_key Luma calendar API key.
	 */
	public function __construct( $api_key ) {
		$this->api_key = (string) $api_key;
	}

	/**
	 * Whether a key is configured.
	 *
	 * @return bool
	 */
	public function has_key() {
		return '' !== trim( $this->api_key );
	}

	/**
	 * GET request.
	 *
	 * @param string $path  API path beginning with /v1/...
	 * @param array  $query Query parameters.
	 * @return array|\WP_Error Decoded body or error.
	 */
	public function get( $path, array $query = array() ) {
		return $this->request( 'GET', $path, $query );
	}

	/**
	 * POST request.
	 *
	 * @param string $path API path.
	 * @param array  $body JSON body.
	 * @return array|\WP_Error Decoded body or error.
	 */
	public function post( $path, array $body = array() ) {
		return $this->request( 'POST', $path, array(), $body );
	}

	/**
	 * Perform a request with 429 backoff.
	 *
	 * @param string     $method HTTP method.
	 * @param string     $path   API path.
	 * @param array      $query  Query parameters.
	 * @param array|null $body   Optional JSON body.
	 * @return array|\WP_Error
	 */
	public function request( $method, $path, array $query = array(), $body = null ) {
		if ( ! $this->has_key() ) {
			return new \WP_Error( 'luma_viewer_no_key', __( 'No Luma API key is configured.', 'luma-viewer' ) );
		}

		$url = self::BASE_URL . '/' . ltrim( $path, '/' );

		$query = array_filter(
			$query,
			static function ( $value ) {
				return null !== $value && '' !== $value;
			}
		);
		if ( ! empty( $query ) ) {
			$url = add_query_arg( $query, $url );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 15,
			'headers' => array(
				'x-luma-api-key' => $this->api_key,
				'accept'         => 'application/json',
			),
		);
		if ( null !== $body ) {
			$args['headers']['content-type'] = 'application/json';
			$args['body']                    = wp_json_encode( $body );
		}

		// Only block-and-retry on 429 in background contexts (cron / WP-CLI). On a
		// front-end or REST request, sleeping would tie up a PHP worker and turn a
		// Luma rate-limit into a site-wide slowdown, so we fail fast and let the
		// caller negative-cache the error instead.
		$may_retry = wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI );

		$attempt  = 0;
		$response = null;
		do {
			$response = wp_remote_request( $url, $args );
			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			if ( 429 === $code && $may_retry && $attempt < $this->max_retries ) {
				$retry_after = (int) wp_remote_retrieve_header( $response, 'retry-after' );
				$sleep       = $retry_after > 0 ? $retry_after : (int) pow( 2, $attempt );
				sleep( min( $sleep, 8 ) );
				++$attempt;
				continue;
			}
			break;
		} while ( $attempt <= $this->max_retries );

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$data = json_decode( $raw, true );

		if ( $code < 200 || $code >= 300 ) {
			$message = ( is_array( $data ) && isset( $data['message'] ) )
				? $data['message']
				: wp_remote_retrieve_response_message( $response );
			$this->log( sprintf( '%s %s -> %d %s', $method, $path, $code, (string) $message ) );
			return new \WP_Error(
				'luma_viewer_http_' . $code,
				$message ? $message : __( 'The Luma API request failed.', 'luma-viewer' ),
				array( 'status' => $code )
			);
		}

		if ( null === $data && '' !== trim( (string) $raw ) ) {
			return new \WP_Error( 'luma_viewer_bad_json', __( 'Could not parse the Luma API response.', 'luma-viewer' ) );
		}

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Log when WP_DEBUG is on.
	 *
	 * @param string $message Message.
	 * @return void
	 */
	private function log( $message ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[Luma-viewer] ' . $message );
		}
	}
}
