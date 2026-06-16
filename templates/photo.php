<?php
/**
 * Photo view: a cover-image-forward grid of upcoming events.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event[]   $events      Events to render.
 * @var \WP_Error|null              $error       API error, if any.
 * @var \LumaViewer\View\Formatter  $formatter   Date formatter.
 * @var callable                    $render_card Renders an event card to HTML.
 */

defined( 'ABSPATH' ) || exit;

if ( $error ) {
	echo '<p class="luma-viewer__error">' . esc_html__( 'Events are temporarily unavailable.', 'luma-viewer' ) . '</p>';
	if ( current_user_can( 'manage_options' ) ) {
		echo '<p class="luma-viewer__error-detail">' . esc_html( $error->get_error_message() ) . '</p>';
	}
	return;
}

if ( empty( $events ) ) {
	echo '<p class="luma-viewer__empty">' . esc_html__( 'No upcoming events.', 'luma-viewer' ) . '</p>';
	return;
}
?>
<div class="luma-viewer__photo-grid">
	<?php
	foreach ( $events as $event ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- card markup is escaped inside the partial.
		echo $render_card( $event, ! empty( $teaser_ids[ $event->id() ] ) );
	}
	?>
</div>
