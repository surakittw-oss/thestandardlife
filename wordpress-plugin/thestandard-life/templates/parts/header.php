<?php
/**
 * LIFE page shell — opening HTML, masthead, and sticky nav.
 * Included by plugin templates via tsl_get_header(). Self-contained on
 * purpose: it does NOT use the active theme's header.
 *
 * @package thestandard-life
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$tsl_home  = get_post_type_archive_link( TSL_CPT );
$tsl_terms = get_terms( array(
	'taxonomy'   => TSL_TAX,
	'hide_empty' => true,
	'number'     => 6,
	'orderby'    => 'count',
	'order'      => 'DESC',
) );
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
			<a class="logo" href="<?php echo esc_url( $tsl_home ); ?>" aria-label="THE STANDARD LIFE">
				<img src="<?php echo esc_url( TSL_URL . 'assets/img/logo-tsl.png' ); ?>" alt="THE STANDARD LIFE" class="logo-img">
			</a>
			<div class="logo-sub">
				<span class="th"><?php esc_html_e( 'คู่มือกิน ดื่ม เที่ยว และ Well-being ของคนเมืองที่อยากมีสุขภาพกายและใจที่ดีอย่างยั่งยืน', 'thestandard-life' ); ?></span>
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

		<ul>
			<?php
			if ( ! empty( $tsl_terms ) && ! is_wp_error( $tsl_terms ) ) {
				foreach ( $tsl_terms as $t ) {
					$link = get_term_link( $t );
					if ( is_wp_error( $link ) ) {
						continue;
					}
					echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( $t->name ) . '</a></li>';
				}
			} else {
				echo '<li><a href="' . esc_url( $tsl_home ) . '">' . esc_html__( 'All LIFE', 'thestandard-life' ) . '</a></li>';
			}
			?>
		</ul>

		<div class="nav-meta">
			<a class="search" href="<?php echo esc_url( home_url( '/?s=&post_type=' . TSL_CPT ) ); ?>" aria-label="<?php esc_attr_e( 'Search', 'thestandard-life' ); ?>">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
				<span class="search-label"><?php esc_html_e( 'Search', 'thestandard-life' ); ?></span>
			</a>
		</div>
	</nav>
</header>
