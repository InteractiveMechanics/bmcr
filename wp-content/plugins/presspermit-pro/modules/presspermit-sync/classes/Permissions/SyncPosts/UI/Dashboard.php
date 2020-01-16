<?php

namespace PublishPress\Permissions\SyncPosts\UI;

class Dashboard
{
    function __construct() {
        add_action('admin_init', [$this, 'revealAuthorCol']);
        add_action('admin_init', [$this, 'actHandleUpdatePPOptions']);
        add_action('admin_notices', [$this, 'actNotices']);
    }

    function actHandleUpdatePPOptions()
    {
        if (did_action('presspermit_update_options') && !empty($_POST['sync_posts_to_users_existing'])) {
            SyncPosts::handlePrivateTypes();
            SyncPosts::userSync()->syncPostsToUsers(['post_types' => array_keys($_POST['sync_posts_to_users_existing'])]);
        }
    }
    
    function actNotices()
    {
        global $pagenow, $typenow;

        if ((in_array($pagenow, ['plugins.php', 'index.php', 'users.php'])
            || ('edit.php' == $pagenow && (false !== strpos($typenow, 'team') || false !== strpos($typenow, 'staff')))
            || 'presspermit-settings' == presspermitPluginPage()) && presspermit()->isUserAdministrator()) {

            if (presspermit()->getOption('sync_posts_to_users')) {
                require_once(PRESSPERMIT_SYNC_CLASSPATH . '/UI/Helper.php');
                Helper::teamPluginNotices();
            }
        }
    }

    function revealAuthorCol()
    {
        if (!presspermit()->getOption('sync_posts_to_users')) {
            return;
        }

        SyncPosts::handlePrivateTypes();

        if ($enabled_types = SyncPosts::getEnabledTypes()) {
            //if ( presspermit()->getOption( 'reveal_author_col' ) ) {
            if (!defined('presspermit_sync_posts_REVEAL_AUTHOR_COL')) {

                $support_types = (array)apply_filters(
                    'presspermit_sync_posts_reveal_author_col_types', 
                    ['jv_team_members', 'tmm']
                );

                $support_types = array_intersect($enabled_types, $support_types);
            }
            foreach ($support_types as $post_type) {
                add_post_type_support($post_type, 'author');
            }
            //}
        }
    }
}
