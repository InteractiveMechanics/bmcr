<?php
	$pub_id = get_the_ID();

?>

<a href="<?php echo get_permalink(); ?>" class="ref-wrapper">
    <small class="ref-id">BMCR <?php echo the_field('bmcr_id'); ?></small> 
    <p class="ref-title"><?php echo the_title(); ?></p>

    <p class="ref-author">Reviewed by 
        <?php if( have_rows('reviewers') ): ?>
            <?php while ( have_rows('reviewers') ) : the_row(); ?>
                <?php echo the_sub_field('reviewer_first_name'); ?> 
                <?php echo the_sub_field('reviewer_last_name'); ?><span class="comma">,&nbsp;</span>
            <?php endwhile; ?>
        <?php endif; ?>
    </p>

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
</a>		
		 