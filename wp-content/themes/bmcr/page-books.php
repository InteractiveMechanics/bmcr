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
			<div class="col-sm-10 offset-sm-1 page-header-wrapper">
				<h2 class="page-title">Available Books</h2>
					
				<div>
					<a href=""><p>All</p></a>
					<a data-toggle="collapse" href="#multiCollapseExample1" role="button" aria-expanded="false" aria-controls="multiCollapseExample1">
						 <p>Authors</p>
					</a>
					<a data-toggle="collapse" href="#multiCollapseExample2" role="button" aria-expanded="false" aria-controls="multiCollapseExample2">
						 <p>Subjects</p>
					</a>
					
				</div>
			</div><!--/.page-header-wrapper -->
		</div><!--/.row -->
		
		<div class="row">
			<div class="col-sm-10 offset-sm-1">
			<div class="collapse multi-collapse" id="multiCollapseExample1">
				<div class="card card-body">
					<p>Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident.</p>
								
	      		</div><!-- /.card -->
	    	</div><!-- /.collapse -->
	    	
	    	<div class="collapse multi-collapse" id="multiCollapseExample2">
				<div class="card card-body">
					<ul>
						<?php 
							$tags = get_tags(); 
							foreach ($tags as $tag): ?>
							<li><a href="<?php echo get_tag_link($tag->term_id); ?>"><?php echo $tag->name; ?></a></li>
						<?php endforeach; ?>
					</ul>

								
	      		</div><!-- /.card -->
	    	</div><!-- /.collapse -->

			</div><!--/.co-sm-10 -->
		</div><!--/.row-->

	
		
	
		
	<div class="row">
		<div class="col-sm-10 offset-sm-1">
	
	<?php 
	global $post;
	$args = array( 
		'posts_per_page' => 20,
		'post_type' => 'reviews',
		'post_status' => 'pitch'
		
	);

	$myposts = get_posts( $args );

	

	foreach ( $myposts as $post ) : setup_postdata( $post ); ?>
	
	<div class="ref-wrapper ref-status-pitch">
	
	<?php get_template_part( 'template-parts/content', 'referencebook' );
	?>
	
	<a href="#" class="btn btn-secondary apply-link">Apply to Review this book</a>
	
	</div>
			 
	<?php endforeach; 
	wp_reset_postdata();
	?>
				
					</div><!--/.col-sm-10 -->
				</div><!--/.row -->
			</div><!--/.container-fluid -->
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();