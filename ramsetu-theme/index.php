<?php
defined( 'ABSPATH' ) || exit;
get_header();
rs_page_hero( '', is_home() ? __( 'News & updates', 'ramsetu' ) : wp_strip_all_tags( get_the_archive_title() ), '', 'orange' );
?>
<section class="section">
	<div class="container narrow">
		<?php if ( have_posts() ) : ?>
			<?php while ( have_posts() ) : the_post(); ?>
				<article <?php post_class( 'post-item' ); ?>>
					<?php if ( is_singular() ) : ?>
						<div class="prose"><?php the_content(); ?></div>
					<?php else : ?>
						<h2 class="h3"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
						<p class="muted small"><?php echo esc_html( get_the_date() ); ?></p>
						<?php the_excerpt(); ?>
					<?php endif; ?>
				</article>
			<?php endwhile; ?>
			<?php the_posts_pagination(); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'Nothing here yet.', 'ramsetu' ); ?></p>
		<?php endif; ?>
	</div>
</section>
<?php
get_footer();
