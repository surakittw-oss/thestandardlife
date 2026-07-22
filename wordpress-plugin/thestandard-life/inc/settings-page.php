<?php
/**
 * "Homepage Settings" admin page — a plain settings form (not the
 * Customizer) under the plugin's own "THE STANDARD LIFE" admin menu, for
 * the curated editorial blocks that were hardcoded in the prototype
 * (Editor's Letter, Podcast, Upcoming Event, Pull Quote, issue label).
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Field definitions, grouped into sections: section key => array(
 *   'title' => ..., 'fields' => array( option_key => array( label, type, default ) )
 * ).
 *
 * @return array
 */
function tsl_home_sections() {
	return array(
		'issue'    => array(
			'title'  => __( 'Issue', 'thestandard-life' ),
			'fields' => array(
				'tsl_issue_label' => array( __( '"In This Issue" label', 'thestandard-life' ), 'text', 'In this issue · No. 12' ),
			),
		),
		'letter'   => array(
			'title'  => __( "Editor's Letter", 'thestandard-life' ),
			'fields' => array(
				'tsl_letter_kicker' => array( __( 'Label', 'thestandard-life' ), 'text', "Editor's Letter" ),
				'tsl_letter_title'  => array( __( 'Quote', 'thestandard-life' ), 'text', '"เราเขียนเล่มนี้ เพื่อคนที่รู้สึกว่าเร็วเกินไป"' ),
				'tsl_letter_body'   => array( __( 'Body text', 'thestandard-life' ), 'textarea', 'ฉบับนี้เราชวนทุกคนหยุด หายใจ แล้วดูว่าอะไรคือสิ่งที่เรายังอยากทำในชีวิต อ่านจดหมายจากบรรณาธิการฉบับเต็ม' ),
				'tsl_letter_author' => array( __( 'Author line', 'thestandard-life' ), 'text', 'ชนาภา โตเจริญ · Editor-in-Chief' ),
				'tsl_letter_avatar' => array( __( 'Avatar image', 'thestandard-life' ), 'image', '' ),
			),
		),
		'podcast'  => array(
			'title'  => __( 'Podcast (Listen block)', 'thestandard-life' ),
			'fields' => array(
				'tsl_podcast_kicker' => array( __( 'Label', 'thestandard-life' ), 'text', 'Podcast · EP.48' ),
				'tsl_podcast_title'  => array( __( 'Title', 'thestandard-life' ), 'text', 'Slow Mornings — ชีวิตช้าๆ ในเมืองเร็วๆ' ),
				'tsl_podcast_meta'   => array( __( 'Meta text', 'thestandard-life' ), 'text', '42 min · New episode' ),
				'tsl_podcast_image'  => array( __( 'Cover image', 'thestandard-life' ), 'image', '' ),
				'tsl_podcast_link'   => array( __( 'Link URL', 'thestandard-life' ), 'url', '' ),
			),
		),
		'event'    => array(
			'title'  => __( 'Upcoming Event', 'thestandard-life' ),
			'fields' => array(
				'tsl_event_title' => array( __( 'Title', 'thestandard-life' ), 'text', 'Standard Life Market — Bangsaen' ),
				'tsl_event_meta'  => array( __( 'Meta text', 'thestandard-life' ), 'text', '24–25 พฤษภาคม · ตลาด · ดนตรี · อาหารชุมชน' ),
				'tsl_event_link'  => array( __( 'Link URL', 'thestandard-life' ), 'url', '' ),
			),
		),
		'quote'    => array(
			'title'  => __( 'Pull Quote', 'thestandard-life' ),
			'fields' => array(
				'tsl_quote_text' => array( __( 'Quote text', 'thestandard-life' ), 'textarea', 'ชีวิตดีๆ ไม่ได้ใหญ่ มันเล็กและซ้ำๆ ทุกวัน' ),
				'tsl_quote_cite' => array( __( 'Attribution', 'thestandard-life' ), 'text', '— THE STANDARD LIFE' ),
			),
		),
	);
}

/**
 * Flat map of every field, option key => array( label, type, default ).
 * Used by tsl_opt() to look up defaults.
 *
 * @return array
 */
function tsl_home_fields() {
	$flat = array();
	foreach ( tsl_home_sections() as $section ) {
		$flat = array_merge( $flat, $section['fields'] );
	}
	return $flat;
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
 * Register each option with the Settings API so options.php can save them.
 */
function tsl_register_settings() {
	foreach ( tsl_home_fields() as $key => $field ) {
		list( , $type, $default ) = $field;

		$sanitize = 'sanitize_text_field';
		if ( 'textarea' === $type ) {
			$sanitize = 'sanitize_textarea_field';
		} elseif ( 'url' === $type || 'image' === $type ) {
			$sanitize = 'esc_url_raw';
		}

		register_setting( 'tsl_home_settings_group', $key, array(
			'type'              => 'string',
			'sanitize_callback' => $sanitize,
			'default'           => $default,
		) );
	}
}
add_action( 'admin_init', 'tsl_register_settings' );

/**
 * Add the "Homepage Settings" submenu under the plugin's own CPT menu.
 */
function tsl_add_settings_page() {
	add_submenu_page(
		'edit.php?post_type=' . TSL_CPT,
		__( 'LIFE Homepage Settings', 'thestandard-life' ),
		__( 'Homepage Settings', 'thestandard-life' ),
		'manage_options',
		'tsl-homepage-settings',
		'tsl_render_settings_page'
	);
}
add_action( 'admin_menu', 'tsl_add_settings_page' );

/**
 * Only load the media uploader JS on our own settings page.
 *
 * @param string $hook Current admin page hook suffix.
 */
function tsl_settings_page_assets( $hook ) {
	if ( 'life_post_page_tsl-homepage-settings' !== $hook ) {
		return;
	}
	wp_enqueue_media();
	wp_add_inline_script( 'media-editor', tsl_image_picker_js() );
}
add_action( 'admin_enqueue_scripts', 'tsl_settings_page_assets' );

/**
 * Inline JS: a generic WP media-frame picker for every ".tsl-image-picker"
 * button on the page (works regardless of how many image fields exist).
 *
 * @return string
 */
function tsl_image_picker_js() {
	return <<<JS
	jQuery(function(\$){
		var frame;
		\$(document).on('click', '.tsl-image-picker', function(e){
			e.preventDefault();
			var button = \$(this);
			var wrap = button.closest('.tsl-image-field');
			var input = wrap.find('input[type=hidden]');
			var preview = wrap.find('.tsl-image-preview');

			frame = wp.media({
				title: 'เลือกรูปภาพ',
				button: { text: 'ใช้รูปนี้' },
				multiple: false
			});
			frame.on('select', function(){
				var attachment = frame.state().get('selection').first().toJSON();
				var url = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
				input.val(attachment.url).trigger('change');
				preview.html('<img src="' + url + '" style="max-width:160px;height:auto;display:block;margin-bottom:8px;border:1px solid #dcdcde;border-radius:4px;">');
				wrap.find('.tsl-image-remove').show();
			});
			frame.open();
		});
		\$(document).on('click', '.tsl-image-remove', function(e){
			e.preventDefault();
			var wrap = \$(this).closest('.tsl-image-field');
			wrap.find('input[type=hidden]').val('').trigger('change');
			wrap.find('.tsl-image-preview').empty();
			\$(this).hide();
		});
	});
JS;
}

/**
 * Render one field's input control.
 *
 * @param string $key   Option name.
 * @param array  $field array( label, type, default ).
 */
function tsl_render_field( $key, $field ) {
	list( , $type, ) = $field;
	$value = tsl_opt( $key );

	switch ( $type ) {
		case 'textarea':
			printf(
				'<textarea name="%1$s" id="%1$s" rows="3" class="large-text">%2$s</textarea>',
				esc_attr( $key ),
				esc_textarea( $value )
			);
			break;

		case 'url':
			printf(
				'<input type="url" name="%1$s" id="%1$s" value="%2$s" class="regular-text" placeholder="https://">',
				esc_attr( $key ),
				esc_attr( $value )
			);
			break;

		case 'image':
			echo '<div class="tsl-image-field">';
			echo '<div class="tsl-image-preview">';
			if ( $value ) {
				echo '<img src="' . esc_url( $value ) . '" style="max-width:160px;height:auto;display:block;margin-bottom:8px;border:1px solid #dcdcde;border-radius:4px;">';
			}
			echo '</div>';
			printf( '<input type="hidden" name="%1$s" id="%1$s" value="%2$s">', esc_attr( $key ), esc_attr( $value ) );
			echo '<button type="button" class="button tsl-image-picker">' . esc_html__( 'เลือกรูปภาพ', 'thestandard-life' ) . '</button> ';
			printf(
				'<button type="button" class="button tsl-image-remove" style="%s">%s</button>',
				$value ? '' : 'display:none;',
				esc_html__( 'ลบรูป', 'thestandard-life' )
			);
			echo '</div>';
			break;

		default: // text
			printf(
				'<input type="text" name="%1$s" id="%1$s" value="%2$s" class="regular-text">',
				esc_attr( $key ),
				esc_attr( $value )
			);
	}
}

/**
 * Render the settings page.
 */
function tsl_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'LIFE Homepage Settings', 'thestandard-life' ); ?></h1>
		<p><?php esc_html_e( 'เนื้อหาส่วนคัดสรรที่แสดงบนหน้า /life/ — Editor\'s Letter, Podcast, Upcoming Event และ Pull Quote', 'thestandard-life' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'tsl_home_settings_group' ); ?>

			<?php foreach ( tsl_home_sections() as $section ) : ?>
				<h2 class="title"><?php echo esc_html( $section['title'] ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
						<?php foreach ( $section['fields'] as $key => $field ) : ?>
							<tr>
								<th scope="row"><label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $field[0] ); ?></label></th>
								<td><?php tsl_render_field( $key, $field ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>

			<?php submit_button( __( 'บันทึกการตั้งค่า', 'thestandard-life' ) ); ?>
		</form>
	</div>
	<?php
}
