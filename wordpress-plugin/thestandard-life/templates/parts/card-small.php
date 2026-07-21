<?php
/**
 * Small horizontal card (hero "In this issue" / Listen list).
 * Use inside a loop.
 *
 * @package thestandard-life
 */
?>
<a class="small-card" href="<?php the_permalink(); ?>">
	<?php tsl_cover_image( 'tsl-cover' ); ?>
	<div>
		<span class="eyebrow"><?php echo esc_html( tsl_primary_category() ); ?></span>
		<h5 style="margin-top:6px;"><?php the_title(); ?></h5>
		<div class="meta" style="margin-top:8px; color:var(--muted);"><?php echo esc_html( tsl_reading_time() ); ?> min read</div>
	</div>
</a>
