<?php
namespace PublishPress\Permissions;

class CompatHooksAdmin
{
    var $netwide_groups;

    function __construct()
    {
        if (defined('CMS_TPV_VERSION') && defined('PRESSPERMIT_COLLAB_VERSION')) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/CMSTreeView.php');
            new Compat\CMSTreeView();
        }

        if (defined('CPTUI_VERSION')) {
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/CPTUI.php');
            new Compat\CPTUI();
        }

        add_action('presspermit_options_ui', [$this, 'optionsUI']);

        add_action('admin_enqueue_scripts', [$this, 'act_scripts']);

        if (is_multisite()) {
            if (did_action('init'))
                $this->load_options();
            else
                add_action('init', [$this, 'load_options']);
        }

        add_filter('presspermit_user_has_group_cap', [$this, 'flt_has_group_cap'], 10, 4);

        add_action('init', [$this, 'relevanssi_init']);
    }

    function optionsUI()
    {
        require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/UI/Settings.php');
        new Compat\UI\Settings();
    }

    function relevanssi_init()
    {
        if (function_exists('relevanssi_query')) {  // wait until init action for this check
            // make sure posts with custom privacy are included in index
            require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/Relevanssi/HooksAdmin.php');
            new Compat\Relevanssi\HooksAdmin();
        }
    }

    function act_scripts()
    {
        if ('presspermit-settings' == presspermitPluginPage()) {
            $urlpath = plugins_url('', PRESSPERMIT_COMPAT_FILE);
            wp_enqueue_style('presspermit-compat-settings', $urlpath . '/common/css/settings.css', [], PRESSPERMIT_COMPAT_VERSION);

            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '-dev' : '';
            wp_enqueue_script('presspermit-compat-settings', $urlpath . "/common/js/settings{$suffix}.js", ['jquery'], PRESSPERMIT_COMPAT_VERSION);
        }
    }

    function load_options()
    {
        if ($this->netwide_groups = get_site_option('presspermit_netwide_groups')) {
            // To avoid confusion, prevent manual url access to site-specific group admin if network-wide groups active
            /*
            if (!empty($_REQUEST['agent_type']) && ('pp_group' == $_REQUEST['agent_type']) 
            && (empty($_REQUEST['group_variant']) || in_array($_REQUEST['group_variant'], ['pp_group', 'pp_net_group'], true))
            ) {
                wp_redirect(add_query_arg('agent_type', 'pp_net_group', remove_query_arg('agent_type', $_SERVER['REQUEST_URI'])));
            }
            */

            add_filter('presspermit_list_group_types', [$this, 'netwide_list_group_types']);
            add_filter('presspermit_query_group_type', [$this, 'netwide_query_agent_type']);
            add_filter('presspermit_query_group_variant', [$this, 'netwide_query_agent_type']);
            add_filter('presspermit_editable_group_types', [$this, 'netwide_editable_group_types'], 10, 3);
            add_action('delete_user', [$this, 'actDeleteUsers']);

            add_filter('presspermit_user_search_site_only', [$this, 'user_search_site_only'], 10, 2);
            add_filter('presspermit_agents_selection_ui_args', [$this, 'agents_selection_ui_args'], 50, 3);
        } else {
            global $blog_id;
            if (is_network_admin())
                add_filter('presspermit_editable_group_types', [$this, 'netwide_no_groups'], 10, 3);
        }
    }

    function agents_selection_ui_args($args, $agent_type, $id_suffix)
    {
        if (('user' == $agent_type) && !empty($_REQUEST['agent_type']) && ('pp_net_group' == $_REQUEST['agent_type'])) {
            $args['context'] = 'pp_net_group';
        }

        return $args;
    }

    // when user search ajax is used on multisite, should we limit the results set to users registered for the current site?
    function user_search_site_only($site_only, $args)
    {
        global $blog_id;

        if (('pp_net_group' == $args['context']) && !defined('PP_NETWORK_GROUPS_SITE_USERS_ONLY') 
        && (is_super_admin() || current_user_can('pp_manage_network_members')) 
        && ((1 != $blog_id) || defined('PP_NETWORK_GROUPS_MAIN_SITE_ALL_USERS'))
        ) {
            return false;
        }

        return $site_only;
    }

    function flt_has_group_cap($has_sitewide, $cap_name, $group_id, $group_type)
    {
        if (is_multisite() && ('pp_net_group' == $group_type)) {
            switch ($cap_name) {
                case 'pp_manage_members' :
                    return is_super_admin() || current_user_can('pp_manage_network_members');
                    break;

                case 'pp_create_groups' :
                    return is_super_admin() || current_user_can('pp_create_network_groups');
                    break;
            }
        }

        return $has_sitewide;
    }

    function netwide_editable_group_types($types)
    {
        global $pagenow;
        
        return (is_network_admin() || ('user-edit.php' == $pagenow)) 
        ? array_diff(array_merge($types, ['pp_net_group']), ['pp_group']) 
        : array_merge($types, ['pp_net_group']);
    }

    function netwide_no_groups($types)
    {
        return [];
    }

    function actDeleteUsers($user_ids)
    {
        global $wpdb;
        $id_csv = implode("','", (array)$user_ids);
        $wpdb->query("DELETE FROM $wpdb->pp_group_members_netwide WHERE user_id IN '$id_csv';");
    }

    function netwide_membership_editable($editable, $agent_type, $agent)
    {
        if ('pp_net_group' == $agent_type)
            return true;

        return $editable;
    }

    function netwide_query_agent_type($agent_type)
    {
        if (!$agent_type)
            $agent_type = 'pp_net_group';

        return $agent_type;
    }

    function netwide_list_group_types($group_types)
    { 
        global $blog_id;

        if (1 == $blog_id) {
            unset($group_types['pp_group']);
        }

        return $group_types;
    }
}
