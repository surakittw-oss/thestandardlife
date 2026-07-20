<?php
/**
 * Archive template (category, tag, author, date).
 *
 * @package thestandard-life
 */

get_header();
?>

<section class="section">
	<div class="section-head">
		<div>
			<div class="kicker" style="margin-bottom:16px;"><?php esc_html_e( 'THE STANDARD LIFE', 'thestandard-life' ); ?></div>
			<h2 class="title"><?php echo esc_html( wp_strip_all_tags( get_the_archive_title() ) ); ?></h2>
		</div>
		<?php if ( get_the_archive_description() ) : ?>
			<p class="lede"><?php echo wp_kses_post( get_the_archive_description() ); ?></p>
		<?php endif; ?>
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
		<p style="padding:0 48px;"><?php esc_html_e( 'ไม่พบบทความในหมวดนี้', 'thestandard-life' ); ?></p>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
