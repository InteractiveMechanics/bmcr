<?php
namespace PublishPress\Permissions\Compat;

class CMSTreeView 
{
    function __construct() {
        add_filter('presspermit_get_pages_args', [$this, 'get_pages_args']);
        add_filter('get_pages', [$this, 'filter_posts'], 50, 2);
        add_filter('presspermit_get_pages_parent', [$this, 'parent_clause'], 10, 2);

        add_action('wp_ajax_cms_tpv_move_page', [$this, 'move_page'], 5);
        add_action('wp_ajax_cms_tpv_add_page', [$this, 'add_page'], 5);
        add_action('wp_ajax_cms_tpv_add_pages', [$this, 'add_pages'], 5);

        add_action('admin_print_scripts', [$this, 'no_add']);
    }

    function add_page()
    {
        if (empty($_REQUEST['pageID'])) {
            return;
        }

        if (current_user_can('pp_administer_content'))
            return;

        if (!$ref_post = get_post($_REQUEST['pageID']))
            return;

        $parent_id = ('inside' == $_REQUEST['type']) ? $ref_post->ID : $ref_post->post_parent;

        $this->limit_parent($parent_id, $_REQUEST['post_type']);
    }

    function add_pages()
    {
        if (empty($_REQUEST['ref_post_id'])) {
            return;
        }

        if (current_user_can('pp_administer_content'))
            return;

        if (!$ref_post = get_post($_REQUEST['ref_post_id']))
            return;

        $parent_id = ('inside' == $_REQUEST['cms_tpv_add_type']) ? $ref_post->ID : $ref_post->post_parent;

        $this->limit_parent($parent_id, $ref_post->post_type);
    }

    function move_page()
    {
        if (empty($_REQUEST['ref_node_id'])) {
            return;
        }

        if (current_user_can('pp_administer_content'))
            return;

        if (!$ref_post = get_post($_REQUEST['ref_node_id']))
            return;

        $parent_id = ('inside' == $_REQUEST['type']) ? $ref_post->ID : $ref_post->post_parent;

        $this->limit_parent($parent_id, $ref_post->post_type);
    }

    function limit_parent($parent_id, $post_type)
    {
        $user = presspermit()->getUser();

        $include_ids = $user->getExceptionPosts('associate', 'include', $post_type);
        $exclude_ids = $user->getExceptionPosts('associate', 'exclude', $post_type);
        $additional_ids = $user->getExceptionPosts('associate', 'additional', $post_type);

        if (in_array($parent_id, $additional_ids) || ($parent_id && in_array($parent_id, $include_ids))) {
            return;  // user has an additional or include exception for requested parent.  

            // note: CMS Tree UI Add inside/after links not filtered, but site-wide 
            // constants PP_CMS_TREE_NO_ADD, PP_CMS_TREE_NO_ADD_PAGE, PP_CMS_TREE_NO_ADD_THINGY etc. 
            // are applied when user has any page association exceptions
        }

        if (((count($include_ids) || in_array($parent_id, $exclude_ids)) && !in_array($parent_id, $include_ids))
            || (defined('PRESSPERMIT_COLLAB_VERSION') && !$parent_id && !Collab::userCanAssociateMain($post_type))
        ) {
            // user has an exclude exception for requested parent OR an include exception for "(none)"

            if (function_exists('_default_wp_die_handler')) {
                $function = apply_filters('wp_die_handler', '_default_wp_die_handler');
                $msg = '<p>' 
                . __('Page creation was blocked because you are not allowed to select that parent page.', 'ppp') 
                . '</p><p><a href="' . admin_url('') . '">' . __('Back to Dashboard', 'ppp') . '</a></p>';
               
                call_user_func($function, $msg, __('Permission Denied', 'ppp'));
            } else
                wp_die(__('You are not allowed to select that parent page.', 'ppp'));
        }
    }

    function get_pages_args($args)
    {
        // CMS Tree Page View passes orderby arg with multiple values separated by spaces
        if (!empty($args['orderby']) && is_scalar($args['orderby'])) {
            $args['sort_column'] = str_replace(' ', ',', $args['orderby']);
        }

        return $args;
    }

    function filter_posts($posts, $args)
    {
        if (isset($args['xsuppress_filters']) && isset($args['post_type']) && ('post' == $args['post_type'])) {
            global $wpdb;

            $post_ids = [];
            foreach (array_keys($posts) as $i)
                $post_ids[] = $posts[$i]->ID;

            $query = apply_filters(
                'presspermit_posts_request', 
                "SELECT ID FROM $wpdb->posts WHERE post_type = 'post' AND ID IN ('" . implode("','", $post_ids) . "')"
            );
            
            $ok_ids = $wpdb->get_col($query);
            $altered = false;

            foreach (array_keys($posts) as $i) {
                if (!in_array($posts[$i]->ID, $ok_ids)) {
                    unset($posts[$i]);
                    $altered = true;
                }
            }

            if ($altered)
                $posts = array_values($posts);
        }

        return $posts;
    }

    // otherwise, pages editable via exception are not displayed if they have an uneditable parent
    function parent_clause($parent_clause, $args = [])
    {
        $user = presspermit()->getUser();

        if ($page_exceptions = array_merge(
            $user->getExceptionPosts('edit', 'additional', $args['post_type']), 
            $user->getExceptionPosts('edit', 'include', $args['post_type'])
        )) {
            if (empty($args['parent'])) {
                $id_csv = implode("','", $page_exceptions);
                $parent_clause = "( ( $parent_clause ) OR ( ID IN ( '$id_csv' ) AND post_parent NOT IN ( '$id_csv' ) ) )";
            }
        }

        return $parent_clause;
    }

    function no_add()
    {
        if (current_user_can('pp_administer_content')) {
            return;
        }
        ?>
        <style type="text/css">
            <?php foreach( presspermit()->getEnabledPostTypes() as $post_type ):
                if ( defined( 'PP_CMS_TREE_NO_ADD' ) || defined( 'PP_CMS_TREE_NO_ADD_' . strtoupper( $post_type ) ) ) {
                    global $current_screen;
                
                    $user = presspermit()->getUser();
            
                    $include_ids = $user->getExceptionPosts( 'associate', 'include', $post_type );
                    $exclude_ids = $user->getExceptionPosts( 'associate', 'exclude', $post_type );
                    
                    if ( count($include_ids) || count($exclude_ids) || ( defined( 'PRESSPERMIT_COLLAB_VERSION' ) 
                    && ! Collab::userCanAssociateMain( $post_type ) ) 
                    ) {
                        echo "a.cms_tpv_action_add_{$post_type}_after, a.cms_tpv_action_add_{$post_type}_inside, span.cms_tpv_action_add_{$post_type} { display:none }";
                    }
                }
            endforeach;?>
        </style>
        <?php
    }
}
