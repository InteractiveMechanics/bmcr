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

    $bmcr_id = get_field('bmcr_id');
	$author_id =  get_the_author_meta('ID');
?>

<main>
	
	<article id="post-<?php the_ID(); ?>" class="container-fluid">
		
		<div class="entry-header row">
			
			<div class="col-sm-10 offset-sm-1 col-md-8 offset-md-2">
			
                <small class="ref-id">BMCR <?php echo $bmcr_id; ?></small>
				<h1 class="entry-title"><?php the_title(); ?></h1>
			
			</div>
			
		
		</div>
		
		<div class="entry-meta row">
			
			<div class="col-sm-10 offset-sm-1 col-md-8 offset-md-2">
			
			
			<?php if( get_field('show_entrymeta') ): ?>
				<h4>Article by
					<?php get_template_part( 'template-parts/content', 'entrymeta' ); ?>
					<!-- the closing h4 tag is included in the entrymeta content partial -->
			<?php else: ?>
				<h4 class="meta-date"><?php the_date(); ?></h4>
			<?php endif; ?>
			
			</div>
						
		
		</div>
		
		
		<div class="entry-content row">
			
			<div class="col-sm-10 offset-sm-1 col-md-8 offset-md-2">
			
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
					
        <?php 

			$posts = get_field('rel_pubs');
			
			if( $posts ): ?>

                <?php get_template_part( 'template-parts/content', 'related' ); ?>
        		
        <?php endif; ?>
		
		<?php if( get_field('show_comments') ): ?>	
    		
            <?php get_template_part( 'template-parts/content', 'comments' ); ?>

		<?php endif; ?>	
	
	</article>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>