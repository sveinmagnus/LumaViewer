<?php
/**
 * Single event detail (basic; full route + JSON-LD arrives in P3).
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event|null  $event     Event.
 * @var \WP_Error|null                $error     API error, if any.
 * @var \LumaViewer\View\Formatter    $formatter Date formatter.
 */

defined( 'ABSPATH' ) || exit;

if ( $error ) {
	echo '<p class="luma-viewer__error">' . esc_html__( 'This event is temporarily unavailable.', 'luma-viewer' ) . '</p>';
	if ( current_user_can( 'manage_options' ) ) {
		echo '<p class="luma-viewer__error-detail">' . esc_html( $error->get_error_message() ) . '</p>';
	}
	return;
}

if ( ! $event || '' === $event->id() ) {
	echo '<p class="luma-viewer__empty">' . esc_html__( 'Event not found.', 'luma-viewer' ) . '</p>';
	return;
}

$location = $event->location();
?>
<article class="luma-viewer__single-event">
	<?php if ( '' !== $event->cover_url() ) : ?>
		<img class="luma-viewer__single-cover" src="<?php echo esc_url( $event->cover_url() ); ?>" alt="" />
	<?php endif; ?>

	<h2 class="luma-viewer__single-title"><?php echo esc_html( $event->name() ); ?></h2>

	<?php if ( $event->has_start() ) : ?>
		<p class="luma-viewer__single-when">
			<time datetime="<?php echo esc_attr( $event->start()->format( 'c' ) ); ?>">
				<?php echo esc_html( $formatter->range( $event ) ); ?>
			</time>
		</p>
	<?php endif; ?>

	<?php if ( $location->is_online() ) : ?>
		<p class="luma-viewer__single-where"><?php esc_html_e( 'Online', 'luma-viewer' ); ?></p>
	<?php elseif ( '' !== $location->label() ) : ?>
		<p class="luma-viewer__single-where"><?php echo esc_html( '' !== $location->address() ? $location->address() : $location->label() ); ?></p>
	<?php endif; ?>

	<?php if ( '' !== $event->description() ) : ?>
		<div class="luma-viewer__single-desc">
			<?php echo wp_kses_post( wpautop( $event->description() ) ); ?>
		</div>
	<?php endif; ?>

	<?php if ( '' !== $event->luma_url() ) : ?>
		<p class="luma-viewer__single-cta">
			<a class="luma-viewer__button luma-viewer__button--primary" href="<?php echo esc_url( $event->luma_url() ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'Register on Luma', 'luma-viewer' ); ?>
			</a>
		</p>
	<?php endif; ?>
</article>
