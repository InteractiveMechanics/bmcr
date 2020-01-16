<?php
namespace PublishPress\Permissions;

class TeaserHooks
{
    function __construct() 
    {
        add_filter('presspermit_default_options', [$this, 'fltDefaultOptions']);
        add_filter('presspermit_teaser_default_options', [$this, 'fltDefaultOptions']); // used by SettingsTabTeaser

        add_action('presspermit_admin_ui', [$this, 'actAdminFilters']);
        add_action('presspermit_post_filters', [$this, 'actPostFilters']);
        add_action('presspermit_init', [$this, 'actPressPermitInit']);

        add_filter('login_redirect', [$this, 'fltEnforceTeaserLoginRedirect'], PHP_INT_MAX - 1, 3);

        add_action('template_redirect', [$this, 'actMaybeRedirect'], 5);
    }

    function fltDefaultOptions($defaults)
    {
        $extra = [
            'rss_private_feed_mode' => 'title_only',
            'rss_nonprivate_feed_mode' => 'full_content',
            'feed_teaser' => __("View the content of this <a href='%permalink%'>article</a>"),
            'post_teaser_enabled' => true,
            'teaser_hide_thumbnail' => true,
            'teaser_hide_custom_private_only' => false,
            'teaser_hide_links_taxonomy' => '',
            'teaser_hide_links_term' => '',
            'teaser_hide_links_type' => '',
            'teaser_redirect_slug' => '',
            'teaser_redirect_anon_slug' => '',

            // object type options (support separate array element for each object type, and possible a nullstring element as default)
            'tease_post_types' => [],
            'tease_logged_only' => [],
            'tease_public_posts_only' => [],
            'tease_direct_access_only' => [],
            'tease_replace_content' => __("Sorry, this content requires additional permissions.  Please contact an administrator for help.", 'pptx'),
            'tease_replace_content_anon' => __("Sorry, you don't have access to this content.  Please log in or contact a site administrator for help.", 'pptx'),
            'tease_prepend_name' => '(',
            'tease_prepend_name_anon' => '(',
            'tease_append_name' => ')*',
            'tease_append_name_anon' => ')*',
            'tease_replace_excerpt' => '',
            'tease_replace_excerpt_anon' => '',
            'tease_prepend_excerpt' => '',
            'tease_prepend_excerpt_anon' => '',
            'tease_append_excerpt' => "<br /><small>" . __("note: This content requires a higher login level.", 'pptx') . "</small>",
            'tease_append_excerpt_anon' => "<br /><small>" . __("note: This content requires site login.", 'pptx') . "</small>",
        ];

        return array_merge($defaults, $extra);
    }

    function actPressPermitInit()
    {
        if (!defined('DOING_CRON') && PWP::isFront()) {
            //require_once(PRESSPERMIT_TEASER_CLASSPATH . '/TemplateFilters.php');
            //new Teaser\TemplateFilters();

            if (!presspermit()->isContentAdministrator() && presspermit()->getOption('post_teaser_enabled')) {
                require_once(PRESSPERMIT_TEASER_CLASSPATH . '/PostFiltersFront.php');
                new Teaser\PostFiltersFront();
            }
        }
    }

    function actPostFilters()
    {
        require_once(PRESSPERMIT_TEASER_CLASSPATH . '/PostFilters.php');
        new Teaser\PostFilters();
    }

    function actAdminFilters()
    {
        require_once(PRESSPERMIT_TEASER_CLASSPATH . '/Admin.php');
        new Teaser\Admin();
    }

    function fltEnforceTeaserLoginRedirect($redirect_to, $requested_redirect_to, $user) {
        if (!empty($_REQUEST['pp_redirect'])) {
            $redirect_to = $requested_redirect_to;
        }

        return $redirect_to;
    }

    function actMaybeRedirect()
    {
        if (defined('DOING_CRON') || !PWP::isFront()) {
            return;
        }

        $pp = presspermit();

        $opt = (is_user_logged_in()) ? 'teaser_redirect_slug' : 'teaser_redirect_anon_slug';

        if (!$redirect_slug = $pp->getOption($opt))
            return;

        if ($pp->isContentAdministrator())
            return;

        global $wp_query, $wpdb;

        if (!empty($wp_query->post)) {
            $queried_object = $wp_query->post;
        } elseif (!empty($wp_query->queried_object)) {
            $queried_object = $wp_query->queried_object;
        }

        if (!empty($queried_object) && !current_user_can('read_post', $queried_object->ID)) {
            $url = '';
            
            if ('[login]' == $redirect_slug) {
                $url = wp_login_url();

            } elseif ($redirect_post_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_name = %s", sanitize_key($redirect_slug)))) {
                if ($redirect_post_id != $queried_object->ID) {
                    $url = get_permalink($redirect_post_id);
                }
            }

            if ($url) {
                if (empty($_REQUEST['redirect_to'])) {
                    $url = add_query_arg('redirect_to', get_permalink($queried_object->ID), $url);
                    
                    if (!defined('PRESSPERMIT_TEASER_LOGIN_REDIRECT_NO_PP_ARG')) {
                        $url = add_query_arg('pp_permissions', 1, $url);
                    }
                }
                wp_redirect($url);
            }
        }
    }
}
