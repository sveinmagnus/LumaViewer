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

$tag_colors = isset( $tag_colors ) && is_array( $tag_colors ) ? $tag_colors : array();

/**
 * The first configured color among an event's tags, or '' for none.
 *
 * @param \LumaViewer\Model\Event $event  Event.
 * @param array<string,string>    $colors Tag color map.
 * @return string
 */
$event_color = static function ( $event, $colors ) {
	foreach ( $event->tags() as $tag ) {
		if ( isset( $colors[ $tag['id'] ] ) ) {
			return $colors[ $tag['id'] ];
		}
	}
	return '';
};

// Map events to their day in each event's display time zone, so the cell they
// land in matches the time shown on the event.
$by_day = array();
foreach ( $events as $event ) {
	if ( $event->has_start() ) {
		$by_day[ wp_date( 'Y-m-d', $event->start()->getTimestamp(), $formatter->display_tz( $event ) ) ][] = $event;
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
										<?php
										foreach ( $by_day[ $key ] as $event ) :
											$color = $event_color( $event, $tag_colors );
											$style = '' !== $color ? ' style="--lv-tag:' . esc_attr( $color ) . '"' : '';
											$dot   = '' !== $color ? ' luma-viewer__cell-event--colored' : '';
											?>
											<li>
												<?php if ( ! empty( $teaser_ids[ $event->id() ] ) ) : ?>
													<span class="luma-viewer__cell-event luma-viewer__cell-event--teaser<?php echo esc_attr( $dot ); ?>" title="<?php echo esc_attr__( 'Members only', 'luma-viewer' ); ?>"<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- value escaped above. ?>><?php echo esc_html( $event->name() ); ?></span>
												<?php else : ?>
													<a class="luma-viewer__cell-event<?php echo esc_attr( $dot ); ?>" href="<?php echo esc_url( $event->luma_url() ); ?>" data-lv-id="<?php echo esc_attr( $event->id() ); ?>"<?php echo $formatter->link_attrs(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed, safe attribute string. ?> title="<?php echo esc_attr( $event->name() ); ?>"<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- value escaped above. ?>><?php echo esc_html( $event->name() ); ?></a>
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
