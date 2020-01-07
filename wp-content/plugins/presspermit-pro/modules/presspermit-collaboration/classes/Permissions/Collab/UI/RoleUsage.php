<?php
namespace PublishPress\Permissions\Collab\UI;

/**
 * Users administration panel.
 *
 * @package WordPress
 * @subpackage Administration
 */

class RoleUsage 
{
    function __construct() 
    {
        require_once(PRESSPERMIT_COLLAB_CLASSPATH . '/UI/RoleUsageHelper.php');
        $this->display();
    }

    private function display() {
        if (!current_user_can('pp_manage_settings'))
            wp_die(__('You are not permitted to do that.', 'presspermit'));

        require_once(PRESSPERMIT_COLLAB_CLASSPATH . '/UI/RoleUsageListTable.php');
        $role_usage_table = RoleUsageListTable::instance();

        $url = $referer = $redirect = $update = '';
        RoleUsageHelper::getUrlProperties($url, $referer, $redirect);

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        if (!$action)
            $action = isset($_REQUEST['pp_action']) ? $_REQUEST['pp_action'] : '';

        $role_usage_table->prepare_items();
        $total_pages = $role_usage_table->get_pagination_arg('total_pages');

        $messages = [];
        if (isset($_GET['update'])) :
            switch ($_GET['update']) {
                case 'edit':
                    $messages[] = '<div id="message" class="updated"><p>' . __('Role Usage edited.', 'presspermit') . '</p></div>';
                    break;
            }
        endif;
        ?>

        <?php
        $admin = presspermit()->admin();

        if (isset($admin->errors) && is_wp_error($admin->errors)) :
            ?>
            <div class="error">
                <ul>
                    <?php
                    foreach ($admin->errors->get_error_messages() as $err)
                        echo "<li>$err</li>\n";
                    ?>
                </ul>
            </div>
        <?php
        endif;

        if (!empty($messages)) {
            foreach ($messages as $msg)
                echo $msg;
        }
        ?>

        <div class="wrap pressshack-admin-wrapper presspermit-role-usage">
            <header>
            <?php \PublishPress\Permissions\UI\PluginPage::icon(); ?>
            <h1>
                <?php

                $caption = __('Edit Role Usage', 'presspermit');

                echo esc_html($caption);
                ?>
            </h1>

            <?php
            if (presspermit()->getOption('display_hints')) {
                echo '<div class="pp-hint">';
                _e("These <strong>optional</strong> settings customize how PressPermit applies <strong>supplemental roles</strong>. Your existing WP Role Definitions can be applied in two different ways:", 'presspermit');
                
                echo '<ul style="list-style-type:disc;list-style-position:outside;margin:1em 0 0 2em"><li>' 
                . __("Pattern Roles convert 'post' capabilities to the corresponding type-specific capability.  In a normal WP installation, this is the easiest solution.", 'presspermit') 
                . '</li>';
                
                echo '<li>' 
                . __("With Direct Assignment, capabilities are applied without modification (leaving you responsible to add custom type caps to the WP Role Definitions).", 'presspermit') 
                . '</li></ul>';
                
                echo '</div>';
            }
            ?>
            </header>

            <?php
            $role_usage_table->views();
            $role_usage_table->display();
            ?>
            <form method="post" action="">
                <?php
                $msg = __("All Role Usage settings will be reset to DEFAULTS.  Are you sure?", 'presspermit');
                $js_call = "javascript:if (confirm('$msg')) {return true;} else {return false;}";
                ?>
                <p class="submit" style="border:none;float:left">
                    <input type="submit" name="pp_role_usage_defaults" value="<?php _e('Revert to Defaults', 'presspermit') ?>"
                        onclick="<?php echo $js_call; ?>"/>
                </p>
                <br style="clear:both"/>
            </form>
            <?php

            if (presspermit()->getOption('display_hints')) {
                RoleUsageHelper::other_notes();
            }

            presspermit()->admin()->publishpressFooter();
            ?>
        </div>
    <?php
    }
}
