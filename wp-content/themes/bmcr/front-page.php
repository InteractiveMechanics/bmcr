<?php
/**
 * The template for the homepage
 *
 * 
 *
 * @package bmcr
 */

get_header();
?>


<main id="main" class="site-main">
	
	<?php get_template_part( 'template-parts/content', 'hero' ); ?>
			
	
	<div class="recent-posts">
		<h2>Recent Publications</h2>
		
	<!-- add Month in Review selection -->
		
	<div>
	<?php
		$args = array(
			'posts_per_page' => 20,
			'offset' => 0,
			'orderby' => 'post_date',
			'order' => 'DESC',
			'post_type' => array( 'articles', 'reviews', 'responses')
		);
		
		$recent_posts = new WP_query( $args );
	
	
		if ($recent_posts->have_posts() ):
			while ($recent_posts->have_posts() ):
				$recent_posts->the_post();
			 
				$post_type = get_post_type( $post->ID );		
		?>
		
			<?php if ($post_type == 'reviews'): ?>
			
			
			<div>
					<?php get_template_part( 'template-parts/content', 'referencereview' ); ?>
			</div>
				
			<?php elseif ($post_type == 'articles'): ?>
			
			<div>
				<?php get_template_part( 'template-parts/content', 'referencearticle' ); ?>
			</div>
					
			<?php elseif ($post_type == 'responses'): ?>

			<div>
				<?php get_template_part( 'template-parts/content', 'referenceresponse' ); ?>
			</div>
	
			<?php endif; ?>
		
	
		<?php endwhile; 
			
		endif;
		?>

	</div>
	</div>
	
		

</main>


<?php get_footer(); ?>
