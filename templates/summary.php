<?php
/**
 * Summary view: a compact, date-grouped agenda (time + title only).
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event[]   $events    Events to render.
 * @var \WP_Error|null              $error     API error, if any.
 * @var \LumaViewer\View\Formatter  $formatter Date formatter.
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

$groups = array();
foreach ( $events as $event ) {
	$key              = $event->has_start() ? $formatter->day_key( $event ) : 'tbd';
	$groups[ $key ][] = $event;
}
?>
<ul class="luma-viewer__summary">
	<?php foreach ( $groups as $items ) : ?>
		<li class="luma-viewer__summary-group">
			<?php if ( $items[0]->has_start() ) : ?>
				<span class="luma-viewer__summary-date"><?php echo esc_html( $formatter->day_label( $items[0] ) ); ?></span>
			<?php endif; ?>
			<ul class="luma-viewer__summary-items">
				<?php foreach ( $items as $event ) : ?>
					<li class="luma-viewer__summary-item">
						<?php if ( $event->has_start() ) : ?>
							<time class="luma-viewer__summary-time" datetime="<?php echo esc_attr( $event->start()->format( 'c' ) ); ?>"><?php echo esc_html( $formatter->time( $event ) ); ?></time>
						<?php endif; ?>
						<a href="<?php echo esc_url( $event->luma_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $event->name() ); ?></a>
					</li>
				<?php endforeach; ?>
			</ul>
		</li>
	<?php endforeach; ?>
</ul>
