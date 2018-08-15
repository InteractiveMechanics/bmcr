<?php
/**
 * Template Name: Responses Archive
 *
 * @package bmcr
 * @since bmcr 1.0
 */
get_header(); ?>


<main id="main" class="site-main">
	
	<?php if ( have_posts() ) : ?>
	
	<div class="page-header">
			<h1 class="page-title">Publications: Responses</h1>
			
			<?php get_template_part( 'template-parts/content', 'pageheader'); ?>
									
		</div><!-- .page-header -->
		
	<?php while ( have_posts() ) :  the_post();
	
		
	get_template_part( 'template-parts/content', 'referenceresponse' );		
	
	
	endwhile; ?>	

	<?php else: 
		
		get_template_part( 'template-parts/content', 'none' );
		
		
	endif; ?>

</main>

<?php get_footer(); ?>