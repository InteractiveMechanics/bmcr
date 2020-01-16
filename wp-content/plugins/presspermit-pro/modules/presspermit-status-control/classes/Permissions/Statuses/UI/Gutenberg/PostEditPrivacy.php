<?php

namespace PublishPress\Permissions\Statuses\UI\Gutenberg;

// This class is only used with PublishPress inactive. It supplies the Post Status dropdown (for custom types).
class PostEditPrivacy
{
    private $custom_privacy_statuses;
    private $post_id;
    private $force_visibility;

    function __construct() 
    {
        add_action('enqueue_block_editor_assets', [$this, 'actLogPrivacyStatuses'], 9);
        add_filter('presspermit_block_editor_privacy_statuses', [$this, 'fltBlockEditorPrivacyStatuses']);

        add_action('admin_enqueue_scripts', [$this, 'actEnqueueScripts'], 11);
        add_action('enqueue_block_editor_assets', [$this, 'actEnqueueBlockEditorAssets'], 11);
    }

    public function actLogPrivacyStatuses()
    {
        $this->custom_privacy_statuses = $this->getCustomPrivacyStatuses();
    }

    public function fltBlockEditorPrivacyStatuses($statuses)
    {
        $status_names = [];

        foreach($this->custom_privacy_statuses as $status_term) {
            $status_names []= $status_term->slug;
        }

        return $status_names;
    }

    /**
     * Enqueue Javascript resources that we need in the admin:
     * - Primary use of Javascript is to manipulate the post status dropdown on Edit Post and Manage Posts
     * - jQuery Sortable plugin is used for drag and dropping custom statuses
     * - We have other custom code for JS niceties
     */
    public function actEnqueueScripts()
    {
        if (!isset($this->custom_privacy_statuses)) {
            $this->custom_privacy_statuses = $this->getCustomPrivacyStatuses();
        }

        if (!$this->custom_privacy_statuses) {
            return;
        }

        wp_enqueue_style(
            'publishpress-custom_privacy-block',
            PRESSPERMIT_STATUSES_URLPATH . '/common/lib/custom-privacy-block-editor.css', 
            false,
            PRESSPERMIT_STATUSES_VERSION, 
            'all'
        );
    }

    /**
     * Enqueue Gutenberg assets.
     */
    public function actEnqueueBlockEditorAssets()
    {
        if (!isset($this->custom_privacy_statuses)) {
            $this->custom_privacy_statuses = $this->getCustomPrivacyStatuses();
        }

        if (!$this->custom_privacy_statuses) {
            return;
        }

        $statuses = $this->mergeCoreStatuses($this->custom_privacy_statuses);

        wp_enqueue_script(
            'presspermit-custom-privacy-block',
            PRESSPERMIT_STATUSES_URLPATH . '/common/lib/custom-privacy-block.min.js',
            ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-hooks'],
            PRESSPERMIT_STATUSES_VERSION,
            true
        );

        $default_privacy = presspermit()->getTypeOption('default_privacy', PWP::findPostType());

        if ($default_privacy && presspermit()->getTypeOption('force_default_privacy', PWP::findPostType())) {
            $force = $default_privacy;
        } else {
            $force = '';
        }

        wp_localize_script(
            'presspermit-custom-privacy-block',
            'PPCustomPrivacy',
            ['statuses' => $statuses, 'ajaxURL' => admin_url(''), 'defaultPrivacy' => $default_privacy, 'forceVisibility' => $force]
        );
    }

    private function mergeCoreStatuses($statuses) 
    {
        return array_merge(
            [(object)['slug' => 'draft', 'name' => __('Draft')]],
            [(object)['slug' => 'publish', 'name' => __('Public')]],
            [(object)['slug' => 'private', 'name' => __('Private')]],
            $statuses
        );
    }

    /**
     * Get all post statuses as an ordered array
     *
     * @param array|string $statuses
     * @param array        $args
     * @param bool         $only_basic_info
     *
     * @return array $statuses All of the statuses
     */
    private function getCustomPrivacyStatuses($args = [], $only_basic_info = false)
    {
        global $post;
        $post_type = PWP::findPostType();
        if ( ! $type_obj = get_post_type_object($post_type) ) {
            return [];
        }

        $attributes = PPS::attributes();
        $is_administrator = presspermit()->isContentAdministrator();

        $statuses = PWP::getPostStatuses(['private' => true, '_builtin' => false, 'post_type' => $post_type], 'object');

        foreach($statuses as $_status => $status_obj) {
            if (!$is_administrator && ($_status != $post->post_status)) {
                if (empty($type_obj->cap->set_posts_status)) {
                    $set_status_cap = $type_obj->cap->publish_posts;
                } else {
                    $_caps = $attributes->getConditionCaps(
                        $type_obj->cap->set_posts_status, 
                        $post->post_type, 
                        'post_status', 
                        $_status
                    );

                    if (!$set_status_cap = reset($_caps)) {
                        $set_status_cap = $type_obj->cap->set_posts_status;
                    }
                }

                if (!current_user_can($set_status_cap)) {
                    unset($statuses[$_status]);
                }
            }
        }

        // compat with js usage of term properties
        foreach($statuses as $key => $status_obj) {
            if (!isset($status_obj->slug)) {
                $statuses[$key]->slug = $status_obj->name;
                $statuses[$key]->name = $status_obj->label;
                $statuses[$key]->description = '-';
                $statuses[$key]->color = '';
                $statuses[$key]->icon = '';
            }
        }

        $statuses = apply_filters('pp_custom_privacy_list', array_values($statuses), $post);

        if (!$statuses) {
            return [];
        }

        // compat with js usage of term properties
        foreach($statuses as $key => $status_obj) {
            if (!isset($status_obj->slug)) {
                $statuses[$key]->slug = $status_obj->name;
                $statuses[$key]->name = $status_obj->label;
                $statuses[$key]->description = '-';
                $statuses[$key]->color = '';
                $statuses[$key]->icon = '';
            }
        }

        return $statuses;
    }
}
