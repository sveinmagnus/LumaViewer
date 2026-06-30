<?php
/**
 * Map view: upcoming events with a venue plotted on an OpenStreetMap map.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event[]  $events Events to plot.
 * @var \WP_Error|null             $error  API error, if any.
 */

defined( 'ABSPATH' ) || exit;

if ( $error ) {
	echo '<p class="luma-viewer__error">' . esc_html__( 'Events are temporarily unavailable.', 'luma-viewer' ) . '</p>';
	if ( current_user_can( 'manage_options' ) ) {
		echo '<p class="luma-viewer__error-detail">' . esc_html( $error->get_error_message() ) . '</p>';
	}
	return;
}

$markers = array();
foreach ( $events as $event ) {
	$location = $event->location();
	if ( $location->is_online() || null === $location->lat() || null === $location->lng() ) {
		continue;
	}
	$markers[] = array(
		'lat'   => $location->lat(),
		'lng'   => $location->lng(),
		'name'  => $event->name(),
		'where' => $location->label(),
		'url'   => $event->luma_url(),
	);
}

if ( empty( $markers ) ) {
	echo '<p class="luma-viewer__empty">' . esc_html__( 'No events with a location to show on a map.', 'luma-viewer' ) . '</p>';
	return;
}

$map_center  = (string) \LumaViewer\Settings::get( 'map_center' );
$map_zoom    = (int) \LumaViewer\Settings::get( 'map_zoom' );
$map_cluster = (bool) \LumaViewer\Settings::get( 'map_cluster' );
?>
<div class="luma-viewer__map" role="application" aria-label="<?php echo esc_attr__( 'Map of upcoming events', 'luma-viewer' ); ?>" data-lv-markers="<?php echo esc_attr( (string) wp_json_encode( $markers ) ); ?>" data-lv-center="<?php echo esc_attr( $map_center ); ?>" data-lv-zoom="<?php echo esc_attr( (string) $map_zoom ); ?>" data-lv-cluster="<?php echo esc_attr( $map_cluster ? '1' : '' ); ?>"></div>
<noscript><p class="luma-viewer__empty"><?php esc_html_e( 'Enable JavaScript to view the event map.', 'luma-viewer' ); ?></p></noscript>
