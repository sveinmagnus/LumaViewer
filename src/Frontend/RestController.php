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
			'/event',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_event' ),
				'permission_callback' => array( $this, 'can_read' ),
				'args'                => array(
					'id' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		register_rest_route(
			self::NS,
			'/events',
			array(
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_events' ),
				'permission_callback' => array( $this, 'can_read' ),
				'args'                => array(
					'view'          => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'tag'           => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'date'          => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'count'         => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'layout'        => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'group_by'      => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'calendar'      => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'filters'       => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'offset'        => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'past'          => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'from'          => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'to'            => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'pagination'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'quickview'     => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'order'         => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'online'        => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'free'          => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_key',
					),
					'tags'          => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'excerpt_words' => array(
						'type'              => 'integer',
						'sanitize_callback' => 'absint',
					),
					'show_cover'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'show_location' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'show_host'     => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'show_price'    => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'show_excerpt'  => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'show_tags'     => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
					),
					'show_relative' => array(
						'type'              => 'string',
						'sanitize_callback' => 'sanitize_text_field',
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
		// Admins are exempt; everyone else is throttled. Logged-in users get a
		// higher, per-user allowance (a membership site is full of subscriber
		// accounts, so "logged in" alone can't be a free pass).
		if ( current_user_can( 'manage_options' ) ) {
			return true;
		}

		$limit  = (int) apply_filters( 'luma_viewer_rest_rate_limit', 60 );
		$window = (int) apply_filters( 'luma_viewer_rest_rate_window', MINUTE_IN_SECONDS );

		if ( is_user_logged_in() ) {
			$limit  = (int) apply_filters( 'luma_viewer_rest_rate_limit_user', $limit * 2 );
			$bucket = 'rest_user_' . get_current_user_id();
		} else {
			$bucket = 'rest_' . RateLimiter::client_ip();
		}

		if ( $this->limiter->allow( $bucket, $limit, $window ) ) {
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
			'view'          => (string) $request->get_param( 'view' ),
			'tag'           => (string) $request->get_param( 'tag' ),
			'date'          => (string) $request->get_param( 'date' ),
			'layout'        => (string) $request->get_param( 'layout' ),
			'group_by'      => (string) $request->get_param( 'group_by' ),
			'calendar'      => (string) $request->get_param( 'calendar' ),
			'filters'       => (string) $request->get_param( 'filters' ),
			'past'          => (string) $request->get_param( 'past' ),
			'from'          => (string) $request->get_param( 'from' ),
			'to'            => (string) $request->get_param( 'to' ),
			'pagination'    => (string) $request->get_param( 'pagination' ),
			'quickview'     => (string) $request->get_param( 'quickview' ),
			'order'         => (string) $request->get_param( 'order' ),
			'online'        => (string) $request->get_param( 'online' ),
			'free'          => (string) $request->get_param( 'free' ),
			'tags'          => (string) $request->get_param( 'tags' ),
			'show_cover'    => (string) $request->get_param( 'show_cover' ),
			'show_location' => (string) $request->get_param( 'show_location' ),
			'show_host'     => (string) $request->get_param( 'show_host' ),
			'show_price'    => (string) $request->get_param( 'show_price' ),
			'show_excerpt'  => (string) $request->get_param( 'show_excerpt' ),
			'show_tags'     => (string) $request->get_param( 'show_tags' ),
			'show_relative' => (string) $request->get_param( 'show_relative' ),
		);

		$count = $request->get_param( 'count' );
		if ( null !== $count && '' !== $count ) {
			$atts['count'] = min( 100, max( 0, (int) $count ) );
		}

		$offset = $request->get_param( 'offset' );
		if ( null !== $offset && '' !== $offset ) {
			$atts['offset'] = min( 10000, max( 0, (int) $offset ) );
		}

		$words = $request->get_param( 'excerpt_words' );
		if ( null !== $words && '' !== $words ) {
			$atts['excerpt_words'] = (int) $words;
		}

		return new \WP_REST_Response( array( 'html' => $this->renderer->calendar( $atts ) ), 200 );
	}

	/**
	 * Return the rendered summary for a single event (quick-view modal).
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function get_event( \WP_REST_Request $request ) {
		$id = (string) $request->get_param( 'id' );

		return new \WP_REST_Response( array( 'html' => $this->renderer->event( array( 'id' => $id ) ) ), 200 );
	}
}
