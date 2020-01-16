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

use MultipleAuthors\Classes\FieldType\Code as CodeFieldType;
use MultipleAuthors\Classes\Legacy\Module;
use MultipleAuthors\Factory;

/**
 * class MA_Author_Custom_Layouts
 */
class MA_Author_Custom_Layouts extends Module
{
    /**
     * Post Type.
     */
    const POST_TYPE_LAYOUT = 'ppmacf_layout';

    /**
     * Meta data prefix.
     */
    const META_PREFIX = 'ppmacflt_';

    public $module_name = 'author_custom_layouts';

    /**
     * Instance of the module
     *
     * @var stdClass
     */
    public $module;

    /**
     * @var array
     */
    protected $customFields = null;

    /**
     * Construct the MA_Multiple_Authors class
     */
    public function __construct()
    {
        $this->module_url = $this->get_module_url(__FILE__);

        // Register the module with PublishPress
        $args = [
            'title'                => __('Author Layouts', 'publishpress-multiple-authors'),
            'short_description'    => __('Add support for custom layouts in the author boxes.',
                'publishpress-multiple-authors'),
            'extended_description' => __('Add support for custom layouts in the author boxes.',
                'publishpress-multiple-authors'),
            'module_url'           => $this->module_url,
            'icon_class'           => 'dashicons dashicons-edit',
            'slug'                 => 'author-custom-layouts',
            'default_options'      => [
                'enabled' => 'on',
            ],
            'options_page'         => false,
            'autoload'             => true,
        ];

        // Apply a filter to the default options
        $args['default_options'] = apply_filters('MA_Author_Custom_Layouts_default_options', $args['default_options']);

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
        add_filter('pp_multiple_authors_author_layouts', [$this, 'filterAuthorLayouts'], 20);
        add_action('add_meta_boxes', [$this, 'addHelpMetabox']);
        add_filter('pp_multiple_authors_author_box_html', [$this, 'filterAuthorBoxHtml'], 10, 2);
        add_filter('manage_edit-' . self::POST_TYPE_LAYOUT . '_columns', [$this, 'filterLayoutColumns']);
        add_action('manage_' . self::POST_TYPE_LAYOUT . '_posts_custom_column', [$this, 'manageLayoutColumns'], 10, 2);
        add_filter('wp_unique_post_slug', [$this, 'fixPostSlug'], 10, 4);
        add_filter('cmb2_override_' . self::META_PREFIX . 'layout_meta_value', [$this, 'overrideLayoutMetaValue'], 10,
            2);
        add_filter('cmb2_override_' . self::META_PREFIX . 'layout_meta_save', [$this, 'overrideLayoutMetaSave'], 10, 2);
        add_filter('cmb2_override_' . self::META_PREFIX . 'slug_meta_value', [$this, 'overridePostSlugMetaValue'], 10,
            2);
        add_filter('cmb2_override_' . self::META_PREFIX . 'slug_meta_save', [$this, 'overridePostSlugMetaSave'], 10, 2);
        add_action('admin_init', [$this, 'handle_action']);

        add_action('multiple_authors_create_default_layouts', [$this, 'createDefaultLayouts']);

        CodeFieldType::addHooks();

        $this->registerPostType();
    }

    /**
     * Register the post types.
     */
    private function registerPostType()
    {
        $labelSingular = __('Author Layout', 'publishpress-multiple-authors');
        $labelPlural   = __('Author Layouts', 'publishpress-multiple-authors');

        $postTypeLabels = [
            'name'                  => _x('%2$s', 'Author Layout post type name', 'publishpress-multiple-authors'),
            'singular_name'         => _x('%1$s', 'singular author layout post type name',
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
            'menu_name'             => _x('%2$s', 'custom layout post type menu name', 'publishpress-multiple-authors'),
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
            'has_archive'        => self::POST_TYPE_LAYOUT,
            'hierarchical'       => false,
            'rewrite'            => false,
            'supports'           => ['title'],
        ];
        register_post_type(self::POST_TYPE_LAYOUT, $postTypeArgs);
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
            esc_html__('Author Layouts', 'publishpress-multiple-authors'),
            esc_html__('Layouts', 'publishpress-multiple-authors'),
            apply_filters('pp_multiple_authors_manage_authors_cap', 'list_users'),
            'edit.php?post_type=' . self::POST_TYPE_LAYOUT
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
        $messages[self::POST_TYPE_LAYOUT] = [
            1 => __('Author Layout updated.', 'publishpress-multiple-authors'),
            4 => __('Author Layout updated.', 'publishpress-multiple-authors'),
            6 => __('Author Layout published.', 'publishpress-multiple-authors'),
            7 => __('Author Layout saved.', 'publishpress-multiple-authors'),
            8 => __('Author Layout submitted.', 'publishpress-multiple-authors'),
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

        $postTypeNameSingular = __('Author Layout', 'publishpress-multiple-authors');
        $postTypeNamePlural   = __('Author Layouts', 'publishpress-multiple-authors');

        $messages[self::POST_TYPE_LAYOUT] = [
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
     * Render che Author Layout admin page.
     */
    public function renderMetaboxes()
    {
        $metabox = new_cmb2_box([
            'id'           => self::META_PREFIX . 'details',
            'title'        => __('Details', 'publishpress-multiple-authors'),
            'object_types' => [self::POST_TYPE_LAYOUT],
            'context'      => 'normal',
            'priority'     => 'high',
            'show_names'   => true,
        ]);

        $metabox->add_field([
            'name' => __('Layout Slug', 'publishpress-multiple-authors'),
            'id'   => self::META_PREFIX . 'slug',
            'type' => 'text',
            'desc' => __('The slug allows only lowercase letters, numbers and underscore. It is used for referencing the layout in shortcodes, hooks and functions.',
                'publishpress-multiple-authors'),
        ]);

        $metabox->add_field([
            'name' => __('Layout Code', 'publishpress-multiple-authors'),
            'desc' => __('You can use Twig syntax here.',
                'publishpress-multiple-authors'),
            'id'   => self::META_PREFIX . 'layout',
            'type' => 'code',
        ]);
    }

    /**
     * @param $layouts
     *
     * @return array
     */
    public function filterAuthorLayouts($layouts)
    {
        $customLayouts = self::getCustomLayouts();

        return $layouts + $customLayouts;
    }

    /**
     * @return array
     */
    public static function getCustomLayouts()
    {
        $posts = get_posts([
            'post_type'      => self::POST_TYPE_LAYOUT,
            'posts_per_page' => 100,
            'post_status'    => 'publish',
        ]);

        $layouts = [];

        if ( ! empty($posts)) {
            foreach ($posts as $post) {
                $layouts[$post->post_name] = $post->post_title;
            }
        }

        return $layouts;
    }

    /**
     * @param $html
     * @param $args
     *
     * @return string
     */
    public function filterAuthorBoxHtml($html, $args)
    {
        $layoutName = sanitize_text_field($args['layout']);

        $layouts = get_posts([
            'post_type'      => self::POST_TYPE_LAYOUT,
            'name'           => $layoutName,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
        ]);

        if (empty($layouts)) {
            return '';
        }

        $layout = $layouts[0];

        $container  = Factory::get_container();
        $layoutCode = html_entity_decode($layout->post_content);

        $twig = $container['twig']->createTemplate($layoutCode);
        $html = $twig->render($args);

        return $html;
    }

    public function addHelpMetabox()
    {
        add_meta_box(
            'ppmacflt-doc',
            __('Help', 'publishpress-multiple-authors'),
            [$this, 'renderHelpMetabox'],
            self::POST_TYPE_LAYOUT,
            'side'
        );
    }

    public function renderHelpMetabox()
    {
        $customFields = $this->getCustomFields();
        ?>
        <p>
            <?php printf(
                /* translators: The param is a link for Twig documentation */
                __('The author layout accepts HTML and %s code. Javascript code is not accepted.',
                    'publishpress-multiple-authors'),
                '<a href="https://twig.symfony.com/doc/1.x/" target="_blank">Twig v1.x</a>');
            ?>
        </p>

        <h3><?php echo __('Show author details:', 'publishpress-multiple-authors'); ?></h3>
        <ul>
            <li>display_name: <?php echo __('Name', 'publishpress-multiple-authors'); ?></li>
            <li>link: <?php echo __('URL for the Author\'s page', 'publishpress-multiple-authors'); ?></li>
            <li>description: <?php echo __('Description', 'publishpress-multiple-authors'); ?></li>
            <li>user_email: <?php echo __('Email address', 'publishpress-multiple-authors'); ?></li>
            <li>user_url: <?php echo __('Author\'s website URL', 'publishpress-multiple-authors'); ?></li>

            <?php
            if ( ! empty($customFields)) {
                foreach ($customFields as $customField) {
                    ?>
                    <li><?php echo $customField['name']; ?>: <?php echo $customField['label']; ?></li>
                    <?php
                }
            }
            ?>
        </ul>

        <h3><?php echo __('Show avatar:', 'publishpress-multiple-authors'); ?></h3>
        <ul>
            <li>get_avatar(size)|raw</li>
        </ul>

        <hr>

        <p><?php printf(__('Check the %s for additional variables and more detailed information.'), '<a href="https://publishpress.com/knowledge-base/custom-layouts/">' . __('documentation', 'publishpress-multiple-authors') . '</a>'); ?></p>
        <?php
    }

    /**
     * @return array
     */
    protected function getCustomFields()
    {
        if (is_null($this->customFields)) {
            $customFields = [];

            $legacyPlugin = Factory::getLegacyPlugin();

            if (isset($legacyPlugin->author_custom_fields)) {
                $customFieldsModule = $legacyPlugin->author_custom_fields;

                $customFields = $customFieldsModule->getAuthorCustomFields();
            }

            $this->customFields = $customFields;
        }

        return $this->customFields;
    }

    /**
     * @param $columns
     *
     * @return array
     */
    public function filterLayoutColumns($columns)
    {
        // Add the first columns.
        $newColumns = [
            'cb'    => $columns['cb'],
            'title' => $columns['title'],
            'name'  => __('Name', 'publishpress-multiple-authors'),
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
    public function manageLayoutColumns($column, $postId)
    {
        if ($column === 'name') {
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
        if (self::POST_TYPE_LAYOUT === $postType) {
            $slug = str_replace('-', '_', $slug);
        }

        return $slug;
    }

    /**
     * Override the CMB2 meta field, to retrieve the layout from post's content,
     * instead from a post meta.
     *
     * @param $data
     * @param $postId
     *
     * @return string
     */
    public function overrideLayoutMetaValue($data, $postId)
    {
        $post = get_post($postId);

        return $post->post_content;
    }

    /**
     * Save the layout code in the post content instead of in a meta data.
     *
     * @param $override
     * @param $args
     *
     * @return bool
     */
    public function overrideLayoutMetaSave($override, $args)
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->prefix . 'posts',
            [
                'post_content' => wp_unslash($args['value']),
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
     * Override the CMB2 meta field, to retrieve the layout slug from post's post_name,
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
     * Save the layout slug in the post_name instead of in a meta data.
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

    public function handle_action()
    {
        $actions = [
            'create_default_layouts',
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

        return true;
    }

    /**
     * Create default layouts in the database.
     */
    public static function createDefaultLayouts()
    {
        $defaultLayouts = [
            'boxed'         => __('Boxed', 'publishpress-multiple-authors'),
            'centered'      => __('Centered', 'publishpress-multiple-authors'),
            'inline'        => __('Inline', 'publishpress-multiple-authors'),
            'inline_avatar' => __('Inline with Avatars', 'publishpress-multiple-authors'),
            'simple_list'   => __('Simple List', 'publishpress-multiple-authors'),
        ];

        foreach ($defaultLayouts as $name => $title) {
            self::createLayoutPost($name, $title);
        }
    }

    /**
     * Create the layout based on a twig file with the same name.
     *
     * @param string $name
     * @param string $title
     */
    protected static function createLayoutPost($name, $title)
    {
        // Check if we already have the layout based on the slug.
        $layouts = self::getCustomLayouts();

        if (isset($layouts[$name])) {
            return;
        }

        $content = file_get_contents(PP_MULTIPLE_AUTHORS_BASE_PATH . 'twig/author_layout/' . $name . '.twig');

        wp_insert_post([
            'post_type'    => MA_Author_Custom_Layouts::POST_TYPE_LAYOUT,
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_name'    => sanitize_title($name),
        ]);
    }
}
