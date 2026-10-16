<?php
/**
 * Template Name: Policies Repository
 * Shows the page content, then lists child pages (each policy) automatically.
 */
defined( 'ABSPATH' ) || exit;
get_header();

rs_page_hero( __( 'Policies Repository', 'ramsetu' ), __( 'Policies & disclosures', 'ramsetu' ), '', 'blue' );
?>
<section class="section">
	<div class="container narrow">
		<?php
		while ( have_posts() ) {
			the_post();
			echo '<div class="prose">';
			the_content();
			echo '</div>';
			$children = get_pages( array( 'child_of' => get_the_ID(), 'sort_column' => 'menu_order,post_title' ) );
			if ( $children ) {
				echo '<ul class="doclist">';
				foreach ( $children as $c ) {
					printf( '<li><a href="%s">%s<span>%s</span>%s</a></li>', esc_url( get_permalink( $c ) ), rs_icon( 'file' ), esc_html( $c->post_title ), rs_icon( 'arrow' ) ); // phpcs:ignore
				}
				echo '</ul>';
			}
		}
		?>
	</div>
</section>
<?php
get_footer();
