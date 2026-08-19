/* THE STANDARD LIFE — paste a week's events straight out of the source doc.
 *
 * The round-up documents arrive with every event already written out: a title
 * line, free prose, sometimes a bulleted schedule, then Time/When/Where/…
 * lines at the end. This turns that whole paste into finished article markup
 * in one step, instead of retyping it field by field.
 */
( function ( $ ) {
	'use strict';

	var T = window.TSL_PASTE || {};

	// Each label accepts its Thai spelling too, since the source docs mix both.
	var LABELS = [
		{ key: 'time',      canon: 'Time:',      re: /^(time|เวลา)\s*[:：]\s*(.*)$/i },
		{ key: 'when',      canon: 'When:',      re: /^(when|วันที่|วัน)\s*[:：]\s*(.*)$/i },
		{ key: 'where',     canon: 'Where:',     re: /^(where|สถานที่)\s*[:：]\s*(.*)$/i },
		{ key: 'admission', canon: 'Admission:', re: /^(admission|ค่าเข้า|ราคา|บัตร)\s*[:：]\s*(.*)$/i },
		{ key: 'booking',   canon: 'Booking:',   re: /^(booking|จอง|ลงทะเบียน)\s*[:：]\s*(.*)$/i },
		{ key: 'info',      canon: 'More Info:', re: /^(more\s*info|ข้อมูลเพิ่มเติม|ติดต่อ)\s*[:：]\s*(.*)$/i }
	];
	var ORDER = [ 'time', 'when', 'where', 'admission', 'booking', 'info' ];

	function canonOf( key ) {
		for ( var i = 0; i < LABELS.length; i++ ) {
			if ( LABELS[ i ].key === key ) { return LABELS[ i ].canon; }
		}
		return key;
	}

	function esc( s ) {
		return String( s ).replace( /&/g, '&amp;' ).replace( /</g, '&lt;' )
			.replace( />/g, '&gt;' ).replace( /"/g, '&quot;' );
	}

	// [label](https://…) is how the docs carry a named link; a bare URL is left
	// alone here and linked when the post renders.
	function inline( s ) {
		return esc( s ).replace(
			/\[([^\]]+)\]\((https?:\/\/[^\s)]+)\)/g,
			'<a href="$2" target="_blank" rel="noopener">$1</a>'
		);
	}

	function newEvent( title ) {
		return { title: title, body: [], meta: {} };
	}

	function parse( text ) {
		var lines = String( text ).replace( /\r/g, '' ).split( '\n' );
		var events = [];
		var cur = null;
		var inMeta = false;
		var lastKey = null;

		function commit() {
			if ( cur && cur.title ) { events.push( cur ); }
		}

		lines.forEach( function ( raw ) {
			// Pasted docs carry non-breaking spaces on their "blank" lines.
			var line = raw.replace( /\u00a0/g, ' ' ).trim();
			if ( ! line ) { return; }

			var label = null;
			var value = '';
			for ( var i = 0; i < LABELS.length; i++ ) {
				var m = line.match( LABELS[ i ].re );
				if ( m ) { label = LABELS[ i ]; value = ( m[ 2 ] || '' ).trim(); break; }
			}

			if ( label ) {
				if ( ! cur ) { cur = newEvent( '' ); }
				cur.meta[ label.key ] = value;
				inMeta = true;
				lastKey = label.key;
				return;
			}

			var bullet = line.match( /^[*\-–—•]\s+(.*)$/ );
			if ( bullet ) {
				var item = bullet[ 1 ].trim();
				if ( ! cur ) { return; }
				if ( inMeta && lastKey ) {
					// Bullets under "More Info:" are extra values for that label,
					// not a list in the body.
					cur.meta[ lastKey ] = cur.meta[ lastKey ] ? cur.meta[ lastKey ] + '\n' + item : item;
				} else {
					cur.body.push( { type: 'li', text: item } );
				}
				return;
			}

			// A plain line once the meta block has started means the next event.
			if ( ! cur || inMeta ) {
				commit();
				cur = newEvent( line );
				inMeta = false;
				lastKey = null;
				return;
			}
			cur.body.push( { type: 'p', text: line } );
		} );

		commit();
		return events;
	}

	function renderBody( body ) {
		var out = '';
		var i = 0;
		while ( i < body.length ) {
			if ( 'li' === body[ i ].type ) {
				var items = '';
				while ( i < body.length && 'li' === body[ i ].type ) {
					items += '<li>' + inline( body[ i ].text ) + '</li>';
					i++;
				}
				out += '<ul>' + items + '</ul>\n';
			} else {
				out += '<p>' + inline( body[ i ].text ) + '</p>\n';
				i++;
			}
		}
		return out;
	}

	function renderMeta( meta ) {
		var rows = [];
		ORDER.forEach( function ( key ) {
			var v = meta[ key ];
			if ( undefined === v || '' === v ) { return; }
			var value = v.split( '\n' ).map( inline ).join( ' <br />' );
			rows.push( '<strong>' + canonOf( key ) + '</strong> ' + value );
		} );
		return rows.length ? '<p class="event-meta">' + rows.join( ' <br />' ) + '</p>\n' : '';
	}

	function renderFigure( attachment ) {
		if ( ! attachment ) { return ''; }
		var sizes = attachment.sizes || {};
		var src = ( sizes.large || sizes.medium_large || sizes.full || {} ).url || attachment.url;
		return '<figure><img class="size-large wp-image-' + attachment.id + '" src="' + esc( src ) +
			'" alt="' + esc( attachment.alt || '' ) + '" /></figure>\n';
	}

	function renderEvent( ev, attachment ) {
		return '<h2>' + esc( ev.title ) + '</h2>\n' +
			renderFigure( attachment ) +
			renderBody( ev.body ) +
			renderMeta( ev.meta ) +
			'<hr />\n';
	}

	function fieldSummary( ev ) {
		var found = [];
		if ( ev.body.length ) { found.push( T.bodyLabel || 'description' ); }
		ORDER.forEach( function ( key ) {
			if ( ev.meta[ key ] ) { found.push( canonOf( key ).replace( ':', '' ) ); }
		} );
		return found.length ? found.join( ', ' ) : ( T.nothingFound || 'title only' );
	}

	function insertHtml( html ) {
		if ( window.wp && wp.media && wp.media.editor ) {
			wp.media.editor.insert( html );
		}
	}

	function openDialog() {
		var overlay = document.createElement( 'div' );
		overlay.className = 'tsl-paste-overlay';
		overlay.innerHTML =
			'<div class="tsl-paste-modal" role="dialog" aria-modal="true" aria-label="' + esc( T.title || 'Paste events' ) + '">' +
				'<div class="tsl-paste-head"><strong>' + esc( T.title || 'Paste events' ) + '</strong>' +
					'<button type="button" class="button-link tsl-paste-x" aria-label="' + esc( T.cancel || 'Cancel' ) + '">&times;</button></div>' +
				'<div class="tsl-paste-body">' +
					'<div><label class="tsl-paste-label" for="tsl-paste-src">' + esc( T.pasteHere || 'Paste everything at once' ) + '</label>' +
						'<textarea id="tsl-paste-src" rows="16" spellcheck="false"></textarea></div>' +
					'<div><span class="tsl-paste-label">' + esc( T.detected || 'Detected' ) + '</span>' +
						'<div class="tsl-paste-preview"><p class="tsl-paste-empty">' + esc( T.awaiting || 'Paste text to see what will be added.' ) + '</p></div>' +
						'<label class="tsl-paste-photos"><input type="checkbox" checked> ' + esc( T.pickPhotos || 'Pick photos next, in this order' ) + '</label>' +
					'</div>' +
				'</div>' +
				'<div class="tsl-paste-foot"><span class="tsl-paste-note">' + esc( T.nothingYet || 'Nothing is inserted until you confirm.' ) + '</span>' +
					'<span><button type="button" class="button tsl-paste-cancel">' + esc( T.cancel || 'Cancel' ) + '</button> ' +
					'<button type="button" class="button button-primary tsl-paste-go" disabled>' + esc( T.insert || 'Insert' ) + '</button></span></div>' +
			'</div>';
		document.body.appendChild( overlay );

		var $o = $( overlay );
		var $src = $o.find( '#tsl-paste-src' );
		var $preview = $o.find( '.tsl-paste-preview' );
		var $go = $o.find( '.tsl-paste-go' );
		var events = [];

		function close() { $o.remove(); $( document ).off( 'keydown.tslPaste' ); }

		function refresh() {
			events = parse( $src.val() );
			if ( ! events.length ) {
				$preview.html( '<p class="tsl-paste-empty">' + esc( T.awaiting || 'Paste text to see what will be added.' ) + '</p>' );
				$go.prop( 'disabled', true ).text( T.insert || 'Insert' );
				return;
			}
			var html = '<p class="tsl-paste-count">' + esc(
				( T.foundN || '%d events found' ).replace( '%d', events.length )
			) + '</p>';
			events.forEach( function ( ev, i ) {
				html += '<div class="tsl-paste-item"><strong>' + ( i + 1 ) + '. ' + esc( ev.title ) + '</strong>' +
					'<span>' + esc( fieldSummary( ev ) ) + '</span></div>';
			} );
			$preview.html( html );
			$go.prop( 'disabled', false ).text(
				( T.insertN || 'Insert %d events' ).replace( '%d', events.length )
			);
		}

		$src.on( 'input paste', function () { setTimeout( refresh, 0 ); } );
		$o.on( 'click', '.tsl-paste-cancel, .tsl-paste-x', close );
		$o.on( 'click', function ( e ) { if ( e.target === overlay ) { close(); } } );
		$( document ).on( 'keydown.tslPaste', function ( e ) { if ( 27 === e.keyCode ) { close(); } } );

		$o.on( 'click', '.tsl-paste-go', function () {
			if ( ! events.length ) { return; }
			var wantPhotos = $o.find( '.tsl-paste-photos input' ).is( ':checked' );

			if ( ! wantPhotos ) {
				insertHtml( events.map( function ( ev ) { return renderEvent( ev, null ); } ).join( '\n' ) );
				close();
				return;
			}

			var frame = wp.media( {
				title: ( T.photoFrame || 'Pick photos in event order' ),
				button: { text: ( T.usePhotos || 'Use these photos' ) },
				library: { type: 'image' },
				multiple: 'add'
			} );
			frame.on( 'select', function () {
				var picked = frame.state().get( 'selection' ).map( function ( m ) { return m.toJSON(); } );
				insertHtml( events.map( function ( ev, i ) {
					return renderEvent( ev, picked[ i ] || null );
				} ).join( '\n' ) );
				close();
			} );
			// Closing the picker without choosing still inserts the text.
			frame.on( 'escape', function () {
				insertHtml( events.map( function ( ev ) { return renderEvent( ev, null ); } ).join( '\n' ) );
				close();
			} );
			frame.open();
		} );

		$src.trigger( 'focus' );
	}

	$( document ).on( 'click', '.tsl-paste-events', function ( e ) {
		e.preventDefault();
		openDialog();
	} );

	// Exposed so the parser can be exercised directly when checking a new doc.
	window.tslParseEvents = parse;
} )( jQuery );
