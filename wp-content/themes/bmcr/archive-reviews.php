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
				<h1>Available Books</h1>
				<ul>
					<li><a href="">All</a>
					<li><a href="">Author</a></li>
									
			</div><!-- .page-header -->
			
			
			
			<?php while ( have_posts() ) :  the_post();
				$post_id = get_the_ID(); 
				$isbn = get_field('isbn', $post_id);
				$book_author = get_field('book_author', $post_id);
				$publisher = get_field('publisher', $post_id);
				$pub_date = get_field('pub_date', $post_id);
				
			?>

				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
				<p><?php echo $book_author; ?></p>
				<p>
					<?php if ($publisher): ?>
					
						<?php echo $publisher; ?>;
						
					<?php endif; ?>
					
					<?php if ($pub_date): ?>
					
						<?php echo $pub_date; ?>
					
					<?php endif; ?>
					
					<?php if ($publisher || $pub_date): ?>
					
						<?php echo 	'|'; ?>
					
					<?php endif; ?>
					
					
					<?php if ($isbn): ?>
					
						ISBN <?php echo $isbn; ?>
					 
					<?php endif; ?>
					
					 </p>
				<p><a href="#">Apply to review this book</a></p>

			<?php endwhile; ?>

			
	<?php endif; ?>	

</main>

<?php get_footer(); ?>