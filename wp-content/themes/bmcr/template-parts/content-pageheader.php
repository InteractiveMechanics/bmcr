<div>
	<a href="<?php echo get_permalink( get_option( 'page_for_posts' ) ); ?>"><p>All</p></a>
	<a href="#" data-toggle="collapse" data-target="#toggle-reviewers" role="button" aria-expanded="false" aria-controls="toggle-reviewers" class="d-none d-lg-block"><p>Reviewer</p></a>
	<a href="#" data-toggle="collapse" data-target="#toggle-authors" role="button" aria-expanded="false" aria-controls="toggle-authors" class="d-none d-lg-block"><p>Author of Work</p></a>
	<a href="#" data-toggle="collapse" data-target="#toggle-types" role="button" aria-expanded="false" aria-controls="toggle-types"><p>Type</p></a>
	<a href="#" data-toggle="collapse" data-target="#toggle-years" role="button" aria-expanded="false" aria-controls="toggle-years"><p>Year</p></a>
	<a href="#" data-toggle="collapse" data-target="#toggle-subjects" role="button" aria-expanded="false" aria-controls="toggle-subjects" class="d-none"><p>Subject</p></a>	
</div>

<?php /* .opening tags for page-header-wrapper and .row are in the template */ ?>

	</div><!--/.page-header-wrapper-->						
</div><!-- /.row -->

<div class="row">
	<div class="col-sm-10 offset-sm-1" id="accordion">
		<div class="collapse" id="toggle-reviewers" data-parent="#accordion">
			<div class="card card-body">
				<ul class="list-inline list-alpha">
                    <?php $array = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z']; ?>

                    <?php foreach($array as $key => $value): ?>
                        <li class="list-inline-item"><a href="<?php echo get_permalink( get_option( 'page_for_posts' ) ); ?>?reviewer=<?php echo $value; ?>"><?php echo $value; ?></a></li>
                    <?php endforeach; ?>
                </ul>
	      	</div><!-- /.card -->
	    </div><!-- /.collapse -->
	    
	    <div class="collapse" id="toggle-authors" data-parent="#accordion">
			<div class="card card-body">
				<ul class="list-inline list-alpha">
                    <?php foreach($array as $key => $value): ?>
                        <li class="list-inline-item"><a href="<?php echo get_permalink( get_option( 'page_for_posts' ) ); ?>?auth=<?php echo $value; ?>"><?php echo $value; ?></a></li>
                    <?php endforeach; ?>
                </ul>
	      	</div><!-- /.card -->
	    </div><!-- /.collapse -->
	    
	    <div class="collapse" id="toggle-types" data-parent="#accordion">
			<div class="card card-body">
				<ul class="list-inline">				
					<li><a href="<?php echo get_post_type_archive_link( 'articles' ); ?>">Articles</a></li>
					<li><a href="<?php echo get_post_type_archive_link( 'responses' ); ?>">Responses</a></li>
					<li><a href="<?php echo get_post_type_archive_link( 'reviews' ); ?>">Reviews</a></li>
				</ul>				
	      	</div><!-- /.card -->
	    </div><!-- /.collapse -->
	    
	    <div class="collapse" id="toggle-years" data-parent="#accordion">
			<div class="card card-body">
				<ul class="list-inline list-alpha">
					<?php $startyear = 1990; ?>

                    <?php for($startyear; $startyear <= intval(date('Y')); $startyear++): ?>
                        <li class="list-inline-item"><a href="<?php echo get_home_url(); ?>/<?php echo $startyear; ?>"><?php echo $startyear; ?></a></li>
                    <?php endfor; ?>
				</ul>		
	      	</div><!-- /.card -->
	    </div><!-- /.collapse -->
	    
	    <div class="collapse" id="toggle-subjects" data-parent="#accordion">
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



