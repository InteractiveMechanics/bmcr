<?php
/*
Plugin Name: BMCR WorldCat
Plugin URI: http://dev.interactivemechanics.com/bmcr
Description: Declares a plugin that will allow you to import WorldCat data.
Version: 1.0
Author: Interactive Mechanics 
Author URI: http://www.interactivemechanics.com/
License: GPLv2
*/


add_action( 'admin_print_scripts-post-new.php', 'review_add_worldcat_script', 11 );
add_action( 'admin_print_scripts-post.php', 'review_add_worldcat_script', 11 );

function review_add_worldcat_script() {
    global $post_type;
    if ('reviews' == $post_type) {
        wp_enqueue_script('bmcr-worldcat', plugin_dir_url(__FILE__) . 'bmcr-worldcat.js', array('jquery'));
    }
}


?>