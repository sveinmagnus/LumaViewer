<?php
/**
 * Featured event hero.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event|null  $event     Event to feature.
 * @var \WP_Error|null                $error     API error, if any.
 * @var \LumaViewer\View\Formatter    $formatter Date formatter.
 */

defined( 'ABSPATH' ) || exit;

if ( $error && current_user_can( 'manage_options' ) ) {
	echo '<p class="luma-viewer__error-detail">' . esc_html( $error->get_error_message() ) . '</p>';
}

if ( ! $event || '' === $event->id() ) {
	echo '<p class="luma-viewer__empty">' . esc_html__( 'No upcoming event to feature.', 'luma-viewer' ) . '</p>';
	return;
}

$location = $event->location();
?>
<article class="luma-viewer__hero">
	<?php if ( '' !== $event->cover_url() ) : ?>
		<img class="luma-viewer__hero-bg" src="<?php echo esc_url( $event->cover_url() ); ?>" alt="" />
	<?php endif; ?>
	<div class="luma-viewer__hero-inner">
		<?php if ( $event->has_start() ) : ?>
			<p class="luma-viewer__hero-when">
				<?php echo esc_html( $formatter->range( $event ) ); ?>
				<?php $relative = $formatter->relative( $event ); ?>
				<?php if ( '' !== $relative ) : ?>
					<span class="luma-viewer__hero-rel"><?php echo esc_html( $relative ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<h2 class="luma-viewer__hero-title"><?php echo esc_html( $event->name() ); ?></h2>

		<?php if ( $location->is_online() ) : ?>
			<p class="luma-viewer__hero-where"><?php esc_html_e( 'Online', 'luma-viewer' ); ?></p>
		<?php elseif ( '' !== $location->label() ) : ?>
			<p class="luma-viewer__hero-where"><?php echo esc_html( $location->label() ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $event->luma_url() ) : ?>
			<p>
				<a class="luma-viewer__button luma-viewer__button--primary" href="<?php echo esc_url( $event->luma_url() ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'View on Luma', 'luma-viewer' ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</article>
