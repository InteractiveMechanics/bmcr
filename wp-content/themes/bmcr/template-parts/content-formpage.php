<?php
/**
 * Template part for displaying page content in page.php
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bmcr
 */

?>

<div id="post-<?php the_ID(); ?>" <?php post_class(); ?> class="container-fluid">
	<header class="entry-header row">
		<div class="col-sm-10 offset-sm-1">

		<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
		</div>
	</header><!-- .entry-header -->

	<?php bmcr_post_thumbnail(); ?>

	<div class="entry-content row">
		<div class="col-sm-10 offset-sm-1">
	
		<?php
		the_content();
		?>
		</div>
	</div><!-- .entry-content -->

</div><!-- #post-<?php the_ID(); ?> -->
