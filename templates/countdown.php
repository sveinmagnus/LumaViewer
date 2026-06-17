<?php
/**
 * Countdown to the next event's start.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event|null  $event     Event to count down to.
 * @var \WP_Error|null                $error     API error, if any.
 * @var \LumaViewer\View\Formatter    $formatter Date formatter.
 */

defined( 'ABSPATH' ) || exit;

if ( $error && current_user_can( 'manage_options' ) ) {
	echo '<p class="luma-viewer__error-detail">' . esc_html( $error->get_error_message() ) . '</p>';
}

if ( ! $event || '' === $event->id() || ! $event->has_start() ) {
	echo '<p class="luma-viewer__empty">' . esc_html__( 'No upcoming event.', 'luma-viewer' ) . '</p>';
	return;
}
?>
<div class="luma-viewer__cd">
	<p class="luma-viewer__cd-name">
		<?php if ( '' !== $event->luma_url() ) : ?>
			<a href="<?php echo esc_url( $event->luma_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $event->name() ); ?></a>
		<?php else : ?>
			<?php echo esc_html( $event->name() ); ?>
		<?php endif; ?>
	</p>
	<div class="luma-viewer__countdown" data-lv-start="<?php echo esc_attr( $event->start()->format( 'c' ) ); ?>" data-lv-live="<?php esc_attr_e( 'Happening now', 'luma-viewer' ); ?>">
		<time datetime="<?php echo esc_attr( $event->start()->format( 'c' ) ); ?>"><?php echo esc_html( $formatter->range( $event ) ); ?></time>
	</div>
</div>
