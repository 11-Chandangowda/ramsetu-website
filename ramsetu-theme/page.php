<?php
defined( 'ABSPATH' ) || exit;
get_header();
while ( have_posts() ) :
	the_post();
	$parent = wp_get_post_parent_id( get_the_ID() );
	rs_page_hero( $parent ? get_the_title( $parent ) : '', get_the_title(), '', 'orange' );
	?>
	<section class="section">
		<div class="container narrow prose">
			<?php the_content(); ?>
		</div>
	</section>
	<?php
endwhile;
get_footer();
