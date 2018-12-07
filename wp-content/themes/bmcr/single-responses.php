<?php
/**
 * Template for displaying a Response
 *
 * @package bmcr
 * @since bmcr 1.0
 */
?>

<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();	

    $bmcr_id = get_field('bmcr_id');
?>

<main>
	
	<article id="post-<?php the_ID(); ?>" class="container-fluid">
	
		<div class="entry-header row">
		
    		<div class="col-sm-10 offset-sm-1 col-md-8 offset-md-2">
    			
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
    				
                        <small class="ref-id">BMCR <?php echo $bmcr_id; ?></small>
    					<h1 class="entry-title">Response: <?php the_title(); ?></h1>
                        <h4>Response to <a href="<?php echo get_the_permalink($p->ID); ?>"><?php echo the_field('bmcr_id', $p->ID); ?></a></h4>
    			
    				<?php endforeach; 
    			
    			elseif ( $responses ):
    			
    				foreach( $responses as $r ): // variable must NOT be called $post (IMPORTANT) ?>
    		
                        <small class="ref-id">BMCR <?php echo $bmcr_id; ?></small>
    					<h1 class="entry-title">Response: <?php the_title(); ?></h1>
                        <h4>Response to <a href="<?php echo get_the_permalink($r->ID); ?>"><?php echo the_field('bmcr_id', $r->ID); ?></a></h4>
    			
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
				
		<div class="entry-meta row">
			
			<div class="col-sm-10 offset-sm-1 col-md-8 offset-md-2">
			
				<h4>Response by</h4>
				
				<?php get_template_part( 'template-parts/content', 'entrymeta' ); ?>
							
			</div>
		
		</div><!-- .entry-meta -->
		
		<div class="entry-content row">
			
			<div class="col-sm-10 offset-sm-1 col-md-8 offset-md-2">
			
			<?php the_content(); ?>
			
			</div>
			
		</div><!--/.entry-content -->
		
		
		<div class="entry-footer row">
			
			<div class="col-sm-10 offset-sm-1 col-md-8 offset-md-2">
			
			<?php
				if(get_the_tag_list()) {
				echo get_the_tag_list('<ul class="tag-wrapper"><li>','</li><li>','</li></ul>');
				}
			?>
			
			</div>
			
		</div>
		
        <?php 
    
			$posts = get_field('response_relationships');
			
			if( get_field('response_relationships') && get_field('relationships') ): ?>

        		<aside id="responses" class="row">
        			
        			<div class="col-sm-10 offset-sm-1">
        			
            			<div class="responses-header">
            				
            				<h2>Responses</h2>
            				
            				<a href="<?php echo esc_url( get_page_link(180) ); ?>"><p>Response Guidelines</p></a>
            				<a href="mailto:bmcr@bmcreview.org"><p>Submit a Response</p></a>
            				
            			</div>
            
    				    <div>
        				    <?php foreach( $posts as $post): // variable must be called $post (IMPORTANT) 
        				       
        				       get_template_part( 'template-parts/content', 'referenceresponse' );
        
        				       
        				    endforeach; ?>
    				    </div>
                        <?php wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>
        					
        			</div>
        		
        		</aside><!--/#responses -->
        <?php endif; ?>

		<?php 

			$posts = get_field('rel_pubs');
			
			if( $posts ): ?>

                <?php get_template_part( 'template-parts/content', 'related' ); ?>
        		
        <?php endif; ?>
				
		<?php get_template_part( 'template-parts/content', 'comments' ); ?>
			
	
	</article>

</main>


<?php endwhile; ?>

<?php get_footer(); ?>