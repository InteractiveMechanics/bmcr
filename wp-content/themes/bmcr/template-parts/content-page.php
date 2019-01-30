<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bmcr
 */

?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?> class="container-fluid">
	<header class="entry-header row">
		<div class="col-sm-10 offset-sm-1 col-lg-8 offset-lg-2">

		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		</div>
	</header><!-- .entry-header -->

	<?php bmcr_post_thumbnail(); ?>

	<div class="entry-content row">
		<div class="col-sm-10 offset-sm-1 col-lg-8 offset-lg-2">
	
		<?php
		the_content();
		?>
		</div>
	</div><!-- .entry-content -->

</article><!-- #post-<?php the_ID(); ?> -->
