<?php
namespace PublishPress\Permissions\Statuses\UI;

class Admin
{
    function __construct() 
    {
        // This script executes on plugin load
        //

        add_filter('display_post_states', [$this, 'flt_display_post_states']);

        add_action('presspermit_post_admin', [$this, 'act_post_admin_ui']);           // fires on the 'init' action (late priority 70)
        add_action('presspermit_post_listing_ui', [$this, 'act_post_listing_ui']);    // fires on the 'init' action (late priority 70)
        add_action('presspermit_post_edit_ui', [$this, 'act_post_edit_ui']);          // fires on the 'init' action (late priority 70)
        add_filter('presspermit_post_status_types', [$this, 'flt_status_links'], 1 );  // fires on admin.php plugin page load or admin_head 
        add_filter('presspermit_exceptions_status_ui', [$this, 'fltExceptionsStatusUi'], 8, 3);  // Ajax: UI generation
        add_action('presspermit_options_ui', [$this, 'act_options_ui']);               // fires on admin.php plugin page load

        add_action('admin_enqueue_scripts', [$this, 'act_scripts']);
        add_action('admin_head', [$this, 'actAdminHead']);
        add_action('presspermit_admin_ui', [$this, 'act_publishpress_dependency']);
    }

    function act_scripts()
    {
        global $pagenow;

        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';

        if ($pp_plugin_page = presspermitPluginPage()) {
            wp_enqueue_style('presspermit-statuses', PRESSPERMIT_STATUSES_URLPATH . '/common/css/plugin-pages.css', [], PRESSPERMIT_STATUSES_VERSION);

        } elseif (in_array($pagenow, ['post.php', 'post-new.php'])) {
            wp_enqueue_style('presspermit-statuses-post-edit', PRESSPERMIT_STATUSES_URLPATH . '/common/css/post-edit.css', [], PRESSPERMIT_STATUSES_VERSION);
            wp_enqueue_style('presspermit-statuses-post-edit', PRESSPERMIT_STATUSES_URLPATH . '/common/css/post-edit-ie.css', [], PRESSPERMIT_STATUSES_VERSION);
        }

        if (!defined('PPS_NATIVE_CUSTOM_STATI_DISABLED')) {
            wp_enqueue_script('presspermit-statuses-misc', PRESSPERMIT_STATUSES_URLPATH . "/common/js/statuses{$suffix}.js", ['jquery'], PRESSPERMIT_STATUSES_VERSION, false);
        }

        if (in_array($pp_plugin_page, ['presspermit-status-edit', 'presspermit-status-new'], true)) {
            wp_enqueue_script('presspermit-status-edit', PRESSPERMIT_STATUSES_URLPATH . "/common/js/status-edit{$suffix}.js", ['jquery', 'jquery-form'], PRESSPERMIT_STATUSES_VERSION, true);
        }

        if ('presspermit-statuses' == $pp_plugin_page) {
            wp_enqueue_script('presspermit-statuses-status-list', PRESSPERMIT_STATUSES_URLPATH . "/common/js/status-list{$suffix}.js", ['jquery', 'jquery-form'], PRESSPERMIT_STATUSES_VERSION, true);
        }
    }

    function actAdminHead()
    {
        if (defined('PUBLISHPRESS_VERSION') && ((strpos($_SERVER['REQUEST_URI'], 'module=pp-custom-status-settings') || (strpos($_SERVER['REQUEST_URI'], 'page=pp-manage-capabilities'))))) {
            if (PPS::publishpressStatusesActive('', ['skip_status_dropdown_check' => true])) {
                require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/PublishPressSettings.php');
                PublishPressSettings::scripts();  // @todo: .js
            }
        }
    }

    function act_publishpress_dependency()
    {
        if (defined('PUBLISHPRESS_VERSION')) {
            if (!defined('PRESSPERMIT_COLLAB_VERSION')) {
                presspermit()->admin()->notice(__('PublishPress integration also requires the PressPermit Collaborative Publishing module.', 'pps'));
            }
        }
    }

    function flt_display_post_states($stati)
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Dashboard/PostsListing.php'); // normally loaded already
        return Dashboard\PostsListing::fltDisplayPostStates($stati);
    }

    function act_post_admin_ui()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Dashboard/PostAdmin.php');
        new Dashboard\PostAdmin();
    }

    function act_post_listing_ui()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Dashboard/PostsListing.php');
        new Dashboard\PostsListing();
    }

    function act_post_edit_ui()
    {
        if (in_array(PWP::findPostType(), ['forum', 'topic', 'reply'])) // future @todo: support bbp custom privacy as applicable
            return;

        if (PWP::isBlockEditorActive()) {
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Gutenberg/PostEdit.php');
            new Gutenberg\PostEdit();
        } else {
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Dashboard/PostEdit.php');
            new Dashboard\PostEdit();
        }
    }

    function flt_status_links($links)
    {
        if ((current_user_can('pp_define_post_status') || current_user_can('pp_define_privacy')) && !defined('PPS_NATIVE_CUSTOM_STATI_DISABLED'))
            $links[] = (object)['attrib_type' => 'private', 'url' => 'admin.php?page=presspermit-statuses&amp;attrib_type=private', 'label' => __('Visibility', 'presspermit')];

        return $links;
    }

    function act_options_ui()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/SettingsTabStatuses.php');
        new SettingsTabStatuses();
    }

    function fltExceptionsStatusUi($html, $for_type, $args = [])
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/PermissionsAjax.php');
        return PermissionsAjax::fltExceptionsStatusUi($html, $for_type, $args);
    }
}
