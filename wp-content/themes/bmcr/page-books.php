<?php
/**
 * Template Name: Available Books
 *
 * This is the template that displays Available Books
 
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/
 *
 * @package bmcr
 */

get_header();
?>

	<div id="primary" class="content-area">
		<main id="main" class="site-main">

	<div class="container-fluid">	
		<div class="page-header" class="row">
			<div class="col-sm-10 offset-sm-1">
				<h2 class="page-title">Available Books</h2>
					
				<ul>
					<li><a href="">All</a></li>
					<li><a href="">Author</a></li>
					<li><a href="">Subject</a></li>
				</ul>
			</div>
		</div>
		
	
		
	
	
	<?php 
	global $post;
	$args = array( 
		'posts_per_page' => 20,
		'post_type' => 'reviews',
		'post_status' => 'pitch'
		
	);

	$myposts = get_posts( $args );

	

	foreach ( $myposts as $post ) : setup_postdata( $post );
	
	get_template_part( 'template-parts/content', 'referencereview' );
	?>
	
	</div>	
		 
	<?php endforeach; 
	wp_reset_postdata();
	?>

	</div><!--/.container-fluid -->
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();