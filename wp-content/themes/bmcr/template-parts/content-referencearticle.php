<?php
	$bmcr_id = get_field('bmcr_id');
	$author_id =  get_the_author_meta('ID');
?>


<small>BMCR <?php echo $bmcr_id; ?></small> 
<p><a href="<?php echo get_permalink(); ?>"><?php echo the_title(); ?></a></p>
<p>By <?php the_author(); ?></p>		