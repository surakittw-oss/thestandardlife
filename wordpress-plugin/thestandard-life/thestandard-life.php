<?php
/**
 * Plugin Name:       THE STANDARD LIFE
 * Plugin URI:        https://thestandard.co/life/
 * Description:       Registers the LIFE custom post type and renders LIFE content (landing, articles, categories) with the magazine design — WITHOUT taking over the whole site. Every other page keeps using your normal active theme.
 * Version:           1.0.0
 * Author:            THE STANDARD
 * Text Domain:       thestandard-life
 * License:           GPLv2 or later
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TSL_VERSION', '1.0.0' );
define( 'TSL_CPT', 'life_post' );
define( 'TSL_TAX', 'life_category' );
define( 'TSL_FILE', __FILE__ );
define( 'TSL_DIR', plugin_dir_path( __FILE__ ) );   // .../thestandard-life/
define( 'TSL_URL', plugin_dir_url( __FILE__ ) );    // https://.../thestandard-life/

require_once TSL_DIR . 'inc/helpers.php';
require_once TSL_DIR . 'inc/template-loader.php';

/**
 * Register the LIFE custom post type + its own taxonomy.
 */
function tsl_register_cpt() {
	register_post_type( TSL_CPT, array(
		'labels'       => array(
			'name'          => __( 'LIFE Articles', 'thestandard-life' ),
			'singular_name' => __( 'LIFE Article', 'thestandard-life' ),
			'add_new_item'  => __( 'Add New LIFE Article', 'thestandard-life' ),
			'edit_item'     => __( 'Edit LIFE Article', 'thestandard-life' ),
			'all_items'     => __( 'All LIFE Articles', 'thestandard-life' ),
			'search_items'  => __( 'Search LIFE Articles', 'thestandard-life' ),
			'not_found'     => __( 'No LIFE articles found', 'thestandard-life' ),
			'menu_name'     => __( 'THE STANDARD LIFE', 'thestandard-life' ),
		),
		'public'       => true,
		'has_archive'  => true, // /life/ becomes the LIFE landing page.
		'show_in_rest' => true,
		'menu_icon'    => 'dashicons-book-alt',
		'menu_position'=> 5,
		'supports'     => array( 'title', 'editor', 'excerpt', 'thumbnail', 'author', 'revisions', 'custom-fields' ),
		'rewrite'      => array( 'slug' => 'life', 'with_front' => false ),
	) );

	register_taxonomy( TSL_TAX, array( TSL_CPT ), array(
		'labels'       => array(
			'name'          => __( 'LIFE Categories', 'thestandard-life' ),
			'singular_name' => __( 'LIFE Category', 'thestandard-life' ),
			'menu_name'     => __( 'Categories', 'thestandard-life' ),
		),
		'hierarchical' => true,
		'public'       => true,
		'show_in_rest' => true,
		'rewrite'      => array( 'slug' => 'life-category', 'with_front' => false ),
	) );

	// Allow LIFE articles to use the built-in Tags (e.g. "editors-pick").
	register_taxonomy_for_object_type( 'post_tag', TSL_CPT );
}
add_action( 'init', 'tsl_register_cpt' );

/**
 * Add the tsl-cover image size (1200x628, cropped) used by the cards.
 */
function tsl_after_setup() {
	add_image_size( 'tsl-cover', 1200, 628, true );
}
add_action( 'after_setup_theme', 'tsl_after_setup' );

/**
 * Flush rewrite rules on activation / deactivation so /life/ URLs work.
 */
function tsl_activate() {
	tsl_register_cpt();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'tsl_activate' );

function tsl_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'tsl_deactivate' );
