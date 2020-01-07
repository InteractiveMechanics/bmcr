<?php
namespace PublishPress\Permissions\Statuses\UI;

/**
 * New Custom Post Status admin panel
 */

class StatusNew
{
    function __construct() {
        // This script executes on admin.php plugin page load (called by Dashboard\DashboardFilters::actMenuHandler)
        //
        $this->display();
    }

    private function display() {
        if (defined('PPS_NATIVE_CUSTOM_STATI_DISABLED')) {
            wp_die(__('Please use PublishPress to create custom statuses for publishing workflow.', 'pps'));
        }

        $url = apply_filters('presspermit_conditions_base_url', 'admin.php');

        if (isset($_REQUEST['wp_http_referer']))
            $wp_http_referer = $_REQUEST['wp_http_referer'];
        elseif (isset($_SERVER['HTTP_REFERER'])) {
            $wp_http_referer = $_SERVER['HTTP_REFERER'];
            $wp_http_referer = remove_query_arg(['update', 'edit', 'delete_count'], stripslashes($wp_http_referer));
        } else
            $wp_http_referer = '';
        ?>

        <?php
        if (!defined('ABSPATH')) exit; // Exit if accessed directly

        $admin = presspermit()->admin();

        $attribute = 'post_status';
        $attrib_type = (isset($_REQUEST['attrib_type'])) ? sanitize_key($_REQUEST['attrib_type']) : '';

        if (!current_user_can('pp_define_post_status') && (!$attrib_type || !current_user_can("pp_define_{$attrib_type}")))
            wp_die(__('You are not permitted to do that.', 'pps'));

        if (isset($_GET['update']) && empty($admin->errors)) :
            $url = add_query_arg(['page' => 'presspermit-statuses', 'attrib_type' => $attrib_type], admin_url(apply_filters('presspermit_role_usage_base_url', 'admin.php')));
            ?>
            <div id="message" class="updated">
                <p><strong><?php _e('Status created.', 'pps') ?>&nbsp;</strong>
                    <?php if ($wp_http_referer) : ?>
                        <a href="<?php echo(esc_url($url)); ?>"><?php _e('Back to Statuses', 'pps'); ?></a>
                    <?php endif; ?>
                </p></div>
        <?php endif; ?>

        <?php
        if (!empty($admin->errors) && is_wp_error($admin->errors)) : ?>
            <div class="error"><p><?php echo implode("</p>\n<p>", $admin->errors->get_error_messages()); ?></p></div>
        <?php endif; ?>

            <div class="wrap pressshack-admin-wrapper" id="condition-profile-page">
                <header>
                <?php \PublishPress\Permissions\UI\PluginPage::icon(); ?>
                <h1><?php echo ('moderation' == $attrib_type) ? __('Create Post Moderation Status', 'pps') : __('Create Post Privacy Status', 'pps');
                    ?></h1>
                </header>

                <form action="" method="post" id="createcondition" name="createcondition"
                    class="pp-admin" <?php do_action('presspermit_condition_create_form_tag'); ?>>
                    <input name="action" type="hidden" value="createcondition"/>
                    <input name="pp_attribute" type="hidden" value="<?php echo $attribute; ?>"/>
                    <input name="attrib_type" type="hidden" value="<?php echo $attrib_type; ?>"/>
                    <?php wp_nonce_field('pp-create-condition', '_wpnonce_pp-create-condition') ?>

                    <?php if ($wp_http_referer) : ?>
                        <input type="hidden" name="wp_http_referer" value="<?php echo esc_url($wp_http_referer); ?>"/>
                    <?php endif; ?>

                    <?php
                    require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/StatusAdmin.php');
                    StatusAdmin::status_edit_ui('', ['new' => true, 'attrib_type' => $attrib_type]);
                    ?>

                    <?php
                    do_action('presspermit_new_condition_ui');
                    ?>

                    <?php
                    submit_button(__('Create', 'pps'));
                    ?>

                </form>

                <?php 
                presspermit()->admin()->publishpressFooter();
                ?>
            </div>
        <?php
    }
}
