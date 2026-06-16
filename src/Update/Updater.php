<?php
/**
 * Self-hosted update checker (GitHub Releases).
 *
 * @package LumaViewer
 */

namespace LumaViewer\Update;

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

defined( 'ABSPATH' ) || exit;

/**
 * Wires the Plugin Update Checker library to this plugin's GitHub releases, so
 * WordPress shows update notices (and can auto-update) without the plugin being
 * hosted on WordPress.org. Inert when the library isn't bundled (e.g. a dev
 * checkout without `composer install`) or when disabled via filter — e.g. for a
 * WordPress.org build, which must rely on WordPress.org's own updates instead.
 */
class Updater {

	/**
	 * Default repository the releases are published to. Override with the
	 * `luma_viewer_update_repo` filter to match the actual repository.
	 */
	const DEFAULT_REPO = 'https://github.com/sveins/luma-viewer/';

	/**
	 * Set up the update checker.
	 *
	 * @return void
	 */
	public function register() {
		if ( ! class_exists( PucFactory::class ) ) {
			return;
		}

		/**
		 * Filters whether GitHub-based update checks are enabled.
		 *
		 * @param bool $enabled Default true.
		 */
		if ( ! apply_filters( 'luma_viewer_enable_github_updates', true ) ) {
			return;
		}

		/**
		 * Filters the repository URL update checks point at.
		 *
		 * @param string $repo Repository URL.
		 */
		$repo = (string) apply_filters( 'luma_viewer_update_repo', self::DEFAULT_REPO );
		if ( '' === $repo ) {
			return;
		}

		$checker = PucFactory::buildUpdateChecker( $repo, LUMA_VIEWER_FILE, 'luma-viewer' );

		// Prefer the release asset (the built luma-viewer.zip) over the source
		// tarball, so users get the production build, not the raw repo.
		$api = $checker->getVcsApi();
		if ( is_object( $api ) && method_exists( $api, 'enableReleaseAssets' ) ) {
			$api->enableReleaseAssets();
		}
	}
}
