<?php
namespace PublishPress\Permissions\FileAccess;

class Network {
    public static function msBlogsRewriting()
    {
        return is_multisite() && get_site_option('ms_files_rewriting');
    }

    public static function networkActivating() 
    {
        return strpos($_SERVER['REQUEST_URI'], 'network/plugins.php')
        && ( !empty($_REQUEST['activate']) || (isset($_REQUEST['action']) && $_REQUEST['action'] == 'activate'));
    }
}
