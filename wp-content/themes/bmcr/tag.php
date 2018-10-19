<?php get_header(); ?>

<div id="primary" class="content-area">
	<main id="main" class="site-main">
	
		
	
	<div class="container-fluid">	
		<div class="page-header" class="row">
			<div class="col-sm-10 offset-sm-1 page-header-wrapper">
		
				<h1 class="page-title">Publications: <?php echo single_tag_title('', false); ?></h1>
				
				<?php get_template_part( 'template-parts/content', 'pageheader'); ?>
				<?php /* closing tags for .page-header-wrapper and .row are in the pageheader template part */ ?>

																		
				
	
	
			
		<div class="row">
				<div class="col-sm-10 offset-sm-1">

			
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
		
		</div><!--/.col-sm-10 -->
		</div><!--/.row -->
			


		
	
	</main>
</div>



<?php get_footer(); ?>