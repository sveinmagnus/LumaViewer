<?php
/**
 * Bootstrap for WordPress integration tests.
 *
 * Runs inside a real WordPress test environment — easiest via wp-env:
 *
 *   npx wp-env start
 *   npx wp-env run tests-cli --env-cwd=wp-content/plugins/luma-viewer \
 *       vendor/bin/phpunit -c phpunit-integration.xml.dist
 *
 * @package LumaViewer\Tests
 */

$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
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
