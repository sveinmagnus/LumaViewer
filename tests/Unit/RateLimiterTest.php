<?php
/**
 * Unit tests for the rate limiter.
 *
 * @package LumaViewer\Tests
 */

namespace LumaViewer\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use LumaViewer\Frontend\RateLimiter;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LumaViewer\Frontend\RateLimiter
 */
final class RateLimiterTest extends TestCase {

	/** @var array<string,mixed> */
	private $store = array();

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		$this->store = array();
		$store       = &$this->store;

		Functions\when( 'get_transient' )->alias(
			static function ( $key ) use ( &$store ) {
				return array_key_exists( $key, $store ) ? $store[ $key ] : false;
			}
		);
		Functions\when( 'set_transient' )->alias(
			static function ( $key, $value ) use ( &$store ) {
				$store[ $key ] = $value;
				return true;
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_allows_up_to_limit_then_blocks(): void {
		$limiter = new RateLimiter();
		$this->assertTrue( $limiter->allow( 'ip', 3, 60 ) );
		$this->assertTrue( $limiter->allow( 'ip', 3, 60 ) );
		$this->assertTrue( $limiter->allow( 'ip', 3, 60 ) );
		$this->assertFalse( $limiter->allow( 'ip', 3, 60 ) );
	}

	public function test_zero_limit_disables_limiting(): void {
		$this->assertTrue( ( new RateLimiter() )->allow( 'ip', 0, 60 ) );
	}

	public function test_buckets_are_independent(): void {
		$limiter = new RateLimiter();
		$this->assertTrue( $limiter->allow( 'a', 1, 60 ) );
		$this->assertFalse( $limiter->allow( 'a', 1, 60 ) );
		$this->assertTrue( $limiter->allow( 'b', 1, 60 ) );
	}
}
