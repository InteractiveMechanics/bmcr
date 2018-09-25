<?php
	$pub_id = get_the_ID();
	$bmcr_id = get_field('bmcr_id');
	$publisher = get_field('publisher');
	$pub_date = get_field('pub_date');
	$isbn = get_field('isbn');
	$book_author = get_field('book_author');
?>

<a href="<?php echo get_permalink(); ?>" class="ref-wrapper">
<small class="ref-id">BMCR <?php echo $bmcr_id; ?></small> 
<p class="ref-title"><?php echo the_title(); ?></p>
<p class="ref-author"><?php echo $book_author; ?> | Review by <?php the_author(); ?></p>
<p class="ref-details">
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
</a>		
		 