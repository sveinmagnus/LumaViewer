<?php
/**
 * Integration smoke tests (require a WordPress test environment).
 *
 * @package LumaViewer\Tests
 */

namespace LumaViewer\Tests\Integration;

use LumaViewer\Plugin;
use LumaViewer\Settings;
use WP_UnitTestCase;

/**
 * Verifies the plugin boots and wires itself into WordPress.
 */
final class PluginTest extends WP_UnitTestCase {

	public function test_plugin_class_loaded(): void {
		$this->assertTrue( class_exists( Plugin::class ) );
	}

	public function test_shortcodes_registered(): void {
		$this->assertTrue( shortcode_exists( 'luma_calendar' ) );
		$this->assertTrue( shortcode_exists( 'luma_event' ) );
	}

	public function test_activation_seeds_options(): void {
		delete_option( Settings::OPTION );
		Plugin::activate();

		$stored = get_option( Settings::OPTION );
		$this->assertIsArray( $stored );
		$this->assertArrayHasKey( 'api_key', $stored );
		$this->assertNotEmpty( $stored['webhook_secret'] );
	}

	public function test_calendar_shortcode_renders_a_container(): void {
		// No API key configured -> renders the wrapper with an error/empty state,
		// which is enough to prove the render path is wired end-to-end.
		$html = do_shortcode( '[luma_calendar]' );
		$this->assertStringContainsString( 'luma-viewer', $html );
	}
}
