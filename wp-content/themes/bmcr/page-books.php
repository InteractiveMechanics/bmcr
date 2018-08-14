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

		
	<div class="page-header">
		<h1 class="page-title">Available Books</h1>
			
		<ul>
			<li><a href="">All</a></li>
			<li><a href="">Author</a></li>
			<li><a href="">Subject</a></li>
		</ul>
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
	
	$pub_id = get_the_ID();
	$bmcr_id = get_field('bmcr_id');
	$pub_location = get_field('pub_location');
	$publisher = get_field('publisher');
	$pub_date = get_field('pub_date');
	$isbn = get_field('isbn');
	$book_author = get_field('book_author');
	
	
	 
	?>
	<div>
	<small>BMCR <?php echo $bmcr_id; ?></small> 
	<h4><?php echo the_title(); ?></h4>
	<p><?php echo $book_author; ?> 
	<p>
		<?php 
			
			if ($pub_location):
				echo $pub_location . ': ';
			endif;
			
			if ($publisher): 
				echo $publisher . ', ';
			endif;
			
			if ($pub_date):
				echo $pub_date;
			endif;
			
			if ($pub_date || $publisher): 
				echo '|';
			endif;
			
			if ($isbn):
				echo 'ISBN ' . $isbn;
			endif;
		?>
	
	</p>
	
	<a href="<?php echo get_page_link(2); ?>" target="_blank">Apply to review this book</a>	
	</div>	
		 
	<?php endforeach; 
	wp_reset_postdata();
	?>

	
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();