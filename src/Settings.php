<?php
/**
 * Settings accessor.
 *
 * @package LumaViewer
 */

namespace LumaViewer;

defined( 'ABSPATH' ) || exit;

/**
 * Reads and writes the single `luma_viewer_settings` option, merging stored
 * values over defaults so callers always get a complete, typed array.
 */
class Settings {

	const OPTION = 'luma_viewer_settings';

	/**
	 * Default settings.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'api_key'          => '',
			'api_mode'         => 'calendar',
			'default_calendar' => '',
			'cache_ttl'        => 900,
			'default_view'     => 'list',
			'default_layout'   => 'cards',
			'default_group_by' => 'day',
			'default_order'    => 'asc',
			'per_page'         => 10,
			'pagination'       => 'more',
			'timezone_mode'    => 'event',
			'date_format'      => '',
			'time_format'      => '',
			'accent_color'     => '',
			'link_target'      => '_blank',
			'empty_message'    => '',
			'excerpt_words'    => 25,
			'show_cover'       => true,
			'show_location'    => true,
			'show_host'        => true,
			'show_price'       => true,
			'show_excerpt'     => true,
			'show_tags'        => true,
			'show_relative'    => true,
			'show_cancelled'   => true,
			'tag_allow'        => '',
			'tag_deny'         => '',
			'category_colors'  => array(),
			'gating_behavior'  => 'teaser',
			'gate_cta_text'    => __( 'This event is for members. Join or log in to see the details.', 'luma-viewer' ),
			'gate_cta_url'     => '',
			'single_base'      => 'events',
			'cron_interval'    => 'fifteen_minutes',
			'disable_prewarm'  => false,
			'map_center'       => '',
			'map_zoom'         => 0,
			'map_cluster'      => false,
			'webhook_secret'   => '',
			'category_map'     => array(),
		);
	}

	/**
	 * All settings, defaults merged under stored values.
	 *
	 * @return array
	 */
	public static function all() {
		$stored = get_option( self::OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return wp_parse_args( $stored, self::defaults() );
	}

	/**
	 * Get one setting.
	 *
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Value to return if the key is unknown.
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$all = self::all();
		return array_key_exists( $key, $all ) ? $all[ $key ] : $fallback;
	}

	/**
	 * Merge and persist settings.
	 *
	 * @param array $values Partial settings to merge over current values.
	 * @return void
	 */
	public static function update( array $values ) {
		update_option( self::OPTION, wp_parse_args( $values, self::all() ) );
	}
}
