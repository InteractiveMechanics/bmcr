<?php get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
	
	<header class="page-header">
			
		
				<h1 class="page-title">Publications: <?php echo single_tag_title('', false); ?></h1>
				
				<?php get_template_part( 'template-parts/content', 'pageheader'); ?>

																		
				
	</header><!-- .page-header -->
	
	<!-- section -->
		<section>

			
		<?php
		$term = get_queried_object();
		$tag_id = ($term->term_id);
		
		$args = array(
			'offset' => 0,
			'orderby' => 'post_date',
			'order' => 'DESC',
			'tag_id' => $tag_id,
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
			


		</section>
		<!-- /section -->	
	
	</main>
</div>



<?php get_footer(); ?>