<?php
namespace PublishPress\Permissions\Statuses\UI\Handlers;

class Status
{
    public static function handleRequest()
    {
        $url = $referer = $redirect = $update = '';

        $pp = presspermit();

        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/StatusHelper.php');
        \PublishPress\Permissions\Statuses\UI\StatusHelper::getUrlProperties($url, $referer, $redirect);

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        if (!$action)
            $action = isset($_REQUEST['pp_action']) ? $_REQUEST['pp_action'] : '';

        $attribute = 'post_status';
        $attrib_type = sanitize_key($_REQUEST['attrib_type']);

        switch ($action) {

            case 'dodelete':
                check_admin_referer('delete-conditions');

                if (!current_user_can('pp_define_post_status') && (!$attrib_type || !current_user_can("pp_define_{$attrib_type}")))
                    wp_die(__('You are not permitted to do that.', 'pps'));

                if (empty($_REQUEST['pp_conditions']) && empty($_REQUEST['status'])) {
                    wp_redirect($redirect);
                    exit();
                }

                if (empty($_REQUEST['pp_conditions']))
                    $conds = [$_REQUEST['status']];
                else
                    $conds = (array)$_REQUEST['pp_conditions'];

                $update = 'del';
                $delete_conds = [];

                foreach ((array)$conds as $cond) {
                    $delete_conds[$cond] = true;
                }

                if (!$delete_conds)
                    wp_die(__('You can&#8217;t delete that status.', 'pps'));

                $conds = (array)get_option("pp_custom_conditions_{$attribute}");

                $conds = array_diff_key($conds, $delete_conds);

                update_option("pp_custom_conditions_{$attribute}", $conds);

                // PublishPress integration
                if (taxonomy_exists('post_status') && !defined('PP_DISABLE_EF_STATUS_SYNC')) {
                    foreach (array_keys($delete_conds) as $status) {
                        if (!in_array($status, ['draft', 'pending', 'pitch'])) {
                            if ($term = get_term_by('slug', $status, 'post_status'))
                                wp_delete_term($term->term_id, 'post_status');
                        }
                    }
                }

                $redirect = add_query_arg(['delete_count' => count($delete_conds), 'update' => $update, 'pp_attribute' => $attribute, 'attrib_type' => $attrib_type], $redirect);
                wp_redirect($redirect);
                exit();

                break;

            case 'delete' :
                check_admin_referer('bulk-conditions');

                if (!current_user_can('pp_define_post_status') && (!$attrib_type || !current_user_can("pp_define_{$attrib_type}")))
                    wp_die(__('You are not permitted to do that.', 'pps'));

                if (!empty($_REQUEST['pp_conditions'])) {
                    $redirect = add_query_arg(['pp_action' => 'bulkdelete', 'wp_http_referer' => $_REQUEST['wp_http_referer'], 'pp_conditions' => $_REQUEST['pp_conditions']], $redirect);
                    wp_redirect($redirect);
                    exit();
                }

                break;

            case 'disable' :
            case 'enable' :
                check_admin_referer('bulk-conditions');

                if (!current_user_can('pp_define_post_status') && (!$attrib_type || !current_user_can("pp_define_{$attrib_type}")))
                    wp_die(__('You are not permitted to do that.', 'pps'));

                if (empty($_REQUEST['status']))
                    break;

                /*
                $custom_stati = (array)get_option("presspermit_custom_conditions_post_status");
                $private_stati = [];
                foreach ($custom_stati as $status => $status_obj) {
                    if ($_status_obj = get_post_status_object($status)) {
                        if (!empty($_status_obj->private)) {
                            $private_stati[] = $status;
                        }
                    } else {
                        // @todo : remove this?
                        if (empty($status_obj['moderation']) && !in_array($_REQUEST['status'], ['approved', 'pitch', 'in-progress', 'assigned'])) {
                            $private_stati[] = $status;
                        }
                    }
                }
                $private_stati = array_filter($private_stati);
                */
                $private_stati = PWP::getPostStatuses(['private' => true]);

                if (in_array($_REQUEST['status'], ['pending', 'future']) || !in_array($_REQUEST['status'], $private_stati, true)) {
                    $pp->updateOption("custom_{$_REQUEST['status']}_caps", ('enable' == $action));
                }

                // All privacy statuses, as well as moderation statuses defined by PressPermit, can be fully disabled
                if (in_array($_REQUEST['status'], $private_stati, true) || ('approved' == $_REQUEST['status'])) {
                    $disabled_conditions = (array)$pp->getOption("disabled_{$attribute}_conditions");
                    $disabled_conditions = array_filter($disabled_conditions);

                    if ('enable' == $action)
                        $disabled_conditions = array_diff_key($disabled_conditions, [$_REQUEST['status'] => true]);
                    else
                        $disabled_conditions[$_REQUEST['status']] = true;

                    $pp->updateOption("disabled_{$attribute}_conditions", $disabled_conditions);
                }

                // If capability status was set to nullstring ("Default Capabilities), also clear that 
                // (resulting in default of own capability status) 
                if ('enable' == $action) {
                    if ($capability_status = $pp->getOption('status_capability_status')) {
                        if (isset($capability_status[$_REQUEST['status']]) 
                        && ('' === $capability_status[$_REQUEST['status']])
                        ) {
                            unset($capability_status[$_REQUEST['status']]);
                            $pp->updateOption("status_capability_status", $capability_status);
                        }
                    }
                }

                $redirect = add_query_arg(['update' => 'edit', 'pp_attribute' => $attribute, 'attrib_type' => $attrib_type], $redirect);
                wp_redirect($redirect);
                exit();

                break;

            default:
                if (!empty($_GET['wp_http_referer'])) {
                    wp_redirect(remove_query_arg(['wp_http_referer', '_wpnonce'], stripslashes($_SERVER['REQUEST_URI'])));
                    exit;
                }
        } // end switch
    }
}
