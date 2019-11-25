<?php
/**
 * Template for displaying a Review
 *
 * @package bmcr
 * @since bmcr 1.0
 */
?>

<?php get_header();?>

<?php while ( have_posts() ) : the_post();

	//static fields
	$bmcr_id = get_field('bmcr_id');
?>

<main>

	<article id="post-<?php the_ID(); ?>" class="container-fluid" itemscope itemtype="http://schema.org/Review">

		<div class="entry-header row">

			<div class="col-sm-10 offset-sm-1 col-lg-8 offset-lg-2">

				<small class="ref-id">BMCR <?php echo $bmcr_id; ?></small>
				<h1 class="entry-title" itemprop="name"><?php the_title(); ?></h1>

				<div class="d-none" itemprop="publisher" itemscope itemtype="http://schema.org/Organization">
					<meta itemprop="name" content="Bryn Mawr Classical Review" />
				</div>

                <?php
                    if( have_rows('books') ):
                        while ( have_rows('books') ) : the_row(); ?>

                            <?php if (get_sub_field('citation')): ?>
                                <div class="entry-citation"><?php echo the_sub_field('citation'); ?></div>
                            <?php else : ?>

                                <div class="entry-citation">
																	<?php if( have_rows('authors') ):
							                        while ( have_rows('authors') ) : the_row(); ?>

                                    <?php if (get_sub_field('author_full_name')): ?>
                                        <?php echo the_sub_field('author_full_name'); ?>,
                                    <?php endif; ?>
																		<?php endwhile; endif; ?>

                                    <?php if (get_sub_field('title')): ?>
                                        <em itemprop="itemReviewed"><?php echo the_sub_field('title'); ?>.</em>
                                    <?php endif; ?>
                                    <?php if (get_sub_field('series_title')): ?>
                                        <em><?php echo the_sub_field('series_title'); ?></em>.
                                    <?php endif; ?>

                                    <?php if (get_sub_field('publisher')): ?>
                                        <?php echo the_sub_field('pub_location'); ?>:
                                        <?php echo the_sub_field('publisher'); ?>,
                                        <?php echo the_sub_field('pub_date'); ?>.
                                    <?php endif; ?>

                                    <?php if (get_sub_field('extent')): ?>
                                        <?php echo the_sub_field('extent'); ?>.
                                    <?php endif; ?>

                                    <?php if (get_sub_field('isbn')): ?>
                                        ISBN <?php echo the_sub_field('isbn'); ?>
                                    <?php endif; ?>

                                    <?php if (get_sub_field('price')): ?>
                                        <?php echo the_sub_field('price'); ?>.
                                    <?php endif; ?>
                                </div>

                            <?php endif; ?>

                <?php endwhile; endif; ?>

				<div class="entry-links">

					<div class="entry-btns">

						<?php if ($book_preview): ?>

							<a href="<?php echo $book_preview; ?>" class="btn btn-primary" target="_blank">Book Preview</a>

						<?php endif; ?>

						<?php if ($purchase_book): ?>

							<a href="<?php echo $purchase_book; ?>" class="btn btn-secondary" target="_blank">Purchase Book</a>

						<?php endif; ?>

					</div>

					<?php if (get_field('relationships')): ?>

					<a href="#responses" class="responses-anchor"><p><?php echo $relationship_count = count(get_field('relationships')); ?> Responses</p></a>

					<?php endif; ?>

				</div>

			</div>

		</div><!-- .entry-header -->


		<div class="entry-meta row">

			<div class="col-sm-10 offset-sm-1 col-lg-8 offset-lg-2">

				<h4>Review by</h4>

				<?php get_template_part( 'template-parts/content', 'entrymeta' ); ?>

				<hr/>
			</div>

		</div><!-- .entry-meta -->

		<div class="entry-content row">

			<div class="col-sm-10 offset-sm-1 col-lg-8 offset-lg-2" itemprop="reviewBody">

				<?php the_content(); ?>

			</div>

		</div><!-- .entry-content -->


		<div class="entry-footer row">

			<div class="col-sm-10 offset-sm-1 col-lg-8 offset-lg-2">

			<?php
				if(get_the_tag_list()) {
				echo get_the_tag_list('<ul class="tag-wrapper"><li>','</li><li>','</li></ul>');
				}
			?>

			</div>

		</div><!-- .entry-footer -->

        <?php

			$posts = get_field('relationships');

			if( $posts ): ?>
        		<aside id="responses" class="row">

        			<div class="col-sm-10 offset-sm-1 col-lg-8 offset-lg-2">

            			<div class="responses-header">

            				<h2>Responses</h2>

            				<a href="<?php echo esc_url( get_page_link(180) ); ?>"><p>Response Guidelines</p></a>
            				<a href="mailto:bmcr@bmcreview.org"><p>Submit a Response</p></a>

            			</div>
    				    <div>
    				    <?php foreach( $posts as $post): // variable must be called $post (IMPORTANT)

    				        setup_postdata($post);

    					    get_template_part( 'template-parts/content', 'referenceresponse' );

    					endforeach; ?>
    				    </div>
    				    <?php wp_reset_postdata(); // IMPORTANT - reset the $post object so the rest of the page works correctly ?>
        			</div>

        		</aside><!--/#responses -->

        <?php endif; ?>
        <?php

			$posts = get_field('rel_pubs');

			if( $posts ): ?>

                <?php get_template_part( 'template-parts/content', 'related' ); ?>

        <?php endif; ?>

		<?php get_template_part( 'template-parts/content', 'comments' ); ?>


	</article>

</main>

<?php endwhile; ?>

<?php get_footer(); ?>
