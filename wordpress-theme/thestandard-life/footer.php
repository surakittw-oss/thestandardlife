<?php
/**
 * Footer.
 *
 * @package thestandard-life
 */
?>

<footer>
	<div class="foot-top">
		<div class="foot-brand">
			<div class="logo-lg">
				<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-tsl.png' ); ?>" alt="<?php bloginfo( 'name' ); ?>" style="height:60px; width:auto; filter:invert(1);">
			</div>
			<p><?php bloginfo( 'description' ); ?></p>
			<div class="socials">
				<a href="#" aria-label="Instagram"><svg viewBox="0 0 24 24"><path d="M12 2.2c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.42.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.42.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41a3.8 3.8 0 0 1-1.38-.9 3.8 3.8 0 0 1-.9-1.38c-.16-.42-.36-1.06-.41-2.23C2.21 15.58 2.2 15.2 2.2 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.42-.16 1.06-.36 2.23-.41C8.42 2.21 8.8 2.2 12 2.2M12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63A5.99 5.99 0 0 0 1.97 2.03C1.22 2.77.77 3.56.46 4.48.16 5.24-.04 6.12-.1 7.39-.16 8.67-.17 9.08-.17 12.34s.01 3.67.07 4.95c.06 1.27.26 2.15.56 2.91.32.92.76 1.71 1.51 2.45a6 6 0 0 0 2.17 1.4c.76.3 1.64.5 2.91.56 1.28.06 1.69.07 4.95.07s3.67-.01 4.95-.07c1.27-.06 2.15-.26 2.91-.56a6 6 0 0 0 2.17-1.4c.75-.74 1.19-1.53 1.51-2.45.3-.76.5-1.64.56-2.91.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.27-.26-2.15-.56-2.91a6 6 0 0 0-1.4-2.17A6 6 0 0 0 19.52.63c-.76-.3-1.64-.5-2.91-.56C15.33.01 14.92 0 11.66 0Z"/><path d="M12 5.84A6.16 6.16 0 1 0 18.16 12 6.16 6.16 0 0 0 12 5.84M12 16a4 4 0 1 1 4-4 4 4 0 0 1-4 4"/><circle cx="18.41" cy="5.59" r="1.44"/></svg></a>
				<a href="#" aria-label="YouTube"><svg viewBox="0 0 24 24"><path d="M23.5 6.2a3 3 0 0 0-2.1-2.1C19.5 3.5 12 3.5 12 3.5s-7.5 0-9.4.6A3 3 0 0 0 .5 6.2C0 8.1 0 12 0 12s0 3.9.5 5.8a3 3 0 0 0 2.1 2.1c1.9.6 9.4.6 9.4.6s7.5 0 9.4-.6a3 3 0 0 0 2.1-2.1C24 15.9 24 12 24 12s0-3.9-.5-5.8M9.6 15.6V8.4L15.8 12Z"/></svg></a>
				<a href="#" aria-label="TikTok"><svg viewBox="0 0 24 24"><path d="M19.6 6.3a5.8 5.8 0 0 1-3.5-1.2 5.7 5.7 0 0 1-2.2-4H10V16a2.5 2.5 0 1 1-2.5-2.5 2.5 2.5 0 0 1 .7.1V9.6a6.2 6.2 0 0 0-.7-.04A6.4 6.4 0 1 0 13.9 16V8.9a9.2 9.2 0 0 0 5.7 2z"/></svg></a>
				<a href="#" aria-label="Facebook"><svg viewBox="0 0 24 24"><path d="M24 12a12 12 0 1 0-13.88 11.86v-8.39H7.08V12h3.04V9.36c0-3 1.79-4.67 4.53-4.67a18.4 18.4 0 0 1 2.68.24v2.95h-1.51c-1.49 0-1.95.92-1.95 1.87V12h3.32l-.53 3.47h-2.79v8.39A12 12 0 0 0 24 12"/></svg></a>
			</div>
		</div>

		<?php
		$footer_menus = array(
			'footer-sections' => __( 'Sections', 'thestandard-life' ),
			'footer-more'     => __( 'More', 'thestandard-life' ),
			'footer-about'    => __( 'About', 'thestandard-life' ),
			'footer-family'   => __( 'The Family', 'thestandard-life' ),
		);
		foreach ( $footer_menus as $location => $label ) :
			if ( ! has_nav_menu( $location ) ) {
				continue;
			}
			?>
			<div>
				<h5><?php echo esc_html( $label ); ?></h5>
				<?php
				wp_nav_menu( array(
					'theme_location' => $location,
					'container'      => false,
					'menu_class'     => '',
					'fallback_cb'    => false,
					'depth'          => 1,
				) );
				?>
			</div>
			<?php
		endforeach;
		?>
	</div>

	<div class="foot-bottom">
		<div class="l">
			<span>&copy; <?php echo esc_html( date_i18n( 'Y' ) ); ?> <?php bloginfo( 'name' ); ?></span>
			<span><?php esc_html_e( 'All rights reserved', 'thestandard-life' ); ?></span>
		</div>
		<div class="l">
			<a href="<?php echo esc_url( home_url( '/privacy/' ) ); ?>"><?php esc_html_e( 'Privacy', 'thestandard-life' ); ?></a>
			<a href="<?php echo esc_url( home_url( '/terms/' ) ); ?>"><?php esc_html_e( 'Terms', 'thestandard-life' ); ?></a>
			<a href="<?php echo esc_url( get_feed_link() ); ?>"><?php esc_html_e( 'RSS', 'thestandard-life' ); ?></a>
		</div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
