<?php
	$pub_id = get_the_ID();
	$bmcr_id = get_field('bmcr_id');
	$response_type = get_field('response_type');
	$publisher = get_field('publisher');
	$pub_date = get_field('pub_date');
	$isbn = get_field('isbn');
?>


<small>BMCR <?php echo $bmcr_id; ?></small> 
<p><a href="<?php echo get_permalink(); ?>"><?php echo the_title(); ?></a></p>
<p>Original 
	<?php 
		if ($response_type === 'review'): 
			echo ' Review by';
			
		else:
			echo ' Response by';
		endif; 
	
	?>
	Authors Name | Response by <?php the_author(); ?>
</p>		