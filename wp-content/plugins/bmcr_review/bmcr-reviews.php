<?php
/*
Plugin Name: BMCR Reviews
Plugin URI: http://dev.interactivemechanics.com/bmcr
Description: Declares a plugin that will create a custom post type displaying reviews.
Version: 1.0
Author: Interactive Mechanics 
Author URI: http://www.interactivemechanics.com/
License: GPLv2
*/

add_action( 'init', 'create_review' );
function create_review() {
    register_post_type( 'reviews',
        array(
            'labels' => array(
                'name' => 'Reviews',
                'singular_name' => 'Review',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Review',
                'edit' => 'Edit',
                'edit_item' => 'Edit Review',
                'new_item' => 'New Review',
                'view' => 'View',
                'view_item' => 'View Review',
                'search_items' => 'Search Reviews',
                'not_found' => 'No Reviews found',
                'not_found_in_trash' => 'No Reviews found in Trash',
                'parent' => 'Parent Review'
            ),
 
            'public' => true,
            'map_meta_cap'=> true,
            'capability_type' => array('bmcrreview', 'bmcrreviews'),
            'capabilities' => array(
                'publish_posts' => 'publish_bmcrreviews',
                'edit_posts' => 'edit_bmcrreviews',
                'edit_post' => 'edit_bmcrreview',
                'edit_others_posts' => 'edit_others_bmcrreviews',
                'delete_posts' => 'delete_bmcrreviews',
                'delete_post' => 'delete_bmcrreview',
                'delete_others_posts' => 'delete_others_bmcrreviews',
                'manage_posts' => 'manage_bmcrreviews',
                'read_private_posts' => 'read_private_bmcrreviews',
                'read_post' => 'read_bmcrreviews',
            ),
            'menu_icon' => 'dashicons-book',
            'menu_position' => 15,
            'supports' => array( 'title', 'editor', 'author', 'revisions', 'excerpt', 'comments', 'thumbnail', 'custom-fields' ),
            'taxonomies' => array( 'post_tag' ),
            'rewrite'     => array( 'slug' => 'review' ), // my custom slug
            'has_archive' => true
        )
    );
}

?>