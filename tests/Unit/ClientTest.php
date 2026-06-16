<?php
/**
 * Unit tests for the Luma HTTP client.
 *
 * @package LumaViewer\Tests
 */

namespace LumaViewer\Tests\Unit;

use Brain\Monkey;
use Brain\Monkey\Functions;
use LumaViewer\Api\Client;
use PHPUnit\Framework\TestCase;

/**
 * @covers \LumaViewer\Api\Client
 */
final class ClientTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		// __() just returns the original string in unit context.
		Functions\when( '__' )->returnArg( 1 );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_has_key_is_false_when_blank(): void {
		$this->assertFalse( ( new Client( '' ) )->has_key() );
		$this->assertFalse( ( new Client( '   ' ) )->has_key() );
	}

	public function test_has_key_is_true_when_set(): void {
		$this->assertTrue( ( new Client( 'secret-key' ) )->has_key() );
	}

	public function test_request_without_key_returns_wp_error(): void {
		$result = ( new Client( '' ) )->get( '/v1/calendars/get' );
		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_get_request_decodes_successful_json(): void {
		$payload = array( 'name' => 'My Calendar' );

		Functions\expect( 'wp_remote_request' )
			->once()
			->andReturn(
				array(
					'response' => array(
						'code'    => 200,
						'message' => 'OK',
					),
					'body'     => json_encode( $payload ),
				)
			);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_response_message' )->justReturn( 'OK' );
		Functions\when( 'wp_remote_retrieve_header' )->justReturn( '' );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( json_encode( $payload ) );

		$client = new Client( 'secret-key' );
		$result = $client->get( '/v1/calendars/get' );

		$this->assertIsArray( $result );
		$this->assertSame( 'My Calendar', $result['name'] );
	}

	public function test_http_error_status_returns_wp_error(): void {
		Functions\expect( 'wp_remote_request' )
			->once()
			->andReturn(
				array(
					'response' => array(
						'code'    => 404,
						'message' => 'Not Found',
					),
					'body'     => '{"message":"Calendar not found"}',
				)
			);
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 404 );
		Functions\when( 'wp_remote_retrieve_response_message' )->justReturn( 'Not Found' );
		Functions\when( 'wp_remote_retrieve_header' )->justReturn( '' );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn( '{"message":"Calendar not found"}' );

		$result = ( new Client( 'secret-key' ) )->get( '/v1/calendars/get' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'Calendar not found', $result->get_error_message() );
	}
}
