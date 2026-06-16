<?php
/**
 * PHPUnit bootstrap for unit tests.
 *
 * These are pure unit tests — no WordPress runtime. WordPress functions are
 * stubbed per-test with Brain Monkey; we only define the few constants/classes
 * the plugin's classes reference at load time.
 *
 * @package LumaViewer\Tests
 */

// Plugin source files guard with `defined('ABSPATH') || exit;`, so ABSPATH must
// exist before any LumaViewer class autoloads.
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', sys_get_temp_dir() . '/wp/' );
}

require dirname( __DIR__ ) . '/vendor/autoload.php';

// Minimal WP_Error stand-in so units can assert error returns without WordPress.
if ( ! class_exists( 'WP_Error' ) ) {
	// phpcs:ignore Generic.Classes.OpeningBraceSameLine, PEAR.NamingConventions
	class WP_Error {

		/** @var string */
		private $code;

		/** @var string */
		private $message;

		/** @var mixed */
		private $data;

		public function __construct( $code = '', $message = '', $data = '' ) {
			$this->code    = $code;
			$this->message = $message;
			$this->data    = $data;
		}

		public function get_error_code() {
			return $this->code;
		}

		public function get_error_message() {
			return $this->message;
		}

		public function get_error_data() {
			return $this->data;
		}
	}
}
