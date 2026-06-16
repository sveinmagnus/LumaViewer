<?php
/**
 * Event card partial.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event     $event         Event to render.
 * @var \LumaViewer\View\Formatter  $formatter     Date formatter.
 * @var bool                        $teaser        Render as a gated teaser (no Luma link).
 * @var string                      $gate_cta_text Teaser call-to-action label.
 * @var string                      $gate_cta_url  Teaser call-to-action URL.
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $event ) ) {
	return;
}

$teaser   = ! empty( $teaser );
$location = $event->location();
?>
<article class="luma-viewer__card<?php echo $teaser ? ' luma-viewer__card--teaser' : ''; ?>">
	<?php if ( '' !== $event->cover_url() ) : ?>
		<?php if ( $teaser ) : ?>
			<span class="luma-viewer__card-cover">
				<img src="<?php echo esc_url( $event->cover_url() ); ?>" alt="" loading="lazy" />
			</span>
		<?php else : ?>
			<a class="luma-viewer__card-cover" href="<?php echo esc_url( $event->luma_url() ); ?>" target="_blank" rel="noopener noreferrer">
				<img src="<?php echo esc_url( $event->cover_url() ); ?>" alt="" loading="lazy" />
			</a>
		<?php endif; ?>
	<?php endif; ?>

	<div class="luma-viewer__card-body">
		<?php if ( $event->has_start() ) : ?>
			<p class="luma-viewer__card-when">
				<time datetime="<?php echo esc_attr( $event->start()->format( 'c' ) ); ?>">
					<?php echo esc_html( $formatter->range( $event ) ); ?>
				</time>
			</p>
		<?php endif; ?>

		<h3 class="luma-viewer__card-title">
			<?php if ( $teaser ) : ?>
				<?php echo esc_html( $event->name() ); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( $event->luma_url() ); ?>" target="_blank" rel="noopener noreferrer">
					<?php echo esc_html( $event->name() ); ?>
				</a>
			<?php endif; ?>
		</h3>

		<?php if ( $location->is_online() ) : ?>
			<p class="luma-viewer__card-where luma-viewer__card-where--online"><?php esc_html_e( 'Online', 'luma-viewer' ); ?></p>
		<?php elseif ( '' !== $location->label() ) : ?>
			<p class="luma-viewer__card-where"><?php echo esc_html( $location->label() ); ?></p>
		<?php endif; ?>

		<?php if ( ! empty( $event->tags() ) ) : ?>
			<ul class="luma-viewer__tags">
				<?php foreach ( $event->tags() as $tag ) : ?>
					<?php
					if ( '' === $tag['name'] ) {
						continue; }
					?>
					<li class="luma-viewer__tag"><?php echo esc_html( $tag['name'] ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php if ( $teaser ) : ?>
			<p class="luma-viewer__card-cta">
				<a class="luma-viewer__button luma-viewer__button--gate" href="<?php echo esc_url( isset( $gate_cta_url ) ? $gate_cta_url : '' ); ?>">
					<?php echo esc_html( ! empty( $gate_cta_text ) ? $gate_cta_text : __( 'Members only', 'luma-viewer' ) ); ?>
				</a>
			</p>
		<?php elseif ( '' !== $event->luma_url() ) : ?>
			<p class="luma-viewer__card-cta">
				<a class="luma-viewer__button" href="<?php echo esc_url( $event->luma_url() ); ?>" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'View on Luma', 'luma-viewer' ); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
</article>
