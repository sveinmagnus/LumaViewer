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
use LumaViewer\Blocks\Blocks;
use LumaViewer\Cache\Cache;
use LumaViewer\Cache\Cron;
use LumaViewer\Cache\Webhook;
use LumaViewer\Elementor\Module as ElementorModule;
use LumaViewer\Events\Repository;
use LumaViewer\Frontend\Assets;
use LumaViewer\Frontend\RestController;
use LumaViewer\Frontend\Shortcodes;
use LumaViewer\Frontend\SingleRoute;
use LumaViewer\Membership\Gate;
use LumaViewer\Membership\MemberPress;
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

		$cache       = new Cache( (int) Settings::get( 'cache_ttl' ) );
		$repository  = new Repository( $this->endpoints, $cache );
		$formatter   = new Formatter();
		$memberpress = new MemberPress();
		$gate        = new Gate( $memberpress );
		$renderer    = new Renderer( $repository, new TemplateLoader(), $formatter, $gate );

		( new Assets() )->register();
		( new Shortcodes( $renderer ) )->register();
		( new Blocks( $renderer ) )->register();
		( new RestController( $renderer ) )->register();
		( new SingleRoute( $repository, $renderer, $formatter, $gate ) )->register();
		( new ElementorModule( $renderer ) )->register();
		( new Cron( $repository ) )->register();
		( new Webhook( $cache ) )->register();

		if ( is_admin() ) {
			( new SettingsPage( $this->endpoints, $memberpress, $cache ) )->register();
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
		$settings = get_option( Settings::OPTION );
		if ( ! is_array( $settings ) ) {
			$settings = Settings::defaults();
		}
		if ( empty( $settings['webhook_secret'] ) ) {
			$settings['webhook_secret'] = wp_generate_password( 40, false );
		}
		update_option( Settings::OPTION, $settings );

		// Defer the rewrite flush to `init`, once SingleRoute has registered its
		// rule (see SingleRoute::add_rewrite()).
		update_option( SingleRoute::FLUSH_FLAG, '1' );
	}

	/**
	 * Deactivation: clear scheduled work and rewrite rules.
	 *
	 * @return void
	 */
	public static function deactivate() {
		wp_clear_scheduled_hook( 'luma_viewer_refresh_cache' );
		flush_rewrite_rules();
	}
}
