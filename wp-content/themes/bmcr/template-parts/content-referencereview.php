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



								<?php if( have_rows('authors') ):
										while ( have_rows('authors') ) : the_row(); ?>
									<?php if (get_sub_field('author_full_name')): ?>
										 <?php echo the_sub_field('author_full_name'); ?>,
									<?php endif; ?>
									<?php endwhile; endif; ?>


            	<?php
	            	if (get_sub_field('publisher') || get_sub_field('pub_date')):
	            		echo '<span class="slash">&nbsp;/&nbsp;</span>';
	            	endif;

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
