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

