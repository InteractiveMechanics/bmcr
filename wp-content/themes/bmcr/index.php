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

$posts_per_page = 20;
$paged = ( get_query_var('paged') ) ? get_query_var('paged') : 1;
$args = array(
	'posts_per_page' => $posts_per_page,
	'paged' => $paged,
    'post_type' => array(
        'reviews', 'responses', 'articles'
    )
);

if(isset($_GET['auth'])){
    $author = $_GET['auth'];
    $args = array(
	    'posts_per_page' => $posts_per_page,
	    'paged' => $paged,
        'post_type' => array(
            'reviews', 'responses'
        ),
        'meta_query' => array(
            array(
                'key'		=> 'books_$_book_author_last',
                'value'		=> '^[' . strtoupper($author) . strtolower($author) . ']',
                'compare'	=> 'REGEXP'
            )
        )
    );
}

if(isset($_GET['reviewer'])){
    $reviewer = $_GET['reviewer'];
    $args = array(
	    'posts_per_page' => $posts_per_pagex,
	    'paged' => $paged,
        'post_type' => array(
            'reviews', 'responses'
        ),
        'meta_query' => array(
            array(
                'key'		=> 'reviewers_$_reviewer_last_name',
                'value'		=> '^[' . strtoupper($reviewer) . strtolower($reviewer) . ']',
                'compare'	=> 'REGEXP'
            )
        )
    );
}

$query = new WP_Query($args);

?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">


            <div class="container-fluid">	
				<div class="page-header" class="row">
					<div class="col-sm-10 offset-sm-1 page-header-wrapper">
					<h1 class="page-title">Publications</h1>
				
				<?php get_template_part( 'template-parts/content', 'pageheader'); ?>

    			<?php /* closing tags for .page-header-wrapper and .row are in the pageheader template part */ ?>
    							
    			<div class="row">
    				<div class="col-sm-10 offset-sm-1">

                		<?php
                		if ( $query->have_posts() ) :

                			/* Start the Loop */
                			while ($query->have_posts() ) :
                				$query->the_post();
                			
                				/*
                				 * Include the Post-Type-specific template for the content.
                				 * If you want to override this in a child theme, then include a file
                				 * called content-___.php (where ___ is the Post Type name) and that will be used instead.
                				 */
                				 
                				$post_type = get_post_type( $post->ID ); if ($post_type === 'reviews'):
                				 
                				get_template_part( 'template-parts/content', 'referencereview' );
                				
                				elseif ($post_type === 'articles'):
                				
                				get_template_part( 'template-parts/content', 'referencearticle' );
                				
                				elseif ($post_type === 'responses') :
                				
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
																		
		                			<?php if(get_query_var('paged') == 0 && $query->max_num_pages == 1): ?>
										<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">Next  &raquo;</a></li>
									<?php elseif (get_query_var('paged') < $query->max_num_pages): ?>
										<li class="page-item"><?php next_posts_link( 'Next  &raquo;' ); ?></li>
									<?php else : ?>
										<li class="page-item disabled"><a class="page-link" href="#" tabindex="-1">Next  &raquo;</a></li>
									<?php endif; ?>
								</ul>
                			</nav>
							
                		<?php else :
                
                			get_template_part( 'template-parts/content', 'none' );
                
                		endif;

            		?>

				</div><!--/.col-sm-10 -->
			</div><!--/.row -->

		</main><!-- #main -->
	</div><!-- #primary -->

<?php

get_footer();
