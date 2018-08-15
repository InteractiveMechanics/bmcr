<ul>
	<li><a href="">All</a></li>
	<li><a href="">Reviewer</a></li>
	<li><a href="">Author</a></li>
	<li><a href="">Year</a></li>
	<li>Type
		<ul>				
			<li><a href="<?php echo get_post_type_archive_link( 'articles' ); ?>">Articles</a></li>
			<li><a href="<?php echo get_post_type_archive_link( 'responses' ); ?>">Responses</a></li>
			<li><a href="<?php echo get_post_type_archive_link( 'reviews' ); ?>">Reviews</a></li>
		</ul>
	</li>
	<li>
		<?php wp_get_archives('type=yearly'); ?>
	</li>
	<li> Subject
		<ul>
			<?php 
			$tags = get_tags(); 
			foreach ($tags as $tag): ?>
				<li><a href="<?php echo get_tag_link($tag->term_id); ?>"><?php echo $tag->name; ?></a></li>										<?php endforeach; ?>
		</ul>
	</li>
</ul>

