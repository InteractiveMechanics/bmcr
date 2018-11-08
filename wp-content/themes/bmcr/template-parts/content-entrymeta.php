<?php $author_id = get_the_author_meta('ID'); ?>

<?php the_author(); ?></h4>
		
<h4 class="meta-affiliation">
	
	<?php if (get_the_author_meta('description')): ?>
	
	    <?php the_author_meta('description'); ?>, 
	
	<?php endif; ?>
			
	<a href="mailto:<?php the_author_meta('user_email', $author_id); ?>" target="_top"><?php the_author_meta('user_email', $author_id); ?></a></h4>
		
<h4 class="meta-date"><?php the_date(); ?></h4>
