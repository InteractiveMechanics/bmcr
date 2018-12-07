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
            'map_meta_cap'=> true,
            'capability_type' => array('bmcrarticle', 'bmcrarticles'),
            'capabilities' => array(
                'publish_posts' => 'publish_bmcrarticles',
                'edit_posts' => 'edit_mbcrarticle',
                'edit_post' => 'edit_memberimage',
                'edit_others_posts' => 'edit_others_bmcrarticles',
                'delete_posts' => 'delete_bmcrarticles',
                'delete_post' => 'delete_bmcrarticle',
                'delete_others_posts' => 'delete_others_bmcrarticles',
                'manage_posts' => 'manage_bmcrarticles',
                'read_private_posts' => 'read_private_bmcrarticles',
                'read_post' => 'read_bmcrarticles',
            ),
            'menu_icon' => 'dashicons-media-text',
            'menu_position' => 15,
            'supports' => array( 'title', 'editor', 'author', 'revisions', 'excerpt', 'comments', 'thumbnail', 'custom-fields' ),
            'taxonomies' => array( 'post_tag' ),
            'rewrite'     => array( 'slug' => 'articles', 'with_front' => false ), // my custom slug
            'has_archive' => true,
            'query_var' => false
        )
    );
}

?>