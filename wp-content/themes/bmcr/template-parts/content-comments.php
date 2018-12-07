<aside id="comments-wrapper" class="row">
	
	<div class="col-sm-10 offset-sm-1 col-md-8 offset-md-2">
		
		<h2>Comments</h2>
		
		<?php  //If comments are open or we have at least one comment, load up the comment template. 
		
			if ( comments_open() || get_comments_number() ) :
			
				comments_template();
				
			endif;
		?>
	
	</div>

</aside>