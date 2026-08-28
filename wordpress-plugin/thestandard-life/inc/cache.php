<?php
/**
 * Keep page caches honest about the LIFE landing page.
 *
 * Page caches invalidate on post save, which covers the articles. They do not
 * watch options — and the landing page's curated blocks (Editor's Letter, the
 * podcast/video card, the upcoming event, the pull quote, the hero cards) all
 * come from options. Without this, editing any of them in Homepage Settings
 * leaves the public page showing the previous text until the cache happens to
 * expire, which reads as "my change did not save".
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Purge the cached copy of the LIFE landing page.
 *
 * Every supported cache is checked for rather than assumed: the plugin has to
 * run unchanged on a site with no caching at all, so each call is guarded.
 */
function tsl_purge_landing_cache() {
	$url = get_post_type_archive_link( TSL_CPT );

	// W3 Total Cache — flush the one URL when it can, otherwise the page cache.
	if ( $url && function_exists( 'w3tc_flush_url' ) ) {
		w3tc_flush_url( $url );
	} elseif ( function_exists( 'w3tc_pgcache_flush' ) ) {
		w3tc_pgcache_flush();
	}

	// WP Rocket.
	if ( $url && function_exists( 'rocket_clean_files' ) ) {
		rocket_clean_files( $url );
	}

	// LiteSpeed Cache — listens for this action; a no-op when it is not active.
	if ( $url ) {
		do_action( 'litespeed_purge_url', $url );
	}

	// WP Super Cache.
	if ( $url && function_exists( 'wpsc_delete_url_cache' ) ) {
		wpsc_delete_url_cache( $url );
	}

	/**
	 * Fires after the LIFE landing page has been purged, for any cache not
	 * handled above.
	 *
	 * @param string $url The landing page URL.
	 */
	do_action( 'tsl_landing_cache_purged', $url );
}

/**
 * Purge when one of this plugin's options changes.
 *
 * Hooked on the generic option actions rather than one hook per key, so a
 * field added to Homepage Settings later is covered without touching this file.
 *
 * @param string $option Option name that changed.
 */
function tsl_purge_on_option_change( $option ) {
	if ( 0 !== strpos( $option, 'tsl_' ) ) {
		return;
	}
	tsl_purge_landing_cache();
}
add_action( 'updated_option', 'tsl_purge_on_option_change' );
add_action( 'added_option', 'tsl_purge_on_option_change' );

/**
 * Purge when a LIFE article changes.
 *
 * The landing page lists articles, so publishing, editing or trashing one
 * changes it — but a cache watching post saves purges the article's own URL and
 * may not connect it to a custom post type's archive.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post object.
 */
function tsl_purge_on_post_change( $post_id, $post ) {
	if ( ! $post || TSL_CPT !== $post->post_type ) {
		return;
	}
	if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	tsl_purge_landing_cache();
}
add_action( 'save_post', 'tsl_purge_on_post_change', 10, 2 );
