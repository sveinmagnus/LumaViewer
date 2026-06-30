<?php
/**
 * Day view: a single day's agenda.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event[]   $events      Events on the day.
 * @var \WP_Error|null              $error       API error, if any.
 * @var \LumaViewer\View\Formatter  $formatter   Date formatter.
 * @var callable                    $render_card Renders an event card to HTML.
 * @var \DateTimeImmutable|null     $anchor      The day being shown.
 */

defined( 'ABSPATH' ) || exit;

if ( $error ) {
	echo '<p class="luma-viewer__error">' . esc_html__( 'Events are temporarily unavailable.', 'luma-viewer' ) . '</p>';
	if ( current_user_can( 'manage_options' ) ) {
		echo '<p class="luma-viewer__error-detail">' . esc_html( $error->get_error_message() ) . '</p>';
	}
	return;
}

$tz  = wp_timezone();
$day = ( $anchor instanceof \DateTimeImmutable ) ? $anchor : new \DateTimeImmutable( 'now', $tz );
?>
<h2 class="luma-viewer__day-title"><?php echo esc_html( wp_date( $formatter->date_format(), $day->getTimestamp(), $tz ) ); ?></h2>
<?php if ( empty( $events ) ) : ?>
	<p class="luma-viewer__empty"><?php esc_html_e( 'No events on this day.', 'luma-viewer' ); ?></p>
<?php else : ?>
	<div class="luma-viewer__events">
		<?php
		foreach ( $events as $event ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- card markup is escaped inside the partial.
			echo $render_card( $event, ! empty( $teaser_ids[ $event->id() ] ) );
		}
		?>
	</div>
<?php endif; ?>
