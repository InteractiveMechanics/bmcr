<?php
/**
 * Template Name: Reviews Archive
 *
 * @package bmcr
 * @since bmcr 1.0
 */
get_header(); ?>


<main id="main" class="site-main">
	
	<?php if ( have_posts() ) : ?>

			<div class="page-header">
				<h1 class="page-title">Available Books</h1>
				<ul>
					<li><a href="">All</a>
					<li><a href="">Author</a></li>
					<li><a href="">Subject</a></li>
									
			</div><!-- .page-header -->
			
			
			
			<?php while ( have_posts() ) :  the_post();
				$post_id = get_the_ID();
				$bmcr_id = get_field('bmcr_id', $post_id); 
				$isbn = get_field('isbn', $post_id);
				$book_author = get_field('book_author', $post_id);
				$publisher = get_field('publisher', $post_id);
				$pub_date = get_field('pub_date', $post_id);
				$pub_location = get_field('pub_location', $post_id);
				
			?>
			
			<?php if (get_post_status() !== 'publish'): ?>
			
			<?php get_template_part( 'template-parts/content', 'referencereview' ); ?>
			
			<h1>i am a thing</h1>
			
			<?php else: ?>

			<p><a href="#">Apply to review this book</a></p>
			
			<?php endif; ?>

			<?php endwhile; ?>
			
	<?php else: 
	
		get_template_part( 'template-parts/content', 'none' );
		
	
	endif; ?>

		
</main>

<?php get_footer(); ?>