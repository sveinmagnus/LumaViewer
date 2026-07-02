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

	/** @var string Owning calendar api_id (organization mode). */
	private $calendar_id = '';

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

	/** @var array<int,array{name:string,avatar_url:string}> */
	private $hosts = array();

	/** @var bool|null Null when unknown. */
	private $is_free = null;

	/** @var string Best-effort price label, '' when unknown. */
	private $price_label = '';

	/** @var bool */
	private $is_cancelled = false;

	/** @var bool */
	private $is_sold_out = false;

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
		$self->calendar_id = (string) ( $ev['calendar_api_id'] ?? '' );
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
		$self->hosts       = self::normalize_hosts( $ev['hosts'] ?? array() );

		// Status / pricing flags are best-effort: several candidate field names
		// are checked and everything degrades to "unknown" (no badge) if absent.
		// Verify the exact field names against the live API.
		$self->is_cancelled = (bool) ( $ev['is_canceled'] ?? ( $ev['is_cancelled'] ?? ( 'canceled' === ( $ev['status'] ?? '' ) ) ) );
		$self->is_sold_out  = (bool) ( $ev['is_sold_out'] ?? ( $ev['is_full'] ?? false ) );
		if ( isset( $ev['is_free'] ) ) {
			$self->is_free = (bool) $ev['is_free'];
		}
		$self->price_label = self::derive_price_label( $ev );

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

	/**
	 * Normalize the hosts array.
	 *
	 * @param mixed $hosts Raw hosts.
	 * @return array<int,array{name:string,avatar_url:string}>
	 */
	private static function normalize_hosts( $hosts ) {
		$out = array();
		if ( is_array( $hosts ) ) {
			foreach ( $hosts as $host ) {
				if ( ! is_array( $host ) ) {
					continue;
				}
				$name = (string) ( $host['name'] ?? '' );
				if ( '' === $name ) {
					continue;
				}
				$out[] = array(
					'name'       => $name,
					'avatar_url' => (string) ( $host['avatar_url'] ?? ( $host['avatar'] ?? '' ) ),
				);
			}
		}
		return $out;
	}

	/**
	 * Best-effort price label, e.g. "From $20". Empty when unknown.
	 *
	 * @param array $ev Raw event object.
	 * @return string
	 */
	private static function derive_price_label( array $ev ) {
		$amount = $ev['min_price'] ?? ( $ev['price'] ?? null );
		if ( null === $amount || '' === $amount || ! is_numeric( $amount ) ) {
			return '';
		}
		$amount   = (float) $amount;
		$currency = (string) ( $ev['currency'] ?? '' );
		$money    = ( '' !== $currency ? $currency . ' ' : '' ) . rtrim( rtrim( number_format( $amount, 2 ), '0' ), '.' );

		$label = isset( $ev['min_price'] )
			/* translators: %s: formatted price. */
			? sprintf( __( 'From %s', 'luma-viewer' ), $money )
			: $money;

		/**
		 * Filters the price label. Luma's exact amount units (major vs. minor /
		 * cents) should be confirmed against the live API; this hook lets a site
		 * correct the formatting without patching the plugin.
		 *
		 * @param string $label The derived price label.
		 * @param array  $ev    The raw event object.
		 */
		return (string) apply_filters( 'luma_viewer_price_label', $label, $ev );
	}

	/** @return string */
	public function id() {
		return $this->id;
	}

	/** @return string */
	public function calendar_id() {
		return $this->calendar_id;
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

	/** @return array<int,array{name:string,avatar_url:string}> */
	public function hosts() {
		return $this->hosts;
	}

	/**
	 * Whether any price information is known (free flag or a price label).
	 *
	 * @return bool
	 */
	public function has_price_info() {
		return null !== $this->is_free || '' !== $this->price_label;
	}

	/** @return bool */
	public function is_free() {
		return true === $this->is_free;
	}

	/** @return string */
	public function price_label() {
		return $this->price_label;
	}

	/** @return bool */
	public function is_cancelled() {
		return $this->is_cancelled;
	}

	/** @return bool */
	public function is_sold_out() {
		return $this->is_sold_out;
	}

	/**
	 * A plain-text excerpt of the description.
	 *
	 * @param int $words Word count.
	 * @return string
	 */
	public function excerpt( $words = 24 ) {
		$text = trim( wp_strip_all_tags( $this->description ) );
		if ( '' === $text ) {
			return '';
		}
		return wp_trim_words( $text, max( 1, (int) $words ) );
	}
}
