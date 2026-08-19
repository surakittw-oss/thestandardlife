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
	// inline-flex keeps the icon centred on the same line as the label; the
	// dashicon's own 20px line-height otherwise drops it below the baseline.
	?>
	<style>
		/* Specificity has to clear core's ".wp-media-buttons .button", which
		   otherwise forces display:inline-block and defeats the flex centring. */
		.wp-media-buttons .button.tsl-editor-btn{display:inline-flex; align-items:center; gap:4px; vertical-align:top;}
		.wp-media-buttons .button.tsl-editor-btn .dashicons{font-size:18px; width:18px; height:18px; line-height:1; vertical-align:middle;}
	</style>
	<button type="button" class="button tsl-editor-btn tsl-insert-event">
		<span class="dashicons dashicons-plus-alt2"></span>
		<?php esc_html_e( 'Insert Event Block', 'thestandard-life' ); ?>
	</button>
	<button type="button" class="button tsl-editor-btn tsl-insert-event-bulk">
		<span class="dashicons dashicons-images-alt2"></span>
		<?php esc_html_e( 'Insert Events from Images', 'thestandard-life' ); ?>
	</button>
	<button type="button" class="button tsl-editor-btn tsl-paste-events">
		<span class="dashicons dashicons-clipboard"></span>
		<?php esc_html_e( 'Paste Events from Doc', 'thestandard-life' ); ?>
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

	wp_enqueue_script(
		'tsl-editor-paste',
		TSL_URL . 'assets/js/editor-paste.js',
		array( 'jquery', 'media-editor' ),
		TSL_VERSION,
		true
	);
	wp_localize_script( 'tsl-editor-paste', 'TSL_PASTE', array(
		'title'        => __( 'Paste events from your document', 'thestandard-life' ),
		'pasteHere'    => __( 'Paste everything at once', 'thestandard-life' ),
		'detected'     => __( 'Detected', 'thestandard-life' ),
		'awaiting'     => __( 'Paste text to see what will be added.', 'thestandard-life' ),
		'foundN'       => __( '%d events found', 'thestandard-life' ),
		'insert'       => __( 'Insert', 'thestandard-life' ),
		'insertN'      => __( 'Insert %d events', 'thestandard-life' ),
		'cancel'       => __( 'Cancel', 'thestandard-life' ),
		'pickPhotos'   => __( 'Pick photos next, in this order', 'thestandard-life' ),
		'photoFrame'   => __( 'Pick photos in event order', 'thestandard-life' ),
		'usePhotos'    => __( 'Use these photos', 'thestandard-life' ),
		'nothingYet'   => __( 'Nothing is inserted until you confirm.', 'thestandard-life' ),
		'bodyLabel'    => __( 'description', 'thestandard-life' ),
		'nothingFound' => __( 'title only', 'thestandard-life' ),
	) );
	wp_add_inline_style( 'wp-admin', tsl_paste_dialog_css() );
}

/**
 * Styles for the paste dialog. Kept inline because it is a single admin screen.
 *
 * @return string
 */
function tsl_paste_dialog_css() {
	return '
	.tsl-paste-overlay{position:fixed; inset:0; z-index:160000; background:rgba(0,0,0,.6); display:flex; align-items:center; justify-content:center; padding:24px;}
	.tsl-paste-modal{background:#fff; border-radius:4px; width:100%; max-width:900px; max-height:90vh; display:flex; flex-direction:column;}
	.tsl-paste-head{display:flex; align-items:center; justify-content:space-between; padding:14px 18px; border-bottom:1px solid #dcdcde; font-size:15px;}
	.tsl-paste-x{font-size:22px; line-height:1; text-decoration:none; color:#646970; cursor:pointer;}
	.tsl-paste-body{display:grid; grid-template-columns:minmax(0,1.2fr) minmax(0,1fr); gap:18px; padding:18px; overflow:auto;}
	.tsl-paste-label{display:block; font-size:12px; color:#646970; margin-bottom:6px;}
	.tsl-paste-body textarea{width:100%; font-family:Menlo,Consolas,monospace; font-size:12px; line-height:1.7;}
	.tsl-paste-preview{border:1px solid #dcdcde; border-radius:4px; padding:12px; background:#f6f7f7; min-height:180px; max-height:340px; overflow:auto;}
	.tsl-paste-empty{color:#646970; margin:0; font-size:13px;}
	.tsl-paste-count{margin:0 0 10px; font-weight:600; color:#2271b1;}
	.tsl-paste-item{border-top:1px solid #dcdcde; padding-top:8px; margin-top:8px; font-size:13px;}
	.tsl-paste-item:first-of-type{border-top:0; padding-top:0; margin-top:0;}
	.tsl-paste-item span{display:block; color:#646970; font-size:12px; margin-top:2px;}
	.tsl-paste-photos{display:block; margin-top:12px; font-size:12px; color:#646970;}
	.tsl-paste-foot{display:flex; align-items:center; justify-content:space-between; gap:8px; padding:12px 18px; border-top:1px solid #dcdcde; background:#f6f7f7;}
	.tsl-paste-note{font-size:12px; color:#646970;}
	@media (max-width:782px){ .tsl-paste-body{grid-template-columns:1fr;} }
	';
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
		'time'      => __( 'Time:', 'thestandard-life' ),
		'when'      => __( 'When:', 'thestandard-life' ),
		'where'     => __( 'Where:', 'thestandard-life' ),
		'admission' => __( 'Admission:', 'thestandard-life' ),
		'booking'   => __( 'Booking:', 'thestandard-life' ),
		'info'      => __( 'More Info:', 'thestandard-life' ),
		'frame'   => __( 'Select event images (you can pick several at once)', 'thestandard-life' ),
		'useThem' => __( 'Use these images', 'thestandard-life' ),
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
			'<strong>' + L.admission + '</strong> <br />' +
			'<strong>' + L.booking + '</strong> <br />' +
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
