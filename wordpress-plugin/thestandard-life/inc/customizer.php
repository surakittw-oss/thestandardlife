<?php
/**
 * Customizer panel "LIFE Homepage" — lets an editor fill in the curated
 * editorial blocks that were hardcoded in the prototype (Editor's Letter,
 * Podcast, Upcoming Event, Pull Quote, issue label).
 *
 * Values are stored as options (type => 'option'), not theme_mods, so they
 * survive theme switches — important since this is a plugin, not a theme.
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field definitions: key => array( label, type, default ).
 * type: text | textarea | url | image
 *
 * @return array
 */
function tsl_home_fields() {
	return array(
		'tsl_issue_label'    => array( __( 'In This Issue — label', 'thestandard-life' ), 'text', 'In this issue · No. 12' ),

		'tsl_letter_kicker'  => array( __( "Editor's Letter — label", 'thestandard-life' ), 'text', "Editor's Letter" ),
		'tsl_letter_title'   => array( __( "Editor's Letter — quote", 'thestandard-life' ), 'text', '"เราเขียนเล่มนี้ เพื่อคนที่รู้สึกว่าเร็วเกินไป"' ),
		'tsl_letter_body'    => array( __( "Editor's Letter — body", 'thestandard-life' ), 'textarea', 'ฉบับนี้เราชวนทุกคนหยุด หายใจ แล้วดูว่าอะไรคือสิ่งที่เรายังอยากทำในชีวิต อ่านจดหมายจากบรรณาธิการฉบับเต็ม' ),
		'tsl_letter_author'  => array( __( "Editor's Letter — author line", 'thestandard-life' ), 'text', 'ชนาภา โตเจริญ · Editor-in-Chief' ),
		'tsl_letter_avatar'  => array( __( "Editor's Letter — avatar", 'thestandard-life' ), 'image', '' ),

		'tsl_podcast_kicker' => array( __( 'Podcast — label', 'thestandard-life' ), 'text', 'Podcast · EP.48' ),
		'tsl_podcast_title'  => array( __( 'Podcast — title', 'thestandard-life' ), 'text', 'Slow Mornings — ชีวิตช้าๆ ในเมืองเร็วๆ' ),
		'tsl_podcast_meta'   => array( __( 'Podcast — meta', 'thestandard-life' ), 'text', '42 min · New episode' ),
		'tsl_podcast_image'  => array( __( 'Podcast — image', 'thestandard-life' ), 'image', '' ),
		'tsl_podcast_link'   => array( __( 'Podcast — link', 'thestandard-life' ), 'url', '' ),

		'tsl_event_title'    => array( __( 'Upcoming Event — title', 'thestandard-life' ), 'text', 'Standard Life Market — Bangsaen' ),
		'tsl_event_meta'     => array( __( 'Upcoming Event — meta', 'thestandard-life' ), 'text', '24–25 พฤษภาคม · ตลาด · ดนตรี · อาหารชุมชน' ),
		'tsl_event_link'     => array( __( 'Upcoming Event — link', 'thestandard-life' ), 'url', '' ),

		'tsl_quote_text'     => array( __( 'Pull Quote — text', 'thestandard-life' ), 'textarea', 'ชีวิตดีๆ ไม่ได้ใหญ่ มันเล็กและซ้ำๆ ทุกวัน' ),
		'tsl_quote_cite'     => array( __( 'Pull Quote — attribution', 'thestandard-life' ), 'text', '— THE STANDARD LIFE' ),
	);
}

/**
 * Read a LIFE homepage option with its registered default.
 *
 * @param string $key Option key.
 * @return string
 */
function tsl_opt( $key ) {
	$fields  = tsl_home_fields();
	$default = isset( $fields[ $key ] ) ? $fields[ $key ][2] : '';
	return get_option( $key, $default );
}

/**
 * Register the Customizer section, settings, and controls.
 *
 * @param WP_Customize_Manager $wp_customize Customizer manager.
 */
function tsl_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'tsl_home', array(
		'title'       => __( 'LIFE Homepage', 'thestandard-life' ),
		'description' => __( 'Curated blocks shown on the /life/ landing page.', 'thestandard-life' ),
		'priority'    => 30,
	) );

	foreach ( tsl_home_fields() as $key => $field ) {
		list( $label, $type, $default ) = $field;

		$sanitize = 'sanitize_text_field';
		if ( 'textarea' === $type ) {
			$sanitize = 'sanitize_textarea_field';
		} elseif ( 'url' === $type || 'image' === $type ) {
			$sanitize = 'esc_url_raw';
		}

		$wp_customize->add_setting( $key, array(
			'type'              => 'option',
			'default'           => $default,
			'sanitize_callback' => $sanitize,
			'transport'         => 'refresh',
		) );

		if ( 'image' === $type ) {
			$wp_customize->add_control( new WP_Customize_Image_Control( $wp_customize, $key, array(
				'label'   => $label,
				'section' => 'tsl_home',
				'settings'=> $key,
			) ) );
		} else {
			$wp_customize->add_control( $key, array(
				'label'   => $label,
				'section' => 'tsl_home',
				'type'    => ( 'textarea' === $type ) ? 'textarea' : ( ( 'url' === $type ) ? 'url' : 'text' ),
			) );
		}
	}
}
add_action( 'customize_register', 'tsl_customize_register' );
