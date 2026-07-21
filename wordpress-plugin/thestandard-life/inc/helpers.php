<?php
/**
 * Helper functions + plugin template loaders.
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Include the LIFE header/footer shell from the plugin (NOT the active theme's
 * get_header()/get_footer(), which would pull the site's normal theme).
 */
function tsl_get_header() {
	include TSL_DIR . 'templates/parts/header.php';
}
function tsl_get_footer() {
	include TSL_DIR . 'templates/parts/footer.php';
}

/**
 * Include a template part from the plugin, passing $args (like get_template_part).
 *
 * @param string $slug Part name under templates/parts/ (without .php).
 * @param array  $args Variables available to the part as $args.
 */
function tsl_part( $slug, $args = array() ) {
	$file = TSL_DIR . 'templates/parts/' . $slug . '.php';
	if ( file_exists( $file ) ) {
		include $file;
	}
}

/**
 * Estimated reading time (minutes) for a post.
 *
 * @param int|null $post_id Post ID.
 * @return int
 */
function tsl_reading_time( $post_id = null ) {
	$content = get_post_field( 'post_content', $post_id ? $post_id : get_the_ID() );
	$words   = str_word_count( wp_strip_all_tags( $content ) );
	if ( $words < 50 ) {
		$words = max( $words, (int) ( mb_strlen( wp_strip_all_tags( $content ) ) / 3 ) );
	}
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Primary LIFE category term for a post (first life_category term).
 *
 * @param int|null $post_id Post ID.
 * @return WP_Term|null
 */
function tsl_primary_category_term( $post_id = null ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$terms   = get_the_terms( $post_id, TSL_TAX );
	if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
		return $terms[0];
	}
	return null;
}

/**
 * Primary category name.
 *
 * @param int|null $post_id Post ID.
 * @return string
 */
function tsl_primary_category( $post_id = null ) {
	$term = tsl_primary_category_term( $post_id );
	return $term ? $term->name : '';
}

/**
 * Cover image markup: featured image, or a neutral placeholder box.
 *
 * @param string $size Image size.
 */
function tsl_cover_image( $size = 'tsl-cover' ) {
	if ( has_post_thumbnail() ) {
		the_post_thumbnail( $size, array( 'alt' => the_title_attribute( array( 'echo' => false ) ) ) );
	} else {
		echo '<img src="' . esc_url( TSL_URL . 'assets/img/logo-tsl.png' ) . '" alt="" style="background:var(--cream);padding:20%;object-fit:contain;">';
	}
}

/**
 * Render a list of WP_Post objects using a plugin template part.
 *
 * @param WP_Post[] $items   Posts.
 * @param string    $slug    Part slug.
 * @param bool      $with_hr Insert <hr> between items.
 */
function tsl_render_cards( $items, $slug, $with_hr = false ) {
	$first = true;
	foreach ( $items as $p ) {
		if ( $with_hr && ! $first ) {
			echo '<hr>';
		}
		$GLOBALS['post'] = $p; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
		setup_postdata( $GLOBALS['post'] );
		tsl_part( $slug );
		$first = false;
	}
	wp_reset_postdata();
}
