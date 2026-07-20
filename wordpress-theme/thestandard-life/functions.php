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
define( 'TSL_CPT', 'life_post' );      // Custom Post Type slug for LIFE content.
define( 'TSL_TAX', 'life_category' );  // Custom taxonomy slug for LIFE sections (Food & Drink, Place, ...).

/**
 * Register the "LIFE" custom post type + its own taxonomy.
 *
 * We register this in code (not via CPT UI) because the theme's templates
 * are wired directly to these exact slugs — keeping registration in the
 * theme means the whole thing stays portable as one package. If CPT UI is
 * active on this site, do NOT also register `life_post` / `life_category`
 * there — a duplicate registration will conflict with this one.
 */
function tsl_register_cpt() {
	register_post_type( TSL_CPT, array(
		'labels'       => array(
			'name'               => __( 'LIFE Articles', 'thestandard-life' ),
			'singular_name'      => __( 'LIFE Article', 'thestandard-life' ),
			'add_new_item'       => __( 'Add New LIFE Article', 'thestandard-life' ),
			'edit_item'          => __( 'Edit LIFE Article', 'thestandard-life' ),
			'all_items'          => __( 'All LIFE Articles', 'thestandard-life' ),
			'search_items'       => __( 'Search LIFE Articles', 'thestandard-life' ),
			'not_found'          => __( 'No LIFE articles found', 'thestandard-life' ),
			'menu_name'          => __( 'THE STANDARD LIFE', 'thestandard-life' ),
		),
		'public'       => true,
		'has_archive'  => true,
		'show_in_rest' => true, // Gutenberg + REST API support.
		'menu_icon'    => 'dashicons-book-alt',
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
		'rewrite'      => array( 'slug' => 'life', 'with_front' => false ),
		'query_var'    => TSL_CPT,
	) );

	register_taxonomy( TSL_TAX, array( TSL_CPT ), array(
		'labels'       => array(
			'name'          => __( 'LIFE Categories', 'thestandard-life' ),
			'singular_name' => __( 'LIFE Category', 'thestandard-life' ),
			'menu_name'     => __( 'Categories', 'thestandard-life' ),
		),
		'hierarchical' => true, // behaves like the built-in Category, not Tag.
		'public'       => true,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'life-category', 'with_front' => false ),
	) );

	// Let LIFE articles also use the built-in Tags (e.g. an "editors-pick" tag
	// to feature posts on the front page) without pulling in the built-in
	// Category taxonomy, which stays reserved for the site's other content.
	register_taxonomy_for_object_type( 'post_tag', TSL_CPT );
}
add_action( 'init', 'tsl_register_cpt' );

/**
 * Flush rewrite rules once after the CPT above is registered for the first
 * time, so /life/... and /life-category/... URLs work without manually
 * visiting Settings > Permalinks. Runs after tsl_register_cpt (priority 10)
 * on the same 'init' hook, so the rules it flushes already include ours.
 */
function tsl_maybe_flush_rewrites() {
	if ( ! get_option( 'tsl_rewrites_flushed' ) ) {
		flush_rewrite_rules();
		update_option( 'tsl_rewrites_flushed', 1 );
	}
}
add_action( 'init', 'tsl_maybe_flush_rewrites', 20 );

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
 * Primary LIFE category term for a post (first assigned term in the
 * life_category taxonomy). Falls back to the built-in Category so the
 * same template parts still work on non-LIFE content, if ever reused.
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
	$cats = get_the_category( $post_id );
	return ! empty( $cats ) ? $cats[0] : null;
}

/**
 * Primary category name for a post.
 *
 * @param int|null $post_id Post ID.
 * @return string
 */
function tsl_primary_category( $post_id = null ) {
	$term = tsl_primary_category_term( $post_id );
	return $term ? $term->name : '';
}

/**
 * Archive/category-listing link for a post's primary LIFE category.
 *
 * @param int|null $post_id Post ID.
 * @return string URL, or '' if the post has no category.
 */
function tsl_primary_category_link( $post_id = null ) {
	$term = tsl_primary_category_term( $post_id );
	if ( ! $term ) {
		return '';
	}
	$link = get_term_link( $term );
	return is_wp_error( $link ) ? '' : $link;
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
