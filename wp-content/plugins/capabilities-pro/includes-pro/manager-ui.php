<?php
namespace PublishPress\Capabilities;

class ManagerUI {
    public static function loadScripts() {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
        wp_enqueue_script('publishpress-caps-pro-settings', plugins_url('', CME_FILE) . "/includes-pro/settings-pro{$suffix}.js", ['jquery', 'jquery-form'], PUBLISHPRESS_CAPS_VERSION, true);
        $wp_scripts->in_footer[] = 'publishpress-caps-pro-settings';  // otherwise it will not be printed in footer  @todo: review
    }

    public static function drawOptionsUI() {
        ?>
        <dl>
            <dt><?php _e('Options', 'capabilities-pro-by-publishpress'); ?></dt>
            <dd style="text-align:center;">
            <?php if (defined('PUBLISHPRESS_VERSION') && class_exists('PP_Custom_Status')):
                $checked = get_option('cme_custom_status_control') ? 'checked="checked"' : '';
                ?>
                <p>
                <label for="" title="<?php _e('Control selection of custom post statuses.', 'capabilities-pro-by-publishpress');?>"> <input type="checkbox" name="cme_custom_status_control" id="cme_custom_status_control" value="1" <?php echo $checked;?>> <?php _e('Control Custom Statuses', 'capabilities-pro-by-publishpress'); ?> </label>
                </p>
            <?php endif;?>
                <?php 
                $checked = get_option('cme_display_branding', 1) ? 'checked="checked"' : '';
                ?>
                <p>
                <label for="" title="<?php _e('Hide the PublishPress footer and other branding.', 'capabilities-pro-by-publishpress');?>"> <input type="checkbox" name="cme_display_branding" id="cme_display_branding" value="1" <?php echo $checked;?>> <?php _e('Display Branding', 'capabilities-pro-by-publishpress'); ?> </label>
                </p>
            </dd>
        </dl>
        <?php
    }

    public static function drawKeyUI() {
        require_once(PUBLISHPRESS_CAPS_ABSPATH . '/includes-pro/library/Factory.php');
        $container      = \PublishPress\Capabilities\Factory::get_container();
        $licenseManager = $container['edd_container']['license_manager'];

        global $activated;

        $id = 'edd_key';

        if (!get_transient('publishpress-caps-refresh-update-info')) {
            publishpress_caps_pro()->keyStatus(true);
            set_transient('publishpress-caps-refresh-update-info', true, 86400);
        }

        //$opt_val = revisionary()->getOption($id);
        $opt_val = get_option("cme_edd_key");

        if (!is_array($opt_val) || count($opt_val) < 2) {
            $activated = false;
            $expired = false;
            $key = '';
            $opt_val = [];
        } else {
            $activated = !empty($opt_val['license_status']) && ('valid' == $opt_val['license_status']);
            $expired = $opt_val['license_status'] && ('expired' == $opt_val['license_status']);
        }

        if (isset($opt_val['expire_date']) && is_date($opt_val['expire_date'])) {
            $date = new \DateTime(date('Y-m-d H:i:s', strtotime($opt_val['expire_date'])), new \DateTimezone('UTC'));
            $date->setTimezone(new \DateTimezone('America/New_York'));
            $expire_date_gmt = $date->format("Y-m-d H:i:s");
            $expire_days = intval((strtotime($expire_date_gmt) - time()) / 86400);
        } else {
            unset($opt_val['expire_date']);
        }

        $msg = '';

        if ($expired) {
            $class = 'activating';
            $is_err = true;
            $msg = sprintf(
                __('Your PublishPress license key has expired. For continued priority support, <a href="%s">please renew</a>.', 'capabilities-pro-by-publishpress'),
                'https://publishpress.com/my-downloads/'
            );
        } elseif (!empty($opt_val['expire_date'])) {
            $class = 'activating';
            if ($expire_days < 30) {
                $is_err = true;
            }

            if ($expire_days == 1) {
                $msg = sprintf(
                    __('Your PublishPress license key will expire today. For updates and priority support, <a href="%s">please renew</a>.', 'capabilities-pro-by-publishpress'),
                    $expire_days,
                    'https://publishpress.com/my-downloads/'
                );
            } elseif ($expire_days < 30) {
                $msg = sprintf(
                    _n(
                        'Your PublishPress license key will expire in %d day. For updates and priority support, <a href="%s">please renew</a>.',
                        'Your PublishPress license key (for plugin updates) will expire in %d days. For updates and priority support, <a href="%s">please renew</a>.',
                        $expire_days,
                        'capabilities-pro-by-publishpress'
                    ),
                    $expire_days,
                    'https://publishpress.com/my-downloads/'
                );
            } else {
                $class = "activating hidden";
            }
        } elseif (!$activated) {
            $class = 'activating';
            //$msg = sprintf(__('For updates, activate your <a href="%s">key</a>.', 'capabilities-pro-by-publishpress'), 'https://publishpress.com/pricing/');
            //$msg = sprintf(__('<a href="%s">Pricing</a>', 'capabilities-pro-by-publishpress'), 'https://publishpress.com/pricing/');
        } else {
            $class = "activating hidden";
            $msg = '';
        }
        ?>

        <dl>
        <dt><?php _e('License Key', 'capabilities-pro-by-publishpress'); ?></dt>
        <dd class="edd-key" style="text-align:center;">
        
        <div class="pp-key-wrap">
            <?php if ($expired && (!empty($key))) : ?>
                <span class="pp-key-expired"><?php _e("Key Expired", 'capabilities-pro-by-publishpress') ?></span>
                <input name="<?php echo($id); ?>" type="text" id="<?php echo($id); ?>" style="display:none"/>
                <button type="button" id="activation-button" name="activation-button"
                        class="button-secondary"><?php _e('Deactivate', 'capabilities-pro-by-publishpress'); ?></button>
            <?php else : ?>
                <div class="pp-key-label">
                    <span class="pp-key-active" <?php if (!$activated) echo 'style="display:none;"';?>><?php _e("Activated", 'press-permit-core') ?></span>
                </div>

                <input name="<?php echo($id); ?>" type="text" placeholder="<?php _e('(enter publishpress.com key)', 'press-permit-pro');?>" id="<?php echo($id); ?>"
                    maxlength="40" <?php echo ($activated) ? ' style="display:none"' : ''; ?> />

                <button type="button" id="activation-button" name="activation-button"
                    class="button-secondary"><?php echo (!$activated) ? __('Activate', 'capabilities-pro-by-publishpress') : __('Deactivate', 'capabilities-pro-by-publishpress'); ?></button>
            <?php endif; ?>

            <img id="pp_support_waiting" class="waiting" style="display:none;position:relative" src="<?php echo esc_url(admin_url('images/wpspin_light.gif')) ?>" alt=""/>
        </div>

        <br class="break" />

        <?php
        $update_info = [];

        $info_link = '';

        if (empty($suppress_updates)) {
            $wp_plugin_updates = get_site_transient('update_plugins');
            if (
                $wp_plugin_updates && isset($wp_plugin_updates->response[plugin_basename(CME_FILE)])
                && !empty($wp_plugin_updates->response[plugin_basename(CME_FILE)]->new_version)
                && version_compare($wp_plugin_updates->response[plugin_basename(CME_FILE)]->new_version, PUBLISHPRESS_CAPS_VERSION, '>')
            ) {
                $update_available = true;

                $slug = 'capabilities-pro-by-publishpress';

                $_url = "plugin-install.php?tab=plugin-information&plugin=$slug&section=changelog&TB_iframe=true&width=600&height=800";
                $info_url = (!empty($use_network_admin)) ? network_admin_url($_url) : admin_url($_url);

                $info_link = "&nbsp;<span class='update-message'> &bull;&nbsp;&nbsp;<a href='$info_url' class='thickbox'>"
                    . sprintf(__('view %s&nbsp;details', 'capabilities-pro-by-publishpress'), $wp_plugin_updates->response[plugin_basename(CME_FILE)]->new_version)
                    . '</a></span>';
            }
        }
        ?>

        <div class="edd-key-links">
            <div id="activation-status" class="<?php echo $class?>"></div>

            <?php if (!empty($update_available)):?>
                <?php //printf(__('Version %1$s %2$s', 'capabilities-pro-by-publishpress'), PUBLISHPRESS_CAPS_VERSION, $info_link); 
                ?>

                <!-- 
                <a href="<?php echo $info_url;?>" class="thickbox"><?php _e('Update&nbsp;Available', 'capabilities-pro-by-publishpress'); ?> </a>
                -->

                <a href="<?php echo admin_url('update-core.php');?>"><?php _e('Update&nbsp;Available', 'capabilities-pro-by-publishpress'); ?></a>

                &nbsp;&bull;&nbsp;
            <?php elseif (current_user_can('activate_plugins')):?>
                <a href="<?php echo add_query_arg('publishpress_caps_refresh_updates', 1, $_SERVER['REQUEST_URI']);?>"><?php _e('Update&nbsp;Check', 'capabilities-pro-by-publishpress'); ?></a>
                &nbsp;&bull;&nbsp;
            <?php endif;?>

            <span class="pp-key-refresh">
            <a href="https://publishpress.com/checkout/purchase-history/" target="_blank">
            <?php _e('Account', 'capabilities-pro-by-publishpress');?>
            </a>
            </span>

            <?php if (!$activated):?>
                &nbsp;&bull;&nbsp;
                <span><?php printf(__('<a href="%s" target="_blank">Pricing</a>', 'capabilities-pro-by-publishpress'), 'https://publishpress.com/pricing/'); ?></span>
            <?php endif;?>
        </div>

        <?php if (!empty($is_err)) : ?>
            <div id="activation-error" class="error"><?php echo $msg; ?></div>
        <?php endif; ?>

            <?php
            /*
            if (!$activated || $expired) {
                require_once(REVISIONARY_CLASSPATH . '/UI/HintsPro.php');
                HintsPro::proPromo();
            }
            */
            ?>

        </dd>
        </dl>

        <?php

        //do_action('cme_support_key_ui');
        self::footer_js($activated, $expired);
    }

    private static function footer_js($activated, $expired)
    {
        $vars = [
            'activated' => ($activated || !empty($expired)) ? true : false,
            'expired' => !empty($expired),
            'activateCaption' => __('Activate Key', 'capabilities-pro-by-publishpress'),
            'deactivateCaption' => __('Deactivate Key', 'capabilities-pro-by-publishpress'),
            'connectingCaption' => __('Connecting to publishpress.com server...', 'capabilities-pro-by-publishpress'),
            'noConnectCaption' => __('The request could not be processed due to a connection failure.', 'capabilities-pro-by-publishpress'),
            'noEntryCaption' => __('Please enter the license key shown on your order receipt.', 'capabilities-pro-by-publishpress'),
            'errCaption' => __('An unidentified error occurred.', 'capabilities-pro-by-publishpress'),
            'keyStatus' => json_encode([
                'deactivated' => __('The key has been deactivated.', 'capabilities-pro-by-publishpress'),
                'valid' => __('The key has been activated.', 'capabilities-pro-by-publishpress'),
                'expired' => __('The key has expired.', 'capabilities-pro-by-publishpress'),
                'invalid' => __('The key is invalid.', 'capabilities-pro-by-publishpress'),
                '-100' => __('An unknown activation error occurred.', 'capabilities-pro-by-publishpress'),
                '-101' => __('The key provided is not valid. Please double-check your entry.', 'capabilities-pro-by-publishpress'),
                '-102' => __('This site is not valid to activate the key.', 'capabilities-pro-by-publishpress'),
                '-103' => __('The key provided could not be validated by publishpress.com.', 'capabilities-pro-by-publishpress'),
                '-104' => __('The key provided is already active on another site.', 'capabilities-pro-by-publishpress'),
                '-105' => __('The key has already been activated on the allowed number of sites.', 'capabilities-pro-by-publishpress'),
                '-200' => __('An unknown deactivation error occurred.', 'capabilities-pro-by-publishpress'),
                '-201' => __('Unable to deactivate because the provided key is not valid.', 'capabilities-pro-by-publishpress'),
                '-202' => __('This site is not valid to deactivate the key.', 'capabilities-pro-by-publishpress'),
                '-203' => __('The key provided could not be validated by publishpress.com.', 'capabilities-pro-by-publishpress'),
                '-204' => __('The key provided is not active on the specified site.', 'capabilities-pro-by-publishpress'),
            ]),
            'activateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_activate_key'),
            'deactivateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_deactivate_key'),
            'refreshURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_refresh_version'),
            'activationHelp' => sprintf(__('If this is incorrect, <a href="%s">request activation help</a>.', 'capabilities-pro-by-publishpress'), 'https://publishpress.com/contact/'),
            'supportOptChanged' => __('Please save settings before uploading site configuration.', 'capabilities-pro-by-publishpress'),
        ];

        wp_localize_script('publishpress-caps-pro-settings', 'ppCapabilitiesSettings', $vars);
    }
}
