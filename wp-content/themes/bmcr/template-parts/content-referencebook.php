<?php /** 
this partial only contains the text of the reference. this is so you can apply the appropriate wrapping el (div, anchor) and els (i.e. btns) in the template. use class ref-wrapper (.ref-wrapper) to get the styling to match other references.
**/ ?>


<?php
	$pub_id = get_the_ID();
?>


<p class="ref-title"><?php echo the_title(); ?></p>
<p class="ref-details">
    <?php if( have_rows('books') ): ?>
        <?php while ( have_rows('books') ) : the_row(); ?>

            <em><?php echo the_sub_field('title'); ?></em>
            <span class="slash">&nbsp;/&nbsp;</span>
            By <?php echo the_sub_field('book_author_full'); ?>
            <span class="slash">&nbsp;/&nbsp;</span>
        	<?php 
        		if (get_sub_field('publisher')): 
        			echo the_sub_field('publisher') . ', ';
        		endif;
        	
        		if (get_sub_field('pub_date')):
        			echo the_sub_field('pub_date');
        		endif;
        	?>

        <?php endwhile; ?>
    <?php endif; ?>
</p>
