


<div>
	<a href="<?php echo get_permalink( get_option( 'page_for_posts' ) ); ?>"><p>All</p></a>
	<a data-toggle="collapse" href="#toggle-reviewers" role="button" aria-expanded="false" aria-controls="toggle-reviewers"><p>Reviewer</p></a>
	<a data-toggle="collapse" href="#toggle-authors" role="button" aria-expanded="false" aria-controls="toggle-authors"><p>Author of Work</p></a>
	<a data-toggle="collapse" href="#toggle-types" role="button" aria-expanded="false" aria-controls="toggle-types"><p>Type</p></a>
	<a data-toggle="collapse" href="#toggle-years" role="button" aria-expanded="false" aria-controls="toggle-years"><p>Year</p></a>
	<a data-toggle="collapse" href="#toggle-subjects" role="button" aria-expanded="false" aria-controls="toggle-subjects" class="d-none"><p>Subject</p></a>	
</div>

<?php /* .opening tags for page-header-wrapper and .row are in the template */ ?>

	</div><!--/.page-header-wrapper-->						
</div><!-- /.row -->

<div class="row">
	<div class="col-sm-10 offset-sm-1">
		<div class="collapse" id="toggle-reviewers">
			<div class="card card-body">
				<p>Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident.</p>
								
	      	</div><!-- /.card -->
	    </div><!-- /.collapse -->
	    
	    <div class="collapse" id="toggle-authors">
			<div class="card card-body">
				<p>Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident.</p>
								
	      	</div><!-- /.card -->
	    </div><!-- /.collapse -->
	    
	    <div class="collapse" id="toggle-types">
			<div class="card card-body">
				<ul>				
					<li><a href="<?php echo get_post_type_archive_link( 'articles' ); ?>">Articles</a></li>
					<li><a href="<?php echo get_post_type_archive_link( 'responses' ); ?>">Responses</a></li>
					<li><a href="<?php echo get_post_type_archive_link( 'reviews' ); ?>">Reviews</a></li>
				</ul>				
	      	</div><!-- /.card -->
	    </div><!-- /.collapse -->
	    
	    <div class="collapse" id="toggle-years">
			<div class="card card-body">
				<ul>
					<?php wp_get_archives('type=yearly'); ?>
				</ul>		
	      	</div><!-- /.card -->
	    </div><!-- /.collapse -->
	    
	    <div class="collapse" id="toggle-subjects">
			<div class="card card-body">
				<ul>
					<?php 
					$tags = get_tags(); 
					foreach ($tags as $tag): ?>
						<li><a href="<?php echo get_tag_link($tag->term_id); ?>"><?php echo $tag->name; ?></a></li>										
					<?php endforeach; ?>
				</ul>
	      	</div><!-- /.card -->
	    </div><!-- /.collapse -->
	    
	    

	    
	    
	    
	    
	    
</div><!--/.co-sm-10 -->
</div><!--/.row-->



