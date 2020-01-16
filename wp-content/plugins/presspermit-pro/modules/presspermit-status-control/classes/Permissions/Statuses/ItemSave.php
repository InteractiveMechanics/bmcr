<?php
namespace PublishPress\Permissions\Statuses;

class ItemSave
{
    public static function get_parent_conditions($attribute, $scope, $source_name, $parent_id)
    {
        global $wpdb;

        // Since this is a new object, propagate item conditions from parent (if any are marked for propagation)
        $qry = $wpdb->prepare(
            "SELECT condition_name, attribute, assignment_id, scope, item_source, item_id, assign_for, mode, inherited_from"
            . " FROM $wpdb->pp_conditions WHERE attribute = %s AND scope = %s AND assign_for = 'children' AND item_source = %s"
            . " AND item_id = %d ORDER BY condition_name", 
            
            $attribute, 
            $scope, 
            $source_name, 
            $parent_id
        );
        
        $results = $wpdb->get_results($qry, OBJECT_K);
        return $results;
    }

    public static function inherit_parent_conditions(
        $attribute, $item_id, $scope, $source_name, $parent_id, $object_type = '', $default_value = false
    ) {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/DB/AttributesUpdate.php');

        if (!$parent_id && $default_value) {
            $parent_conditions = [(object)['attribute' => $attribute, 'condition_name' => $default_value, 'item_id' => 0]];
        } else {
            $parent_conditions = self::get_parent_conditions($attribute, $scope, $source_name, $parent_id);
        }

        if ($parent_conditions) {
            foreach ($parent_conditions as $row) {
                $inherited_from = ($row->item_id) ? $row->assignment_id : 0;

                $args = ['is_auto_insertion' => true, 'inherited_from' => $inherited_from];

                DB\AttributesUpdate::insert_item_condition(
                    $row->attribute, 
                    $scope, 
                    $source_name, 
                    $item_id, 
                    $row->condition_name, 
                    'item', 
                    $args
                );
                
                DB\AttributesUpdate::insert_item_condition(
                    $row->attribute, 
                    $scope, 
                    $source_name, 
                    $item_id, 
                    $row->condition_name, 
                    'children', 
                    $args
                );
            }
        }
    }

    public static function propagate_post_visibility($post_id, $visibility, $args = [])
    {
        if ($visibility) {
            PPS::setItemCondition(
                'force_visibility', 
                'object', 
                'post', 
                $post_id, 
                $visibility, 
                'children', 
                ['propagate' => true]
            );

            // if child visibility is set, apply it for all published subposts
            $post_status = get_post_stati(['public' => true, 'private' => true], 'names', 'or');

            if ($published_subposts = PWP::getDescendantIds(
                'post', 
                $post_id, 
                ['post_status' => $post_status, 'append_clause' => "AND post_password = ''"]
            )) {
                global $wpdb;
                $visibility = sanitize_key($visibility);
                $wpdb->query(
                    "UPDATE $wpdb->posts SET post_status = '$visibility'"
                    . " WHERE ID IN ('" . implode("','", $published_subposts) . "')"
                );
            }
        } else {
            PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, 'item');
            PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, 'item', ['propagate' => true, 'inherited_only' => true]);
            PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, 'children', ['propagate' => true]);
        }
    }

    public static function post_update_force_visibility($object)
    {
        $post_id = $object->ID;

        // setting for post being edited
        foreach (['item' => 'pp_force_visibility', 'children' => 'pp_ch_force_visibility'] as $assign_for => $var) {
            // make sure the UI for this condition was actually reviewed
            if (isset($_POST[$var])) {
                if ($_POST[$var])
                    PPS::setItemCondition('force_visibility', 'object', 'post', $post_id, $_POST[$var], $assign_for);
                else
                    PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, $assign_for);
            }
        } // end foreach (item/children)

        // parent setting affects auto-assignment of force_visibility
        $set_parent = $object->post_parent;
        $last_parent = ($post_id > 0) ? get_post_meta($post_id, '_pp_last_parent', true) : 0;

        if ($set_parent !== $last_parent) {
            update_post_meta($object->ID, '_pp_last_parent', (int)$set_parent);

            // Inherit parent condition, but only for new post or if parent has changed 
            // (force_visibility is always propagated and cannot be overrident by manual setting)
            PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, 'item', ['inherited_only' => true]);
            PPS::clearItemCondition('force_visibility', 'object', 'post', $post_id, 'children', ['inherited_only' => true]);

            // apply propagating conditions from specific parent
            self::inherit_parent_conditions('force_visibility', $post_id, 'object', 'post', $set_parent, $object->post_type);
        }
    }
}
