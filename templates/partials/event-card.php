<?php
/**
 * Event card partial.
 *
 * @package LumaViewer
 *
 * @var \LumaViewer\Model\Event     $event         Event to render.
 * @var \LumaViewer\View\Formatter  $formatter     Date formatter.
 * @var bool                        $teaser        Render as a gated teaser (no Luma link).
 * @var string                      $layout        cards | compact | minimal.
 * @var array<string,bool|int>      $display       Resolved element visibility + excerpt length.
 * @var string                      $gate_cta_text Teaser call-to-action label.
 * @var string                      $gate_cta_url  Teaser call-to-action URL.
 */

defined( 'ABSPATH' ) || exit;

if ( ! isset( $event ) ) {
	return;
}

$teaser  = ! empty( $teaser );
$layout  = ( ! empty( $layout ) && in_array( $layout, array( 'cards', 'compact', 'minimal' ), true ) ) ? $layout : 'cards';
$display = isset( $display ) && is_array( $display ) ? $display : array();
$show    = static function ( $key ) use ( $display ) {
	return ! empty( $display[ $key ] );
};
$words   = isset( $display['excerpt_words'] ) ? (int) $display['excerpt_words'] : 25;

$location   = $event->location();
$show_price = $show( 'price' ) && ( $event->is_free() || '' !== $event->price_label() );
$has_badges = $event->is_cancelled() || $event->is_sold_out() || $show_price;
$has_cta    = ( 'minimal' !== $layout );
$link_attrs = $formatter->link_attrs();
$classes    = 'luma-viewer__card luma-viewer__card--' . $layout . ( $teaser ? ' luma-viewer__card--teaser' : '' );
?>
<?php
// Tags are stored as a |-delimited, lowercased list so a chip can match a whole
// tag name (which may itself contain spaces) rather than a loose substring.
$lv_tag_names = array_filter( wp_list_pluck( $event->tags(), 'name' ) );
$lv_tags_attr = empty( $lv_tag_names ) ? '' : '|' . $formatter->lc( implode( '|', $lv_tag_names ) ) . '|';
?>
<article class="<?php echo esc_attr( $classes ); ?>" data-lv-id="<?php echo esc_attr( $event->id() ); ?>" data-lv-title="<?php echo esc_attr( $formatter->lc( $event->name() ) ); ?>" data-lv-tags="<?php echo esc_attr( $lv_tags_attr ); ?>">
	<?php if ( $show( 'cover' ) && '' !== $event->cover_url() ) : ?>
		<?php if ( $teaser ) : ?>
			<span class="luma-viewer__card-cover">
				<img src="<?php echo esc_url( $event->cover_url() ); ?>" alt="" loading="lazy" />
			</span>
		<?php else : ?>
			<a class="luma-viewer__card-cover" href="<?php echo esc_url( $event->luma_url() ); ?>"<?php echo $link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed, safe attribute string. ?>>
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
				<?php $relative = $show( 'relative' ) ? $formatter->relative( $event ) : ''; ?>
				<?php if ( '' !== $relative ) : ?>
					<span class="luma-viewer__card-rel"><?php echo esc_html( $relative ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<h3 class="luma-viewer__card-title">
			<?php if ( $teaser ) : ?>
				<?php echo esc_html( $event->name() ); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( $event->luma_url() ); ?>"<?php echo $link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed, safe attribute string. ?>>
					<?php echo esc_html( $event->name() ); ?>
				</a>
			<?php endif; ?>
		</h3>

		<?php if ( $has_badges ) : ?>
			<p class="luma-viewer__card-badges">
				<?php if ( $event->is_cancelled() ) : ?>
					<span class="luma-viewer__badge luma-viewer__badge--cancelled"><?php esc_html_e( 'Cancelled', 'luma-viewer' ); ?></span>
				<?php endif; ?>
				<?php if ( $event->is_sold_out() ) : ?>
					<span class="luma-viewer__badge luma-viewer__badge--soldout"><?php esc_html_e( 'Sold out', 'luma-viewer' ); ?></span>
				<?php endif; ?>
				<?php if ( $show_price ) : ?>
					<span class="luma-viewer__badge luma-viewer__badge--price"><?php echo esc_html( $event->is_free() ? __( 'Free', 'luma-viewer' ) : $event->price_label() ); ?></span>
				<?php endif; ?>
			</p>
		<?php endif; ?>

		<?php if ( $show( 'location' ) ) : ?>
			<?php if ( $location->is_online() ) : ?>
				<p class="luma-viewer__card-where luma-viewer__card-where--online"><?php esc_html_e( 'Online', 'luma-viewer' ); ?></p>
			<?php elseif ( '' !== $location->label() ) : ?>
				<p class="luma-viewer__card-where"><?php echo esc_html( $location->label() ); ?></p>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( $show( 'host' ) && ! empty( $event->hosts() ) ) : ?>
			<p class="luma-viewer__card-hosts">
				<?php
				/* translators: %s: comma-separated host names. */
				echo esc_html( sprintf( __( 'Hosted by %s', 'luma-viewer' ), implode( ', ', wp_list_pluck( $event->hosts(), 'name' ) ) ) );
				?>
			</p>
		<?php endif; ?>

		<?php if ( $show( 'tags' ) && ! empty( $event->tags() ) ) : ?>
			<?php $tag_colors = isset( $tag_colors ) && is_array( $tag_colors ) ? $tag_colors : array(); ?>
			<ul class="luma-viewer__tags">
				<?php foreach ( $event->tags() as $tag ) : ?>
					<?php
					if ( '' === $tag['name'] ) {
						continue; }
					$tag_color = isset( $tag_colors[ $tag['id'] ] ) ? $tag_colors[ $tag['id'] ] : '';
					$tag_style = '' !== $tag_color ? ' style="--lv-tag:' . esc_attr( $tag_color ) . '"' : '';
					?>
					<li class="luma-viewer__tag<?php echo '' !== $tag_color ? ' luma-viewer__tag--colored' : ''; ?>"<?php echo $tag_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- value escaped above. ?>><?php echo esc_html( $tag['name'] ); ?></li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>

		<?php
		if ( $show( 'excerpt' ) && ! $teaser ) :
			$excerpt = $event->excerpt( $words );
			if ( '' !== $excerpt ) :
				?>
				<p class="luma-viewer__card-excerpt"><?php echo esc_html( $excerpt ); ?></p>
				<?php
			endif;
		endif;
		?>

		<?php if ( $has_cta ) : ?>
			<?php if ( $teaser ) : ?>
				<p class="luma-viewer__card-cta">
					<a class="luma-viewer__button luma-viewer__button--gate" href="<?php echo esc_url( isset( $gate_cta_url ) ? $gate_cta_url : '' ); ?>">
						<?php echo esc_html( ! empty( $gate_cta_text ) ? $gate_cta_text : __( 'Members only', 'luma-viewer' ) ); ?>
					</a>
				</p>
			<?php elseif ( '' !== $event->luma_url() ) : ?>
				<p class="luma-viewer__card-cta">
					<a class="luma-viewer__button" href="<?php echo esc_url( $event->luma_url() ); ?>"<?php echo $link_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed, safe attribute string. ?>>
						<?php esc_html_e( 'View on Luma', 'luma-viewer' ); ?>
					</a>
				</p>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</article>
