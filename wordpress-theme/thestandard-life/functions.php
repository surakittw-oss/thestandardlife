<?php
/**
 * THE STANDARD LIFE — theme functions
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TSL_VERSION', '1.0.0' );

/**
 * Theme setup.
 */
function tsl_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'custom-logo', array(
		'height'      => 110,
		'width'       => 300,
		'flex-height' => true,
		'flex-width'  => true,
	) );
	add_theme_support( 'html5', array(
		'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script',
	) );
	add_theme_support( 'responsive-embeds' );

	// Cover ratio used across cards (1200 x 628 ≈ the --cover CSS var).
	add_image_size( 'tsl-cover', 1200, 628, true );

	register_nav_menus( array(
		'primary'         => __( 'Primary Menu', 'thestandard-life' ),
		'footer-sections' => __( 'Footer — Sections', 'thestandard-life' ),
		'footer-more'     => __( 'Footer — More', 'thestandard-life' ),
		'footer-about'    => __( 'Footer — About', 'thestandard-life' ),
		'footer-family'   => __( 'Footer — The Family', 'thestandard-life' ),
	) );
}
add_action( 'after_setup_theme', 'tsl_setup' );

/**
 * Enqueue styles & scripts.
 */
function tsl_assets() {
	// Google Fonts (same families as the prototype).
	wp_enqueue_style(
		'tsl-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..900;1,9..144,300..900&family=Noto+Serif+Thai:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap',
		array(),
		null
	);

	wp_enqueue_style( 'tsl-style', get_stylesheet_uri(), array( 'tsl-fonts' ), TSL_VERSION );

	wp_enqueue_script( 'tsl-theme', get_template_directory_uri() . '/assets/js/theme.js', array(), TSL_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'tsl_assets' );

/**
 * Add preconnect hints for Google Fonts.
 */
function tsl_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array( 'href' => 'https://fonts.googleapis.com' );
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'tsl_resource_hints', 10, 2 );

/**
 * Inline "LIFE" SVG favicon (only if the user has not set a Site Icon).
 */
function tsl_favicon() {
	if ( has_site_icon() ) {
		return;
	}
	$svg = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='6' fill='%23000'/%3E%3Ctext x='16' y='22.5' font-family='Arial,Helvetica,sans-serif' font-weight='800' font-size='13' text-anchor='middle' fill='%23fff' textLength='25' lengthAdjust='spacingAndGlyphs'%3ELIFE%3C/text%3E%3C/svg%3E";
	echo '<link rel="icon" type="image/svg+xml" href="' . esc_attr( $svg ) . '">' . "\n";
}
add_action( 'wp_head', 'tsl_favicon' );

/**
 * Estimated reading time for a post, in minutes.
 *
 * @param int|null $post_id Post ID.
 * @return int Minutes (min 1).
 */
function tsl_reading_time( $post_id = null ) {
	$content = get_post_field( 'post_content', $post_id ? $post_id : get_the_ID() );
	$words   = str_word_count( wp_strip_all_tags( $content ) );
	// Thai text has no spaces; approximate by character count when word count is low.
	if ( $words < 50 ) {
		$words = max( $words, (int) ( mb_strlen( wp_strip_all_tags( $content ) ) / 3 ) );
	}
	return max( 1, (int) ceil( $words / 200 ) );
}

/**
 * Primary category name for a post (first assigned category).
 *
 * @param int|null $post_id Post ID.
 * @return string
 */
function tsl_primary_category( $post_id = null ) {
	$cats = get_the_category( $post_id ? $post_id : get_the_ID() );
	return ! empty( $cats ) ? $cats[0]->name : '';
}

/**
 * Fallback cover image markup: featured image or a neutral placeholder box.
 *
 * @param string $size Image size.
 */
function tsl_cover_image( $size = 'tsl-cover' ) {
	if ( has_post_thumbnail() ) {
		the_post_thumbnail( $size, array( 'alt' => the_title_attribute( array( 'echo' => false ) ) ) );
	} else {
		echo '<img src="' . esc_url( get_template_directory_uri() . '/assets/img/logo-tsl.png' ) . '" alt="" style="background:var(--cream);padding:20%;object-fit:contain;">';
	}
}

/**
 * Render a list of WP_Post objects using a template part (used on the front page).
 *
 * @param WP_Post[] $items   Posts.
 * @param string    $slug    Template part slug under template-parts/.
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
		get_template_part( 'template-parts/' . $slug );
		$first = false;
	}
	wp_reset_postdata();
}

/**
 * Register footer widget areas (optional alternative to footer menus).
 */
function tsl_widgets() {
	register_sidebar( array(
		'name'          => __( 'Footer Brand', 'thestandard-life' ),
		'id'            => 'footer-brand',
		'before_widget' => '<div class="foot-widget %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h5>',
		'after_title'   => '</h5>',
	) );
}
add_action( 'widgets_init', 'tsl_widgets' );
