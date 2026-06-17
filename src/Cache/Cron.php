<?php
/**
 * Scheduled cache pre-warming.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Cache;

use LumaViewer\Events\Repository;
use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps the event cache warm via WP-Cron so visitors never wait on the Luma API
 * and calls stay well under the rate limit.
 */
class Cron {

	const HOOK     = 'luma_viewer_refresh_cache';
	const INTERVAL = 'luma_viewer_quarter_hour';

	/** @var Repository */
	private $repo;

	/**
	 * Constructor.
	 *
	 * @param Repository $repo Event repository.
	 */
	public function __construct( Repository $repo ) {
		$this->repo = $repo;
	}

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_filter( 'cron_schedules', array( $this, 'add_interval' ) ); // phpcs:ignore WordPress.WP.CronInterval.ChangeDetected
		add_action( self::HOOK, array( $this, 'warm' ) );
		add_action( 'init', array( $this, 'maybe_schedule' ) );
	}

	/**
	 * Add a 15-minute cron interval.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function add_interval( $schedules ) {
		$schedules[ self::INTERVAL ] = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 15 minutes (Luma Viewer)', 'luma-viewer' ),
		);
		return $schedules;
	}

	/**
	 * Schedule the refresh event if it isn't already.
	 *
	 * @return void
	 */
	public function maybe_schedule() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, self::INTERVAL, self::HOOK );
		}
	}

	/**
	 * Pre-fetch the default upcoming list into the cache.
	 *
	 * @return void
	 */
	public function warm() {
		$this->repo->get_events( array( 'count' => (int) Settings::get( 'per_page' ) ) );
		update_option( 'luma_viewer_last_refresh', time(), false );
	}
}
