<?php
/*
Plugin Name: BMCR ID Generator
Plugin URI: http://dev.interactivemechanics.com/bmcr
Description: Declares a plugin that will auto-increments BMCR IDs.
Version: 1.0
Author: Interactive Mechanics 
Author URI: http://www.interactivemechanics.com/
License: GPLv2
*/

$name = 'bmcr-id';
add_option($name);


add_action( 'admin_print_scripts-post-new.php', 'toggle_bmcr_id_editable', 11 );
add_action( 'admin_print_scripts-post.php', 'toggle_bmcr_id_editable', 11 );

function toggle_bmcr_id_editable() {
    global $post_type;
    if ($post_type == 'articles' || 'responses' || 'reviews') {
        if (get_post_status() !== 'publish') {
            wp_enqueue_script('bmcr-id-generator', plugin_dir_url(__FILE__) . 'bmcr-id-generator.js', array('jquery'));
        }
    }
}


// TODO: 
// - Incrementing isn't working properly, figure that out
// - Add action for saving the post, probabyl a different function

add_action( 'publish_articles', 'set_bmcr_id_on_publish', 10, 2 );
add_action( 'publish_responses', 'set_bmcr_id_on_publish', 10, 2 );
add_action( 'publish_reviews', 'set_bmcr_id_on_publish', 10, 2 );

function set_bmcr_id_on_publish() {

    $last_id    = get_option($name);
    $cur_id     = get_field('bmcr_id');

    list($last_year, $last_mo, $last_inc) = explode('.', $last_id);

    if (!$cur_id || empty($cur_id)) {
        $now_year   = date("Y");
        $now_mo     = date("m");

        if ($now_mo !== $last_mo) {
            $now_inc = (int)$last_inc++;
            if ($now_inc < 10) { $now_inc = '0' . (string)$now_inc; }
        } else {
            $now_inc = '01';
        }
        
        $new_id = $now_year . "." . $now_mo . "." . $now_inc;
        
        update_field('bmcr_id', $new_id);
        update_option($name, $new_id);
    } else {
        update_option($name, $cur_id);
    }
}

?>