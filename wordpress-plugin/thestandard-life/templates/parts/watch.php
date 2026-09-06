<?php
/**
 * Watch block — full YouTube episodes, one lead plus a stacked list.
 *
 * Deliberately not the Reels layout. A Short is decided on in a glance and its
 * thumbnail does all the work; giving fifty minutes to something is a different
 * decision, and it needs the title, the topic and the running time to be
 * legible before anyone will make it. Hence one large card carrying a summary,
 * and the rest as a list rather than another sideways row.
 *
 * Thumbnails, not players — see templates/parts/reels.php.
 *
 * @package thestandard-life
 */

$tsl_watch = tsl_yt_items( 'watch' );
if ( empty( $tsl_watch ) ) {
	return;
}

$tsl_watch_lead = array_shift( $tsl_watch );
$tsl_watch_more = tsl_yt_more_link( 'watch' );

/**
 * One thumbnail, with its running time over the corner.
 *
 * @param array $video Item from tsl_yt_items().
 */
function tsl_watch_thumb( $video ) {
	$base   = 'https://i.ytimg.com/vi/' . rawurlencode( $video['id'] ) . '/';
	$length = tsl_yt_length( isset( $video['duration'] ) ? $video['duration'] : 0 );
	?>
	<span class="watch-frame">
		<img src="<?php echo esc_url( $base . 'maxresdefault.jpg' ); ?>"
			data-fallback="<?php echo esc_url( $base . 'hqdefault.jpg' ); ?>"
			alt="" loading="lazy" decoding="async">
		<span class="watch-play" aria-hidden="true">
			<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 6.5v11a1 1 0 0 0 1.53.85l8.5-5.5a1 1 0 0 0 0-1.7l-8.5-5.5A1 1 0 0 0 9 6.5"/></svg>
		</span>
		<?php if ( $length ) : ?>
			<span class="watch-len"><?php echo esc_html( $length ); ?></span>
		<?php endif; ?>
	</span>
	<?php
}

/**
 * The "131K views · 27 สิงหาคม 2026" line under a title.
 *
 * @param array $video Item from tsl_yt_items().
 */
function tsl_watch_meta( $video ) {
	$bits  = array();
	$views = tsl_yt_views( isset( $video['views'] ) ? $video['views'] : 0 );
	if ( $views ) {
		/* translators: %s: shortened view count, e.g. 131K */
		$bits[] = sprintf( __( '%s views', 'thestandard-life' ), $views );
	}
	if ( ! empty( $video['published'] ) ) {
		$stamp = strtotime( $video['published'] );
		if ( $stamp ) {
			$bits[] = date_i18n( get_option( 'date_format' ), $stamp );
		}
	}
	if ( $bits ) {
		echo '<span class="watch-meta">' . esc_html( implode( ' · ', $bits ) ) . '</span>';
	}
}
?>
<section class="section watch-section">
	<div class="section-head">
		<div>
			<div class="kicker" style="margin-bottom:16px;"><?php echo esc_html( tsl_opt( 'tsl_watch_kicker' ) ); ?></div>
			<h2 class="title"><?php echo esc_html( tsl_opt( 'tsl_watch_title' ) ); ?></h2>
		</div>
		<?php if ( $tsl_watch_more ) : ?>
			<a class="more" href="<?php echo esc_url( $tsl_watch_more ); ?>" target="_blank" rel="noopener"><?php esc_html_e( 'All episodes →', 'thestandard-life' ); ?></a>
		<?php endif; ?>
	</div>

	<div class="watch">
		<button type="button" class="watch-lead"
			data-video="<?php echo esc_attr( $tsl_watch_lead['id'] ); ?>"
			aria-label="<?php echo esc_attr( sprintf( __( 'เล่นคลิป: %s', 'thestandard-life' ), $tsl_watch_lead['title'] ) ); ?>">
			<?php tsl_watch_thumb( $tsl_watch_lead ); ?>
			<span class="watch-body">
				<span class="watch-title"><?php echo esc_html( $tsl_watch_lead['title'] ); ?></span>
				<?php if ( ! empty( $tsl_watch_lead['description'] ) ) : ?>
					<span class="watch-dek"><?php echo esc_html( wp_trim_words( $tsl_watch_lead['description'], 34 ) ); ?></span>
				<?php endif; ?>
				<?php tsl_watch_meta( $tsl_watch_lead ); ?>
			</span>
		</button>

		<?php if ( $tsl_watch ) : ?>
			<ul class="watch-list">
				<?php foreach ( $tsl_watch as $tsl_watch_item ) : ?>
					<li>
						<button type="button" class="watch-item"
							data-video="<?php echo esc_attr( $tsl_watch_item['id'] ); ?>"
							aria-label="<?php echo esc_attr( sprintf( __( 'เล่นคลิป: %s', 'thestandard-life' ), $tsl_watch_item['title'] ) ); ?>">
							<?php tsl_watch_thumb( $tsl_watch_item ); ?>
							<span class="watch-body">
								<span class="watch-title"><?php echo esc_html( $tsl_watch_item['title'] ); ?></span>
								<?php tsl_watch_meta( $tsl_watch_item ); ?>
							</span>
						</button>
					</li>
				<?php endforeach; ?>
			</ul>
		<?php endif; ?>
	</div>
</section>
