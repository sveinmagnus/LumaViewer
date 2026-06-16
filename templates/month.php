<?php
/**
 * Month view: a calendar grid with events placed on day cells.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event[]      $events    Events within the month.
 * @var \WP_Error|null                 $error     API error, if any.
 * @var \LumaViewer\View\Formatter     $formatter Date formatter.
 * @var \DateTimeImmutable|null        $anchor    Anchor date (first of month).
 */

defined( 'ABSPATH' ) || exit;

if ( $error ) {
	echo '<p class="luma-viewer__error">' . esc_html__( 'Events are temporarily unavailable.', 'luma-viewer' ) . '</p>';
	if ( current_user_can( 'manage_options' ) ) {
		echo '<p class="luma-viewer__error-detail">' . esc_html( $error->get_error_message() ) . '</p>';
	}
	return;
}

global $wp_locale;

$tz    = wp_timezone();
$first = ( $anchor instanceof \DateTimeImmutable ) ? $anchor : new \DateTimeImmutable( 'now', $tz );
$first = $first->setTime( 0, 0, 0 );

// Map events to their day in the site time zone.
$by_day = array();
foreach ( $events as $event ) {
	if ( $event->has_start() ) {
		$by_day[ wp_date( 'Y-m-d', $event->start()->getTimestamp(), $tz ) ][] = $event;
	}
}

$week_start    = (int) get_option( 'start_of_week', 0 );
$first_weekday = (int) $first->format( 'w' );
$lead          = ( $first_weekday - $week_start + 7 ) % 7;
$days_in_month = (int) $first->format( 't' );
$year          = (int) $first->format( 'Y' );
$month_num     = (int) $first->format( 'n' );
$today_key     = wp_date( 'Y-m-d', time(), $tz );
?>
<div class="luma-viewer__month">
	<h2 class="luma-viewer__month-title"><?php echo esc_html( wp_date( 'F Y', $first->getTimestamp(), $tz ) ); ?></h2>

	<div class="luma-viewer__grid" role="grid">
		<?php for ( $i = 0; $i < 7; $i++ ) : ?>
			<?php $dow = ( $week_start + $i ) % 7; ?>
			<div class="luma-viewer__dow" role="columnheader">
				<?php echo esc_html( $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( $dow ) ) ); ?>
			</div>
		<?php endfor; ?>

		<?php for ( $i = 0; $i < $lead; $i++ ) : ?>
			<div class="luma-viewer__cell luma-viewer__cell--empty" role="gridcell"></div>
		<?php endfor; ?>

		<?php
		for ( $day = 1; $day <= $days_in_month; $day++ ) :
			$cell_date = $first->setDate( $year, $month_num, $day );
			$key       = $cell_date->format( 'Y-m-d' );
			$classes   = 'luma-viewer__cell' . ( $key === $today_key ? ' luma-viewer__cell--today' : '' );
			?>
			<div class="<?php echo esc_attr( $classes ); ?>" role="gridcell">
				<div class="luma-viewer__cell-day"><?php echo esc_html( (string) $day ); ?></div>
				<?php if ( ! empty( $by_day[ $key ] ) ) : ?>
					<ul class="luma-viewer__cell-events">
						<?php foreach ( $by_day[ $key ] as $event ) : ?>
							<li>
								<a href="<?php echo esc_url( $event->luma_url() ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $event->name() ); ?>">
									<?php echo esc_html( $event->name() ); ?>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>
			</div>
		<?php endfor; ?>
	</div>
</div>
