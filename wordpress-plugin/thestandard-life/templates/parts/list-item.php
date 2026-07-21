<?php
/**
 * list-grid item (PLACE / TECH style rows). Expects $args['num'].
 * Use inside a loop.
 *
 * @package thestandard-life
 */
$num = isset( $args['num'] ) ? $args['num'] : '';
?>
<a class="li" href="<?php the_permalink(); ?>">
	<span class="idx">N&deg; <?php echo esc_html( $num ); ?> &middot; <?php echo esc_html( tsl_primary_category() ); ?></span>
	<?php tsl_cover_image( 'tsl-cover' ); ?>
	<h4><?php the_title(); ?></h4>
	<span class="cat"><?php echo esc_html( tsl_primary_category() ); ?></span>
	<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
</a>
