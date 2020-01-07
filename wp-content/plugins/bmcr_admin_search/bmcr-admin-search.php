<?php
/*
Plugin Name: BMCR Admin Search
Plugin URI: http://dev.interactivemechanics.com/bmcr
Description: Declares a plugin that expands admin search to include bmcr-id.
Version: 1.0
Author: Interactive Mechanics
Author URI: http://www.interactivemechanics.com/
License: GPLv2
*/


add_filter( 'relevanssi_index_content', '__return_false' );

add_filter( 'relevanssi_index_titles', '__return_false' );


function relevanssi_add_custom_fields( $fields )
{
	$fields = array();
	$fields[] = 'bmcr_id';
	$fields[] = 'books_0_title';
  $fields[] = 'books_0_isbn';
  $fields[] = 'reviewers_0_reviewer_first_name';
  $fields[] = 'reviewers_0_reviewer_last_name';
  $fields[] = 'books_0_authors_0_author_first_name';
  $fields[] = 'books_0_authors_0_author_last_name';
	$fields = implode( ',', $fields );

	return $fields;
}
add_filter( 'option_relevanssi_index_fields', 'relevanssi_add_custom_fields' );

//order search by bmcr_id
add_filter('relevanssi_modify_wp_query', 'rlv_dsc_bmcr');
function rlv_dsc_bmcr($query) {

    $query->set( 'orderby', 'meta_value' );
		$query->set( 'meta_key', 'bmcr_id');
		$query->set('order', 'DESC');
    return $query;
}




?>
