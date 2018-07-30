<?php
/**
 * Template Name: Articles Archive
 *
 * @package bmcr
 * @since bmcr 1.0
 */
get_header(); ?>


<main id="main" class="site-main">
	
	<?php if ( have_posts() ) : ?>
	
		<div class="page-header">
			<h1 class="page-title">Publications</h1>
			
			<ul>
				<li><a href="">All</a></li>
				<li><a href="">Reviewer</a></li>
				<li><a href="">Author</a></li>
				<li><a href="">Year</a></li>
				<ul><a href="">Type</a></ul>
				<li><a href="">Subject</a></li>
			</ul>
									
		</div><!-- .page-header -->
	
		<?php while ( have_posts() ) :  the_post();
			$post_id = get_the_ID();
			$bmcr_id = get_field('bmcr_id', $post_id); 
			
		?>	
			<h5>BMCR <?php echo $bmcr_id; ?></h5>
			<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			<p>By Still Have to Sort out Author Data</p>		
		
		<?php endwhile; ?>
	
	
	<?php else: 
	
		get_template_part( 'template-parts/content', 'none' );
		
	
	<?php endif; ?>

</main>

<?php get_footer(); ?>