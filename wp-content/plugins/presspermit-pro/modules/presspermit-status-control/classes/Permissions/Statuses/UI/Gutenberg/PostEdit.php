<?php
namespace PublishPress\Permissions\Statuses\UI\Gutenberg;

class PostEdit
{
    function __construct() 
    {
        add_action('rest_api_init', [$this, 'act_status_control_scripts']);

        // Gutenberg Block Editor support for workflow status progression guidance / limitation
        add_action('enqueue_block_editor_assets', [$this, 'act_status_guidance_scripts']);

        // NOTE: 'pp_custom_status_list' filter is applied by PublishPress (if active) or by class PostEditStatus
        require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/PostEdit.php');
        add_filter('pp_custom_status_list', ['PublishPress\Permissions\Statuses\UI\PostEdit', 'flt_publishpress_status_list'], 50, 2);

        add_action('admin_enqueue_scripts', [$this, 'act_replace_publishpress_scripts'], 50);
    }

    // If PressPermit permissions filtering is enabled for this post type, load additional js to support it
    public function act_status_control_scripts() {
        if (self::isPostTypeEnabled()) {
            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Gutenberg/PostEditStatus.php');
            new PostEditStatus();

            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Gutenberg/PostEditPrivacy.php');
            new PostEditPrivacy();

            if (is_post_type_hierarchical(PWP::findPostType())) {
                require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Gutenberg/PostEditPrivacySub.php');
                new PostEditPrivacySub();
            }
        }
    }

	// If PressPermit permissions filtering is disabled for post type, don't load custom js for status dropdown and button labeling
    // Note, though, that 'pp_custom_status_list' filter still applies any per-type status availability set in Permissions > Post Statuses
    private static function isPostTypeEnabled($post_type = '') {
        global $post;

        $post_type = (!empty($post)) ? $post->post_type : get_post_field('post_type', PWP::getPostID());

        return in_array($post_type, presspermit()->getEnabledPostTypes(), true);
    }

    // If PressPermit permissions filtering is enabled for this post type, replace certain PublishPress scripts with a permissions-aware equivalent
    public function act_replace_publishpress_scripts()
    {
        if (self::isPostTypeEnabled()) {
            wp_dequeue_style('publishpress-custom_status-block');

            wp_enqueue_style(
                'publishpress-custom_status-block',
                PRESSPERMIT_STATUSES_URLPATH . '/lib/custom-status-block-editor.css', 
                false,
                PRESSPERMIT_STATUSES_VERSION,
                'all'
            );

            wp_dequeue_script('pp-custom-status-block');
        }
    }

    // If PressPermit permissions filtering is enabled for this post type and the user may be limited, load scripts to support status progression guidance
    public function act_status_guidance_scripts()
    {
        // Administrators don't need this script
        if (!presspermit()->isContentAdministrator() && self::isPostTypeEnabled()) {

            // Post Type Editors and Post Type Authors editing own post don't need this script
            $user = presspermit()->getUser();
            $user_caps = $user->allcaps;

            if ($post_type = PWP::findPostType()) {
                if ($post_type_obj = get_post_type_object($post_type)) {
                    if (!empty($user_caps[$post_type_obj->cap->publish_posts])) {
                        if (!$have_edit_cap = !empty($user_caps[$post_type_obj->cap->edit_others_posts])) {
                            if ($post_id = PWP::getPostID()) {
                                if ($post = get_post($post_id)) {
                                    if ($post->post_author == $user->ID) {
                                        $have_edit_cap = !empty($user_caps[$post_type_obj->cap->edit_posts]);
                                    }
                                }
                            }
                        }

                        if ($have_edit_cap) {
                            return;
                        }
                    }
                }
            }

            require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/UI/Gutenberg/PostEditStatus.php');
            PostEditStatus::loadBlockEditorStatusGuidance();
        }
    }
}
