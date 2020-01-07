<?php
namespace PublishPress\Permissions\Circles\UI\Dashboard;

use \PublishPress\Permissions\Circles as Circles;

class Profile
{
    public static function displayUserCirclesUI($user)
    {
        $circles = [];

        foreach (Circles::getCircleTypes() as $circle_type) {
            $circles[$circle_type] = Circles::getCircleMembers($circle_type, $user);
        }

        if (empty($circles['read']) && empty($circles['edit']))
            return;

        echo '<div class="pp-group-box pp-group_post-types"><h3>' . __('Circle Membership', 'ppcc') . '</h3>';

        $circle_labels = ['read' => __('Viewing', 'ppcc'), 'edit' => __('Editing', 'ppcc')];

        foreach (array_keys($circles) as $circle_type) {
            if (!empty($circles[$circle_type])) {
                echo '<div>' 
                . sprintf(
                    __('<strong>%s</strong> is limited to circle authors only, for these post types:', 'ppcc'), 
                    $circle_labels[$circle_type]
                ) 
                . '</div>';
                
                $labels = [];
                foreach (array_keys($circles[$circle_type]) as $post_type) {
                    if ( $type_obj = get_post_type_object($post_type) ) {
                        $labels[$post_type] = $type_obj->label;
                    }
                }
                echo '<div style="margin-left:10px;margin-bottom:10px">' . implode(", ", $labels) . '</div>';
            }
        }

        /*
        echo '<div class="pp-current-roles-note">' 
        . __('note: Circle membership is defined by one or more of this user&apos;s Permission Groups.', 'ppcc') 
        . '</div>';
        */
        
        echo '</div>';
    }
}
