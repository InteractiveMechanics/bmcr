<?php
/**
 * Template Name: Apply to Review Form
 *
 * This is the template that displays Apply to Review form
 
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bmcr
 */

get_header();
?>


	<div id="primary" class="content-area">
		<main id="main" class="site-main">

		<?php
		while ( have_posts() ) :
			the_post();

			get_template_part( 'template-parts/content', 'formpage' );

			

		endwhile; // End of the loop.
		?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();
