<?php
/**
 * Small stacked card (category row right columns). Use inside a loop.
 *
 * @package thestandard-life
 */
?>
<a class="card" href="<?php the_permalink(); ?>">
	<?php tsl_cover_image( 'tsl-cover' ); ?>
	<span class="cat"><?php echo esc_html( tsl_primary_category() ); ?></span>
	<h4><?php the_title(); ?></h4>
	<div class="meta"><?php echo esc_html( get_the_date() ); ?> &middot; <?php echo esc_html( tsl_reading_time() ); ?> min</div>
</a>
