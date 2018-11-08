<?php
/**
 * Template for displaying a Review
 *
 * @package bmcr
 * @since bmcr 1.0
 */
?>

<?php get_header();?>

<?php while ( have_posts() ) : the_post();
	
	//static fields
	$bmcr_id = get_field('bmcr_id');
	$citation = get_field('citation'); 
	$book_title = get_field('title');
	$isbn = get_field('isbn');
	$oclc_number = get_field('oclc_number');
	$book_preview = get_field('book_preview');
	$purchase_book = get_field('purchase_book');
?>

<main>
	
	<article id="post-<?php the_ID(); ?>" class="container-fluid">
		
		<div class="entry-header row">
			
			<div class="col-sm-10 offset-sm-1">
		
				<small class="ref-id">BMCR <?php echo $bmcr_id; ?></small>
				
				<h2 class="entry-title"><?php the_title(); ?></h2>
				
				<div class="entry-citation"><?php echo $citation; ?></div>
			
				<div class="entry-links">
				
					<div class="entry-btns">
									
						<?php if ($book_preview): ?>
						
							<a href="<?php echo $book_preview; ?>" class="btn btn-primary" target="_blank">Book Preview</a>
							
						<?php endif; ?>
						
						<?php if ($purchase_book): ?>
						
							<a href="<?php echo $purchase_book; ?>" class="btn btn-secondary" target="_blank">Purchase Book</a>
						
						<?php endif; ?>
					
					</div>
				
					<?php if (get_field('relationships')): ?>
					
					<a href="#responses" class="responses-anchor"><p><?php echo $relationship_count = count(get_field('relationships')); ?> Responses</p></a>
					
					<?php endif; ?>
				
				</div>
			
			</div>
		
		</div><!-- .entry-header -->
		
		
		<div class="entry-meta row">
			
			<div class="col-sm-10 offset-sm-1">
			
				<h4>Review by 
				
				<?php get_template_part( 'template-parts/content', 'entrymeta' ); ?>
				
				</h4>
			
			</div>
		
		</div><!-- .entry-meta -->
		
		<div class="entry-content row">
			
			<div class="col-sm-10 offset-sm-1">
			
				<?php the_content(); ?>
			
			</div>
			
		</div><!-- .entry-content -->
		
		
		<div class="entry-footer row">
			
			<div class="col-sm-10 offset-sm-1">
			
			<?php
				if(get_the_tag_list()) {
				echo get_the_tag_list('<ul class="tag-wrapper"><li>','</li><li>','</li></ul>');
				}
			?>
			
			</div>
			
		</div><!-- .entry-footer -->
		
		<aside id="responses" class="row">
			
			<div class="col-sm-10 offset-sm-1">
			
			<div class="responses-header">
				
				<h2>Responses</h2>
				
				<a href="#"><p>Response Guidelines</p></a>
				<a href="#"><p>Submit a Response</p></a>
				
			</div>
			
			<?php 

				$posts = get_field('relationships');
				
				if( $posts ): ?>
				    <div>
				    <?php foreach( $posts as $post): // variable must be called $post (IMPORTANT)
					    
				        setup_postdata($post);
					        
					    get_template_part( 'template-parts/content', 'referenceresponse' );
						
					endforeach; ?>
				    </div>
				    <?php wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>
			<?php endif; ?>
			
			</div>	
		
		</aside><!--/#responses -->

        <?php 
			$posts = get_field('rel_pubs');

			if( $posts ): ?>
                <aside id="rel-pubs" class="row">
			
				    <div class="col-sm-10 offset-sm-1">				
							
						<h2>Related publications</h2>
					
					    <ul>
					    <?php foreach( $posts as $post): // variable must be called $post (IMPORTANT)
					        
					        setup_postdata($post);
					        
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
			
			        </div>
			
		        </aside><!-- /#rel-pubs -->
        <?php endif; ?>
		
		<aside id="comments-wrapper" class="row">
			
			<div class="col-sm-10 offset-sm-1">
			
			<h2>Comments</h2>
				
				<?php  //If comments are open or we have at least one comment, load up the comment template. 
				
					if ( comments_open() || get_comments_number() ) :
					
						comments_template();
						
					endif;
				?>
			
			</div>
		
		</aside><!-- /#comments -->
			
	
	</article>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>