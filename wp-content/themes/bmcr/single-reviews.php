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
	
	<article id="post-<?php the_ID(); ?>">
		
		<div class="entry-header">
	
			<small>BMCR <?php echo $bmcr_id; ?></small>
			
			<h1 class="entry-title"><?php the_title(); ?></h1>
			
			<p><?php echo $citation; ?></p>
			
			<?php if ($book_preview): ?>
			
				<a href="<?php echo $book_preview; ?>" target="_blank">Book Preview</a>
				
			<?php endif; ?>
			
			<?php if ($purchase_book): ?>
			
				<a href="<?php echo $purchase_book; ?>" target="_blank">Purchase Book</a>
			
			<?php endif; ?>
			
			<?php if (get_field('relationships')): ?>
			
			<a href="#responses"><p><?php echo $relationship_count = count(get_field('relationships')); ?> Responses</p></a>
			
			<?php endif; ?>
		
		</div>
		
		
		<div class="entry-meta">
			
			<h4>Review by Sort out Reviewer Data</h4>
			
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

				$posts = get_field('relationships');
				
				if( $posts ): ?>
				    <div>
				    <?php foreach( $posts as $post): // variable must be called $post (IMPORTANT) ?>
				        <?php setup_postdata($post); ?>
				        <div>
				            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				            <p>Original Review by</p>
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