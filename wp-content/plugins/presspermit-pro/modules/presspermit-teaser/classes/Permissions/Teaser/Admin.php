<?php
namespace PublishPress\Permissions\Teaser;

/**
 * PPTX_AdminFilters class
 *
 * @package PressPermit
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Kevin Behrens
 *
 */
class Admin
{
    function __construct()
    {
        if ('presspermit-settings' == presspermitPluginPage()) {
            $urlpath = plugins_url('', PRESSPERMIT_TEASER_FILE);
            wp_enqueue_style('presspermit-teaser-settings', $urlpath . '/common/css/settings.css', [], PRESSPERMIT_TEASER_VERSION);

            add_action('presspermit_options_ui', [$this, 'actOptionsUI']);
        }
    }

    function actOptionsUI()
    {
        require_once(PRESSPERMIT_TEASER_CLASSPATH . '/UI/SettingsTabTeaser.php');
        new UI\SettingsTabTeaser();
    }
}
