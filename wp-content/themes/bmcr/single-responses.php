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
	
	<article id="post-<?php the_ID(); ?>">
	
		<div class="entry-header">
			
		<?php 

			//if the response refers to a review
			$posts = get_field('relationships');
			//if thre response refers to another response
			$responses = get_field('response_relationships');

			if( $posts ): ?>
			
				<?php foreach( $posts as $p ): // variable must NOT be called $post (IMPORTANT) ?>
		
					<h1 class="entry-title">Response: <?php the_title(); ?>. Response to <a href="<?php echo get_the_permalink($p->ID); ?>"><?php echo the_field('bmcr_id', $p->ID); ?></a></h1>
			
				<?php endforeach; 
			
			elseif ( $responses ):
			
				foreach( $responses as $r ): // variable must NOT be called $post (IMPORTANT) ?>
		
					<h1 class="entry-title">Response: <?php the_title(); ?>. Response to <a href="<?php echo get_the_permalink($r->ID); ?>"><?php echo the_field('bmcr_id', $r->ID); ?></a></h1>
			
				<?php endforeach; 

			endif;
				 
					
		?>
		
		
		
		<?php if (get_field('response_relationships') && get_field('relationships') ): ?>
			
			<a href="#responses"><p><?php echo $relationship_count = count(get_field('response_relationships')); ?> Responses</p></a>
			
		<?php endif; ?>
			
			
			
		
		
		</div>
		
		<!--TODO: sort out how to include responses-->
		
		<div class="entry-meta">
			
			<h4>Response by by Sort out Reviewer Data</h4>
			
			<h4>Affiliation, <a href="mailto:someone@example.com" target="_top">email address</a></h4>
			
			<h4><?php the_date(); ?></h4>
		
		</div>
		
		<div class="entry-content">
			
			<?php the_content(); ?>
			
		</div>
		
		
		<div class="entry-footer">
			
			<?php
				if(get_the_tag_list()) {
				echo get_the_tag_list('<ul><li>','</li><li>','</li></ul>');
				}
			?>
			
		</div>
			
		<aside>
			<h2>Related publications</h2>
			
			<?php 

				$posts = get_field('rel_pubs');

				if( $posts ): ?>
				    <ul>
				    <?php foreach( $posts as $post): // variable must be called $post (IMPORTANT) ?>
				        <?php setup_postdata($post); ?>
				        <li>
				        	<?php //TODO Configure custom fields for related publications ?>
				            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				        </li>
				    <?php endforeach; ?>
				    </ul>
				    <?php wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>
				    
			<?php endif; ?>
			
		</aside>
		
		<aside id="responses">
			<h2>Responses</h2>
			<small><a href="#">Response Guidelines</a></small>
			<small><a href="#">Submit a Response</a></small>
			
			<?php 

				$posts = get_field('response_relationships');
				
				if( get_field('response_relationships') && get_field('relationships') ): ?>
				    <div>
				    <?php foreach( $posts as $post): // variable must be called $post (IMPORTANT) ?>
				        <?php setup_postdata($post); ?>
				        <div>
				            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				            <p>Response by</p>
				    
				            
				        </div>
				    <?php endforeach; ?>
				    </div>
				    <?php wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>
			<?php endif; ?>
			
					
		
		</aside>

		
				
		<aside>
			<h2>Comments</h2>
			
			<?php  //If comments are open or we have at least one comment, load up the comment template. 
			
				if ( comments_open() || get_comments_number() ) :
				
					comments_template();
					
				endif;
			?>
		
		</aside>
			
	
	</article>

</main>


<?php endwhile; ?>

<?php get_footer(); ?>