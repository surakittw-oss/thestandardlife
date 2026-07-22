<?php
/**
 * LIFE landing page (post type archive at /life/).
 * Renders the full magazine layout: hero, editor's pick, popular, category rows.
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

tsl_get_header();

/* ---------- Cover story (first sticky LIFE post, else latest) ---------- */
$stickies   = get_option( 'sticky_posts' );
$cover_args = array( 'post_type' => TSL_CPT, 'posts_per_page' => 1, 'ignore_sticky_posts' => true, 'no_found_rows' => true );
if ( ! empty( $stickies ) ) {
	$cover_args['post__in'] = $stickies;
}
$cover_q = new WP_Query( $cover_args );
if ( ! $cover_q->have_posts() && ! empty( $cover_args['post__in'] ) ) {
	unset( $cover_args['post__in'] );
	$cover_q = new WP_Query( $cover_args );
}
$cover_post = $cover_q->have_posts() ? $cover_q->posts[0] : null;
$cover_id   = $cover_post ? $cover_post->ID : 0;

/* ---------- Recent feed for hero side columns ---------- */
$feed       = new WP_Query( array(
	'post_type'           => TSL_CPT,
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
		<div class="kicker"><?php echo esc_html( tsl_opt( 'tsl_issue_label' ) ); ?></div>
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
		<?php
		$letter_title = tsl_opt( 'tsl_letter_title' );
		$podcast_title = tsl_opt( 'tsl_podcast_title' );
		$event_title   = tsl_opt( 'tsl_event_title' );
		$has_editorial = $letter_title || $podcast_title || $event_title;
		?>

		<?php if ( $letter_title ) : ?>
			<div>
				<div class="kicker"><?php echo esc_html( tsl_opt( 'tsl_letter_kicker' ) ); ?></div>
				<h4 style="margin-top:14px; font-size:18px; font-family:var(--sans); font-weight:600; line-height:1.5;"><?php echo esc_html( $letter_title ); ?></h4>
				<?php if ( tsl_opt( 'tsl_letter_body' ) ) : ?>
					<p style="color:var(--muted); font-size:13.5px; margin-top:10px;"><?php echo esc_html( tsl_opt( 'tsl_letter_body' ) ); ?></p>
				<?php endif; ?>
				<?php if ( tsl_opt( 'tsl_letter_author' ) ) : ?>
					<div class="byline" style="margin-top:14px;">
						<?php $avatar = tsl_opt( 'tsl_letter_avatar' ); ?>
						<?php if ( $avatar ) : ?>
							<img class="avatar" src="<?php echo esc_url( $avatar ); ?>" alt="">
						<?php else : ?>
							<span class="avatar" style="display:inline-block; width:24px; height:24px; border-radius:50%; background:var(--line);"></span>
						<?php endif; ?>
						<span><?php echo esc_html( tsl_opt( 'tsl_letter_author' ) ); ?></span>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $podcast_title ) : ?>
			<?php if ( $letter_title ) : ?><hr><?php endif; ?>
			<div>
				<div class="kicker"><?php esc_html_e( 'Listen', 'thestandard-life' ); ?></div>
				<?php $pod_link = tsl_opt( 'tsl_podcast_link' ); ?>
				<a class="small-card" style="margin-top:14px;" href="<?php echo $pod_link ? esc_url( $pod_link ) : '#'; ?>"<?php echo $pod_link ? ' target="_blank" rel="noopener"' : ''; ?>>
					<?php $pod_img = tsl_opt( 'tsl_podcast_image' ); ?>
					<?php if ( $pod_img ) : ?>
						<img src="<?php echo esc_url( $pod_img ); ?>" alt="">
					<?php else : ?>
						<img src="<?php echo esc_url( TSL_URL . 'assets/img/logo-tsl.png' ); ?>" alt="" style="background:var(--cream);padding:14%;object-fit:contain;">
					<?php endif; ?>
					<div>
						<span class="eyebrow"><?php echo esc_html( tsl_opt( 'tsl_podcast_kicker' ) ); ?></span>
						<h5 style="margin-top:6px;"><?php echo esc_html( $podcast_title ); ?></h5>
						<?php if ( tsl_opt( 'tsl_podcast_meta' ) ) : ?>
							<div class="meta" style="margin-top:8px; color:var(--muted);"><?php echo esc_html( tsl_opt( 'tsl_podcast_meta' ) ); ?></div>
						<?php endif; ?>
					</div>
				</a>
			</div>
		<?php endif; ?>

		<?php if ( $event_title ) : ?>
			<?php if ( $letter_title || $podcast_title ) : ?><hr><?php endif; ?>
			<div>
				<div class="kicker"><?php esc_html_e( 'Upcoming Event', 'thestandard-life' ); ?></div>
				<?php $ev_link = tsl_opt( 'tsl_event_link' ); ?>
				<h5 style="font-family:var(--sans); font-size:16px; margin-top:12px; font-weight:600; line-height:1.5;">
					<?php if ( $ev_link ) : ?>
						<a href="<?php echo esc_url( $ev_link ); ?>" style="color:inherit;"><?php echo esc_html( $event_title ); ?></a>
					<?php else : ?>
						<?php echo esc_html( $event_title ); ?>
					<?php endif; ?>
				</h5>
				<?php if ( tsl_opt( 'tsl_event_meta' ) ) : ?>
					<div class="meta" style="margin-top:8px;"><?php echo esc_html( tsl_opt( 'tsl_event_meta' ) ); ?></div>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php
		// Fallback: if the editor cleared every curated block, show recent posts.
		if ( ! $has_editorial ) {
			echo '<div class="kicker">' . esc_html__( 'More from LIFE', 'thestandard-life' ) . '</div>';
			tsl_render_cards( $hero_right, 'card-small', true );
		}
		?>
	</aside>
</section>

<!-- PULL QUOTE -->
<?php if ( tsl_opt( 'tsl_quote_text' ) ) : ?>
	<section class="quote">
		<blockquote><?php echo esc_html( tsl_opt( 'tsl_quote_text' ) ); ?></blockquote>
		<?php if ( tsl_opt( 'tsl_quote_cite' ) ) : ?>
			<cite><?php echo esc_html( tsl_opt( 'tsl_quote_cite' ) ); ?></cite>
		<?php endif; ?>
	</section>
<?php endif; ?>

<!-- EDITOR'S PICK -->
<?php
$pick_q = new WP_Query( array(
	'post_type'           => TSL_CPT,
	'posts_per_page'      => 3,
	'tag'                 => 'editors-pick',
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
) );
if ( ! $pick_q->have_posts() ) {
	$pick_q = new WP_Query( array( 'post_type' => TSL_CPT, 'posts_per_page' => 3, 'ignore_sticky_posts' => true, 'no_found_rows' => true ) );
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
			<a class="more" href="<?php echo esc_url( home_url( '/tag/editors-pick/' ) ); ?>"><?php esc_html_e( 'All Picks →', 'thestandard-life' ); ?></a>
		</div>
		<div class="pick">
			<?php
			while ( $pick_q->have_posts() ) :
				$pick_q->the_post();
				tsl_part( 'card-pick', array(
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
	'post_type'           => TSL_CPT,
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
			<a class="more" href="<?php echo esc_url( get_post_type_archive_link( TSL_CPT ) ); ?>"><?php esc_html_e( 'See the list →', 'thestandard-life' ); ?></a>
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
$cats = get_terms( array(
	'taxonomy'   => TSL_TAX,
	'orderby'    => 'count',
	'order'      => 'DESC',
	'number'     => 6,
	'hide_empty' => true,
) );
if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
	foreach ( $cats as $index => $c ) {
		tsl_part( 'row-category', array(
			'category' => $c,
			'dark'     => ( 2 === $index ),
		) );
	}
}
?>

<?php
tsl_get_footer();
