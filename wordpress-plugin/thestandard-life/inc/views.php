<?php
/**
 * Article view counter.
 *
 * Counting in PHP as the page renders is the obvious approach and the wrong
 * one here: this site serves LIFE pages from a page cache, and a cached page
 * never reaches PHP — most readers would go uncounted. The count is recorded
 * from the browser instead, which runs whether the HTML came from the cache or
 * not, and the same request hands back the fresh total so the number on screen
 * is not the one that was cached with the page.
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const TSL_VIEWS_META = '_tsl_views';

/**
 * Read an article's view count.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function tsl_get_views( $post_id ) {
	return (int) get_post_meta( $post_id, TSL_VIEWS_META, true );
}

/**
 * Record one view and return the new total.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function tsl_record_view( $post_id ) {
	$views = tsl_get_views( $post_id ) + 1;
	update_post_meta( $post_id, TSL_VIEWS_META, $views );
	return $views;
}

/**
 * Should this request be counted?
 *
 * The browser already avoids reporting the same article twice, but that is a
 * client-side promise and easily bypassed, so the server keeps its own short
 * memory per address as well. Editors are skipped so the team's own previews
 * and proof-reads do not inflate what the number is supposed to measure.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function tsl_should_count_view( $post_id ) {
	if ( current_user_can( 'edit_posts' ) ) {
		return false;
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
	if ( ! $ip ) {
		return true;
	}

	$key = 'tsl_v_' . $post_id . '_' . md5( $ip );
	if ( get_transient( $key ) ) {
		return false;
	}
	set_transient( $key, 1, 6 * HOUR_IN_SECONDS );
	return true;
}

/**
 * POST /wp-json/tsl/v1/view/<id> — record a view, answer with the total.
 */
function tsl_register_view_route() {
	register_rest_route( 'tsl/v1', '/view/(?P<id>\d+)', array(
		'methods'             => 'POST',
		'permission_callback' => '__return_true', // Readers are anonymous by definition.
		'args'                => array(
			'id' => array(
				'validate_callback' => function ( $value ) {
					return is_numeric( $value );
				},
			),
		),
		'callback'            => function ( $request ) {
			$post_id = absint( $request['id'] );
			$post    = get_post( $post_id );

			// Only published LIFE articles, so the endpoint cannot be used to
			// write meta onto arbitrary posts.
			if ( ! $post || TSL_CPT !== $post->post_type || 'publish' !== $post->post_status ) {
				return new WP_Error( 'tsl_not_countable', __( 'Not a published LIFE article.', 'thestandard-life' ), array( 'status' => 404 ) );
			}

			$counted = tsl_should_count_view( $post_id );
			$views   = $counted ? tsl_record_view( $post_id ) : tsl_get_views( $post_id );

			return array(
				'counted' => $counted,
				'views'   => $views,
			);
		},
	) );
}
add_action( 'rest_api_init', 'tsl_register_view_route' );
