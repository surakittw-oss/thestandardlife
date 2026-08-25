/* THE STANDARD LIFE — "Insert Photo Album" button.
 *
 * Creating a gallery in the Classic Editor means going into Add Media and
 * finding "Create Gallery" in the sidebar, which is easy to miss. This opens
 * WordPress's own gallery flow directly, and inserts the same [gallery]
 * shortcode it would have produced — the album rendering is applied when the
 * post is displayed, so nothing here is specific to this plugin.
 */
( function ( $ ) {
	'use strict';

	$( document ).on( 'click', '.tsl-insert-album', function ( e ) {
		e.preventDefault();
		if ( ! window.wp || ! wp.media ) {
			return;
		}

		// The post frame owns the gallery states. "gallery" is the create step;
		// "gallery-library" is a different thing — adding to a gallery that
		// already exists — and starting there asks to extend nothing.
		// Titles and button labels are left to WordPress, which names each step
		// of the flow itself; overriding them here would mislabel later steps.
		var frame = wp.media( {
			frame: 'post',
			state: 'gallery',
			library: { type: 'image' },
			multiple: true
		} );

		// Both the frame and the gallery-edit state can relay the update, and
		// which one arrives varies; this guard keeps a single insertion either
		// way rather than leaving it to whichever fires first.
		var inserted = false;

		function insertFrom( selection ) {
			if ( inserted || ! selection ) {
				return;
			}
			var ids = selection.map( function ( m ) { return m.id; } );
			if ( ! ids.length ) {
				return;
			}
			inserted = true;
			wp.media.editor.insert( '[gallery ids="' + ids.join( ',' ) + '"]' );
			frame.close();
		}

		// Fired by the "Insert gallery" button once the images are arranged.
		frame.on( 'update', insertFrom );
		frame.state( 'gallery-edit' ).on( 'update', insertFrom );

		frame.open();
	} );
} )( jQuery );
