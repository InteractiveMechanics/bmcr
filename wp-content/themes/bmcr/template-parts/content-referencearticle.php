<?php
	$bmcr_id = get_field('bmcr_id');
	$author_id =  get_the_author_meta('ID');
?>

<a href="<?php echo get_permalink(); ?>" class="ref-wrapper">
	<small class="ref-id">BMCR <?php echo $bmcr_id; ?></small> 
	<p class="ref-title"><?php echo the_title(); ?></p>
	<p class="ref-author">By <?php the_author(); ?></p>	
</a>	