<?php
/**
 * List view (default): upcoming events, optionally grouped.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event[]   $events      Events to render.
 * @var \WP_Error|null              $error       API error, if any.
 * @var \LumaViewer\View\Formatter  $formatter   Date formatter.
 * @var callable                    $render_card Renders an event card to HTML.
 * @var array<string,bool>          $teaser_ids  Gated (teaser) event ids.
 * @var string                      $group_by    day | month | none.
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

$group_by = ( ! empty( $group_by ) && in_array( $group_by, array( 'day', 'month', 'none' ), true ) ) ? $group_by : 'day';

// Build groups as key => [ label, items ].
$groups = array();
foreach ( $events as $event ) {
	if ( 'none' === $group_by || ! $event->has_start() ) {
		$key   = '_all';
		$label = '';
	} elseif ( 'month' === $group_by ) {
		$ts    = $event->start()->getTimestamp();
		$zone  = $formatter->display_tz( $event );
		$key   = wp_date( 'Y-m', $ts, $zone );
		$label = wp_date( 'F Y', $ts, $zone );
	} else {
		$key   = $formatter->day_key( $event );
		$label = $formatter->day_label( $event );
	}

	if ( ! isset( $groups[ $key ] ) ) {
		$groups[ $key ] = array(
			'label' => $label,
			'items' => array(),
		);
	}
	$groups[ $key ]['items'][] = $event;
}
?>
<div class="luma-viewer__list luma-viewer__list--group-<?php echo esc_attr( $group_by ); ?>">
	<?php foreach ( $groups as $group ) : ?>
		<section class="luma-viewer__group">
			<?php if ( '' !== $group['label'] ) : ?>
				<h2 class="luma-viewer__group-date"><?php echo esc_html( $group['label'] ); ?></h2>
			<?php endif; ?>
			<div class="luma-viewer__events">
				<?php
				foreach ( $group['items'] as $event ) {
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- card markup is escaped inside the partial.
					echo $render_card( $event, ! empty( $teaser_ids[ $event->id() ] ) );
				}
				?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
