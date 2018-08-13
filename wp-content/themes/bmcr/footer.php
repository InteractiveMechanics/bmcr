<?php
/**
 * The template for displaying the footer
 *
 * Contains the closing of the #content div and all content after.
 *
 * @link https://developer.wordpress.org/themes/basics/template-files/#template-partials
 *
 * @package bmcr
 */

?>

	</div><!-- #content -->

	<footer id="colophon" class="site-footer">
		<div class="newsletter-wrapper">
			<h3><?php the_field('newsletter_heading', 'option'); ?></h3>
			<small><?php the_field('newsletter_subheading', 'option'); ?></small>
			<button><?php the_field('newsletter_button_text', 'option'); ?></button>
		</div>
		<div class="site-info">
			<h3>Contact Us</h3>
			<small>
				<?php the_field('address_1', 'option'); ?>
			</small>
			<address>
			<a href="https://goo.gl/maps/M4cbPNJ8Zsx" target="_blank">
				<?php the_field('address_2', 'option'); ?>, 
				<?php the_field('city', 'option'); ?>
				<?php the_field('state', 'option'); ?>
				<?php the_field('zip', 'option'); ?>
			</a>
			</address>
			<small>
			<?php the_field('site_info_email', 'option'); ?>|
			<?php the_field('phone', 'option'); ?>
			</small>
		</div><!-- .site-info -->
		<div class="social">
			<h3>Follow Us</h3>
			<a href="http://www.twitter.com/<?php the_field('twitter', 'option'); ?>">Twitter</a> 
		</div>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
