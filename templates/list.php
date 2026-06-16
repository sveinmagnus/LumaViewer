<?php
/**
 * List view (default): upcoming events grouped by day.
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

// Group events by display day.
$groups = array();
foreach ( $events as $event ) {
	$key              = $event->has_start() ? $formatter->day_key( $event ) : 'tbd';
	$groups[ $key ][] = $event;
}
?>
<div class="luma-viewer__list">
	<?php foreach ( $groups as $items ) : ?>
		<section class="luma-viewer__group">
			<?php if ( $items[0]->has_start() ) : ?>
				<h2 class="luma-viewer__group-date"><?php echo esc_html( $formatter->day_label( $items[0] ) ); ?></h2>
			<?php endif; ?>
			<div class="luma-viewer__events">
				<?php
				foreach ( $items as $event ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- card markup is escaped inside the partial.
					echo $render_card( $event, ! empty( $teaser_ids[ $event->id() ] ) );
				}
				?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
