<?php
/**
 * Template for displaying an Article
 *
 * @package bmcr
 * @since bmcr 1.0
 */
?>

<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
?>

<h1>this is a single article</h1>

<?php endwhile; ?>

<?php get_footer(); ?>