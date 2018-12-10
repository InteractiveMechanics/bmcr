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
        				<h1 class="page-title">Available Books</h1>
        					
        				<div>
        					<a href="<?php echo get_permalink(); ?>"><p>All</p></a>
        					<a data-toggle="collapse" data-target="#toggle-authors" role="button" aria-expanded="false" aria-controls="toggle-authors"><p>Authors</p></a>
        				</div>
        			</div><!--/.page-header-wrapper -->
        		</div><!--/.row -->
		
        		<div class="row">
        			<div class="col-sm-10 offset-sm-1" id="accordion">
            			<div class="collapse multi-collapse" id="toggle-authors" data-parent="#accordion">
            				<div class="card card-body">
            					<ul class="list-inline list-alpha">
                                    <?php $array = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z']; ?>
                
                                    <?php foreach($array as $key => $value): ?>
                                        <li class="list-inline-item"><a href="?auth=<?php echo $value; ?>"><?php echo $value; ?></a></li>
                                    <?php endforeach; ?>
                                </ul>
            	      		</div><!-- /.card -->
            	    	</div><!-- /.collapse -->
        	    	
            	    	<div class="collapse multi-collapse" id="toggle-tags" data-parent="#accordion">
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
                            $args = array(
                                'posts_per_page' => -1,
                        		'post_type' => 'reviews',
                        		'post_status' => 'title-added'
                            );
                            
                            if(isset($_GET['auth'])){
                                $author = $_GET['auth'];
                                $args = array(
                                    'posts_per_page' => -1,
	                        		'post_type' => 'reviews',
	                        		'post_status' => 'title-added',
                                    'meta_query' => array(
                                        array(
                                            'key'		=> 'books_$_book_author_last',
                                            'value'		=> '^[' . strtoupper($author) . strtolower($author) . ']',
                                            'compare'	=> 'REGEXP'
                                        )
                                    )
                                );
                            }
                        
                        	$query = new WP_Query($args); ?>
                                        
                            <?php
                        		if ( $query->have_posts() ) :
        
                        			/* Start the Loop */
                        			while ($query->have_posts() ) :
                        				$query->the_post(); ?>
                    	
                                        <div class="ref-wrapper ref-status-pitch">
                    	
                                            <?php get_template_part( 'template-parts/content', 'referencebook' ); ?>
                    	
                                            <a href="<?php echo get_page_link(2); ?>?bid=<?php echo get_the_id(); ?>" class="btn btn-secondary apply-link">Apply to Review this Book</a>
                    	
                    	                </div>
                    			 
                                    <?php endwhile;
            
                                else :
                
                                    get_template_part( 'template-parts/content', 'none' );
                
                                endif;

            		        wp_reset_postdata(); ?>
				
					</div><!--/.col-sm-10 -->
				</div><!--/.row -->

			</div><!--/.container-fluid -->
		</main><!-- #main -->
	</div><!-- #primary -->

<?php
get_footer();