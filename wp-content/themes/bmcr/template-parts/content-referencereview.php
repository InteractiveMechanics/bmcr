<?php
	$pub_id = get_the_ID();
	$bmcr_id = get_field('bmcr_id');
	$publisher = get_field('publisher');
	$pub_date = get_field('pub_date');
	$isbn = get_field('isbn');
?>


<small>BMCR <?php echo $bmcr_id; ?></small> 
<p><a href="<?php echo get_permalink(); ?>"><?php echo the_title(); ?></a></p>
<p>Review by <?php the_author(); ?>
<p>
	<?php 
		if ($publisher): 
			echo $publisher . ', ';
		endif;
	
		if ($pub_date):
			echo $pub_date;
		endif;
		
		if ($pub_date || $publisher): 
			echo '|';
		endif;
		
		if ($isbn):
			echo 'ISBN ' . $isbn;
		endif;
	?>

</p>		
		 