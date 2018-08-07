<?php

namespace PublishPress\Addon\Multiple_authors\Traits;

use PublishPress\Addon\Multiple_authors\Factory;
use PublishPress\Addon\Multiple_authors\Classes\Authors_Iterator;
use PublishPress\Util;

trait Author_box {
	/**
	 * Returns true if the post type and current page is valid.
	 *
	 * @return boolean
	 */
	protected function should_display_author_box() {
		$display = $this->is_valid_page_to_display_author_box() && $this->is_valid_post_type_to_display_author_box();

		// Apply a filter
		$display = apply_filters( 'pp_multiple_authors_filter_should_display_author_box', $display );

		return $display;
	}

	/**
	 * Returns true if the current page is valid to display. Basically,
	 * we should display only if is a post's page.
	 *
	 * @return boolean
	 */
	protected function is_valid_page_to_display_author_box() {
		return ! is_home() && ! is_category() && ( is_single() || is_page() );
	}

	/**
	 * Returns true if the current post type is valid, selected in the options.
	 *
	 * @return boolean
	 */
	protected function is_valid_post_type_to_display_author_box() {
		global $publishpress;

		$supported_post_types = Util::get_post_types_for_module( $publishpress->modules->multiple_authors );
		$post_type            = Util::get_current_post_type();

		return in_array( $post_type, $supported_post_types );
	}

	/**
	 * Returns the HTML markup for the author box.
	 *
	 * @param string $target
	 * @param bool   $show_title
	 *
	 * @return string
	 */
	protected function get_author_box_markup( $target = null, $show_title = true, $layout = null ) {

		global $publishpress;

		$html = '';

		wp_enqueue_style( 'multiple-authors-widget-css',
			plugins_url( 'assets/css/multiple-authors-widget.css', PP_MULTIPLE_AUTHORS_FILE ), false,
			PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION, 'all' );

		if ( ! function_exists( 'multiple_authors' ) ) {
			require_once PP_MULTIPLE_AUTHORS_PATH_BASE . '/template-tags.php';
		}

		$css_class = '';
		if ( ! empty( $target ) ) {
			$css_class = 'pp-multiple-authors-target-' . str_replace( '_', '-', $target );
		}

		$title = isset( $publishpress->modules->multiple_authors->options->title_appended_to_content )
			? $publishpress->modules->multiple_authors->options->title_appended_to_content : esc_html__( 'Authors',
				'publishpress-multiple-authors' );
		$title = esc_html( $title );

		if ( empty( $layout ) ) {
			$layout = isset( $publishpress->modules->multiple_authors->options->layout )
				? $publishpress->modules->multiple_authors->options->layout : 'simple_list';
		}

		$show_email = isset( $publishpress->modules->multiple_authors->options->show_email_link )
			? 'yes' === $publishpress->modules->multiple_authors->options->show_email_link : true;

		$show_site = isset( $publishpress->modules->multiple_authors->options->show_site_link )
			? 'yes' === $publishpress->modules->multiple_authors->options->show_site_link : true;

		$args = [
			'show_title' => $show_title,
			'css_class'  => $css_class,
			'title'      => $title,
			'authors'    => get_multiple_authors(),
			'target'     => $target,
			'item_class' => 'author url fn',
			'layout'     => $layout,
			'show_email' => $show_email,
			'show_site'  => $show_site,
		];

		/**
		 * Get the twig template to display the author boxes.
		 *
		 * @param string $layout
		 * @param string $target
		 */
		$twig_template = apply_filters( 'pp_multiple_authors_author_box_twig_template',
			'author_layout/' . $layout . '.twig', $layout, $target );

		/**
		 * Filter the author box arguments before sending to the renderer.
		 *
		 * @param array $args
		 */
		$args = apply_filters( 'pp_multiple_authors_author_box_args', $args );

		$container = Factory::get_container();
		$html      = $container['twig']->render( $twig_template, $args );

		$authors_iterator = new Authors_Iterator;
		$html             = apply_filters( 'pp_multiple_authors_filter_author_box_markup', $html, $authors_iterator, $target );

		return $html;
	}
}
