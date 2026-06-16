<?php
/**
 * Settings screen + connection test.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Admin;

use LumaViewer\Api\Endpoints;
use LumaViewer\Cache\Cache;
use LumaViewer\Membership\MemberPress;
use LumaViewer\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the Settings → Luma Viewer screen, the option group, and the
 * "Test connection" AJAX handler.
 */
class SettingsPage {

	const MENU_SLUG  = 'luma-viewer';
	const GROUP      = 'luma_viewer_group';
	const TEST_NONCE = 'luma_viewer_test_connection';

	/**
	 * API endpoints.
	 *
	 * @var Endpoints
	 */
	private $endpoints;

	/**
	 * MemberPress adapter.
	 *
	 * @var MemberPress
	 */
	private $memberpress;

	/**
	 * Response cache.
	 *
	 * @var Cache
	 */
	private $cache;

	/**
	 * Constructor.
	 *
	 * @param Endpoints   $endpoints   API endpoints.
	 * @param MemberPress $memberpress MemberPress adapter.
	 * @param Cache       $cache       Response cache.
	 */
	public function __construct( Endpoints $endpoints, MemberPress $memberpress, Cache $cache ) {
		$this->endpoints   = $endpoints;
		$this->memberpress = $memberpress;
		$this->cache       = $cache;
	}

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'wp_ajax_luma_viewer_test_connection', array( $this, 'ajax_test_connection' ) );
		add_action( 'admin_post_luma_viewer_clear_cache', array( $this, 'handle_clear_cache' ) );
	}

	/**
	 * Add the options page.
	 *
	 * @return void
	 */
	public function add_menu() {
		add_options_page(
			__( 'Luma Viewer', 'luma-viewer' ),
			__( 'Luma Viewer', 'luma-viewer' ),
			'manage_options',
			self::MENU_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register the setting and its fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::GROUP,
			Settings::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => Settings::defaults(),
			)
		);

		add_settings_section( 'luma_viewer_connection', __( 'Luma connection', 'luma-viewer' ), '__return_false', self::MENU_SLUG );
		add_settings_field( 'api_key', __( 'API key', 'luma-viewer' ), array( $this, 'field_api_key' ), self::MENU_SLUG, 'luma_viewer_connection' );

		add_settings_section( 'luma_viewer_display', __( 'Display', 'luma-viewer' ), '__return_false', self::MENU_SLUG );
		add_settings_field( 'default_view', __( 'Default view', 'luma-viewer' ), array( $this, 'field_default_view' ), self::MENU_SLUG, 'luma_viewer_display' );
		add_settings_field( 'per_page', __( 'Events per page', 'luma-viewer' ), array( $this, 'field_per_page' ), self::MENU_SLUG, 'luma_viewer_display' );
		add_settings_field( 'timezone_mode', __( 'Time zone', 'luma-viewer' ), array( $this, 'field_timezone_mode' ), self::MENU_SLUG, 'luma_viewer_display' );
		add_settings_field( 'cache_ttl', __( 'Cache lifetime', 'luma-viewer' ), array( $this, 'field_cache_ttl' ), self::MENU_SLUG, 'luma_viewer_display' );
		add_settings_field( 'single_base', __( 'Single-event URL base', 'luma-viewer' ), array( $this, 'field_single_base' ), self::MENU_SLUG, 'luma_viewer_display' );

		add_settings_section( 'luma_viewer_membership', __( 'Membership access', 'luma-viewer' ), array( $this, 'membership_intro' ), self::MENU_SLUG );
		add_settings_field( 'gating_behavior', __( 'For non-members', 'luma-viewer' ), array( $this, 'field_gating_behavior' ), self::MENU_SLUG, 'luma_viewer_membership' );
		add_settings_field( 'gate_cta_text', __( 'Gate message', 'luma-viewer' ), array( $this, 'field_gate_cta_text' ), self::MENU_SLUG, 'luma_viewer_membership' );
		add_settings_field( 'gate_cta_url', __( 'Gate button URL', 'luma-viewer' ), array( $this, 'field_gate_cta_url' ), self::MENU_SLUG, 'luma_viewer_membership' );
		add_settings_field( 'category_map', __( 'Category → membership', 'luma-viewer' ), array( $this, 'field_category_map' ), self::MENU_SLUG, 'luma_viewer_membership' );

		add_settings_section( 'luma_viewer_sync', __( 'Cache and sync', 'luma-viewer' ), array( $this, 'sync_intro' ), self::MENU_SLUG );
		add_settings_field( 'webhook', __( 'Luma webhook URL', 'luma-viewer' ), array( $this, 'field_webhook' ), self::MENU_SLUG, 'luma_viewer_sync' );
		add_settings_field( 'clear_cache', __( 'Cached events', 'luma-viewer' ), array( $this, 'field_clear_cache' ), self::MENU_SLUG, 'luma_viewer_sync' );
	}

	/**
	 * Sanitize submitted settings. Unknown / blank fields fall back to current
	 * values; the API key is preserved when the field is left blank so it isn't
	 * wiped by a normal save.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array
	 */
	public function sanitize( $input ) {
		$current = Settings::all();
		$out     = $current;

		if ( ! is_array( $input ) ) {
			return $out;
		}

		if ( isset( $input['api_key'] ) ) {
			$submitted = trim( (string) $input['api_key'] );
			if ( '' !== $submitted ) {
				$out['api_key'] = sanitize_text_field( $submitted );
			}
		}

		$views               = array( 'list', 'month', 'day', 'photo', 'summary' );
		$out['default_view'] = ( isset( $input['default_view'] ) && in_array( $input['default_view'], $views, true ) )
			? $input['default_view']
			: $current['default_view'];

		$out['per_page']      = isset( $input['per_page'] ) ? min( 100, max( 1, absint( $input['per_page'] ) ) ) : $current['per_page'];
		$out['cache_ttl']     = isset( $input['cache_ttl'] ) ? max( 60, absint( $input['cache_ttl'] ) ) : $current['cache_ttl'];
		$out['timezone_mode'] = ( isset( $input['timezone_mode'] ) && in_array( $input['timezone_mode'], array( 'event', 'site' ), true ) )
			? $input['timezone_mode']
			: $current['timezone_mode'];

		if ( isset( $input['single_base'] ) ) {
			$base               = sanitize_title( $input['single_base'] );
			$out['single_base'] = '' !== $base ? $base : 'events';
		}

		$out['gating_behavior'] = ( isset( $input['gating_behavior'] ) && in_array( $input['gating_behavior'], array( 'teaser', 'hide' ), true ) )
			? $input['gating_behavior']
			: $current['gating_behavior'];

		$out['gate_cta_text'] = isset( $input['gate_cta_text'] ) ? sanitize_text_field( $input['gate_cta_text'] ) : $current['gate_cta_text'];
		$out['gate_cta_url']  = isset( $input['gate_cta_url'] ) ? esc_url_raw( trim( (string) $input['gate_cta_url'] ) ) : $current['gate_cta_url'];

		// Only rebuild the category map when its section was actually rendered
		// (avoids wiping it when the mapping UI couldn't load).
		if ( isset( $input['category_map_submitted'] ) ) {
			$map = array();
			if ( isset( $input['category_map'] ) && is_array( $input['category_map'] ) ) {
				foreach ( $input['category_map'] as $tag_id => $level_ids ) {
					$tag_id = sanitize_text_field( (string) $tag_id );
					$ids    = array_values( array_unique( array_filter( array_map( 'absint', (array) $level_ids ) ) ) );
					if ( '' !== $tag_id && ! empty( $ids ) ) {
						$map[ $tag_id ] = $ids;
					}
				}
			}
			$out['category_map'] = $map;
		}

		return $out;
	}

	/**
	 * API key field. Never echoes the stored key — shows only whether one is set.
	 *
	 * @return void
	 */
	public function field_api_key() {
		$has_key = '' !== trim( (string) Settings::get( 'api_key' ) );
		printf(
			'<input type="password" name="%1$s[api_key]" id="luma_viewer_api_key" class="regular-text" autocomplete="off" value="" placeholder="%2$s" />',
			esc_attr( Settings::OPTION ),
			$has_key ? esc_attr__( 'A key is saved — leave blank to keep it', 'luma-viewer' ) : esc_attr__( 'Paste your Luma calendar API key', 'luma-viewer' )
		);
		echo '<p class="description">' . esc_html__( 'From your Luma calendar: Settings → Developer → API Keys. Requires Luma Plus. Save changes, then test the connection.', 'luma-viewer' ) . '</p>';
		$this->render_test_button();
	}

	/**
	 * Default view selector.
	 *
	 * @return void
	 */
	public function field_default_view() {
		$value = (string) Settings::get( 'default_view' );
		$views = array(
			'list'    => __( 'List', 'luma-viewer' ),
			'month'   => __( 'Month', 'luma-viewer' ),
			'day'     => __( 'Day', 'luma-viewer' ),
			'photo'   => __( 'Photo', 'luma-viewer' ),
			'summary' => __( 'Summary', 'luma-viewer' ),
		);
		echo '<select name="' . esc_attr( Settings::OPTION ) . '[default_view]">';
		foreach ( $views as $key => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $value, $key, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	/**
	 * Events-per-page field.
	 *
	 * @return void
	 */
	public function field_per_page() {
		printf(
			'<input type="number" min="1" max="100" name="%1$s[per_page]" value="%2$d" class="small-text" />',
			esc_attr( Settings::OPTION ),
			(int) Settings::get( 'per_page' )
		);
	}

	/**
	 * Timezone mode field.
	 *
	 * @return void
	 */
	public function field_timezone_mode() {
		$value = (string) Settings::get( 'timezone_mode' );
		$modes = array(
			'event' => __( "Each event's own time zone", 'luma-viewer' ),
			'site'  => __( 'The site time zone', 'luma-viewer' ),
		);
		echo '<select name="' . esc_attr( Settings::OPTION ) . '[timezone_mode]">';
		foreach ( $modes as $key => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $key ), selected( $value, $key, false ), esc_html( $label ) );
		}
		echo '</select>';
	}

	/**
	 * Cache TTL field.
	 *
	 * @return void
	 */
	public function field_cache_ttl() {
		printf(
			'<input type="number" min="60" step="60" name="%1$s[cache_ttl]" value="%2$d" class="small-text" /> %3$s',
			esc_attr( Settings::OPTION ),
			(int) Settings::get( 'cache_ttl' ),
			esc_html__( 'seconds', 'luma-viewer' )
		);
		echo '<p class="description">' . esc_html__( 'How long fetched events are cached before refreshing from Luma.', 'luma-viewer' ) . '</p>';
	}

	/**
	 * Single-event URL base field.
	 *
	 * @return void
	 */
	public function field_single_base() {
		printf(
			'<code>%1$s/%2$s/&lt;id&gt;/</code><br /><input type="text" name="%3$s[single_base]" value="%2$s" class="regular-text" />',
			esc_html( untrailingslashit( home_url() ) ),
			esc_attr( (string) Settings::get( 'single_base' ) ),
			esc_attr( Settings::OPTION )
		);
		echo '<p class="description">' . esc_html__( 'URL base for single-event pages. If links 404 after changing this, re-save Settings → Permalinks.', 'luma-viewer' ) . '</p>';
	}

	/**
	 * Membership section intro / status.
	 *
	 * @return void
	 */
	public function membership_intro() {
		if ( ! $this->memberpress->is_active() ) {
			echo '<p>' . esc_html__( 'MemberPress is not active — all events are shown to everyone until it is enabled.', 'luma-viewer' ) . '</p>';
			return;
		}
		echo '<p>' . esc_html__( 'Map Luma event categories (tags) to MemberPress memberships. Events with a mapped category are shown only to members who hold one of the mapped memberships.', 'luma-viewer' ) . '</p>';
	}

	/**
	 * Gating-behavior radios.
	 *
	 * @return void
	 */
	public function field_gating_behavior() {
		$value = (string) Settings::get( 'gating_behavior' );
		printf(
			'<label><input type="radio" name="%1$s[gating_behavior]" value="teaser" %2$s /> %3$s</label><br />',
			esc_attr( Settings::OPTION ),
			checked( $value, 'teaser', false ),
			esc_html__( 'Show a teaser with a join / log-in button', 'luma-viewer' )
		);
		printf(
			'<label><input type="radio" name="%1$s[gating_behavior]" value="hide" %2$s /> %3$s</label>',
			esc_attr( Settings::OPTION ),
			checked( $value, 'hide', false ),
			esc_html__( 'Hide the event entirely', 'luma-viewer' )
		);
	}

	/**
	 * Gate message field.
	 *
	 * @return void
	 */
	public function field_gate_cta_text() {
		printf(
			'<input type="text" name="%1$s[gate_cta_text]" value="%2$s" class="large-text" />',
			esc_attr( Settings::OPTION ),
			esc_attr( (string) Settings::get( 'gate_cta_text' ) )
		);
	}

	/**
	 * Gate button URL field.
	 *
	 * @return void
	 */
	public function field_gate_cta_url() {
		printf(
			'<input type="url" name="%1$s[gate_cta_url]" value="%2$s" class="regular-text" placeholder="%3$s" />',
			esc_attr( Settings::OPTION ),
			esc_attr( (string) Settings::get( 'gate_cta_url' ) ),
			esc_attr__( 'Defaults to the login page', 'luma-viewer' )
		);
	}

	/**
	 * Category → membership mapping table.
	 *
	 * @return void
	 */
	public function field_category_map() {
		if ( ! $this->memberpress->is_active() ) {
			echo '<p class="description">' . esc_html__( 'Enable MemberPress to map categories.', 'luma-viewer' ) . '</p>';
			return;
		}

		$levels = $this->memberpress->levels();
		if ( empty( $levels ) ) {
			echo '<p class="description">' . esc_html__( 'No MemberPress memberships found — create one first.', 'luma-viewer' ) . '</p>';
			return;
		}

		$tags = $this->fetch_tags();
		if ( empty( $tags ) ) {
			echo '<p class="description">' . esc_html__( 'No Luma categories found. Add an API key and create event tags in Luma.', 'luma-viewer' ) . '</p>';
			return;
		}

		// Marker only when the real mapping UI renders, so a save can't wipe the
		// stored map when the table couldn't be shown.
		printf( '<input type="hidden" name="%s[category_map_submitted]" value="1" />', esc_attr( Settings::OPTION ) );

		$map = (array) Settings::get( 'category_map' );

		echo '<table class="widefat striped" style="max-width:640px"><thead><tr><th>'
			. esc_html__( 'Luma category', 'luma-viewer' ) . '</th><th>'
			. esc_html__( 'Required memberships', 'luma-viewer' ) . '</th></tr></thead><tbody>';

		foreach ( $tags as $tag ) {
			$selected = isset( $map[ $tag['id'] ] ) ? array_map( 'intval', (array) $map[ $tag['id'] ] ) : array();
			echo '<tr><td>' . esc_html( $tag['name'] ) . '</td><td>';
			printf( '<select multiple size="3" name="%1$s[category_map][%2$s][]" style="min-width:220px">', esc_attr( Settings::OPTION ), esc_attr( $tag['id'] ) );
			foreach ( $levels as $level_id => $level_name ) {
				printf(
					'<option value="%1$d"%2$s>%3$s</option>',
					(int) $level_id,
					in_array( (int) $level_id, $selected, true ) ? ' selected="selected"' : '',
					esc_html( $level_name )
				);
			}
			echo '</select></td></tr>';
		}

		echo '</tbody></table>';
		echo '<p class="description">' . esc_html__( 'Hold Ctrl/Cmd to select multiple memberships. Categories left empty stay public.', 'luma-viewer' ) . '</p>';
	}

	/**
	 * Fetch Luma event tags as id/name pairs (best-effort).
	 *
	 * @return array<int,array{id:string,name:string}>
	 */
	private function fetch_tags() {
		if ( ! $this->endpoints->client()->has_key() ) {
			return array();
		}

		$response = $this->endpoints->list_event_tags();
		if ( is_wp_error( $response ) || ! is_array( $response ) ) {
			return array();
		}

		$entries = array();
		foreach ( array( 'entries', 'event_tags', 'tags' ) as $key ) {
			if ( ! empty( $response[ $key ] ) && is_array( $response[ $key ] ) ) {
				$entries = $response[ $key ];
				break;
			}
		}
		if ( empty( $entries ) && isset( $response[0] ) ) {
			$entries = $response;
		}

		$tags = array();
		foreach ( $entries as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$id = (string) ( $entry['api_id'] ?? ( $entry['id'] ?? '' ) );
			if ( '' !== $id ) {
				$tags[] = array(
					'id'   => $id,
					'name' => (string) ( $entry['name'] ?? $id ),
				);
			}
		}
		return $tags;
	}

	/**
	 * Cache/sync section intro.
	 *
	 * @return void
	 */
	public function sync_intro() {
		echo '<p>' . esc_html__( 'Events are cached and refreshed automatically every 15 minutes. Use the webhook for instant updates, or clear the cache manually.', 'luma-viewer' ) . '</p>';
	}

	/**
	 * Webhook URL field (read-only).
	 *
	 * @return void
	 */
	public function field_webhook() {
		$secret = (string) Settings::get( 'webhook_secret' );
		$url    = '' !== $secret ? add_query_arg( 'token', rawurlencode( $secret ), rest_url( 'lumaviewer/v1/webhook' ) ) : '';
		printf(
			'<input type="text" class="large-text code" readonly value="%s" />',
			esc_attr( $url )
		);
		echo '<p class="description">' . esc_html__( 'Add this as a webhook in Luma (Event Created / Updated / Canceled) to refresh the cache instantly. Treat it as a secret.', 'luma-viewer' ) . '</p>';
	}

	/**
	 * Clear-cache button.
	 *
	 * @return void
	 */
	public function field_clear_cache() {
		$url = wp_nonce_url( admin_url( 'admin-post.php?action=luma_viewer_clear_cache' ), 'luma_viewer_clear_cache' );
		printf( '<a href="%s" class="button">%s</a>', esc_url( $url ), esc_html__( 'Clear cached events', 'luma-viewer' ) );
	}

	/**
	 * Handle the clear-cache action.
	 *
	 * @return void
	 */
	public function handle_clear_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'luma-viewer' ) );
		}
		check_admin_referer( 'luma_viewer_clear_cache' );

		$this->cache->flush();

		wp_safe_redirect( add_query_arg( 'luma_viewer_cache', 'cleared', admin_url( 'options-general.php?page=' . self::MENU_SLUG ) ) );
		exit;
	}

	/**
	 * Render the "Test connection" button + inline script.
	 *
	 * @return void
	 */
	private function render_test_button() {
		$nonce = wp_create_nonce( self::TEST_NONCE );
		?>
		<p>
			<button type="button" class="button" id="luma-viewer-test-connection"><?php esc_html_e( 'Test connection', 'luma-viewer' ); ?></button>
			<span id="luma-viewer-test-result" style="margin-left:8px;"></span>
		</p>
		<script>
		( function () {
			var btn = document.getElementById( 'luma-viewer-test-connection' );
			if ( ! btn ) { return; }
			btn.addEventListener( 'click', function () {
				var out = document.getElementById( 'luma-viewer-test-result' );
				out.textContent = <?php echo wp_json_encode( __( 'Testing…', 'luma-viewer' ) ); ?>;
				out.style.color = '';
				var body = new URLSearchParams();
				body.append( 'action', 'luma_viewer_test_connection' );
				body.append( 'nonce', <?php echo wp_json_encode( $nonce ); ?> );
				fetch( <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>, {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
					body: body.toString()
				} )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					out.textContent = ( res && res.data && res.data.message ) ? res.data.message : '';
					out.style.color = ( res && res.success ) ? '#1a7f37' : '#b32d2e';
				} )
				.catch( function () {
					out.textContent = <?php echo wp_json_encode( __( 'Request failed.', 'luma-viewer' ) ); ?>;
					out.style.color = '#b32d2e';
				} );
			} );
		} )();
		</script>
		<?php
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Luma Viewer', 'luma-viewer' ); ?></h1>
			<?php
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flag set by a nonce-verified redirect.
			if ( isset( $_GET['luma_viewer_cache'] ) && 'cleared' === sanitize_key( wp_unslash( $_GET['luma_viewer_cache'] ) ) ) :
				?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Cache cleared.', 'luma-viewer' ); ?></p></div>
			<?php endif; ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::MENU_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * AJAX: test the API connection using the saved key.
	 *
	 * @return void
	 */
	public function ajax_test_connection() {
		check_ajax_referer( self::TEST_NONCE, 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'luma-viewer' ) ), 403 );
		}

		if ( ! $this->endpoints->client()->has_key() ) {
			wp_send_json_error( array( 'message' => __( 'Save an API key first, then test.', 'luma-viewer' ) ) );
		}

		$result = $this->endpoints->get_calendar();
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$name = '';
		if ( isset( $result['name'] ) ) {
			$name = (string) $result['name'];
		} elseif ( isset( $result['calendar']['name'] ) ) {
			$name = (string) $result['calendar']['name'];
		}

		wp_send_json_success(
			array(
				'message' => $name
					/* translators: %s: calendar name. */
					? sprintf( __( 'Connected to “%s”.', 'luma-viewer' ), $name )
					: __( 'Connected successfully.', 'luma-viewer' ),
			)
		);
	}
}
