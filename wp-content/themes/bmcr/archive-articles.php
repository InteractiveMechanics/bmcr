<?php
/**
 * Template Name: Articles Archive
 *
 * @package bmcr
 * @since bmcr 1.0
 */
get_header(); ?>


<main id="main" class="site-main">
	
	<?php if ( have_posts() ) : ?>
	
		<div class="page-header">
			<h1 class="page-title">Publications: Articles</h1>
			
			<?php get_template_part( 'template-parts/content', 'pageheader'); ?>
									
		</div><!-- .page-header -->
	
		<?php while ( have_posts() ) :  the_post();
			$post_id = get_the_ID();
			$bmcr_id = get_field('bmcr_id', $post_id); 
			
	
			
			get_template_part( 'template-parts/content', 'referencearticle' );		
		
		 endwhile; ?>
	
	
	<?php else: 
	
		get_template_part( 'template-parts/content', 'none' );
		
	
	endif; ?>

</main>

<?php get_footer(); ?>