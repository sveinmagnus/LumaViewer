<?php
/**
 * Synthetic single-event pages (no CPT): pretty URL + SEO metadata.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Frontend;

use LumaViewer\Events\Repository;
use LumaViewer\Membership\Gate;
use LumaViewer\Settings;
use LumaViewer\View\Formatter;
use LumaViewer\View\Renderer;

defined( 'ABSPATH' ) || exit;

/**
 * Registers a rewrite route (`/<base>/<event-id>/`) that renders a single event
 * within the active theme, emitting a proper document title and JSON-LD `Event`
 * schema so these otherwise-virtual pages are indexable.
 */
class SingleRoute {

	const QUERY_VAR  = 'luma_event';
	const FLUSH_FLAG = 'luma_viewer_flush_needed';

	/** @var Repository */
	private $repo;

	/** @var Renderer */
	private $renderer;

	/** @var Formatter */
	private $formatter;

	/** @var Gate */
	private $gate;

	/**
	 * Constructor.
	 *
	 * @param Repository $repo      Event repository.
	 * @param Renderer   $renderer  Shared renderer.
	 * @param Formatter  $formatter Date formatter.
	 * @param Gate       $gate      Membership visibility gate.
	 */
	public function __construct( Repository $repo, Renderer $renderer, Formatter $formatter, Gate $gate ) {
		$this->repo      = $repo;
		$this->renderer  = $renderer;
		$this->formatter = $formatter;
		$this->gate      = $gate;
	}

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'add_rewrite' ) );
		add_filter( 'query_vars', array( $this, 'register_query_var' ) );
		add_action( 'template_redirect', array( $this, 'maybe_render' ) );
	}

	/**
	 * The URL base segment for single events.
	 *
	 * @return string
	 */
	private function base() {
		$base = sanitize_title( (string) Settings::get( 'single_base' ) );
		return '' !== $base ? $base : 'events';
	}

	/**
	 * Register the rewrite rule (and flush once after activation).
	 *
	 * @return void
	 */
	public function add_rewrite() {
		$base = $this->base();
		add_rewrite_rule( '^' . $base . '/([^/]+)/?$', 'index.php?' . self::QUERY_VAR . '=$matches[1]', 'top' );

		if ( get_option( self::FLUSH_FLAG ) ) {
			flush_rewrite_rules();
			delete_option( self::FLUSH_FLAG );
		}
	}

	/**
	 * Allow our query var.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public function register_query_var( $vars ) {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/**
	 * If the request is for a single event, render it as a full themed page.
	 *
	 * @return void
	 */
	public function maybe_render() {
		$id = get_query_var( self::QUERY_VAR );
		if ( empty( $id ) ) {
			return;
		}

		$id = sanitize_text_field( (string) $id );

		// Reject obviously malformed ids before spending an API call on them.
		if ( ! preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $id ) ) {
			$this->set_404();
			return;
		}

		$result = $this->repo->get_event( $id );
		$event  = $result['event'];

		if ( ! $event || '' === $event->id() ) {
			$this->set_404();
			return;
		}

		$decision = $this->gate->resolve( $event, get_current_user_id() );

		// "Hide" behavior: the event must not exist for this viewer. 404 so its URL
		// can't be used to confirm the event (or leak it to crawlers).
		if ( Gate::HIDDEN === $decision ) {
			$this->set_404();
			return;
		}

		// Emit the document title + JSON-LD (dropping the description for teasers).
		add_filter(
			'document_title_parts',
			static function ( $parts ) use ( $event ) {
				$parts['title'] = $event->name();
				return $parts;
			}
		);

		$hide_description = ( Gate::TEASER === $decision );
		add_action(
			'wp_head',
			function () use ( $event, $hide_description ) {
				$this->print_meta_tags( $event, $hide_description );
				$this->print_json_ld( $event, $hide_description );
			}
		);

		$html = $this->renderer->event( array( 'id' => $id ) );

		get_header();
		// Renderer output is escaped within its own templates.
		echo '<main class="luma-viewer-single-page">' . $html . '</main>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		get_footer();
		exit;
	}

	/**
	 * Send a 404 for the current request.
	 *
	 * @return void
	 */
	private function set_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();
	}

	/**
	 * Print Open Graph / Twitter Card meta for the event.
	 *
	 * @param \LumaViewer\Model\Event $event            Event.
	 * @param bool                    $hide_description Omit the description (teaser).
	 * @return void
	 */
	private function print_meta_tags( $event, $hide_description ) {
		echo '<meta property="og:type" content="website" />' . "\n";
		printf( '<meta property="og:title" content="%s" />' . "\n", esc_attr( $event->name() ) );

		if ( '' !== $event->luma_url() ) {
			printf( '<meta property="og:url" content="%s" />' . "\n", esc_url( $event->luma_url() ) );
		}
		if ( '' !== $event->cover_url() ) {
			printf( '<meta property="og:image" content="%s" />' . "\n", esc_url( $event->cover_url() ) );
		}
		if ( ! $hide_description ) {
			$desc = wp_strip_all_tags( $event->description() );
			if ( '' !== $desc ) {
				printf( '<meta property="og:description" content="%s" />' . "\n", esc_attr( wp_html_excerpt( $desc, 200, '…' ) ) );
			}
		}

		printf( '<meta name="twitter:card" content="%s" />' . "\n", esc_attr( '' !== $event->cover_url() ? 'summary_large_image' : 'summary' ) );
	}

	/**
	 * Print JSON-LD Event schema for the event.
	 *
	 * @param \LumaViewer\Model\Event $event            Event.
	 * @param bool                    $hide_description Omit the description (teaser).
	 * @return void
	 */
	private function print_json_ld( $event, $hide_description = false ) {
		$location = $event->location();

		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => 'Event',
			'name'     => $event->name(),
			'url'      => $event->luma_url(),
		);

		if ( $event->has_start() ) {
			$tz                  = $this->formatter->display_tz( $event );
			$schema['startDate'] = wp_date( 'c', $event->start()->getTimestamp(), $tz );
			if ( $event->end() ) {
				$schema['endDate'] = wp_date( 'c', $event->end()->getTimestamp(), $tz );
			}
		}

		if ( '' !== $event->cover_url() ) {
			$schema['image'] = $event->cover_url();
		}

		if ( $location->is_online() ) {
			$schema['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
			$schema['location']            = array(
				'@type' => 'VirtualLocation',
				'url'   => $event->luma_url(),
			);
		} else {
			$schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
			$schema['location']            = array_filter(
				array(
					'@type'   => 'Place',
					'name'    => $location->name(),
					'address' => $location->address(),
				)
			);
		}

		$description = wp_strip_all_tags( $event->description() );
		if ( ! $hide_description && '' !== $description ) {
			$schema['description'] = $description;
		}

		// Encoded with JSON_HEX_TAG|JSON_HEX_AMP so it is safe inside <script>.
		$json = wp_json_encode( $schema, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE );
		echo '<script type="application/ld+json">' . $json . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
