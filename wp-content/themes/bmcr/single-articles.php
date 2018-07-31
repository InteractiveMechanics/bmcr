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
?>

<main>
	
	<article id="post-<?php the_ID(); ?>">
		
		<div class="entry-header">
	
			
			<h1 class="entry-title"><?php the_title(); ?></h1>
			
			
		
		</div>
		
		
		<div class="entry-meta">
			
			<h4>Article by Sort out What is Happening with Article Authors</h4>
			
			<h4>Affiliation, <a href="mailto:someone@example.com" target="_top">email address</a></h4>
			
			<h4><?php the_date(); ?></h4>
		
		</div>
		
		<div class="entry-content">
			
			<?php the_content(); ?>
			
		</div>
		
		
		<div class="entry-footer">
			
			
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