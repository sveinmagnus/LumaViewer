<?php
/**
 * Uninstall cleanup: remove options, cached transients, and scheduled events.
 *
 * @package LumaViewer
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'luma_viewer_settings' );
delete_option( 'luma_viewer_flush_needed' );
delete_option( 'luma_viewer_cache_version' );
delete_option( 'luma_viewer_last_refresh' );
// Plugin Update Checker's cached update data.
delete_option( 'external_updates-luma-viewer' );

wp_clear_scheduled_hook( 'luma_viewer_refresh_cache' );

global $wpdb;

// Remove our transients (and their timeouts) from the options table. Object
// caches are cleared on their own TTL; this covers the DB-backed fallback. Both
// the cache prefix and the (separate) rate-limiter prefix are cleaned up.
foreach ( array( '_transient_luma_viewer_', '_transient_timeout_luma_viewer_', '_transient_lv_ratelimit_', '_transient_timeout_lv_ratelimit_' ) as $luma_viewer_prefix ) {
	$luma_viewer_like = $wpdb->esc_like( $luma_viewer_prefix ) . '%';
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $luma_viewer_like ) );
}
