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
		return wp_date( (string) get_option( 'date_format' ), $event->start()->getTimestamp(), $this->display_tz( $event ) );
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
		return wp_date( (string) get_option( 'time_format' ), $event->start()->getTimestamp(), $this->display_tz( $event ) );
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
		$date_format = (string) get_option( 'date_format' );
		$time_format = (string) get_option( 'time_format' );
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
