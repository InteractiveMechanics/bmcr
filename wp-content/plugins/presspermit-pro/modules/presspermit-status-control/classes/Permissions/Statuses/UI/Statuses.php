<?php
namespace PublishPress\Permissions\Statuses\UI;

//use \PressShack\LibWP as PWP;

/**
 * PressPermit Custom Post Statuses administration panel.
 *
 */

class Statuses
{
    function __construct() {
        // This script executes on admin.php plugin page load (called by Dashboard\DashboardFilters::actMenuHandler)
        //
        $this->display();
    }

    private function display() {
        $pp = presspermit();
        
        $attribute = 'post_status';

        if (isset($_REQUEST['attrib_type'])) {
            $attrib_type = sanitize_key($_REQUEST['attrib_type']);

            if (defined('PPS_NATIVE_CUSTOM_STATI_DISABLED') && ('private' == $attrib_type)) {
                $attrib_type = 'moderation';
            }
        } else {
            if ($links = apply_filters('presspermit_post_status_types', [])) {
                $link = reset($links);
                $attrib_type = $link->attrib_type;
            }
        }

        if (!current_user_can('pp_administer_content') && (!$attrib_type || !current_user_can("pp_define_{$attrib_type}")))
            wp_die(__('You are not permitted to do that.', 'pps'));

        $attributes = PPS::attributes();

        //$list_table = apply_filters( 'presspermit_conditions_list_table', false, $attribute ); 

        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/StatusListTable.php');
        $presspermit_statuses_table = StatusListTable::instance($attrib_type);

        $pagenum = $presspermit_statuses_table->get_pagenum();

        // contextual help - choose Help on the top right of admin panel to preview this.
        /*
        add_contextual_help($current_screen,
            '<p>' . __('This screen lists all the existing users for your site. Each user has one of five defined roles as set by the site admin: Site Administrator, Editor, Author, Contributor, or Subscriber. Users with roles other than Administrator will see fewer options in the dashboard navigation when they are logged in, based on their role.') . '</p>' .
            '<p>' . __('You can customize the display of information on this screen as you can on other screens, by using the Screen Options tab and the on-screen filters.') . '</p>' .
            '<p>' . __('To add a new user for your site, click the Add New button at the top of the screen or Add New in the Users menu section.') . '</p>' .
            '<p><strong>' . __('For more information:') . '</strong></p>' .
            '<p>' . __('<a href="https://codex.wordpress.org/Users_Screen" target="_blank">Documentation on Managing Users</a>') . '</p>' .
            '<p>' . __('<a href="https://codex.wordpress.org/Roles_and_Capabilities" target="_blank">Descriptions of Roles and Capabilities</a>') . '</p>' .
            '<p>' . __('<a href="https://wordpress.org/support/" target="_blank">Support Forums</a>') . '</p>'
        );
        */

        $url = $referer = $redirect = $update = '';

        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/StatusHelper.php');
        StatusHelper::getUrlProperties($url, $referer, $redirect);

        $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
        if (!$action)
            $action = isset($_REQUEST['pp_action']) ? $_REQUEST['pp_action'] : '';

        switch ($action) {
        //switch ( $presspermit_statuses_table->current_action() ) {

            case 'delete':
            case 'bulkdelete':
                if (empty($_REQUEST['statuses']))
                    $conds = [$_REQUEST['status']];
                else
                    $conds = (array)$_REQUEST['statuses'];
                ?>
                <form action="" method="post" name="updateconditions" id="updateconditions">
                    <?php wp_nonce_field('delete-conditions') ?>
                    <?php echo $referer; ?>

                    <div class="wrap">
                        <?php \PublishPress\Permissions\UI\PluginPage::icon(); ?>
                        <h1><?php _e('Delete Statuses'); ?></h1>
                        <p><?php echo _n('You have specified this status for deletion:', 'You have specified these statuses for deletion:', count($conds), 'pps'); ?></p>
                        <ul>
                            <?php
                            $go_delete = 0;
                            foreach ($conds as $cond) {
                                if ($cond_obj = $this->get_condition($attribute, $cond)) {
                                    echo "<li><input type=\"hidden\" name=\"users[]\" value=\"" . esc_attr($cond) . "\" />" . $cond_obj->label . "</li>\n";
                                    $go_delete++;
                                }
                            }

                            ?>
                        </ul>
                        <?php if ($go_delete) : ?>
                            <input type="hidden" name="action" value="dodelete"/>
                            <input type="hidden" name="pp_attribute" value="<?php echo $attribute; ?>"/>
                            <input type="hidden" name="attrib_type" value="<?php echo $attrib_type; ?>"/>
                            <?php submit_button(__('Confirm Deletion'), 'secondary'); ?>
                        <?php else : ?>
                            <p><?php _e('There are no valid statuses selected for deletion.', 'pps'); ?></p>
                        <?php endif; ?>
                    </div>
                </form>
                <?php

                break;

            default:

                $admin = presspermit()->admin();

                if (!empty($_REQUEST['update']) && empty($admin->errors) && !empty($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'presspermit-status-new')) :
                    ?>
                    <div id="message" class="updated">
                        <p><strong><?php _e('Post Status Created.', 'pps') ?>&nbsp;</strong>
                        </p></div>
                <?php
                endif;

                $presspermit_statuses_table->prepare_items();
                $total_pages = $presspermit_statuses_table->get_pagination_arg('total_pages');

                $messages = [];
                if (isset($_GET['update'])) {
                    switch ($_GET['update']) {
                        case 'del':
                        case 'del_many':
                            $delete_count = isset($_GET['delete_count']) ? (int)$_GET['delete_count'] : 0;
                            $messages[] = '<div id="message" class="updated"><p>' . sprintf(_n('%s status deleted', '%s status deleted', $delete_count, 'pps'), $delete_count) . '</p></div>';
                            break;
                        case 'edit':
                            $messages[] = '<div id="message" class="updated"><p>' . __('Status edited.', 'pps') . '</p></div>';
                            break;
                        case 'add':
                            if (!defined('PPS_NATIVE_CUSTOM_STATI_DISABLED')) {
                                $messages[] = '<div id="message" class="updated"><p>' . __('New status created.', 'pps') . '</p></div>';
                            }
                            break;
                    }
                }
                ?>

                <?php if (isset($admin->errors) && is_wp_error($admin->errors)) : ?>
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
                } ?>

                <div class="wrap pressshack-admin-wrapper pp-conditions">
                    <header>
                    <?php \PublishPress\Permissions\UI\PluginPage::icon(); ?>
                    <h1>
                        <?php
                        $attrib_obj = $attributes->attributes[$attribute];

                        if ('post_status' == $attribute) {
                            if ('private' == $attrib_type) {
                                $attrib_caption = __('Define Post Privacy Statuses', 'pps');
                                $hint = __("Statuses enabled here are available as Visibility options for post publishing. Affected posts become inaccessable without a corresponding status-specific role assignment.", 'pps');
                            } elseif ('moderation' == $attrib_type) {
                                $attrib_caption = (PPS::publishpressStatusesActive()) ? __('Configure PublishPress Workflow Statuses', 'pps') : __('Define Workflow Statuses', 'pps');

                                $hint = __("Statuses enabled here are available in the editor as additional steps between Draft and Published.", 'pps');
                            } else
                                $attrib_caption = __('Define Post Statuses', 'pps');
                        } else {
                            $attrib_caption = sprintf(__('Define Statuses: %s', 'pps'), $attrib_obj->label);
                            $hint = __("Statuses alter your content's accessibility by imposing additional capability requirements.", 'pps');
                        }

                        echo esc_html($attrib_caption);

                        /*
                        if ( current_user_can( 'pp_edit_groups' ) ) {
                            if ( MULTISITE && $pp->getOption('ms_netwide_groups') )
                                $url = 'users.php';
                            else
                                $url = 'admin.php';
                        }
                        */
                        ?>

                        <?php if (('private' == $attrib_type) && !defined('PPS_NATIVE_CUSTOM_STATI_DISABLED')) : ?>
                            <a href="<?php echo $url; ?>?page=presspermit-status-new&amp;attrib_type=<?php echo $attrib_type; ?>"
                            class="add-new-h2"><?php echo esc_html(PWP::__wp('Add New')); ?></a>
                        <?php elseif (('moderation' == $attrib_type) && PPS::publishpressStatusesActive('', ['skip_status_dropdown_check' => true])) : ?>
                            <?php if (!defined('PUBLISHPRESS_VERSION')): ?>
                                <a href="<?php echo $url; ?>?page=presspermit-status-new&amp;attrib_type=<?php echo $attrib_type; ?>"
                                class="add-new-h2"><?php echo esc_html(PWP::__wp('Add New')); ?></a>
                            <?php else: ?>
                                <a href="<?php echo admin_url('admin.php?action=add-new&page=pp-modules-settings&module=pp-custom-status-settings'); ?>"
                                class="add-new-h2"><?php echo esc_html(PWP::__wp('Add New')); ?></a>
                            <?php endif; ?>
                        <?php endif; ?>

                    </h1>
                    </header>

                    <?php
                    if (presspermit()->getOption('display_hints')) {
                        echo '<div class="pp-hint">';
                        echo esc_html($hint);
                        echo '</div><br />';
                    }

                    /* if ( current_user_can( 'create_users' ) ) { ?> */

                    if ('moderation' == $attrib_type) :?>
                        <div class="activating">
                            <p>
                                <?php
                                $gen_url = admin_url("admin.php?page=presspermit-settings&pp_tab=statuses");

                                if (PPS::publishpressStatusesActive()) {
                                    $url = admin_url("admin.php?page=pp-modules-settings&module=pp-custom-status-settings");
                                    printf(__('Set permissions, workflow order, post type usage or button labels for %sPublishPress Statuses%s below. See also %sPermissions > Settings > Statuses%s.', 'pps'), '<a href="' . $url . '">', '</a>', '<a href="' . $gen_url . '">', '</a>');

                                } elseif (!defined('PUBLISHPRESS_VERSION')) {
                                    printf(__('For best results, activate the PublishPress plugin. See also %sPermissions > Settings > Statuses%s.', 'pps'), '<a href="' . $gen_url . '">', '</a>');

                                } elseif (PPS::publishpressStatusesActive('', ['skip_status_dropdown_check' => true])) {
                                    $url = admin_url("admin.php?page=pp-modules-settings&module=pp-custom-status-settings");
                                    printf(__('Please turn on the %sPublishPress Status Dropdown%s for Gutenberg. Then come back to set workflow status order, permissions and post type usage.', 'pps'), '<a href="' . $url . '">', '</a>', '<a href="' . $gen_url . '">', '</a>');
                                } else {
                                    $url = admin_url("admin.php?page=pp-modules-settings&module=pp-modules-settings-settings#modules-wrapper");
                                    printf(__('For best results, please turn on the %sPublishPress Statuses Module%s. See also %sPermissions > Settings > Statuses%s.', 'pps'), '<a href="' . $url . '">', '</a>', '<a href="' . $gen_url . '">', '</a>');
                                }
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <ul class="subsubsub">
                        <?php
                        $links = apply_filters('presspermit_post_status_types', []);

                        if (count($links) > 1) {
                            foreach ($links as $link_obj) :
                                if (empty($stepped)) {
                                    $stepped = true;
                                } else {
                                    echo '|';
                                }
                                ?>
                                <li>
                                    <a href="<?php echo $link_obj->url; ?>" <?php if ($attrib_type == $link_obj->attrib_type) echo 'class="current"'; ?> ><?php echo $link_obj->label; ?></a>
                                </li>
                            <?php endforeach;
                        } // endif more than one attribute
                        ?>
                    </ul>
                    <?php

                    $presspermit_statuses_table->views();
                    $presspermit_statuses_table->display();
                    ?>

                    <?php if (('moderation' == $attrib_type) && $pp->getOption('display_hints')) : ?>
                        <div class="activating">
                            <p>
                                <?php
                                $url = admin_url("admin.php?page=pp-modules-settings&module=pp-custom-status-settings");
                                printf(__('Enable Custom Capabilities by toggling the link below status name. If enabled, non-Editors will need a corresponding %ssupplemental role%s to edit posts of that status.', 'pps'), '<a href="' . admin_url("admin.php?page=presspermit-groups") . '">', '</a>');
                                ?>
                            </p>
                            <p>
                                <?php
                                if ($pp->getOption('moderation_statuses_default_by_sequence') && !PWP::isBlockEditorActive()) {
                                    printf(__('For post edit by a user who cannot publish, %sworkflow is configured%s to make the Publish button increment the post to the next workflow status permitted.', 'pps'), '<a href="' . admin_url("admin.php?page=presspermit-settings&pp_tab=statuses") . '">', '</a>');
                                } else {
                                    if (!PWP::isBlockEditorActive()) {
                                        printf(__('For post edit by a user who cannot publish, %sworkflow is configured%s to make the Publish button escalate the post to the highest-ordered workflow status permitted.', 'pps'), '<a href="' . admin_url("admin.php?page=presspermit-settings&pp_tab=statuses") . '">', '</a>');
                                    } else {
                                        printf(__('For post edit by a user cannot publish, the Publish button will escalate the post to the highest-order status permitted to the user.', 'pps'), '<a href="' . admin_url("admin.php?page=presspermit-settings&pp_tab=statuses") . '">', '</a>');
                                    }
                                }
                                ?>
                            </p>
                            <p>
                                <?php
                                if (!defined('PUBLISHPRESS_VERSION')) {
                                    $url = admin_url("admin.php?page=pp-modules-settings&module=pp-custom-status-settings");
                                    printf(__('Note that the Post Type itself will also need to have %sCustom Statuses%s and %sPermissions%s enabled.', 'pps'), "<a href='$url'>", '</a>', '<a href="' . admin_url("admin.php?page=presspermit-settings&pp_tab=core") . '">', '</a>');
                                } else {
                                    printf(__('Note that the Post Type itself will also need to have %sPermissions%s enabled.', 'pps'), '<a href="' . admin_url("admin.php?page=presspermit-settings&pp_tab=core") . '">', '</a>');
                                }
                                ?>
                            </p>
                        </div>
                    <?php endif; ?>

                    <p>
                    <?php
                        if (!defined('PRESSPERMIT_COLLAB_VERSION') && $pp->getOption('display_hints')) {
                            if (!$pp->moduleActive('compatibility'))
                                $msg = __('To define moderation statuses, activate the Compatibility Pack module.', 'presspermit');
                            elseif (true == $pp->keyStatus())
                                $msg = sprintf(__('To define moderation statuses, %1$sinstall%2$s the Compatibility Pack module.', 'presspermit'), "<a href='admin.php?page=presspermit-settings&pp_tab=install'>", '</a>');
                            else
                                $msg = sprintf(__('To define moderation statuses, %1$spurchase a license key%2$s and install the Compatibility Pack module.', 'presspermit'), '<a href="https://publishpress.com/pricing/">', '</a>');

                            ?>
                            <span class='pp-subtext' style='float:right'><?php echo $msg;?></span>
                            <?php
                        }
                        ?>

                        <a href="#show_cap_map" class="show-cap-map"><?php echo __('show capability mapping', 'pps'); ?></a>
                        <span class="cap-map-note"
                            style="display:none">&nbsp;&nbsp;&bull;&nbsp;&nbsp;<?php _e('<strong>Note</strong>: Capabilities are also mapped uniquely per post type.', 'pps'); ?></span>
                    </p>

                    <?php if (!empty($_REQUEST['show_caps'])): ?>
                        <script type="text/javascript">
                            /* <![CDATA[ */
                            jQuery(document).ready(function ($) {
                                $('div.pp-conditions table th.column-cap_map,div.pp-conditions table td.cap_map,span.cap-map-note').show();
                            });
                            /* ]]> */
                        </script>
                    <?php endif; ?>

                    <?php 
                    presspermit()->admin()->publishpressFooter();
                    ?>
                </div>
                <?php

                break;

        } // end of the $doaction switch
    }

    private function get_condition($attrib, $cond)
    {
        $attributes = PPS::attributes();

        if (!isset($attributes->attributes[$attrib]) || !isset($attributes->attributes[$attrib]->conditions[$cond]))
            return false;

        return $attributes->attributes[$attrib]->conditions[$cond];
    }
}
