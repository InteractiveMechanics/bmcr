<?php
/*
Plugin Name: BMCR Articles
Plugin URI: http://dev.interactivemechanics.com/bmcr
Description: Declares a plugin that will create a custom post type displaying articles.
Version: 1.0
Author: Interactive Mechanics 
Author URI: http://www.interactivemechanics.com/
License: GPLv2
*/

add_action( 'init', 'create_article' );
function create_article() {
    register_post_type( 'articles',
        array(
            'labels' => array(
                'name' => 'Articles',
                'singular_name' => 'Article',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Article',
                'edit' => 'Edit',
                'edit_item' => 'Edit Article',
                'new_item' => 'New Article',
                'view' => 'View',
                'view_item' => 'View Article',
                'search_items' => 'Search Articles',
                'not_found' => 'No Articles found',
                'not_found_in_trash' => 'No Articles found in Trash',
                'parent' => 'Parent Article'
            ),
 
            'public' => true,
            'menu_icon' => 'dashicons-media-text',
            'menu_position' => 15,
            'supports' => array( 'title', 'editor', 'author', 'revisions', 'excerpt', 'comments', 'thumbnail', 'custom-fields' ),
            'rewrite'     => array( 'slug' => 'article' ), // my custom slug
            'has_archive' => true
        )
    );
}

?>