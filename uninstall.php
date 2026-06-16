<?php
/**
 * Uninstall cleanup: remove options, cached transients, and scheduled events.
 *
 * @package LumaViewer
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'luma_viewer_settings' );

wp_clear_scheduled_hook( 'luma_viewer_refresh_cache' );

global $wpdb;

// Remove our transients (and their timeouts) from the options table. Object
// caches are cleared on their own TTL; this covers the DB-backed fallback.
$luma_viewer_like = $wpdb->esc_like( '_transient_luma_viewer_' ) . '%';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $luma_viewer_like ) );

$luma_viewer_like = $wpdb->esc_like( '_transient_timeout_luma_viewer_' ) . '%';
// phpcs:ignore WordPress.DB.DirectDatabaseQuery
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $luma_viewer_like ) );
