<?php
namespace PublishPress\Permissions\Compat\BuddyPress\PermissionGroups;

//use \PublishPress\Permissions\API as API;

require_once(PRESSPERMIT_CLASSPATH . '/UI/GroupsListTableBase.php');

//require_once(PRESSPERMIT_CLASSPATH . '/UI/GroupsQuery.php');
require_once(PRESSPERMIT_COMPAT_CLASSPATH . '/BuddyPress/PermissionGroups/GroupsQuery.php');

class GroupsListTable extends \PublishPress\Permissions\UI\GroupsListTableBase
{

    var $site_id;
    var $listed_ids;

    function __construct()
    {
        global $_wp_column_headers;

        $screen = get_current_screen();

        // clear out empty entry from initial admin_header.php execution
        if (isset($_wp_column_headers[$screen->id]))
            unset($_wp_column_headers[$screen->id]);

        parent::__construct([
            'singular' => 'bp_group',
            'plural' => 'bp_groups'
        ]);
    }

    function ajax_user_can()
    {
        return current_user_can('manage_pp_groups');
    }

    function prepare_items()
    {
        global $groupsearch;

        $groupsearch = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';

        $groups_per_page = $this->get_items_per_page('groups_per_page');

        $paged = $this->get_pagenum();

        $args = [
            'number' => $groups_per_page,
            'offset' => ($paged - 1) * $groups_per_page,
            'search' => $groupsearch,
        ];

        $args['search'] = '*' . $args['search'] . '*';

        if (isset($_REQUEST['orderby']))
            $args['orderby'] = $_REQUEST['orderby'];

        if (isset($_REQUEST['order']))
            $args['order'] = $_REQUEST['order'];

        // Query the user IDs for this page
        $group_search = new GroupsQuery($args);

        $this->items = $group_search->get_results();

        foreach ($this->items as $group) {
            $this->listed_ids[] = $group->id;
        }

        if (isset($this->listed_ids)) {
            $this->role_info = \PublishPress\Permissions\API::countRoles('bp_group', ['query_agent_ids' => $this->listed_ids]);
            $this->exception_info = \PublishPress\Permissions\API::countExceptions('bp_group', ['query_agent_ids' => $this->listed_ids]);
        }

        $this->set_pagination_args([
            'total_items' => $group_search->get_total(),
            'per_page' => $groups_per_page,
        ]);
    }

    function no_items()
    {
        _e('No matching groups were found.', 'presspermit');
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
            'cb' => '',
            'ID' => __('ID'),
            'name' => __('Name'),
            'num_users' => _x('Users', 'count', 'presspermit'),
            'site_roles' => _x('Sitewide Roles', 'count', 'presspermit'),
            'item_roles' => _x('Content Roles', 'count', 'presspermit'),
            'description' => __('Description', 'presspermit'),
        ];

        return $c;
    }

    function get_sortable_columns()
    {
        return [];
    }

    function display_rows()
    {
        $style = '';

        bp_has_groups();

        foreach ($this->items as $group_object) {
            $style = (' class="alternate"' == $style) ? '' : ' class="alternate"';
            echo "\n\t", $this->single_row($group_object, $style);
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
    function single_row($group_object, $style = '')
    {
        global $groups_template;
        $groups_template->group = $group_object;

        static $base_url;
        static $members_cap;
        static $is_administrator;

        $pp = presspermit();
        $pp_groups = $pp->groups();

        $group_id = $group_object->id;

        if (!isset($base_url)) {
            $base_url = apply_filters('presspermit_groups_base_url', 'admin.php'); // TODO: filter based on menu usage

            $is_administrator = presspermit()->isUserAdministrator();

            if (!$is_administrator)
                $members_cap = apply_filters('presspermit_edit_groups_reqd_caps', ['manage_pp_groups'], 'edit-members');
        }

        // Set up the hover actions for this user
        $actions = [];

        $can_manage_group = $is_administrator || $pp_groups->userCan('pp_edit_groups', $group_id, 'bp_group');

        // Check if the group for this row is editable
        if ($can_manage_group) {
            $edit_link = $base_url . "?page=presspermit-edit-permissions&amp;action=edit&amp;agent_type=bp_group&amp;agent_id={$group_id}";
            $edit = "<strong><a href=\"$edit_link\">$group_object->name</a></strong><br />";
            $actions['edit'] = '<a href="' . $edit_link . '">' . __('Permissions') . '</a>';
        } else {
            $edit = '<strong>' . $group_object->name . '</strong>';
        }

        $actions = apply_filters('ppbg_group_row_actions', $actions, $group_object);
        $edit .= $this->row_actions($actions);

        // Set up the checkbox ( because the group or group members are editable, otherwise it's empty )
        if ($actions)
            $checkbox = "<input type='checkbox' name='groups[]' id='group_{$group_id}' value='{$group_id}' />";
        else
            $checkbox = '';

        $r = "<tr id='group-$group_id'$style>";

        list($columns, $hidden) = $this->get_column_info();

        foreach ($columns as $column_name => $column_display_name) {
            $class = "class=\"$column_name column-$column_name\"";

            $style = '';
            if (in_array($column_name, $hidden, true))
                $style = ' style="display:none;"';

            $attributes = "$class $style";

            switch ($column_name) {
                case 'id':
                case 'ID':
                    $r .= "<td $attributes>$group_object->id</td>";
                    break;
                case 'name':
                case 'group_name': // todo: clean this up
                    $r .= "<td $attributes>" . bp_get_group_avatar("id={$group_id}&type=thumb&width=50&height=50&alt=") . ' ' . $edit . "</td>";
                    break;
                case 'num_users':
                    $num_users = count(\BP_Groups_Member::get_group_member_ids($group_id));

                    $attributes = 'class="posts column-num_users num"' . $style;

                    // Check if the group for this row is editable
                    if ($can_manage_group) {
                        $edit_url = bp_get_group_admin_permalink($group_object);
                        $title = __('BuddyPress Group Admin', 'presspermit');
                        $members_link = "<strong><a href=\"$edit_url\" title=\"$title\">$num_users</a></strong><br />";
                    } else {
                        $members_link = $num_users;
                    }

                    $r .= "<td $attributes>$members_link</td>";
                    break;

                case 'circle_type':
                    static $group_circles;
                    static $circle_captions;
                    if (!isset($group_circles)) {
                        $group_circles = [];
                        global $wpdb;
                        $results = $wpdb->get_results("SELECT DISTINCT group_id, circle_type FROM $wpdb->pp_circles WHERE group_type = 'bp_group'");
                        foreach ($results as $row) {
                            $group_circles[$row->group_id][$row->circle_type] = true;
                        }

                        $circle_captions = [];
                        $circle_captions['read'] = __('Visibility', 'presspermit');
                        $circle_captions['edit'] = __('Editorial', 'presspermit');
                    }

                    if (isset($group_circles[$group_id])) {
                        $capt_arr = array_intersect_key($circle_captions, $group_circles[$group_id]);
                        $_caption = implode(", ", $capt_arr);
                    } else {
                        $_caption = '';
                    }
                    $r .= "<td $attributes>$_caption</td>";
                    break;

                case 'roles':
                case 'exceptions':
                    $r .= $this->single_row_role_column($column_name, $group_id, $can_manage_group, $edit_link, $attributes);
                    break;
                case 'description':
                    $r .= "<td $attributes>$group_object->description</td>";
                    break;
                default:
                    $r .= "<td $attributes>";
                    $r .= apply_filters('presspermit_buddypress_manage_groups_custom_column', '', $column_name, $group_id);
                    $r .= "</td>";
            }
        }
        $r .= '</tr>';

        return $r;
    }
}
