<?php
namespace PublishPress\Permissions\FileAccess;

class AttachmentTemplate
{
    public static function regulateReadAccess()
    {
        if (defined('PP_ATTACHMENT_TEMPLATE_PASSTHRU') && PP_ATTACHMENT_TEMPLATE_PASSTHRU)
            return;

        global $post;

        if (empty($post)) {
            global $wp_query, $wpdb;

            if (!empty($wp_query->query_vars['attachment_id'])) {
                $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND ID = %d", $wp_query->query_vars['attachment_id']));

            } elseif (!empty($wp_query->query_vars['attachment']))
                $post = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_name = %d", $wp_query->query_vars['attachment']));
        }

        if (empty($post->ID) || !current_user_can('read_post', $post->ID)) {
            global $wp_query;
            $wp_query->is_single = false;
            $wp_query->is_page = false;

            if (defined('PPFF_STATUS_CODE') && is_numeric(constant('PPFF_STATUS_CODE'))) {
                $code = (!empty($post)) ? constant('PPFF_STATUS_CODE') : 404;
                status_header($code);
            } else
                $code = 404;

            if (404 == $code)
                $wp_query->is_404 = true;

            if (403 == $code)
                $wp_query->is_403 = true;

            include(get_query_template($code));

            exit();
        }
    }

    // Filter attachment page content prior to display by attachment template.
    // Note: teaser-subject direct file URL requests also land here
    public static function teaserFilter()
    {
        global $post, $wpdb;

        $pp = presspermit();

        $teaser_applied = false;

        if (empty($post)) {
            global $wp_query;

            if (!empty($wp_query->query_vars['attachment_id'])) {
                $post = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND ID = '{$wp_query->query_vars['attachment_id']}'");

            } elseif (!empty($wp_query->query_vars['attachment']))
                $post = $wpdb->get_row("SELECT * FROM $wpdb->posts WHERE post_type = 'attachment' AND post_name = '{$wp_query->query_vars['attachment']}'");
        }

        if (!empty($post)) {
            $object_type = $post->post_type; // $wpdb->get_var("SELECT post_type FROM $wpdb->posts WHERE ID = '$post->post_parent'");

            // default to 'post' object type if retrieval failed for some reason
            if (empty($object_type)) {
                $object_type = 'post';
            }

            if (defined('PRESSPERMIT_TEASER_VERSION') && !current_user_can('read_post', $post->ID)) {
                if ($pp->getOption('post_teaser_enabled')) {
                    if ($use_teaser_type = $pp->getTypeOption('tease_post_types', $object_type)) {
                        self::imposePostsTeaser($post, $object_type, $use_teaser_type);
                        $teaser_applied = true;
                    } else
                        unset($post);
                } else
                    unset($post); // WordPress generates 404 if teaser is not enabled
            }
        }

        return $teaser_applied;
    }

    static function imposePostsTeaser(&$object, $post_type, $use_teaser_type = 'fixed')
    {
        global $current_user, $wp_query;

        do_action('presspermit_teaser_init_template');

        $teaser_replace = [];
        $teaser_prepend = [];
        $teaser_append = [];

        $teaser_replace[$post_type]['post_content'] = apply_filters('presspermit_get_teaser_text', '', 'replace', 'content', 'post', $post_type, $current_user);

        $teaser_replace[$post_type]['post_excerpt'] = apply_filters('presspermit_get_teaser_text', '', 'replace', 'excerpt', 'post', $post_type, $current_user);
        $teaser_prepend[$post_type]['post_excerpt'] = apply_filters('presspermit_get_teaser_text', '', 'prepend', 'excerpt', 'post', $post_type, $current_user);
        $teaser_append[$post_type]['post_excerpt'] = apply_filters('presspermit_get_teaser_text', '', 'append', 'excerpt', 'post', $post_type, $current_user);

        $teaser_prepend[$post_type]['post_name'] = apply_filters('presspermit_get_teaser_text', '', 'prepend', 'name', 'post', $post_type, $current_user);
        $teaser_append[$post_type]['post_name'] = apply_filters('presspermit_get_teaser_text', '', 'append', 'name', 'post', $post_type, $current_user);

        $force_excerpt = [];
        $force_excerpt[$post_type] = ('excerpt' == $use_teaser_type);

        $args = [
            'teaser_prepend' => $teaser_prepend,
            'teaser_append' => $teaser_append,
            'teaser_replace' => $teaser_replace,
            'force_excerpt' => $force_excerpt
        ];

        \PublishPress\Permissions\Teaser\PostsTeaser::applyTeaser($object, 'post', $post_type, $args);

        $wp_query->is_404 = false;
        $wp_query->is_attachment = true;
        $wp_query->is_single = true;
        $wp_query->is_singular = true;
        $object->ancestors = [$object->post_parent];

        $wp_query->post_count = 1;
        $wp_query->posts[] = $object;
        $wp_query->queried_object = $object;
        $wp_query->queried_object_id = $object->ID;

        if (isset($wp_query->query_vars['error']))
            unset($wp_query->query_vars['error']);

        if (isset($wp_query->query['error']))
            $wp_query->query['error'] = '';
    }
}
