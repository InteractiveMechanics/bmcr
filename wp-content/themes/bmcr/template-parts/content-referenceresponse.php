<?php
	$pub_id = get_the_ID();
	$bmcr_id = get_field('bmcr_id');
	$response_type = get_field('response_type');
	$publisher = get_field('publisher');
	$pub_date = get_field('pub_date');
	$isbn = get_field('isbn');
	$review_rels = get_field('relationships'); //if responding to a review
	$response_rels = get_field('response_relationships'); //if responding to another review
?>

<a href="<?php echo get_permalink(); ?>"  class="ref-wrapper">
<small class="ref-id">BMCR <?php echo $bmcr_id; ?></small> 
<p class="ref-title"><?php echo the_title(); ?></p>
<p class="ref-author">
    Response by <?php the_author(); ?> | 

    Original 
	<?php 
		if ($response_type === 'review'): 
			
			echo ' Review by';
		
		else:
		
			echo ' Response by';
		
		endif; 
		
	
		if ( $review_rels):
		
		foreach ($review_rels as $rel):
		
		$author_id = get_post_field('post_author', $rel->ID);
		
		echo ' ' . get_the_author_meta('display_name', $author_id) . ' ';
		
		endforeach;
				
		elseif ( $response_rels):
		
		foreach ($response_rels as $r):
		
		$author_id = get_post_field('post_author', $r->ID);
		
		echo ' ' . get_the_author_meta('display_name', $author_id) . ' ';
		
		endforeach;

		endif;
		
		
		
	?>
</p>
</a>		