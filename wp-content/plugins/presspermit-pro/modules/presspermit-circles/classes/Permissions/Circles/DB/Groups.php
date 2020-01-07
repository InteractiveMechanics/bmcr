<?php
namespace PublishPress\Permissions\Circles\DB;

/**
 * Groups class
 *
 * @package PressPermit
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2013, Agapetry Creations LLC
 *
 */

//use \PressShack\LibArray as Arr;

//use \PublishPress\Permissions\Circles as Circles;

class Groups
{
    public static function getGroupCircles($group_type, $group_id, $circle_type = false)
    {
        global $wpdb;

        if (!$circle_type)
            $circle_type = ['read', 'edit'];
        else
            $circle_type = (array)$circle_type;

        $group_id = (array)$group_id;

        $id_csv = implode("','", $group_id);
        $ctype_csv = implode("','", $circle_type);
        $circles = $wpdb->get_results(
            "SELECT group_id, circle_type, post_type, ID FROM $wpdb->pp_circles"
            . " WHERE group_type = '$group_type' AND circle_type IN ('$ctype_csv') AND group_id IN ('$id_csv')"
            . " ORDER BY group_id"
        );

        $circle_groups = [];
        foreach ($circles as $circle) {
            $circle_groups[$circle->group_id][$circle->circle_type][$circle->post_type] = $circle->ID;
        }
        return $circle_groups;
    }

    // $circle_members[circle_type][post_type] = array of user_ids 
    // (note: this limits the user's scope of assigned editing role(s), does not bestow editing access)
    public static function getCircleMembers($circle_type, $user = false, $force_refresh = false)
    {
        if (!in_array($circle_type, \PublishPress\Permissions\Circles::getCircleTypes(), true))
            return [];

        if (!$user) {
            $user = presspermit()->getUser();
        }

        static $cfg;

        $pp = presspermit();
        $pp_groups = $pp->groups();

        if (!isset($cfg))
            $cfg = [];

        if (!isset($cfg[$user->ID]) || $force_refresh)
            $cfg[$user->ID] = [];

        if (!isset($cfg[$user->ID][$circle_type]))
            $cfg[$user->ID][$circle_type] = [];
        else {
            return $cfg[$user->ID][$circle_type];
        }

        $all_post_types = $pp->getEnabledPostTypes();
        $cfg[$user->ID][$circle_type] = array_fill_keys($all_post_types, []);

        $user_groups = (isset($user->groups)) ? $user->groups : [];

        if (!$user_groups) {
            $user_groups = [];

            foreach (array_keys($pp->groups()->getGroupTypes()) as $agent_type) {
                $user_groups[$agent_type] = $pp_groups->getGroupsForUser(
                    $user->ID, 
                    $agent_type, 
                    ['cols' => 'id', 'force_refresh' => true]
                );
            }
        }

        global $wpdb;
        if ($user->ID != presspermit()->getUser()->ID) {
            static $circles;
            $circle_where = '';
        } else {
            $circle_where = [];
            foreach (array_keys($user_groups) as $group_type) {
                $circle_where [] = 
                    "group_type = '$group_type' AND group_id IN ( '" 
                    . implode("','", array_keys($user_groups[$group_type])) 
                    . "')";
            }

            if ($circle_where)
                $circle_where = ' AND ( ' . Arr::implode(' OR ', $circle_where) . ' ) ';
            else
                $circle_where = '';
        }

        if (!isset($circles))
            $circles = [];

        if (!isset($circles[$circle_type])) {
            $circles[$circle_type] = [];

            $results = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT group_type, group_id, post_type FROM $wpdb->pp_circles"
                    . " WHERE circle_type = %s{$circle_where}", 
                    
                    $circle_type
                )
            );
            
            foreach ($results as $row) {
                $circles[$circle_type][$row->group_type][$row->group_id][$row->post_type] = true;
            }
        }

        foreach (array_keys($user_groups) as $group_type) {
            if (!empty($circles[$circle_type][$group_type])) {
                foreach (array_intersect_key($circles[$circle_type][$group_type], $user_groups[$group_type]) 
                    as $group_id => $circle_post_types
                ) {
                    $get_arg = ('bp_group' == $group_type) ? 'ids' : 'id'; // todo: clean this up in Compat module
                    $members = $pp_groups->getGroupMembers($group_id, $group_type, $get_arg);

                    foreach ($all_post_types as $post_type) {
                        if (!isset($cfg[$user->ID][$circle_type][$post_type]))
                            $cfg[$user->ID][$circle_type][$post_type] = [];

                        if ($members && !empty($circles[$circle_type][$group_type][$group_id][$post_type])) {
                            $cfg[$user->ID][$circle_type][$post_type] = array_merge(
                                $cfg[$user->ID][$circle_type][$post_type], 
                                $members
                            );
                        }
                    }
                }
            }
        }

        $cfg[$user->ID][$circle_type] = apply_filters(
            'presspermit_circle_members', 
            $cfg[$user->ID][$circle_type], 
            $circle_type, 
            $user->ID
        );

        foreach (array_keys($cfg[$user->ID][$circle_type]) as $post_type) {
            if (empty($cfg[$user->ID][$circle_type][$post_type])) {
                unset($cfg[$user->ID][$circle_type][$post_type]);
            } else {
                $cfg[$user->ID][$circle_type][$post_type] = array_unique($cfg[$user->ID][$circle_type][$post_type]);
            }
        }

        return $cfg[$user->ID][$circle_type];
    }
}
