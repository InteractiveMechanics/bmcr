<?php
/**
 * PublishPress Permissions plugin bootstrap file.
 *
 * @link        https://publishpress.com/permissions/
 * @package     PublishPress\Permissions
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 *
 * @publishpress-permissions
 * Plugin Name: PublishPress Permissions
 * Plugin URI:  https://publishpress.com/
 * Version: 2.2.0
 * Description: Add permissions support for PublishPress
 * Author:      PublishPress
 * Author URI:  https://publishpress.com
 * Text Domain: publishpress-permissions
 */

require_once __DIR__ . '/includes.php';

if (defined('PP_PERMISSIONS_LOADED')) {
    $plugin = new PublishPress\Addon\Permissions\Plugin;
    $plugin->init();
}
