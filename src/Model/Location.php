<?php
/**
 * Event location value object.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Normalizes Luma's `geo_address_json` (and lat/lng) into a stable shape. A
 * missing geo block means the event is online/virtual.
 *
 * All knowledge of Luma's raw location field names lives here.
 */
class Location {

	/** @var bool */
	private $online = true;

	/** @var string */
	private $name = '';

	/** @var string */
	private $address = '';

	/** @var string */
	private $city = '';

	/** @var float|null */
	private $lat = null;

	/** @var float|null */
	private $lng = null;

	/**
	 * Build from a raw Luma event array.
	 *
	 * @param array $ev Raw event object.
	 * @return self
	 */
	public static function from_event( array $ev ) {
		$self = new self();

		$geo = isset( $ev['geo_address_json'] ) && is_array( $ev['geo_address_json'] )
			? $ev['geo_address_json']
			: null;

		if ( null === $geo ) {
			return $self; // online.
		}

		$self->online  = false;
		$self->name    = (string) ( $geo['description'] ?? ( $geo['name'] ?? '' ) );
		$self->address = (string) ( $geo['full_address'] ?? ( $geo['address'] ?? '' ) );
		$self->city    = (string) ( $geo['city'] ?? '' );
		$self->lat     = isset( $ev['geo_latitude'] ) ? (float) $ev['geo_latitude'] : null;
		$self->lng     = isset( $ev['geo_longitude'] ) ? (float) $ev['geo_longitude'] : null;

		return $self;
	}

	/**
	 * Whether the event has no physical address (online/virtual).
	 *
	 * @return bool
	 */
	public function is_online() {
		return $this->online;
	}

	/**
	 * Venue name.
	 *
	 * @return string
	 */
	public function name() {
		return $this->name;
	}

	/**
	 * Full street address.
	 *
	 * @return string
	 */
	public function address() {
		return $this->address;
	}

	/**
	 * City.
	 *
	 * @return string
	 */
	public function city() {
		return $this->city;
	}

	/**
	 * Latitude, if known.
	 *
	 * @return float|null
	 */
	public function lat() {
		return $this->lat;
	}

	/**
	 * Longitude, if known.
	 *
	 * @return float|null
	 */
	public function lng() {
		return $this->lng;
	}

	/**
	 * Best single-line label for display.
	 *
	 * @return string
	 */
	public function label() {
		foreach ( array( $this->name, $this->address, $this->city ) as $candidate ) {
			if ( '' !== $candidate ) {
				return $candidate;
			}
		}
		return '';
	}
}
