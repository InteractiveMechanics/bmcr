<?php
/**
 * File responsible for defining basic addon class
 *
 * @package     PublishPress\Permissions
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Addon\Permissions;

use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Loader_Filesystem;

defined('ABSPATH') or die('No direct script access allowed.');

class Plugin
{
    /**
     * Twig instance
     *
     * @var Twig
     */
    protected $twig;

    /**
     * Flag for debug
     *
     * @var boolean
     */
    protected $debug = false;

    /**
     * The constructor
     */
    public function __construct()
    {
        $twigPath = PP_PERMISSIONS_PATH_BASE . 'twig';

        $loader     = new Twig_Loader_Filesystem($twigPath);
        $this->twig = new Twig_Environment($loader, [
            'debug' => $this->debug,
        ]);

        if ($this->debug) {
            $this->twig->addExtension(new Twig_Extension_Debug());
        }
    }

    /**
     * The method which runs the plugin
     */
    public function init()
    {
        if ( ! $this->checkRequirements()) {
            add_action('admin_notices', [$this, 'warning_requirements']);

            return false;
        }

        add_filter('pp_module_dirs', [$this, 'filter_module_dirs']);

        add_action('pp_permissions_install', [Installer::class, 'install']);
        add_action('pp_permissions_upgrade', [Installer::class, 'upgrade']);

        add_action('init', [$this, 'manage_installation'], 11);
    }

    /**
     * Check if the system complies the requirements
     *
     * @return bool
     */
    protected function checkRequirements()
    {
        return defined('PUBLISHPRESS_VERSION') && version_compare(PUBLISHPRESS_VERSION, '1.4.0', 'ge');
    }

    /**
     * Manages the installation detecting if this is the first time this module runs or is an upgrade.
     * If no version is stored in the options, we treat as a new installation. Otherwise, we check the
     * last version. If different, it is an upgrade or downgrade.
     */
    public function manage_installation()
    {
        $option_name = 'publishpress_permissions_version';

        $previous_version = get_option($option_name);
        $redirect         = false;

        if (empty($previous_version)) {
            /**
             * Action called when the module is installed.
             *
             * @param string $current_version
             */
            do_action('pp_permissions_install', PUBLISHPRESS_PERMISSIONS_VERSION);
            $redirect = true;
        } elseif (version_compare($previous_version, PUBLISHPRESS_PERMISSIONS_VERSION, '>')) {

            /**
             * Action called when the module is downgraded.
             *
             * @param string $previous_version
             */
            do_action('pp_permissions_downgrade', $previous_version);
            $redirect = true;
        } elseif (version_compare($previous_version, PUBLISHPRESS_PERMISSIONS_VERSION, '<')) {

            /**
             * Action called when the module is upgraded.
             *
             * @param string $previous_version
             */
            do_action('pp_permissions_upgrade', $previous_version);
        }

        if (PUBLISHPRESS_PERMISSIONS_VERSION !== $previous_version) {
            update_option($option_name, PUBLISHPRESS_PERMISSIONS_VERSION, true);

            $redirect = true;
        }

        // Redirect to make sure we have a clean start after any installation
        if ($redirect) {
            wp_redirect(admin_url('admin.php?page=pp-calendar'));
            exit;
        }
    }

    /**
     * Add custom module directory
     *
     * @param  array
     *
     * @return array
     */
    public function filter_module_dirs($dirs)
    {
        $dirs['permissions'] = rtrim(PP_PERMISSIONS_PATH_BASE, '/');

        return $dirs;
    }

    public function warning_requirements()
    {
        echo $this->twig->render(
            'requirements-warning.twig',
            [
                'lang' => [
                    'publishpress' => __('PublishPress', 'publishpress-permissions'),
                    'warning'      => __('PublishPress Permissions requires __plugin__ 1.3.0 or later. Please, update.',
                        'publishpress-permissions'),
                ],
            ]
        );
    }
}
