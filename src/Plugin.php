<?php
/**
 * Plugin bootstrap / service wiring.
 *
 * @package LumaViewer
 */

namespace LumaViewer;

use LumaViewer\Admin\Notices;
use LumaViewer\Admin\SettingsPage;
use LumaViewer\Api\Client;
use LumaViewer\Api\Endpoints;
use LumaViewer\Cache\Cache;
use LumaViewer\Events\Repository;
use LumaViewer\Frontend\Assets;
use LumaViewer\Frontend\Shortcodes;
use LumaViewer\View\Formatter;
use LumaViewer\View\Renderer;
use LumaViewer\View\TemplateLoader;

defined( 'ABSPATH' ) || exit;

/**
 * Central plugin container. Instantiated once on `plugins_loaded`; every hook is
 * registered from here so nothing runs in the global scope.
 */
final class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Luma API endpoints wrapper.
	 *
	 * @var Endpoints
	 */
	private $endpoints;

	/**
	 * Get the shared instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor — use {@see Plugin::instance()}.
	 */
	private function __construct() {}

	/**
	 * Register hooks and wire components.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		$this->endpoints = new Endpoints( new Client( (string) Settings::get( 'api_key' ) ) );

		$repository = new Repository( $this->endpoints, new Cache( (int) Settings::get( 'cache_ttl' ) ) );
		$renderer   = new Renderer( $repository, new TemplateLoader(), new Formatter() );

		( new Assets() )->register();
		( new Shortcodes( $renderer ) )->register();

		if ( is_admin() ) {
			( new SettingsPage( $this->endpoints ) )->register();
			( new Notices() )->register();
		}
	}

	/**
	 * Expose the API endpoints wrapper to other components.
	 *
	 * @return Endpoints
	 */
	public function endpoints() {
		return $this->endpoints;
	}

	/**
	 * Load translations.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'luma-viewer', false, dirname( LUMA_VIEWER_BASENAME ) . '/languages' );
	}

	/**
	 * Activation: seed default options.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, Settings::defaults() );
		}
	}

	/**
	 * Deactivation: clear scheduled work.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'luma_viewer_refresh_cache' );
	}
}
