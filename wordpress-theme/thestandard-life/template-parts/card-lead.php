<?php
/**
 * Category row lead card (large, left column). Use inside a loop.
 *
 * @package thestandard-life
 */
?>
<a class="cat-lead" href="<?php the_permalink(); ?>">
	<?php tsl_cover_image( 'tsl-cover' ); ?>
	<span class="cat"><?php echo esc_html( tsl_primary_category() ); ?></span>
	<h3><?php the_title(); ?></h3>
	<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 28 ) ); ?></p>
	<div class="byline">
		<?php echo get_avatar( get_the_author_meta( 'ID' ), 48, '', '', array( 'class' => 'avatar' ) ); ?>
		<span><?php printf( esc_html__( 'By %s', 'thestandard-life' ), esc_html( get_the_author() ) ); ?></span>
		<span class="dot"></span>
		<span><?php echo esc_html( tsl_reading_time() ); ?> min read</span>
	</div>
</a>
