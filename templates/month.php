<?php
/**
 * Month view: a semantic calendar table with events placed on day cells.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event[]      $events     Events within the month.
 * @var \WP_Error|null                 $error      API error, if any.
 * @var \LumaViewer\View\Formatter     $formatter  Date formatter.
 * @var array<string,bool>             $teaser_ids Gated (teaser) event ids.
 * @var \DateTimeImmutable|null        $anchor     Anchor date (first of month).
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
$rows          = (int) ceil( ( $lead + $days_in_month ) / 7 );
$cell          = 0;
?>
<div class="luma-viewer__month">
	<h2 class="luma-viewer__month-title"><?php echo esc_html( wp_date( 'F Y', $first->getTimestamp(), $tz ) ); ?></h2>

	<table class="luma-viewer__month-table">
		<thead>
			<tr>
				<?php for ( $i = 0; $i < 7; $i++ ) : ?>
					<?php $dow = ( $week_start + $i ) % 7; ?>
					<th scope="col" class="luma-viewer__dow" abbr="<?php echo esc_attr( $wp_locale->get_weekday( $dow ) ); ?>">
						<?php echo esc_html( $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( $dow ) ) ); ?>
					</th>
				<?php endfor; ?>
			</tr>
		</thead>
		<tbody>
			<?php for ( $row = 0; $row < $rows; $row++ ) : ?>
				<tr>
					<?php
					for ( $col = 0; $col < 7; $col++ ) :
						$day_num = ( $cell - $lead ) + 1;
						if ( $cell < $lead || $day_num > $days_in_month ) :
							?>
							<td class="luma-viewer__cell luma-viewer__cell--empty"></td>
							<?php
						else :
							$cell_date = $first->setDate( $year, $month_num, $day_num );
							$key       = $cell_date->format( 'Y-m-d' );
							$classes   = 'luma-viewer__cell' . ( $key === $today_key ? ' luma-viewer__cell--today' : '' );
							?>
							<td class="<?php echo esc_attr( $classes ); ?>">
								<span class="luma-viewer__cell-day"><?php echo esc_html( (string) $day_num ); ?></span>
								<?php if ( ! empty( $by_day[ $key ] ) ) : ?>
									<ul class="luma-viewer__cell-events">
										<?php foreach ( $by_day[ $key ] as $event ) : ?>
											<li>
												<?php if ( ! empty( $teaser_ids[ $event->id() ] ) ) : ?>
													<span class="luma-viewer__cell-event luma-viewer__cell-event--teaser" title="<?php echo esc_attr__( 'Members only', 'luma-viewer' ); ?>"><?php echo esc_html( $event->name() ); ?></span>
												<?php else : ?>
													<a href="<?php echo esc_url( $event->luma_url() ); ?>" target="_blank" rel="noopener noreferrer" title="<?php echo esc_attr( $event->name() ); ?>"><?php echo esc_html( $event->name() ); ?></a>
												<?php endif; ?>
											</li>
										<?php endforeach; ?>
									</ul>
								<?php endif; ?>
							</td>
							<?php
						endif;
						++$cell;
					endfor;
					?>
				</tr>
			<?php endfor; ?>
		</tbody>
	</table>
</div>
