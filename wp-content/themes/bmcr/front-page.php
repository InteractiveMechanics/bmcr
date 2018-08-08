<?php
/**
 * The template for the homepage
 *
 * 
 *
 * @package bmcr
 */

get_header();
	$bkgr_img = get_field('bkgr_img');
	$bkgr_img_url = $bkgr_img['url'];
	$bkgr_img_alt = $bkgr_img['alt'];
	$hero_title = get_field('hero_title');
?>


<main id="main" class="site-main">
	<!-- check if there is a background image -->
	<?php if($bkgr_img): ?>
	
		<div class="jumbotron" style="background-image: url('<?php echo $bkgr_img_url; ?>')" alt="<?php echo $bkgr_img_alt; ?>">
	
	<?php else: ?>
		
		<div class="jumbotron">
	
	<?php endif; ?>
	
	
	<h1><?php echo $hero_title; ?></h1>
		
	
	<?php if( have_rows('hero_btns') ): 
		
		while ( have_rows('hero_btns') ) : the_row();
			$hero_btn_url = get_sub_field('btn_link'); 
			$hero_btn_text = get_sub_field('button_text');
	?>
        
        	<a href="<?php echo $hero_btn_url; ?>"><?php echo $hero_btn_text; ?></a>
	        	
		<?php endwhile; ?>
		
	<? endif; ?>
		
	</div><!-- /.jumbotron -->
	
	<div class="recent-posts">
		<h2>Recent Publications</h2>
		
	<!-- add Month in Review selection -->
		
	<div>
	<?php
		$args = array(
			'posts_per_page' => 20,
			'offset' => 0,
			'orderby' => 'post_date',
			'order' => 'DESC',
			'post_type' => array( 'articles', 'reviews', 'responses')
		);
		
		$recent_posts = new WP_query( $args );
	
	
		if ($recent_posts->have_posts() ):
			while ($recent_posts->have_posts() ):
				$recent_posts->the_post();
			 
				$post_type = get_post_type( $post->ID );		
		?>
		
			<?php if ($post_type == 'reviews'): ?>
			
			
			<div>
					<?php get_template_part( 'template-parts/content', 'referencereview' ); ?>
			</div>
				
			<?php elseif ($post_type == 'articles'): ?>
			
			<div>
				<?php get_template_part( 'template-parts/content', 'referencearticle' ); ?>
			</div>
					
			<?php elseif ($post_type == 'responses'): ?>

			<div>
				<?php get_template_part( 'template-parts/content', 'referenceresponse' ); ?>
			</div>
	
			<?php endif; ?>
		
	
		<?php endwhile; 
			
		endif;
		?>

	</div>
	</div>
	
		

</main>


<?php get_footer(); ?>
