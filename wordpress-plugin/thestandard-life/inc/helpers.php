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
 * Every card/hero image in this design is sized entirely by its container's
 * CSS `aspect-ratio` (16/9, 4/3, --cover, etc.) with object-fit:cover. But
 * WordPress auto-adds width/height attributes to every rendered <img>, which
 * browsers treat as the element's *intrinsic* aspect ratio — and per the CSS
 * spec, that intrinsic ratio wins over a plain `aspect-ratio: <ratio>` CSS
 * value. The result: images render at their native crop ratio instead of the
 * container's ratio, and since different WP image sizes have different
 * native ratios, this looks "inconsistent" across breakpoints/templates.
 *
 * Filtering the $attr array (via wp_get_attachment_image_attributes) does NOT
 * work here: wp_get_attachment_image() reads width/height into its own local
 * vars *before* that filter runs, then builds the `width="…" height="…"`
 * part of the tag straight from those vars regardless of what the filter did
 * to $attr. The only reliable point to remove them is the final HTML string,
 * via the `wp_get_attachment_image` filter.
 *
 * @param string $html Attachment image HTML.
 * @return string
 */
function tsl_strip_image_dimensions( $html ) {
	if ( tsl_current_view() ) {
		$html = preg_replace( '/\s(width|height)="\d+"/', '', $html );
	}
	return $html;
}
add_filter( 'wp_get_attachment_image', 'tsl_strip_image_dimensions' );

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

/**
 * Turn bare URLs inside an event block's meta lines into links.
 *
 * Round-up posts carry a "Booking:" and "More Info:" line per event, and
 * making a dozen of those clickable by hand is the sort of step that gets
 * skipped. Only the .event-meta paragraphs are touched, so nothing else in
 * the article changes.
 *
 * make_clickable() does the matching — it already leaves existing anchors and
 * tag attributes alone, which a hand-rolled URL regex tends to get wrong.
 * Social handles (@somewhere) are deliberately left as plain text: there is no
 * way to tell an Instagram handle from a Facebook one, and guessing would send
 * readers to the wrong place.
 *
 * @param string $content Post content.
 * @return string
 */
function tsl_linkify_event_meta( $content ) {
	if ( ! tsl_current_view() || false === strpos( $content, 'event-meta' ) ) {
		return $content;
	}

	return preg_replace_callback(
		'#(<p[^>]*class="[^"]*\bevent-meta\b[^"]*"[^>]*>)(.*?)(</p>)#is',
		function ( $m ) {
			$inner = make_clickable( $m[2] );

			// Give each link a target, and merge "noopener" into whatever rel is
			// already there — make_clickable sets rel="nofollow" itself, so adding
			// a second rel attribute would be invalid and silently drop one of them.
			$inner = preg_replace_callback(
				'#<a\b([^>]*)>#i',
				function ( $a ) {
					$attrs = $a[1];
					if ( ! preg_match( '#\btarget\s*=#i', $attrs ) ) {
						$attrs .= ' target="_blank"';
					}
					if ( preg_match( '#\brel\s*=\s*"([^"]*)"#i', $attrs, $rel ) ) {
						if ( ! preg_match( '#\bnoopener\b#i', $rel[1] ) ) {
							$attrs = str_ireplace( $rel[0], 'rel="' . $rel[1] . ' noopener"', $attrs );
						}
					} else {
						$attrs .= ' rel="noopener"';
					}
					return '<a' . $attrs . '>';
				},
				$inner
			);

			return $m[1] . $inner . $m[3];
		},
		$content
	);
}
add_filter( 'the_content', 'tsl_linkify_event_meta', 20 );

/**
 * Cache-busting version string for a bundled asset.
 *
 * TSL_VERSION alone is the plugin's release number, which does not move every
 * time a stylesheet is edited — so a browser or CDN holding assets under a
 * far-future expiry keeps serving the old file to anyone who has visited
 * before. Folding the file's own modification time into the version means an
 * edited asset always arrives under a URL nobody has cached yet.
 *
 * @param string $rel Path relative to the plugin directory.
 * @return string
 */
function tsl_asset_version( $rel ) {
	$path  = TSL_DIR . ltrim( $rel, '/' );
	$mtime = file_exists( $path ) ? filemtime( $path ) : false;
	return $mtime ? TSL_VERSION . '.' . $mtime : TSL_VERSION;
}
