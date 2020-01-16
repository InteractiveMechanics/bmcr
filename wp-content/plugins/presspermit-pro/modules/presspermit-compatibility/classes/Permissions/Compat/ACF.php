<?php
namespace PublishPress\Permissions\Compat;

class ACF {
    function __construct() {
        if (!empty($_REQUEST['preview_id'])) {
            // @todo Review: any conditions where this is needed?
            //add_filter('query', [$this, 'fltQueryRevisions']);
        }
    }

    function fltQueryRevisions($query)
    {
        global $wpdb;
        //SELECT   wp_posts.* FROM wp_posts  WHERE 1=1  AND wp_posts.post_parent = 2721  AND wp_posts.post_type = 'revision' AND ((wp_posts.post_status = 'inherit'))

        if (preg_match('/\.post_type\s*=\s*' . "'revision'" . '/', $query) 
        && preg_match('/\.post_status\s*=\s*' . "'inherit'" . '/', $query) 
        && (0 === strpos($query, 'SELECT'))
        && strpos($query, 'ORDER BY')
        ) {
            $query = preg_replace(
                '/' . $wpdb->posts . '\.post_status\s*=\s*' . "'inherit'" . '/',
                "$wpdb->posts.post_status = 'inherit' AND $wpdb->posts.post_name NOT LIKE '%-autosave%'",
                $query
            );
        }
    
        return $query;
    }
}
