<?php
namespace PublishPress\Permissions\Compat\Relevanssi;

class HooksAdmin
{
    function __construct() {
        if (is_user_logged_in()) {
            add_filter('query', [$this, 'fltIncludeCustomPrivacy']);
        }

        add_action('save_post', [$this, 'act_save_object'], 50);
    }

    private function reindex()
    {
        if (function_exists('relevanssi_truncate_cache'))
            relevanssi_truncate_cache(true);

        /*  // too much overhead to do this on each save
        if ( function_exists( 'relevanssi_build_index' ) )
            // possible todo: auto-rebuild index selectively
            add_action( 'shutdown', [ 'PP_Relevanssi_Admin_Helper', 'do_reindex' ] );
        */
    }

    /*
    public static function do_reindex() {
        ob_start();  // as of version 2.9.12, no way to rebuild index w/o outputting message
        relevanssi_build_index();
        ob_end_clean();
    }
    */

    function act_save_object()
    {
        if (function_exists('relevanssi_query')) {
            $this->reindex();
        }
    }

    public function fltIncludeCustomPrivacy($query)
    {
        // note: our relevanssi_post_ok filter will determine actual visibility of each search hit
        if (strpos($query, "post_status IN ('publish', 'draft', 'private', 'pending', 'future'")) {

            $valid_stati = get_post_stati(['public' => true, 'private' => true], 'names', 'OR');

            $query = str_replace(
                "post_status IN ('publish', 'draft', 'private', 'pending', 'future'", 
                "post_status IN ('draft', 'pending', 'future', '" . implode("', '", $valid_stati) . "'", 
                $query
            );

        // note: our relevanssi_post_ok filter will determine actual visibility of each search hit
        } elseif (strpos($query, "post_status IN ('publish','draft','private','pending','future'")) {

            $valid_stati = get_post_stati(['public' => true, 'private' => true], 'names', 'OR');

            $query = str_replace(
                "post_status IN ('publish','draft','private','pending','future'", 
                "post_status IN ('draft','pending','future', '" . implode("','", $valid_stati) . "'", 
                $query
            );
        }
        return $query;
    }
}
