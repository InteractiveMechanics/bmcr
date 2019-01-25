<?php
/**
 * The template for displaying archive pages
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bmcr
 */

get_header();
global $wp_query;
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

		<?php if ( have_posts() ) : ?>

			<div class="container-fluid">	
				<div class="page-header" class="row">
					<div class="col-sm-10 offset-sm-1 page-header-wrapper">
				
				<?php
				the_archive_title( '<h1 class="page-title">', '</h1>' );
				?>
				
				<?php get_template_part( 'template-parts/content', 'pageheader'); ?>													
				
			<?php /* closing tags for .page-header-wrapper and .row are in the pageheader template part */ ?>
			
			<div class="row">
				<div class="col-sm-10 offset-sm-1">

					<?php
					/* Start the Loop */
					while ( have_posts() ) :
						the_post();
		
						/*
						 * Include the Post-Type-specific template for the content.
						 * If you want to override this in a child theme, then include a file
						 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
						 */
						$post_type = get_post_type( $post->ID ); 
						
						if ($post_type === 'reviews'):
						 
						get_template_part( 'template-parts/content', 'referencereview' );
						
						elseif ($post_type === 'articles'):
						
						get_template_part( 'template-parts/content', 'referencearticle' );
										
						elseif ($post_type === 'responses'):
						
						get_template_part( 'template-parts/content', 'referenceresponse' );			
						
						endif;
		
		
					endwhile; ?>
					
					<nav aria-label="Pagination">
						<ul class="pagination justify-content-center pagination-lg">
							<?php if(get_query_var('paged') < 2): ?>
								<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">&laquo; Previous</a></li>
							<?php else : ?>	
								<li class="page-item"><?php previous_posts_link( '&laquo; Previous' ); ?></li>
							<?php endif; ?>
																
                			<?php if(get_query_var('paged') == 0 && $wp_query->max_num_pages == 1): ?>
								<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">Next  &raquo;</a></li>
							<?php elseif (get_query_var('paged') < $wp_query->max_num_pages): ?>
								<li class="page-item"><?php next_posts_link( 'Next  &raquo;' ); ?></li>
							<?php else : ?>
								<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">Next  &raquo;</a></li>
							<?php endif; ?>
						</ul>
        			</nav>
		
				<?php 
				else :
		
					get_template_part( 'template-parts/content', 'none' );
		
				endif;
				?>
				
			</div><!--/.col-sm-10 -->
		</div><!--/.row -->


		</main><!-- #main -->
	</div><!-- #primary -->

<?php

get_footer();
