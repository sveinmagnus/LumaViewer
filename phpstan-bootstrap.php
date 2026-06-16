<?php
/**
 * PHPStan bootstrap.
 *
 * Declares the constants the plugin defines at runtime in its main file, so
 * static analysis of individual source files resolves them. This file is only
 * used by PHPStan — it is not loaded by WordPress and is not shipped.
 *
 * @package LumaViewer
 */

define( 'LUMA_VIEWER_VERSION', '0.0.0' );
define( 'LUMA_VIEWER_FILE', __FILE__ );
define( 'LUMA_VIEWER_DIR', __DIR__ . '/' );
define( 'LUMA_VIEWER_URL', 'https://example.test/wp-content/plugins/luma-viewer/' );
define( 'LUMA_VIEWER_BASENAME', 'luma-viewer/luma-viewer.php' );
