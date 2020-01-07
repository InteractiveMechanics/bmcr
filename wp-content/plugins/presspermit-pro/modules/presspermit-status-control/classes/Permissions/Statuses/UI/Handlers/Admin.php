<?php
namespace PublishPress\Permissions\Statuses\UI\Handlers;

class Admin
{
    public static function handleRequest()
    {
        // This script executes on plugin load if is_admin(), for POST requests or if the action, action 2 or pp_action REQUEST arguments are non-empty
        //

        if (!empty($_POST)) {
            if (in_array(presspermitPluginPage(), ['presspermit-status-edit', 'presspermit-status-new'], true)) {
                add_action(
                    'wp_loaded', 
                    function()
                    {
                        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Handlers/StatusEdit.php');
                        StatusEdit::handleRequest();
                    }
                );
            }
        }

        if (!empty($_REQUEST['action']) || !empty($_REQUEST['action2']) || !empty($_REQUEST['pp_action'])) {
            if (strpos($_SERVER['REQUEST_URI'], 'page=presspermit-statuses') || (!empty($_REQUEST['wp_http_referer']) && (strpos($_REQUEST['wp_http_referer'], 'page=presspermit-statuses')))) {
                add_action(
                    'init', 
                    function()
                    {
                        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Handlers/Status.php');
                        Status::handleRequest();
                    }
                    , 100000
                );
            }
        }
    }
}
