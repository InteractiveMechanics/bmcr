<?php
/**
 * Template for displaying an Article
 *
 * @package bmcr
 * @since bmcr 1.0
 */
?>

<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
	$author_id =  get_the_author_meta('ID');
?>

<main>
	
	<article id="post-<?php the_ID(); ?>" class="container-fluid">
		
		<div class="entry-header row">
			
			<div class="col-sm-10 offset-sm-1">
			
				<h2 class="entry-title"><?php the_title(); ?></h2>
			
			</div>
			
		
		</div>
		
		<?php if( get_field('show_entrymeta') ): ?>
		<div class="entry-meta row">
			
			<div class="col-sm-10 offset-sm-1">
			
			<h4>Article by
			<?php get_template_part( 'template-parts/content', 'entrymeta' ); ?>
			<!-- the closing h4 tag is included in the entrymeta content partial -->
			
			</div>			
		
		</div>
		<?php endif; ?>	
		
		<div class="entry-content row">
			
			<div class="col-sm-10 offset-sm-1">
			
			<?php the_content(); ?>
			
			<?php if (get_field('month_in_review')):
			
				$posts = get_field('mir_selections');

					if( $posts ): ?>
    
					<?php foreach( $posts as $post): // variable must be called $post (IMPORTANT) ?>
					
					<?php setup_postdata($post); ?>
					
						<div>
							
						<?php the_field('citation'); ?>
						
						</div> 
					
					<?php endforeach; ?>
   
					<?php wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>
					
					<?php endif; ?>
			
			<?php endif; ?>
		
			</div>
			
		</div>
		
		
		<div class="entry-footer row">
			
			
		</div>
			
		<aside class="row">
			
			<div class="col-sm-10 offset-sm-1">
				
<!-- 			<h2>Related publications</h2> -->
			
			<?php 

				$posts = get_field('rel_pubs');

				if( $posts ): ?>
					<h2>Related publications</h2>
				
				    <ul>
				    <?php foreach( $posts as $post): // variable must be called $post (IMPORTANT) ?>
				        <?php setup_postdata($post);
				        
				        	 
						$post_type = get_post_type( $post->ID ); if ($post_type == 'reviews'):
						 
						get_template_part( 'template-parts/content', 'referencereview' );
						
						elseif ($post_type == 'articles'):
						
						get_template_part( 'template-parts/content', 'referencearticle' );
						
						else:
						
						get_template_part( 'template-parts/content', 'referenceresponse' );
						
						endif;


				      
					endforeach; ?>
				    </ul>
				    <?php wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>
				    
			<?php endif; ?>
			
			</div>
			
		</aside>
		
				
		<aside class="row">
			
			<div class="col-sm-10 offset-sm-1">
				
			<h2>Comments</h2>
			
			<?php  //If comments are open or we have at least one comment, load up the comment template. 
			
				if ( comments_open() || get_comments_number() ) :
				
					comments_template();
					
				endif;
			?>
			
			</div>
		
		</aside>
			
	
	</article>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>