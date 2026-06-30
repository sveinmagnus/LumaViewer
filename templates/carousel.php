<?php
/**
 * Carousel view: a horizontally scrolling strip of upcoming events.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event[]   $events      Events to render.
 * @var \WP_Error|null              $error       API error, if any.
 * @var \LumaViewer\View\Formatter  $formatter   Date formatter.
 * @var callable                    $render_card Renders an event card to HTML.
 * @var array<string,bool>          $teaser_ids  Gated (teaser) event ids.
 * @var string                      $empty       "No events" message.
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
	echo '<p class="luma-viewer__empty">' . esc_html( isset( $empty ) && '' !== $empty ? $empty : __( 'No upcoming events.', 'luma-viewer' ) ) . '</p>';
	return;
}
?>
<div class="luma-viewer__carousel">
	<button type="button" class="luma-viewer__carousel-nav luma-viewer__carousel-prev" data-lv-carousel="prev" aria-label="<?php echo esc_attr__( 'Previous', 'luma-viewer' ); ?>">&#8249;</button>
	<div class="luma-viewer__carousel-track">
		<?php
		foreach ( $events as $event ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- card markup is escaped inside the partial.
			echo $render_card( $event, ! empty( $teaser_ids[ $event->id() ] ) );
		}
		?>
	</div>
	<button type="button" class="luma-viewer__carousel-nav luma-viewer__carousel-next" data-lv-carousel="next" aria-label="<?php echo esc_attr__( 'Next', 'luma-viewer' ); ?>">&#8250;</button>
</div>
