<aside id="rel-pubs" class="row">
        			
	<div class="col-sm-10 offset-sm-1">

		<h2>Related Publications</h2>
	
	    <ul>
		  <?php foreach( $posts as $post): // variable must be called $post (IMPORTANT) ?>
	        <?php setup_postdata($post);
	        
	        	 
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

</aside>