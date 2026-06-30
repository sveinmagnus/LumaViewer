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

	const HOOK        = 'luma_viewer_refresh_cache';
	const INTERVAL    = 'luma_viewer_quarter_hour';
	const INTERVAL_30 = 'luma_viewer_half_hour';

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
	 * Add the 15- and 30-minute cron intervals.
	 *
	 * @param array $schedules Existing schedules.
	 * @return array
	 */
	public function add_interval( $schedules ) {
		$schedules[ self::INTERVAL ]    = array(
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 15 minutes (Luma-viewer)', 'luma-viewer' ),
		);
		$schedules[ self::INTERVAL_30 ] = array(
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 30 minutes (Luma-viewer)', 'luma-viewer' ),
		);
		return $schedules;
	}

	/**
	 * The WP-Cron recurrence name for the configured interval.
	 *
	 * @return string
	 */
	private function schedule_name() {
		switch ( (string) Settings::get( 'cron_interval' ) ) {
			case 'hourly':
				return 'hourly';
			case 'thirty_minutes':
				return self::INTERVAL_30;
			default:
				return self::INTERVAL;
		}
	}

	/**
	 * Schedule (or reschedule) the refresh event to match the settings, or clear
	 * it when pre-warming is disabled.
	 *
	 * @return void
	 */
	public function maybe_schedule() {
		if ( (bool) Settings::get( 'disable_prewarm' ) ) {
			$timestamp = wp_next_scheduled( self::HOOK );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::HOOK );
			}
			return;
		}

		$desired = $this->schedule_name();
		$current = wp_get_schedule( self::HOOK );

		if ( false === $current ) {
			wp_schedule_event( time() + MINUTE_IN_SECONDS, $desired, self::HOOK );
		} elseif ( $current !== $desired ) {
			$timestamp = wp_next_scheduled( self::HOOK );
			if ( $timestamp ) {
				wp_unschedule_event( $timestamp, self::HOOK );
			}
			wp_schedule_event( time() + MINUTE_IN_SECONDS, $desired, self::HOOK );
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
