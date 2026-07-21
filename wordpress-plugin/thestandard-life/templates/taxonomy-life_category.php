<?php
/**
 * LIFE category archive (taxonomy life_category).
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

tsl_get_header();

$term = get_queried_object();
?>

<section class="section" style="padding-top:64px;">
	<div class="section-head">
		<div>
			<div class="kicker" style="margin-bottom:16px;"><?php esc_html_e( 'THE STANDARD LIFE', 'thestandard-life' ); ?></div>
			<h2 class="title"><?php echo esc_html( $term ? $term->name : get_the_archive_title() ); ?></h2>
		</div>
		<?php if ( $term && $term->description ) : ?>
			<p class="lede"><?php echo esc_html( $term->description ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( have_posts() ) : ?>
		<div class="list-grid">
			<?php
			$n = 0;
			while ( have_posts() ) :
				the_post();
				$n++;
				tsl_part( 'list-item', array( 'num' => sprintf( '%02d', $n ) ) );
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

<?php
tsl_get_footer();
