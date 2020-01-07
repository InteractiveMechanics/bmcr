<?php
namespace PublishPress\Permissions\Statuses\UI;

class SettingsTabStatuses
{
    //var $advanced_enabled;

    function __construct()
    {
        //$this->advanced_enabled = presspermit()->getOption( 'advanced_options' );

        add_filter('presspermit_option_tabs', [$this, 'optionTabs'], 3);

        add_filter('presspermit_section_captions', [$this, 'sectionCaptions']);
        add_filter('presspermit_option_captions', [$this, 'optionCaptions']);
        add_filter('presspermit_option_sections', [$this, 'optionSections']);

        add_action('presspermit_statuses_options_pre_ui', [$this, 'statuses_options_pre_ui']);
        add_action('presspermit_statuses_options_ui', [$this, 'statuses_options_ui']);

        //add_action( 'presspermit_options_ui_insertion', [ $this, 'advanced_tab_options_ui' ], 5, 2 );  // hook for UI insertion on Settings > Advanced tab
        add_filter('presspermit_cap_descriptions', [$this, 'flt_cap_descriptions'], 5);  // priority 5 for ordering between PPS and PPCC additions in caps list
    }

    function optionTabs($tabs)
    {
        $tabs['statuses'] = __('Statuses', 'pps');
        return $tabs;
    }

    function sectionCaptions($sections)
    {
        $new = [
            'privacy' => __('Visibility', 'pps'),
        ];

        if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            $new['workflow'] = __('Workflow', 'pps');
        }

        $key = 'statuses';
        $sections[$key] = (isset($sections[$key])) ? array_merge($sections[$key], $new) : $new;

        return $sections;
    }

    function optionCaptions($captions)
    {
        //$captions['edit_form_custom_privacy'] = __('Selectively disable the Gutenberg Editor, to allow custom privacy settings');
        $captions['custom_privacy_edit_caps'] = __('Custom Visibility Statuses require status-specific editing capabilities', 'pps');
        $captions['draft_reading_exceptions'] = __('Drafts visible on front end if Reading Exception assigned', 'pps');

        if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            $captions['supplemental_cap_moderate_any'] = __('Supplemental Editor Role for "standard statuses" also grants capabilities for Workflow Statuses', 'pps');
            $captions['moderation_statuses_default_by_sequence'] = __('Publish button defaults to next workflow status (instead of highest permitted)', 'pps');
        }

        return $captions;
    }

    function optionSections($sections)
    {
        $new = [
            'privacy' => ['custom_privacy_edit_caps', 'draft_reading_exceptions'],
        ];

        if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            $new['workflow'] = ['supplemental_cap_moderate_any'];

            if (!defined('PPS_NATIVE_CUSTOM_STATI_DISABLED')) {
                $new['workflow'][] = 'moderation_statuses_default_by_sequence';
            }
        }

        $tab = 'statuses';
        $sections[$tab] = (isset($sections[$tab])) ? array_merge($sections[$tab], $new) : $new;

        return $sections;
    }

    function statuses_options_pre_ui()
    {
        if (presspermit()->getOption('display_hints')) :
            ?>
            <div class="pp-optionhint">
                <?php
                //printf( __( 'Add some caption here.', 'pps') );
                ?>
            </div>
        <?php
        endif;
    }

    function statuses_options_ui()
    {
        $ui = \PublishPress\Permissions\UI\SettingsAdmin::instance(); 
        $tab = 'statuses';

        $section = 'privacy';                       // --- PRIVACY STATUS SECTION ---
        if (!empty($ui->form_options[$tab][$section])) : ?>
            <tr>
                <th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?>

                    <div class="pp-statuses-other-config">
                        <h4><?php _e('Additional Configuration:', 'pps'); ?></h4>
                        <ul>
                            <li>
                                <a href="<?php echo admin_url('admin.php?page=presspermit-statuses&attrib_type=private'); ?>"><?php _e('Define Privacy Statuses', 'pps'); ?></a>
                            </li>

                        </ul>
                    </div>
                </th>

                <td>
                    <?php
                    if (!defined('PPS_NATIVE_CUSTOM_STATI_DISABLED')) {
                        $hint = __('Should pages with privacy status "premium" require set_pages_premium and edit_premium_pages capabilities (to be supplied by a supplemental status-specific Page Editor role)?', 'pps');
                        $args = (defined('PP_SUPPRESS_PRIVACY_EDIT_CAPS')) ? ['val' => 0, 'no_storage' => true, 'disabled' => true] : [];
                        $ui->optionCheckbox('custom_privacy_edit_caps', $tab, $section, $hint, '', $args);
                    }
                    ?>
                </td>
            </tr>
        <?php endif; // any options accessable in this section


        $section = 'workflow';                      // --- WORKFLOW STATUS SECTION ---
        if (!empty($ui->form_options[$tab][$section]) && defined('PRESSPERMIT_COLLAB_VERSION')) : ?>
            <tr>
                <th scope="row"><?php echo $ui->section_captions[$tab][$section]; ?>
                    <div class="pp-statuses-other-config">
                        <h4><?php _e('Additional Configuration:', 'pps'); ?></h4>
                        <ul>

                            <?php
                            if (PPS::publishpressStatusesActive()) : ?>
                                <li>
                                    <a href="<?php echo admin_url('admin.php?page=pp-modules-settings&module=pp-custom-status-settings'); ?>"><?php _e('Define Workflow Statuses', 'pps'); ?></a>
                                </li>

                            <?php elseif (PPS::publishpressStatusesActive('', ['skip_status_dropdown_check' => true])) : ?>
                                <li>
                                    <a href="<?php echo admin_url('admin.php?page=pp-modules-settings&module=pp-custom-status-settings'); ?>"><?php _e('Enable PublishPress Status Dropdown', 'pps'); ?></a>
                                </li>
                            <?php elseif (defined('PUBLISHPRESS_VERSION')) : ?>
                                <li>
                                    <a href="<?php echo admin_url('admin.php?page=pp-modules-settings&module=pp-modules-settings-settings#modules-wrapper'); ?>"><?php _e('Turn on PublishPress Statuses', 'pps'); ?></a>
                                </li>
                            <?php else : ?>
                                <li style="font-size:12px;font-weight:normal">
                                    <?php _e('Activate PublishPress', 'pps'); ?></a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $caption = (PPS::publishpressStatusesActive('', ['skip_status_dropdown_check' => true])) ? __('Workflow Order, Branching, Permissions', 'pps') : __('Define Workflow Statuses', 'pps');
                            ?>
                            <li>
                                <a href="<?php echo admin_url('admin.php?page=presspermit-statuses&attrib_type=moderation'); ?>"><?php echo $caption; ?></a>
                            </li>

                        </ul>
                    </div>
                </th>

                <td>
                    <?php
                    $hint = __('Note, this only applies if the role definition includes the pp_moderate_any capability', 'pps');
                    $ui->optionCheckbox('supplemental_cap_moderate_any', $tab, $section, $hint);
                    
                    if(!PWP::isBlockEditorActive()) {
                        $hint = sprintf(__('Workflow sequence and branching for pre-publication may be defined %shere%s', 'pps'), '<a href="' . admin_url('admin.php?page=presspermit-statuses&attrib_type=moderation') . '">', '</a>');
                        $ui->optionCheckbox('moderation_statuses_default_by_sequence', $tab, $section, $hint);
                    }

                    $ui->optionCheckbox('draft_reading_exceptions', $tab, $section);
                    ?>
                </td>
            </tr>
        <?php endif; // any options accessable in this section
    }

    function flt_cap_descriptions($pp_caps)
    {
        $pp_caps['pp_define_post_status'] = __('Create or edit custom Privacy or Workflow statuses', 'pps');
        $pp_caps['pp_define_moderation'] = __('Create or edit Publication Workflow statuses', 'pps');
        $pp_caps['pp_define_privacy'] = __('Create or edit custom Privacy statuses', 'pps');
        $pp_caps['set_posts_status'] = __('Pertains to assignment of a custom privacy or moderation status. This capability in a WP role enables PP to assign a type-specific supplemental role with custom capabilities such as "set_pages_approved"', 'pps');
        $pp_caps['pp_moderate_any'] = __('Editors can edit posts having a moderation status (i.e. Approved) without a supplemental status-specific role', 'pps');

        return $pp_caps;
    }
}
