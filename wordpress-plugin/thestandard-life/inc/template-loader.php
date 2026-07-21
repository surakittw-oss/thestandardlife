<?php
/**
 * Route LIFE pages to the plugin's own templates, and load LIFE assets only
 * on those pages. Every other request is left untouched, so the site's normal
 * theme keeps handling it.
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Is the current main query a LIFE-owned page?
 *
 * @return string One of 'single', 'archive', 'tax', or '' when not a LIFE page.
 */
function tsl_current_view() {
	if ( is_singular( TSL_CPT ) ) {
		return 'single';
	}
	if ( is_post_type_archive( TSL_CPT ) ) {
		return 'archive';
	}
	if ( is_tax( TSL_TAX ) ) {
		return 'tax';
	}
	return '';
}

/**
 * Swap in the plugin template for LIFE pages.
 *
 * @param string $template Template chosen by WordPress.
 * @return string
 */
function tsl_template_include( $template ) {
	$view = tsl_current_view();
	if ( ! $view ) {
		return $template; // Not a LIFE page — let the theme handle it.
	}

	$map = array(
		'single'  => 'templates/single-life_post.php',
		'archive' => 'templates/archive-life_post.php',
		'tax'     => 'templates/taxonomy-life_category.php',
	);
	$file = TSL_DIR . $map[ $view ];
	return file_exists( $file ) ? $file : $template;
}
add_filter( 'template_include', 'tsl_template_include', 99 );

/**
 * Enqueue LIFE fonts/CSS/JS ONLY on LIFE pages.
 */
function tsl_enqueue_assets() {
	if ( ! tsl_current_view() ) {
		return;
	}

	wp_enqueue_style(
		'tsl-fonts',
		'https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300..900;1,9..144,300..900&family=Noto+Serif+Thai:wght@300;400;500;600;700&family=Noto+Sans+Thai:wght@300;400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap',
		array(),
		null
	);
	wp_enqueue_style( 'tsl-life', TSL_URL . 'assets/css/life.css', array( 'tsl-fonts' ), TSL_VERSION );
	wp_enqueue_script( 'tsl-life', TSL_URL . 'assets/js/theme.js', array(), TSL_VERSION, true );
}
add_action( 'wp_enqueue_scripts', 'tsl_enqueue_assets' );

/**
 * Preconnect for Google Fonts (only meaningful when LIFE assets load).
 */
function tsl_resource_hints( $urls, $relation_type ) {
	if ( 'preconnect' === $relation_type && tsl_current_view() ) {
		$urls[] = array( 'href' => 'https://fonts.googleapis.com' );
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'tsl_resource_hints', 10, 2 );

/**
 * "LIFE" SVG favicon on LIFE pages (only if the site has no Site Icon set).
 */
function tsl_favicon() {
	if ( ! tsl_current_view() || has_site_icon() ) {
		return;
	}
	$svg = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 32 32'%3E%3Crect width='32' height='32' rx='6' fill='%23000'/%3E%3Ctext x='16' y='22.5' font-family='Arial,Helvetica,sans-serif' font-weight='800' font-size='13' text-anchor='middle' fill='%23fff' textLength='25' lengthAdjust='spacingAndGlyphs'%3ELIFE%3C/text%3E%3C/svg%3E";
	echo '<link rel="icon" type="image/svg+xml" href="' . esc_attr( $svg ) . '">' . "\n";
}
add_action( 'wp_head', 'tsl_favicon' );
