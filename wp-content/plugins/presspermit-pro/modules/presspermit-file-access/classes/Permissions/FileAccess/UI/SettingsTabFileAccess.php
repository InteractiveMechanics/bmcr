<?php
namespace PublishPress\Permissions\FileAccess\UI;

//use \PublishPress\Permissions\FileAccess as FileAccess;

class SettingsTabFileAccess
{
    var $advanced_enabled;

    function __construct()
    {
        $pp = presspermit();

        $this->advanced_enabled = $pp->getOption('advanced_options');

        add_filter('presspermit_option_tabs', [$this, 'optionTabs'], 5);

        add_filter('presspermit_section_captions', [$this, 'sectionCaptions']);
        add_filter('presspermit_option_captions', [$this, 'optionCaptions']);
        add_filter('presspermit_option_sections', [$this, 'optionSections']);

        add_action('presspermit_file_access_options_pre_ui', [$this, 'fileAccessOptionsPreUi']);
        add_action('presspermit_file_access_options_ui', [$this, 'fileAccessOptionsUi']);

        if (!$pp->getOption('file_filtering_regen_key')) {
            $pp->updateOption('file_filtering_regen_key', substr(md5(rand()), 0, 16));
        }
    }

    function optionTabs($tabs)
    {
        $tabs['file_access'] = __('File Access', 'pps');
        return $tabs;
    }

    function sectionCaptions($sections)
    {
        $new = [
            'file_filtering' => __('File Filtering', 'pps'),
        ];

        $key = 'file_access';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;

        return $sections;
    }

    function optionCaptions($captions)
    {
        $opt = [
            'file_filtering_regen_key' => __('File Filtering Reset Key:', 'ppff'),
            'small_thumbnails_unfiltered' => __('Small Thumbnails Unfiltered', 'ppff'),
            'unattached_files_private' => __('Make Unattached Files Private', 'ppff'),
            'attached_files_private' => __('Make Attached Files Private', 'ppff'),
        ];

        return array_merge($captions, $opt);
    }

    function optionSections($sections)
    {
        $new = [
            'file_filtering' => ['file_filtering_regen_key', 'unattached_files_private', 'attached_files_private'],
        ];

        $new['file_filtering'][] = 'small_thumbnails_unfiltered';

        $key = 'file_access';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;

        return $sections;
    }

    function fileAccessOptionsPreUi()
    {
        if (presspermit()->getOption('display_hints')) :
            ?>
            <div class="pp-optionhint">
                <?php
                printf(__('Settings related to the regulation of direct file access (by file URL).', 'presspermit'), __('File Access', 'ppf'));
                ?>
            </div>
        <?php
        endif;
    }

    private function displayFilteringStatus()
    {
        global $wp_rewrite;
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 

        $site_url = untrailingslashit(get_option('siteurl'));
        require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRules.php');
        
        $uploads = FileAccess::getUploadInfo();

        if (!got_mod_rewrite())
            $content_dir_notice = __('<strong>Note</strong>: Direct access to uploaded file attachments cannot be filtered because mod_rewrite is not enabled on your server.', 'ppff');

        //elseif (false === strpos($uploads['baseurl'], $site_url))
         //   $content_dir_notice = __('<strong>Note</strong>: Direct access to uploaded file attachments cannot be filtered because your WP_CONTENT_DIR is not in the WordPress branch.', 'ppff');

        elseif (!\PublishPress\Permissions\FileAccess\RewriteRules::siteConfigSupportsRewrite())
            $content_dir_notice = __('<strong>Note</strong>: Direct access to uploaded file attachments will not be filtered due to your nonstandard UPLOADS path.', 'ppff');

        elseif (empty($wp_rewrite->permalink_structure))
            $content_dir_notice = __('<strong>Note</strong>: Direct access to uploaded file attachments cannot be filtered because WordPress permalinks are set to default.', 'ppff');
        else
            $attachment_filtering = true;

        $disabled = 'disabled="disabled"'; // ! got_mod_rewrite() || ! empty($content_dir_notice);
        ?>
        <label for="file_filtering">
            <input name="file_filtering" type="checkbox" id="file_filtering" <?php echo $disabled; ?>
                   value="1" <?php checked(true, !empty($attachment_filtering)); ?> />
            <?php _e('Filter Uploaded File Attachments', 'ppff') ?></label>
        <br/>
        <div class="pp-subtext">
            <?php
            //if ( $ui->display_hints) 
            _e('Block direct URL access to images and other uploaded files in the WordPress uploads folder which are attached to posts that the user cannot read.  For each protected file, a separate RewriteRule will be added to the .htaccess file in this site&apos;s uploads folder.  Non-protected files are returned with no script execution whatsoever.', 'ppff');

            if (!empty($content_dir_notice)) {
                echo '<br /><span class="pp-warning">';
                echo $content_dir_notice;
                echo '</span>';
            }
            ?>
        </div>

        <?php


        if (is_multisite() && \PublishPress\Permissions\FileAccess\Network::msBlogsRewriting() && is_super_admin()) {
            $network_activated = PWP::isNetworkActivated();

            $default_all_sites = get_site_option('presspermit_last_file_rules_all_sites');

            if (!defined('PP_SUPPRESS_SETTINGS_HTACCESS')) {
                require_once(PRESSPERMIT_FILEACCESS_CLASSPATH . '/RewriteRulesNetLegacy.php');
                $rules = \PublishPress\Permissions\FileAccess\RewriteRulesNetLegacy::build_main_rules(
                    ['ms_all_sites' => $default_all_sites, 
                    'current_site_only' => !$ppff_network_activated]
                );
            }

            ?>
            <br/>
            <div class="pp-admin-info">

                <p>
                    <?php
                    echo "<strong>" . __('Multisite File Filtering Configuration:', 'presspermit') . "</strong> <br />";

                    _e('File Filtering on multisite installations will require the following rules to be inserted above the stock ms-files.php rules in the <strong>main .htaccess file</strong>:', 'presspermit');
                    ?>
                </p>

                <?php
                $suppress_htaccess_display = defined('PP_SUPPRESS_SETTINGS_HTACCESS') || (empty($_REQUEST['pp_show_rules']) && strlen($rules) > 1000);

                if (!$suppress_htaccess_display) :
                    ?>
                    <textarea rows='10' cols='110' readonly='readonly'><?php echo $rules; ?></textarea>
                <?php else : ?>
                    <div>
                        <a href="<?php echo admin_url("admin.php?page=presspermit-settings&amp;pp_tab=file_access&amp;pp_show_rules=1"); ?>"><?php _e('show required rules', 'ppff'); ?></a>
                    </div>
                <?php endif; ?>

                <div>

                    <?php
                    if ($ppff_network_activated && !defined('PP_SUPPRESS_SETTINGS_HTACCESS_CHECK')) {
                        if (file_exists(ABSPATH . '/wp-admin/includes/misc.php'))
                            include_once(ABSPATH . '/wp-admin/includes/misc.php');

                        if (file_exists(ABSPATH . '/wp-admin/includes/file.php'))
                            include_once(ABSPATH . '/wp-admin/includes/file.php');

                        //if ( function_exists( 'get_home_path' ) ) {
                        $htaccess_path = \PublishPress\Permissions\FileAccess\NetworkLegacy::get_home_path() . '.htaccess';
                        if (!file_exists($htaccess_path) || !is_writable($htaccess_path)) :
                            ?>
                            <br/>
                            <div class="pp-warning">
                                <?php _e('But your .htaccess is missing or not writeable!', 'presspermit'); ?>
                            </div>
                        <?php else :
                            $contents = file_get_contents($htaccess_path);

                            if (false === strpos($contents, $rules)) :
                                ?>
                                <br/>
                                <div class="pp-warning">
                                    <?php _e('.htaccess needs to be updated to include these rules.', 'presspermit'); ?>
                                </div>
                            <?php else : ?>
                                <br/>
                                <div class="pp-success">
                                    <?php _e('.htaccess file has all required rules.', 'presspermit'); ?>
                                </div>
                            <?php
                            endif;
                        endif; // .htaccess is writeable
                        //}
                    }
                    ?>

                    <?php
                    _e('These rules will not be inserted automatically.  You are responsible for editing .htaccess and later removing the rules if the functionality is no longer desired.', 'presspermit');
                    echo ' ';
                    _e('Note that an additional rule will need to be added with each new site. <em>To eliminate this requirement, research "WordPress remove ms-files".</em>', 'presspermit');
                    ?>
                </div>

                <?php if ($ppff_network_activated) : ?>
                    <div class="submit" style="padding:4px;padding-bottom:0;text-align:center">
                        <?php
                        $msg = __("You will need to manually restore the .htacces file to default contents if anything goes wrong. Proceed?", 'ppff');
                        $js_call = "javascript:if (confirm('$msg')) {return true;} else {return false;}";
                        ?>
                        <input type="submit" name="ppff_update_mu_htaccess"
                               value="<?php _e('Update .htaccess now', 'ppff') ?>" onclick="<?php echo $js_call; ?>"/>
                    </div>

                    <?php
                    $name = 'pp_htaccess_all_sites';
                    ?>
                    <div style="text-align:center">
                        <label for='<?php echo $name ?>'><select name='<?php echo $name; ?>' id='<?php echo $name; ?>'>
                                <option value="0"><?php _e('only for sites with protected files', 'ppff'); ?></option>
                                <option value="1" <?php if ($default_all_sites) echo ' selected=selected"'; ?>><?php _e('for all sites', 'ppff'); ?></option>
                                <option value="remove"><?php _e('NONE: remove PressPermit rules', 'ppff'); ?></option>
                            </select></label>
                    </div>
                <?php else : ?>
                    <br/>
                    <div class="pp-warning">
                        <?php _e('Since the plugin is not network-activated, you will need to modify the .htaccess file manually, inserting a RewriteRule as shown above for each site which needs file filtering.', 'ppff'); ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
        ?>
        <?php
    }

    function fileAccessOptionsUi()
    {
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 
        $tab = 'file_access';

        $section = 'file_filtering';                    // --- MAIN SECTION ---
        if (!empty($ui->form_options[$tab][$section])) : ?>
            <tr>
                <th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
                <td>
                    <?php
                    $this->displayFilteringStatus();

                    $id = 'unattached_files_private';
                    $hint = __('Make unattached files unreadable to users who do not have the edit_private_files or pp_list_all_files capability.', 'ppff');
                    echo '<br />';
                    $ret = $ui->optionCheckbox($id, $tab, 'file_filtering', $hint, '');

                    if (defined('PP_ATTACHED_FILE_AUTOPRIVACY')) {
                        $id = 'attached_files_private';
                        $hint = __('Make attached files unreadable to users who do not have the edit_private_files or pp_list_all_files capability.', 'ppff');
                        $ret = $ui->optionCheckbox($id, $tab, 'file_filtering', $hint, '');
                        echo '<br />';
                    }

                    if ($this->advanced_enabled) {
                        $id = 'small_thumbnails_unfiltered';
                        $hint = __('If Media Library performance and disclosure would be acceptable, you can disable file filtering for thumbnails (size specified in Settings > Media).', 'ppff');
                        $ret = $ui->optionCheckbox($id, $tab, 'file_filtering', $hint, '');
                    } else
                        echo '<br />';

                    $id = 'file_filtering_regen_key';  // retrieve for link display even if option setting is not enabled
                    $val = get_option("presspermit_{$id}");

                    if ($this->advanced_enabled) :
                        $ui->all_options[] = $id;

                        echo "<br /><div><label for='$id'>";
                        _e('File Filtering Reset Key:', 'ppff');
                        ?>
                        <input name="<?php echo($id); ?>" type="text" style="vertical-align:middle; width: 11em"
                               id="<?php echo($id); ?>" value="<?php echo($val); ?>"/>
                        </label>
                        </div>
                    <?php endif; ?>

                    <div style="margin-top:10px">
                        <?php
                        //if ( $ui->display_hints)  {
                        if ($val) {
                            if (is_multisite())
                                _e('To force regeneration of <strong>uploads/.htaccess</strong> with new file URL keys (at next site access), execute the following URL:', 'ppff');
                            else
                                _e('To force regeneration of <strong>uploads/.htaccess</strong> with new file URL keys, execute the following URL:', 'ppff');

                            $url = site_url("index.php?action=presspermit-expire-file-rules&amp;key=$val");
                            echo("<div style='margin-left:30px;margin-bottom:5px'><a href='$url'>$url</a></div>");
                            ?>
                            <div class="pp-subtext">
                                <?php _e('Best practice is to access the above url periodically (using your own cron service) to prevent long-term bookmarking of protected files.', 'ppff'); ?>
                            </div>
                            <?php
                        } else
                            _e('Supply a custom key which will enable a support url to regenerate file access keys.  Then execute the url regularly (using your own cron service) to prevent long-term bookmarking of protected files.', 'ppff');

                        //}
                        ?>
                    </div>
                    <br/>
                    <?php
                    printf(__('<strong>Note:</strong> FTP-uploaded files will not be filtered correctly until you run the %1$sAttachments Utility%2$s.', 'ppff'), "<a href='admin.php?page=presspermit-attachments_utility'>", '</a>');
                    ?>
                    <br/>

                    <div style="margin-top:10px">
                        <?php
                        //if ( $ui->display_hints)  {
                        if (!defined('PP_SUPPRESS_NGINX_CAPTION')) {
                            _e('For Nginx integration, see readme.txt in your plugins/file-access folder.', 'ppff');
                        }
                        //}
                        ?>
                    </div>

                </td>
            </tr>
        <?php endif;
    }
} // end class
