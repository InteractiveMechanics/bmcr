<?php
namespace PublishPress\Permissions\Compat\UI;

/**
 * PP Compatibility Pack Settings
 *
 * @package PressPermit
 * @author Kevin Behrens
 * @copyright Copyright (c) 2019, PublishPress
 * 
 */

class Settings
{
    //private $bbp_teaser_disabled = false;

    function __construct()
    {
        add_filter('presspermit_option_tabs', [$this, 'optionTabs'], 10);

        add_filter('presspermit_section_captions', [$this, 'sectionCaptions']);
        add_filter('presspermit_option_captions', [$this, 'optionCaptions']);
        add_filter('presspermit_option_sections', [$this, 'optionSections'], 20);

        add_action('presspermit_network_options_pre_ui', [$this, 'network_options_pre_ui']);
        add_action('presspermit_network_options_ui', [$this, 'network_options_ui']);

        add_action('presspermit_teaser_options_ui', [$this, 'teaser_options_ui'], 20);
        add_filter('presspermit_teaser_enable_options', [$this, 'teaser_enable_options'], 10, 3);

        add_filter('presspermit_cap_descriptions', [$this, 'flt_cap_descriptions']);
        add_action('presspermit_teaser_type_row', [$this, 'act_teaser_type_row'], 10, 2);
    }

    function teaser_disable_bbp($types, $args)
    {
        unset($types['forum']);
        return $types;
    }

    function teaser_enable_options($options, $post_type, $current_setting)
    {
        if ('forum' == $post_type) {
            $options = array_intersect_key($options, ['0' => true, '1' => true]);
            $options[1] = __("fixed teaser (specify topic and reply parameters below)", 'ppp');
        }
        return $options;
    }

    function optionTabs($tabs)
    {
        global $blog_id;

        if (is_multisite() && (1 == $blog_id))
            $tabs['network'] = __('Network', 'ppp');

        return $tabs;
    }

    function sectionCaptions($sections)
    {
        global $blog_id;

        // Network tab
        if (is_multisite() && (1 == $blog_id)) {
            $new = [
                'groups' => __('Groups', 'ppp'),
            ];

            $key = 'network';
            $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        }

        if (function_exists('bbp_get_version')) {
            $new = [
                'forum_teaser' => __('Forum Teaser', 'ppp'),
            ];

            $key = 'teaser';
            $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        }

        return $sections;
    }

    function optionCaptions($captions)
    {
        global $blog_id;

        $opt = [];

        if (is_multisite() && (1 == $blog_id)) {
            $opt['netwide_groups'] = __('Network-wide groups', 'ppp');
        }

        if (function_exists('bbp_get_version')) {
            $opt['topics_teaser'] = __('Teaser Topics', 'ppp');
            $opt['forum_teaser_hide_author_link'] = __('Hide Topic / Reply Author Link', 'ppp');
        }

        return array_merge($captions, $opt);
    }

    function optionSections($sections)
    {
        global $blog_id;

        if (is_multisite() && (1 == $blog_id)) {
            $new = [
                'groups' => ['netwide_groups'],
            ];

            $key = 'network';
            $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        }

        /* NOTE: all teaser options follow scope setting of do_teaser */
        if (function_exists('bbp_get_version')) {
            $sections['teaser']['forum_teaser'] = ['tease_topic_replace_content', 'forum_teaser_hide_author_link'];
        }

        return $sections;
    }

    function network_options_pre_ui()
    {
        if (presspermit()->getOption('display_hints')) :
            ?>
        <div class="pp-optionhint">
            <?php
            printf(__('Additional settings provided by the %s module.', 'presspermit'), __('Compatibility Pack', 'ppp'));
            ?>
        </div>
    <?php
    endif;
    }

    function network_options_ui()
    {
        global $blog_id;

        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 
        $tab = 'network';

        if (is_multisite() && (1 == $blog_id)) {
            $section = 'groups';                                    // --- GROUPS SECTION ---
            if (!empty($ui->form_options[$tab][$section])) : ?>
                    <tr>
                        <th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
                        <td>
                            <?php
                            $hint =  __('If enabled, custom group membership is applied network-wide (though role assignments are still site-specific).', 'ppp');
                            $ui->optionCheckbox('netwide_groups', $tab, $section, $hint, '');
                            ?>
                        </td>
                    </tr>
                <?php
            endif; // any options accessable in this section
        } // endif multisite
    }

    function teaser_options_ui()
    {
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 
        $tab = 'teaser';

        $pp = presspermit();

        if (!function_exists('bbp_get_version'))
            return;

        $option_basename = 'post_teaser_enabled';
        $do_teaser = ['post' => $ui->getOption($option_basename)];
        $source_name = 'post';

        $section = 'forum_teaser';                                // --- FORUM TEASER SECTION ---

        if (!empty($ui->form_options[$tab][$section])) : ?>
            <?php
            $tease_post_types = (array)$pp->getOption('tease_post_types');
            $topics_teaser = $pp->getOption('topics_teaser');

            $tr_style = ($do_teaser[$source_name] && !empty($tease_post_types['forum']) && $topics_teaser) 
            ? '' 
            : "style='display:none'";

            ?>
            <tr <?php echo $tr_style; ?>>
            <th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
            <td>

            <?php
            // now draw the teaser replacement / prefix / suffix input boxes
            $user_suffixes = ['_anon', ''];

            $types_display = [
                'topic' => __('Topic Teaser Text (%s):', 'ppp'), 
                'reply' => __('Reply Teaser Text (%s):', 'ppp')
            ];

            $items_display = [
                'content' => __('First Reply', 'ppp'), 
                'other_content' => __('Other Replies', 'ppp')
            ];

            $div_display = ($tr_style) ? 'none' : 'block';

            echo "<div id='topics-teaserdef' style='display:$div_display; margin-top: 2em;'>";

            foreach ($types_display as $type => $type_caption) {
                if ('topic' == $type)
                    $div_display = (in_array($topics_teaser, [1, '1', 'tease_topics'])) ? 'block' : 'none';
                else
                    $div_display = ($topics_teaser) ? 'block' : 'none';

                echo "<div id='{$type}_teaserdef' style='display:$div_display;'>";

                // separate input boxes to specify teasers for anon users and unpermitted logged users
                foreach ($user_suffixes as $anon) {
                    $user_descript = ($anon) ?  __('anonymous users', 'ppp') : __('logged users', 'ppp');

                    echo '<strong>';
                    printf($type_caption, $user_descript);
                    echo '</strong>';
                    echo ('<ul class="pp-textentries ppp-textentries">');

                    $action = 'replace';

                    echo ('<li>');
                    echo '<table><tbody>';

                    foreach (['content', 'other_content'] as $item) {
                        $option_name = "tease_{$type}_{$action}_{$item}{$anon}";
                        if (!$opt_val = $pp->getOption($option_name))
                            $opt_val = '';

                        $ui->all_options[] = $option_name;

                        $id = $option_name;
                        $name = $option_name;

                        echo "<tr><td class='td-label'><label for='$id'>";
                        echo ($items_display[$item] . ':');
                        echo '</label>';

                        if ('content' == $item) {
                            echo '<br /><div class="pp-gray pp-add-login-form" style="padding-left: 15px;"><a href="#">[login_form]</a></div>';
                        }
                        ?>
                    </td>
                    <td>

                    <?php if ('content' == $item) : ?>
                        <textarea style="width:100%" name="<?php echo ($name); ?>" id="<?php echo ($id); ?>">
                        <?php echo ($opt_val); ?>
                        </textarea>
                    <?php else : ?>
                        <input name="<?php echo ($name); ?>" type="text" id="<?php echo ($id); ?>" value="<?php echo ($opt_val); ?>" />
                    <?php endif; ?>

                    </td>
                </tr>
                <?php
                } // end foreach items

                echo ('</tbody></table></li>');

                echo ("</ul><br />");
            } // end foreach user_suffixes

            echo '</div>';
        } // end foreach types
        ?>

        <?php if (in_array('forum_teaser_hide_author_link', $ui->form_options[$tab][$section], true)) :
            $hint =  '';
            $ui->optionCheckbox('forum_teaser_hide_author_link', $tab, $section, $hint, '');
        endif;
        ?>

        </td>
        </tr>
    <?php
    endif;
    }

    function flt_cap_descriptions($pp_caps)
    {
        $pp_caps['pp_create_network_groups'] = __('Can create network-wide permission groups', 'ppp');
        $pp_caps['pp_manage_network_members'] = __('If group editing is allowed, can also modify network group membership', 'ppp');

        return $pp_caps;
    }

    function act_teaser_type_row($post_type, $teaser_setting)
    {
        if ('forum' == $post_type) {
            /*
            if (!empty($this->bbp_teaser_disabled))
                return;
            */

            echo '<div style="margin-top:10px;">';
            printf(__('%sTopics:%s', 'ppp'), '<a href="#topics-teaserdef">', '</a>');
            echo '&nbsp;<select name="topics_teaser" id="topics_teaser">';


            $topics_teaser = presspermit()->getOption('topics_teaser');
            $captions = [
                0 => __("Hide topics and replies", 'ppp'), 
                1 => __("Tease topics and replies", 'ppp'), 
                'tease_replies' => __("Show topics, tease replies", 'ppp')
            ];
            
            foreach ($captions as $key => $value) {
                $selected = ($topics_teaser == $key) ? 'selected="selected"' : '';
                echo "\n\t<option value='$key' " . $selected . ">$captions[$key]</option>";
            }
            echo '</select>&nbsp;</div>';

            ?>
            <div id="pp_bbp_forum_teaser_template_notice" style="margin-top:5px;<?php if ($topics_teaser) echo 'display:none;'; ?>">
            <span class="pp-subtext">
            <?php
            _e('The single forum teaser may be customized by STYLESHEETPATH/press-permit/teaser-content-forum.php', 'ppp');
            echo '</span></div>';

            \PublishPress\Permissions\UI\SettingsAdmin::instance()->all_options[] = 'topics_teaser';
        }
    }
}
