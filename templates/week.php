<?php
/**
 * Week view: seven day columns from the week's start.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event[]   $events      Events within the week.
 * @var \WP_Error|null              $error       API error, if any.
 * @var \LumaViewer\View\Formatter  $formatter   Date formatter.
 * @var callable                    $render_card Renders an event card to HTML.
 * @var array<string,bool>          $teaser_ids  Gated (teaser) event ids.
 * @var \DateTimeImmutable|null     $anchor      First day of the week.
 */

defined( 'ABSPATH' ) || exit;

if ( $error ) {
	echo '<p class="luma-viewer__error">' . esc_html__( 'Events are temporarily unavailable.', 'luma-viewer' ) . '</p>';
	if ( current_user_can( 'manage_options' ) ) {
		echo '<p class="luma-viewer__error-detail">' . esc_html( $error->get_error_message() ) . '</p>';
	}
	return;
}

$tz    = wp_timezone();
$start = ( $anchor instanceof \DateTimeImmutable ) ? $anchor : new \DateTimeImmutable( 'now', $tz );
$start = $start->setTime( 0, 0, 0 );

$by_day = array();
foreach ( $events as $event ) {
	if ( $event->has_start() ) {
		// File under the event's own display day so it lines up with its shown time.
		$by_day[ wp_date( 'Y-m-d', $event->start()->getTimestamp(), $formatter->display_tz( $event ) ) ][] = $event;
	}
}

$today_key   = wp_date( 'Y-m-d', time(), $tz );
$date_format = $formatter->date_format();
?>
<div class="luma-viewer__week">
	<?php
	for ( $i = 0; $i < 7; $i++ ) :
		$day_date = $start->modify( '+' . $i . ' days' );
		$key      = $day_date->format( 'Y-m-d' );
		$items    = isset( $by_day[ $key ] ) ? $by_day[ $key ] : array();
		$classes  = 'luma-viewer__week-day' . ( $key === $today_key ? ' luma-viewer__week-day--today' : '' );
		?>
		<section class="<?php echo esc_attr( $classes ); ?>">
			<h2 class="luma-viewer__week-day-title"><?php echo esc_html( wp_date( 'l, ' . $date_format, $day_date->getTimestamp(), $tz ) ); ?></h2>
			<?php if ( empty( $items ) ) : ?>
				<p class="luma-viewer__week-empty"><?php esc_html_e( 'No events.', 'luma-viewer' ); ?></p>
			<?php else : ?>
				<div class="luma-viewer__events">
					<?php
					foreach ( $items as $event ) {
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- card markup is escaped inside the partial.
						echo $render_card( $event, ! empty( $teaser_ids[ $event->id() ] ) );
					}
					?>
				</div>
			<?php endif; ?>
		</section>
	<?php endfor; ?>
</div>
