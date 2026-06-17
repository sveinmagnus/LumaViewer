<?php
/**
 * Core-sitemap provider for single-event pages.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Frontend;

use LumaViewer\Events\Repository;
use LumaViewer\Membership\Gate;
use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Adds the synthetic single-event URLs to the WordPress core sitemap. Only
 * referenced once WP_Sitemaps_Provider is known to exist (WP 5.5+), so it never
 * fatals on older installs. Members-only (hidden) events are excluded.
 */
class EventSitemapProvider extends \WP_Sitemaps_Provider {

	/** @var Repository */
	private $repo;

	/** @var Gate */
	private $gate;

	/**
	 * Constructor.
	 *
	 * @param Repository $repo Event repository.
	 * @param Gate       $gate Visibility gate.
	 */
	public function __construct( Repository $repo, Gate $gate ) {
		$this->name        = 'luma_events';
		$this->object_type = 'luma_event';
		$this->repo        = $repo;
		$this->gate        = $gate;
	}

	/**
	 * URL list for the sitemap.
	 *
	 * @param int    $page_num       Page number.
	 * @param string $object_subtype Optional subtype.
	 * @return array<int,array<string,string>>
	 */
	public function get_url_list( $page_num, $object_subtype = '' ) {
		unset( $page_num, $object_subtype );

		$result = $this->repo->get_events( array( 'count' => 0 ) );
		if ( $result['error'] || empty( $result['events'] ) ) {
			return array();
		}

		$base = sanitize_title( (string) Settings::get( 'single_base' ) );
		if ( '' === $base ) {
			$base = 'events';
		}

		$urls = array();
		foreach ( $result['events'] as $event ) {
			if ( '' === $event->id() || Gate::HIDDEN === $this->gate->resolve( $event, 0 ) ) {
				continue;
			}
			$urls[] = array( 'loc' => home_url( '/' . $base . '/' . $event->id() . '/' ) );
		}
		return $urls;
	}

	/**
	 * Number of sitemap pages (events are bounded, so a single page is enough).
	 *
	 * @param string $object_subtype Optional subtype.
	 * @return int
	 */
	public function get_max_num_pages( $object_subtype = '' ) {
		unset( $object_subtype );
		return 1;
	}
}
