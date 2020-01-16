<?php
namespace PublishPress\Permissions\Statuses;

class PostSave
{
    public static function fltPostStatus($post_status)
    {
        if ('_pending' == $post_status) {
            $save_as_pending = true;
            $post_status = 'pending';
        }

        if (!$post_id = PWP::getPostID()) {
            return $post_status;
        }

        if (defined('REVISIONARY_VERSION') && (
            in_array($post_status,['pending-revision', 'future-revision']))
            || (!empty($_POST) && (!empty($_REQUEST['page']) && ('rvy-revisions' == $_REQUEST['page']))
        )) {
            return $post_status;
        }

        // Important: if other plugin code inserts additional posts in response, don't filter those
        static $done;                               // @todo: Review (3/2019)
        if (!empty($done)) return $post_status;  
        $done = true;

        $is_administrator = presspermit()->isContentAdministrator();

        $_post = get_post($post_id);

        if ($stored_status = get_post_field('post_status', $post_id)) {
            $stored_status_obj = get_post_status_object($stored_status);
        }

        $selected_status = (!empty($_POST['post_status'])) ? sanitize_key($_POST['post_status']) : $post_status;

        if (('publish' == $selected_status) && !empty($_POST['visibility'])) {
            $selected_status = sanitize_key($_POST['visibility']);
        }

        if ('public' == $selected_status) {
            $selected_status = 'publish';
        }

        // inline edit: apply keep_status checkbox selection
        if ($_post && !empty($_POST['action']) && ('inline-save' == $_POST['action'])) {
            foreach (PWP::getPostStatuses(['private' => true, 'post_type' => $_post->post_type]) as $_status) {
                if (!empty($_POST["keep_{$_status}"])) {
                    $selected_status = $_status;
                    break;
                }
            }
        }

        if (!$post_status_obj = get_post_status_object($selected_status)) {
            return $post_status;
        }

        $post_status = $selected_status;

        // Scheduled Post handling (Classic Editor)  @todo: Gutenberg
        if (!defined('REST_REQUEST')) {
            if (!empty($post_status_obj->private)) {
                $_POST['post_password'] = '';

                if (isset($_POST['sticky']))
                    unset($_POST['sticky']);
            }

            if ($post_status_obj->public || $post_status_obj->private) {
                if (!empty($_POST['post_date_gmt']))
                    $post_date_gmt = $_POST['post_date_gmt'];
                elseif (!empty($_POST['aa'])) {
                    foreach (['aa' => 'Y', 'mm' => 'n', 'jj' => 'j', 'hh' => '', 'mn' => '', 'ss' => ''] as $var => $format) {
                        $$var = (!$format || $_POST[$var] > 0) ? $_POST[$var] : date($format);
                    }
                    $post_date = sprintf("%04d-%02d-%02d %02d:%02d:%02d", $aa, $mm, min($jj, 31), min($hh, 23), min($mn, 59), 0);
                    $post_date_gmt = get_gmt_from_date($post_date);
                }

                // set status to future if a future date was selected with a private status
                $now = gmdate('Y-m-d H:i:59');
                if (!empty($post_date_gmt) && mysql2date('U', $post_date_gmt, false) > mysql2date('U', $now, false)) {
                    update_post_meta($post_id, '_scheduled_status', $post_status);
                    $post_status = 'future';
                } else {
                    // if a post is being transitioned from scheduled to published/private, apply scheduled status
                    $_post = get_post($post_id);
                    if ('future' == $_post->post_status) {  // stored status is future
                        if ($_status = get_post_meta($post_id, '_scheduled_status', true)) {
                            $post_status = $_status;
                            $post_status_obj = get_post_status_object($post_status);
                        }

                        delete_post_meta($post_id, '_scheduled_status');
                    }
                }
            }
        }

        // Allow Publish / Submit button to trigger our desired workflow progression instead of Publish / Pending status.
        // Apply this change only if stored post is not already published or scheduled.
        // Also skip retain normal WP editor behavior if the newly posted status is privately published or future.
        if (
            //empty($post_status_obj->private) && !in_array($post_status, ['future', 'private'])
            ('pending' == $selected_status) && !in_array($stored_status, ['public', 'private', 'future']) 
            && empty($stored_status_obj->public) && empty($stored_status_obj->private)
        ) {
            // Gutenberg REST gives no way to distinguish between Publish and Save request. Treat as Publish (next workflow progression) if any of the following:
            //  * user cannot set pending status
            //  * already set to pending status

            global $current_user;

            $doing_rest = defined('REST_REQUEST') && (!empty($_REQUEST['meta-box-loader']) || presspermit()->doingREST());

            if ( ($stored_status == 'pending') || !PPS::haveStatusPermission('set_status', $_post->post_type, 'pending') 
                || ! defined('REST_REQUEST') 
                || ! presspermit()->doingREST()
            ) {
                if (($doing_rest && empty($save_as_pending)) || (!empty($_POST) && !empty($_POST['publish']))) { //} && ! empty( $_POST['pp_submission_status'] ) ) { 
                    // Classic Editor previous behavior: explicitly posted hidden input (was permission-checked downstream)
                    //$post_status = $_POST['pp_submission_status'];

                    // Submission status inferred using same logic as UI generation (including permission check)
                    $post_status = PPS::defaultStatusProgression($post_id, ['return' => 'name']);
                }

                $filtered_status = apply_filters('presspermit_selected_moderation_status', $post_status, $post_id);

                if (($filtered_status != $post_status) 
                && PPS::haveStatusPermission('set_status', $_post->post_type, $filtered_status)
                ) {
                    $post_status = $filtered_status;
                }
            }

        } elseif(in_array($post_status, ['publish', 'private'])) {
            if ( $default_privacy = presspermit()->getTypeOption('default_privacy', $_post->post_type)) {
                if (get_post_status_object($default_privacy)) {                    
                    if ( $stored_status = get_post_meta($post_id, '_pp_original_status') ) {
                        $stored_status_obj = get_post_status_object($stored_status);
                    }

                    if (empty($stored_status_obj) || (empty($stored_status_obj->public) && empty($stored_status_obj->private))) {
                        $post_status = $default_privacy;

                        delete_post_meta($post_id, '_pp_original_status');
                    }
                }
            }

            // Final permission check to cover all other custom statuses (draft, publish and private status capabilities are already checked by WP)
        } elseif (!$is_administrator && ($post_status != $stored_status) 
        && !in_array($post_status, ['draft', 'publish', 'private']) 
        && !PPS::haveStatusPermission('set_status', $_post->post_type, $post_status)
        ) {
            
            $post_status = ($stored_status) ? $stored_status : 'draft';
        }

        return $post_status;
    }

    // If a public or private status is selected, change it to the specified force_visibility status
    public static function flt_force_visibility($status)
    {
        if (!$status_obj = get_post_status_object($status))
            return $status;

        static $done;                          // @todo: review (3/2019)
        if (!empty($done)) return $status;  // Important: if other plugin code inserts additional posts in response, don't filter those
        $done = true;

        if ($status_obj->public || $status_obj->private) {
            if (!$post_id = PWP::getPostID()) {
                return $status;
            }
            
            $_post = get_post($post_id);

            if (empty($_POST) || empty($_post) || !is_object($_post))
                return $status;

            if (!empty($_POST)) {
                if (!empty($_POST['post_password'])) return $status;
            } elseif ($_post && $_post->post_password)
                return $status;

            if (presspermit()->getTypeOption('force_default_privacy', $_post->post_type)) {
                if ($forced_default = presspermit()->getTypeOption('default_privacy', $_post->post_type)) {

                    if (false /*(defined('PRESSPERMIT_STATUSES_VERSION') && version_compare(PRESSPERMIT_STATUSES_VERSION, '2.7-beta', '<')) 
                    && PWP::is-BlockEditorActive($_post->post_type)*/
                    ) {
                        // @todo: Force default privacy with Gutenberg
                    } else {
                        return $forced_default;
                    }
                }
            }

            if ($is_hierarchical = is_post_type_hierarchical($_post->post_type)) {
                // Since force_visibility is always a propagating condition and the parent setting may be in flux too, 
                // check setting for parent instead of post
                if (!empty($_POST) && isset($_POST['parent_id']))
                    $parent_id = apply_filters('pre_post_parent', $_POST['parent_id']);
                elseif ($_post)
                    $parent_id = $_post->post_parent;
            }

            if (!$is_hierarchical || !empty($parent_id)) {
                // also poll force_visibility for non-hierarchical types to support PPCE forcing default visibility
                $attributes = PPS::attributes();

                $_args = ($is_hierarchical) 
                ? ['id' => $parent_id, 'assign_for' => 'children'] 
                : ['default_only' => true, 'post_type' => $_post->post_type];
                
                if ($force_status = $attributes->getItemCondition('post', 'force_visibility', $_args)) {
                    $status = $force_status;
                }
            }
        }

        return $status;
    }

    // called by AdminFilters::mnt_save_object
    // This handler is meant to fire whenever an object is inserted or updated.
    public static function actSavePost($post_id, $object = '')
    {
        if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            || (!empty($_REQUEST['action']) && ('untrash' == $_REQUEST['action']))
            || ('revision' == $object->post_type)  // operations in this function do not apply to revision save
        ) {
            return;
        }

        if (defined('REVISIONARY_VERSION')) {
            global $revisionary;
            if (!empty($revisionary->admin->revision_save_in_progress)) {
                $revisionary->admin->revision_save_in_progress = false;
                return;
            }
        }

        if (is_post_type_hierarchical($object->post_type)) {
            $set_subpost_visibility = false;
            
            if (presspermit()->doingREST()) {
                $rest = \PublishPress\Permissions\REST::instance();
                $set_subpost_visibility = isset($rest->params['pp_subpost_visibility']) ? $rest->params['pp_subpost_visibility'] : false;
            } elseif(empty($_POST) || PWP::isBlockEditorActive($object->post_type)) {
                return;
            } else {
                $set_subpost_visibility = isset($_POST['ch_visibility']) ? $_POST['ch_visibility'] : false;
            }

            if (false !== $set_subpost_visibility) {
                $set_subpost_visibility = sanitize_key($set_subpost_visibility);
                
               // require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/ItemSave.php');
                //ItemSave::propagate_post_visibility($post_id, $set_subpost_visibility);

                require_once(PRESSPERMIT_STATUSES_CLASSPATH . '/ItemSave.php');
                ItemSave::post_update_force_visibility($object, ['children' => $set_subpost_visibility]);
            }
        }
    }
}
