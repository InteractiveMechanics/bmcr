<?php
namespace PublishPress\Permissions\Compat\WPML;

class Hooks
{
    private $elem_types;
    private $lang_ids;

    function __construct()
    {
        add_filter('presspermit_default_options', [$this, 'fltOptions'], 20);

        // Language Role Implementation
        add_action('save_post', [$this, 'actClearTranslatorId'], 48, 2);

        // for mirroring of source post roles to translations
        add_action('save_post', [$this, 'actMirrorPostExceptions'], 50, 2);

        // already applying separate mirror on post save, regardless of role modification
        if (false === strpos($_SERVER['REQUEST_URI'], 'post.php') 
        && false === strpos($_SERVER['REQUEST_URI'], 'post-new.php')
        ) {
            // Use this instead of 'save_post' action due to execution order issues.
            add_action('presspermit_exception_items_updated', [$this, 'actExceptionItemsUpdated'], 10, 2);

            //add_action('presspermit_removed_exception_item', [$this, 'actRemovedExceptionItem'], 10, 2);
        }

        add_filter('presspermit_exception_item_update_hooks', [$this, 'fltExceptionItemUpdateHooks']);
    }

    function fltExceptionItemUpdateHooks($do_hooks)
    {
        return true;
    }

    // PP Option specifies whether post and term roles should be automatically mirrored to translations.  
    // Default: true (options UI on Editing tab)
    function fltOptions($options)
    {
        $options['mirror_post_translation_exceptions'] = 1;
        $options['mirror_term_translation_exceptions'] = 1;
        return $options;
    }

    private function mirrorEnabled($item_source)
    {
        $option_name = "mirror_{$item_source}_translation_exceptions";
        return class_exists('\PublishPress\Permissions\Compat.php') && presspermit()->getOption($option_name);
    }

    function actMirrorPostExceptions($post_id, $post_obj)
    {
        if (empty($post_obj))
            return;

        if (in_array($post_obj->post_type, ['revision', 'attachment']) || ('auto-draft' == $post_obj->post_status))
            return;

        if (!$this->mirrorEnabled('post'))
            return;

        global $sitepress, $wpdb;

        if (empty($sitepress))
            return;

        // debug
        //$trid = $sitepress->get_element_trid( $post_id, 'post_' . $post_obj->post_type );
        
        $el_type = 'post_' . $post_obj->post_type;
        $tr_obj = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}icl_translations WHERE element_id = %d AND element_type = %s LIMIT 1", 
                $post_id, 
                $el_type
            )
        );

        if ($tr_obj) {
            $def_lang = $sitepress->get_default_language();

            if ($tr_obj->language_code == $def_lang) {
                // editing main language post
                global $wpdb;

                $source_id = $post_id;
                $target_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type = %s"
                        . " AND trid = %d AND translation_id != %d", 
                        
                        "post_{$post_obj->post_type}", 
                        $tr_obj->trid, 
                        $tr_obj->translation_id
                    )
                );
            } elseif (!is_null($tr_obj->source_language_code) && ($tr_obj->language_code != $tr_obj->source_language_code)) {
                if (!$source_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type = %s"
                        . " AND trid = %d AND language_code = %s LIMIT 1", 
                        
                        "post_{$post_obj->post_type}", 
                        $tr_obj->trid, 
                        $def_lang
                    )
                )) {
                    return;
                }

                // Mirror source post's roles and conditions to this translation, but only if it hasn't already been done 
                // (mirror to all translations occurs on source post edit).
                if ($mirrored_to_translations = (array)get_post_meta($source_id, '_pp_wpml_mirrored_exceptions', true)) {
                    if (in_array($post_id, $mirrored_to_translations))
                        return;
                }

                $target_ids = $post_id;
            }

            if (!empty($target_ids)) {
                \PublishPress\Permissions\Compat\mirrorExceptionItems('post', $source_id, $target_ids, '_pp_wpml_mirrored_exceptions');
            }
        }
    }

    private function mirrorTermExceptions($term_id, $tt_id, $taxonomy)
    {
        if (!$this->mirrorEnabled('term'))
            return;

        global $sitepress;

        if (empty($sitepress))
            return;

        $trid = $sitepress->get_element_trid($tt_id, 'tax_' . $taxonomy);

        if ($trid) {
            global $wpdb;
            $translation_ids = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT element_id FROM {$wpdb->prefix}icl_translations"
                    . " WHERE trid = %d AND element_type = %s AND language_code != source_language_code", 
                    
                    $trid, 
                    "tax_{$taxonomy}"
                )
            );

            if (!in_array($tt_id, $translation_ids)) {
                // editing main language term
                $parent_id = $tt_id;
                $target_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type = %s"
                        . " AND trid = %d AND element_id != %d", "tax_{$taxonomy}", 
                        
                        $trid, 
                        $tt_id
                    )
                );
            } else {
                $parent_id = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE element_type = %s"
                        . " AND trid = %d AND language_code = source_language_code", 
                        
                        "tax_{$taxonomy}", 
                        $trid
                    )
                );
                $target_ids = $tt_id;
            }

            \PublishPress\Permissions\Compat\mirrorExceptionItems('term', $parent_id, $target_ids);
        }
    }

    /*
    function actRemovedExceptionItem($eitem_id, $obj)
    {
        $this->actExceptionItemsUpdated($obj->via_item_source, $obj->item_id);
    }
    */

    function actExceptionItemsUpdated($item_source, $item_id)
    {
        if (!$this->mirrorEnabled($item_source) || !$item_id)
            return;

        if ('post' == $item_source) {
            $post = get_post($item_id);

            $this->actMirrorPostExceptions($item_id, $post);

        } elseif ('term' == $item_source) {
            global $wpdb;
            $term = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT term_taxonomy_id, term_id, taxonomy from $wpdb->term_taxonomy WHERE term_taxonomy_id = %d", 
                    $item_id
                )
            );

            $this->mirrorTermExceptions($term->term_id, $item_id, $term->taxonomy);
        }
    }

    // set translator_id to zero to enable editing based on Language roles (but only if current translator is local)
    function actClearTranslatorId($post_id, $post_obj)
    {
        global $iclTranslationManagement, $current_user, $wpdb;

        if (in_array($post_obj->post_type, ['revision', 'attachment'])) {
            if (!$post_obj = get_post($post_obj->post_parent))
                return;

            $post_id = $post_obj->ID;
        }

        if (empty($iclTranslationManagement) || empty($_REQUEST['icl_trid']) || empty($_REQUEST['icl_post_language'])) {
            return;
        }

        if (!$job_id = $iclTranslationManagement->get_translation_job_id($_REQUEST['icl_trid'], $_REQUEST['icl_post_language'])) {
            return;
        }

        // this is done on init
        $iclTranslationManagement->force_job_retrieval = true;

        $el_type = 'post_' . $post_obj->post_type;

        if ($tr_obj = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}icl_translations AS t"
                . " INNER JOIN {$wpdb->prefix}icl_translation_status AS ts ON t.translation_id = ts.translation_id"
                . " WHERE t.element_id= %d AND t.element_type = %s AND ts.translation_service = 'local' LIMIT 1", 
                
                $post_id, 
                $el_type
            )
        )) {    
            if ($job = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}icl_translate_job WHERE job_id = %d", 
                    $job_id
                )
            )) {
                if (($job->translator_id != $current_user->ID) 
                || ($tr_obj->translator_id && ($tr_obj->translator_id != $current_user->ID))
                ) {
                    if (!defined('WPML_TM_FOLDER'))
                        define('WPML_TM_FOLDER', '');  // WPML throws PHP warnings otherwise

                    // WPML PHP warnings on notification
                    //$iclTranslationManagement->assign_translation_job( (int) $job_id, 0, 'local' );

                    $wpdb->update("{$wpdb->prefix}icl_translate_job", ['translator_id' => 0], ['job_id' => $job_id]);
                }
            }
        }
    }
}
