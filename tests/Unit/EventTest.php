<?php
/**
 * Unit tests for event normalization (no WordPress required).
 *
 * @package LumaViewer\Tests
 */

namespace LumaViewer\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use LumaViewer\Model\Event;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LumaViewer\Model\Event
 * @covers \LumaViewer\Model\Location
 */
final class EventTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_strip_all_tags' )->alias(
			static function ( $text ) {
				return trim( strip_tags( (string) $text ) );
			}
		);
		Functions\when( 'wp_trim_words' )->alias(
			static function ( $text, $num = 55 ) {
				$words = preg_split( '/\s+/', trim( (string) $text ) );
				if ( count( $words ) <= $num ) {
					return implode( ' ', $words );
				}
				return implode( ' ', array_slice( $words, 0, $num ) ) . '…';
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * A representative list-events entry (documented Luma shape).
	 *
	 * @return array
	 */
	private function list_entry(): array {
		return array(
			'api_id' => 'evt-123',
			'event'  => array(
				'api_id'           => 'evt-123',
				'name'             => 'Community Night',
				'start_at'         => '2026-07-01T18:00:00.000Z',
				'end_at'           => '2026-07-01T20:00:00.000Z',
				'timezone'         => 'America/New_York',
				'cover_url'        => 'https://images.lu.ma/cover.png',
				'url'              => 'community-night',
				'description'      => 'Join us!',
				'geo_address_json' => array(
					'description'  => 'The Hall',
					'full_address' => '1 Main St, Springfield',
					'city'         => 'Springfield',
				),
				'geo_latitude'     => '40.7',
				'geo_longitude'    => '-74.0',
				'visibility'       => 'public',
			),
			'tags'   => array(
				array(
					'api_id' => 'evttag-aaa',
					'name'   => 'Members',
				),
			),
		);
	}

	public function test_normalizes_core_fields(): void {
		$event = Event::from_entry( $this->list_entry() );

		$this->assertSame( 'evt-123', $event->id() );
		$this->assertSame( 'Community Night', $event->name() );
		$this->assertSame( 'America/New_York', $event->timezone() );
		$this->assertSame( 'https://images.lu.ma/cover.png', $event->cover_url() );
		$this->assertSame( 'public', $event->visibility() );
	}

	public function test_builds_public_luma_url_from_slug(): void {
		$event = Event::from_entry( $this->list_entry() );
		$this->assertSame( 'https://lu.ma/community-night', $event->luma_url() );
	}

	public function test_passes_through_absolute_url(): void {
		$entry                  = $this->list_entry();
		$entry['event']['url'] = 'https://lu.ma/already-absolute';
		$event                  = Event::from_entry( $entry );
		$this->assertSame( 'https://lu.ma/already-absolute', $event->luma_url() );
	}

	public function test_parses_dates_to_utc(): void {
		$event = Event::from_entry( $this->list_entry() );

		$this->assertTrue( $event->has_start() );
		$this->assertInstanceOf( \DateTimeImmutable::class, $event->start() );
		$this->assertSame( 'UTC', $event->start()->getTimezone()->getName() );
		$this->assertSame( '2026-07-01 18:00:00', $event->start()->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( '2026-07-01 20:00:00', $event->end()->format( 'Y-m-d H:i:s' ) );
	}

	public function test_missing_start_is_handled(): void {
		$entry = $this->list_entry();
		unset( $entry['event']['start_at'] );
		$event = Event::from_entry( $entry );
		$this->assertFalse( $event->has_start() );
		$this->assertNull( $event->start() );
	}

	public function test_normalizes_tags_and_ids(): void {
		$event = Event::from_entry( $this->list_entry() );

		$this->assertSame(
			array( array( 'id' => 'evttag-aaa', 'name' => 'Members' ) ),
			$event->tags()
		);
		$this->assertSame( array( 'evttag-aaa' ), $event->tag_ids() );
	}

	public function test_physical_location(): void {
		$location = Event::from_entry( $this->list_entry() )->location();

		$this->assertFalse( $location->is_online() );
		$this->assertSame( 'The Hall', $location->name() );
		$this->assertSame( '1 Main St, Springfield', $location->address() );
		$this->assertSame( 'The Hall', $location->label() );
	}

	public function test_online_when_no_geo(): void {
		$entry = $this->list_entry();
		unset( $entry['event']['geo_address_json'] );
		$location = Event::from_entry( $entry )->location();

		$this->assertTrue( $location->is_online() );
		$this->assertSame( '', $location->label() );
	}

	public function test_parses_hosts(): void {
		$entry                    = $this->list_entry();
		$entry['event']['hosts']  = array(
			array(
				'name'       => 'Ada',
				'avatar_url' => 'https://img/ada.png',
			),
			array( 'name' => 'Grace' ),
			array( 'avatar_url' => 'https://img/none.png' ), // no name -> skipped
		);
		$hosts = Event::from_entry( $entry )->hosts();

		$this->assertCount( 2, $hosts );
		$this->assertSame( 'Ada', $hosts[0]['name'] );
		$this->assertSame( 'https://img/ada.png', $hosts[0]['avatar_url'] );
		$this->assertSame( 'Grace', $hosts[1]['name'] );
	}

	public function test_free_flag(): void {
		$entry                     = $this->list_entry();
		$entry['event']['is_free'] = true;
		$event                     = Event::from_entry( $entry );

		$this->assertTrue( $event->is_free() );
		$this->assertTrue( $event->has_price_info() );
	}

	public function test_min_price_label(): void {
		$entry                       = $this->list_entry();
		$entry['event']['min_price'] = 20;
		$entry['event']['currency']  = 'USD';
		$event                       = Event::from_entry( $entry );

		$this->assertFalse( $event->is_free() );
		$this->assertTrue( $event->has_price_info() );
		$this->assertSame( 'From USD 20', $event->price_label() );
	}

	public function test_no_price_info_by_default(): void {
		$event = Event::from_entry( $this->list_entry() );
		$this->assertFalse( $event->has_price_info() );
		$this->assertSame( '', $event->price_label() );
	}

	public function test_cancelled_and_sold_out_flags(): void {
		$entry                          = $this->list_entry();
		$entry['event']['is_canceled']  = true;
		$entry['event']['is_sold_out']  = true;
		$event                          = Event::from_entry( $entry );

		$this->assertTrue( $event->is_cancelled() );
		$this->assertTrue( $event->is_sold_out() );
	}

	public function test_excerpt_trims_words(): void {
		$entry                         = $this->list_entry();
		$entry['event']['description'] = 'one two three four five';
		$event                         = Event::from_entry( $entry );

		$this->assertSame( 'one two…', $event->excerpt( 2 ) );
	}

	public function test_accepts_bare_event_object(): void {
		// get_event may return the event without the list wrapper.
		$bare  = $this->list_entry()['event'];
		$event = Event::from_entry( $bare );
		$this->assertSame( 'Community Night', $event->name() );
		$this->assertSame( 'https://lu.ma/community-night', $event->luma_url() );
	}
}
