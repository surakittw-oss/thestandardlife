<?php
/**
 * Reels strip — vertical YouTube Shorts cards, scrolled sideways.
 *
 * Cards are thumbnails, not embedded players. Six real YouTube iframes would
 * pull in megabytes of third-party JavaScript before the reader has asked for
 * any of it; the player is built only when a card is clicked (see theme.js).
 *
 * @package thestandard-life
 */

$tsl_reels = tsl_reels_items();
if ( empty( $tsl_reels ) ) {
	return;
}
$tsl_reels_more = tsl_reels_more_link();
?>
<section class="section reels-section">
	<div class="section-head">
		<div>
			<div class="kicker" style="margin-bottom:16px;"><?php echo esc_html( tsl_opt( 'tsl_reels_kicker' ) ); ?></div>
			<h2 class="title"><?php echo esc_html( tsl_opt( 'tsl_reels_title' ) ); ?></h2>
		</div>
		<?php if ( $tsl_reels_more ) : ?>
			<a class="more" href="<?php echo esc_url( $tsl_reels_more ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'Watch all →', 'thestandard-life' ); ?></a>
		<?php endif; ?>
	</div>

	<div class="reels-rail">
		<ul class="reels">
			<?php foreach ( $tsl_reels as $tsl_reel ) : ?>
				<li>
					<button type="button" class="reel"
						data-video="<?php echo esc_attr( $tsl_reel['id'] ); ?>"
						aria-label="<?php echo esc_attr( $tsl_reel['title'] ? sprintf( __( 'เล่นคลิป: %s', 'thestandard-life' ), $tsl_reel['title'] ) : __( 'เล่นคลิป', 'thestandard-life' ) ); ?>">
						<span class="reel-frame">
							<?php
							// maxres is the sharp one but YouTube does not generate it for
							// every video; theme.js swaps in hqdefault when it 404s.
							$tsl_reel_thumb = 'https://i.ytimg.com/vi/' . rawurlencode( $tsl_reel['id'] ) . '/';
							?>
							<img src="<?php echo esc_url( $tsl_reel_thumb . 'maxresdefault.jpg' ); ?>"
								data-fallback="<?php echo esc_url( $tsl_reel_thumb . 'hqdefault.jpg' ); ?>"
								alt="" loading="lazy" decoding="async">
							<span class="reel-play" aria-hidden="true">
								<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 6.5v11a1 1 0 0 0 1.53.85l8.5-5.5a1 1 0 0 0 0-1.7l-8.5-5.5A1 1 0 0 0 9 6.5"/></svg>
							</span>
						</span>
						<?php if ( $tsl_reel['title'] ) : ?>
							<span class="reel-title"><?php echo esc_html( $tsl_reel['title'] ); ?></span>
						<?php endif; ?>
					</button>
				</li>
			<?php endforeach; ?>
		</ul>

		<button type="button" class="reels-arrow reels-prev" aria-label="<?php esc_attr_e( 'เลื่อนซ้าย', 'thestandard-life' ); ?>" hidden>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5 8 12l7 7"/></svg>
		</button>
		<button type="button" class="reels-arrow reels-next" aria-label="<?php esc_attr_e( 'เลื่อนขวา', 'thestandard-life' ); ?>" hidden>
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="m9 5 7 7-7 7"/></svg>
		</button>
	</div>
</section>
