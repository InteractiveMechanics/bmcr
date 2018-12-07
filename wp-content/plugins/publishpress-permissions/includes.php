<?php
/**
 * File responsible for defining basic general constants used by the plugin.
 *
 * @package     PublishPress\Permissions
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

use PublishPress\Addon\Permissions\Auto_loader;

defined('ABSPATH') or die('No direct script access allowed.');


if ( ! function_exists('is_plugin_inactive')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}


/*======================================================================
=            Check if PublishPress is installed and active             =
======================================================================*/
$publishpressPath = WP_PLUGIN_DIR . '/publishpress/publishpress.php';
if ( ! file_exists($publishpressPath) || is_plugin_inactive('publishpress/publishpress.php')) {
    function pp_permissions_admin_error()
    {
        ?>
        <div class="notice notice-error is-dismissible">
            <p>Please, install and activate the <a href="https://wordpress.org/plugins/publishpress" target="_blank">PublishPress</a>
                plugin in order to make <em>PublishPress Permissions</em> work.</p>
        </div>
        <?php
    }

    add_action('admin_notices', 'pp_permissions_admin_error');

    define('PP_PERMISSIONS_HALT', 1);
}
/*=====  End of Check if PublishPress is installed and active   ======*/

if ( ! defined('PP_PERMISSIONS_HALT')) {
    require_once $publishpressPath;

    if ( ! defined('PP_PERMISSIONS_MIN_PARENT_VERSION')) {
        define('PP_PERMISSIONS_MIN_PARENT_VERSION', '1.11.4');
    }

    /*==========================================================
    =            Check PublishPress minimum version            =
    ==========================================================*/
    if (version_compare(PUBLISHPRESS_VERSION, PP_PERMISSIONS_MIN_PARENT_VERSION, '<')) {
        function pp_permissions_admin_version_error()
        {
            ?>
            <div class="notice notice-error is-dismissible">
                <p>Sorry, PublishPress Permissions requires <a href="https://wordpress.org/plugins/publishpress"
                                                               target="_blank">PublishPress</a>
                    version <?php echo PP_PERMISSIONS_MIN_PARENT_VERSION; ?> or later.</p>
            </div>
            <?php
        }

        add_action('admin_notices', 'pp_permissions_admin_version_error');

        define('PP_PERMISSIONS_HALT', 1);
    }
    /*=====  End of Check PublishPress minimum version  ======*/

    if ( ! defined('PP_PERMISSIONS_HALT') && ! defined('PP_PERMISSIONS_LOADED')) {
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        }

        if ( ! defined('PP_PERMISSIONS')) {
            define('PP_PERMISSIONS', 'Permissions');
        }

        if ( ! defined('PP_PERMISSIONS_NAME')) {
            define('PP_PERMISSIONS_NAME', 'PublishPress Permissions');
        }

        if ( ! defined('PP_PERMISSIONS_SLUG')) {
            define('PP_PERMISSIONS_SLUG', strtolower(PP_PERMISSIONS));
        }

        if ( ! defined('PP_PERMISSIONS_NAMESPACE')) {
            define('PP_PERMISSIONS_NAMESPACE', 'PublishPress\\Addon\\Permissions');
        }

        if ( ! defined('PP_PERMISSIONS_PATH_BASE')) {
            define('PP_PERMISSIONS_PATH_BASE', plugin_dir_path(__FILE__));
        }

        if ( ! defined('PP_PERMISSIONS_PATH_CORE')) {
            define('PP_PERMISSIONS_PATH_CORE', PP_PERMISSIONS_PATH_BASE . PP_PERMISSIONS);
        }

        if ( ! defined('PUBLISHPRESS_PERMISSIONS_VERSION')) {
            define('PUBLISHPRESS_PERMISSIONS_VERSION', '2.2.0');
        }

        if ( ! defined('PP_PERMISSIONS_MODULE_PATH')) {
            define('PP_PERMISSIONS_MODULE_PATH', __DIR__ . '/modules/permissions');
        }

        if ( ! defined('PP_PERMISSIONS_FILE')) {
            define('PP_PERMISSIONS_FILE', 'publishpress-permissions/publishpress-permissions.php');
        }

        if ( ! defined('PP_PERMISSIONS_ITEM_ID')) {
            define('PP_PERMISSIONS_ITEM_ID', '6920');
        }

        if ( ! defined('PP_PERMISSIONS_LIB_PATH')) {
            define('PP_PERMISSIONS_LIB_PATH', PP_PERMISSIONS_PATH_BASE . '/library');
        }

        if ( ! class_exists('PP_Module')) {
            require_once(PUBLISHPRESS_ROOT . '/common/php/class-module.php');
        }

        // Load the modules
        if ( ! class_exists('PP_permissions')) {
            require_once PP_PERMISSIONS_MODULE_PATH . '/permissions.php';
        }

        // Register the autoloader
        if ( ! class_exists('\\PublishPress\\Addon\\Permissions\\Auto_loader')) {
            require_once PP_PERMISSIONS_LIB_PATH . '/Auto_loader.php';
        }

        // Register the library
        Auto_loader::register('\\PublishPress\\Addon\\Permissions', PP_PERMISSIONS_PATH_BASE . '/library');

        // Define the add-on as loaded
        define('PP_PERMISSIONS_LOADED', 1);
    }
}// End if().
