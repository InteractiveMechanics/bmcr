<?php
namespace PublishPress\Permissions\Statuses\UI;

//use \PressShack\LibWP as PWP;

require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/StatusQuery.php');

class StatusListTable extends \WP_List_Table
{
    var $site_id;
    var $attribute;
    var $attrib_type;
    var $role_info;

    private static $instance = null;

    public static function instance($attrib_type) {
        if ( is_null(self::$instance) ) {
            self::$instance = new StatusListTable($attrib_type);
        }

        return self::$instance;
    }

    public function __construct($attrib_type)  // PHP 5.6.x and some PHP 7.x configurations prohibit restrictive subclass constructors
    {
        $screen = get_current_screen();

        // clear out empty entry from initial admin_header.php execution
        global $_wp_column_headers;
        if (isset($_wp_column_headers[$screen->id]))
            unset($_wp_column_headers[$screen->id]);

        add_filter("manage_{$screen->id}_columns", [$this, 'get_columns'], 0);

        parent::__construct([
            'singular' => 'status',
            'plural' => 'statuses'
        ]);

        $this->attribute = 'post_status';
        $this->attrib_type = $attrib_type;
    }

    function ajax_user_can()
    {
        return current_user_can('pp_define_post_status');
    }

    function prepare_items()
    {
        global $groupsearch;

        $args = [];

        // Query the user IDs for this page
        $pp_attrib_search = new StatusQuery($this->attribute, $this->attrib_type, $args);

        $this->items = $pp_attrib_search->get_results();

        $this->set_pagination_args([
            'total_items' => $pp_attrib_search->get_total(),
        ]);
    }

    function no_items()
    {
        _e('No matching statuses were found.', 'pps');
    }

    function get_views()
    {
        return [];
    }

    function get_bulk_actions()
    {
        return [];
    }

    function get_columns()
    {
        $c = [
            'status' => __('Status')
        ];

        if (defined('PRESSPERMIT_COLLAB_VERSION') && ('moderation' == $this->attrib_type))
            $c['order'] = __('Order', 'pps');

        $c = array_merge($c, [
            'cap_map' => __('Capability Mapping', 'pps'),
            'post_types' => __('Post Types', 'pps'),
            'enabled' => __('Capabilities'),
        ]);

        return $c;
    }

    function display_tablenav($which)
    {
    }

    function get_sortable_columns()
    {
        $c = [];

        return $c;
    }

    function display_rows()
    {
        $style = '';

        foreach ($this->items as $cond_object) {
            $style = (' class="alternate"' == $style) ? '' : ' class="alternate"';
            echo "\n\t", $this->single_row($cond_object, $style);
        }
    }

    /**
     * Generate HTML for a single row on the PP Role Groups admin panel.
     *
     * @param object $user_object
     * @param string $style Optional. Attributes added to the TR element.  Must be sanitized.
     * @param int $num_users Optional. User count to display for this group.
     * @return string
     */
    function single_row($cond_obj, $style = '')
    {
        static $base_url;
        static $disabled_conditions;

        $attrib = $this->attribute;
        $attrib_type = $this->attrib_type;

        if (!isset($base_url)) {
            $base_url = apply_filters('presspermit_conditions_base_url', 'admin.php');
            $disabled_conditions = presspermit()->getOption("disabled_{$attrib}_conditions");
        }

        $cond = $cond_obj->name;

        // Set up the hover actions for this user
        $actions = [];
        $checkbox = '';

        static $can_manage_cond;
        if (!isset($can_manage_cond))
            $can_manage_cond = current_user_can('pp_define_post_status');

        if ($is_publishpress = !empty($cond_obj->pp_custom)) {
            unset($disabled_conditions[$cond]);
        }

        // Check if the group for this row is editable
        if ($can_manage_cond && !in_array($cond, ['private', 'future']) && empty($disabled_conditions[$cond])) {
            $edit_link = $base_url . "?page=presspermit-status-edit&amp;action=edit&amp;status={$cond}";
            $label = (!empty($cond_obj->status_parent)) ? "&mdash; {$cond_obj->label}" : $cond_obj->label;
            $edit = "<strong><a href=\"$edit_link\">$label</a></strong><br />";
            $actions['edit'] = '<a href="' . $edit_link . '">' . PWP::__wp('Edit') . '</a>';
        } else {
            $edit = '<strong>' . $cond_obj->label . '</strong>';
        }

        if (in_array($cond, ['pending', 'future']) || (!empty($cond_obj->moderation) && $is_publishpress)) {
            if (!PPS::postStatusHasCustomCaps($cond))
                $actions['enable'] = "<a class='submitdelete' href='" . wp_nonce_url($base_url . "?page=presspermit-statuses&amp;pp_action=enable&amp;attrib_type=$attrib_type&amp;status=$cond", 'bulk-conditions') . "'>" . __('Enable Custom Capabilities', 'pps') . "</a>";
            else
                $actions['disable'] = "<a class='submitdelete' href='" . wp_nonce_url($base_url . "?page=presspermit-statuses&amp;pp_action=disable&amp;attrib_type=$attrib_type&amp;status=$cond", 'bulk-conditions') . "'>" . __('Disable Custom Capabilities', 'pps') . "</a>";
        } elseif ($cond && empty($cond_obj->builtin)) {
            if (!empty($disabled_conditions[$cond]))
                $actions['enable'] = "<a class='submitdelete' href='" . wp_nonce_url($base_url . "?page=presspermit-statuses&amp;pp_action=enable&amp;attrib_type=$attrib_type&amp;status=$cond", 'bulk-conditions') . "'>" . __('Enable', 'pps') . "</a>";
            else
                $actions['disable'] = "<a class='submitdelete' href='" . wp_nonce_url($base_url . "?page=presspermit-statuses&amp;pp_action=disable&amp;attrib_type=$attrib_type&amp;status=$cond", 'bulk-conditions') . "'>" . __('Disable', 'pps') . "</a>";
        } else
            $actions[''] = '&nbsp;';  // temp workaround to prevent shrunken row

        if (empty($cond_obj->_builtin) && !$is_publishpress && !in_array($cond, ['draft', 'pending', 'future'])) { // || ( ( 'moderation' == $attrib_type ) && ! in_array( $cond, [ 'draft', 'pending', 'pitch' ] ) && get_term_by( 'slug', $cond, 'post_status' ) ) ) {  // post_status taxonomy: PublishPress integration
            $actions['delete'] = "<a class='submitdelete' href='" . wp_nonce_url($base_url . "?page=presspermit-statuses&amp;pp_action=delete&amp;attrib_type=$attrib_type&amp;status=$cond", 'bulk-conditions') . "'>" . __('Delete') . "</a>";
        }

        $actions = apply_filters('presspermit_condition_row_actions', $actions, $attrib, $cond_obj);
        $edit .= $this->row_actions($actions);

        // Set up the checkbox ( because the group or group members are editable, otherwise it's empty )
        if ($actions)
            $checkbox = "<input type='checkbox' name='pp_conditions[]' id='pp_condition_{$cond}' value='{$cond}' />";
        else
            $checkbox = '';

        $r = "<tr $style>";

        list($columns, $hidden) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {
            $class = "class=\"$column_name column-$column_name\"";

            $style = '';
            if (in_array($column_name, $hidden, true))
                $style = ' style="display:none;"';

            $attributes = "$class$style";

            switch ($column_name) {
                case 'cb':
                    $r .= "<th scope='row' class='check-column'>$checkbox</th>";
                    break;
                case 'status':
                    $r .= "<td $attributes>$edit</td>";
                    break;
                case 'order':
                    $order = (!empty($cond_obj->order)) ? $cond_obj->order : '';

                    if (!empty($cond_obj->status_parent)) {
                        $status_parent_obj = get_post_status_object($cond_obj->status_parent);
                        $status_parent_label = (!empty($status_parent_obj) && !empty($status_parent_obj->label)) ? $status_parent_obj->label : $status_parent;
                    }

                    if ($order && !empty($cond_obj->status_parent)) {
                        $order = "&mdash; $order";
                        $title = 'title="' . esc_attr(sprintf(__('Normal order for workflow progression within the %s branch.', 'pps'), $status_parent_label)) . '"';
                    } else {
                        if (!$order) {
                            if (!empty($cond_obj->status_parent)) {
                                $title = 'title="' . esc_attr(sprintf(__('This status will be available within the %s branch, but not offered as a default next step.', 'pps'), $status_parent_label)) . '"';
                            } else {
                                $title = 'title="' . esc_attr(__('This status will be available in the main workflow, but not offered as a default next step.', 'pps')) . '"';
                            }
                        } else {
                            $title = 'title="' . esc_attr(__('Normal order for workflow progression.', 'pps')) . '"';
                        }
                    }

                    $r .= "<td $attributes $title>$order</td>";
                    break;
                case 'post_types':
                    if (!empty($cond_obj->post_type)) {
                        $arr_captions = [];
                        foreach ($cond_obj->post_type as $_post_type) {
                            if ($type_obj = get_post_type_object($_post_type)) {
                                $arr_captions [] = $type_obj->labels->singular_name;
                            }
                        }

                        $types_caption = implode(', ', array_slice($arr_captions, 0, 7));

                        if (count($arr_captions) > 7)
                            $types_caption = sprintf(__('%s, more...', 'pps'), $types_caption);
                    } else
                        $types_caption = __('All');

                    $r .= "<td $attributes>$types_caption</td>";
                    break;
                case 'cap_map':
                    $maps = [];
                    if (!empty($cond_obj->metacap_map)) {
                        foreach ($cond_obj->metacap_map as $orig => $map)
                            $maps [] = $orig . ' > ' . $map;
                    }
                    if (!empty($cond_obj->cap_map)) {
                        foreach ($cond_obj->cap_map as $orig => $map)
                            $maps [] = $orig . ' > ' . $map;
                    }
                    $r .= "<td $attributes><ul><li>" . implode('</li><li>', $maps) . "</li></ul></td>";
                    break;
                case 'enabled':
                    if (!empty($disabled_conditions[$cond])) {
                        $caption = __('Disabled', 'pps');
                
                    } elseif (in_array($cond, ['pending', 'future']) || ! empty($cond_obj->moderation) || $is_publishpress) {
                        if (!PPS::postStatusHasCustomCaps($cond)) {
                            $caption = __('(Standard)', 'pps');
                        } else {
                            if (!empty($cond_obj->capability_status) && ($cond_obj->capability_status != $cond)) {
                                if ($cap_status_obj = get_post_status_object($cond_obj->capability_status)) {
                                    $caption = sprintf(__('(same as %s)', 'pps'), $cap_status_obj->label);
                                } else {
                                    $caption = __('Custom', 'pps');
                                }
                            } else {
                                $caption = __('Custom', 'pps');
                            }
                        }
                    } else {
                        $caption = __('Enabled', 'pps');
                    }

                    $r .= "<td $attributes>$caption</td>";
                    break;
                default:
                    $r .= "<td $attributes>";
                    $r .= apply_filters('presspermit_manage_conditions_custom_column', '', $column_name, $attrib, $cond);
                    $r .= "</td>";
            }
        }
        $r .= '</tr>';

        return $r;
    }
}
