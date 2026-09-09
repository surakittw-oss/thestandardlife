<?php
/**
 * Render Classic Editor galleries as a photo album — one large image with
 * prev/next arrows and a thumbnail strip beneath — instead of WordPress's
 * default grid of squares.
 *
 * This hooks the gallery's output rather than inventing a shortcode, so
 * writers keep using Add Media > Create Gallery exactly as before, including
 * its drag-to-reorder. Turning the plugin off leaves ordinary [gallery]
 * markup behind, not a broken tag.
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Swap the gallery markup on LIFE pages.
 *
 * @param string $output   Existing output; non-empty means someone else won.
 * @param array  $attr     Shortcode attributes.
 * @param int    $instance Gallery instance number on this post.
 * @return string
 */
function tsl_render_gallery( $output, $attr, $instance = 0 ) {
	if ( ! tsl_current_view() ) {
		return $output;
	}

	// Classic's gallery writes "ids"; "include" is the older spelling.
	$ids = '';
	if ( ! empty( $attr['ids'] ) ) {
		$ids = $attr['ids'];
	} elseif ( ! empty( $attr['include'] ) ) {
		$ids = $attr['include'];
	}
	if ( ! $ids ) {
		return $output;
	}

	$id_list = array_values( array_filter( array_map( 'absint', explode( ',', $ids ) ) ) );
	if ( ! $id_list ) {
		return $output;
	}

	// A gallery of one is just a picture; let WordPress handle it.
	if ( count( $id_list ) < 2 ) {
		return $output;
	}

	$uid    = 'tsl-album-' . absint( $instance ) . '-' . wp_rand( 1000, 9999 );
	$slides = '';
	$thumbs = '';

	foreach ( $id_list as $i => $id ) {
		// Slides keep their width/height attributes, unlike every other image
		// on these pages. The stage takes its height from the photo, so without
		// them the browser has no ratio to reserve space with and the stage
		// lands on an arbitrary height until the file loads, then jumps.
		remove_filter( 'wp_get_attachment_image', 'tsl_strip_image_dimensions' );
		$full = wp_get_attachment_image( $id, 'large', false, array( 'loading' => $i ? 'lazy' : 'eager' ) );
		add_filter( 'wp_get_attachment_image', 'tsl_strip_image_dimensions' );
		if ( ! $full ) {
			continue;
		}
		$caption = wp_get_attachment_caption( $id );
		$thumb   = wp_get_attachment_image( $id, 'thumbnail' );

		$slides .= '<li class="tsl-album-slide' . ( 0 === $i ? ' is-active' : '' ) . '"'
			. ( 0 === $i ? '' : ' aria-hidden="true"' ) . '>' . $full;
		if ( $caption ) {
			$slides .= '<figcaption>' . esc_html( $caption ) . '</figcaption>';
		}
		$slides .= '</li>';

		$thumbs .= '<li><button type="button" class="tsl-album-thumb' . ( 0 === $i ? ' is-active' : '' ) . '"'
			. ' data-index="' . esc_attr( $i ) . '"'
			. ' aria-label="' . esc_attr( sprintf( __( 'Photo %d', 'thestandard-life' ), $i + 1 ) ) . '"'
			. ( 0 === $i ? ' aria-current="true"' : '' ) . '>' . $thumb . '</button></li>';
	}

	if ( '' === $slides ) {
		return $output;
	}

	$total = count( $id_list );

	ob_start();
	?>
	<figure class="tsl-album" id="<?php echo esc_attr( $uid ); ?>" data-total="<?php echo esc_attr( $total ); ?>">
		<div class="tsl-album-stage">
			<ul class="tsl-album-slides"><?php echo $slides; // phpcs:ignore WordPress.Security.EscapeOutput ?></ul>
			<button type="button" class="tsl-album-nav tsl-album-prev" aria-label="<?php esc_attr_e( 'Previous photo', 'thestandard-life' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 5 8 12l7 7"/></svg>
			</button>
			<button type="button" class="tsl-album-nav tsl-album-next" aria-label="<?php esc_attr_e( 'Next photo', 'thestandard-life' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 5 7 7-7 7"/></svg>
			</button>
			<span class="tsl-album-count"><span class="tsl-album-current">1</span>/<?php echo esc_html( $total ); ?></span>
		</div>
		<ul class="tsl-album-thumbs"><?php echo $thumbs; // phpcs:ignore WordPress.Security.EscapeOutput ?></ul>
	</figure>
	<?php
	return ob_get_clean();
}
/**
 * Runs late on purpose.
 *
 * post_gallery hands every filter the previous one's output and takes whatever
 * comes back last, and this one substitutes its own markup wholesale rather
 * than building on what it was given. So on a LIFE page it has to be the last
 * word — at the default priority of 10 it is not: the active theme registers
 * its own gallery slider on post_gallery at 10 too, and a theme's functions
 * load after every plugin, so the theme's callback is registered second and
 * therefore runs second, quietly replacing the album with its own markup.
 *
 * The visible result was worse than just "the wrong slider": LIFE pages drop
 * the theme's stylesheets (see tsl_dequeue_theme_styles), so the theme's
 * gallery arrived with none of its CSS — every slide stacked down the page,
 * arrows and counter sitting in the text flow, thumbnails at full column width.
 */
add_filter( 'post_gallery', 'tsl_render_gallery', 999, 3 );
