<?php
namespace PublishPress\Permissions;

class StatusesHooks 
{
    function __construct() 
    {
        // This script executes on plugin load.
        //
        require_once(PRESSPERMIT_STATUSES_ABSPATH . '/db-config.php');

        add_filter('presspermit_statuses_default_options', [$this, 'flt_default_statuses_options']);

        add_action('presspermit_maintenance_triggers', [$this, 'actMaintenanceTriggers']);

        add_action('init', [$this, 'actRegistrations'], 46);
        add_action('init', [$this, 'act_post_stati_prep'], 48);  // StatusesHooksAdmin::act_process_conditions() follows at priority 49

        add_action('presspermit_pre_init', [$this, 'act_version_check']);
        add_action('presspermit_pre_init', [$this, 'act_forceDistinctPostCaps']);

        add_action('presspermit_register_role_attributes', [$this, 'act_register_role_attributes']);
        add_action('wp_loaded', [$this, 'act_late_registrations']);  // PublishPress compat

        add_action('presspermit_roles_defined', [$this, 'act_roles_defined']);

        add_action('presspermit_post_filters', [$this, 'act_load_capability_filters']);
        add_action('presspermit_enable_status_mapping', [$this, 'act_enable_status_mapping']);

        add_filter('presspermit_pattern_roles', [$this, 'fltPatternRoles']);
        add_filter('presspermit_pattern_role_caps', [$this, 'flt_default_rolecaps']);
        add_filter('presspermit_exclude_arbitrary_caps', [$this, 'fltExcludeArbitraryCaps']);

        //add_filter('use_block_editor_for_post_type', [$this, 'fltUseGutenberg'], 10, 2);

        add_filter('rest_user_query', [$this, 'flt_rest_user_query'], 20, 2);  // @todo: relocate?

        add_filter('get_terms', [$this, 'flt_publishpress_status_position'], 50, 4);

        add_filter('option_publishpress_custom_status_options', [$this, 'flt_enable_publishpress_statuses']);

        if (defined('REVISIONARY_VERSION')) {
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/Revisionary/CapabilityFilters.php');
            new Statuses\Revisionary\CapabilityFilters();
        }

        add_filter('presspermit_order_statuses', [$this, 'fltOrderStatuses'], 10, 2);

        add_filter('user_has_cap', [$this, 'fltPublishPostsContext'], 100, 3);

        add_action('rest_api_init', [$this, 'actRestInit'], 1);
    }

    function fltPublishPostsContext($wp_sitecaps, $orig_reqd_caps, $args)
    {
        $user = presspermit()->getUser();
        $args = (array)$args;

        $post_id = PWP::getPostID();

        if (($args[1] != $user->ID) || !$post_id) {
            return $wp_sitecaps;
        }

        // If we are crediting edit_others_posts capability based on ownership of edit_others_{$status}_posts, 
        // don't honor publish_posts except for own posts
        if ($_post = get_post($post_id)) {
            if ($user->ID != $_post->post_author) {
                if ($type_obj = get_post_type_object($_post->post_type)) {
                    if (isset($type_obj->cap->publish_posts) && ($type_obj->cap->publish_posts == $args[0])) {
                        if (isset($type_obj->cap->edit_others_posts) && empty($user->allcaps[$type_obj->cap->edit_others_posts])) {
                            unset($wp_sitecaps[$type_obj->cap->publish_posts]);
                        }
                    }
                }
            }
        }

        return $wp_sitecaps;
    }

    function actRestInit()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/RESTFields.php');
        Statuses\RESTFields::registerRESTFields();

        if (current_user_can('pp_administer_content')) {
            return;
        }

        register_post_status(
            '_pending', 
            [
                'label'                     => __('Pending Review'),
                'label_count'               => false,
                'exclude_from_search'       => true,
                'public'                    => false,
                'internal'                  => false,
                'protected'                 => true,
                'private'                   => false,
                'publicly_queryable'        => false,
                'show_in_admin_status_list' => false,
                'show_in_admin_all_list'    => false,
            ]
        );
    }

    function flt_default_statuses_options($def = [])
    {
        $new = [
            'edit_form_custom_privacy' => 'existing',
            'custom_privacy_edit_caps' => 1,
            'supplemental_cap_moderate_any' => 0,
            'moderation_statuses_default_by_sequence' => 0,
            'draft_reading_exceptions' => 0,
        ];

        return array_merge($def, $new);
    }

    function flt_enable_publishpress_statuses($pubp_options) {
        $pubp_options->enabled = 'on';

        if (PWP::isWP5()) {
            $pubp_options->always_show_dropdown = 'on';
        }

        return $pubp_options;
    }

    function fltUseGutenberg($enabled, $post_type)
    {
        /* Allow Gutenberg if PublishPress is inactive or the post(s) involved to not have a custom privacy status.
            
        Note that in this case, native PressPermit custom moderation statuses are disable in favor of PublishPress statuses
        */

        global $wp_post_statuses;

        if (!$enabled) {
            return false;  // this function only applies Gutenberg disable
        }

        if (empty($wp_post_statuses) || (defined('PPS_ENABLE_GUTENBERG') && PPS_ENABLE_GUTENBERG)) {
            return $enabled;  // filter applied inappropriately, or we are forcing Gutenberg for dev/test purposes
        }

        /*
        if (!defined('PUBLISHPRESS_VERSION') || version_compare(PUBLISHPRESS_VERSION, '1.19', '<')) {
            return false;
        }

        // Some conditions where we need to disable Gutenberg even if a modern PublishPress version is active:
        if (!PPS::publishpressStatusesActive($post_type)) {
            return false;
        }
        */

        /*
        // Graceful disabling of Gutenberg based on custom privacy status usage and administrative setting
        if ($custom_privacy_usage = presspermit()->getOption('edit_form_custom_privacy')) { // options: 'existing', 'all_private', 'always'
            $post_id = PWP::getPostID();

            if (('always' == $custom_privacy_usage) || ($post_id && ('existing' != $custom_privacy_usage))) {
                global $wp_post_statuses, $wpdb;

                // Ignore stored PublishPress statuses, even if PublishPress is deactivated.
                $ignore_status = $wpdb->get_col(
                    "SELECT t.slug FROM $wpdb->terms AS t"
                    . " INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id"
                    . " WHERE tt.taxonomy = 'post_status'"
                );

                // Ignore custom statuses defined by PressPermit that are not privacy statuses.
                foreach ($wp_post_statuses as $post_status => $status_obj) {
                    if (empty($status_obj->private)) {
                        $ignore_status[] = $post_status;
                    }
                }
            }

            if ('always' == $custom_privacy_usage) {
                return $enabled && !PPS::customStatusesEnabled($post_type, $ignore_status);

            } else {
                if ($post_id) {
                    // Custom Privacy is enabled, but still use Gutenberg for existing posts that don't have custom privacy

                    $disable = false;
                    if ($post_status = get_post_field('post_status', $post_id)) {
                        if ($post_status_obj = get_post_status_object($post_status)) {

                            $disable = ($post_status_obj->private && 'private' != $post_status) 
                            && (empty($post_status_obj->post_type) || in_array($post_type, $post_status_obj->post_type));

                            if ('existing' != $custom_privacy_usage) {
                                // Setting a post to normal privacy will throw it back to Classic Editor 
                                // so a custom privacy status can be set (if any are enabled for post type).
                                $disable = $disable || (('private' == $post_status) 
                                && PPS::customStatusesEnabled($post_type, $ignore_status));
                            }
                        }
                    }

                    return $enabled && !$disable;
                }  //else {
                    // Disable Gutenberg if any privacy statuses enabled for this post type  
                //    return $enabled && ! PPS::customStatusesEnabled( $post_type, $ignore_status );
                //}  
            }
        }
        */

        return $enabled;
    }

    function actMaintenanceTriggers()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/Triggers.php');
        new Statuses\Triggers();
    }

    // Register default custom stati; Additional labels in status registration
    function actRegistrations()
    {
        global $wp_post_statuses;

        // custom private stati
        register_post_status('member', [
            'label' => _x('Member', 'post'),
            'private' => true,
            'label_count' => _n_noop('Member <span class="count">(%s)</span>', 'Member <span class="count">(%s)</span>'),
            'pp_builtin' => true,
        ]);

        register_post_status('premium', [
            'label' => _x('Premium', 'post'),
            'private' => true,
            'label_count' => _n_noop('Premium <span class="count">(%s)</span>', 'Premium <span class="count">(%s)</span>'),
            'pp_builtin' => true,
        ]);

        register_post_status('staff', [
            'label' => _x('Staff', 'post'),
            'private' => true,
            'label_count' => _n_noop('Staff <span class="count">(%s)</span>', 'Staff <span class="count">(%s)</span>'),
            'pp_builtin' => true,
        ]);

        $custom_stati = (array)get_option('presspermit_custom_conditions_post_status');

        foreach ($custom_stati as $status => $status_args) {
            if (isset($wp_post_statuses[$status])) {
                foreach(['label', 'save_as_label', 'publish_label'] as $property) {
                    if (!empty($status_args[$property])) {
                        $wp_post_statuses[$status]->$property = $status_args[$property];
                    }
                }
            }
            
            if (!empty($status_args['moderation'])) {
                if (defined('PP_NO_MODERATION'))
                    continue;

                $status_args['protected'] = true;
            }

            if (!isset($wp_post_statuses[$status]) && $status && empty($status_args['publishpress']) 
            && !in_array($status, ['pitch', 'in-progress', 'assigned'])
            ) {
                register_post_status($status, $status_args);
            }

            if (!isset($wp_post_statuses[$status])) {
                continue;
            }

            if (!empty($status_args['post_type'])) {
                $wp_post_statuses[$status]->post_type = (array)$status_args['post_type'];
            }

            /*
            if ( empty( $wp_post_statuses[$status]->_builtin ) && empty( $status_args['private']) 
            && ! in_array( $status, [ 'draft', 'pending', 'future' ] ) 
            ) {
                $wp_post_statuses[$status]->pp_custom = true;
            }
            */
        }

        global $wp_post_statuses;

        // If PublishPress is active with Gutenberg, disable moderation statuses created by PressPermit
        if (defined('PUBLISHPRESS_VERSION') && version_compare(PUBLISHPRESS_VERSION, '1.19', '>=') 
        && PWP::isBlockEditorActive('', ['suppress_filter' => [$this, 'fltUseGutenberg']])
        ) {
            // If custom private stati are used, we need to disable Gutenberg for now
            if (!PPS::customStatiUsed(['ignore_moderation_statuses' => true])) {

                define('PPS_NATIVE_CUSTOM_STATI_DISABLED', true);

                // ... but, for now, also disable PP moderation statuses in favor of PublishPress statuses
                foreach (array_intersect_key($wp_post_statuses, $custom_stati) as $post_status => $status_obj) {
                    if (!in_array($post_status, ['draft', 'pending', 'future']) && empty($status_obj->_builtin)) {
                        unset($wp_post_statuses[$post_status]);
                    }
                }
            }
        }

    }

    function act_post_stati_prep()
    {
        global $wp_post_statuses;

        $pp = presspermit();

        // set default properties
        foreach (array_keys($wp_post_statuses) as $status) {
            if (!isset($wp_post_statuses[$status]->moderation))
                $wp_post_statuses[$status]->moderation = false;
        }

        // apply PP-stored status config
        // @todo: does this cause extra query because not included in presspermit_default_options array?
        if ($stati_post_types = (array)$pp->getOption('status_post_types')) {
            foreach ($stati_post_types as $status => $types) {
                if (isset($wp_post_statuses[$status])) {
                    $wp_post_statuses[$status]->post_type = $types;
                }
            }
        }

        if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            $status_cap_status = array_intersect_key((array)$pp->getOption('status_capability_status'), $wp_post_statuses);
            
            foreach ($status_cap_status as $status => $cap_status) {
                $wp_post_statuses[$status]->capability_status = $cap_status;
            }

            $stati_order = array_intersect_key((array)$pp->getOption('status_order'), $wp_post_statuses);
            foreach ($stati_order as $status => $order) {
                $wp_post_statuses[$status]->order = $order;
            }

            $stati_parent = array_intersect_key((array)$pp->getOption('status_parent'), $wp_post_statuses);
            foreach ($stati_parent as $status => $parent) {
                $wp_post_statuses[$status]->status_parent = $parent;
            }

            if (isset($wp_post_statuses['pending']) && !isset($wp_post_statuses['pending']->order))
                $wp_post_statuses['pending']->order = 10;

            if (isset($wp_post_statuses['approved']) && !isset($wp_post_statuses['approved']->order))
                $wp_post_statuses['approved']->order = $wp_post_statuses['pending']->order + 8;

            foreach (PWP::getPostStatuses(['moderation' => true]) as $status) {
                if (!isset($wp_post_statuses[$status]->status_parent)) {
                    $wp_post_statuses[$status]->status_parent = '';
                }

                if (!isset($wp_post_statuses[$status]->order)) {
                    $wp_post_statuses[$status]->order = ($wp_post_statuses[$status]->status_parent) 
                    ? 0 
                    : $wp_post_statuses['pending']->order + 4;

                    $wp_post_statuses[$status]->order = 0;
                }
            }
        }
    }

    function act_version_check()
    {
        $ver = get_option('pps_version');

        /*
        if (get_option('ppperm_added_cc_role_caps_10beta') && !get_option('ppperm_added_pps_role_caps_10beta')) {
            // clean up from dual use of ppperm_added_cc_role_caps_10beta flag by both PP Circles and PP Custom Post Statuses
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/Updated.php');
            Statuses\Updated::flag_cleanup();
        }
        */

        if (!empty($ver['version'])) {
            // These maintenance operations only apply when a previous version of PPCS was installed 
            if (version_compare(PRESSPERMIT_STATUSES_VERSION, $ver['version'], '!=')) {
                require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/Updated.php');
                new Statuses\Updated($ver['version']);
                update_option(
                    'pps_version', 
                    ['version' => PRESSPERMIT_STATUSES_VERSION, 'db_version' => PRESSPERMIT_STATUSES_DB_VERSION]
                );

                // pp_attributes table was not created in previous 2.7-beta versions
                $force_db_update = version_compare($ver['version'], '2.7-beta3', '<');
            }
        } else {
            // first execution after install
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/Updated.php');
            Statuses\Updated::populateRoles();
        }

        if (!empty($force_db_update) || empty($ver['db_version']) 
        || version_compare(PRESSPERMIT_STATUSES_DB_VERSION, $ver['db_version'], '!=')
        ) {
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/DB/DatabaseSetup.php');
            new Statuses\DB\DatabaseSetup($ver['db_version']);
            update_option('pps_version', ['version' => PRESSPERMIT_STATUSES_VERSION, 'db_version' => PRESSPERMIT_STATUSES_DB_VERSION]);
        }
    }

    function act_forceDistinctPostCaps()
    {
        global $wp_post_types;

        $pp = presspermit();

        $generic_caps = ['post' => ['set_posts_status' => 'set_posts_status'], 'page' => ['set_posts_status' => 'set_posts_status']];

        // post types which are enabled for PP filtering must have distinct type-related cap definitions
        foreach (array_intersect(get_post_types(['public' => true], 'names'), $pp->getEnabledPostTypes()) as $post_type) {
            if ('post' == $post_type) {
                $type_caps['set_posts_status'] = 'set_posts_status';
            } else {
                $type_caps['set_posts_status'] = str_replace('_post', "_$post_type", 'set_posts_status');
            }

            $wp_post_types[$post_type]->cap = (object)array_merge((array)$wp_post_types[$post_type]->cap, $type_caps);

            $pp->capDefs()->all_type_caps = array_merge($pp->capDefs()->all_type_caps, array_fill_keys($type_caps, true));

            foreach (PWP::getPostStatuses(['moderation' => true, 'post_type' => $post_type]) as $status_name) {
                $cap_property = "set_{$status_name}_posts";
                $wp_post_types[$post_type]->cap->$cap_property = str_replace("_posts", "_$post_type", $cap_property);
            }
        }
    }

    function act_load_capability_filters()
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/CapabilityFilters.php');
        Statuses\CapabilityFilters::instance();
    }

    function act_enable_status_mapping($enable)
    {
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/CapabilityFilters.php');

         // for perf, instead of removing/adding 'map_meta_cap' filter here
        Statuses\CapabilityFilters::instance()->do_status_cap_map = $enable;
    }

    // For optimal flexibility with custom moderation stati (including PublishPress Statuses), dynamically insert a Submitter role 
    // containing the 'set_posts_status' capability.
    //
    // With default Contributor rolecaps, a "Page Contributor - Assigned" role enables the user to edit their own pages 
    // which have been set to assigned status.  
    //
    // "Page Submitter - Assigned" role enables setting their other pages to the Approved status
    //
    // These supplemental roles may be assigned individually or in conjunction
    // Note that the set_posts_status capability is granted implicitly for the 'pending' status, 
    // even if custom capabilities are enabled.
    function flt_default_rolecaps($caps)
    {
        if (defined('PRESSPERMIT_COLLAB_VERSION') && !isset($caps['submitter'])) {
            $caps['submitter'] = array_fill_keys(['read', 'set_posts_status'], true);
        }

        return $caps;
    }

    function fltPatternRoles($roles)
    {
        if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            if (!isset($roles['submitter']))
                $roles['submitter'] = (object)[];

            if (!isset($roles['submitter']->labels))
                $roles['submitter']->labels = (object)[
                    'name' => __('Submitters', 'presspermit'), 
                    'singular_name' => __('Submitter', 'presspermit')
                ];
        }

        return $roles;
    }

    function act_register_role_attributes()
    {
        $attributes = PPS::attributes();

        do_action('presspermit_status_registrations');

        // Restriction of read access will be accomplished by post status setting (either private or a custom status registered with private=true) 
        //
        // force_visibility attribute does not impose condition caps, but affects the post_status of published posts.
        PPS::registerAttribute(
            'force_visibility', 
            'post',
            [
                'label' => __('Force Visibility', 'pps'), 
                'default' => 'none', 
                'suppress_item_edit_ui' => ['object' => true]
            ]
        );

        // note: post_status is NOT stored to the attributes table
        PPS::registerAttribute('post_status', 'post', ['label' => __('Post Status', 'pps')]);

        if (!defined('PPS_CUSTOM_PRIVACY_EDIT_CAPS')) {
            define('PPS_CUSTOM_PRIVACY_EDIT_CAPS', presspermit()->getOption('custom_privacy_edit_caps'));
        }

        // register each custom post status as an attribute condition with mapped caps
        PPS::registerConditions(get_post_stati([], 'object'));

        if (is_user_logged_in() && presspermit()->getOption('draft_reading_exceptions') 
        && (PWP::isFront() || (defined('REST_REQUEST') && REST_REQUEST))
        ) {
            global $wp_post_statuses;
            $wp_post_statuses['draft']->private = true;
            $wp_post_statuses['draft']->protected = false;

            $status_obj = get_post_status_object('draft');

            PPS::registerCondition('post_status', 'draft', [
                'label' => $status_obj->label,
                'metacap_map' => ['read_post' => 'read_draft_posts'],
            ]);
        }

        $attributes->process_status_caps();
    }

    // late registration of statuses for PublishPress compat (PublishPress hooks to 'init' action at priority 1000)
    function act_late_registrations()
    {
        global $wp_post_statuses, $pagenow;

        $pp = presspermit();

        // @todo: does this cause extra query because not included in presspermit_default_options array?
        if ($stati_post_types = (array)$pp->getOption('status_post_types')) {
            foreach ($stati_post_types as $status => $types) {
                if (isset($wp_post_statuses[$status])) {
                    $wp_post_statuses[$status]->post_type = $types;
                }
            }
        }

        // late execution to ensure pending and approved status, if present, have appropriate default order
        if (defined('PRESSPERMIT_COLLAB_VERSION')) {
            $status_cap_status = array_intersect_key((array)$pp->getOption('status_capability_status'), $wp_post_statuses);
            foreach ($status_cap_status as $status => $cap_status) {
                $wp_post_statuses[$status]->capability_status = $cap_status;
            }

            $stati_order = array_intersect_key((array)$pp->getOption('status_order'), $wp_post_statuses);
            foreach ($stati_order as $status => $order) {
                $wp_post_statuses[$status]->order = $order;
            }

            if (isset($wp_post_statuses['pending']) && !isset($wp_post_statuses['pending']->order)) {
                $wp_post_statuses['pending']->order = 10;
            }

            if (isset($wp_post_statuses['approved']) && !isset($wp_post_statuses['approved']->order))
                $wp_post_statuses['approved']->order = $wp_post_statuses['pending']->order + 8;

            foreach (PWP::getPostStatuses(['moderation' => true]) as $status) {
                if (empty($wp_post_statuses[$status]->order)) {
                    //$wp_post_statuses[$status]->order = $wp_post_statuses['pending']->order + 4;
                    $wp_post_statuses[$status]->order = 0;
                }
            }

            $stati_parent = array_intersect_key((array)$pp->getOption('status_parent'), $wp_post_statuses);
            foreach ($stati_parent as $status => $parent) {
                $wp_post_statuses[$status]->status_parent = $parent;
            }
        }

        // PublishPress compat (mark EF stati as moderation)
        if ((defined('PUBLISHPRESS_VERSION') && class_exists('PP_Custom_Status')) 
        && defined('PRESSPERMIT_COLLAB_VERSION') && !defined('PP_NO_MODERATION')
        ) {
            global $wp_post_statuses;

            $ef_stati = [];
            $ef_terms = get_terms('post_status', ['hide_empty' => false]);

            foreach ($ef_terms as $term) {
                if (is_object($term))
                    $ef_stati[$term->slug] = true; // $term->position;
            }

            global $wp_post_statuses;

            $default_status_order = PPS::defaultStatusOrder();

            foreach (get_post_stati(['public' => false, 'private' => false], 'names') as $status) {
                if (array_key_exists($status, $ef_stati) && !in_array($status, ['draft', 'pending'])) {
                    $wp_post_statuses[$status]->moderation = true;
                    $wp_post_statuses[$status]->pp_custom = true;

                    if (!isset($wp_post_statuses[$status]->order) && isset($default_status_order[$status])) {
                        $wp_post_statuses[$status]->order = $default_status_order[$status];
                    }
                }
            }
        }

        $custom_stati = array_intersect_key((array)get_option("presspermit_custom_conditions_post_status"), $wp_post_statuses);
        foreach ($custom_stati as $status => $status_args) {
            if (!empty($status_args['post_type'])) {
                $wp_post_statuses[$status]->post_type = (array)$status_args['post_type'];
            }
            if (! isset($wp_post_statuses[$status]->capability_status)) {
                $wp_post_statuses[$status]->capability_status = $status;
            }
        }

        if (in_array($pagenow, ['edit.php', 'post.php', 'post-new.php'])
            || (is_admin() && PWP::isAjax('inline-save'))
            || (in_array(presspermitPluginPage(), ['presspermit-status-edit', 'presspermit-status-new'], true))) {

            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Dashboard/PostAdmin.php');
            Statuses\UI\Dashboard\PostAdmin::set_status_labels();
        }

        do_action('presspermit_registrations');

        do_action('presspermit_conditions_loaded');

        PPS::attributes()->process_status_caps();
    }

    // Mirror ordering defined by PressPermit back to PublishPress.
    //
    // PublishPress does not natively support branching, but position sequence 
    // is flattened considering branch parent and child order.
    function flt_publishpress_status_position($terms, $taxonomies, $query_vars, $term_query)
    {
        global $publishpress;

        $pp = presspermit();

        $taxonomies = (array)$taxonomies;

        if ('post_status' != reset($taxonomies)) {
            return $terms;
        }

        $status_order_arr = array_merge(PPS::defaultStatusOrder(), (array)$pp->getOption('status_order'));
        $status_parent_arr = (array)$pp->getOption('status_parent');

        foreach ($terms as $k => $term_obj) {
            $status = $term_obj->slug;

            if (!empty($status_parent_arr[$status])) {
                $parent_status = $status_parent_arr[$status];
                $parent_order = (!empty($status_order_arr[$parent_status])) ? $status_order_arr[$parent_status] : 0;
                $status_order = (!empty($status_order_arr[$status])) ? $status_order_arr[$status] : 500;
                $terms[$k]->position = (1000 * $parent_order) + $status_order + 1;

            } elseif (isset($status_order_arr[$status])) {
                // Allow space between top level statuses for branch statuses
                $terms[$k]->position = 1000 * $status_order_arr[$status];
            }

            // term description may contain encoded copy of other position property, overriding filtering 
            if (!empty($publishpress->custom_status)) {
                if (!empty($terms[$k]->description)) {
                    $descript = $publishpress->custom_status->get_unencoded_description($terms[$k]->description);
                    if (is_array($descript)) {
                        unset($descript['position']);
                    }
                    $terms[$k]->description = $publishpress->custom_status->get_encoded_description($descript);
                }
            }
        }

        return $terms;
    }

    function act_roles_defined()
    {
        if ('presspermit-statuses' != presspermitPluginPage()) {
            if ($disabled = (array)presspermit()->getOption('disabled_post_status_conditions')) {
                $attributes = PPS::attributes();
                
                $attributes->attributes['post_status']->conditions = array_diff_key(
                    $attributes->attributes['post_status']->conditions, 
                    $disabled
                );

                global $wp_post_statuses;
                $disabled = array_diff_key($disabled, get_post_stati(['_builtin' => true]));

                foreach ($wp_post_statuses as $k => $status_obj) {
                    if (!empty($status_obj->pp_custom)) {
                        unset($wp_post_statuses[$k]);
                    }
                }

                $wp_post_statuses = array_diff_key($wp_post_statuses, $disabled);
            }
        }
    }

    function fltExcludeArbitraryCaps($caps)
    {
        $excluded = ['pp_define_post_status', 'pp_define_moderation', 'pp_define_privacy'];

        if (!presspermit()->getOption('supplemental_cap_moderate_any'))
            $excluded [] = 'pp_moderate_any';

        return array_merge($caps, $excluded);
    }

    function fltOrderStatuses($statuses, $args = []) {
        return PPS::orderStatuses($statuses, $args);
    }

    // Gutenberg: filter post author dropdown 
    function flt_rest_user_query($prepared_args, $request)
    {
        if (isset($prepared_args['who']) && ('authors' == $prepared_args['who'])) {
            if ($post_type = PWP::findPostType()) {
                if ($type_obj = get_post_type_object($post_type)) {
                    if (!current_user_can($type_obj->cap->edit_others_posts)) {
                        global $current_user;
                        $prepared_args['include'] = $current_user->ID;
                    }
                }
            }
        }

        return $prepared_args;
    }
}
