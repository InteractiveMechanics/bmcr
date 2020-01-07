<?php
namespace PublishPress\Permissions\Circles\UI;

class GroupEdit
{
    public static function circlesUI($group_type, $group_id)
    {
        if (!in_array($group_type, presspermit()->groups()->getGroupTypes(), true) 
        || !$circle_types = Circles::getCircleTypes()
        ) {
            return;
        }

        $pp = presspermit();

        echo '<div class="pp-group-box pp-group_post-types" style="float:left;width:500px;">'
            . '<h3 style="margin-bottom:0">';

        _e('Circle Activation', 'ppcc');

        echo '</h3>';

        $all_types = $pp->getEnabledPostTypes([], 'object');
        $circles = Circles::getGroupCircles($group_type, $group_id);

        $types_csv = implode(",", array_keys($all_types));
        echo "<input type='hidden' name='reviewed_circle_types' value=" . $types_csv . " />";

        echo '<table><tr>';

        $circle_captions = ['read' => __('Visibility Circle', 'ppcc'), 'edit' => __('Editorial Circle', 'ppcc')];

        $disabled_circle_types = [];

        // If a WP role has exemption capability assigned, don't offer to make it a circle
        if ('pp_group' == $group_type) {
            if ($metagroup = presspermit()->groups()->isMetagroup('wp_role', $group_id)) {
                if ($role = get_role($metagroup->metagroup_id) ) {
                    foreach ($circle_types as $_circle_type) {
                        if (!empty($role->capabilities["pp_exempt_{$_circle_type}_circle"])) {
                            $disabled_circle_types[$_circle_type] = "pp_exempt_{$_circle_type}_circle";
                        }
                    }
                }
            }
        }

        foreach ($circle_types as $_circle_type) {
            echo '<td style="vertical-align:top;padding-right:40px">';

            $is_circle = isset($circles[$group_id][$_circle_type]);

            $caption = sprintf(__('This group is a %s', 'ppcc'), $circle_captions[$_circle_type]);
            
            if (!empty($disabled_circle_types[$_circle_type])) {
                $checked = 'disabled=disabled';
                $caption = "<span style='text-decoration:line-through'>$caption</span>";
            } else {
                $checked = ($is_circle) ? 'checked="checked"' : '';
            }

            echo "<div class='agp-vspaced_input' style='margin-right:50px'>"
            . "<span style='white-space:nowrap'><input type='checkbox' name='is_{$_circle_type}_circle' value='1' id='is_{$_circle_type}_circle' class='is_circle' $checked> "
            . "<label for='is_{$_circle_type}_circle'>" . $caption . '</label></span></div>';

            if (!empty($disabled_circle_types[$_circle_type])) {
                echo '<br />';

                printf(
                    __('Members of this role are exempted from all %s restrictions, due to the %s capability.', 'ppcc'),
                    $circle_captions[$_circle_type],
                    '<strong>' . $disabled_circle_types[$_circle_type] . '</strong>'
                );

                echo '</td>';

                continue;
            }

            $hide = ($is_circle) ? '' : 'style="display:none"';
            ?>
            <table class="form-table" <?php echo $hide; ?>>
                <tbody>
                <tr>
                    <td style="max-width:215px">
                        <?php
                        echo '<h4 style="margin:0.1em">' . __('apply limitation for post types:', 'ppcc') . '</h4>';

                        $hide_types = count($all_types) > 7;

                        $ordered_types = [];
                        foreach (array_keys($all_types) as $post_type) {
                            $ordered_types[$post_type] = $all_types[$post_type]->labels->singular_name;
                        }
                        asort($ordered_types);

                        $enabled_list = [];
                        if ($is_circle) {
                            $i = 0;
                            foreach ($ordered_types as $post_type => $caption) {
                                if (isset($circles[$group_id][$_circle_type][$post_type])) {
                                    $i++;
                                    if ($i == 6) {
                                        $enabled_list[] = __('more...', 'ppcc');
                                        break;
                                    } else
                                        $enabled_list[] = $caption;
                                }
                            }

                            if ($enabled_list) : ?>
                                <div>
                                    <?php echo implode(', ', $enabled_list); ?>
                                </div>
                            <?php
                            endif;
                        }
                        ?>

                        <div<?php if (!$hide_types) echo " style='display:none'"; ?>>
                            <a href='#show-circle-types'><?php _e('edit', 'ppcc'); ?></a>
                        </div>

                        <div class="circle-post-types"<?php if ($hide_types) echo " style='display:none'"; ?>>

                            <p><a href='#hide-circle-types'><?php _e('hide', 'ppcc'); ?></a></p>

                            <?php
                            foreach (array_keys($ordered_types) as $post_type) {
                                // default new activations to all post types
                                $checked = (isset($circles[$group_id][$_circle_type][$post_type]) || !$is_circle) 
                                ? 'checked="checked"' 
                                : '';
                                
                                echo "<input type='checkbox' name='{$_circle_type}_circle_post_types[]' value='$post_type' id='{$_circle_type}_circle_post_type_{$post_type}' $checked> "
                                . "<label for='{$_circle_type}_circle_post_type_{$post_type}'>" 
                                . $all_types[$post_type]->labels->singular_name 
                                . '</label><br />';
                            }
                            ?>
                            <div style="margin-top:5px;margin-left:10px"><input type="checkbox" class="ppc-check-all"/><?php _e('(toggle all)', 'presspermit');?></div>
                        </div>

                    </td>
                </tr>
                </tbody>
            </table>
            <?php

            echo '</td>';
        } // end foreach circle_type

        echo '</tr></table>';

        if ($pp->getOption('display_hints')) {
            echo '<div class="pp-current-roles-note" style="margin-top:5px;margin-left:0">';
            _e('<strong>Block</strong> viewing or editing of posts not authored by a fellow Circle Group member. Users may be a member of multiple circles. Note that an appropriate role assignment (either WP role or supplemental) is still required.', 'ppcc');
            
            if (empty($metagroup)) {
                echo '<div style="margin-top:10px">';
                _e('Users may be exempted by adding the pp_exempt_read_circle or pp_exempt_edit_circle capability to their primary WP role.', 'ppcc');
                echo '</div>';
            }
            
            echo '</div>';
        }

        // _e( '<strong>Limit</strong> group members to viewing/editing only posts which were authored by a fellow member. Note that an appropriate role assignment (either WP role or supplemental) is also required.', 'ppcc' );

        echo '</div>';
    }
}
