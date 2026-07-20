<?php
/**
 * Main listing template (blog home, search, and fallback).
 *
 * @package thestandard-life
 */

get_header();

// Heading for the current context.
if ( is_search() ) {
	$heading = sprintf( __( 'ผลการค้นหา: %s', 'thestandard-life' ), get_search_query() );
} elseif ( is_home() && ! is_front_page() ) {
	$heading = single_post_title( '', false );
	if ( ! $heading ) {
		$heading = __( 'บทความล่าสุด', 'thestandard-life' );
	}
} else {
	$heading = __( 'บทความล่าสุด', 'thestandard-life' );
}
?>

<section class="section">
	<div class="section-head">
		<div>
			<h2 class="title"><?php echo esc_html( $heading ); ?></h2>
		</div>
	</div>

	<?php if ( have_posts() ) : ?>
		<div class="list-grid">
			<?php
			$n = 0;
			while ( have_posts() ) :
				the_post();
				$n++;
				get_template_part( 'template-parts/list-item', null, array( 'num' => sprintf( '%02d', $n ) ) );
			endwhile;
			?>
		</div>

		<div class="section-head" style="margin-top:48px;">
			<?php the_posts_pagination( array( 'mid_size' => 1 ) ); ?>
		</div>
	<?php else : ?>
		<p style="padding:0 48px;"><?php esc_html_e( 'ไม่พบบทความ', 'thestandard-life' ); ?></p>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
