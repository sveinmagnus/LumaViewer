<?php
/**
 * Normalized calendar value object.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Model;

defined( 'ABSPATH' ) || exit;

/**
 * A minimal calendar reference (used in organization mode to list and select
 * calendars). Isolates Luma's calendar field names.
 */
class Calendar {

	/** @var string */
	private $id = '';

	/** @var string */
	private $name = '';

	/**
	 * Build from a raw calendars-list entry (or a bare calendar object).
	 *
	 * @param array $entry Raw entry.
	 * @return self
	 */
	public static function from_entry( array $entry ) {
		$cal = isset( $entry['calendar'] ) && is_array( $entry['calendar'] ) ? $entry['calendar'] : $entry;

		$self       = new self();
		$self->id   = (string) ( $cal['api_id'] ?? '' );
		$self->name = (string) ( $cal['name'] ?? $self->id );

		return $self;
	}

	/** @return string */
	public function id() {
		return $this->id;
	}

	/** @return string */
	public function name() {
		return $this->name;
	}
}
