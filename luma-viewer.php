<?php
/**
 * Plugin Name:       Calendar Viewer for Luma Events
 * Plugin URI:        https://github.com/sveinmagnus/LumaViewer
 * Description:       Display your Lu.ma organization calendar on WordPress with The Events Calendar–style views, Gutenberg & Elementor blocks, and MemberPress-aware access by event category.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Svein
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       luma-viewer
 * Domain Path:       /languages
 *
 * @package LumaViewer
 */

defined( 'ABSPATH' ) || exit;

define( 'LUMA_VIEWER_VERSION', '0.1.0' );
define( 'LUMA_VIEWER_FILE', __FILE__ );
define( 'LUMA_VIEWER_DIR', plugin_dir_path( __FILE__ ) );
define( 'LUMA_VIEWER_URL', plugin_dir_url( __FILE__ ) );
define( 'LUMA_VIEWER_BASENAME', plugin_basename( __FILE__ ) );

/*
 * Autoloading: use Composer's autoloader when present (production / CI), and
 * fall back to a minimal PSR-4 loader so the plugin runs straight from a clone
 * before `composer install` has been run.
 */
$luma_viewer_autoload = LUMA_VIEWER_DIR . 'vendor/autoload.php';
if ( is_readable( $luma_viewer_autoload ) ) {
	require $luma_viewer_autoload;
} else {
	spl_autoload_register(
		static function ( $class_name ) {
			$prefix = 'LumaViewer\\';
			$len    = strlen( $prefix );
			if ( 0 !== strncmp( $prefix, $class_name, $len ) ) {
				return;
			}
			$relative = substr( $class_name, $len );
			$path     = LUMA_VIEWER_DIR . 'src/' . str_replace( '\\', '/', $relative ) . '.php';
			if ( is_readable( $path ) ) {
				require $path;
			}
		}
	);
}

register_activation_hook( __FILE__, array( '\LumaViewer\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\LumaViewer\Plugin', 'deactivate' ) );

add_action(
	'plugins_loaded',
	static function () {
		\LumaViewer\Plugin::instance()->boot();
	}
);
