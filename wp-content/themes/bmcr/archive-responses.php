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
	
		
	<div class="container-fluid">	
		<div class="page-header" class="row">
			<div class="col-sm-10 offset-sm-1 page-header-wrapper">
				<h1 class="page-title">Publications</h1>
			
					<?php get_template_part( 'template-parts/content', 'pageheader'); ?>
									
	<?php /* closing tags for .page-header-wrapper and .row are in the pageheader template part */ ?>
		
	<div class="row">
		<div class="col-sm-10 offset-sm-1">
		
	<?php while ( have_posts() ) :  the_post();
	
		
	get_template_part( 'template-parts/content', 'referenceresponse' );		
	
	
	endwhile; ?>	

	<?php else: 
		
		get_template_part( 'template-parts/content', 'none' );
		
		
	endif; ?>
	
	</div><!--/.col-sm-10 -->
	</div><!--/.row -->

</main>

<?php get_footer(); ?>