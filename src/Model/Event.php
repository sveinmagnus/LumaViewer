<?php
/**
 * Normalized event value object.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Model;

defined( 'ABSPATH' ) || exit;

/**
 * Stable, normalized representation of a Luma event.
 *
 * This is the ONLY place that knows Luma's raw JSON field names — every view,
 * block, and widget consumes this object, so if the live API differs from the
 * documented shape, the fix is contained to {@see Event::from_entry()} and
 * {@see Location::from_event()}.
 */
class Event {

	/** @var string */
	private $id = '';

	/** @var string */
	private $name = '';

	/** @var \DateTimeImmutable|null UTC. */
	private $start = null;

	/** @var \DateTimeImmutable|null UTC. */
	private $end = null;

	/** @var string IANA tz name for display. */
	private $timezone = '';

	/** @var string */
	private $cover_url = '';

	/** @var string Public lu.ma URL. */
	private $luma_url = '';

	/** @var string Raw (untrusted) description. */
	private $description = '';

	/** @var Location */
	private $location;

	/** @var array<int,array{id:string,name:string}> */
	private $tags = array();

	/** @var string */
	private $visibility = '';

	/**
	 * Build from a list entry or a single-event response.
	 *
	 * Handles both the list shape (`{ api_id, event:{…}, tags:[…] }`) and a bare
	 * event object.
	 *
	 * @param array $entry Raw entry.
	 * @return self
	 */
	public static function from_entry( array $entry ) {
		$ev   = isset( $entry['event'] ) && is_array( $entry['event'] ) ? $entry['event'] : $entry;
		$tags = $entry['tags'] ?? ( $ev['tags'] ?? array() );

		$self              = new self();
		$self->id          = (string) ( $ev['api_id'] ?? '' );
		$self->name        = (string) ( $ev['name'] ?? '' );
		$self->start       = self::parse_date( $ev['start_at'] ?? null );
		$self->end         = self::parse_date( $ev['end_at'] ?? null );
		$self->timezone    = (string) ( $ev['timezone'] ?? '' );
		$self->cover_url   = (string) ( $ev['cover_url'] ?? '' );
		$self->description = (string) ( $ev['description'] ?? ( $ev['description_md'] ?? '' ) );
		$self->visibility  = (string) ( $ev['visibility'] ?? '' );
		$self->location    = Location::from_event( $ev );
		$self->tags        = self::normalize_tags( $tags );
		$self->luma_url    = self::build_url( (string) ( $ev['url'] ?? '' ) );

		return $self;
	}

	/**
	 * Parse an ISO-8601 datetime to UTC.
	 *
	 * @param mixed $value Raw value.
	 * @return \DateTimeImmutable|null
	 */
	private static function parse_date( $value ) {
		if ( ! is_string( $value ) || '' === $value ) {
			return null;
		}
		try {
			return ( new \DateTimeImmutable( $value ) )->setTimezone( new \DateTimeZone( 'UTC' ) );
		} catch ( \Exception $e ) {
			return null;
		}
	}

	/**
	 * Turn a Luma slug (or full URL) into a public lu.ma URL.
	 *
	 * @param string $slug Slug or URL.
	 * @return string
	 */
	private static function build_url( $slug ) {
		if ( '' === $slug ) {
			return '';
		}
		if ( preg_match( '#^https?://#i', $slug ) ) {
			return $slug;
		}
		return 'https://lu.ma/' . ltrim( $slug, '/' );
	}

	/**
	 * Normalize the tags array.
	 *
	 * @param mixed $tags Raw tags.
	 * @return array<int,array{id:string,name:string}>
	 */
	private static function normalize_tags( $tags ) {
		$out = array();
		if ( is_array( $tags ) ) {
			foreach ( $tags as $tag ) {
				if ( is_array( $tag ) ) {
					$out[] = array(
						'id'   => (string) ( $tag['api_id'] ?? '' ),
						'name' => (string) ( $tag['name'] ?? '' ),
					);
				}
			}
		}
		return $out;
	}

	/** @return string */
	public function id() {
		return $this->id;
	}

	/** @return string */
	public function name() {
		return $this->name;
	}

	/** @return \DateTimeImmutable|null */
	public function start() {
		return $this->start;
	}

	/** @return \DateTimeImmutable|null */
	public function end() {
		return $this->end;
	}

	/** @return bool */
	public function has_start() {
		return $this->start instanceof \DateTimeImmutable;
	}

	/** @return string */
	public function timezone() {
		return $this->timezone;
	}

	/** @return string */
	public function cover_url() {
		return $this->cover_url;
	}

	/** @return string */
	public function luma_url() {
		return $this->luma_url;
	}

	/** @return string */
	public function description() {
		return $this->description;
	}

	/** @return Location */
	public function location() {
		return $this->location;
	}

	/** @return array<int,array{id:string,name:string}> */
	public function tags() {
		return $this->tags;
	}

	/**
	 * Tag ids only (used by MemberPress category mapping in P4).
	 *
	 * @return array<int,string>
	 */
	public function tag_ids() {
		$ids = array();
		foreach ( $this->tags as $tag ) {
			if ( '' !== $tag['id'] ) {
				$ids[] = $tag['id'];
			}
		}
		return $ids;
	}

	/** @return string */
	public function visibility() {
		return $this->visibility;
	}
}
