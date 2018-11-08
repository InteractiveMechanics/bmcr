<?php /** 
this partial only contains the text of the reference. this is so you can apply the appropriate wrapping el (div, anchor) and els (i.e. btns) in the template. use class ref-wrapper (.ref-wrapper) to get the styling to match other references.
**/ ?>


<?php
	$pub_id = get_the_ID();
	$publisher = get_field('publisher');
	$pub_date = get_field('pub_date');
	$isbn = get_field('isbn');
	$book_author = get_field('book_author');
?>


<p class="ref-title"><?php echo the_title(); ?></p>
<p class="ref-author"><?php echo $book_author; ?></p>
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

		
		 