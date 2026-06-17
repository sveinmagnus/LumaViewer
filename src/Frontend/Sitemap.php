<?php
/**
 * Registers the event sitemap provider.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Frontend;

use LumaViewer\Events\Repository;
use LumaViewer\Membership\Gate;

defined( 'ABSPATH' ) || exit;

/**
 * Wires {@see EventSitemapProvider} into the WordPress core sitemaps, when they
 * are available.
 */
class Sitemap {

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
		$this->repo = $repo;
		$this->gate = $gate;
	}

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_provider' ) );
	}

	/**
	 * Register the provider with core sitemaps.
	 *
	 * @return void
	 */
	public function register_provider() {
		if ( ! function_exists( 'wp_sitemaps_register_provider' ) || ! class_exists( 'WP_Sitemaps_Provider' ) ) {
			return;
		}
		wp_sitemaps_register_provider( 'luma_events', new EventSitemapProvider( $this->repo, $this->gate ) );
	}
}
