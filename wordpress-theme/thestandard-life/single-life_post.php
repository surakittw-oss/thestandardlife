<?php
/**
 * Single LIFE article (life_post custom post type).
 *
 * WordPress picks this file automatically for the "life_post" CPT via the
 * template hierarchy (single-{post_type}.php). Regular Posts still use the
 * theme's plain single.php, since this site has other content mixed in.
 *
 * @package thestandard-life
 */

get_header();

while ( have_posts() ) :
	the_post();

	$terms     = get_the_terms( get_the_ID(), TSL_TAX );
	$cats      = ( $terms && ! is_wp_error( $terms ) ) ? $terms : array();
	$primary   = ! empty( $cats ) ? $cats[0] : null;
	$permalink = get_permalink();
	$title_enc = rawurlencode( get_the_title() );
	$url_enc   = rawurlencode( $permalink );
	?>

	<!-- READING PROGRESS -->
	<div class="progress" id="progress"></div>

	<!-- BREADCRUMB -->
	<nav class="breadcrumb">
		<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'thestandard-life' ); ?></a>
		<?php if ( $primary ) :
			$primary_link = get_term_link( $primary );
			?>
			<span class="sep">/</span>
			<a href="<?php echo esc_url( is_wp_error( $primary_link ) ? '#' : $primary_link ); ?>"><?php echo esc_html( $primary->name ); ?></a>
		<?php endif; ?>
		<span class="sep">/</span>
		<a href="<?php echo esc_url( $permalink ); ?>"><?php the_title(); ?></a>
	</nav>

	<article <?php post_class(); ?>>
		<!-- ARTICLE HEADER -->
		<header class="art-head">
			<span class="art-kicker">
				<?php
				$labels = array();
				foreach ( $cats as $c ) {
					$labels[] = $c->name;
				}
				echo esc_html( implode( ' · ', $labels ) );
				?>
			</span>
			<h1 class="art-title"><?php the_title(); ?></h1>
			<?php if ( has_excerpt() ) : ?>
				<p class="art-dek"><?php echo esc_html( get_the_excerpt() ); ?></p>
			<?php endif; ?>
			<div class="art-meta">
				<span class="who"><?php echo get_avatar( get_the_author_meta( 'ID' ), 40 ); ?> <?php esc_html_e( 'โดย', 'thestandard-life' ); ?> <b><?php the_author(); ?></b></span>
				<span class="dot"></span>
				<span><?php echo esc_html( get_the_date() ); ?></span>
				<span class="dot"></span>
				<span><?php printf( esc_html__( 'อ่าน %d นาที', 'thestandard-life' ), tsl_reading_time() ); ?></span>
			</div>
			<div class="art-share">
				<a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo $url_enc; ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M24 12a12 12 0 1 0-13.88 11.86v-8.39H7.08V12h3.04V9.36c0-3 1.79-4.67 4.53-4.67a18.4 18.4 0 0 1 2.68.24v2.95h-1.51c-1.49 0-1.95.92-1.95 1.87V12h3.32l-.53 3.47h-2.79v8.39A12 12 0 0 0 24 12"/></svg></a>
				<a href="https://twitter.com/intent/tweet?url=<?php echo $url_enc; ?>&text=<?php echo $title_enc; ?>" target="_blank" rel="noopener noreferrer" aria-label="X"><svg viewBox="0 0 24 24"><path d="M18.9 1.15h3.68l-8.04 9.19L24 22.85h-7.4l-5.8-7.58-6.63 7.58H.49l8.6-9.83L0 1.15h7.59l5.24 6.93zM17.61 20.64h2.04L6.48 3.24H4.29z"/></svg></a>
				<a href="https://social-plugins.line.me/lineit/share?url=<?php echo $url_enc; ?>" target="_blank" rel="noopener noreferrer" aria-label="LINE"><svg viewBox="0 0 24 24"><path d="M24 10.3C24 4.93 18.62.56 12 .56S0 4.93 0 10.3c0 4.81 4.27 8.84 10.03 9.6.39.08.92.26 1.06.6.12.3.08.78.04 1.09l-.17 1.03c-.05.3-.24 1.19 1.04.65 1.28-.54 6.9-4.06 9.42-6.96C22.98 14.4 24 12.5 24 10.3M7.75 13.4H5.36a.63.63 0 0 1-.63-.63V8a.63.63 0 0 1 1.26 0v4.14h1.76a.63.63 0 0 1 0 1.26m2.48-.63a.63.63 0 0 1-1.26 0V8a.63.63 0 0 1 1.26 0zm5.72 0a.63.63 0 0 1-.43.6.63.63 0 0 1-.71-.22l-2.45-3.33v2.95a.63.63 0 0 1-1.26 0V8a.63.63 0 0 1 1.14-.37l2.45 3.34V8a.63.63 0 0 1 1.26 0zm3.86-3.03a.63.63 0 0 1 0 1.26H21.9v1.14h1.76a.63.63 0 0 1 0 1.26h-2.39a.63.63 0 0 1-.63-.63V8a.63.63 0 0 1 .63-.63h2.39a.63.63 0 0 1 0 1.26H21.9v1.11z"/></svg></a>
				<a href="#" onclick="navigator.clipboard&&navigator.clipboard.writeText('<?php echo esc_js( $permalink ); ?>');return false;" aria-label="<?php esc_attr_e( 'Copy link', 'thestandard-life' ); ?>"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M10 13a5 5 0 0 0 7 0l3-3a5 5 0 0 0-7-7l-1 1"/><path d="M14 11a5 5 0 0 0-7 0l-3 3a5 5 0 0 0 7 7l1-1"/></svg></a>
			</div>
		</header>

		<!-- HERO IMAGE -->
		<?php if ( has_post_thumbnail() ) : ?>
			<div class="art-hero-img"><?php the_post_thumbnail( 'full' ); ?></div>
			<?php if ( get_the_post_thumbnail_caption() ) : ?>
				<div class="art-hero-cap"><?php echo esc_html( get_the_post_thumbnail_caption() ); ?></div>
			<?php endif; ?>
		<?php endif; ?>

		<!-- BODY -->
		<div class="art-wrap">
			<!-- TOC (auto-built from H2 headings by theme.js) -->
			<aside class="toc" id="toc">
				<h6><?php esc_html_e( 'ในบทความนี้', 'thestandard-life' ); ?></h6>
				<ol></ol>
			</aside>

			<!-- PROSE -->
			<div class="prose">
				<?php
				the_content();
				wp_link_pages();
				?>
			</div>
		</div>

		<!-- AUTHOR BOX -->
		<div class="author-box">
			<?php echo get_avatar( get_the_author_meta( 'ID' ), 72 ); ?>
			<div>
				<span class="abt"><?php esc_html_e( 'เกี่ยวกับผู้เขียน', 'thestandard-life' ); ?></span>
				<h4><?php the_author(); ?></h4>
				<?php if ( get_the_author_meta( 'description' ) ) : ?>
					<p><?php echo esc_html( get_the_author_meta( 'description' ) ); ?></p>
				<?php endif; ?>
				<div class="a-links">
					<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php esc_html_e( 'บทความทั้งหมด', 'thestandard-life' ); ?></a>
				</div>
			</div>
		</div>
	</article>

	<!-- RELATED -->
	<?php
	if ( $primary ) :
		$related = new WP_Query( array(
			'post_type'           => TSL_CPT,
			'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => TSL_TAX,
					'field'    => 'term_id',
					'terms'    => $primary->term_id,
				),
			),
			'posts_per_page'      => 3,
			'post__not_in'        => array( get_the_ID() ),
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		) );
		if ( $related->have_posts() ) :
			?>
			<section class="related">
				<h3><?php printf( esc_html__( 'อ่านต่อในหมวด %s', 'thestandard-life' ), esc_html( $primary->name ) ); ?></h3>
				<div class="related-grid">
					<?php
					while ( $related->have_posts() ) :
						$related->the_post();
						?>
						<article class="rel-card">
							<a href="<?php the_permalink(); ?>"><?php tsl_cover_image( 'tsl-cover' ); ?></a>
							<span class="cat"><?php echo esc_html( tsl_primary_category() ); ?></span>
							<h4><a href="<?php the_permalink(); ?>" style="color:inherit;"><?php the_title(); ?></a></h4>
							<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 16 ) ); ?></p>
							<div class="meta"><?php echo esc_html( get_the_date() ); ?> &middot; <?php echo esc_html( tsl_reading_time() ); ?> min</div>
						</article>
						<?php
					endwhile;
					wp_reset_postdata();
					?>
				</div>
			</section>
			<?php
		endif;
	endif;
	?>

<?php endwhile; ?>

<?php get_footer(); ?>
