<?php
/**
 * Template for displaying a Response
 *
 * @package bmcr
 * @since bmcr 1.0
 */
?>

<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();	?>

<h1>this is a response</h1>

<?php endwhile; ?>

<?php get_footer(); ?>