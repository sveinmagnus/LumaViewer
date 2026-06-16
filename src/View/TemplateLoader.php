<?php
/**
 * Theme-overridable template loader.
 *
 * @package LumaViewer
 */

namespace LumaViewer\View;

defined( 'ABSPATH' ) || exit;

/**
 * Resolves view templates, letting a theme override the plugin's defaults by
 * placing a file at `luma-viewer/<name>.php` in the (child) theme — the pattern
 * The Events Calendar uses.
 */
class TemplateLoader {

	/**
	 * Locate a template by name (e.g. "list", "partials/event-card").
	 *
	 * @param string $name Template name without extension.
	 * @return string|null Absolute path or null if not found.
	 */
	public function locate( $name ) {
		$relative = 'luma-viewer/' . ltrim( $name, '/' ) . '.php';

		foreach ( array( get_stylesheet_directory(), get_template_directory() ) as $dir ) {
			$candidate = trailingslashit( $dir ) . $relative;
			if ( is_readable( $candidate ) ) {
				return $candidate;
			}
		}

		$plugin = LUMA_VIEWER_DIR . 'templates/' . ltrim( $name, '/' ) . '.php';
		return is_readable( $plugin ) ? $plugin : null;
	}

	/**
	 * Render a template to a string with the given data in scope.
	 *
	 * @param string $name Template name.
	 * @param array  $data Variables exposed to the template.
	 * @return string
	 */
	public function capture( $name, array $data = array() ) {
		$file = $this->locate( $name );
		if ( null === $file ) {
			return '';
		}

		ob_start();
		( static function ( $luma_viewer_template, $luma_viewer_data ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- controlled, plugin-provided data only.
			extract( $luma_viewer_data, EXTR_SKIP );
			include $luma_viewer_template;
		} )( $file, $data );

		return (string) ob_get_clean();
	}
}
