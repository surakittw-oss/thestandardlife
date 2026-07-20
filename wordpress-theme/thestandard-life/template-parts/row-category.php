<?php
/**
 * A full category row: heading + lead card + two stacks of two.
 * Expects $args['category'] (WP_Term) and optional $args['dark'] (bool).
 *
 * @package thestandard-life
 */
$cat  = isset( $args['category'] ) ? $args['category'] : null;
$dark = ! empty( $args['dark'] );
if ( ! $cat ) {
	return;
}

$q = new WP_Query( array(
	'post_type'           => TSL_CPT,
	'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		array(
			'taxonomy' => TSL_TAX,
			'field'    => 'term_id',
			'terms'    => $cat->term_id,
		),
	),
	'posts_per_page'      => 5,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
) );

if ( ! $q->have_posts() ) {
	wp_reset_postdata();
	return;
}

// Collect posts so we can split them into lead + two stacks.
$posts = $q->posts;
?>
<section class="<?php echo $dark ? 'dark-row' : 'section'; ?>">
	<div class="cat-row"<?php echo $dark ? ' style="margin-bottom:0;"' : ''; ?>>
		<div class="cat-row-head">
			<h2><?php echo esc_html( $cat->name ); ?></h2>
			<div class="cat-sub">
				<?php $cat_link = get_term_link( $cat ); ?>
				<a href="<?php echo esc_url( is_wp_error( $cat_link ) ? '#' : $cat_link ); ?>"><?php esc_html_e( 'See all →', 'thestandard-life' ); ?></a>
			</div>
		</div>
		<div class="cat-grid"<?php echo $dark ? ' style="margin-top:28px;"' : ''; ?>>
			<?php
			// Lead (first post).
			if ( isset( $posts[0] ) ) {
				$GLOBALS['post'] = $posts[0]; // phpcs:ignore
				setup_postdata( $GLOBALS['post'] );
				get_template_part( 'template-parts/card-lead' );
			}

			// Two stacks of up to two posts each: [1,2] and [3,4].
			$stacks = array( array_slice( $posts, 1, 2 ), array_slice( $posts, 3, 2 ) );
			foreach ( $stacks as $stack ) {
				if ( empty( $stack ) ) {
					continue;
				}
				echo '<div class="cat-stack">';
				foreach ( $stack as $p ) {
					$GLOBALS['post'] = $p; // phpcs:ignore
					setup_postdata( $GLOBALS['post'] );
					get_template_part( 'template-parts/card-stack' );
				}
				echo '</div>';
			}
			wp_reset_postdata();
			?>
		</div>
	</div>
</section>
