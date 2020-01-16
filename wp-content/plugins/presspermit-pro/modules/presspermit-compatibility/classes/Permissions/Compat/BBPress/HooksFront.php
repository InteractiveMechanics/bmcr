<?php
namespace PublishPress\Permissions\Compat\BBPress;

class HooksFront
{
    function __construct() {
        add_action('presspermit_post_filters_front_non_administrator', [$this, 'actPostFiltersFrontNonAdministrator']);

        add_filter('bbp_get_forum_subforum_count', [$this, 'flt_count_private_subforums'], 10, 2);
        add_filter('presspermit_options', [$this, 'enable_topic_teaser']);
        add_filter('posts_join', [$this, 'handle_search_join'], 10, 2);
    }

    function actPostFiltersFrontNonAdministrator()
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/PostFiltersFrontNonAdministrator.php');
        new PostFiltersFrontNonAdministrator();
    }

    // force BBpress to include private subforums in the count so we have a chance to filter them into the list (or not) 
    // based on supplemental role assignment
    function flt_count_private_subforums($forum_count, $forum_id)
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BBPress/Helper.php');
        return Helper::flt_count_private_subforums($forum_count, $forum_id);
    }

    public function enable_topic_teaser($options)
    {
        if (isset($options['presspermit_tease_post_types'])) {
            $tease_post_types = maybe_unserialize($options['presspermit_tease_post_types']);
            if (!empty($tease_post_types['forum'])) {
                $tease_post_types['topic'] = '1';
                $options['presspermit_tease_post_types'] = serialize($tease_post_types);
            }
        }

        return $options;
    }
    
    public function handle_search_join($join, $query_obj)
    {
        if (!empty($query_obj->bbp_is_search) && !strpos($join, 'AS pp_bbpf')) {
            global $wpdb;
            $join .= " INNER JOIN $wpdb->postmeta AS pp_bbpf ON pp_bbpf.post_id = $wpdb->posts.ID AND pp_bbpf.meta_key = '_bbp_forum_id'";

            add_filter('presspermit_adjust_posts_where_clause', [$this, 'flt_search_where_clause'], 10, 4);
            add_filter('posts_results', [$this, 'remove_search_where_filter']);
        }

        return $join;
    }

	public function flt_search_where_clause( $alternate_where, $type_where, $post_type, $args ) {
		if ( in_array( $post_type, ['topic', 'reply'], true) && ( 'read' == $args['required_operation'] ) ) {

            $alternate_where = $type_where . " AND pp_bbpf.meta_value IN ("
            . " SELECT ID FROM {$args['src_table']} WHERE 1=1 " 
            . \PublishPress\Permissions\PostFilters::instance()->getPostsWhere(['post_types' => 'forum', 'required_operation' => 'read']) 
            . " )";
		}

		return $alternate_where;
    }
    
    public function remove_search_where_filter( $results ) {
		remove_filter( 'pp_adjust_posts_where_clause', [$this, 'flt_search_where_clause'], 10, 4 );
		return $results;
	}
}
