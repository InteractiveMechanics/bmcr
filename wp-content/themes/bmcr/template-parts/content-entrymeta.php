<?php if( have_rows('reviewers') ):
    while ( have_rows('reviewers') ) : the_row(); ?>

    <h4 class="meta-affiliation">
	
        <?php echo the_sub_field('reviewer_first_name'); ?>
    	<?php echo the_sub_field('reviewer_last_name'); ?><?php if (get_sub_field('reviewer_affiliation')): ?>, <?php echo the_sub_field('reviewer_affiliation'); ?><?php endif; ?>
    	<?php if (get_sub_field('reviewer_email')): ?>. <a href="mailto:<?php echo the_sub_field('reviewer_email'); ?>" target="_top"><?php echo the_sub_field('reviewer_email'); ?></a><?php endif; ?>

    </h4>

<?php endwhile; endif; ?>
