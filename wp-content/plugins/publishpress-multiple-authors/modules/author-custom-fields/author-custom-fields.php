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

use MultipleAuthors\Classes\CustomFieldsModel;
use MultipleAuthors\Classes\Legacy\Module;
use MultipleAuthors\Classes\Objects\Author;
use MultipleAuthors\Factory;

/**
 * class MA_Author_Custom_Fields
 */
class MA_Author_Custom_Fields extends Module
{
    /**
     * Post Type.
     */
    const POST_TYPE_CUSTOM_FIELDS = 'ppmacf_field';

    /**
     * Meta data prefix.
     */
    const META_PREFIX = 'ppmacf_';

    public $module_name = 'author_custom_fields';

    /**
     * Instance of the module
     *
     * @var stdClass
     */
    public $module;

    /**
     * Construct the MA_Multiple_Authors class
     */
    public function __construct()
    {
        $this->module_url = $this->get_module_url(__FILE__);

        // Register the module with PublishPress
        $args = [
            'title'                => __('Author Fields', 'publishpress-multiple-authors'),
            'short_description'    => __('Add support for custom fields in the author profiles.',
                'publishpress-multiple-authors'),
            'extended_description' => __('Add support for custom fields in the author profiles.',
                'publishpress-multiple-authors'),
            'module_url'           => $this->module_url,
            'icon_class'           => 'dashicons dashicons-edit',
            'slug'                 => 'author-custom-fields',
            'default_options'      => [
                'enabled' => 'on',
            ],
            'options_page'         => false,
            'autoload'             => true,
        ];

        // Apply a filter to the default options
        $args['default_options'] = apply_filters('MA_Author_Custom_Fields_default_options', $args['default_options']);

        $legacyPlugin = Factory::getLegacyPlugin();

        $this->module = $legacyPlugin->register_module($this->module_name, $args);

        parent::__construct();
    }

    /**
     * Initialize the module. Conditionally loads if the module is enabled
     */
    public function init()
    {
        add_action('multiple_authors_admin_submenu', [$this, 'adminSubmenu'], 50);
        add_filter('post_updated_messages', [$this, 'setPostUpdateMessages']);
        add_filter('bulk_post_updated_messages', [$this, 'setPostBulkUpdateMessages'], 10, 2);
        add_action('cmb2_admin_init', [$this, 'renderMetaboxes']);
        add_filter('multiple_authors_author_fields', [$this, 'filterAuthorFields'], 10, 2);
        add_action('created_author', [$this, 'saveTermCustomField']);
        add_action('edited_author', [$this, 'saveTermCustomField']);
        add_filter('cmb2_field_new_value', [$this, 'sanitizeFieldName'], 10, 3);
        add_filter('cmb2_override_' . self::META_PREFIX . 'slug_meta_remove', [$this, 'removePostCustomFieldSlug']);
        add_filter('cmb2_override_' . self::META_PREFIX . 'slug_meta_value', [$this, 'overridePostSlugMetaValue'], 10,
            2);
        add_filter('cmb2_override_' . self::META_PREFIX . 'slug_meta_save', [$this, 'overridePostSlugMetaSave'], 10, 2);
        add_filter('pp_multiple_authors_author_properties', [$this, 'filterAuthorProperties']);
        add_filter('pp_multiple_authors_author_attribute', [$this, 'filterAuthorAttribute'], 10, 3);
        add_filter('manage_edit-' . self::POST_TYPE_CUSTOM_FIELDS . '_columns', [$this, 'filterFieldColumns']);
        add_action('manage_' . self::POST_TYPE_CUSTOM_FIELDS . '_posts_custom_column', [$this, 'manageFieldColumns'],
            10, 2);
        add_filter('wp_unique_post_slug', [$this, 'fixPostSlug'], 10, 4);

        $this->registerPostType();
    }

    /**
     * Register the post types.
     */
    private function registerPostType()
    {
        $labelSingular = __('Author Field', 'publishpress-multiple-authors');
        $labelPlural   = __('Author Fields', 'publishpress-multiple-authors');

        $postTypeLabels = [
            'name'                  => _x('%2$s', 'Custom Field post type name', 'publishpress-multiple-authors'),
            'singular_name'         => _x('%1$s', 'singular custom field post type name',
                'publishpress-multiple-authors'),
            'add_new'               => __('New %1s', 'publishpress-multiple-authors'),
            'add_new_item'          => __('Add New %1$s', 'publishpress-multiple-authors'),
            'edit_item'             => __('Edit %1$s', 'publishpress-multiple-authors'),
            'new_item'              => __('New %1$s', 'publishpress-multiple-authors'),
            'all_items'             => __('%2$s', 'publishpress-multiple-authors'),
            'view_item'             => __('View %1$s', 'publishpress-multiple-authors'),
            'search_items'          => __('Search %2$s', 'publishpress-multiple-authors'),
            'not_found'             => __('No %2$s found', 'publishpress-multiple-authors'),
            'not_found_in_trash'    => __('No %2$s found in Trash', 'publishpress-multiple-authors'),
            'parent_item_colon'     => '',
            'menu_name'             => _x('%2$s', 'custom field post type menu name', 'publishpress-multiple-authors'),
            'featured_image'        => __('%1$s Image', 'publishpress-multiple-authors'),
            'set_featured_image'    => __('Set %1$s Image', 'publishpress-multiple-authors'),
            'remove_featured_image' => __('Remove %1$s Image', 'publishpress-multiple-authors'),
            'use_featured_image'    => __('Use as %1$s Image', 'publishpress-multiple-authors'),
            'filter_items_list'     => __('Filter %2$s list', 'publishpress-multiple-authors'),
            'items_list_navigation' => __('%2$s list navigation', 'publishpress-multiple-authors'),
            'items_list'            => __('%2$s list', 'publishpress-multiple-authors'),
        ];

        foreach ($postTypeLabels as $labelKey => $labelValue) {
            $postTypeLabels[$labelKey] = sprintf($labelValue, $labelSingular, $labelPlural);
        }

        $postTypeArgs = [
            'labels'             => $postTypeLabels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => false,
            'map_meta_cap'       => true,
            'has_archive'        => self::POST_TYPE_CUSTOM_FIELDS,
            'hierarchical'       => false,
            'rewrite'            => false,
            'supports'           => ['title', 'slug'],
        ];
        register_post_type(self::POST_TYPE_CUSTOM_FIELDS, $postTypeArgs);
    }

    /**
     * Add the admin submenu.
     */
    public function adminSubmenu()
    {
        $legacyPlugin = Factory::getLegacyPlugin();

        // Add the submenu to the PublishPress menu.
        add_submenu_page(
            $legacyPlugin->get_menu_slug(),
            esc_html__('Author Fields', 'publishpress-multiple-authors'),
            esc_html__('Fields', 'publishpress-multiple-authors'),
            apply_filters('pp_multiple_authors_manage_authors_cap', 'list_users'),
            'edit.php?post_type=' . self::POST_TYPE_CUSTOM_FIELDS
        );
    }

    /**
     * Add custom update messages to the post_updated_messages filter flow.
     *
     * @param array $messages Post updated messages.
     *
     * @return  array   $messages
     */
    public function setPostUpdateMessages($messages)
    {
        $messages[self::POST_TYPE_CUSTOM_FIELDS] = [
            1 => __('Custom Field updated.', 'publishpress-multiple-authors'),
            4 => __('Custom Field updated.', 'publishpress-multiple-authors'),
            6 => __('Custom Field published.', 'publishpress-multiple-authors'),
            7 => __('Custom Field saved.', 'publishpress-multiple-authors'),
            8 => __('Custom Field submitted.', 'publishpress-multiple-authors'),
        ];

        return $messages;
    }

    /**
     * Add custom update messages to the bulk_post_updated_messages filter flow.
     *
     * @param array $messages Array of messages.
     * @param array $counts   Array of item counts for each message.
     *
     * @return  array   $messages
     */
    public function setPostBulkUpdateMessages($messages, $counts)
    {
        $countsUpdated   = (int)$counts['updated'];
        $countsLocked    = (int)$counts['locked'];
        $countsDeleted   = (int)$counts['deleted'];
        $countsTrashed   = (int)$counts['trashed'];
        $countsUntrashed = (int)$counts['untrashed'];

        $postTypeNameSingular = __('Custom Field', 'publishpress-multiple-authors');
        $postTypeNamePlural   = __('Custom Fields', 'publishpress-multiple-authors');

        $messages[self::POST_TYPE_CUSTOM_FIELDS] = [
            'updated'   => sprintf(
                _n('%1$s %2$s updated.', '%1$s %3$s updated.', $countsUpdated),
                $countsUpdated,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'locked'    => sprintf(
                _n('%1$s %2$s not updated, somebody is editing it.', '%1$s %3$s updated, somebody is editing them.',
                    $countsLocked),
                $countsLocked,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'deleted'   => sprintf(
                _n('%1$s %2$s permanently deleted.', '%1$s %3$s permanently deleted.', $countsDeleted),
                $countsDeleted,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'trashed'   => sprintf(
                _n('%1$s %2$s moved to the Trash.', '%1$s %3$s moved to the Trash.', $countsTrashed),
                $countsTrashed,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
            'untrashed' => sprintf(
                _n('%1$s %2$s restored from the Trash.', '%1$s %3$s restored from the Trash.', $countsUntrashed),
                $countsUntrashed,
                $postTypeNameSingular,
                $postTypeNamePlural
            ),
        ];

        return $messages;
    }

    /**
     * Render che Custom Field admin page.
     */
    public function renderMetaboxes()
    {
        $metabox = new_cmb2_box([
            'id'           => self::META_PREFIX . 'details',
            'title'        => __('Details', 'publishpress-multiple-authors'),
            'object_types' => [self::POST_TYPE_CUSTOM_FIELDS],
            'context'      => 'normal',
            'priority'     => 'high',
            'show_names'   => true,
        ]);

        $metabox->add_field([
            'name' => __('Field Slug', 'publishpress-multiple-authors'),
            'id'   => self::META_PREFIX . 'slug',
            'type' => 'text',
            'desc' => __('The slug allows only lowercase letters, numbers and underscore. It is used as an author\'s attribute when referencing the field in the custom layouts.',
                'publishpress-multiple-authors'),
        ]);

        $fieldTypes = CustomFieldsModel::getFieldTypes();

        $metabox->add_field([
            'name'    => __('Field Type', 'publishpress-multiple-authors'),
            'id'      => self::META_PREFIX . 'type',
            'type'    => 'select',
            'options' => $fieldTypes,
        ]);

        $metabox->add_field([
            'name' => __('Description', 'publishpress-multiple-authors'),
            'desc' => __('This description appears under the fields and helps users understand their choice.',
                'publishpress-multiple-authors'),
            'id'   => self::META_PREFIX . 'description',
            'type' => 'textarea_small',
        ]);
    }

    /**
     * @param array  $fields
     * @param Author $author
     *
     * @return mixed
     */
    public function filterAuthorFields($fields, $author)
    {
        $customFields = $this->getAuthorCustomFields();

        return array_merge($fields, $customFields);
    }

    public function getAuthorCustomFields()
    {
        $posts = get_posts([
            'post_type'      => self::POST_TYPE_CUSTOM_FIELDS,
            'posts_per_page' => 100,
            'post_status'    => 'publish',
        ]);

        $fields = [];

        if ( ! empty($posts)) {
            foreach ($posts as $post) {
                $fields[$post->post_name] = [
                    'name'        => $post->post_name,
                    'label'       => $post->post_title,
                    'type'        => $this->getFieldMeta($post->ID, 'type'),
                    'description' => $this->getFieldMeta($post->ID, 'description'),
                ];
            }
        }

        return $fields;
    }

    /**
     * @param $postId
     * @param $field
     *
     * @return mixed
     */
    public function getFieldMeta($postId, $field)
    {
        return get_post_meta($postId, self::META_PREFIX . $field, true);
    }

    /**
     * @param int $termId
     */
    public function saveTermCustomField($termId)
    {
        // Get a list of custom fields to save them.
        $fields = $this->getAuthorCustomFields();

        if ( ! empty($fields)) {
            foreach ($fields as $field) {
                if (isset($_POST['authors-' . $field['name']])) {
                    $value = $_POST['authors-' . $field['name']];

                    if ($field['type'] === 'url') {
                        $value = esc_url_raw($value);
                    } elseif ($field['type'] === 'email') {
                        $value = sanitize_email($value);
                    } elseif ($field['type'] === 'wysiwyg') {
                        $value = wp_kses_post($value);
                    } else {
                        $value = sanitize_text_field($value);
                        // Remove any HTML code.
                        $value = strip_tags($value);
                    }

                    update_term_meta($termId, $field['name'], $value);
                }
            }
        }

        return;
    }

    /**
     * @param $newValue
     * @param $single
     * @param $args
     *
     * @return mixed|string|string[]|null
     */
    public function sanitizeFieldName($newValue, $single, $args)
    {
        if ($args['id'] === self::META_PREFIX . 'slug') {
            $newValue = $this->slugify($newValue);
        }

        return $newValue;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    protected function slugify($string)
    {
        $string = strtolower($string);
        $string = str_replace('-', '_', $string);
        $string = preg_replace('/[^a-z0-9_]/', '', $string);

        return $string;
    }

    /**
     * Short circuit in the remove action on CMB2, to avoid removing the
     * field name automatically set when the field is empty.
     *
     * @param $override
     *
     * @return bool
     */
    public function removePostCustomFieldSlug($override)
    {
        return true;
    }

    /**
     * Override the CMB2 meta field, to retrieve the field slug from post's post_name,
     * instead from a post meta.
     *
     * @param $data
     * @param $postId
     *
     * @return string
     */
    public function overridePostSlugMetaValue($data, $postId)
    {
        $post = get_post($postId);

        return $post->post_name;
    }

    /**
     * Save the field slug in the post_name instead of in a meta data.
     *
     * @param $override
     * @param $args
     *
     * @return bool
     */
    public function overridePostSlugMetaSave($override, $args)
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'posts',
            [
                'post_name' => sanitize_title($args['value']),
            ],
            [
                'ID' => (int)$args['id'],
            ],
            [
                '%s',
            ]
        );

        return true;
    }

    /**
     * @param array $properties
     *
     * @return array
     */
    public function filterAuthorProperties($properties)
    {
        $customFields = $this->getAuthorCustomFields();

        if ( ! empty($customFields)) {
            foreach ($customFields as $customField) {
                $properties[$customField['name']] = true;
            }
        }

        return $properties;
    }

    /**
     * @param mixed  $return
     * @param Author $authorId
     * @param string $attribute
     *
     * @return mixed
     */
    public function filterAuthorAttribute($return, $authorId, $attribute)
    {
        $customFields = $this->getAuthorCustomFields();

        if ( ! empty($customFields) && isset($customFields[$attribute])) {
            return $this->getCustomFieldValue($authorId, $attribute);
        }

        return $return;
    }

    /**
     * @param int    $authorId
     * @param string $customField
     *
     * @return mixed
     */
    protected function getCustomFieldValue($authorId, $customField)
    {
        return get_term_meta($authorId, $customField, true);
    }

    /**
     * @param $columns
     *
     * @return array
     */
    public function filterFieldColumns($columns)
    {
        // Add the first columns.
        $newColumns = [
            'cb'    => $columns['cb'],
            'title' => $columns['title'],
            'slug'  => __('Slug', 'publishpress-multiple-authors'),
        ];

        unset($columns['cb'], $columns['title']);

        // Add the remaining columns.
        $newColumns = array_merge($newColumns, $columns);

        unset($columns);

        return $newColumns;
    }

    /**
     * @param $column
     * @param $postId
     */
    public function manageFieldColumns($column, $postId)
    {
        if ($column === 'slug') {
            global $post;

            echo $post->post_name;
        }
    }

    /**
     * Make sure the layout name has not a '-' char.
     *
     * @param $slug
     * @param $postID
     * @param $postStatus
     * @param $postType
     *
     * @return string
     */
    public function fixPostSlug($slug, $postID, $postStatus, $postType)
    {
        if (self::POST_TYPE_CUSTOM_FIELDS === $postType) {
            $slug = str_replace('-', '_', $slug);
        }

        return $slug;
    }
}
