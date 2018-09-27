<?php
/**
 * Template for displaying a Response
 *
 * @package bmcr
 * @since bmcr 1.0
 */
?>

<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();	?>

<main>
	
	<article id="post-<?php the_ID(); ?>" class="container-fluid">
	
		<div class="entry-header row">
		
		<div class="col-sm-10 offset-sm-1">
			
		<?php 

			//if the response refers to a review
			$posts = get_field('relationships');
			//if the response refers to another response
			$responses = get_field('response_relationships');
			//we only want to get the first response we don't want to get child responses
			$i = 0;
			$max = 1;

			if( $posts ): ?>
			
				<?php foreach( $posts as $p ): // variable must NOT be called $post (IMPORTANT) ?>
				
		
					<h2 class="entry-title">Response: <?php the_title(); ?>. Response to <a href="<?php echo get_the_permalink($p->ID); ?>"><?php echo the_field('bmcr_id', $p->ID); ?></a></h2>
			
				<?php endforeach; 
			
			elseif ( $responses ):
			
				foreach( $responses as $r ): // variable must NOT be called $post (IMPORTANT) ?>
		
					<h2 class="entry-title">Response: <?php the_title(); ?>. Response to <a href="<?php echo get_the_permalink($r->ID); ?>"><?php echo the_field('bmcr_id', $r->ID); ?></a></h2>
			
				<?php endforeach; 

			endif;
				 
					
		?>
		
		<div class="entry-links response-entry-links">
		
		<?php if (get_field('response_relationships') && get_field('relationships') ): ?>
			
			<a href="#responses" class="responses-anchor"><p><?php echo $relationship_count = count(get_field('response_relationships')); ?> Responses</p></a>
			
		<?php endif; ?>
		
		</div>
			
			
			
		</div>
		
		</div><!-- /.entry-header -->
		
		<!--TODO: sort out how to include responses-->
		
		<div class="entry-meta row">
			
			<div class="col-sm-10 offset-sm-1">
			
				<h4>Response by
				<?php get_template_part( 'template-parts/content', 'entrymeta' ); ?>
				
				</h4>
			
			</div>
		
		</div><!--/.entry-meta -->
		
		<div class="entry-content row">
			
			<div class="col-sm-10 offset-sm-1">
			
			<?php the_content(); ?>
			
			</div>
			
		</div><!--/.entry-content -->
		
		
		<div class="entry-footer row">
			
			<div class="col-sm-10 offset-sm-1">
			
			<?php
				if(get_the_tag_list()) {
				echo get_the_tag_list('<ul class="tag-wrapper"><li>','</li><li>','</li></ul>');
				}
			?>
			
			</div>
			
		</div>
			
		<aside id="rel-pubs" class="row">
			
			<div class="col-sm-10 offset-sm-1">
			
			
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
			
		</aside>
		
		<aside id="responses" class="row">
			
			
			<div class="col-sm-10 offset-sm-1">
			
			<div class="responses-header">
				
				<h2>Responses</h2>
				
				<a href="#"><p>Response Guidelines</p></a>
				<a href="#"><p>Submit a Response</p></a>
				
			</div>

			
			<?php 

				$posts = get_field('response_relationships');
				
				if( get_field('response_relationships') && get_field('relationships') ): ?>
				    <div>
				    <?php foreach( $posts as $post): // variable must be called $post (IMPORTANT) 
				       
				       get_template_part( 'template-parts/content', 'referenceresponse' );

				       
				    endforeach; ?>
				    </div>
				    <?php wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>
			<?php endif; ?>
			
					
			</div>
		
		</aside><!--/#responses -->

		
				
		<aside  id="comments-wrapper" class="row">
			
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