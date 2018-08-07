<?php
/**
 * Add-on initializer class.
 *
 * @package     PublishPressAddonFramework
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPressAddonFramework;

if (!function_exists('is_plugin_inactive')) {
    include_once(ABSPATH . 'wp-admin/includes/plugin.php');
}

class Initializer
{
    const PUBLISHPRESS_FILE = 'publishpress/publishpress.php';

    protected $pluginName;

    protected $minVersion;

    /**
     * Initializer constructor.
     *
     * @param string $pluginName
     * @param string $minVersion
     */
    public function __construct($pluginName, $minVersion)
    {
        $this->pluginName = $pluginName;
        $this->minVersion = $minVersion;
    }

    /**
     * Check if PublishPress is installed and active. Check if we have the correct
     * version installed.
     *
     * @return bool
     */
    public function isPublishPressInstalled()
    {
        $publishpress_path = WP_PLUGIN_DIR . '/' . self::PUBLISHPRESS_FILE;

        // Check if PublishPress is installed.
        if (!file_exists($publishpress_path) || is_plugin_inactive(self::PUBLISHPRESS_FILE)) {
            add_action('admin_notices', [$this, 'noticePublishPressNotFound']);

            return false;
        }

        // Try to load PublishPress if not loaded yet.
        if (!defined('PUBLISHPRESS_VERSION')) {
            require_once $publishpress_path;
        }

        // Check PublishPress minimum version.
        if (!defined('PUBLISHPRESS_VERSION')
            || version_compare(PUBLISHPRESS_VERSION, $this->minVersion, '<')) {

            add_action('admin_notices', [$this, 'noticePublishPressWrongVersion']);

            return false;
        }

        return true;
    }

    /**
     * Check if WooCommerce is installed.
     *
     * @return bool
     */
    public function isWooCommerceInstalled()
    {
        if (!file_exists(WP_PLUGIN_DIR . '/woocommerce/woocommerce.php')) {
            add_action('admin_notices', [$this, 'noticeWooCommerceNotFound']);

            return false;
        }

        return true;
    }

    /**
     * Displays an admin notice saying PublishPress was not found or is not active.
     */
    public function noticePublishPressNotFound()
    {
        echo '<div class="notice notice-error is-dismissible"><p>';
        echo sprintf(
            __('Please, install and activate the %s plugin in order to make %s work.', 'publishpress-addon-framework'),
            '<a href="https://wordpress.org/plugins/publishpress" target="_blank">PublishPress</a>',
            $this->pluginName
        );
        echo '</p></div>';
    }

    /**
     * Displays an admin notice saying PublishPress is not at the correct version.
     *
     * @param string $minVersion
     */
    public function noticePublishPressWrongVersion($minVersion)
    {
        echo '<div class="notice notice-error is-dismissible"><p>';
        echo sprintf(
            __('Sorry, %s requires %s version %s or later.', 'publishpress-addon-framework'),
            $this->pluginName,
            '<a href="https://wordpress.org/plugins/publishpress" target="_blank">PublishPress</a>',
            $this->minVersion
        );
        echo '</p></div>';
    }

    /**
     * Displays an admin notice saying WooCommerce was not found or is not active.
     */
    public function noticeWooCommerceNotFound()
    {
        echo '<div class="notice notice-error is-dismissible"><p>';
        echo sprintf(
            __('Sorry, %s requires %s . Please, install it.', 'publishpress-addon-framework'),
            $this->pluginName,
            '<a href="https://wordpress.org/plugins/woocommerce" target="_blank">WooCommerce</a>'
        );
        echo '</p></div>';
    }
}
