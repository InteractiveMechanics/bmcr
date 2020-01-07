<?php
namespace PublishPress\Permissions\Statuses\UI\Handlers;

class StatusSave
{
    public static function save($status, $new = false)
    {
        $pp = presspermit();

        $arr_return = ['retval' => false, 'redirect' => ''];

        if (strlen($status) > 20)
            $status = substr($status, 0, 20);

        $status_obj = get_post_status_object($status);

        if ($new && $status_obj || in_array($status, ['public', 'password'])) {
            $errors = new \WP_Error();
            $errors->add('status_name', __('<strong>ERROR</strong>: That status name is already registered. Please choose another one.', 'pps'));
            $arr_return['retval'] = $errors;
            return $arr_return;
        }

        if ($status_obj || $new) {
            if (empty($_REQUEST['status_label']) && !in_array($status, ['pending', 'future', 'draft']) && empty($status_obj->pp_custom)) {
                $errors = new \WP_Error();
                $errors->add('status_label', __('<strong>ERROR</strong>: Please enter a label for the status.', 'pps'));
                $arr_return['retval'] = $errors;
            } else {
                $custom_conditions = (array)get_option("presspermit_custom_conditions_post_status");

                if (isset($_REQUEST['status_label'])) {
                    $custom_conditions[$status]['label'] = sanitize_text_field($_REQUEST['status_label']);
                }

                $custom_conditions[$status]['save_as_label'] = (!empty($_REQUEST['status_save_as_label'])) ? sanitize_text_field($_REQUEST['status_save_as_label']) : '';
                $custom_conditions[$status]['publish_label'] = (!empty($_REQUEST['status_publish_label'])) ? sanitize_text_field($_REQUEST['status_publish_label']) : '';

                if ($new) {
                    $attrib_type = (isset($_REQUEST['attrib_type'])) ? sanitize_key($_REQUEST['attrib_type']) : '';
                    if ($attrib_type)
                        $custom_conditions[$status][$attrib_type] = true;
                } else {
                    $attrib_type = (!empty($status_obj->private)) ? 'private' : 'moderation';
                }

                if (!empty($status_obj->pp_custom)) {
                    $custom_conditions[$status]['publishpress'] = true;
                }

                $pp->updateOption("custom_conditions_post_status", $custom_conditions);

                $arr_return['redirect'] = str_replace('presspermit-status-new', "presspermit-statuses&attrib_type=$attrib_type", $_SERVER['REQUEST_URI']);
                $arr_return['redirect'] = str_replace('presspermit-status-edit', "presspermit-statuses&attrib_type=$attrib_type", $arr_return['redirect']);
            }

            // === store status post types ===
            if (!$status_post_types = $pp->getOption('status_post_types'))
                $status_post_types = [];

            if (!empty($_REQUEST['pp_status_all_types'])) {
                $status_post_types[$status] = [];

            } elseif (isset($_REQUEST['pp_status_post_types'])) {
                if (!isset($status_post_types[$status]))
                    $status_post_types[$status] = [];

                if ($add_types = array_intersect($_REQUEST['pp_status_post_types'], ['1', true, 1]))
                    $status_post_types[$status] = array_unique(array_merge($status_post_types[$status], array_map('sanitize_key', array_keys($add_types))));

                if ($remove_types = array_diff($_REQUEST['pp_status_post_types'], ['1', true, 1]))
                    $status_post_types[$status] = array_diff($status_post_types[$status], array_keys($remove_types));
            }

            $pp->updateOption('status_post_types', $status_post_types);

            // === store capability status ===
            if ('moderation' == $attrib_type) {

                if (isset($_REQUEST['status_capability_status'])) {
                    if (!$capability_status = $pp->getOption('status_capability_status'))
                        $capability_status = [];

                    if (empty($_REQUEST['status_capability_status'])) {
                        $pp->deleteOption("custom_{$status}_caps");
                    }

                    //} else {
                        if ($status == $_REQUEST['status_capability_status']) {
                            unset($capability_status[$status]);
                        } else {
                            $capability_status[$status] = sanitize_key($_REQUEST['status_capability_status']);
                        }

                        $pp->updateOption("status_capability_status", $capability_status);
                        $pp->updateOption("custom_{$status}_caps", true);
                    //}
                }

                // === store status order ===
                if (!$status_order = $pp->getOption('status_order'))
                    $status_order = [];

                if ($_REQUEST['status_order'] === '') {
                    unset($status_order[$status]);
                } else {
                    $status_order[$status] = (int)$_REQUEST['status_order'];
                }
                $pp->updateOption("status_order", $status_order);

                // === store status parent ===
                if (!$status_parent = $pp->getOption('status_parent'))
                    $status_parent = [];

                if (!empty($_REQUEST['status_parent']) || !empty($status_parent[$status])) {  // don't store value if no entry and not already stored
                    if (!empty($_REQUEST['status_parent'])) {
                        $status_parent[$status] = sanitize_key($_REQUEST['status_parent']);
                        $status_obj = get_post_status_object($status_parent[$status]);
                    } else {
                        unset($status_parent[$status]);
                    }

                    // don't allow status grandchildren
                    if (empty($_REQUEST['status_parent']) 
                    || ( !empty($status_obj) && empty($status_obj->status_parent) && ($status_parent[$status] != $status))
                    ) {
                        // If this status is being set to a parent but already has children, move its children also
                        if (!empty($status_parent[$status])) {
                            global $wp_post_statuses;
                            foreach ($wp_post_statuses as $_status => $_status_obj) {
                                if (!empty($_status_obj->status_parent) && ($_status_obj->status_parent == $status)) {
                                    $wp_post_statuses[$_status]->status_parent = $status_parent[$status];
                                }
                            }
                        }
                        
                        $pp->updateOption("status_parent", $status_parent);
                    }
                }
            }
        } else {
            $errors = new \WP_Error();
            $errors->add('condition_name', __('<strong>ERROR</strong>: The specified status does not exist.', 'pps'));
            $arr_return['retval'] = $errors;
        }

        return $arr_return;
    }
}
