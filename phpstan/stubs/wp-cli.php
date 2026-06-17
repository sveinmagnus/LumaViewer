<?php
/**
 * PHPStan stub for the small WP-CLI surface Luma Viewer uses. Scanned only.
 *
 * @package LumaViewer
 */

class WP_CLI {

	/**
	 * @param string          $name     Command name.
	 * @param object|callable $callable Command handler.
	 * @param array           $args     Options.
	 * @return void
	 */
	public static function add_command( $name, $callable, $args = array() ) {}

	/**
	 * @param string $message Message.
	 * @return void
	 */
	public static function success( $message ) {}

	/**
	 * @param string $message Message.
	 * @param bool   $exit    Whether to exit.
	 * @return void
	 */
	public static function error( $message, $exit = true ) {}

	/**
	 * @param string $message Message.
	 * @return void
	 */
	public static function line( $message = '' ) {}
}
