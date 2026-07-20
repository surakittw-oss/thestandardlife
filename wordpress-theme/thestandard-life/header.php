<?php
/**
 * Header: <head>, masthead, and sticky navigation.
 *
 * @package thestandard-life
 */
?>
<!doctype html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="color-scheme" content="light dark">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="masthead">
	<div class="masthead-top">
		<div class="mh-left">
			<span class="mh-date"><?php echo esc_html( date_i18n( 'l, F j, Y' ) ); ?></span>
			<span class="mh-weather"><?php esc_html_e( '· Bangkok', 'thestandard-life' ); ?></span>
		</div>

		<div>
			<?php if ( has_custom_logo() ) : ?>
				<div class="logo"><?php the_custom_logo(); ?></div>
			<?php else : ?>
				<a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php bloginfo( 'name' ); ?>">
					<img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-tsl.png' ); ?>" alt="<?php bloginfo( 'name' ); ?>" class="logo-img">
				</a>
			<?php endif; ?>
			<div class="logo-sub">
				<span class="th"><?php bloginfo( 'description' ); ?></span>
				<span class="en"><?php esc_html_e( 'The Urban Guide to Well-being', 'thestandard-life' ); ?></span>
			</div>
		</div>

		<div class="mh-right">
			<button class="theme-toggle" id="themeToggle" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'thestandard-life' ); ?>" title="<?php esc_attr_e( 'Toggle dark mode', 'thestandard-life' ); ?>">
				<svg class="moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
				<svg class="sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
			</button>
			<button class="hamburger" id="hamburger" aria-label="<?php esc_attr_e( 'Menu', 'thestandard-life' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
			</button>
		</div>
	</div>

	<nav class="nav" id="mainNav">
		<div class="nav-hamburger-wrap">
			<span class="brand-hint"><?php esc_html_e( 'Menu', 'thestandard-life' ); ?></span>
			<button class="hamburger" id="navHamburger" aria-label="<?php esc_attr_e( 'Menu', 'thestandard-life' ); ?>" aria-expanded="false">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
			</button>
		</div>

		<?php
		if ( has_nav_menu( 'primary' ) ) {
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'container'      => false,
				'menu_class'     => '',
				'fallback_cb'    => false,
				'depth'          => 1,
			) );
		} else {
			// Fallback: list top-level categories so the nav is never empty.
			echo '<ul>';
			wp_list_categories( array(
				'title_li' => '',
				'number'   => 6,
			) );
			echo '</ul>';
		}
		?>

		<div class="nav-meta">
			<a class="search" href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" aria-label="<?php esc_attr_e( 'Search', 'thestandard-life' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
				<span class="search-label"><?php esc_html_e( 'Search', 'thestandard-life' ); ?></span>
			</a>
		</div>
	</nav>
</header>
