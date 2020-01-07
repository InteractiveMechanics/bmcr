<?php

namespace PublishPress\Permissions\UI;

use PublishPress\Permissions\Factory;

class SettingsTabInstall
{
    public function __construct()
    {
        add_filter('presspermit_option_tabs', [$this, 'optionTabs'], 0);
        add_filter('presspermit_section_captions', [$this, 'sectionCaptions']);
        add_filter('presspermit_option_captions', [$this, 'optionCaptions']);
        add_filter('presspermit_option_sections', [$this, 'optionSections']);

        add_action('presspermit_install_options_pre_ui', [$this, 'optionsPreUI']);
        add_action('presspermit_install_options_ui', [$this, 'optionsUI']);
    }

    public function optionTabs($tabs)
    {
        $tabs['install'] = __('Install', 'press-permit-core');
        return $tabs;
    }

    public function sectionCaptions($sections)
    {
        $new = [
            'key' => __('License Key', 'press-permit-core'),
            'version' => __('Version', 'press-permit-core'),
            'modules' => __('Modules', 'press-permit-core'),
            'beta_updates' => __('Beta Updates', 'press-permit-core'),
            'help' => PWP::__wp('Help'),
        ];

        $key = 'install';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        return $sections;
    }

    public function optionCaptions($captions)
    {
        $opt = [
            'key' => __('settings', 'press-permit-core'),
            'beta_updates' => __('Receive beta version updates for modules', 'press-permit-core'),
            'help' => __('settings', 'press-permit-core'),
        ];

        return array_merge($captions, $opt);
    }

    public function optionSections($sections)
    {
        $new = [
            'key' => ['edd_key'],
            'beta_updates' => ['beta_updates'],
            'help' => ['no_option'],
            'modules' => ['no_option'],
        ];

        $key = 'install';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        return $sections;
    }

    public function optionsPreUI()
    {
        if (isset($_REQUEST['pp_config_uploaded']) && empty($_POST)) : ?>
            <div id="message" class="updated">
                <p>
                    <strong><?php _e('Configuration data was uploaded.', 'press-permit-core'); ?>&nbsp;</strong>
                </p>
            </div>
        <?php elseif (isset($_REQUEST['pp_config_no_change']) && empty($_POST)) : ?>
            <div id="message" class="updated error">
                <p>
                    <strong><?php _e('Configuration data is unchanged since last upload.', 'press-permit-core'); ?>&nbsp;</strong>
                </p>
            </div>
        <?php elseif (isset($_REQUEST['pp_config_failed']) && empty($_POST)) : ?>
            <div id="message" class="error">
                <p>
                    <strong><?php _e('Configuration data could not be uploaded.', 'press-permit-core'); ?>&nbsp;</strong>
                </p>
            </div>
        <?php endif;
    }

    public function optionsUI()
    {
        $pp = presspermit();

        $ui = SettingsAdmin::instance();
        $tab = 'install';

        require_once(PRESSPERMIT_ABSPATH . '/includes-pro/library/Factory.php');
        $container      = \PublishPress\Permissions\Factory::get_container();
        $licenseManager = $container['edd_container']['license_manager'];

        $use_network_admin = $this->useNetworkUpdates();
        $suppress_updates = $use_network_admin && !is_super_admin();

        $section = 'key'; // --- UPDATE KEY SECTION ---
        if (!empty($ui->form_options[$tab][$section]) && !$suppress_updates) : ?>
            <tr>
                <td scope="row" colspan="2">
                    <?php

                    global $activated;

                    //$id = 'support_key';
                    $id = 'edd_key';

                    if (!get_transient('presspermit-refresh-update-info')) {
                        $pp->keyStatus(true);
                        set_transient('presspermit-refresh-update-info', true, 86400);
                    }

                    $opt_val = $pp->getOption($id);

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
                            __('Your license key has expired. For continued priority support, <a href="%s">please renew</a>.', 'press-permit-core'),
                            'admin.php?page=presspermit-settings&amp;pp_renewal=1'
                        );
                    } elseif (!empty($opt_val['expire_date'])) {
                        $class = 'activating';
                        if ($expire_days < 30) {
                            $is_err = true;
                        }

                        if ($expire_days == 1) {
                            $msg = sprintf(
                                __('Your license key will expire today. For updates and priority support, <a href="%s">please renew</a>.', 'press-permit-core'),
                                $expire_days,
                                'admin.php?page=presspermit-settings&amp;pp_renewal=1'
                            );
                        } elseif ($expire_days < 30) {
                            $msg = sprintf(
                                _n(
                                    'Your license key will expire in %d day. For updates and priority support, <a href="%s">please renew</a>.',
                                    'Your license key (for plugin updates) will expire in %d days. For updates and priority support, <a href="%s">please renew</a>.',
                                    $expire_days,
                                    'press-permit-core'
                                ),
                                $expire_days,
                                'admin.php?page=presspermit-settings&amp;pp_renewal=1'
                            );
                        } else {
                            $class = "activating hidden";
                        }
                    } elseif (!$activated) {
                        $class = 'activating';
                        $msg = sprintf(
                            __('For updates to Press Permit Pro, activate your <a href="%s">PublishPress license key</a>.', 'press-permit-core'),
                            'https://publishpress.com/pricing/'
                        );
                    } else {
                        $class = "activating hidden";
                        $msg = '';
                    }
                    ?>

                    <div class="pp-key-wrap">

                    <?php if ($expired && (!empty($key))) : ?>
                    
                        <span class="pp-key-expired"><?php _e("Key Expired", 'press-permit-core') ?></span>
                        <input name="<?php echo($id); ?>" type="text" id="<?php echo($id); ?>" style="display:none"/>
                        <button type="button" id="activation-button" name="activation-button"
                                class="button-secondary"><?php _e('Deactivate Key', 'press-permit-core'); ?></button>
                    <?php else : ?>
                        <div class="pp-key-label" style="float:left">
                            <span class="pp-key-active" <?php if (!$activated) echo 'style="display:none;"';?>><?php _e("Key Activated", 'press-permit-core') ?></span>
                            <span class="pp-key-inactive" <?php if ($activated) echo 'style="display:none;"';?>><?php _e("License Key", 'press-permit-core') ?></span>
                        </div>

                            <input name="<?php echo($id); ?>" type="text" placeholder="<?php _e('(please enter publishpress.com key)', 'press-permit-pro');?>" id="<?php echo($id); ?>"
                                   maxlength="40" <?php echo ($activated) ? ' style="display:none"' : ''; ?> />
                        
                            <button type="button" id="activation-button" name="activation-button"
                                    class="button-secondary"><?php echo (!$activated) ? __('Activate Key', 'press-permit-core') : __('Deactivate Key', 'press-permit-core'); ?></button>
                    <?php endif; ?>

                        <img id="pp_support_waiting" class="waiting" style="display:none;position:relative"
                             src="<?php echo esc_url(admin_url('images/wpspin_light.gif')) ?>" alt=""/>

                        <div class="pp-key-refresh" style="display:inline">
                            &bull;&nbsp;&nbsp;<a href="https://publishpress.com/checkout/purchase-history/"
                                                       target="_blank"><?php _e('review your account info', 'press-permit-core'); ?></a>
                        </div>
                    </div>

                    <?php if ($activated) : ?>
                        <?php if ($expired) : ?>
                            <div class="pp-key-hint-expired">
                                <span class="pp-key-expired pp-key-warning"> <?php _e('note: Renewal does not require deactivation. If you do deactivate, re-entry of the license key will be required.', 'press-permit-core'); ?></span>
                            </div>
                        <?php elseif ($pp->getOption('display_hints')) : ?>
                            <div class="pp-key-hint">
                            <span class="pp-subtext"> <?php _e('note: If you deactive, re-entry of the license key will be required for re-activation.', 'press-permit-core'); ?></span>
                        <?php endif; ?>
                        </div>

                    <?php elseif (!$expired) : ?>
                        <div class="pp-key-hint">
                        </div>
                    <?php endif ?>

                    <div id="activation-status" class="<?php echo $class ?>"><?php echo $msg; ?></div>
                    <div class="pp-settings-caption" style="display:none;">
                        <a href="<?php echo admin_url('admin.php?page=presspermit-settings'); ?>"><?php _e('reload module info', 'press-permit-core'); ?></a>
                    </div>

                    <?php if (!empty($is_err)) : ?>
                        <div id="activation-error" class="error"><?php echo $msg; ?></div>
                    <?php endif; ?>

                        <?php

                        if (!$activated || $expired) {
                            require_once(PRESSPERMIT_CLASSPATH . '/UI/HintsPro.php');
                            HintsPro::proPromo();
                        }
                        ?>
                </td>
            </tr>
            <?php

            do_action('presspermit_support_key_ui');
            self::footer_js($activated, $expired);
        endif; // any options accessable in this section

        $section = 'version'; // --- VERSION SECTION ---
        ?>
            <tr>
                <th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
                <td>

                    <?php
                    $update_info = [];

                    $info_link = '';

                    if (!$suppress_updates) {
                        $wp_plugin_updates = get_site_transient('update_plugins');
                        if (
                            $wp_plugin_updates && isset($wp_plugin_updates->response[plugin_basename(PRESSPERMIT_FILE)])
                            && !empty($wp_plugin_updates->response[plugin_basename(PRESSPERMIT_FILE)]->new_version)
                            && version_compare($wp_plugin_updates->response[plugin_basename(PRESSPERMIT_FILE)]->new_version, PRESSPERMIT_VERSION, '>')
                        ) {
                            $slug = 'presspermit-pro';

                            $_url = "plugin-install.php?tab=plugin-information&plugin=$slug&section=changelog&TB_iframe=true&width=600&height=800";
                            $info_url = ($use_network_admin) ? network_admin_url($_url) : admin_url($_url);

                            $info_link = "&nbsp;<span class='update-message'> &bull;&nbsp;&nbsp;<a href='$info_url' class='thickbox'>"
                                . sprintf(__('view %s&nbsp;details', 'press-permit-core'), $wp_plugin_updates->response[plugin_basename(PRESSPERMIT_FILE)]->new_version)
                                . '</a></span>';
                        }
                    }

                    ?>
                    <p>
                        <?php printf(__('PressPermit Pro Version: %1$s %2$s', 'press-permit-core'), PRESSPERMIT_VERSION, $info_link); ?>

                        &nbsp;&nbsp;&bull;&nbsp;&nbsp;<a href="admin.php?page=presspermit-settings&amp;presspermit_refresh_updates=1"><?php _e('update check / install', 'press-permit-core'); ?></a>

                        <br/>
                        <span style="display:none"><?php printf(__("Database Schema Version: %s", 'press-permit-core'), PRESSPERMIT_DB_VERSION); ?><br/></span>
                    </p>

                    <?php
                    global $wp_version;
                    printf(__("WordPress Version: %s", 'press-permit-core'), $wp_version);
                    ?>
                    <br/>
                    <?php printf(__("PHP Version: %s", 'press-permit-core'), phpversion()); ?>
                </td>
            </tr>
        <?php

        $section = 'modules'; // --- EXTENSIONS SECTION ---
        if (!empty($ui->form_options[$tab][$section])) : ?>
            <tr>
                <th scope="row">
                    <?php

                    echo $ui->section_captions[$tab][$section];
                    ?>
                </th>
                <td>

                    <?php
                    $missing = $inactive = [];

                    $ext_info = $pp->admin()->getModuleInfo();

                    if ($pp->getOption('display_hints')) {
                        $ext_info->blurb['capability-manager-enhanced'] =
                            __('Create your own WP roles or modify the capabilities defined for any WP Role.', 'press-permit-core');

                        $ext_info->descript['capability-manager-enhanced'] =
                            __('Create your own WP roles or modify the capabilities defined for any WP Role. Not necessary for all installations, but PP interop is particularly important for bbPress and BuddyPress installations.', 'press-permit-core');
                    }
                    
                    $pp_modules = presspermit()->getActiveModules();
                    $active_module_plugin_slugs = [];

                    if ($pp_modules) : ?>
                        <?php

                        $change_log_caption = __('<strong>Change Log</strong> (since your current version)', 'press-permit-core');

                        ?>
                        <h4 style="margin-top:0"><?php _e('Active Modules:', 'press-permit-core'); ?></h4>
                        <table class="pp-extensions">
                            <?php foreach ($pp_modules as $slug => $plugin_info) :
                                $info_link = '';
                                $update_link = '';
                                $alert = '';
                                ?>
                                <tr>
                                    <td <?php if ($alert) {
                                        echo 'colspan="2"';
                                    }
                                    ?>>
                                        <?php $id = "module_active_{$slug}";?>

                                        <label for="<?php echo $id; ?>">
                                            <input type="checkbox" id="<?php echo $id; ?>"
                                                   name="presspermit_active_modules[<?php echo $plugin_info->plugin_slug;?>]"
                                                   value="1" checked="checked" />

                                            <?php echo __($plugin_info->label);?>
                                        </label>

                                        <?php
                                            echo ' <span class="pp-gray">'
                                                . "</span> $info_link $update_link $alert"
                                        ?>
                                    </td>

                                    <?php if (!empty($ext_info) && !$alert) : ?>
                                        <td>
                                            <?php if (isset($ext_info->blurb[$slug])) : ?>
                                                <span class="pp-ext-info"
                                                      title="<?php if (isset($ext_info->descript[$slug])) {
                                                          echo esc_attr($ext_info->descript[$slug]);
                                                      }
                                                      ?>">
                                                <?php echo $ext_info->blurb[$slug]; ?>
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php 
                                $active_module_plugin_slugs[]= $plugin_info->plugin_slug;
                            endforeach; ?>
                        </table>
                    <?php
                    endif;

                    echo "<input type='hidden' name='presspermit_reviewed_modules' value='" . implode(',', $active_module_plugin_slugs) . "' />";

                    $inactive = $pp->getDeactivatedModules();

                    if (!defined('CAPSMAN_ENH_VERSION')) {
                        if (
                    0 === validate_plugin('capability-manager-enhanced/capsman-enhanced.php')
                    || 0 === validate_plugin('capsman-enhanced/capsman-enhanced.php')
                        ) {
                            $inactive['capability-manager-enhanced'] = true;
                        } else {
                            $missing['capability-manager-enhanced'] = true;
                        }
                    }

                    ksort($inactive);
                    if ($inactive) : ?>

                        <h4>
                            <?php
                            _e('Inactive Modules:', 'press-permit-core')
                            ?>
                        </h4>

                        <table class="pp-extensions">
                            <?php foreach ($inactive as $plugin_slug => $module_info) :
                                $slug = str_replace('presspermit-', '', $plugin_slug);
                                ?>
                                <tr>
                                    <td>
                                    
                                    <?php $id = "module_deactivated_{$slug}";?>

                                    <label for="<?php echo $id; ?>">
                                        <input type="checkbox" id="<?php echo $id; ?>"
                                                name="presspermit_deactivated_modules[<?php echo $plugin_slug;?>]"
                                                value="1" />

                                        <?php echo (!empty($module_info->title)) ? $module_info->title : $this->prettySlug($slug);?></td>
                                    </label>

                                    <?php if (!empty($ext_info)) : ?>
                                        <td>
                                            <?php if (isset($ext_info->blurb[$slug])) : ?>
                                                <span class="pp-ext-info"
                                                      title="<?php if (isset($ext_info->descript[$slug])) {
                                                          echo esc_attr($ext_info->descript[$slug]);
                                                      }
                                                      ?>">
                                                <?php echo $ext_info->blurb[$slug]; ?>
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php
                    endif;

                    ksort($missing);
                    if ($missing) :
                        ?>
                        <h4><?php _e('Available Modules:', 'press-permit-core'); ?></h4>
                        <table class="pp-extensions">
                            <?php foreach (array_keys($missing) as $slug) :
                                if ($activated && isset($update_info[$slug]) && !$need_supplemental_key && !$suppress_updates) {
                                    $_url = "update.php?action=$slug&amp;plugin=$slug&pp_install=1&TB_iframe=true&height=400";
                                    $install_url = ($use_network_admin) ? network_admin_url($_url) : admin_url($_url);
                                    $url = wp_nonce_url($install_url, "{$slug}_$slug");
                                    $install_link = "<span> &bull; <a href='$url' class='thickbox' target='_blank'>" . __('install', 'press-permit-core') . '</a></span>';
                                } else {
                                    $install_link = '';
                                }
                                ?>

                                <tr>
                                    <td>
                                        <?php

                                            $caption = ucwords(str_replace('-', ' ', $slug));
                                            echo '<span class="plugins update-message">'
                                                . '<a href="' . Settings::pluginInfoURL($slug) . '" class="thickbox" title=" ' . $caption . '">'
                                                . str_replace(' ', '&nbsp;', $caption) . '</a></span>';

                                        ?></td>
                                    <?php if (!empty($ext_info)) : ?>
                                        <td>
                                            <?php if (isset($ext_info->blurb[$slug])) : ?>
                                                <span class="pp-ext-info"
                                                      title="<?php if (isset($ext_info->descript[$slug])) {
                                                          echo esc_attr($ext_info->descript[$slug]);
                                                      }
                                                      ?>">
                                                <?php echo $ext_info->blurb[$slug]; ?>
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                        <p style="padding-left:15px;">
                            <?php

                            if (!$activated) {
                                echo '<span class="pp-red">'
                                    . __('For updates, please activate your PressPermit Pro license key above.', 'press-permit-core')
                                    . '<span>';
                            }
                            ?>
                        </p>

                    <?php elseif (!defined('PRESSPERMIT_PRO_VERSION')) : ?>
                        <p class="pp-feature-list-caption">
                            <strong><?php _e('PressPermit Pro features include:', 'press-permit-core'); ?></strong></p>
                        <ul class="pp-bullet-list">
                            <li><?php printf(__('%1$sContent-specific editing permissions, with PublishPress and Revisionary support%2$s', 'press-permit-core'), '<a href="https://publishpress.com/presspermit/?pp_module=collaboration">', '</a>'); ?></li>
                            <li><?php printf(__('%1$sCustom Post Statuses (for visibility or workflow moderation)%2$s', 'press-permit-core'), '<a href="https://publishpress.com/presspermit/?pp_module=status_control">', '</a>'); ?></li>
                            <li><?php printf(__('%1$sCustomize bbPress forum access%2$s', 'press-permit-core'), '<a href="https://publishpress.com/presspermit/?pp_module=compatibility">', '</a>'); ?></li>
                            <li><?php printf(__('%1$sFile Access control%2$s', 'press-permit-core'), '<a href="https://publishpress.com/presspermit/?pp_module=file-access">', '</a>'); ?></li>
                            <li><?php printf(__('%1$sRole Scoper import script%2$s', 'press-permit-core'), '<a href="https://publishpress.com/presspermit/?pp_module=import">', '</a>'); ?></li>
                            <li><?php printf(__('%1$s...and more%2$s', 'press-permit-core'), '<a href="https://publishpress.com/presspermit/">', '</a>'); ?></li>
                        </ul>
                    <?php
                    endif;

                    ?>
                </td>
            </tr>
        <?php
        endif; // any options accessable in this section

        $section = 'help'; // --- HELP SECTION ---
        if (!empty($ui->form_options[$tab][$section])) : ?>
            <tr>
                <th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
                <td>
                    <?php

                    if ($activated) {
                        ?>
                        <ul class="pp-support-list">
                            <li><a href='https://publishpress.com/presspermit/'
                                   target='pp_doc'><?php _e('PressPermit Documentation', 'press-permit-core'); ?></a></li>

                            <li class="pp-support-forum">
                                <a href="admin.php?page=presspermit-settings&amp;pp_help_ticket=1"
                                   target="pp_help_ticket">
                                    <?php _e('Submit a Help Ticket', 'press-permit-core'); ?>
                                </a> <strong>*</strong>
                            </li>

                            <li class="upload-config">
                                <a href="admin.php?page=presspermit-settings&amp;pp_upload_config=1">
                                    <?php _e('Upload site configuration to presspermit.com now', 'press-permit-core'); ?>
                                </a> <strong>*</strong>
                                <img id="pp_upload_waiting" class="waiting" style="display:none;position:relative"
                                     src="<?php echo esc_url(admin_url('images/wpspin_light.gif')) ?>" alt=""/>
                            </li>
                        </ul>

                        <div id="pp_config_upload_caption"><strong>
                                <?php printf(__('%s Site configuration data selected below will be uploaded to presspermit.com:', 'press-permit-core'), '<strong>* </strong>'); ?>
                            </strong></div>

                        <div id="pp_config_upload_wrap">
                            <?php
                            $ok = (array)$pp->getOption('support_data');
                            $ok['ver'] = 1;

                            $ui->all_options[] = 'support_data';

                            $avail = [
                                'ver' => __('Version info for server, WP, PressPermit and various other plugins', 'press-permit-core'),
                                'pp_options' => __('PressPermit Settings and related WP Settings', 'press-permit-core'),
                                'theme' => __('Theme name, version and status', 'press-permit-core'),
                                'active_plugins' => __('Activated plugins list', 'press-permit-core'),
                                'installed_plugins' => __('Inactive plugins list', 'press-permit-core'),
                                'wp_roles_types' => __('WordPress Roles, Capabilities, Post Types, Taxonomies and Post Statuses', 'press-permit-core'),
                                'pp_permissions' => __('Role Assignments and Exceptions', 'press-permit-core'),
                                'pp_groups' => __('Group definitions', 'press-permit-core'),
                                'pp_group_members' => __('Group Membership (id only)', 'press-permit-core'),
                                'pp_imports' => __('Role Scoper / Press Permit 1.x Configuration and Import Results', 'press-permit-core'),
                                'post_data' => __('Post id, status, author id, parent id, and term ids and taxonomy name (when support accessed from post or term edit form)', 'press-permit-core'),
                                'error_log' => __('PHP Error Log (recent entries, no absolute paths)', 'press-permit-core'),
                            ];

                            $ok['ver'] = true;
                            $ok['pp_options'] = true;
                            $ok['error_log'] = true;

                            ?>
                            <div class="support_data">
                                <?php
                                foreach ($avail as $key => $caption) :
                                    $id = 'support_data_' . $key;
                                    $disabled = (in_array($key, ['ver', 'pp_options', 'error_log'], true)) ? 'disabled="disabled"' : '';
                                    ?>
                                    <div>
                                        <label for="<?php echo $id; ?>">
                                            <input type="checkbox" id="<?php echo $id; ?>"
                                                   name="support_data[<?php echo $key; ?>]"
                                                   value="1" <?php echo $disabled;
                                            checked('1', !empty($ok[$key]), true); ?> />
                                            <?php echo $caption; ?>
                                        </label>
                                    </div>
                                <?php
                                endforeach;
                                ?>
                            </div>

                            <div>
                                <label for="pp_support_data_all"><input type="checkbox" id="pp_support_data_all"
                                                                        value="1"/> <?php _e('(all)', 'press-permit-core'); ?></label>
                            </div>

                            <div id="pp_config_upload_subtext">
                                <?php _e('<strong>note:</strong> user data, absolute paths, database prefix, post title, post content and post excerpt are <strong>never</strong> uploaded', 'press-permit-core'); ?>
                            </div>

                        </div>
                        <?php
                    } else {
                        ?>
                        <div>
                            <?php _e('Purchase of a license key enables access to the following resources:', 'press-permit-core'); ?>
                        </div>

                        <ul class="pp-support-list pp-bullet-list">
                            <li><?php _e('Priority support through our help ticket system', 'press-permit-core'); ?></a></li>
                            <li><?php _e('Optional uploading of your site configuration to assist troubleshooting', 'press-permit-core'); ?></li>
                        </ul>
                        <?php
                    }
                    ?>
                </td>
            </tr>
        <?php

        endif; // any options accessable in this section

        /* // @ todo: EDD integration, or eliminate
        if (!empty($activated)) {
            $section = 'beta_updates'; // --- BETA UPDATES SECTION ---
            if (!empty($ui->form_options[$tab][$section]) && !$suppress_updates) : ?>
                <tr>
                    <th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
                    <td>
                        <?php
                        if (preg_match("/dev|alpha|beta|rc/i", PRESSPERMIT_VERSION)) {
                            $hint = __('If you have already received a beta update and want to switch back to the current production version, switch off this option and click Update. Then look for an update prompt in the Plugins list.', 'press-permit-core');
                        } else {
                            $hint = '';
                        }

                        $ui->optionCheckbox('beta_updates', $tab, $section, $hint);
                        ?>
                    </td>
                </tr>
            <?php
            endif; // any options accessable in this section
        }
        */
    } // end function optionsUI()

    public function footer_js($activated, $expired)
    {
        $vars = [
            'activated' => ($activated || !empty($expired)) ? true : false,
            'expired' => !empty($expired),
            'activateCaption' => __('Activate Key', 'press-permit-core'),
            'deactivateCaption' => __('Deactivate Key', 'press-permit-core'),
            'connectingCaption' => __('Connecting to publishpress.com server...', 'press-permit-core'),
            'noConnectCaption' => __('The request could not be processed due to a connection failure.', 'press-permit-core'),
            'noEntryCaption' => __('Please enter the license key shown on your order receipt.', 'press-permit-core'),
            'errCaption' => __('An unidentified error occurred.', 'press-permit-core'),
            'keyStatus' => json_encode([
                'deactivated' => __('The key has been deactivated.', 'press-permit-core'),
                'valid' => __('The key has been activated.', 'press-permit-core'),
                'expired' => __('The key has expired.', 'press-permit-core'),
                'invalid' => __('The key is invalid.', 'press-permit-core'),
                '-100' => __('An unknown activation error occurred.', 'press-permit-core'),
                '-101' => __('The key provided is not valid. Please double-check your entry.', 'press-permit-core'),
                '-102' => __('This site is not valid to activate the key.', 'press-permit-core'),
                '-103' => __('The key provided could not be validated by publishpress.com.', 'press-permit-core'),
                '-104' => __('The key provided is already active on another site.', 'press-permit-core'),
                '-105' => __('The key has already been activated on the allowed number of sites.', 'press-permit-core'),
                '-200' => __('An unknown deactivation error occurred.', 'press-permit-core'),
                '-201' => __('Unable to deactivate because the provided key is not valid.', 'press-permit-core'),
                '-202' => __('This site is not valid to deactivate the key.', 'press-permit-core'),
                '-203' => __('The key provided could not be validated by publishpress.com.', 'press-permit-core'),
                '-204' => __('The key provided is not active on the specified site.', 'press-permit-core'),
            ]),
            'activateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_activate_key'),
            'deactivateURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_deactivate_key'),
            'refreshURL' => wp_nonce_url(admin_url(''), 'wp_ajax_pp_refresh_version'),
            'activationHelp' => sprintf(__('If this is incorrect, <a href="%s">request activation help</a>.', 'press-permit-core'), 'https://publishpress.com/contact/'),
            'supportOptChanged' => __('Please save settings before uploading site configuration.', 'press-permit-core'),
        ];

        wp_localize_script('presspermit-settings', 'ppSettings', $vars);
    }

    private function useNetworkUpdates()
    {
        return false; //(is_multisite() && (is_network_admin() || PWP::isNetworkActivated() || PWP::isMuPlugin()));
    }

    private function pluginUpdateUrl($plugin_file, $action = 'upgrade-plugin')
    {
        $_url = "update.php?action=$action&amp;plugin=$plugin_file";
        $url = ($this->useNetworkUpdates()) ? network_admin_url($_url) : admin_url($_url);
        $url = wp_nonce_url($url, "{$action}_$plugin_file");
        return $url;
    }

    private function prettySlug($slug)
    {
        switch ($slug) {  // @todo: adjust this upstream
            case 'collaboration':
                return __('Collaborative Publishing', 'press-permit-core');
                break;

            case 'statuses':
                return __('Status Control', 'press-permit-core');
                break;

            case 'circles':
                return __('Access Circles', 'press-permit-core');
                break;

            case 'compatibility':
                return __('Compatibility Pack', 'press-permit-core');
                break;
            
            case 'sync':
                return __('Sync Posts', 'press-permit-core');
                break;

            default:
                $slug = str_replace('presspermit-', '', $slug);
                $slug = str_replace('Pp', 'PP', ucwords(str_replace('-', ' ', $slug)));
                $slug = str_replace('press', 'Press', $slug); // temp workaround
                $slug = str_replace('Wpml', 'WPML', $slug);
                return $slug;
        }
    }
} // end class
