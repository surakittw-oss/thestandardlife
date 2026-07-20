<?php
/**
 * Static page template.
 *
 * @package thestandard-life
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<div class="progress" id="progress"></div>

	<header class="art-head">
		<h1 class="art-title"><?php the_title(); ?></h1>
	</header>

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="art-hero-img"><?php the_post_thumbnail( 'full' ); ?></div>
	<?php endif; ?>

	<div class="art-wrap" style="grid-template-columns:minmax(0,1fr);">
		<div class="prose">
			<?php
			the_content();
			wp_link_pages();
			?>
		</div>
	</div>
	<?php
endwhile;

get_footer();
