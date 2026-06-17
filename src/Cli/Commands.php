<?php
/**
 * WP-CLI commands.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Cli;

use LumaViewer\Cache\Cache;
use LumaViewer\Cache\Cron;
use LumaViewer\Events\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * `wp luma-viewer <command>` — refresh, clear, and list cached events.
 */
class Commands {

	/** @var Repository */
	private $repo;

	/** @var Cache */
	private $cache;

	/**
	 * Constructor.
	 *
	 * @param Repository $repo  Event repository.
	 * @param Cache      $cache Response cache.
	 */
	public function __construct( Repository $repo, Cache $cache ) {
		$this->repo  = $repo;
		$this->cache = $cache;
	}

	/**
	 * Register the command with WP-CLI.
	 *
	 * @return void
	 */
	public function register() {
		\WP_CLI::add_command( 'luma-viewer', $this );
	}

	/**
	 * Refresh cached events from Luma now (clears the cache and re-warms it).
	 *
	 * @param array $args       Positional args (unused).
	 * @param array $assoc_args Associative args (unused).
	 * @return void
	 */
	public function refresh( $args, $assoc_args ) {
		unset( $args, $assoc_args );
		$this->cache->flush();
		do_action( Cron::HOOK ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- the hook constant is prefixed.
		\WP_CLI::success( 'Luma Viewer cache refreshed.' );
	}

	/**
	 * Clear all cached Luma data.
	 *
	 * @param array $args       Positional args (unused).
	 * @param array $assoc_args Associative args (unused).
	 * @return void
	 */
	public function clear( $args, $assoc_args ) {
		unset( $args, $assoc_args );
		$this->cache->flush();
		\WP_CLI::success( 'Luma Viewer cache cleared.' );
	}

	/**
	 * List upcoming events.
	 *
	 * ## OPTIONS
	 *
	 * [--count=<count>]
	 * : Maximum number of events to list. Default 20.
	 *
	 * @param array $args       Positional args (unused).
	 * @param array $assoc_args Associative args.
	 * @return void
	 */
	public function list( $args, $assoc_args ) {
		unset( $args );
		$count  = isset( $assoc_args['count'] ) ? (int) $assoc_args['count'] : 20;
		$result = $this->repo->get_events( array( 'count' => $count ) );

		if ( $result['error'] ) {
			\WP_CLI::error( $result['error']->get_error_message() );
		}
		if ( empty( $result['events'] ) ) {
			\WP_CLI::line( 'No upcoming events.' );
			return;
		}
		foreach ( $result['events'] as $event ) {
			$when = $event->has_start() ? $event->start()->format( 'Y-m-d H:i' ) : '—';
			\WP_CLI::line( sprintf( '%s  %s  (%s)', $when, $event->name(), $event->id() ) );
		}
	}
}
