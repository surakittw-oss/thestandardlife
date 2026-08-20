<?php
/**
 * Dumbbell icon for the LIFE admin menu.
 *
 * Dashicons has no gym or exercise-equipment glyph — the nearest options are
 * stick figures — so the icon is supplied as an SVG instead.
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The dumbbell, drawn on a 20x20 grid to match the admin menu's icon size:
 * an outer plate and an inner plate each side of a short bar. Kept to plain
 * rectangles so it stays readable at 20px, where thin outlines turn to mush.
 *
 * @return string
 */
function tsl_dumbbell_svg() {
	return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">'
		. '<path d="M1.8 4.5h3v11h-3zM5.2 6.5h2v7h-2zM7 8.7h6v2.6H7z'
		. 'M12.8 6.5h2v7h-2zM15.2 4.5h3v11h-3z"/></svg>';
}

/**
 * Base64 data URI, the form register_post_type() accepts for menu_icon.
 *
 * @return string
 */
function tsl_dumbbell_data_uri() {
	return 'data:image/svg+xml;base64,' . base64_encode( tsl_dumbbell_svg() ); // phpcs:ignore
}

/**
 * Paint the icon through a CSS mask rather than leaving it as the background
 * image WordPress sets from menu_icon.
 *
 * A background-image SVG keeps whatever fill it was drawn with, so it stays
 * one flat colour while every neighbouring dashicon lightens on hover and
 * turns white on the current item. Masking the shape and colouring it with
 * currentColor lets it follow those states like the built-in icons do.
 */
function tsl_admin_icon_css() {
	// Not esc_url() — it drops data: URIs entirely, leaving an empty mask. The
	// value is our own base64, so it can only contain [A-Za-z0-9+/=] and cannot
	// break out of the url() it sits in.
	$uri = tsl_dumbbell_data_uri();
	$sel = '#adminmenu #menu-posts-' . TSL_CPT . ' .wp-menu-image';
	?>
	<style id="tsl-admin-icon">
		<?php echo $sel; // phpcs:ignore WordPress.Security.EscapeOutput ?>{
			/* WordPress prints menu_icon as an inline background-image, so
			   overriding it takes precedence this rule would not otherwise have. */
			background-image:none !important;
		}
		<?php echo $sel; // phpcs:ignore WordPress.Security.EscapeOutput ?>::before{
			content:''; display:block; width:20px; height:20px; margin:0 auto;
			background-color:currentColor;
			-webkit-mask:url('<?php echo $uri; // phpcs:ignore WordPress.Security.EscapeOutput ?>') no-repeat center / 20px 20px;
			mask:url('<?php echo $uri; // phpcs:ignore WordPress.Security.EscapeOutput ?>') no-repeat center / 20px 20px;
		}
	</style>
	<?php
}
add_action( 'admin_head', 'tsl_admin_icon_css' );
