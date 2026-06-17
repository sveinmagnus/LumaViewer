<?php
/**
 * PHPStan stub for the Elementor classes Luma Viewer integrates with.
 *
 * Only scanned by PHPStan (never loaded at runtime, never shipped). Keep in sync
 * with the small Elementor surface the widgets actually use.
 *
 * @package LumaViewer
 */

namespace Elementor;

class Controls_Manager {
	const TEXT     = 'text';
	const SELECT   = 'select';
	const NUMBER   = 'number';
	const SWITCHER = 'switcher';
}

abstract class Widget_Base {

	/**
	 * @param array $data Widget data.
	 * @param mixed $args Widget args.
	 */
	public function __construct( $data = array(), $args = null ) {}

	/** @return string */
	public function get_name() {
		return '';
	}

	/** @return string */
	public function get_title() {
		return '';
	}

	/** @return string */
	public function get_icon() {
		return '';
	}

	/** @return string[] */
	public function get_categories() {
		return array();
	}

	/** @return string[] */
	public function get_keywords() {
		return array();
	}

	/**
	 * @param string $section_id Section id.
	 * @param array  $args       Section args.
	 * @return void
	 */
	protected function start_controls_section( $section_id, $args = array() ) {}

	/**
	 * @param string $control_id Control id.
	 * @param array  $args       Control args.
	 * @return void
	 */
	protected function add_control( $control_id, $args = array() ) {}

	/** @return void */
	protected function end_controls_section() {}

	/** @return void */
	protected function register_controls() {}

	/**
	 * @param string|null $setting Optional setting key.
	 * @return array<string,mixed>
	 */
	protected function get_settings_for_display( $setting = null ) {
		return array();
	}

	/** @return void */
	protected function render() {}
}

class Widgets_Manager {

	/**
	 * @param Widget_Base $widget Widget instance.
	 * @return void
	 */
	public function register( $widget ) {}
}
