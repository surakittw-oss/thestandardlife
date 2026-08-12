<?php
/**
 * "Insert event block" buttons for the Classic Editor, for the weekly
 * round-up format (LIFE This Week) where the same block — heading, photo,
 * blurb, Time/When/Where/More Info — repeats a dozen-plus times per post.
 *
 * Typing that skeleton by hand is slow, and inserting the photos one at a
 * time is slower still, so the second button takes a whole multi-selection
 * from the media library and lays out one complete block per photo.
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Are we on the add/edit screen of a LIFE post?
 *
 * @return bool
 */
function tsl_is_life_edit_screen() {
	if ( ! function_exists( 'get_current_screen' ) ) {
		return false;
	}
	$screen = get_current_screen();
	return $screen && 'post' === $screen->base && TSL_CPT === $screen->post_type;
}

/**
 * Add the buttons next to "Add Media" above the Classic Editor.
 *
 * @param string $editor_id Editor instance being rendered.
 */
function tsl_editor_buttons( $editor_id ) {
	if ( 'content' !== $editor_id || ! tsl_is_life_edit_screen() ) {
		return;
	}
	?>
	<button type="button" class="button tsl-insert-event">
		<span class="dashicons dashicons-plus-alt2" style="vertical-align:text-top;"></span>
		<?php esc_html_e( 'แทรกบล็อกกิจกรรม', 'thestandard-life' ); ?>
	</button>
	<button type="button" class="button tsl-insert-event-bulk">
		<span class="dashicons dashicons-images-alt2" style="vertical-align:text-top;"></span>
		<?php esc_html_e( 'แทรกหลายกิจกรรมจากรูป', 'thestandard-life' ); ?>
	</button>
	<?php
}
add_action( 'media_buttons', 'tsl_editor_buttons', 20 );

/**
 * Load the media frame + our inserter script on the LIFE edit screen.
 */
function tsl_editor_buttons_assets() {
	if ( ! tsl_is_life_edit_screen() ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script( 'media-editor', tsl_editor_buttons_js() );
}
add_action( 'admin_enqueue_scripts', 'tsl_editor_buttons_assets' );

/**
 * The inserter script.
 *
 * Blocks are inserted through wp.media.editor.insert(), which routes to the
 * visual or the text tab depending on which one is open, so the buttons work
 * in both. The image markup carries the wp-image-{ID} class WordPress needs
 * to attach srcset/sizes when the post is rendered.
 *
 * @return string
 */
function tsl_editor_buttons_js() {
	$labels = wp_json_encode( array(
		'name'    => __( 'ชื่องาน', 'thestandard-life' ),
		'blurb'   => __( 'คำอธิบายงาน…', 'thestandard-life' ),
		'time'    => __( 'Time:', 'thestandard-life' ),
		'when'    => __( 'When:', 'thestandard-life' ),
		'where'   => __( 'Where:', 'thestandard-life' ),
		'info'    => __( 'More Info:', 'thestandard-life' ),
		'frame'   => __( 'เลือกรูปกิจกรรม (เลือกได้หลายรูปพร้อมกัน)', 'thestandard-life' ),
		'useThem' => __( 'ใช้รูปเหล่านี้', 'thestandard-life' ),
	) );

	return <<<JS
( function( \$ ) {
	var L = {$labels};

	function esc( s ) {
		return String( s ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
	}

	// One complete event block. Pass an attachment to lead with its photo.
	function block( attachment ) {
		var figure = '';
		if ( attachment ) {
			var sizes = attachment.sizes || {};
			var src   = ( sizes.large || sizes.medium_large || sizes.full || {} ).url || attachment.url;
			figure = '<figure><img class="size-large wp-image-' + attachment.id + '" src="' + esc( src ) +
				'" alt="' + esc( attachment.alt || '' ) + '" /></figure>\\n';
		}
		return '<h2>' + L.name + '</h2>\\n' +
			figure +
			'<p>' + L.blurb + '</p>\\n' +
			'<p class="event-meta">' +
			'<strong>' + L.time + '</strong> <br />' +
			'<strong>' + L.when + '</strong> <br />' +
			'<strong>' + L.where + '</strong> <br />' +
			'<strong>' + L.info + '</strong> ' +
			'</p>\\n<hr />\\n';
	}

	function insert( html ) {
		if ( window.wp && wp.media && wp.media.editor ) {
			wp.media.editor.insert( html );
		}
	}

	\$( document ).on( 'click', '.tsl-insert-event', function( e ) {
		e.preventDefault();
		insert( block( null ) );
	} );

	var bulkFrame;
	\$( document ).on( 'click', '.tsl-insert-event-bulk', function( e ) {
		e.preventDefault();

		// Rebuild each time so the selection never carries over between runs.
		bulkFrame = wp.media( {
			title: L.frame,
			button: { text: L.useThem },
			library: { type: 'image' },
			multiple: 'add'
		} );

		bulkFrame.on( 'select', function() {
			var out = bulkFrame.state().get( 'selection' ).map( function( m ) {
				return block( m.toJSON() );
			} );
			if ( out.length ) {
				insert( out.join( '\\n' ) );
			}
		} );

		bulkFrame.open();
	} );
} )( jQuery );
JS;
}
