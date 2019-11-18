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
        "bmcr_id"
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
        }
        $query->set("meta_query", $meta_query);
    };
}


//add wildcard string replace to subfield queries
function my_posts_where( $where ) {

	$where = str_replace("meta_key = 'reviewers_$", "meta_key LIKE 'reviewers_%", $where);
  $where = str_replace("meta_key = 'books_$", "meta_key LIKE 'books_%", $where);

	return $where;
}

add_filter('posts_where', 'my_posts_where');


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

add_filter( "pre_get_posts", "custom_search_query_2");
//add_filter( "pre_get_posts", "custom_search_query");

?>
