<?php
/**
 * REST endpoint backing AJAX view-switching / navigation.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Frontend;

use LumaViewer\View\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Exposes `GET lumaviewer/v1/events`, which returns the rendered calendar HTML
 * for the requested view/params so the front-end script can swap it in without
 * a full page reload. Reads only from the cache and renders the same public
 * calendar already shown on the page, so the route is public — but it runs as
 * the current user, so per-user (MemberPress) gating still applies in P4.
 */
class RestController {

	const NS = 'lumaviewer/v1';

	/** @var Renderer */
	private $renderer;

	/** @var RateLimiter */
	private $limiter;

	/**
	 * Constructor.
	 *
	 * @param Renderer    $renderer Shared renderer.
	 * @param RateLimiter $limiter  Rate limiter.
	 */
	public function __construct( Renderer $renderer, RateLimiter $limiter ) {
		$this->renderer = $renderer;
		$this->limiter  = $limiter;
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
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			self::NS,
			'/events',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_events' ),
				'permission_callback' => array( $this, 'can_read' ),
				'args'                => array(
					'view'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'tag'   => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'date'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'count' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
				),
			)
		);
	}

	/**
	 * Public, read-only access — but anonymous traffic is rate-limited per IP to
	 * blunt scripted abuse / amplification. Logged-in users are exempt.
	 *
	 * @return bool|\WP_Error
	 */
	public function can_read() {
		if ( is_user_logged_in() ) {
			return true;
		}

		$limit  = (int) apply_filters( 'luma_viewer_rest_rate_limit', 60 );
		$window = (int) apply_filters( 'luma_viewer_rest_rate_window', MINUTE_IN_SECONDS );

		if ( $this->limiter->allow( 'rest_' . RateLimiter::client_ip(), $limit, $window ) ) {
			return true;
		}

		return new \WP_Error(
			'luma_viewer_rate_limited',
			__( 'Too many requests. Please slow down and try again shortly.', 'luma-viewer' ),
			array( 'status' => 429 )
		);
	}

	/**
	 * Return rendered calendar HTML.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_events( \WP_REST_Request $request ) {
		$atts = array(
			'view' => (string) $request->get_param( 'view' ),
			'tag'  => (string) $request->get_param( 'tag' ),
			'date' => (string) $request->get_param( 'date' ),
		);

		$count = $request->get_param( 'count' );
		if ( null !== $count && '' !== $count ) {
			$atts['count'] = (int) $count;
		}

		return new \WP_REST_Response( array( 'html' => $this->renderer->calendar( $atts ) ), 200 );
	}
}
