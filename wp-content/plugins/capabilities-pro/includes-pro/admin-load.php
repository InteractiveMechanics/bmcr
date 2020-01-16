<?php
namespace PublishPress\Capabilities;

class AdminFiltersPro {
    function __construct() {
        add_action('init', [$this, 'versionInfoRedirect'], 1);
        add_action('admin_init', [$this, 'loadUpdater']);

        add_action('publishpress-caps_manager-load', [$this, 'CapsManagerLoad']);
        add_action('admin_print_styles', array($this, 'adminStyles'));

        add_action('publishpress-caps_manager_postcaps_section', [$this, 'capsManagerUI']);

        add_action('publishpress-caps_sidebar_top', [$this, 'keyUI']);
        //add_action('publishpress-caps_sidebar_bottom', [$this, 'sidebarUI']);

        add_action('publishpress-caps_process_update', [$this, 'updateOptions']);
    }

    function versionInfoRedirect() {
        if (!empty($_REQUEST['publishpress_caps_refresh_updates'])) {
            publishpress_caps_pro()->keyStatus(true);
            set_transient('publishpress-caps-refresh-update-info', true, 86400);

            delete_site_transient('update_plugins');
            delete_option('_site_transient_update_plugins');

            $opt_val = get_option('cme_edd_key');
            if (is_array($opt_val) && !empty($opt_val['license_key'])) {
                $plugin_slug = basename(CME_FILE, '.php'); // 'capabilities-pro-by-publishpress';
                $plugin_relpath = basename(dirname(CME_FILE)) . '/' . basename(CME_FILE);  // $_REQUEST['plugin']
                $license_key = $opt_val['license_key'];
                $beta = false;

                delete_option(md5(serialize($plugin_slug . $license_key . $beta)));
                delete_option('edd_api_request_' . md5(serialize($plugin_slug . $license_key . $beta)));
                delete_option(md5('edd_plugin_' . sanitize_key($plugin_relpath) . '_' . $beta . '_version_info'));
            }

            wp_update_plugins();
            //wp_version_check(array(), true);

            if (current_user_can('update_plugins')) {
                $url = remove_query_arg('publishpress_caps_refresh_updates', $_SERVER['REQUEST_URI']);
                $url = add_query_arg('publishpress_caps_refresh_done', 1, $url);
                $url = "//" . $_SERVER['HTTP_HOST'] . $url;
                wp_redirect($url);
                exit;
            }
        }

        if (!empty($_REQUEST['publishpress_caps_refresh_done']) && empty($_POST)) {
            if (current_user_can('activate_plugins')) {
                $url = admin_url('update-core.php');
                wp_redirect($url);
            }
        }
    }

    function CapsManagerLoad() {
        require_once(dirname(__FILE__).'/manager-ui.php');
        ManagerUI::loadScripts();
    }

    function loadUpdater() {
        require_once(PUBLISHPRESS_CAPS_ABSPATH . '/includes-pro/library/Factory.php');
        $container = \PublishPress\Capabilities\Factory::get_container();
        return $container['edd_container']['update_manager'];
    }

    function adminStyles() {
        global $plugin_page;

        if (!empty($plugin_page) && ('capsman' == $plugin_page)) {
            wp_enqueue_style('publishpress-caps-pro', plugins_url( '', CME_FILE ) . '/includes-pro/pro.css', [], PUBLISHPRESS_CAPS_VERSION);
            wp_enqueue_style('publishpress-caps-status-caps', plugins_url( '', CME_FILE ) . '/includes-pro/status-caps.css', [], PUBLISHPRESS_CAPS_VERSION);

            add_thickbox();
        }
    }

    function capsManagerUI($args) {
        if (Pro::customStatusPermissionsAvailable() && get_option('cme_custom_status_control')) {
            require_once(dirname(__FILE__).'/admin.php');
            $ui = new CustomStatusCapsUI();
            $ui ->drawUI($args);
        }
    }

    function keyUI() {
        require_once(dirname(__FILE__).'/manager-ui.php');
        ManagerUI::drawKeyUI();

        ManagerUI::drawOptionsUI();
    }

    function updateOptions() {
        update_option('cme_custom_status_control', !empty($_REQUEST['cme_custom_status_control']));
        update_option('cme_display_branding', !empty($_REQUEST['cme_display_branding']));
    }
}
