<?php
/**
 * Single event detail.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event|null  $event         Event.
 * @var \WP_Error|null                $error         API error, if any.
 * @var \LumaViewer\View\Formatter    $formatter     Date formatter.
 * @var bool                          $teaser        Gated teaser (hide details, no Luma link).
 * @var bool                          $blocked       Gated and hidden entirely.
 * @var string                        $gate_cta_text Gate call-to-action label.
 * @var string                        $gate_cta_url  Gate call-to-action URL.
 */

defined( 'ABSPATH' ) || exit;

$teaser  = ! empty( $teaser );
$blocked = ! empty( $blocked );

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

$cta_text = ! empty( $gate_cta_text ) ? $gate_cta_text : __( 'This event is for members. Join or log in to see the details.', 'luma-viewer' );
$cta_url  = isset( $gate_cta_url ) ? $gate_cta_url : '';

// Fully gated: reveal nothing but a join/login prompt.
if ( $blocked ) {
	echo '<div class="luma-viewer__gate">';
	echo '<p class="luma-viewer__gate-text">' . esc_html( $cta_text ) . '</p>';
	if ( '' !== $cta_url ) {
		echo '<p><a class="luma-viewer__button luma-viewer__button--primary" href="' . esc_url( $cta_url ) . '">' . esc_html__( 'Join or log in', 'luma-viewer' ) . '</a></p>';
	}
	echo '</div>';
	return;
}

$location = $event->location();
?>
<article class="luma-viewer__single-event<?php echo $teaser ? ' luma-viewer__single-event--teaser' : ''; ?>">
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

	<?php if ( $event->is_cancelled() || $event->is_sold_out() || $event->is_free() || '' !== $event->price_label() ) : ?>
		<p class="luma-viewer__card-badges">
			<?php if ( $event->is_cancelled() ) : ?>
				<span class="luma-viewer__badge luma-viewer__badge--cancelled"><?php esc_html_e( 'Cancelled', 'luma-viewer' ); ?></span>
			<?php endif; ?>
			<?php if ( $event->is_sold_out() ) : ?>
				<span class="luma-viewer__badge luma-viewer__badge--soldout"><?php esc_html_e( 'Sold out', 'luma-viewer' ); ?></span>
			<?php endif; ?>
			<?php if ( $event->is_free() || '' !== $event->price_label() ) : ?>
				<span class="luma-viewer__badge luma-viewer__badge--price"><?php echo esc_html( $event->is_free() ? __( 'Free', 'luma-viewer' ) : $event->price_label() ); ?></span>
			<?php endif; ?>
		</p>
	<?php endif; ?>

	<?php if ( $location->is_online() ) : ?>
		<p class="luma-viewer__single-where"><?php esc_html_e( 'Online', 'luma-viewer' ); ?></p>
	<?php elseif ( '' !== $location->label() ) : ?>
		<p class="luma-viewer__single-where"><?php echo esc_html( '' !== $location->address() ? $location->address() : $location->label() ); ?></p>
	<?php endif; ?>

	<?php if ( ! $teaser && ! empty( $event->hosts() ) ) : ?>
		<p class="luma-viewer__single-hosts">
			<?php
			/* translators: %s: comma-separated host names. */
			echo esc_html( sprintf( __( 'Hosted by %s', 'luma-viewer' ), implode( ', ', wp_list_pluck( $event->hosts(), 'name' ) ) ) );
			?>
		</p>
	<?php endif; ?>

	<?php if ( $teaser ) : ?>
		<div class="luma-viewer__gate">
			<p class="luma-viewer__gate-text"><?php echo esc_html( $cta_text ); ?></p>
			<?php if ( '' !== $cta_url ) : ?>
				<p>
					<a class="luma-viewer__button luma-viewer__button--primary" href="<?php echo esc_url( $cta_url ); ?>">
						<?php esc_html_e( 'Join or log in', 'luma-viewer' ); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
	<?php else : ?>
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
	<?php endif; ?>
</article>
