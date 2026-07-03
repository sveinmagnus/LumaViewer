<?php
/**
 * Bootstrap for WordPress integration tests.
 *
 * Runs inside a real WordPress test environment — easiest via wp-env (the
 * mount directory is the repo folder name, e.g. LumaViewer):
 *
 *   npx wp-env start
 *   npx wp-env run tests-cli --env-cwd=wp-content/plugins/LumaViewer \
 *       vendor/bin/phpunit -c phpunit-integration.xml.dist
 *
 * @package LumaViewer\Tests
 */

// Prefer the test suite wp-env provides (WP_TESTS_DIR) — it ships the
// wp-tests-config.php that defines WP_TESTS_DOMAIN, DB creds, WP_PHP_BINARY, etc.
// Fall back to the vendored wp-phpunit for other runners.
$_tests_dir = getenv( 'WP_TESTS_DIR' );
if ( ! $_tests_dir ) {
	$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
}
if ( ! $_tests_dir ) {
	$_tests_dir = dirname( __DIR__, 2 ) . '/vendor/wp-phpunit/wp-phpunit';
}

require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';
require_once $_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function () {
		require dirname( __DIR__, 2 ) . '/luma-viewer.php';
	}
);

require $_tests_dir . '/includes/bootstrap.php';
