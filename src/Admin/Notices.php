<?php
/**
 * Admin notices.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Admin;

use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Surfaces setup problems to admins (missing API key, MemberPress absent once
 * category gating is configured).
 */
class Notices {

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_notices', array( $this, 'maybe_show' ) );
	}

	/**
	 * Render notices when relevant.
	 *
	 * @return void
	 */
	public function maybe_show() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$screen           = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$on_settings_page = $screen && 'settings_page_' . SettingsPage::MENU_SLUG === $screen->id;

		if ( '' === trim( (string) Settings::get( 'api_key' ) ) && ! $on_settings_page ) {
			$url = admin_url( 'options-general.php?page=' . SettingsPage::MENU_SLUG );
			printf(
				'<div class="notice notice-warning"><p>%s <a href="%s">%s</a></p></div>',
				esc_html__( 'Luma-viewer needs a Luma API key before it can show events.', 'luma-viewer' ),
				esc_url( $url ),
				esc_html__( 'Add your key', 'luma-viewer' )
			);
		}

		$map = (array) Settings::get( 'category_map' );
		if ( ! empty( $map ) && ! $this->memberpress_active() ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'Luma-viewer has category-based access rules, but MemberPress is not active — all events are shown to everyone until it is enabled.', 'luma-viewer' )
			);
		}
	}

	/**
	 * Whether MemberPress is available.
	 *
	 * @return bool
	 */
	private function memberpress_active() {
		return defined( 'MEPR_VERSION' ) || class_exists( 'MeprUser' );
	}
}
