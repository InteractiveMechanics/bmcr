<?php
/**
 * @package PublishPress Multiple Authors Pro
 * @author  PublishPress
 *
 * Copyright (C) 2018 PublishPress
 *
 * This file is part of PublishPress Multiple Authors Pro
 *
 * PublishPress Multiple Authors Pro is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

use MultipleAuthors\Classes\Authors_Iterator;
use MultipleAuthors\Classes\Installer;
use MultipleAuthors\Classes\Legacy\Module;
use MultipleAuthors\Classes\Legacy\Util;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Classes\Utils;
use MultipleAuthors\Factory;

if ( ! class_exists('MA_Multiple_Authors')) {
    /**
     * class MA_Multiple_Authors
     */
    class MA_Multiple_Authors extends Module
    {
        const SETTINGS_SLUG = 'ppma-settings';

        /**
         * Constant for valid status
         */
        const LICENSE_STATUS_VALID = 'valid';

        /**
         * Constant for invalid status
         */
        const LICENSE_STATUS_INVALID = 'invalid';

        public $module_name = 'multiple_authors';

        /**
         * The menu slug.
         */
        const MENU_SLUG = 'ppma-authors';

        /**
         * List of post types which supports checklist
         *
         * @var array
         */
        protected $post_types = [];

        /**
         * Instance for the module
         *
         * @var stdClass
         */
        public $module;

        private $eddAPIUrl = 'https://publishpress.com';


        /**
         * Construct the MA_Multiple_Authors class
         */
        public function __construct()
        {
            $this->module_url = $this->get_module_url(__FILE__);

            // Register the module with PublishPress
            $args = [
                'title'                => __('Multiple Authors', 'publishpress-multiple-authors'),
                'short_description'    => __('Add support for multiple authors on your content',
                    'publishpress-multiple-authors'),
                'extended_description' => __('Add support for multiple authors on your content',
                    'publishpress-multiple-authors'),
                'module_url'           => $this->module_url,
                'icon_class'           => 'dashicons dashicons-feedback',
                'slug'                 => 'multiple-authors',
                'default_options'      => [
                    'enabled'              => 'on',
                    'post_types'           => [
                        'post' => 'on',
                        'page' => 'on',
                    ],
                    'append_to_content'    => 'yes',
                    'author_for_new_users' => [],
                    'layout'               => 'simple_list',
                    'force_empty_author'   => 'no',
                    'license_key'          => '',
                    'license_status'       => self::LICENSE_STATUS_INVALID,
                    'display_branding'     => 'on',
                ],
                'options_page'         => false,
                'autoload'             => true,
            ];

            // Apply a filter to the default options
            $args['default_options'] = apply_filters('pp_multiple_authors_default_options', $args['default_options']);

            $legacyPlugin = Factory::getLegacyPlugin();

            $this->module = $legacyPlugin->register_module($this->module_name, $args);

            parent::__construct();
        }

        /**
         * Returns a list of post types the multiple authors module.
         *
         * @return array
         */
        public function get_post_types()
        {
            if (empty($this->post_types)) {
                $post_types = [
                    'post' => esc_html__('Posts', 'publishpress-multiple-authors'),
                    'page' => esc_html__('Pages', 'publishpress-multiple-authors'),
                ];

                // Apply filters to the list of requirements
                $this->post_types = apply_filters('pp_multiple_authors_post_types', $post_types);

                // Try a more readable name
                foreach ($this->post_types as $type => $label) {
                    $this->post_types[$type] = esc_html__(ucfirst($label));
                }
            }

            return $this->post_types;
        }

        /**
         * Initialize the module. Conditionally loads if the module is enabled
         */
        public function init()
        {
            add_action('admin_init', [$this, 'register_settings']);
            add_action('admin_init', [$this, 'load_updater']);
            add_action('admin_init', [$this, 'handle_action_reset_author_terms']);
            add_action('admin_init', [$this, 'migrate_legacy_settings']);
            add_action('admin_notices', [$this, 'handle_action_reset_author_terms_notice']);

            add_action('multiple_authors_delete_mapped_authors', [$this, 'action_delete_mapped_authors']);
            add_action('multiple_authors_delete_guest_authors', [$this, 'action_delete_guest_authors']);
            add_action('multiple_authors_create_post_authors', [$this, 'action_create_post_authors']);
            add_action('multiple_authors_create_role_authors', [$this, 'action_create_role_authors']);

            // Filters the list of authors in the Improved Notifications add-on.
            add_filter(
                'publishpress_notif_workflow_receiver_post_authors',
                [$this, 'filter_workflow_receiver_post_authors'],
                10,
                3
            );

            add_filter('multiple_authors_validate_module_settings', [$this, 'validate_module_settings'], 10, 2);
            add_filter('publishpress_multiple_authors_settings_tabs', [$this, 'settings_tab']);

            add_filter('gettext', [$this, 'filter_get_text'], 101, 3);

            // Fix upload permissions for multiple authors.
            add_filter('map_meta_cap', [$this, 'filter_map_meta_cap'], 10, 4);

            add_filter('wp_insert_post_data', [$this, 'force_empty_user']);

            // Menu
            add_filter('multiple_authors_admin_menu_slug', [$this, 'filter_admin_menu_slug']);
            add_action('multiple_authors_admin_menu_page', [$this, 'action_admin_menu_page']);
            add_action('multiple_authors_admin_submenu', [$this, 'action_admin_submenu']);

            add_action('admin_enqueue_scripts', [$this, 'admin_enqueue_scripts']);
        }

        /**
         * Filters the menu slug.
         *
         * @param $menu_slug
         *
         * @return string
         */
        public function filter_admin_menu_slug($menu_slug)
        {
            if (empty($menu_slug)) {
                $menu_slug = self::MENU_SLUG;
            }

            return $menu_slug;
        }

        /**
         * Add necessary things to the admin menu
         */
        public function action_admin_submenu()
        {
            $legacyPlugin = Factory::getLegacyPlugin();

            // Main Menu
            add_submenu_page(
                $legacyPlugin->get_menu_slug(),
                esc_html__('Authors', 'publishpress-multiple-authors'),
                esc_html__('Authors', 'publishpress-multiple-authors'),
                apply_filters('pp_multiple_authors_manage_authors_cap', 'ppma_view_authors'),
                self::MENU_SLUG,
                [$this, 'render_admin_page']
            );
        }

        public function render_admin_page()
        {
            echo 'Settings...';
        }

        /**
         * Creates the admin menu if there is no menu set.
         */
        public function action_admin_menu_page()
        {
            $legacyPlugin = Factory::getLegacyPlugin();

            if ($legacyPlugin->get_menu_slug() !== self::MENU_SLUG) {
                return;
            }

            $legacyPlugin->add_menu_page(
                esc_html__('Authors', 'publishpress-multiple-authors'),
                apply_filters('pp_multiple_authors_manage_authors_cap', 'manage_options'),
                self::MENU_SLUG,
                [$this, 'options_page_controller']
            );
        }

        /**
         * Print the content of the configure tab.
         */
        public function print_configure_view()
        {
            $container = Factory::get_container();
            $twig      = $container['twig'];

            echo $twig->render(
                'settings-tab.twig',
                [
                    'form_action'        => menu_page_url($this->module->settings_slug, false),
                    'options_group_name' => $this->module->options_group_name,
                    'module_name'        => $this->module->slug,
                ]
            );
        }

        /**
         * Register settings for notifications so we can partially use the Settings API
         * (We use the Settings API for form generation, but not saving)
         */
        public function register_settings()
        {
            /**
             * General
             */

            add_settings_section(
                $this->module->options_group_name . '_general',
                __return_false(),
                [$this, 'settings_section_general'],
                $this->module->options_group_name
            );

            add_settings_field(
                'license_key',
                __('License key:', 'publishpress-multiple-authors'),
                [$this, 'settings_license_key_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            if (Util::hasValidLicenseKeySet()) {
                add_settings_field(
                    'display_branding',
                    __('Display PublishPress branding in the admin:', 'publishpress-multiple-authors'),
                    [$this, 'settings_branding_option'],
                    $this->module->options_group_name,
                    $this->module->options_group_name . '_general'
                );
            }

            add_settings_field(
                'post_types',
                __('Add to these post types:', 'publishpress-multiple-authors'),
                [$this, 'settings_post_types_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'author_for_new_users',
                __('Automatically create author profiles:',
                    'publishpress-multiple-authors'),
                [$this, 'settings_author_for_new_users_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            add_settings_field(
                'force_empty_author',
                __('Remove author from new posts:', 'publishpress-multiple-authors'),
                [$this, 'settings_force_empty_author'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_general'
            );

            /**
             *
             * Display
             */

            add_settings_section(
                $this->module->options_group_name . '_display',
                __return_false(),
                [$this, 'settings_section_display'],
                $this->module->options_group_name
            );

            add_settings_field(
                'append_to_content',
                __('Show below the content:', 'publishpress-multiple-authors'),
                [$this, 'settings_append_to_content_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display'
            );

            add_settings_field(
                'title_appended_to_content',
                __('Title for the author box:', 'publishpress-multiple-authors'),
                [$this, 'settings_title_appended_to_content_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display'
            );

            add_settings_field(
                'layout',
                __('Layout:', 'publishpress-multiple-authors'),
                [$this, 'settings_layout_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display'
            );

            add_settings_field(
                'show_email_link',
                __('Show email link:', 'publishpress-multiple-authors'),
                [$this, 'settings_show_email_link_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display'
            );

            add_settings_field(
                'show_site_link',
                __('Show site link:', 'publishpress-multiple-authors'),
                [$this, 'settings_show_site_link_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_display'
            );

            /**
             *
             * Maintenance
             */

            add_settings_section(
                $this->module->options_group_name . '_maintenance',
                __return_false(),
                [$this, 'settings_section_maintenance'],
                $this->module->options_group_name
            );

            add_settings_field(
                'maintenance',
                __return_empty_string(),
                [$this, 'settings_maintenance_option'],
                $this->module->options_group_name,
                $this->module->options_group_name . '_maintenance'
            );
        }

        public function settings_section_general()
        {
            echo '<input type="hidden" id="ppma-tab-general" />';
        }

        public function settings_section_display()
        {
            echo '<input type="hidden" id="ppma-tab-display" />';
        }

        public function settings_section_maintenance()
        {
            echo '<input type="hidden" id="ppma-tab-maintenance" />';
        }

        /**
         * Displays the field to allow select the post types for checklist.
         */
        public function settings_post_types_option()
        {
            $legacyPlugin = Factory::getLegacyPlugin();

            $legacyPlugin->settings->helper_option_custom_post_type($this->module);
        }

        /**
         * Displays the field to allow force new posts to not have author.
         */
        public function settings_force_empty_author()
        {
            $id    = $this->module->options_group_name . '_force_empty_author';
            $value = isset($this->module->options->force_empty_author) ? $this->module->options->force_empty_author : 'no';

            echo '<label for="' . $id . '">';
            echo '<input type="checkbox" value="yes" id="' . $id . '" name="' . $this->module->options_group_name . '[force_empty_author]" '
                 . checked($value, 'yes', false) . ' />';
            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">' . esc_html__('This will remove the default author when creating posts. This means that new posts will have no author until you select one.',
                    'publishpress-multiple-authors') . '</span>';
            echo '</label>';
        }

        public function settings_license_key_option()
        {
            $id     = $this->module->options_group_name . '_license_key';
            $value  = isset($this->module->options->license_key) ? $this->module->options->license_key : '';
            $status = isset($this->module->options->license_status) ? $this->module->options->license_status : self::LICENSE_STATUS_INVALID;

            if (empty($status) || empty($value)) {
                $status = self::LICENSE_STATUS_INVALID;
            }

            if ($status === self::LICENSE_STATUS_VALID) {
                $statusLabel = __('Activated', 'publishpress-multiple-authors');
            } else {
                $statusLabel = __('Inactive', 'publishpress-multiple-authors');
            }

            echo '<label for="' . $id . '">';
            echo '<input type="text" value="' . $value . '" id="' . $id . '" name="' . $this->module->options_group_name . '[license_key]"/>';
            echo '<div class="ppma_license_key_status ' . $status . '"><span class="ppma_license_key_status_label">' . __('Status: ',
                    'publishpress-multiple-authors') . '</span>' . $statusLabel . '</div>';
            echo '<p class="ppma_settings_field_description">' . esc_html__('Enter the license key for being able to update the plugin.',
                    'publishpress-multiple-authors') . '</p>';
            echo '</label>';
        }

        /**
         * Branding options
         *
         * @since 0.7
         */
        public function settings_branding_option()
        {
            $id = $this->module->options_group_name . '_display_branding';

            echo '<label for="' . $id . '">';
            echo '<input id="' . $id . '" name="'
                 . $this->module->options_group_name . '[display_branding]"';
            if (isset($this->module->options->display_branding)) {
                checked($this->module->options->display_branding, 'on');
            }
            echo ' type="checkbox" value="on" /></label>';
        }

        /**
         * Displays the field to choose display or not the author box at the
         * end of the content
         *
         * @param array
         */
        public function settings_append_to_content_option($args = [])
        {
            $id    = $this->module->options_group_name . '_append_to_content';
            $value = isset($this->module->options->append_to_content) ? $this->module->options->append_to_content : 'yes';

            echo '<label for="' . $id . '">';
            echo '<input type="checkbox" value="yes" id="' . $id . '" name="' . $this->module->options_group_name . '[append_to_content]" '
                 . checked($value, 'yes', false) . ' />';
            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">' . esc_html__('This will display the authors box at the end of the content.',
                    'publishpress-multiple-authors') . '</span>';
            echo '</label>';
        }

        /**
         * Displays the field to choose the title for the author box at the
         * end of the content
         *
         * @param array
         */
        public function settings_title_appended_to_content_option($args = [])
        {
            $id    = $this->module->options_group_name . '_title_appended_to_content';
            $value = isset($this->module->options->title_appended_to_content) ? $this->module->options->title_appended_to_content : esc_html__('Author',
                'publishpress-multiple-authors');

            echo '<label for="' . $id . '">';
            echo '<input type="text" value="' . esc_attr($value) . '" id="' . $id . '" name="' . $this->module->options_group_name . '[title_appended_to_content]" class="regular-text" />';
            echo '</label>';
        }

        /**
         * @param array $args
         */
        public function settings_layout_option($args = [])
        {
            $id    = $this->module->options_group_name . '_layout';
            $value = isset($this->module->options->layout) ? $this->module->options->layout : 'simple_list';

            echo '<label for="' . $id . '">';

            echo '<select id="' . $id . '" name="' . $this->module->options_group_name . '[layout]">';

            $layouts = apply_filters('pp_multiple_authors_author_layouts', []);

            foreach ($layouts as $layout => $text) {
                $selected = $value === $layout ? 'selected="selected"' : '';
                echo '<option value="' . $layout . '" ' . $selected . '>' . $text . '</option>';
            }

            echo '</select>';
            echo '</label>';
        }

        /**
         * @param array $args
         */
        public function settings_author_for_new_users_option($args = [])
        {
            $id     = $this->module->options_group_name . '_author_for_new_users';
            $values = isset($this->module->options->author_for_new_users) ? $this->module->options->author_for_new_users : '';

            echo '<label for="' . $id . '">';

            echo '<select id="' . $id . '" name="' . $this->module->options_group_name . '[author_for_new_users][]" multiple="multiple" class="chosen-select">';

            $roles = get_editable_roles();

            foreach ($roles as $role => $data) {
                $selected = in_array($role, $values) ? 'selected="selected"' : '';
                echo '<option value="' . $role . '" ' . $selected . '>' . $data['name'] . '</option>';
            }

            echo '</select>';

            echo '<p class="ppma_settings_field_description">' . __('Author profiles can be mapped to WordPress user accounts. This option allows you to automatically create author profiles when users are created in these roles. You can also do this for existing users by clicking the "Create missed authors from role" button in the Maintenance tab.',
                    'publishpress-multiple-authors');

            echo '</label>';
        }

        /**
         * Displays the field to choose display or not the email link/icon.
         *
         * @param array
         */
        public function settings_show_email_link_option($args = [])
        {
            $id    = $this->module->options_group_name . '_show_email_link';
            $value = isset($this->module->options->show_email_link) ? $this->module->options->show_email_link : 'yes';

            echo '<label for="' . $id . '">';
            echo '<input type="checkbox" value="yes" id="' . $id . '" name="' . $this->module->options_group_name . '[show_email_link]" '
                 . checked($value, 'yes', false) . ' />';
            echo '&nbsp;&nbsp;&nbsp;<span class="ppma_settings_field_description">' . esc_html__('This will display the authors email in the author box.',
                    'publishpress-multiple-authors') . '</span>';
            echo '</label>';
        }

        /**
         * Displays the field to choose display or not the email link/icon.
         *
         * @param array
         */
        public function settings_show_site_link_option($args = [])
        {
            $id    = $this->module->options_group_name . '_show_site_link';
            $value = isset($this->module->options->show_site_link) ? $this->module->options->show_site_link : 'yes';

            echo '<label for="' . $id . '">';
            echo '<input type="checkbox" value="yes" id="' . $id . '" name="' . $this->module->options_group_name . '[show_site_link]" '
                 . checked($value, 'yes', false) . ' />';
            echo '&nbsp;&nbsp;&nbsp; <span class="ppma_settings_field_description">' . esc_html__('This will display the authors site in the author box.',
                    'publishpress-multiple-authors') . '</span>';
            echo '</label>';
        }

        /**
         * Displays the button to reset the author terms.
         *
         * @param array
         */
        public function settings_maintenance_option($args = [])
        {
            $nonce     = wp_create_nonce('multiple_authors_maintenance');
            $base_link = esc_url(admin_url('/admin.php?page=ppma-modules-settings&ppma_action=%s&nonce=' . $nonce));

            $actions = [
                'create_post_authors' => [
                    'title'        => __('Create missed post authors', 'publishpress-multiple-authors'),
                    'description'  => 'This action is very helpful if you\'re installing Multiple Authors on an existing WordPress site. This action analyzes all the posts on your site. If the action finds a WordPress user is set as an author, it will automatically share that data with Multiple Authors.',
                    'button_label' => __('Create missed post authors', 'publishpress-multiple-authors'),
                ],

                'create_role_authors' => [
                    'title'        => __('Create missed authors from role', 'publishpress-multiple-authors'),
                    'description'  => 'This action is very helpful if you\'re installing Multiple Authors on an existing WordPress site. This action finds all the users in a role and creates author profiles for them. You can choose the roles using the "Automatically create author profiles" setting.',
                    'button_label' => __('Create missed authors from role', 'publishpress-multiple-authors'),
                ],

                'create_default_layouts' => [
                    'title'        => __('Create default layouts',
                        'publishpress-multiple-authors'),
                    'description'  => 'This action creates the default custom layouts if they don\'t exist or were accidentally deleted.',
                    'button_label' => __('Create default layouts', 'publishpress-multiple-authors'),
                ],

                'delete_mapped_authors' => [
                    'title'        => __('Delete Mapped Authors', 'publishpress-multiple-authors'),
                    'description'  => 'This action can reset the Multiple Authors data before using other maintenance options. It will delete all author profiles that are mapped to a WordPress user account. This will not delete the WordPress user accounts, but any links between the posts and multiple authors will be lost.',
                    'button_label' => __('Delete all authors mapped to users', 'publishpress-multiple-authors'),
                ],

                'delete_guest_authors' => [
                    'title'        => __('Delete Guest Authors', 'publishpress-multiple-authors'),
                    'description'  => 'This action can reset the Multiple Authors data before using other maintenance options. Guest authors are author profiles that are not mapped to a WordPress user account. This action will delete all guest authors.',
                    'button_label' => __('Delete all guest authors', 'publishpress-multiple-authors'),
                ],
            ];

            echo '<div id="ppma_maintenance_settings">';

            echo '<p class="ppma_warning">' . __('Please be careful clicking these buttons. Before clicking, we recommend taking a site backup in case anything goes wrong.',
                    'publishpress-multiple-authors');
            echo '</p>';

            foreach ($actions as $actionName => $actionInfo) {

                echo '<div class="ppma_maintenance_action_wrapper">';
                echo '<h4>' . $actionInfo['title'] . '</h4>';
                echo '<p class="ppma_settings_field_description">' . $actionInfo['description'] . '</p>';
                echo '<a href="' . sprintf($base_link,
                        $actionName) . '" class="button-secondary button-danger ppma_maintenance_button" id="' . $actionName . '">' . $actionInfo['button_label'] . '</a>';

                echo '</div>';
            }

            echo '</div>';
        }

        /**
         * Validate data entered by the user
         *
         * @param array $new_options New values that have been entered by the user
         *
         * @return array $new_options Form values after they've been sanitized
         */
        public function validate_module_settings($new_options, $module_name)
        {
            if ($module_name !== 'multiple_authors') {
                return $new_options;
            }

            // Whitelist validation for the post type options
            if ( ! isset($new_options['post_types'])) {
                $new_options['post_types'] = [];
            }

            $new_options['post_types'] = $this->clean_post_type_options(
                $new_options['post_types'],
                $this->module->post_type_support
            );

            if ( ! isset($new_options['append_to_content'])) {
                $new_options['append_to_content'] = 'no';
            }

            if ( ! isset($new_options['author_for_new_users']) || ! is_array($new_options['author_for_new_users'])) {
                $new_options['author_for_new_users'] = [];
            }

            if ( ! isset($new_options['show_email_link'])) {
                $new_options['show_email_link'] = 'no';
            }

            if ( ! isset($new_options['show_site_link'])) {
                $new_options['show_site_link'] = 'no';
            }

            if ( ! isset($new_options['force_empty_author'])) {
                $new_options['force_empty_author'] = 'no';
            }

            if (isset($new_options['layout'])) {
                /**
                 * Filter the list of available layouts.
                 */
                $layouts = apply_filters('pp_multiple_authors_author_layouts', []);

                if ( ! array_key_exists($new_options['layout'], $layouts)) {
                    $new_options['layout'] = 'simple_list';
                }
            }

            // Check if we need to activate the license key
            $justActivatedLicense = false;
            if (isset($new_options['license_key'])) {
                $licenseStatus = $this->module->options->license_status;

                if ($this->module->options->license_key !== $new_options['license_key'] || empty($licenseStatus) || $licenseStatus === self::LICENSE_STATUS_INVALID) {
                    $this->validate_license_key($new_options['license_key']);
                    $justActivatedLicense = true;
                }
            }

            if ( ! isset($new_options['display_branding']) && ! $justActivatedLicense) {
                $new_options['display_branding'] = 'off';
            }

            return $new_options;
        }

        public function validate_license_key($licenseKey)
        {
            // The default status.
            $licenseNewStatus = self::LICENSE_STATUS_INVALID;

            // Make the request.
            $eddResponse = wp_remote_post(
                $this->eddAPIUrl,
                [
                    'timeout'   => 30,
                    'sslverify' => true,
                    'body'      => [
                        'edd_action' => "activate_license",
                        'license'    => $licenseKey,
                        'item_id'    => PP_MULTIPLE_AUTHORS_ITEM_ID,
                        'url'        => site_url(),
                    ],
                ]
            );

            // Is the response an error?
            if (is_wp_error($eddResponse) || 200 !== wp_remote_retrieve_response_code($eddResponse)) {
                $licenseNewStatus = self::LICENSE_STATUS_INVALID;
            } else {
                // Convert data response to an object.
                $data = json_decode(wp_remote_retrieve_body($eddResponse));

                // Do we have empty data? Throw an error.
                if (empty($data) || ! is_object($data)) {
                    $licenseNewStatus = self::LICENSE_STATUS_INVALID;
                } else {
                    if ($data->success && $data->license === self::LICENSE_STATUS_VALID) {
                        $licenseNewStatus = self::LICENSE_STATUS_VALID;
                    } else {
                        $licenseNewStatus = self::LICENSE_STATUS_INVALID;
                    }
                }
            }

            $legacyPlugin = Factory::getLegacyPlugin();
            $legacyPlugin->update_module_option('multiple_authors', 'license_status', $licenseNewStatus);
        }

        /**
         * @param array $tabs
         *
         * @return array
         */
        public function settings_tab($tabs)
        {
            $tabs = array_merge($tabs, [
                '#ppma-tab-general'     => __('General', 'publishpress-multiple-authors'),
                '#ppma-tab-display'     => __('Display', 'publishpress-multiple-authors'),
                '#ppma-tab-maintenance' => __('Maintenance', 'publishpress-multiple-authors'),
            ]);

            return $tabs;
        }

        /**
         * Filters the list of receivers in the notification workflows provided
         * by the improved notifications add-on.
         *
         * @param array   $receivers
         * @param WP_Post $workflow
         * @param array   $args
         *
         * @return array
         */
        public function filter_workflow_receiver_post_authors($receivers, $workflow, $args)
        {
            if ( ! function_exists('multiple_authors')) {
                require_once PP_MULTIPLE_AUTHORS_BASE_PATH . 'template-tags.php';
            }

            $authors_iterator = new Authors_Iterator($args['post']->ID);
            while ($authors_iterator->iterate()) {
                if ( ! in_array($authors_iterator->current_author->ID, $receivers)) {
                    $receivers[] = $authors_iterator->current_author->ID;
                }
            }

            return $receivers;
        }

        /**
         * Over hide some strings for Authors.
         *
         * @param string $translation Translated text.
         * @param string $text        Text to translate.
         * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
         *
         * @return string
         */
        public function filter_get_text($translation, $text, $domain)
        {
            if ( ! Utils::is_valid_page()) {
                return $translation;
            }

            // The description of the field Name
            if ('default' === $domain && 'The name is how it appears on your site.' === $translation) {
                $translation = __('This is how the author’s name will appears on your site.',
                    'publishpress-multiple-authors');
            }

            // The name of field Slug, convert to Author URL
            if (isset($_GET['taxonomy']) && 'author' === $_GET['taxonomy']) {
                if ('default' === $domain) {
                    if ('Slug' === $translation) {
                        $translation = __('Author URL', 'publishpress-multiple-authors');
                    }

                    if ('The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' === $translation) {
                        $translation = __('This forms part of the URL for the author’s profile page. If you choose a Mapped User, this URL is taken from the user’s account and can not be changed.',
                            'publishpress-multiple-authors');
                    }
                }
            }

            return $translation;
        }

        /**
         * Handle the action to reset author therms.
         * Remove all authors and regenerate based on posts' authors and the setting to automatically create authors
         * for specific roles.
         */
        public function handle_action_reset_author_terms()
        {
            $actions = [
                'delete_mapped_authors',
                'delete_guest_authors',
                'create_post_authors',
                'create_role_authors',
            ];

            if ( ! isset($_GET['ppma_action']) || ! in_array($_GET['ppma_action'],
                    $actions) || isset($_GET['author_term_reset_notice'])) {
                return;
            }

            $nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';
            if ( ! wp_verify_nonce($nonce, 'multiple_authors_maintenance')) {
                wp_redirect(admin_url('/admin.php?page=ppma-modules-settings&author_term_reset_notice=fail'),
                    301);

                return;
            }

            try {
                $result = do_action('multiple_authors_' . $_GET['ppma_action']);

                wp_redirect(admin_url('/admin.php?page=ppma-modules-settings&author_term_reset_notice=success'),
                    301);
            } catch (Exception $e) {
                wp_redirect(admin_url('/admin.php?page=ppma-modules-settings&author_term_reset_notice=fail'),
                    301);
            }
        }

        public function action_delete_mapped_authors()
        {
            global $wpdb;

            $query = "
                SELECT tt.term_id
                FROM {$wpdb->term_taxonomy} AS tt
                WHERE
                tt.taxonomy = 'author'
                AND (SELECT COUNT(*) FROM {$wpdb->termmeta} AS tm WHERE tm.term_id = tt.term_id AND tm.meta_key = 'user_id') > 0";

            $terms = $wpdb->get_results($query);

            if ( ! empty($terms)) {
                foreach ($terms as $term) {
                    wp_delete_term($term->term_id, 'author');
                }
            }
        }

        public function action_delete_guest_authors()
        {
            global $wpdb;

            $query = "
                SELECT tt.term_id
                FROM {$wpdb->term_taxonomy} AS tt
                WHERE
                    tt.taxonomy = 'author'
                    AND (
                        (SELECT tm.meta_value FROM {$wpdb->termmeta} AS tm WHERE tt.term_id = tm.term_id AND tm.meta_key = 'user_id') = 0
                        OR (SELECT tm.meta_value FROM {$wpdb->termmeta} AS tm WHERE tt.term_id = tm.term_id AND tm.meta_key = 'user_id') IS NULL
                    )";

            $terms = $wpdb->get_results($query);

            if ( ! empty($terms)) {
                foreach ($terms as $term) {
                    wp_delete_term($term->term_id, 'author');
                }
            }
        }

        public function action_create_post_authors()
        {
            Installer::convert_post_author_into_taxonomy();
            Installer::add_author_term_for_posts();
        }

        public function action_create_role_authors()
        {
            // Create authors for users in the taxonomies selected for automatic creation of authors.
            $legacyPlugin = Factory::getLegacyPlugin();

            $roles = (array)$legacyPlugin->modules->multiple_authors->options->author_for_new_users;

            // Check if we have any role selected to create an author for the new user.
            if ( ! empty($roles)) {

                // Get users from roles
                $args  = [
                    'role__in' => $roles,
                ];
                $users = get_users($args);

                if ( ! empty($users)) {
                    foreach ($users as $user) {
                        // Create author for this user
                        Author::create_from_user($user->ID);
                    }
                }
            }
        }

        /**
         *
         */
        public function handle_action_reset_author_terms_notice()
        {
            if ( ! isset($_GET['author_term_reset_notice'])) {
                return;
            }

            if ($_GET['author_term_reset_notice'] === 'fail') {
                echo '<div class="notice notice-error is-dismissible">';
                echo '<p>' . __('Error. Author terms could not be reseted.',
                        'publishpress-multiple-authors') . '</p>';
                echo '</div>';

                return;
            }

            if ($_GET['author_term_reset_notice'] === 'success') {
                echo '<div class="notice notice-success is-dismissible">';
                echo '<p>' . __('Maintenance completed successfully.', 'publishpress-multiple-authors') . '</p>';
                echo '</div>';

                return;
            }
        }

        /**
         * Fix the upload of media for posts when the user is a secondary author and can't edit others' posts.
         *
         * @param $caps
         * @param $cap
         * @param $user_id
         * @param $args
         *
         * @return mixed
         */
        public function filter_map_meta_cap($caps, $cap, $user_id, $args)
        {
            if ($cap === 'edit_post' && in_array('edit_others_posts', $caps)) {
                if (isset($args[0])) {
                    $post_id = (int)$args[0];

                    // Check if it is an orphan post
                    if ($this->is_force_empty_author_enabled()) {
                        $post = get_post($post_id);
                        $user = get_userdata($user_id);

                        if ((int)$post->post_author === 0 && $user->has_cap('ppma_edit_orphan_post')) {
                            foreach ($caps as &$item) {
                                // If he is an author for this post we should only check edit_posts.
                                if ($item === 'edit_others_posts') {
                                    $item = 'edit_posts';
                                }
                            }
                        }
                    }

                    // Check if the user is an author for the current post
                    if (is_multiple_author_for_post($user_id, $post_id)) {
                        foreach ($caps as &$item) {
                            // If he is an author for this post we should only check edit_posts.
                            if ($item === 'edit_others_posts') {
                                $item = 'edit_posts';
                            }
                        }
                    }
                }
            }

            return $caps;
        }

        private function is_force_empty_author_enabled()
        {
            return isset($this->module->options->force_empty_author) ? $this->module->options->force_empty_author === 'yes' : 'no';
        }

        /**
         * @param array $data
         *
         * @return array
         */
        public function force_empty_user($data)
        {
            $emptyAuthorByDefault = $this->is_force_empty_author_enabled();

            if ($emptyAuthorByDefault && $data['post_status'] === 'auto-draft') {
                $data['post_author'] = 0;
            }

            return $data;
        }

        public function admin_enqueue_scripts()
        {
            if (isset($_GET['page']) && $_GET['page'] === 'ppma-modules-settings') {
                wp_enqueue_script(
                    'multiple-authors-settings',
                    PP_MULTIPLE_AUTHORS_ASSETS_URL . '/js/settings.js',
                    ['jquery'],
                    PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION
                );
            }
        }

        /**
         * @return bool
         */
        public function migrate_legacy_settings()
        {
            if (get_option('publishpress_multiple_authors_settings_migrated_3_0_0')) {
                return false;
            }

            $legacyOptions = get_option('publishpress_multiple_authors_options');
            if ( ! empty($legacyOptions)) {
                update_option('multiple_authors_multiple_authors_options', $legacyOptions);
            }

            update_option('publishpress_multiple_authors_settings_migrated_3_0_0', 1);
        }

        /**
         * Load the update manager.
         *
         * @return mixed
         */
        public function load_updater()
        {

            $container = Factory::get_container();

            return $container['edd_container']['update_manager'];
        }
    }
}
