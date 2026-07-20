<?php
/**
 * Editor's Pick card. Expects $args['num'] and $args['badge'].
 * The first card gets the .feature modifier via $args['feature'] but is
 * styled the same size as the others per the current design.
 *
 * @package thestandard-life
 */
$num   = isset( $args['num'] ) ? $args['num'] : '';
$badge = isset( $args['badge'] ) ? $args['badge'] : __( 'Editor\'s Pick', 'thestandard-life' );
?>
<a class="pick-card" href="<?php the_permalink(); ?>">
	<div class="pick-badge"><span class="n"><?php echo esc_html( $num ); ?></span> <?php echo esc_html( $badge ); ?></div>
	<?php tsl_cover_image( 'tsl-cover' ); ?>
	<span class="cat" style="margin-top:16px; display:block;"><?php echo esc_html( tsl_primary_category() ); ?></span>
	<h3><?php the_title(); ?></h3>
	<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 22 ) ); ?></p>
	<div class="meta"><?php echo esc_html( get_the_date() ); ?> &middot; <?php echo esc_html( tsl_reading_time() ); ?> min read</div>
</a>
