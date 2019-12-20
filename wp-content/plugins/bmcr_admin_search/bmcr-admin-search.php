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


function custom_search_query( $query ) {
    $custom_fields = array(
        "searchable_content"
    );
    $searchterm = $query->query_vars['s'];

    // we have to remove the "s" parameter from the query, because it will prevent the posts from being found
    $query->query_vars['s'] = "";

    if ($searchterm != "") {
        $meta_query = array('relation' => 'OR');
        foreach($custom_fields as $cf) {
            array_push($meta_query, array(
                'key' => $cf,
                'value' => $searchterm,
                'compare' => 'LIKE'
            ));

        };
        // array_push($meta_query, array(
        //   'key'		=> 'reviewers_$_reviewer_last_name',
    		// 	'compare'	=> '=',
    		// 	'value'		=> $searchterm,
        //   ));
        $query->set("meta_query", $meta_query);
    };
}

//add_filter( "pre_get_posts", "custom_search_query");


//add wildcard string replace to subfield queries
function my_posts_where( $where ) {

	$where = str_replace("meta_key = 'reviewers_$", "meta_key LIKE 'reviewers_%", $where);
  // $where = str_replace("meta_key = 'books_$_authors_$", "meta_key LIKE 'books_%_authors_%", $where);

	return $where;
}

//add_filter('posts_where', 'my_posts_where');


//make similar to functionality above, pass in array of search params
function custom_search_query_2( $query ) {
  $searchterm = $query->query_vars['s'];
  // we have to remove the "s" parameter from the query, because it will prevent the posts from being found
  $query->query_vars['s'] = "";
  if ($searchterm != "") {
    $meta_query = array(
      array(
			'key'		=> 'reviewers_$_reviewer_last_name',
			'compare'	=> '=',
			'value'		=> $searchterm,
		)
	);
$query->set('meta_query', $meta_query);
}
}

//add_filter( "pre_get_posts", "custom_search_query_2");




function save_searchable_content_meta($post_id, $post, $update) {
    $fields = get_fields($post->ID);
    if (array_key_exists("searchable_content", $fields)) {
      $bmcr_id = get_field("bmcr_id");
	    $first_reviewer_first_name = get_field("reviewers_0_reviewer_first_name");
      $first_reviewer_last_name = get_field("reviewers_0_reviewer_last_name");
      $first_book_title = get_field("books_0_title");
      $first_book_isbn = get_field("books_0_isbn");
      $first_author_first_name = get_field("books_0_authors_0_author_first_name");
      $first_author_last_name = get_field("books_0_authors_0_author_last_name");

      $searchterms = $bmcr_id . ', ' . $first_reviewer_first_name . ', ' . $first_reviewer_last_name . ', ' . $first_book_title . ', ' . $first_book_isbn . ', ' . $first_author_first_name . ', ' . $first_author_last_name;
      //don't need title or content for search
	    //array_unshift($fields, $post->post_title, $post->post_content);
	    $str = sanitize_text_field($searchterms);
	    update_field("searchable_content", $str, $post_id);
	}
}

//add_action('save_post', 'save_searchable_content_meta', 10, 3);

// function relevanssi_add_custom_fields( $fields )
// {
// 	$fields = array();
// 	$fields[] = 'bmcr_id';
// 	$fields[] = 'books_0_title';
//   $fields[] = 'books_0_isbn';
//   $fields[] = 'reviewers_0_reviewer_first_name';
//   $fields[] = 'reviewers_0_reviewer_last_name';
//   $fields[] = 'books_0_authors_0_author_first_name';
//   $fields[] = 'books_0_authors_0_author_last_name';
// 	$fields = implode( ',', $fields );
//
// 	return $fields;
// }
// add_filter( 'option_relevanssi_index_fields', 'relevanssi_add_custom_fields' );


add_filter( 'relevanssi_index_content', '__return_false' );

add_filter( 'relevanssi_index_titles', '__return_false' );

// function index_custom_fields( $fields )
// {
// 	$fields = array();
// 	$fields[] = 'bmcr_id';
// 	$fields[] = 'books_0_title';
//   $fields[] = 'books_0_isbn';
//   $fields[] = 'reviewers_0_reviewer_first_name';
//   $fields[] = 'reviewers_0_reviewer_last_name';
//   $fields[] = 'books_0_authors_0_author_first_name';
//   $fields[] = 'books_0_authors_0_author_last_name';
// 	$fields = implode( ',', $fields );
//
// 	return $fields;
// }
//
// add_filter( 'relevanssi_index_custom_fields', 'index_custom_fields' );


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




?>
