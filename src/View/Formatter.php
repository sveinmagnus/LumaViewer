<?php
/**
 * Date/time formatting for events.
 *
 * @package LumaViewer
 */

namespace LumaViewer\View;

use LumaViewer\Model\Event;
use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Formats event times according to the site's date/time formats and the
 * configured time-zone mode (the event's own zone, or the site zone).
 */
class Formatter {

	/**
	 * The time zone to display an event in.
	 *
	 * @param Event $event Event.
	 * @return \DateTimeZone
	 */
	public function display_tz( Event $event ) {
		if ( 'site' === Settings::get( 'timezone_mode' ) ) {
			return wp_timezone();
		}
		$tz = $event->timezone();
		if ( '' !== $tz ) {
			try {
				return new \DateTimeZone( $tz );
			} catch ( \Exception $e ) {
				return wp_timezone();
			}
		}
		return wp_timezone();
	}

	/**
	 * A "date" label for grouping/headers (site date format).
	 *
	 * @param Event $event Event.
	 * @return string
	 */
	public function day_label( Event $event ) {
		if ( ! $event->has_start() ) {
			return '';
		}
		return wp_date( $this->date_format(), $event->start()->getTimestamp(), $this->display_tz( $event ) );
	}

	/**
	 * The configured date format, falling back to the site format.
	 *
	 * @return string
	 */
	public function date_format() {
		$format = (string) Settings::get( 'date_format' );
		return '' !== $format ? $format : (string) get_option( 'date_format' );
	}

	/**
	 * The configured time format, falling back to the site format.
	 *
	 * @return string
	 */
	public function time_format() {
		$format = (string) Settings::get( 'time_format' );
		return '' !== $format ? $format : (string) get_option( 'time_format' );
	}

	/**
	 * The `target`/`rel` attributes for outbound Luma links, honoring the
	 * "open links in" setting. Returns a fixed, safe attribute string (leading
	 * space included) or an empty string for same-tab.
	 *
	 * @return string
	 */
	public function link_attrs() {
		return '_self' === Settings::get( 'link_target' )
			? ''
			: ' target="_blank" rel="noopener noreferrer"';
	}

	/**
	 * A Y-m-d key in the display zone (for grouping by day).
	 *
	 * @param Event $event Event.
	 * @return string
	 */
	public function day_key( Event $event ) {
		if ( ! $event->has_start() ) {
			return '';
		}
		return wp_date( 'Y-m-d', $event->start()->getTimestamp(), $this->display_tz( $event ) );
	}

	/**
	 * The start time only (site time format), in the display zone.
	 *
	 * @param Event $event Event.
	 * @return string
	 */
	public function time( Event $event ) {
		if ( ! $event->has_start() ) {
			return '';
		}
		return wp_date( $this->time_format(), $event->start()->getTimestamp(), $this->display_tz( $event ) );
	}

	/**
	 * A relative phrase like "in 3 days" or "2 hours ago".
	 *
	 * @param Event $event Event.
	 * @return string
	 */
	public function relative( Event $event ) {
		if ( ! $event->has_start() ) {
			return '';
		}
		$start = $event->start()->getTimestamp();
		$now   = time();
		if ( $start >= $now ) {
			/* translators: %s: human time difference, e.g. "3 days". */
			return sprintf( __( 'in %s', 'luma-viewer' ), human_time_diff( $now, $start ) );
		}
		/* translators: %s: human time difference, e.g. "2 hours". */
		return sprintf( __( '%s ago', 'luma-viewer' ), human_time_diff( $start, $now ) );
	}

	/**
	 * A human start–end range, collapsing the end date when it's the same day.
	 *
	 * @param Event $event Event.
	 * @return string
	 */
	public function range( Event $event ) {
		if ( ! $event->has_start() ) {
			return '';
		}

		$tz          = $this->display_tz( $event );
		$date_format = $this->date_format();
		$time_format = $this->time_format();
		$start_ts    = $event->start()->getTimestamp();

		$start = wp_date( $date_format . ' ' . $time_format, $start_ts, $tz );

		if ( ! $event->end() ) {
			return $start;
		}

		$end_ts   = $event->end()->getTimestamp();
		$same_day = wp_date( 'Y-m-d', $start_ts, $tz ) === wp_date( 'Y-m-d', $end_ts, $tz );
		$end      = $same_day
			? wp_date( $time_format, $end_ts, $tz )
			: wp_date( $date_format . ' ' . $time_format, $end_ts, $tz );

		return $start . ' – ' . $end;
	}
}
