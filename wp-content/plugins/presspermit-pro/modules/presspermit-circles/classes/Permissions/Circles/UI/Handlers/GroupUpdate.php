<?php
namespace PublishPress\Permissions\Circles\UI\Handlers;

//use \PublishPress\Permissions\Circles as Circles;

class GroupUpdate
{
    public static function updateGroup($group_type, $group_id)
    {
        if (empty($_REQUEST['reviewed_circle_types']))
            return;

        $stored_circles = Circles::getGroupCircles($group_type, $group_id);
        
        $delete_circles = [];
        $add_circles = ['read' => [], 'edit' => []];
        foreach (['read', 'edit'] as $circle_type) {
            if (empty($_REQUEST["is_{$circle_type}_circle"])) {
                // circle deactivated
                if (!empty($stored_circles[$group_id][$circle_type])) {
                    $delete_circles = array_merge($delete_circles, $stored_circles[$group_id][$circle_type]);
                }
            } else {
                $posted_types = (isset($_REQUEST["{$circle_type}_circle_post_types"])) 
                ? $_REQUEST["{$circle_type}_circle_post_types"] 
                : [];

                // circle activated
                if (isset($stored_circles[$group_id][$circle_type])) {
                    // already stored (at least for some post types)
                    if ($old = array_diff_key(
                        $stored_circles[$group_id][$circle_type], 
                        array_flip($posted_types))
                    ) {    
                        $delete_circles = array_intersect_key(
                            array_merge($delete_circles, $old), 
                            array_flip(explode(",", $_REQUEST['reviewed_circle_types']))
                        );  // don't remove circle activation for types which are not currently registered
                    }

                    if (!empty($_REQUEST["{$circle_type}_circle_post_types"])) {
                        if ($new = array_diff(
                            $_REQUEST["{$circle_type}_circle_post_types"], 
                            array_flip($stored_circles[$group_id][$circle_type]))
                        ) {
                            $add_circles[$circle_type] = array_merge($add_circles[$circle_type], $new);
                        }
                    }
                } else {
                    // not stored at all yet
                    $add_circles[$circle_type] = array_merge($add_circles[$circle_type], $posted_types);
                }
            }
        }

        global $wpdb;
        if ($delete_circles) {
            $id_csv = implode("','", array_map('intval', $delete_circles));
            $wpdb->query("DELETE FROM $wpdb->pp_circles WHERE ID IN ('$id_csv')");
        }

        if ($add_circles) {
            foreach (array_keys($add_circles) as $circle_type) {
                foreach ($add_circles[$circle_type] as $post_type) {
                    $wpdb->query(
                        $wpdb->prepare(
                            "INSERT INTO $wpdb->pp_circles"
                            . " (group_type,group_id,circle_type,post_type) VALUES (%s,%d,%s,%s)", 
                            
                            $group_type, $group_id, $circle_type, $post_type
                        )
                    );
                }
            }
        }
    }
}
