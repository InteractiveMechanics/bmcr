<?php

namespace PublishPress\Permissions\Statuses\UI\Gutenberg;

// This class is only used with PublishPress inactive. It supplies the Post Status dropdown (for custom types).
class PostEditStatus
{
    function __construct() 
    {
        add_action('admin_enqueue_scripts', [$this, 'actEnqueueScripts']);
        add_action('enqueue_block_editor_assets', [$this, 'actEnqueueBlockEditorAssets']);
    }

    /**
     * Enqueue Javascript resources that we need in the admin:
     * - Primary use of Javascript is to manipulate the post status dropdown on Edit Post and Manage Posts
     * - jQuery Sortable plugin is used for drag and dropping custom statuses
     * - We have other custom code for JS niceties
     */
    public function actEnqueueScripts()
    {
        if (!$statuses = $this->getStatuses()) {
            return;
        }

        wp_enqueue_style(
            'publishpress-custom_status-block',
            PRESSPERMIT_STATUSES_URLPATH . '/common/lib/custom-status-block-editor.css', 
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
        if (!$statuses = $this->getStatuses()) {
            return;
        }

        wp_enqueue_script(
            'presspermit-custom-status-block',
            PRESSPERMIT_STATUSES_URLPATH . '/common/lib/custom-status-block.min.js',
            ['wp-blocks', 'wp-i18n', 'wp-element', 'wp-hooks'],
            PRESSPERMIT_STATUSES_VERSION,
            true
        );

        $custom_privacy_statuses = apply_filters('presspermit_block_editor_privacy_statuses', []);
        $published_statuses = array_merge($custom_privacy_statuses, ['publish', 'private']);

        wp_localize_script(
            'presspermit-custom-status-block',
            'PPCustomStatuses',
            ['statuses' => $statuses, 'publishedStatuses' => $published_statuses]
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
    private function getStatuses($args = [], $only_basic_info = false)
    {
        global $post;
        $post_type = PWP::findPostType();

        $ordered_statuses = array_merge(
            [(object)['slug' => 'draft', 'name' => __('Draft')]],

            PPS::orderStatuses(
                array_diff_key(
                    PWP::getPostStatuses(['moderation' => true, 'post_type' => $post_type], 'object'),
                    ['future' => true]
                )
            ),

            //[(object)['slug' => 'private', 'name' => __('Private')]],

            [(object)['slug' => 'publish', 'name' => __('Published')]],
            [(object)['slug' => 'future', 'name' => __('Scheduled')]]
        );

        // compat with js usage of term properties
        foreach($ordered_statuses as $key => $status_obj) {
            if (!isset($status_obj->slug)) {
                $ordered_statuses[$key]->slug = $status_obj->name;
                $ordered_statuses[$key]->name = $status_obj->label;
                $ordered_statuses[$key]->description = '-';
                $ordered_statuses[$key]->color = '';
                $ordered_statuses[$key]->icon = '';
            }
        }

        $ordered_statuses = apply_filters('pp_custom_status_list', array_values($ordered_statuses), $post);

        if (!$ordered_statuses) {
            return [];
        }

        // compat with js usage of term properties
        foreach($ordered_statuses as $key => $status_obj) {
            if (!isset($status_obj->slug)) {
                $ordered_statuses[$key]->slug = $status_obj->name;
                $ordered_statuses[$key]->name = $status_obj->label;
                $ordered_statuses[$key]->description = '-';
                $ordered_statuses[$key]->color = '';
                $ordered_statuses[$key]->icon = '';
            }
        }

        return $ordered_statuses;
    }

    public static function loadBlockEditorStatusGuidance() 
    {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '.dev' : '';
        
        wp_enqueue_script('presspermit-object-edit', PRESSPERMIT_STATUSES_URLPATH . "/common/js/post-block-edit{$suffix}.js", ['jquery', 'jquery-form'], PRESSPERMIT_STATUSES_VERSION, true);

        //div.editor-post-publish-panel button.editor-post-publish-button

        $current_status = get_post_field('post_status', PWP::getPostID());

        if (in_array($current_status, ['', 'auto-draft'])) {
            $current_status = 'draft';
        }

        $current_status_obj = get_post_status_object($current_status);

        if ($current_status_obj && (!empty($current_status_obj->public) || !empty($current_status_obj->private))) {
            $next_status_obj = $current_status_obj;
        } else {
            $next_status_obj = PPS::defaultStatusProgression();
        }

        if (($current_status == $next_status_obj->name) || ( (!empty($current_status_obj->public) || !empty($current_status_obj->private)) && (!empty($next_status_obj->public) || !empty($next_status_obj->private)))) {
            if (!empty($next_status_obj->public) || !empty($next_status_obj->private)) {
                $publish_label = __('Update');
                $save_as_label = $publish_label;
            } else {
                $publish_label = $next_status_obj->labels->save_as;
            }
        } else {
            // secondary safeguard to ensure a valid button label
            if (!empty($next_status_obj->labels->publish)) {
                $publish_label = $next_status_obj->labels->publish;
            } elseif (!empty($next_status_obj->labels->save_as)) {
                $publish_label = $next_status_obj->labels->save_as;
            } else {
                $publish_label = __('Update');  // fallback will not happen if statuses properly defined
            }
        }

        if (!isset($save_as_label)) {
            $save_as_label = (!empty($next_status_obj->labels->save_as)) ? $next_status_obj->labels->save_as : __('Update');
        }

        $args = ['publish' => $publish_label, 'saveAs' => $save_as_label];

        if (!empty($next_status_obj->moderation)) {
            $args['prePublish'] = __('Workflow&hellip;', 'ppp');
        }

        if (!presspermit()->isContentAdministrator()) {
            $attributes = PPS::attributes();
            $post_type = PWP::findPostType();
            $user = presspermit()->getUser();

            foreach (PWP::getPostStatuses(['moderation' => true, 'post_type' => $post_type]) as $status) {
                if (PPS::haveStatusPermission('set_status', $post_type, $status)) {
                    if ($check_caps = $attributes->getConditionCaps('edit_post', $post_type, 'post_status', $status)) {
                        if (array_diff($check_caps, array_keys($user->allcaps))) {
                            $args["redirectURL{$status}"] = admin_url("edit.php?post_type={$post_type}&pp_submission_done={$status}");
                        }
                    }
                }
            }
        }

        wp_localize_script('presspermit-object-edit', 'ppObjEdit', $args);
    }
}
