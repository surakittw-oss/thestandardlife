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

/**
 * Bumped whenever a stored value could be wrong because of a bug rather than
 * because it went stale. Version 2 discards what version 1's handle lookup
 * resolved: it could land on a sibling channel, so both the mapping and the
 * clips fetched with it have to go.
 */
const TSL_REELS_SCHEMA = 2;

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
 * Throw away stored values that an older version may have got wrong.
 *
 * Nothing is re-fetched here — clearing the timestamps is enough, because the
 * staleness check on shutdown then treats the list as overdue and refills it on
 * the very next page load.
 */
function tsl_reels_maybe_upgrade() {
	if ( (int) get_option( 'tsl_reels_schema', 0 ) === TSL_REELS_SCHEMA ) {
		return;
	}
	delete_option( TSL_REELS_RESOLVED );
	delete_option( TSL_REELS_CACHE );
	update_option( 'tsl_reels_schema', TSL_REELS_SCHEMA, true );
}
add_action( 'init', 'tsl_reels_maybe_upgrade', 5 );

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
 * Two shapes work. A hand-made playlist gives the team exact control over what
 * appears. A channel is the low-effort option: point at it once and new Shorts
 * arrive on their own.
 *
 * For a channel we do NOT read the channel feed, which carries every upload
 * including long videos. YouTube maintains a hidden per-channel playlist of
 * that channel's Shorts, whose ID is the channel ID with "UC" swapped for
 * "UUSH" — Shorts-only by construction, in a single request, with no need to
 * probe each video. The channel feed stays as the fallback for a channel that
 * has no such playlist, and there tsl_reels_is_short() does the filtering.
 *
 * @param string $source  Pasted URL or bare ID.
 * @param bool   $resolve Allow the remote lookup a bare @handle needs. Only
 *                        true on the fetch path — a page render must never
 *                        block on an HTTP request.
 * @return array{url:string,kind:string,id:string,fallback:string} Empty url when unusable.
 */
function tsl_reels_feed( $source, $resolve = false ) {
	$none   = array( 'url' => '', 'kind' => '', 'id' => '', 'fallback' => '' );
	$source = trim( $source );
	if ( '' === $source ) {
		return $none;
	}

	$url = static function ( $kind, $id ) {
		return 'https://www.youtube.com/feeds/videos.xml?' . $kind . '_id=' . rawurlencode( $id );
	};

	$feed = static function ( $kind, $id ) use ( $url ) {
		return array(
			'url'      => $url( $kind, $id ),
			'kind'     => $kind,
			'id'       => $id,
			'fallback' => '',
		);
	};

	// A channel: read its Shorts playlist, and keep the raw channel feed in
	// reserve in case that playlist does not exist.
	$channel = static function ( $id ) use ( $url ) {
		return array(
			'url'      => $url( 'playlist', 'UUSH' . substr( $id, 2 ) ),
			'kind'     => 'channel',
			'id'       => $id,
			'fallback' => $url( 'channel', $id ),
		);
	};

	// Bare IDs. Playlist IDs start PL/UU/FL/OL/LL; channel IDs start UC.
	if ( preg_match( '#^UC[A-Za-z0-9_-]{16,}$#', $source ) ) {
		return $channel( $source );
	}
	if ( preg_match( '#^(?:PL|UU|FL|OL|LL)[A-Za-z0-9_-]{8,}$#', $source ) ) {
		return $feed( 'playlist', $source );
	}

	// A playlist URL, or a watch URL that happens to carry ?list=.
	if ( preg_match( '#[?&]list=([A-Za-z0-9_-]+)#i', $source, $m ) ) {
		return $feed( 'playlist', $m[1] );
	}
	if ( preg_match( '#youtube\.com/channel/(UC[A-Za-z0-9_-]+)#i', $source, $m ) ) {
		return $channel( $m[1] );
	}

	// A handle or legacy custom URL — the channel ID isn't in the URL, so the
	// page has to be read once to find it. Any trailing path (/shorts, /videos)
	// is ignored, so pasting the Shorts tab straight from the address bar works.
	if ( preg_match( '#youtube\.com/(@[A-Za-z0-9_.\-]+|c/[^/?\#]+|user/[^/?\#]+)#i', $source, $m )
		|| preg_match( '#^(@[A-Za-z0-9_.\-]+)$#', $source, $m ) ) {
		$id = tsl_reels_resolve_channel( $m[1], $resolve );
		return $id ? $channel( $id ) : $none;
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
 * Only the page's OWN identity is read, from the four places YouTube states it:
 * the canonical link, og:url, the itemprop identifier, and the channel metadata
 * block. A channel's front page also lists the other channels it features, so a
 * loose search for the first "channelId" on the page finds a sibling channel
 * roughly as often as the right one — THE STANDARD LIFE's front page carries
 * eight channel IDs, and the first belongs to THE STANDARD SPORT.
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

	$body     = wp_remote_retrieve_body( $res );
	$patterns = array(
		'#<link[^>]+rel="canonical"[^>]+href="https://www\.youtube\.com/channel/(UC[A-Za-z0-9_-]+)"#i',
		'#<meta[^>]+property="og:url"[^>]+content="https://www\.youtube\.com/channel/(UC[A-Za-z0-9_-]+)"#i',
		'#<meta[^>]+itemprop="identifier"[^>]+content="(UC[A-Za-z0-9_-]+)"#i',
		'#"channelMetadataRenderer":\{.*?"externalId":"(UC[A-Za-z0-9_-]+)"#s',
	);

	$m = null;
	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $body, $found ) ) {
			$m = $found;
			break;
		}
	}
	if ( ! $m ) {
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
 * The feed also names the channel it belongs to, which is carried back so the
 * settings page can show whose clips these are. Pointing at the wrong channel is
 * an easy mistake to make and an easy one to miss — the strip just quietly fills
 * with somebody else's videos.
 *
 * @param string $feed_url Feed URL.
 * @param bool   $filter   Drop non-Shorts (only worth doing for a channel feed).
 * @return array{items:array[],name:string} Items as array( id, title, published ).
 */
function tsl_reels_fetch_feed( $feed_url, $filter ) {
	$empty = array( 'items' => array(), 'name' => '' );

	if ( ! function_exists( 'simplexml_load_string' ) ) {
		return $empty;
	}

	$res = wp_remote_get( $feed_url, array( 'timeout' => 10 ) );
	if ( is_wp_error( $res ) || 200 !== wp_remote_retrieve_response_code( $res ) ) {
		return $empty;
	}

	// LIBXML_NONET blocks any network fetch the document asks for. Entity
	// substitution is deliberately NOT enabled — untrusted XML is untrusted XML,
	// even from YouTube.
	$previous = libxml_use_internal_errors( true );
	$xml      = simplexml_load_string( wp_remote_retrieve_body( $res ), 'SimpleXMLElement', LIBXML_NONET );
	libxml_clear_errors();
	libxml_use_internal_errors( $previous );

	if ( ! $xml || ! isset( $xml->entry ) ) {
		return $empty;
	}

	// A playlist feed names the channel under <author>; a channel feed repeats
	// it as the feed <title>.
	$name = isset( $xml->author->name ) ? (string) $xml->author->name : '';
	if ( '' === $name && isset( $xml->title ) ) {
		$name = (string) $xml->title;
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
	return array( 'items' => $items, 'name' => $name );
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

	$name = '';

	if ( 'manual' === $source ) {
		$items = tsl_reels_fetch_manual();
	} else {
		$feed = tsl_reels_feed( tsl_opt( 'tsl_reels_playlist' ), true );
		if ( ! $feed['url'] ) {
			return false;
		}

		// The primary feed is Shorts-only either way — a playlist the team
		// curates, or a channel's UUSH playlist — so nothing needs probing.
		$result = tsl_reels_fetch_feed( $feed['url'], false );

		// Only a channel has a fallback, and only reaching for it costs the
		// per-video Shorts check.
		if ( empty( $result['items'] ) && $feed['fallback'] ) {
			$result = tsl_reels_fetch_feed( $feed['fallback'], true );
		}

		$items = $result['items'];
		$name  = $result['name'];
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
		'name'    => $name,
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
	echo '<strong>' . esc_html__( 'ง่ายที่สุด:', 'thestandard-life' ) . '</strong> ';
	esc_html_e( 'วางลิงก์แท็บ Shorts ของช่องไปเลย เช่น youtube.com/@THE.STANDARDLIFE/shorts — อัป Shorts ใหม่เมื่อไหร่ หน้าแรกขึ้นเองภายใน 30 นาที', 'thestandard-life' );
	echo '<br>';
	esc_html_e( 'หรือใส่ลิงก์ playlist ถ้าอยากคุมเองว่าคลิปไหนขึ้นหน้าแรก (ระบบจะแสดงเรียงตามลำดับใน playlist)', 'thestandard-life' );
	echo '<br>';
	esc_html_e( 'ช่อง "คลิปที่ 1–6" ใช้เฉพาะตอนเลือก "ใส่ลิงก์เอง" เท่านั้น', 'thestandard-life' );
	echo '</p>';

	if ( 'off' === $source ) {
		return;
	}

	if ( $items ) {
		$name = ( is_array( $cache ) && ! empty( $cache['name'] ) ) ? $cache['name'] : '';
		$note = sprintf(
			/* translators: 1: number of clips, 2: human-readable time difference */
			esc_html__( 'ตอนนี้มี %1$d คลิปพร้อมแสดง · อัปเดตล่าสุดเมื่อ %2$s ที่แล้ว', 'thestandard-life' ),
			count( $items ),
			$when ? esc_html( human_time_diff( $when ) ) : '—'
		);
		// Naming the channel turns "is this the right one?" into something an
		// editor can see at a glance instead of having to open the homepage.
		if ( $name ) {
			$note .= '<br><strong>' . esc_html__( 'กำลังดึงจาก:', 'thestandard-life' ) . ' ' . esc_html( $name ) . '</strong>';
		}
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
