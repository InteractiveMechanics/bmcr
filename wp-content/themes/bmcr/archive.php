<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bmcr
 */

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

		<?php if ( have_posts() ) : ?>

			<header class="page-header">
				<?php
				the_archive_title( '<h1 class="page-title">', '</h1>' );
				?>
				
					
				<?php get_template_part( 'template-parts/content', 'pageheader'); ?>													
				
			</header><!-- .page-header -->

			<?php
			/* Start the Loop */
			while ( have_posts() ) :
				the_post();

				/*
				 * Include the Post-Type-specific template for the content.
				 * If you want to override this in a child theme, then include a file
				 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
				 */
				$post_type = get_post_type( $post->ID ); if ($post_type === 'reviews'):
				 
				get_template_part( 'template-parts/content', 'referencereview' );
				
				elseif ($post_type === 'articles'):
				
				get_template_part( 'template-parts/content', 'referencearticle' );
				
				elseif ($post_type === 'responses'):
				
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
