<?php
/**
 * Luma webhook receiver (cache invalidation).
 *
 * @package LumaViewer
 */

namespace LumaViewer\Cache;

use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Receives Luma webhooks (Event Created/Updated/Canceled, Calendar Event Added)
 * and flushes the cache so changes appear promptly.
 *
 * Authenticated with a shared secret token (generated on activation). Luma's
 * exact request-signing scheme should be verified against the live API and the
 * luma-api skill when integrating; this guards the endpoint in the meantime.
 */
class Webhook {

	const NS = 'lumaviewer/v1';

	/** @var Cache */
	private $cache;

	/**
	 * Constructor.
	 *
	 * @param Cache $cache Response cache.
	 */
	public function __construct( Cache $cache ) {
		$this->cache = $cache;
	}

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the webhook route.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/webhook',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'verify' ),
			)
		);
	}

	/**
	 * Verify the shared secret (constant-time) before doing anything.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return bool
	 */
	public function verify( \WP_REST_Request $request ) {
		$secret = (string) Settings::get( 'webhook_secret' );
		if ( '' === $secret ) {
			return false;
		}

		$provided = (string) $request->get_param( 'token' );
		if ( '' === $provided ) {
			$provided = (string) $request->get_header( 'x-luma-signature' );
		}

		return '' !== $provided && hash_equals( $secret, $provided );
	}

	/**
	 * Flush the cache.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function handle( \WP_REST_Request $request ) {
		unset( $request );
		$this->cache->flush();
		return new \WP_REST_Response( array( 'ok' => true ), 200 );
	}
}
