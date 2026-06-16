<?php
/**
 * Visibility gate: maps Luma event tags to MemberPress access.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Membership;

use LumaViewer\Model\Event;
use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Decides whether a given user may see a given event, based on the admin's
 * Luma-tag → membership-level mapping. Events with no mapped tag are public.
 */
class Gate {

	const VISIBLE = 'visible';
	const TEASER  = 'teaser';
	const HIDDEN  = 'hidden';

	/** @var MemberPress */
	private $mepr;

	/**
	 * Constructor.
	 *
	 * @param MemberPress $mepr MemberPress adapter.
	 */
	public function __construct( MemberPress $mepr ) {
		$this->mepr = $mepr;
	}

	/**
	 * Whether gating is configured and active at all.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		$map = (array) Settings::get( 'category_map' );
		return ! empty( $map ) && $this->mepr->is_active();
	}

	/**
	 * Resolve visibility for an event and user.
	 *
	 * @param Event $event   Event.
	 * @param int   $user_id Current user ID (0 if logged out).
	 * @return string One of VISIBLE | TEASER | HIDDEN.
	 */
	public function resolve( Event $event, $user_id ) {
		if ( ! $this->is_enabled() ) {
			return self::VISIBLE;
		}

		$map      = (array) Settings::get( 'category_map' );
		$required = array();
		foreach ( $event->tag_ids() as $tag_id ) {
			if ( ! empty( $map[ $tag_id ] ) ) {
				$required = array_merge( $required, array_map( 'intval', (array) $map[ $tag_id ] ) );
			}
		}
		$required = array_values( array_unique( array_filter( $required ) ) );

		if ( empty( $required ) ) {
			return self::VISIBLE; // Event is not gated.
		}

		// Let admins preview gated events.
		if ( current_user_can( 'manage_options' ) ) {
			return self::VISIBLE;
		}

		$held = $this->mepr->user_level_ids( (int) $user_id );
		if ( ! empty( array_intersect( $required, $held ) ) ) {
			return self::VISIBLE;
		}

		return 'hide' === Settings::get( 'gating_behavior' ) ? self::HIDDEN : self::TEASER;
	}
}
