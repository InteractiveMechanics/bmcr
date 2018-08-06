<?php
/**
 * The main template file
 *
 * This is the most generic template file in a WordPress theme
 * and one of the two required files for a theme (the other being style.css).
 * It is used to display a page when nothing more specific matches a query.
 * E.g., it puts together the home page when no home.php file exists.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bmcr
 */

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

		<?php
		if ( have_posts() ) : ?>

			
			<div class="page-header">
				<h1 class="page-title">Publications</h1>
				
				<ul>
					<li><a href="">All</a></li>
					<li><a href="">Reviewer</a></li>
					<li><a href="">Author</a></li>
					<li><a href="">Year</a></li>
					<ul><a href="">Type</a></ul>
					<ul>
					<?php wp_get_archives('type=yearly'); ?>
					</ul>
		
					<li><a href="">Subject</a></li>
					</ul>
										
			</div><!-- .page-header -->

			<?php
			/* Start the Loop */
			while ( have_posts() ) :
				the_post();
			

				/*
				 * Include the Post-Type-specific template for the content.
				 * If you want to override this in a child theme, then include a file
				 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
				 */
				 
				$post_type = get_post_type( $post->ID ); if ($post_type == 'reviews'):
				 
				get_template_part( 'template-parts/content', 'referencereview' );
				
				elseif ($post_type == 'articles'):
				
				get_template_part( 'template-parts/content', 'referencearticle' );
				
				else:
				
				get_template_part( 'template-parts/content', 'referenceresponse' );
				
				endif;

			endwhile;

	

		else :

			get_template_part( 'template-parts/content', 'none' );

		endif;
		?>

		</main><!-- #main -->
	</div><!-- #primary -->

<?php

get_footer();
