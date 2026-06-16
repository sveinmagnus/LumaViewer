<?php
/**
 * PHPStan stub for the MemberPress classes Luma Viewer integrates with.
 *
 * Only scanned by PHPStan (never loaded at runtime, never shipped) so static
 * analysis resolves `\MeprUser` even though MemberPress isn't installed in CI.
 * Keep this in sync with the methods the plugin actually calls; verify against
 * the live MemberPress version when integrating.
 *
 * @package LumaViewer
 */

class MeprUser {

	/**
	 * @param int|null $id User ID.
	 */
	public function __construct( $id = null ) {}

	/**
	 * Membership (product) IDs the user is actively subscribed to.
	 *
	 * @return array<int,int>
	 */
	public function active_product_subscriptions() {
		return array();
	}
}
