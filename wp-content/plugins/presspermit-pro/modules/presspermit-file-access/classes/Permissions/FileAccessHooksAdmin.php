<?php
namespace PublishPress\Permissions;

class FileAccessHooksAdmin
{
    function __construct() {
        add_action('admin_menu', [$this, 'actMenu'], 99);
        add_action('presspermit_options_ui', [$this, 'actOptions']);

        add_filter('presspermit_exception_types', [$this, 'fltExceptionTypes']);
        add_filter('presspermit_exception_operations', [$this, 'actExceptionOperations'], 1, 3);
    }

    function actMenu()
    {
        // satisfy WordPress' demand that all admin links be properly defined in menu
        if ('presspermit-attachments_utility' == presspermitPluginPage()) {
            add_submenu_page(
                presspermit()->admin()->getMenuParams('permits'), 
                __('Attachment Utility', 'presspermit'), 
                __('Attachment Utility', 'presspermit'), 
                'read', 
                'presspermit-attachments_utility', 
                [$this, 'attachmentsUtility']
            );
        }
    }

    function attachmentsUtility()
    {
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/UI/SettingsUtility.php');
        FileAccess\UI\SettingsUtility::display();
    }

    function actOptions()
    {
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/UI/SettingsTabFileAccess.php');
        new FileAccess\UI\SettingsTabFileAccess();
    }

    function fltExceptionTypes($types)
    {
        if (!isset($types['attachment']))
            $types['attachment'] = get_post_type_object('attachment');
        return $types;
    }

    function actExceptionOperations($ops, $for_item_source, $for_item_type)
    {
        if (('post' == $for_item_source) && ('attachment' == $for_item_type) && !isset($ops['read'])) {
            $op_obj = presspermit()->admin()->getOperationObject('read', $for_item_type);
            $ops['read'] = $op_obj->label;
        }

        return $ops;
    }
}
