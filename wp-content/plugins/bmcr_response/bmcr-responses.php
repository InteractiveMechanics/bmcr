<?php
/*
Plugin Name: BMCR Responses
Plugin URI: http://dev.interactivemechanics.com/bmcr
Description: Declares a plugin that will create a custom post type displaying responses.
Version: 1.0
Author: Interactive Mechanics 
Author URI: http://www.interactivemechanics.com/
License: GPLv2
*/

add_action( 'init', 'create_response' );
function create_response() {
    register_post_type( 'responses',
        array(
            'labels' => array(
                'name' => 'Responses',
                'singular_name' => 'Response',
                'add_new' => 'Add New',
                'add_new_item' => 'Add New Response',
                'edit' => 'Edit',
                'edit_item' => 'Edit Response',
                'new_item' => 'New Response',
                'view' => 'View',
                'view_item' => 'View Response',
                'search_items' => 'Search Responses',
                'not_found' => 'No Responses found',
                'not_found_in_trash' => 'No Responses found in Trash',
                'parent' => 'Parent Response'
            ),
            'public' => true,
            'map_meta_cap'=> true,
            'capability_type' => array('bmcrresponse', 'bmcrresponses'),
            'capabilities' => array(
                'publish_posts' => 'publish_bmcrresponses',
                'edit_posts' => 'edit_bmcrresponses',
                'edit_post' => 'edit_bmcrresponse',
                'edit_others_posts' => 'edit_others_bmcrresponses',
                'delete_posts' => 'delete_bmcrresponses',
                'delete_post' => 'delete_bmcrresponse',
                'delete_others_posts' => 'delete_others_bmcrresponses',
                'manage_posts' => 'manage_bmcrresponses',
                'read_private_posts' => 'read_private_bmcrresponses',
                'read_post' => 'read_bmcrresponses',
            ),
            'menu_icon' => 'dashicons-testimonial',
            'menu_position' => 15,
            'supports' => array( 'title', 'editor', 'author', 'revisions', 'excerpt', 'comments', 'thumbnail', 'custom-fields' ),
            'rewrite'     => array( 'slug' => 'response' ), // my custom slug
            'taxonomies' => array( 'post_tag' ),
            'has_archive' => true
        )
    );
}


?>

