<?php
namespace PublishPress\Permissions\Statuses\UI\Handlers;

class StatusEdit
{
    public static function handleRequest()
    {
        // This script executes on plugin load if is_admin(), for POST requests with our presspermit-status-edit or presspermit-status-new admin.php URL
        //

        $action = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : '';

        $attribute = 'post_status';
        $attrib_type = (isset($_REQUEST['attrib_type'])) ? sanitize_key($_REQUEST['attrib_type']) : '';

        PPS::attributes();

        if (!current_user_can("pp_define_{$attribute}") && (!$attrib_type || !current_user_can("pp_define_{$attrib_type}")))
            wp_die(__('You are not permitted to do that.', 'pps'));

        switch ($action) {
            case 'update':
                $status = sanitize_key($_REQUEST['status']);
                check_admin_referer('pp-update-condition_' . $status);

                require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Handlers/StatusSave.php');
                $return_array = StatusSave::save($status);
                $retval = $return_array['retval'];
                $redirect = $return_array['redirect'];
                break;
            case 'createcondition':
                check_admin_referer('pp-create-condition', '_wpnonce_pp-create-condition');

                $status = sanitize_key(str_replace(' ', '_', $_REQUEST['status_name']));
                require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Handlers/StatusSave.php');
                $return_array = StatusSave::save($status, true);
                $retval = $return_array['retval'];
                $redirect = $return_array['redirect'];
                break;
        } // end switch

        if (!empty($retval) && is_wp_error($retval)) {
            presspermit()->admin()->errors = $retval;
        } elseif ($redirect) {
            wp_redirect(esc_url_raw(add_query_arg('update', 1, $redirect)));
            exit;
        }
    }
}
