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
 * Purge the cached copies of specific LIFE URLs.
 *
 * Every supported cache is checked for rather than assumed: the plugin has to
 * run unchanged on a site with no caching at all, so each call is guarded.
 *
 * @param string|string[] $urls One or more URLs.
 */
function tsl_purge_urls( $urls ) {
	$urls = array_filter( array_unique( (array) $urls ) );
	if ( empty( $urls ) ) {
		return;
	}

	// The W3TC fallback empties the whole page cache, so it must not run once
	// per URL — a handful of stale pages is not worth clearing everyone's.
	$flushed_everything = false;

	foreach ( $urls as $url ) {
		// W3 Total Cache — flush the one URL when it can, otherwise the lot.
		if ( function_exists( 'w3tc_flush_url' ) ) {
			w3tc_flush_url( $url );
		} elseif ( ! $flushed_everything && function_exists( 'w3tc_pgcache_flush' ) ) {
			w3tc_pgcache_flush();
			$flushed_everything = true;
		}

		// WP Rocket.
		if ( function_exists( 'rocket_clean_files' ) ) {
			rocket_clean_files( $url );
		}

		// LiteSpeed Cache — listens for this action; a no-op when it is not active.
		do_action( 'litespeed_purge_url', $url );

		// WP Super Cache.
		if ( function_exists( 'wpsc_delete_url_cache' ) ) {
			wpsc_delete_url_cache( $url );
		}
	}

	/**
	 * Fires after LIFE pages have been purged, for any cache not handled above.
	 *
	 * @param string[] $urls The URLs purged.
	 */
	do_action( 'tsl_cache_purged', $urls );
}

/**
 * Purge the cached copy of the LIFE landing page.
 */
function tsl_purge_landing_cache() {
	$url = get_post_type_archive_link( TSL_CPT );
	tsl_purge_urls( $url );

	/**
	 * Fires after the LIFE landing page has been purged.
	 *
	 * @param string $url The landing page URL.
	 */
	do_action( 'tsl_landing_cache_purged', $url );
}

/**
 * The category archive URLs a post appears on.
 *
 * @param int $post_id Post ID.
 * @return string[]
 */
function tsl_post_archive_urls( $post_id ) {
	$terms = get_the_terms( $post_id, TSL_TAX );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return array();
	}

	$urls = array();
	foreach ( $terms as $term ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			$urls[] = $link;
		}
	}
	return $urls;
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

	// The plugin's own scratch options are not editorial content. The YouTube
	// caches in particular are rewritten every half hour whether or not the clip
	// lists actually moved, and purging the landing page on each of those would
	// mean the homepage is never cached for more than 30 minutes at a time.
	// tsl_yt_refresh() purges directly when a list really does change.
	$internal = array(
		'tsl_reels_cache',
		'tsl_watch_cache',
		'tsl_yt_resolved',
		'tsl_yt_schema',
	);
	if ( in_array( $option, $internal, true ) ) {
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
 * The category archives it appears on change for the same reason, and those are
 * even easier to miss: W3TC only purges term archives when "Purge Policy → Term
 * archives" is ticked, and it is not ticked by default. Handling it here means
 * a new article shows up in its category straight away whatever the cache
 * plugin has been configured to do.
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

	tsl_purge_urls( array_merge(
		array( get_post_type_archive_link( TSL_CPT ) ),
		tsl_post_archive_urls( $post_id )
	) );
}
add_action( 'save_post', 'tsl_purge_on_post_change', 10, 2 );
