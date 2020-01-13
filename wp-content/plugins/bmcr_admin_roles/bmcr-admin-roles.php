<?php
/*
Plugin Name: BMCR Admin Roles
Plugin URI: http://dev.interactivemechanics.com/bmcr
Description: Declares a plugin that limits backend access based on role.
Version: 1.0
Author: Interactive Mechanics
Author URI: http://www.interactivemechanics.com/
License: GPLv2
*/


// Show only posts and media related to logged in author
add_action('pre_get_posts', 'query_set_only_author' );
function query_set_only_author( $wp_query ) {
    global $current_user;
    $user_login = $current_user->user_nicename;
    //print_r($user_login);
    //echo $first;
    if( is_admin() && !current_user_can('edit_others_posts') ) {
        //$user_login = get_user_meta( $current_user->ID, 'user_login', true );
        //$wp_query->unset( $query->query['author_name'] );
        $wp_query->set( 'author_name', $user_login );
        // add_filter('views_edit-post', 'fix_post_counts');
        // add_filter('views_upload', 'fix_media_counts');
    }
}

//for reviewers, remove views from reviews list
function remove_draft_all_mine_products($views) {
if( is_admin() && !current_user_can('edit_others_posts') ) {
  unset($views['all']);
  unset($views['publish']);
  unset($views['draft']);
  unset($views['final-review-needed']);
  unset($views['in-review']);
  unset($views['title-added']);
  unset($views['merged']);
  unset($views['pending']);
  unset($views['pitch']);
}

  return $views;
}
add_filter('views_edit-reviews', 'remove_draft_all_mine_products');
add_filter('views_edit-responses', 'remove_draft_all_mine_products');

//for reviewers, sets default view to "mine" from submenu doesn't function currently
function wcs_change_admin_page_link() {
  if( is_admin() && !current_user_can('edit_others_posts') ) {
    global $submenu;
    global $current_user;
    $submenu['edit.php?post_type=reviews'][5][2] = 'edit.php?author_name='. $current_user->user_nicename .'&post_type=reviews';
  }
}
//add_action( 'admin_menu', 'wcs_change_admin_page_link' );

//menu items
add_action( 'admin_init', 'my_remove_menu_pages' );

function my_remove_menu_pages() {

global $user_ID;

if( is_admin() && !current_user_can('edit_others_posts') ) {
//remove_menu_page( 'post-new.php?post_type=reviews' );
remove_submenu_page( 'edit.php?post_type=reviews', 'post-new.php?post_type=reviews');
remove_submenu_page( 'edit.php?post_type=responses', 'post-new.php?post_type=responses');
}
}

?>
