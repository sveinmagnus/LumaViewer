<?php
/**
 * Unit tests for the membership visibility gate.
 *
 * @package LumaViewer\Tests
 */

namespace LumaViewer\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use LumaViewer\Membership\Gate;
use LumaViewer\Membership\MemberPress;
use LumaViewer\Model\Event;
use LumaViewer\Settings;
use PHPUnit\Framework\TestCase;

/**
 * A MemberPress test double so the gate logic can be tested without MemberPress.
 */
class FakeMemberPress extends MemberPress {

	/** @var bool */
	public $active = true;

	/** @var array<int,int> */
	public $user_levels = array();

	public function is_active() {
		return $this->active;
	}

	public function user_level_ids( $user_id ) {
		return $this->user_levels;
	}
}

/**
 * @covers \LumaViewer\Membership\Gate
 */
final class GateTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( '__' )->returnArg( 1 );
		Functions\when( 'wp_parse_args' )->alias(
			static function ( $args, $defaults ) {
				return array_merge( (array) $defaults, (array) $args );
			}
		);
		Functions\when( 'current_user_can' )->justReturn( false );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Point Settings at a fixed stored array.
	 *
	 * @param array $stored Stored settings.
	 * @return void
	 */
	private function with_settings( array $stored ): void {
		Functions\when( 'get_option' )->alias(
			static function ( $name, $default = array() ) use ( $stored ) {
				return Settings::OPTION === $name ? $stored : $default;
			}
		);
	}

	/**
	 * Build an event carrying the given tag id.
	 *
	 * @param string $tag_id Tag api_id (empty for no tag).
	 * @return Event
	 */
	private function event_with_tag( string $tag_id ): Event {
		$entry = array(
			'event' => array(
				'api_id' => 'evt-1',
				'name'   => 'Test',
			),
		);
		if ( '' !== $tag_id ) {
			$entry['tags'] = array(
				array(
					'api_id' => $tag_id,
					'name'   => 'Tag',
				),
			);
		}
		return Event::from_entry( $entry );
	}

	public function test_visible_when_no_map_configured(): void {
		$this->with_settings( array( 'category_map' => array() ) );
		$gate = new Gate( new FakeMemberPress() );
		$this->assertSame( Gate::VISIBLE, $gate->resolve( $this->event_with_tag( 'evttag-x' ), 0 ) );
	}

	public function test_visible_when_memberpress_inactive(): void {
		$this->with_settings( array( 'category_map' => array( 'evttag-x' => array( 12 ) ) ) );
		$mepr         = new FakeMemberPress();
		$mepr->active = false;
		$gate         = new Gate( $mepr );
		$this->assertSame( Gate::VISIBLE, $gate->resolve( $this->event_with_tag( 'evttag-x' ), 0 ) );
	}

	public function test_visible_when_event_tag_not_mapped(): void {
		$this->with_settings( array( 'category_map' => array( 'evttag-other' => array( 12 ) ) ) );
		$gate = new Gate( new FakeMemberPress() );
		$this->assertSame( Gate::VISIBLE, $gate->resolve( $this->event_with_tag( 'evttag-x' ), 0 ) );
	}

	public function test_teaser_for_non_member_when_behavior_is_teaser(): void {
		$this->with_settings(
			array(
				'category_map'    => array( 'evttag-x' => array( 12 ) ),
				'gating_behavior' => 'teaser',
			)
		);
		$gate = new Gate( new FakeMemberPress() );
		$this->assertSame( Gate::TEASER, $gate->resolve( $this->event_with_tag( 'evttag-x' ), 5 ) );
	}

	public function test_hidden_for_non_member_when_behavior_is_hide(): void {
		$this->with_settings(
			array(
				'category_map'    => array( 'evttag-x' => array( 12 ) ),
				'gating_behavior' => 'hide',
			)
		);
		$gate = new Gate( new FakeMemberPress() );
		$this->assertSame( Gate::HIDDEN, $gate->resolve( $this->event_with_tag( 'evttag-x' ), 5 ) );
	}

	public function test_visible_for_member_holding_required_level(): void {
		$this->with_settings( array( 'category_map' => array( 'evttag-x' => array( 12 ) ) ) );
		$mepr              = new FakeMemberPress();
		$mepr->user_levels = array( 12 );
		$gate              = new Gate( $mepr );
		$this->assertSame( Gate::VISIBLE, $gate->resolve( $this->event_with_tag( 'evttag-x' ), 5 ) );
	}

	public function test_admin_bypasses_gate(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		$this->with_settings(
			array(
				'category_map'    => array( 'evttag-x' => array( 12 ) ),
				'gating_behavior' => 'hide',
			)
		);
		$gate = new Gate( new FakeMemberPress() );
		$this->assertSame( Gate::VISIBLE, $gate->resolve( $this->event_with_tag( 'evttag-x' ), 1 ) );
	}
}
