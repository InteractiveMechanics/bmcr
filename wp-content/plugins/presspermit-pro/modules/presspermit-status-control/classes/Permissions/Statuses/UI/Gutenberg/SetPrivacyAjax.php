<?php

namespace PublishPress\Permissions\Statuses\UI\Gutenberg;

/*
 * Prevent Gutenberg from imposing "Are you sure you want to unpublish?" dialog and switching to draft status 
 * when user saves to a custom privacy status while post is currently in 'publish' / 'private'
 * 
 * Under this condition, we set that post status immediately on dropdown selection via Ajax.
 * After the ajax request completes, we call wp.data.dispatch('core/editor').refreshPost(); 
 */
class SetPrivacyAjax
{
    public function __construct() 
    {
        global $wpdb;

        if (empty($_REQUEST['post_id'])) {
            // no need for error handling because worst case on update failure is Gutenberg imposes "Are you sure?" and sets to draft
            exit;
        }

        if (!$post = get_post($_REQUEST['post_id'])) {
            exit;
        }

        $set_status = $_REQUEST['pp_ajax_set_privacy'];

        // If post is already set to requested status, indicate success.
        if ($post->post_status == $set_status) {
            echo $set_status;
            exit;
        }

        // This Gutenberg workaround is only needed and supported when the post currently has 'publish' or 'private' status.
        if (!in_array($post->post_status, ['publish', 'private'])) {
            exit;
        }

        // This Gutenberg workaround is only needed and supported for privacy statuses registered for this post type.
        $valid_statuses = PWP::getPostStatuses(['private' => true, 'post_type' => $post->post_type]);
        if (!in_array($set_status, $valid_statuses)) {
            exit;
        }

        // Make sure logged user is allowed to edit the post
        if (!current_user_can('edit_post', $post->ID)) {
            exit;
        }

        // Make sure logged user is allowed to set the requested post status
        if (!PPS::haveStatusPermission('set_status', $post->post_type, $set_status)) {
            exit;
        }

        wp_update_post(['ID' => $post->ID, 'post_status' => $set_status]);
        $post = get_post($post->ID);
        echo $post->post_status;

        exit;
    }
}
