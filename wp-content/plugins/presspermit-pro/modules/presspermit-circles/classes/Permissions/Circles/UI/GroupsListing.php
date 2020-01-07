<?php
namespace PublishPress\Permissions\Circles\UI;

class GroupsListing
{
    function __construct()
    {
        add_filter('presspermit_manage_groups_columns', [$this, 'fltManageGroupsColumns']);
        add_filter('presspermit_manage_groups_custom_column', [$this, 'fltManageGroupsCustomColumn'], 10, 4);
    }

    function fltManageGroupsColumns($cols)
    {
        $cols['circle_type'] = __('Circle Type', 'ppcc');
        return $cols;
    }

    function fltManageGroupsCustomColumn($val, $column_name, $group_id, $groups_list_table)
    {
        if ('circle_type' == $column_name) {
            static $group_circles;
            static $circle_captions;
            if (!isset($group_circles)) {
                $group_circles = [];

                $group_type = $groups_list_table->getAgentType();

                global $wpdb;
                $results = $wpdb->get_results(
                    "SELECT DISTINCT group_id, circle_type FROM $wpdb->pp_circles WHERE group_type = '$group_type'"
                );
                
                foreach ($results AS $row) {
                    $group_circles[$row->group_id][$row->circle_type] = true;
                }

                $circle_captions = [];
                $circle_captions['read'] = __('Visibility', 'ppcc');
                $circle_captions['edit'] = __('Editorial', 'ppcc');
            }

            if (isset($group_circles[$group_id])) {
                $capt_arr = array_intersect_key($circle_captions, $group_circles[$group_id]);
                $_caption = implode(", ", $capt_arr);
            } else {
                $_caption = '';
            }
            $val .= $_caption;
        }

        return $val;
    }
}
