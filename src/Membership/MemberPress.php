<?php
/**
 * MemberPress adapter.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Membership;

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrapper isolating all MemberPress specifics, so the rest of the plugin
 * never touches MemberPress classes directly and degrades gracefully when it is
 * not installed. See the luma-viewer skill's references/memberpress.md.
 */
class MemberPress {

	/**
	 * Per-request cache of user → level ids, so resolving visibility across a
	 * whole result set doesn't issue one MeprUser query per event.
	 *
	 * @var array<int,array<int,int>>
	 */
	private $level_cache = array();

	/**
	 * Whether MemberPress is available.
	 *
	 * @return bool
	 */
	public function is_active() {
		return defined( 'MEPR_VERSION' ) || class_exists( 'MeprUser' );
	}

	/**
	 * Published membership levels as id => title (empty if MemberPress is off).
	 *
	 * @return array<int,string>
	 */
	public function levels() {
		if ( ! $this->is_active() ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'   => 'memberpressproduct',
				'post_status' => 'publish',
				'numberposts' => -1,
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);

		$levels = array();
		foreach ( $posts as $post ) {
			$levels[ (int) $post->ID ] = $post->post_title;
		}
		return $levels;
	}

	/**
	 * Membership level IDs the given user is actively subscribed to.
	 *
	 * @param int $user_id User ID.
	 * @return array<int,int>
	 */
	public function user_level_ids( $user_id ) {
		$user_id = (int) $user_id;
		if ( ! $user_id || ! class_exists( 'MeprUser' ) ) {
			return array();
		}

		if ( isset( $this->level_cache[ $user_id ] ) ) {
			return $this->level_cache[ $user_id ];
		}

		$mepr_user = new \MeprUser( $user_id );
		if ( ! method_exists( $mepr_user, 'active_product_subscriptions' ) ) {
			$this->level_cache[ $user_id ] = array();
			return array();
		}

		$this->level_cache[ $user_id ] = array_map( 'intval', (array) $mepr_user->active_product_subscriptions() );
		return $this->level_cache[ $user_id ];
	}
}
