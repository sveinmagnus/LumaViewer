<?php
/**
 * Settings screen + connection test.
 *
 * @package LumaViewer
 */

namespace LumaViewer\Admin;

use LumaViewer\Api\Endpoints;
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
	 * Constructor.
	 *
	 * @param Endpoints $endpoints API endpoints.
	 */
	public function __construct( Endpoints $endpoints ) {
		$this->endpoints = $endpoints;
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
