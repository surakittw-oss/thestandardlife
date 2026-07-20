<?php
/**
 * Front page (home).
 *
 * @package thestandard-life
 */

get_header();

/* ---------- Resolve the cover story (first sticky, else latest) ---------- */
$stickies   = get_option( 'sticky_posts' );
$cover_args = ! empty( $stickies )
	? array( 'post__in' => $stickies, 'posts_per_page' => 1, 'ignore_sticky_posts' => true )
	: array( 'posts_per_page' => 1, 'ignore_sticky_posts' => true );
$cover_q    = new WP_Query( $cover_args );
$cover_post = $cover_q->have_posts() ? $cover_q->posts[0] : null;
$cover_id   = $cover_post ? $cover_post->ID : 0;

/* ---------- Recent feed for the hero side columns ---------- */
$feed       = new WP_Query( array(
	'posts_per_page'      => 6,
	'post__not_in'        => array( $cover_id ),
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
) );
$feed_posts = $feed->posts;
$hero_left  = array_slice( $feed_posts, 0, 3 );
$hero_right = array_slice( $feed_posts, 3, 3 );
?>

<!-- HERO -->
<section class="hero">
	<aside class="hero-side">
		<div class="kicker"><?php esc_html_e( 'In this issue', 'thestandard-life' ); ?></div>
		<?php tsl_render_cards( $hero_left, 'card-small', true ); ?>
	</aside>

	<article class="hero-main">
		<?php if ( $cover_post ) :
			$GLOBALS['post'] = $cover_post; // phpcs:ignore
			setup_postdata( $GLOBALS['post'] );
			?>
			<span class="cat"><?php echo esc_html( tsl_primary_category() ); ?> &middot; <?php esc_html_e( 'The Cover Story', 'thestandard-life' ); ?></span>
			<h1><a href="<?php the_permalink(); ?>" style="color:inherit;"><?php the_title(); ?></a></h1>
			<p class="dek"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 42 ) ); ?></p>
			<figure class="img-frame">
				<span class="tag"><?php esc_html_e( 'Cover Story', 'thestandard-life' ); ?></span>
				<a href="<?php the_permalink(); ?>"><?php tsl_cover_image( 'tsl-cover' ); ?></a>
				<?php if ( get_the_post_thumbnail_caption() ) : ?>
					<figcaption><?php echo esc_html( get_the_post_thumbnail_caption() ); ?></figcaption>
				<?php endif; ?>
			</figure>
			<div class="byline">
				<?php echo get_avatar( get_the_author_meta( 'ID' ), 40, '', '', array( 'class' => 'avatar' ) ); ?>
				<span><?php printf( esc_html__( 'By %s', 'thestandard-life' ), esc_html( get_the_author() ) ); ?></span>
				<span class="dot"></span>
				<span><?php echo esc_html( get_the_date() ); ?></span>
				<span class="dot"></span>
				<span><?php echo esc_html( tsl_reading_time() ); ?> min read</span>
			</div>
			<?php wp_reset_postdata(); ?>
		<?php endif; ?>
	</article>

	<aside class="hero-side">
		<div class="kicker"><?php esc_html_e( 'More from LIFE', 'thestandard-life' ); ?></div>
		<?php tsl_render_cards( $hero_right, 'card-small', true ); ?>
		<?php // The original design also had Editor's Letter / Podcast / Event blocks here — wire these to a CPT or ACF if desired. ?>
	</aside>
</section>

<!-- PULL QUOTE (edit this text, or wire to a theme option / ACF) -->
<section class="quote">
	<blockquote><?php esc_html_e( 'ชีวิตดีๆ ไม่ได้ใหญ่ มันเล็กและซ้ำๆ ทุกวัน', 'thestandard-life' ); ?></blockquote>
	<cite><?php esc_html_e( '— THE STANDARD LIFE', 'thestandard-life' ); ?></cite>
</section>

<!-- EDITOR'S PICK -->
<?php
$pick_q = new WP_Query( array(
	'posts_per_page'      => 3,
	'tag'                 => 'editors-pick',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
) );
if ( ! $pick_q->have_posts() ) {
	$pick_q = new WP_Query( array( 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
}
if ( $pick_q->have_posts() ) :
	$badges = array( __( 'Feature of the week', 'thestandard-life' ), __( "Editor's note", 'thestandard-life' ), __( 'Reading guide', 'thestandard-life' ) );
	$pi     = 0;
	?>
	<section class="section" style="padding-top:84px;">
		<div class="section-head">
			<div>
				<div class="kicker" style="margin-bottom:16px;"><?php esc_html_e( "Editor's Pick · คัดสรรโดยบรรณาธิการ", 'thestandard-life' ); ?></div>
				<h2 class="title"><?php esc_html_e( 'สิ่งที่ควรอ่าน สัปดาห์นี้', 'thestandard-life' ); ?></h2>
			</div>
			<p class="lede"><?php esc_html_e( 'บทความที่ทีมงานเลือก — เพราะมันจะติดอยู่ในใจคุณไปสักพักใหญ่', 'thestandard-life' ); ?></p>
			<a class="more" href="<?php echo esc_url( home_url( '/tag/editors-pick/' ) ); ?>"><?php esc_html_e( 'All Picks →', 'thestandard-life' ); ?></a>
		</div>
		<div class="pick">
			<?php
			while ( $pick_q->have_posts() ) :
				$pick_q->the_post();
				get_template_part( 'template-parts/card-pick', null, array(
					'num'   => sprintf( '%02d', $pi + 1 ),
					'badge' => isset( $badges[ $pi ] ) ? $badges[ $pi ] : '',
				) );
				$pi++;
			endwhile;
			wp_reset_postdata();
			?>
		</div>
	</section>
<?php endif; ?>

<!-- POPULAR THIS WEEK -->
<?php
$pop_q = new WP_Query( array(
	'posts_per_page'      => 8,
	'orderby'             => 'comment_count',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
) );
if ( $pop_q->have_posts() ) : ?>
	<section class="section">
		<div class="section-head">
			<div>
				<div class="kicker" style="margin-bottom:16px;"><?php esc_html_e( 'Popular this week · อ่านมากที่สุด', 'thestandard-life' ); ?></div>
				<h2 class="title"><?php esc_html_e( 'สิ่งที่ผู้อ่านกำลังคุยกัน', 'thestandard-life' ); ?></h2>
			</div>
			<a class="more" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'See the list →', 'thestandard-life' ); ?></a>
		</div>
		<div class="popular">
			<?php
			$n = 0;
			while ( $pop_q->have_posts() ) :
				$pop_q->the_post();
				$n++;
				?>
				<div class="pop-item">
					<span class="num"><?php echo esc_html( sprintf( '%02d', $n ) ); ?></span>
					<div>
						<h4><a href="<?php the_permalink(); ?>" style="color:inherit;"><?php the_title(); ?></a></h4>
						<span class="cat"><?php echo esc_html( tsl_primary_category() ); ?></span>
					</div>
				</div>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	</section>
<?php endif; ?>

<!-- CATEGORY ROWS -->
<?php
$cats = get_categories( array(
	'orderby'    => 'count',
	'order'      => 'DESC',
	'number'     => 6,
	'hide_empty' => true,
) );
foreach ( $cats as $index => $c ) {
	get_template_part( 'template-parts/row-category', null, array(
		'category' => $c,
		'dark'     => ( 2 === $index ), // make the third row a cream "dark-row" for visual rhythm
	) );
}
?>

<?php get_footer(); ?>
