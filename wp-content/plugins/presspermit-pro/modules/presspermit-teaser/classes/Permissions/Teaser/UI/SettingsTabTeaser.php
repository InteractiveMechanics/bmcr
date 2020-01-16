<?php
namespace PublishPress\Permissions\Teaser\UI;

//use \PublishPress\Permissions\Teaser as Teaser;

class SettingsTabTeaser
{
    function __construct()
    {
        add_filter('presspermit_option_tabs', [$this, 'optionTabs'], 7);
        add_filter('presspermit_section_captions', [$this, 'sectionCaptions']);
        add_filter('presspermit_option_captions', [$this, 'optionCaptions']);
        add_filter('presspermit_option_sections', [$this, 'optionSections']);

        add_action('presspermit_teaser_options_pre_ui', [$this, 'optionsPreUI']);
        add_action('presspermit_teaser_options_ui', [$this, 'optionsUI']);
    }

    function optionTabs($tabs)
    {
        $tabs['teaser'] = __('Teaser', 'pptx');
        return $tabs;
    }

    function sectionCaptions($sections)
    {
        $new = [
            'rss_feeds' => __('RSS Feeds', 'pptx'),
            'custom_fields' => __('Custom Fields', 'pptx'),
            'hidden_content_teaser' => __('Hidden Content Teaser', 'pptx'),
        ];
        $key = 'teaser';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        return $sections;
    }

    function optionCaptions($captions)
    {
        $opt = [
            'rss_private_feed_mode' => __('Display mode for readable private posts', 'pptx'),
            'rss_nonprivate_feed_mode' => __('Display mode for readable non-private posts', 'pptx'),
            'feed_teaser' => __('Feed Replacement Text (use %permalink% for post URL)', 'pptx'),
            'post_teaser_enabled' => __('Enable', 'pptx'),    /* NOTE: SettingsSubmit must sync all other teaser-related options to do_teaser scope setting */
            'teaser_hide_thumbnail' => __('Hide Featured Image when Teaser is applied', 'pptx'),
            'teaser_hide_custom_private_only' => __('"Hide Private" settings only apply to custom privacy (Member, Premium, Staff, etc.)', 'pptx'),
        ];

        return array_merge($captions, $opt);
    }

    function optionSections($sections)
    {
        $new = [
            'rss_feeds' => ['rss_private_feed_mode', 'rss_nonprivate_feed_mode', 'feed_teaser'],
            'custom_fields' => ['teaser_hide_thumbnail'],
            'hidden_content_teaser' => ['post_teaser_enabled', 'teaser_hide_custom_private_only'] /* NOTE: all teaser options follow scope setting of do_teaser */
            /*, 'tease_public_posts_only', 'tease_direct_access_only', 'use_teaser', 'tease_logged_only', 
                                'tease_replace_content', 'tease_replace_content_anon', 'tease_prepend_content', 'tease_prepend_content_anon',
                                'tease_append_content', 'tease_append_content_anon', 'tease_prepend_name', 'tease_prepend_name_anon',
                                'tease_append_name', 'tease_append_name_anon', 'tease_replace_excerpt', 'tease_replace_excerpt_anon',
                                'tease_prepend_excerpt', 'tease_prepend_excerpt_anon', 'tease_append_excerpt', 'tease_append_excerpt_anon' ), */
        ];

        $key = 'teaser';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;
        return $sections;
    }

    function optionsPreUI()
    {
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 

        if (presspermit()->getOption('display_hints')) :
            ?>
            <div class="pp-optionhint">
                <?php
                printf(__('Settings for replacing unreadable content with teaser text, provided by the %s module.', 'presspermit'), __('Teaser', 'ppe'));
                ?>
            </div>
        <?php
        endif;
    }

    function optionsUI()
    {
        $pp = presspermit();

        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 
        $tab = 'teaser';

        $section = 'rss_feeds';                                // --- RSS FEEDS SECTION ---
        if (!empty($ui->form_options[$tab][$section])) : ?>
            <tr>
                <th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
                <td>

                    <?php if (in_array('rss_private_feed_mode', $ui->form_options[$tab][$section], true)) :
                        $ui->all_options[] = 'rss_private_feed_mode';

                        //echo ( _ x( 'Display', 'prefix to RSS content dropdown', 'pptx' ) );
                        echo(__('Display', 'pptx'));
                        echo '&nbsp;<select name="rss_private_feed_mode" id="rss_private_feed_mode">';

                        $captions = ['full_content' => __("Full Content", 'pptx'), 'excerpt_only' => __("Excerpt Only", 'pptx'), 'title_only' => __("Title Only", 'pptx')];
                        foreach ($captions as $key => $value) {
                            $selected = ($ui->getOption('rss_private_feed_mode') == $key) ? 'selected="selected"' : '';
                            echo "\n\t<option value='$key' " . $selected . ">$captions[$key]</option>";
                        }
                        echo '</select>&nbsp;';
                        //echo ( _ x( 'for readable private posts', 'suffix to RSS content dropdown', 'pptx' ) );
                        echo(__('for readable private posts', 'pptx'));
                        echo "<br />";
                    endif;
                    ?>

                    <?php if (in_array('rss_nonprivate_feed_mode', $ui->form_options[$tab][$section], true)) :
                        $ui->all_options[] = 'rss_nonprivate_feed_mode';
                        //echo ( _ x( 'Display', 'prefix to RSS content dropdown', 'pptx' ) );
                        echo(__('Display', 'pptx'));
                        echo '&nbsp;<select name="rss_nonprivate_feed_mode" id="rss_nonprivate_feed_mode">';

                        $captions = ['full_content' => __("Full Content", 'pptx'), 'excerpt_only' => __("Excerpt Only", 'pptx'), 'title_only' => __("Title Only", 'pptx')];
                        foreach ($captions as $key => $value) {
                            $selected = ($ui->getOption('rss_nonprivate_feed_mode') == $key) ? 'selected="selected"' : '';
                            echo "\n\t<option value='$key' " . $selected . ">$captions[$key]</option>";
                        }
                        echo '</select>&nbsp;';
                        //echo ( _ x( 'for readable non-private posts', 'suffix to RSS content dropdown', 'pptx' ) );
                        echo(__('for readable non-private posts', 'pptx'));

                        echo "<br />";
                        ?>
                        <span class="pp-subtext">
                        <?php if ($ui->display_hints) _e('Since some browsers will cache feeds without regard to user login, block RSS content even for qualified users.', 'pptx'); ?>
                    </span>
                        <br/><br/>
                    <?php endif; ?>

                    <?php if (in_array('feed_teaser', $ui->form_options[$tab][$section], true)) :
                        $id = 'feed_teaser';
                        $ui->all_options[] = $id;
                        $val = htmlspecialchars($ui->getOption($id));

                        echo "<label for='$id'>";
                        _e('Feed Replacement Text (use %permalink% for post URL)', 'pptx');
                        echo "<br /><textarea name='$id' cols=60 rows=1 id='$id'>$val</textarea>";
                        echo "</label>";
                    endif;
                    ?>

                </td>
            </tr>
        <?php
        endif; // any options accessable in this section


        $section = 'custom_fields';                                // --- CUSTOM FIELDS SECTION ---
        if (!empty($ui->form_options[$tab][$section])) : ?>
            <tr>
                <th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
                <td>

                    <?php
                    if (in_array('teaser_hide_thumbnail', $ui->form_options[$tab][$section], true)) {
                        $hint = '';
                        $ui->optionCheckbox('teaser_hide_thumbnail', $tab, $section, $hint, '');
                    }
                    ?>

                </td>
            </tr>
        <?php
        endif; // any options accessable in this section


// --- HIDDEN CONTENT TEASER SECTION ---
        $section = 'hidden_content_teaser';

        $default_options = apply_filters('presspermit_teaser_default_options', []);

        if (!empty($ui->form_options[$tab][$section]) && in_array('post_teaser_enabled', $ui->form_options[$tab][$section], true)) : ?>
            <tr>
            <th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?></th>
            <td>
            <?php
            $option_basename = 'post_teaser_enabled';

            $ui->all_options[] = $option_basename;

            $do_teaser = ['post' => $ui->getOption($option_basename)];

            $opt_available = array_fill_keys($pp->getEnabledPostTypes(), 0);
            $no_tease_types = Teaser::noTeaseTypes();

            $option_use_teaser = 'tease_post_types';
            $ui->all_otype_options[] = $option_use_teaser;
            $opt_vals = $ui->getOptionArray($option_use_teaser);
            $use_teaser = array_diff_key(array_merge($opt_available, $default_options[$option_use_teaser], $opt_vals), $no_tease_types);

            $option_logged_only = 'tease_logged_only';
            $ui->all_otype_options[] = $option_logged_only;
            $opt_vals = $ui->getOptionArray($option_logged_only);
            $logged_only = array_diff_key(array_merge($opt_available, $default_options[$option_logged_only], $opt_vals), $no_tease_types);

            $option_hide_private = 'tease_public_posts_only';
            $ui->all_otype_options[] = $option_hide_private;
            $opt_vals = $ui->getOptionArray($option_hide_private);
            $hide_private = array_diff_key(array_merge($opt_available, $default_options[$option_hide_private], $opt_vals), $no_tease_types);

            $option_direct_only = 'tease_direct_access_only';
            $ui->all_otype_options[] = $option_direct_only;
            $opt_vals = $ui->getOptionArray($option_direct_only);
            $direct_only = array_diff_key(array_merge($opt_available, $default_options[$option_direct_only], $opt_vals), $no_tease_types);

            // loop through each source that has a default do_teaser setting defined
            foreach ($do_teaser as $source_name => $val) {
                $id = $option_basename; // . '-' . $source_name;

                echo '<div class="agp-vspaced_input">';
                echo "<label for='$id'>";
                $checked = ($val) ? ' checked="checked"' : '';
                echo "<input name='$id' type='checkbox' id='$id' value='1' $checked /> ";

                _e('Enable teaser', 'pptx');
                echo('</label><br />');

                $css_display = ($do_teaser[$source_name]) ? 'block' : 'none';

                $style = "style='margin-left: 1em;'";
                echo "<div id='teaser_usage-$source_name' style='display:$css_display;'><table>";

                $use_teaser = array_intersect_key($use_teaser, array_fill_keys($pp->getEnabledPostTypes(), true));
                $use_teaser = $pp->admin()->orderTypes($use_teaser, ['item_type' => 'post']);

                // loop through each object type (for current source) to provide a use_teaser checkbox
                foreach ($use_teaser as $object_type => $teaser_setting) {
                    if ($type_obj = get_post_type_object($object_type))
                        $item_label_singular = $type_obj->labels->singular_name;
                    else
                        $item_label_singular = $object_type;

                    if (is_bool($teaser_setting) || is_numeric($teaser_setting))
                        $teaser_setting = intval($teaser_setting);

                    $id = $option_use_teaser . '-' . $object_type;
                    $name = "tease_post_types[$object_type]";

                    echo '<tr><td>';

                    echo "<label style='margin-left: 2em;'>";
                    printf(__("%s:", 'pptx'), $item_label_singular);
                    echo '</label>';

                    echo "</td><td><select name='$name' id='$id'>";
                    $num_chars = (defined('PP_TEASER_NUM_CHARS')) ? PP_TEASER_NUM_CHARS : 50;
                    $captions = apply_filters('presspermit_teaser_enable_options', [0 => __("no teaser", 'pptx'), 1 => __("fixed teaser (specified below)", 'pptx'), 'excerpt' => __("excerpt as teaser", 'pptx'), 'more' => __("excerpt or pre-more as teaser", 'pptx'), 'x_chars' => sprintf(__("excerpt, pre-more or first %s chars", 'pptx'), $num_chars)], $object_type, $teaser_setting);

                    foreach ($captions as $teaser_option_val => $teaser_caption) {
                        $selected = ($teaser_setting === $teaser_option_val) ? 'selected="selected"' : '';
                        echo "\n\t<option value='$teaser_option_val' $selected>$teaser_caption</option>";
                    }
                    echo '</select>';
                    echo "<span style='display:none'>$object_type</span>";
                    echo '</td></tr>';

                    // Checkbox option to skip teaser for anonymous users
                    $id = $option_logged_only . '-' . $object_type;
                    $name = "tease_logged_only[$object_type]";
                    $display = ($teaser_setting) ? '' : "style='display:none'";
                    echo "<tr class='teaser_vspace' $display><td></td><td><span>";
                    //echo( _ x( 'for:', 'teaser: anonymous, logged or both', 'pptx') );
                    echo(__('for:', 'pptx'));
                    echo "&nbsp;&nbsp;<label for='{$id}_logged'>";
                    $checked = (!empty($logged_only[$object_type]) && 'anon' == $logged_only[$object_type]) ? ' checked="checked"' : '';
                    echo "<input name='$name' type='radio' id='{$id}_logged' value='anon' $checked />";
                    echo "";
                    _e("anonymous", 'pptx');
                    echo '</label></span>';

                    // Checkbox option to skip teaser for logged users
                    echo "<span style='margin-left: 1em'><label for='{$id}_anon'>";
                    $checked = (!empty($logged_only[$object_type]) && 'anon' != $logged_only[$object_type]) ? ' checked="checked"' : '';
                    echo "<input name='$name' type='radio' id='{$id}_anon' value='1' $checked />";
                    echo "";
                    _e("logged", 'pptx');
                    echo '</label></span>';

                    // Checkbox option to do teaser for BOTH logged and anon users
                    echo "<span style='margin-left: 1em'><label for='{$id}_all'>";
                    $checked = (empty($logged_only[$object_type])) ? ' checked="checked"' : '';
                    echo "<input name='$name' type='radio' id='{$id}_all' value='0' $checked />";
                    echo "";
                    _e("both", 'pptx');
                    echo '</label></span>';

                    do_action('presspermit_teaser_type_row', $object_type, $teaser_setting);

                    echo('</td></tr>');
                }
                echo '</table><div>';

                if (empty($displayed_teaser_caption)) {
                    if ($ui->display_hints) {
                        echo '<span class="pp-subtext pp-options-indent">';
                        _e('If content is unreadable, display replacement text instead of hiding it completely.', 'pptx');
                        echo '</span><br />';
                        echo '<span class="pp-subtext pp-options-indent">';
                        _e('<strong>Note:</strong> the prefix and suffix settings below will always be applied unless the teaser mode is "no teaser".', 'pptx');
                        echo '</span>';
                    }

                    $displayed_teaser_caption = true;
                }

                echo '</div></div>'; // teaser_usage-post

                // provide hide private (instead of teasing) checkboxes for each pertinent object type
                echo '<br />';

                $display_style = (array_diff($use_teaser, [0])) ? '' : "style='display:none;'";
                echo "<div id='teaser-pvt-$source_name' $display_style>";

                $type_obj = get_post_type_object('post');
                $type_caption = $type_obj->labels->name;

                echo '<span>';
                printf(__("Tease on Direct Access Only", 'pptx'), $type_caption); // back compat for existing translations
                echo '</span><br />';

                $direct_only = array_intersect_key($direct_only, array_fill_keys($pp->getEnabledPostTypes(), true));
                $direct_only = $pp->admin()->orderTypes($direct_only, ['item_type' => 'post']);

                echo '<div style="margin-left:2em">';
                foreach ($direct_only as $object_type => $teaser_setting) {
                    $id = $option_direct_only . '-' . $object_type;
                    $name = "tease_direct_access_only[$object_type]";

                    if ($type_obj = get_post_type_object($object_type))
                        $item_label = $type_obj->labels->name;
                    else
                        $item_label = $object_type;

                    $display = (!empty($use_teaser[$object_type])) ? '' : "style='display:none'";
                    $teaser_direct_only_types = apply_filters('presspermit_teaser_direct_only_types', []);
                    if (!empty($teaser_direct_only_types[$object_type]))
                        $checked = ' checked="checked" disabled="disabled"';
                    else
                        $checked = ($teaser_setting) ? ' checked="checked"' : '';

                    echo "<input name='$name' type='hidden' value='0' /> ";
                    echo "<div $display><label for='$id'>";
                    echo "<input name='$name' type='checkbox' id='$id' value='1' $checked /> ";

                    echo($item_label);
                    echo('</label></div>');
                }

                echo '</div>';

                echo '<span class="pp-subtext pp-options-indent">';
                if ($ui->display_hints) _e('Keep unreadable content hidden in the blogroll and link lists, but show the teaser on direct access attempts.', 'pptx');
                echo '</span><br /><br />';

                echo '<span>';
                printf(__("Hide Private Posts (instead of teasing)", 'pptx'), $type_caption); // back compat for existing translations
                echo '</span><br />';

                $hide_private = array_intersect_key($hide_private, array_fill_keys($pp->getEnabledPostTypes(), true));
                $hide_private = $pp->admin()->orderTypes($hide_private, ['item_type' => 'post']);

                echo '<div style="margin-left: 2em">';
                foreach ($hide_private as $object_type => $teaser_setting) {
                    $id = $option_hide_private . '-' . $object_type;
                    $name = "tease_public_posts_only[$object_type]";

                    if ($type_obj = get_post_type_object($object_type))
                        $item_label = $type_obj->labels->name;
                    else
                        $item_label = $object_type;

                    $display = (!empty($use_teaser[$object_type])) ? '' : "style='display:none'";
                    $teaser_hide_private_types = apply_filters('presspermit_teaser_hide_private_types', []);
                    if (!empty($teaser_hide_private_types[$object_type]))
                        $checked = ' checked="checked" disabled="disabled"';
                    else
                        $checked = ($teaser_setting) ? ' checked="checked"' : '';

                    echo "<input name='$name' type='hidden' value='0' /> ";
                    echo "<div $display><label for='$id'>";
                    echo "<input name='$name' type='checkbox' id='$id' value='1' $checked /> ";

                    echo($item_label);
                    echo('</label></div>');
                }
                echo '</div>';

                echo '<span class="pp-subtext pp-options-indent">';
                if ($ui->display_hints) _e('Hide unreadable private posts, but show a teaser for posts which are unreadable due to regular Privacy or Role Restrictions.', 'pptx');
                echo '</span>';

                //if ( defined('PRESSPERMIT_STATUSES_VERSION') ) {
                echo '<div class="pp-options-indent" style="margin-top:1em">';
                $hint = '';
                $ui->optionCheckbox('teaser_hide_custom_private_only', $tab, $section, $hint, '');
                echo '</div>';
                //} else {
                // echo "<input name='teaser_hide_custom_private_only' type='hidden' value='0' />";
                //}

                echo '</div>'; // teaser-pvt-post


                echo '<br /><div><span>';
                printf(__("Redirect Page", 'pptx'));
                ?>
                </span><br/>

                <?php
                $id = "teaser_redirect_anon_slug";
                $ui->all_options[] = $id;
                $_setting = $pp->getOption($id);
                ?>
                <table class="pp-teaser-redirect-table pp-form-table pp-options-table" style="margin-left:2em">
                    <tr>
                        <td>
                            <label for='<?php echo $id; ?>'>
                                <?php _e('Page slug (anonymous users):', 'pptx');
                                ?>
                        </td>
                        <td>
                            <?php
                            echo "<input name='$id' type='text' id='$id' value='$_setting' /> ";
                            ?>
                            </label>
                        </td>
                    </tr>

                    <?php
                    $id = "teaser_redirect_slug";
                    $ui->all_options[] = $id;
                    $_setting = $pp->getOption($id);
                    ?>
                    <tr>
                        <td>
                            <label for='<?php echo $id; ?>'>
                                <?php _e('Page slug (logged users):', 'pptx');
                                ?>
                        </td>
                        <td>
                            <?php
                            echo "<input name='$id' type='text' id='$id' value='$_setting' /> ";
                            ?>
                            </label>
                        </td>
                    </tr>
                </table>
                <?php
                echo '</div><div style="margin-bottom:15px"><span class="pp-subtext pp-options-indent">';
                if ($ui->display_hints) _e('Optionally, specify page slug or "[login]" for redirect when teased content is accessed.', 'pptx');
                echo '</span></div>';
                ?>

                <?php
                echo '<br /><div><span>';
                printf(__("Hide Unreadable Links (instead of teasing)", 'pptx')); // back compat for existing translations
                ?>
                </span><br/>
                <table id="pptx-options-teaser-links" class="pp-form-table pp-options-table" style="margin-left:2em">
                    <tbody>

                    <?php
                    $id = "teaser_hide_links_type";
                    $ui->all_options[] = $id;
                    $_setting = $pp->getOption($id);
                    ?>
                    <tr class="teaser_vspace">
                        <td style="vertical-align:top">
                            <div>
                                <label for='<?php echo $id; ?>'>
                                    <?php _e('Post Types:', 'pptx');
                                    ?>
                        </td>
                        <td>
                            <?php
                            echo "<input name='$id' type='text' id='$id' value='$_setting' style='width:550px' /> ";
                            ?>

                            <?php
                            echo '</div><div style="margin-bottom:15px"><span class="pp-subtext pp-options-in-dent">';
                            if ($ui->display_hints) _e('Unreadable items of these comma-separated types will have nav menu item hidden.', 'pptx');
                            echo '</span></div>';
                            ?>
                        </td>
                    </tr>

                    <?php
                    $id = "teaser_hide_links_taxonomy";
                    $ui->all_options[] = $id;
                    $_setting = $pp->getOption($id);
                    ?>
                    <tr class="teaser_vspace">
                        <td style="vertical-align:top">
                            <label for='<?php echo $id; ?>'><span style="width:300px">
                                            <?php _e('Taxonomy:', 'pptx');
                                            $taxonomies = get_taxonomies(['public' => true], 'object');
                                            $taxonomies = $pp->admin()->orderTypes($taxonomies);
                                            ?>
                        </td>
                        <td>
                            <select name='<?php echo $id; ?>' id='<?php echo $id; ?>'/>
                            <option value=''><?php _e('(none)', 'presspermit'); ?></option>
                            <?php
                            foreach ($taxonomies as $taxonomy => $tx) {
                                $selected = ($_setting === $taxonomy) ? 'selected="selected"' : '';
                                echo "\n\t<option value='$taxonomy' $selected>{$tx->labels->singular_name}</option>";
                            }
                            ?>
                            </label></select>
                        </td>
                    </tr>

                    <?php
                    $id = "teaser_hide_links_term";
                    $ui->all_options[] = $id;
                    $_setting = $pp->getOption($id);
                    ?>
                    <tr class="teaser_vspace">
                        <td style="vertical-align:top;padding-top:10px">
                            <div>
                                <label for='<?php echo $id; ?>'>
                                    <?php _e('Term IDs:', 'pptx');
                                    ?>
                        </td>
                        <td>
                            <?php
                            echo "<input name='$id' type='text' id='$id' value='$_setting' style='margin-top:10px;width:600px' /> ";
                            ?>

                            <?php
                            echo '</div><div><span class="pp-subtext pp-options-in-dent">';
                            if ($ui->display_hints) _e('Unreadable items associated with these comma-separated terms will have nav menu item hidden.', 'pptx');
                            echo '</span></div>';
                            ?>

                        </td>
                    </tr>

                    </tbody>
                </table>

                <?php

                echo '</div>';
                ?>
                </div>
                <?php
                // now draw the teaser replacement / prefix / suffix input boxes
                $user_suffixes = ['_anon', ''];
                $item_actions = [
                    'name' => ['prepend', 'append'],
                    'content' => ['replace', 'prepend', 'append'],
                    'excerpt' => ['replace', 'prepend', 'append']
                ];

                $items_display = ['name' => __('name', 'pptx'), 'content' => __('content', 'pptx'), 'excerpt' => __('excerpt', 'pptx')];
                $actions_display = ['replace' => __('replace with:', 'pptx'), 'prepend' => __('prefix with:', 'pptx'), 'append' => __('suffix with:', 'pptx')];

                // first determine all object types
                foreach ($user_suffixes as $anon) {
                    foreach ($item_actions as $item => $actions) {
                        foreach ($actions as $action) {
                            $ui->all_options[] = "tease_{$action}_{$item}{$anon}";
                        }
                    }
                }

                $types = [''];

                $css_display = ($do_teaser[$source_name]) ? 'block' : 'none';
                echo "<div id='teaserdef-$source_name' style='display:$css_display; margin-top: 2em;'>";

                // separate input boxes to specify teasers for anon users and unpermitted logged users
                foreach ($user_suffixes as $anon) {
                    $user_descript = ($anon) ? __('anonymous users', 'pptx') : __('logged users', 'pptx');

                    echo '<strong>';
                    //printf( __('%1$s Teaser Text (%2$s):', 'pptx'), $item_label_singular, $user_descript );
                    printf(__('Teaser Text (%s):', 'pptx'), $user_descript);
                    echo '</strong>';
                    echo('<ul class="pp-textentries">');

                    // items are name, content, excerpt
                    foreach ($item_actions as $item => $actions) {
                        echo('<li>' . $items_display[$item] . ':');
                        echo '<table><tbody>';

                        // actions are prepend / append / replace
                        foreach ($actions as $action) {
                            $option_name = "tease_{$action}_{$item}{$anon}";
                            if (!$opt_val = $pp->getOption($option_name))
                                $opt_val = '';

                            $ui->all_options[] = $option_name;

                            $id = $option_name;
                            $name = $option_name;

                            echo "<tr><td class='td-label'><label for='$id'>";
                            echo($actions_display[$action]);
                            echo '</label>';

                            if (('content' == $item) && ('replace' == $action)) {
                                echo '<br /><div class="pp-gray pp-add-login-form" style="padding-left: 15px;"><a href="#">[login_form]</a></div>';
                            }
                            ?>
                            </td>
                            <td>
                                <?php if ('content' == $item) : ?>
                                    <textarea style="width:100%" name="<?php echo($name); ?>"
                                              id="<?php echo($id); ?>"><?php echo($opt_val); ?></textarea>
                                <?php else : ?>
                                    <input name="<?php echo($name); ?>" type="text" id="<?php echo($id); ?>"
                                           value="<?php echo($opt_val); ?>"/>
                                <?php endif; ?>

                            </td>
                            </tr>
                            <?php
                        } // end foreach actions

                        echo('</tbody></table></li>');
                    } // end foreach item_actions

                    echo("</ul><br />");
                } // end foreach user_suffixes
                ?>

                <strong><?php _e('Type-Specific Teaser Text', 'pptx'); ?></strong>

                <ul class="pp-textentries">
                    <li>
                        <div class="pp_teaser_filter_notes" style="max-width:750px;padding:10px">
                            <?php
                            _e('<strong>Copy</strong> the following code into your theme&apos;s <strong>functions.php</strong> file (or some other file which is always executed and not auto-updated). You will need to adjust the &apos;my_custom_type&apos; identifier and text as desired:', 'pptx');
                            ?>
                            <textarea rows='20' cols='150' readonly='readonly' style="margin-top:5px">
                        add_filter( 'presspermit_teaser_text', 'my_custom_teaser_text', 10, 5 );

                        /*
                         * adjustment_type: replace, prefix or suffix
                         * post_part: content, excerpt or name
                        */
                        function my_custom_teaser_text( $text, $adjustment_type, $post_part, $post_type, $is_anonymous ) {
                            switch ( $post_type ) {
                                case 'page':
                                    if ( ( 'content' == $post_part ) && ( 'replace' == $adjustment_type ) ) {
                                        if ( $is_anonymous ) { // note: if you put a link or other html tags in the text, be sure to use single quotes
                                            $text = "Sorry, you don't have access to this page. Please log in or contact an administrator.";
                                        } else {
                                            $text = "Sorry, this page requires additional permissions. Please contact an administrator for help.";
                                        }
                                    }

                                    break;

                                case 'my_custom_type':
                                    if ( ( 'content' == $post_part ) && ( 'replace' == $adjustment_type ) ) {
                                        if ( $is_anonymous ) {  // note: if you put a link or other html tags in the text, be sure to use single quotes
                                            $text = "Sorry, you don't have access to this custom content. Please log in or contact an administrator.";
                                        } else {
                                            $text = "Sorry, this custom content requires additional permissions. Please contact an administrator for help.";
                                        }
                                    }

                                    break;
                            }

                            return $text;
                        }
                                            </textarea>
                        </div>
                    </li>
                </ul>


                <?php
                if (is_multisite()) : ?>
                    <br/>
                    <strong><?php _e('Teaser Settings for Networks', 'pptx'); ?></strong>

                    <ul class="pp-textentries">
                        <li>
                            <div class="pp_teaser_filter_notes" style="max-width:750px;padding:10px">
                                <?php
                                _e('To modify default settings network-wide, <strong>copy</strong> the following code into your theme&apos;s <strong>functions.php</strong> file (or some other file which is always executed and not auto-updated) and modify as desired:', 'presspermit');
                                ?>
                                <textarea rows='12' cols='150' readonly='readonly' style="margin-top:5px">
                                add_filter( 'presspermit_default_options', 'my_presspermit_default_options', 99 );

                                /*
                                 * def_options[option_name] = option_value
                                */
                                function my_presspermit_default_options( $def_options ) {
                                    // option name (array key) corresponds to name attributes of checkboxes, dropdowns and input boxes.  Modify as desired.

                                    return $def_options;
                                }
                                                    </textarea>
                            </div>

                            <div class="pp_teaser_filter_notes" style="max-width:750px;padding:10px">
                                <?php
                                _e('To force the value of a specific setting network-wide, <strong>copy</strong> the following code into your theme&apos;s <strong>functions.php</strong> file (or some other file which is always executed and not auto-updated) and modify as desired:', 'presspermit');
                                ?>
                                <textarea rows='12' cols='150' readonly='readonly' style="margin-top:5px">
                                add_filter( 'presspermit_default_options', 'my_presspermit_default_options', 99 );

                                /*
                                 * def_options[option_name] = option_value
                                */
                                function my_presspermit_default_options( $def_options ) {
                                    // option name (array key) corresponds to name attributes of checkboxes, dropdowns and input boxes.  Modify as desired.

                                    return $def_options;
                                }
                                                    </textarea>
                            </div>

                        </li>
                    </ul>
                <?php endif; ?>

                <?php

                echo '</div>';
            } // end foreach data source
            ?>
            </td>
            </tr>
        <?php
        endif; // any options accessable in this section

    } // end function optionsUI()
}

?>

<script type="text/javascript">
    /* <![CDATA[ */
    jQuery(document).ready(function ($) {
        $('#post_teaser_enabled').change(function()
        {
            if ($(this).is(':checked')) {
                $('#teaser_usage-post').show();
                $('#teaser-pvt-post').show();
                $('#teaserdef-post').show();
            } else {
                $('#teaser_usage-post').hide();
                $('#teaser-pvt-post').hide();
                $('#teaserdef-post').hide();
            }
        });

        $('#teaser_usage-post select[name!="topics_teaser"]').change(function()
        {
            var otype = $(this).next().html();

            if ($(this).val() != '0') {
                $(this).closest('tr').next('tr').show();
                $('#teaser-pvt-post').show();
                $('#tease_public_posts_only-' + otype).closest('div').show();
            } else {
                $(this).closest('tr').next('tr').hide();
                $('#tease_public_posts_only-' + otype).closest('div').hide();
            }

            if ($('#teaser-pvt-post input:visible').length)
                $('#teaser-pvt-post').show();
            else
                $('#teaser-pvt-post').hide();
        });

        $('div.pp-add-login-form a').click(function()
        {
            var e;
            if (e = $(this).closest('td').next().find('textarea')) {
                if (-1 == e.val().indexOf('[login_form]')) {
                    e.val(e.val() + '[login_form]');
                }
            }
            return false;
        });
    });
    /* ]]> */
</script>