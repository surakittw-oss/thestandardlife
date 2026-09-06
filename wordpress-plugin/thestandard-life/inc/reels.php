<?php
/**
 * The Reels strip on the LIFE landing page: a row of vertical YouTube Shorts
 * cards that refills itself.
 *
 * Everything here is deliberately key-free. YouTube publishes an RSS feed for
 * any playlist or channel (…/feeds/videos.xml), and oEmbed resolves a single
 * video's title — neither needs an API key, an OAuth token, or a quota. That
 * matters more than it sounds: the alternative (the Data API, or Instagram's
 * Graph API) hands you a credential that expires, and when it does the section
 * silently empties with nobody watching. Nothing here can expire.
 *
 * Clips are fetched by cron into an option and rendered server-side, so a page
 * cache serves the strip like any other markup — see inc/cache.php.
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Option holding the fetched clip list. */
const TSL_REELS_CACHE = 'tsl_reels_cache';

/** Option holding @handle → channel-ID lookups, so we resolve each one once. */
const TSL_REELS_RESOLVED = 'tsl_reels_resolved';

/** Cron hook name. */
const TSL_REELS_EVENT = 'tsl_reels_refresh';

/** How often the clip list is refetched, in seconds. */
const TSL_REELS_INTERVAL = 1800;

/**
 * Add the half-hourly cron interval. WordPress ships hourly at the shortest,
 * and an hour is long enough that a clip posted right after a run looks like
 * the feature is broken.
 *
 * @param array $schedules Registered schedules.
 * @return array
 */
function tsl_reels_cron_schedule( $schedules ) {
	$schedules['tsl_half_hourly'] = array(
		'interval' => TSL_REELS_INTERVAL,
		'display'  => __( 'ทุก 30 นาที (LIFE Reels)', 'thestandard-life' ),
	);
	return $schedules;
}
add_filter( 'cron_schedules', 'tsl_reels_cron_schedule' ); // phpcs:ignore WordPress.WP.CronInterval

/**
 * Make sure the refresh event exists.
 *
 * Scheduled from init rather than the activation hook, because a site that
 * already had this plugin active when the Reels feature shipped never fires
 * activation again — and would never schedule anything.
 */
function tsl_reels_schedule() {
	if ( ! wp_next_scheduled( TSL_REELS_EVENT ) ) {
		wp_schedule_event( time() + 60, 'tsl_half_hourly', TSL_REELS_EVENT );
	}
}
add_action( 'init', 'tsl_reels_schedule' );

/**
 * Drop the scheduled event when the plugin is switched off.
 */
function tsl_reels_unschedule() {
	wp_clear_scheduled_hook( TSL_REELS_EVENT );
}

/* -------------------------------------------------------------------------
 * Reading what the editor typed
 * ---------------------------------------------------------------------- */

/**
 * Pull an 11-character YouTube video ID out of any of the URL shapes a person
 * might paste — a Short, a watch link, a share link, an embed.
 *
 * @param string $url Anything the editor pasted.
 * @return string Video ID, or '' when there isn't one.
 */
function tsl_reels_video_id( $url ) {
	$url = trim( $url );
	if ( '' === $url ) {
		return '';
	}

	// Already a bare ID.
	if ( preg_match( '#^[A-Za-z0-9_-]{11}$#', $url ) ) {
		return $url;
	}

	$patterns = array(
		'#youtube\.com/shorts/([A-Za-z0-9_-]{11})#i',
		'#youtube\.com/live/([A-Za-z0-9_-]{11})#i',
		'#youtube\.com/embed/([A-Za-z0-9_-]{11})#i',
		'#youtu\.be/([A-Za-z0-9_-]{11})#i',
		'#[?&]v=([A-Za-z0-9_-]{11})#i',
	);
	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $url, $m ) ) {
			return $m[1];
		}
	}
	return '';
}

/**
 * Work out which RSS feed to read from whatever the editor put in the
 * "Playlist or channel" box.
 *
 * A playlist is the shape we want people to use: the team decides what goes in
 * it, so the strip shows exactly the clips they chose. A channel works too, but
 * its feed carries every upload — see tsl_reels_is_short() for how the long
 * ones get dropped.
 *
 * @param string $source  Pasted URL or bare ID.
 * @param bool   $resolve Allow the remote lookup a bare @handle needs. Only
 *                        true on the fetch path — a page render must never
 *                        block on an HTTP request.
 * @return array{url:string,kind:string,id:string} Empty url when unusable.
 */
function tsl_reels_feed( $source, $resolve = false ) {
	$none   = array( 'url' => '', 'kind' => '', 'id' => '' );
	$source = trim( $source );
	if ( '' === $source ) {
		return $none;
	}

	$feed = static function ( $kind, $id ) {
		return array(
			'url'  => 'https://www.youtube.com/feeds/videos.xml?' . $kind . '_id=' . rawurlencode( $id ),
			'kind' => $kind,
			'id'   => $id,
		);
	};

	// Bare IDs. Playlist IDs start PL/UU/FL/OL/LL; channel IDs start UC.
	if ( preg_match( '#^UC[A-Za-z0-9_-]{16,}$#', $source ) ) {
		return $feed( 'channel', $source );
	}
	if ( preg_match( '#^(?:PL|UU|FL|OL|LL)[A-Za-z0-9_-]{8,}$#', $source ) ) {
		return $feed( 'playlist', $source );
	}

	// A playlist URL, or a watch URL that happens to carry ?list=.
	if ( preg_match( '#[?&]list=([A-Za-z0-9_-]+)#i', $source, $m ) ) {
		return $feed( 'playlist', $m[1] );
	}
	if ( preg_match( '#youtube\.com/channel/(UC[A-Za-z0-9_-]+)#i', $source, $m ) ) {
		return $feed( 'channel', $m[1] );
	}

	// A handle or legacy custom URL — the channel ID isn't in the URL, so the
	// page has to be read once to find it.
	if ( preg_match( '#youtube\.com/(@[A-Za-z0-9_.\-]+|c/[^/?\#]+|user/[^/?\#]+)#i', $source, $m )
		|| preg_match( '#^(@[A-Za-z0-9_.\-]+)$#', $source, $m ) ) {
		$id = tsl_reels_resolve_channel( $m[1], $resolve );
		return $id ? $feed( 'channel', $id ) : $none;
	}

	return $none;
}

/**
 * Turn a @handle (or legacy /c/ or /user/ path) into a UC… channel ID.
 *
 * Resolved IDs are stored, because a handle only changes if someone renames the
 * channel — refetching a whole HTML page every 30 minutes to learn the same
 * answer would be wasteful.
 *
 * @param string $handle Handle or path fragment.
 * @param bool   $fetch  Whether the page may be fetched when it is not known yet.
 * @return string Channel ID, or '' if it could not be read.
 */
function tsl_reels_resolve_channel( $handle, $fetch = false ) {
	$handle = ltrim( $handle, '/' );
	$known  = get_option( TSL_REELS_RESOLVED, array() );
	if ( is_array( $known ) && ! empty( $known[ $handle ] ) ) {
		return $known[ $handle ];
	}
	if ( ! $fetch ) {
		return '';
	}

	$res = wp_remote_get( 'https://www.youtube.com/' . $handle, array(
		'timeout'    => 8,
		'user-agent' => 'Mozilla/5.0 (compatible; THE STANDARD LIFE/' . TSL_VERSION . ')',
	) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return '';
	}

	$body = wp_remote_retrieve_body( $res );
	if ( ! preg_match( '#"(?:channelId|externalId)":"(UC[A-Za-z0-9_-]+)"#', $body, $m )
		&& ! preg_match( '#channel/(UC[A-Za-z0-9_-]+)#', $body, $m ) ) {
		return '';
	}

	$known             = is_array( $known ) ? $known : array();
	$known[ $handle ]  = $m[1];
	update_option( TSL_REELS_RESOLVED, $known, false );
	return $m[1];
}

/* -------------------------------------------------------------------------
 * Fetching
 * ---------------------------------------------------------------------- */

/**
 * Is this video actually a Short?
 *
 * YouTube's feeds carry no duration and no "this is a Short" flag, so there is
 * nothing to filter on directly. What does distinguish them: /shorts/<id>
 * serves a page for a Short and redirects to /watch for anything else.
 *
 * Deliberately fails open — a network hiccup returns true and the clip stays.
 * A strip with one long video in it is a much smaller problem than an empty
 * strip, and this only ever runs inside cron.
 *
 * @param string $video_id Video ID.
 * @return bool
 */
function tsl_reels_is_short( $video_id ) {
	$res = wp_remote_head( 'https://www.youtube.com/shorts/' . $video_id, array(
		'timeout'     => 5,
		'redirection' => 0,
	) );
	if ( is_wp_error( $res ) ) {
		return true;
	}
	return 200 === (int) wp_remote_retrieve_response_code( $res );
}

/**
 * Read the clip list from YouTube's RSS feed.
 *
 * @param string $feed_url Feed URL.
 * @param bool   $filter   Drop non-Shorts (only worth doing for a channel feed).
 * @return array[] Items as array( id, title, published ).
 */
function tsl_reels_fetch_feed( $feed_url, $filter ) {
	if ( ! function_exists( 'simplexml_load_string' ) ) {
		return array();
	}

	$res = wp_remote_get( $feed_url, array( 'timeout' => 10 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return array();
	}

	// LIBXML_NONET blocks any network fetch the document asks for. Entity
	// substitution is deliberately NOT enabled — untrusted XML is untrusted XML,
	// even from YouTube.
	$previous = libxml_use_internal_errors( true );
	$xml      = simplexml_load_string( wp_remote_retrieve_body( $res ), 'SimpleXMLElement', LIBXML_NONET );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $xml || ! isset( $xml->entry ) ) {
		return array();
	}

	$items = array();
	foreach ( $xml->entry as $entry ) {
		$yt = $entry->children( 'http://www.youtube.com/xml/schemas/2015' );
		$id = isset( $yt->videoId ) ? (string) $yt->videoId : '';
		if ( ! preg_match( '#^[A-Za-z0-9_-]{11}$#', $id ) ) {
			continue;
		}
		if ( $filter && ! tsl_reels_is_short( $id ) ) {
			continue;
		}
		$items[] = array(
			'id'        => $id,
			'title'     => isset( $entry->title ) ? (string) $entry->title : '',
			'published' => isset( $entry->published ) ? (string) $entry->published : '',
		);
		if ( count( $items ) >= 12 ) {
			break;
		}
	}
	return $items;
}

/**
 * Read the titles of hand-picked clips via oEmbed.
 *
 * Manual mode exists so the strip still works when there is no playlist yet, or
 * when an editor wants to override the feed for a week. Only the title needs
 * looking up; the thumbnail comes straight off the video ID.
 *
 * @return array[] Items as array( id, title, published ).
 */
function tsl_reels_fetch_manual() {
	$items = array();
	for ( $i = 1; $i <= 6; $i++ ) {
		$id = tsl_reels_video_id( tsl_opt( 'tsl_reels_manual_' . $i ) );
		if ( ! $id ) {
			continue;
		}

		$title = '';
		$res   = wp_remote_get(
			'https://www.youtube.com/oembed?format=json&url=' . rawurlencode( 'https://www.youtube.com/watch?v=' . $id ),
			array( 'timeout' => 8 )
		);
		if ( ! is_wp_error( $res ) && 200 === wp_remote_retrieve_response_code( $res ) ) {
			$data = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( is_array( $data ) && ! empty( $data['title'] ) ) {
				$title = (string) $data['title'];
			}
		}

		$items[] = array(
			'id'        => $id,
			'title'     => $title,
			'published' => '',
		);
	}
	return $items;
}

/**
 * Refresh the stored clip list. Runs on cron; safe to call directly.
 *
 * A failed fetch keeps whatever is already stored rather than blanking the
 * strip — YouTube being briefly unreachable should not take the section off the
 * homepage.
 *
 * @return bool Whether the stored list changed.
 */
function tsl_reels_refresh() {
	$source = tsl_opt( 'tsl_reels_source' );
	if ( 'off' === $source ) {
		return false;
	}

	if ( 'manual' === $source ) {
		$items = tsl_reels_fetch_manual();
	} else {
		$feed = tsl_reels_feed( tsl_opt( 'tsl_reels_playlist' ), true );
		if ( ! $feed['url'] ) {
			return false;
		}
		$items = tsl_reels_fetch_feed( $feed['url'], 'channel' === $feed['kind'] );
	}

	$cache = get_option( TSL_REELS_CACHE, array() );
	$cache = is_array( $cache ) ? $cache : array();

	if ( empty( $items ) ) {
		// Note the attempt so we don't hammer a broken feed every request.
		$cache['checked'] = time();
		update_option( TSL_REELS_CACHE, $cache, true );
		return false;
	}

	$before = ( isset( $cache['items'] ) && is_array( $cache['items'] ) ) ? wp_list_pluck( $cache['items'], 'id' ) : array();
	$after  = wp_list_pluck( $items, 'id' );

	// Autoloaded: small, and read on every landing-page render — and the
	// staleness check below runs on every request, so a query per request is
	// the alternative.
	update_option( TSL_REELS_CACHE, array(
		'items'   => $items,
		'fetched' => time(),
		'checked' => time(),
	), true );

	if ( $before !== $after ) {
		// New clips mean the cached landing page is now out of date.
		tsl_purge_landing_cache();
		return true;
	}
	return false;
}
add_action( TSL_REELS_EVENT, 'tsl_reels_refresh' );

/**
 * Top up the list when cron has not run for a while.
 *
 * WP-Cron only fires on a page view, and on a heavily cached site the landing
 * page may go a long time without reaching PHP. Hooked to shutdown so the fetch
 * happens after the response has been sent and never delays a reader.
 */
function tsl_reels_maybe_refresh() {
	if ( wp_doing_cron() || wp_doing_ajax() || 'off' === tsl_opt( 'tsl_reels_source' ) ) {
		return;
	}

	$cache   = get_option( TSL_REELS_CACHE, array() );
	$checked = ( is_array( $cache ) && ! empty( $cache['checked'] ) ) ? (int) $cache['checked'] : 0;

	// Twice the cron interval: by then cron has clearly not been running.
	if ( time() - $checked < TSL_REELS_INTERVAL * 2 ) {
		return;
	}
	tsl_reels_refresh();
}
add_action( 'shutdown', 'tsl_reels_maybe_refresh', 99 );

/**
 * Refetch immediately when the source is changed, so an editor who pastes a
 * playlist sees the result on the next page load instead of in half an hour.
 *
 * @param string $option Option name that changed.
 */
function tsl_reels_refresh_on_save( $option ) {
	if ( 0 !== strpos( $option, 'tsl_reels_' ) || TSL_REELS_CACHE === $option ) {
		return;
	}
	// The form saves option by option, so this fires several times per submit.
	// add_action() keys on the callback, so the repeats collapse into one run.
	add_action( 'shutdown', 'tsl_reels_refresh', 98 );
}
add_action( 'updated_option', 'tsl_reels_refresh_on_save' );
add_action( 'added_option', 'tsl_reels_refresh_on_save' );

/* -------------------------------------------------------------------------
 * Rendering
 * ---------------------------------------------------------------------- */

/**
 * The clips to show, already trimmed to the configured count.
 *
 * @return array[]
 */
function tsl_reels_items() {
	if ( 'off' === tsl_opt( 'tsl_reels_source' ) ) {
		return array();
	}

	$cache = get_option( TSL_REELS_CACHE, array() );
	$items = ( is_array( $cache ) && ! empty( $cache['items'] ) ) ? $cache['items'] : array();
	if ( empty( $items ) ) {
		return array();
	}

	$count = (int) tsl_opt( 'tsl_reels_count' );
	return array_slice( $items, 0, $count > 0 ? $count : 6 );
}

/* -------------------------------------------------------------------------
 * Admin status
 * ---------------------------------------------------------------------- */

/**
 * Say, on the settings page itself, whether the last fetch actually worked.
 *
 * Without this the only way to find out is to open the homepage and look — and
 * when the strip is empty there is nothing to distinguish "the playlist ID is
 * wrong" from "the playlist is empty" from "cron never ran".
 *
 * @param string $section Section being rendered.
 */
function tsl_reels_settings_intro( $section ) {
	if ( 'reels' !== $section ) {
		return;
	}

	$source = tsl_opt( 'tsl_reels_source' );
	$cache  = get_option( TSL_REELS_CACHE, array() );
	$items  = ( is_array( $cache ) && ! empty( $cache['items'] ) ) ? $cache['items'] : array();
	$when   = ( is_array( $cache ) && ! empty( $cache['fetched'] ) ) ? (int) $cache['fetched'] : 0;

	echo '<p class="description" style="max-width:46em;">';
	esc_html_e( 'ช่อง "Playlist หรือ Channel" วางได้ทั้งลิงก์ playlist, ลิงก์ช่อง หรือ @handle', 'thestandard-life' );
	echo '<br><strong>' . esc_html__( 'แนะนำให้ใช้ playlist', 'thestandard-life' ) . '</strong> ';
	esc_html_e( '— ทีมคุมเองได้ว่าคลิปไหนขึ้นหน้าแรก ถ้าใส่เป็นช่อง ระบบจะดึงคลิปล่าสุดมาแล้วคัดเฉพาะที่เป็น Shorts ให้', 'thestandard-life' );
	echo '<br>';
	esc_html_e( 'ช่อง "คลิปที่ 1–6" ใช้เฉพาะตอนเลือก "ใส่ลิงก์เอง" เท่านั้น', 'thestandard-life' );
	echo '</p>';

	if ( 'off' === $source ) {
		return;
	}

	if ( $items ) {
		$note = sprintf(
			/* translators: 1: number of clips, 2: human-readable time difference */
			esc_html__( 'ตอนนี้มี %1$d คลิปพร้อมแสดง · อัปเดตล่าสุดเมื่อ %2$s ที่แล้ว', 'thestandard-life' ),
			count( $items ),
			$when ? esc_html( human_time_diff( $when ) ) : '—'
		);
		printf( '<div class="notice notice-success inline" style="margin:0 0 8px;"><p>%s</p></div>', $note ); // phpcs:ignore WordPress.Security.EscapeOutput
		return;
	}

	$hint = ( 'manual' === $source )
		? esc_html__( 'ยังไม่มีคลิป — ใส่ลิงก์ในช่อง "คลิปที่ 1–6" แล้วกดบันทึก', 'thestandard-life' )
		: esc_html__( 'ยังไม่มีคลิป — ตรวจว่าใส่ playlist/channel ถูกต้อง และ playlist ตั้งเป็น Public แล้วกดบันทึกอีกครั้ง', 'thestandard-life' );
	printf( '<div class="notice notice-warning inline" style="margin:0 0 8px;"><p>%s</p></div>', $hint ); // phpcs:ignore WordPress.Security.EscapeOutput
}
add_action( 'tsl_home_settings_section_intro', 'tsl_reels_settings_intro' );

/**
 * The "watch all" destination — whatever the editor pasted, or the channel.
 *
 * @return string
 */
function tsl_reels_more_link() {
	$more = tsl_opt( 'tsl_reels_more' );
	if ( $more ) {
		return $more;
	}

	$feed = tsl_reels_feed( tsl_opt( 'tsl_reels_playlist' ) );
	if ( 'playlist' === $feed['kind'] ) {
		return 'https://www.youtube.com/playlist?list=' . $feed['id'];
	}
	if ( 'channel' === $feed['kind'] ) {
		return 'https://www.youtube.com/channel/' . $feed['id'] . '/shorts';
	}
	return '';
}
