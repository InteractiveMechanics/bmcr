<?php
  	global $wp_query;
  	
  	$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
  	
  	$prev_text = __('Newer Posts');
  	$next_text = __('Older Posts');
  	
  	// The magic happens here. First we grab the URL and check it for URL params,
  	// if they're present then we remove them, insert the $format placeholder,
  	// and append them to the end of the URL.
  	$url_params_regex = '/\?.*?$/';
  	preg_match($url_params_regex, get_pagenum_link(), $url_params);
  	
  	$base   = !empty($url_params[0]) ? preg_replace($url_params_regex, '', get_pagenum_link()).'%_%/'.$url_params[0] : get_pagenum_link().'%_%';
  	$format = 'page/%#%';
  	
  	$blog_nav = paginate_links(array(
	    'base'      => $base,
	    'format'    => $format,
	    'current'   => max(1, get_query_var('paged')),
	    'total'     => $wp_query->max_num_pages,
	    'prev_next' => true,
	    'prev_text' => $prev_text,
	    'next_text' => $next_text,
	    'type'      => 'array',
	    'end_size'  => 3,
	    'mid_size'  => 3
	));
?>