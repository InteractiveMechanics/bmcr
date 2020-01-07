<?php
namespace PublishPress\Permissions\FileAccess;

//use \PressShack\LibArray as Arr;

/**
 * PP_Analyst class
 *
 * @package PressPermit
 * @author Kevin Behrens <kevin@agapetry.net>
 * @copyright Copyright (c) 2011-2017, Agapetry Creations LLC
 *
 */

class Analyst
{
    public static function identifyProtectedAttachments()
    {
        global $wpdb;

        /*
        if ( defined( 'PP_NO_THUMBNAIL_FILTER' ) ) {
            if ( $thumbnail_ids = scoper_get_col( "SELECT DISTINCT meta_value FROM $wpdb->postmeta WHERE meta_key = '_thumbnail_id'" ) ) {
                $id_clause .= " AND ID NOT IN ('" . implode( "','", $thumbnail_ids ) . "')";
            }
        }
        */

        // note: currently, if a post type or taxonomy applies include exceptions, all posts will have their attachments protected

        $pp = presspermit();

        $pp_enabled_types = $pp->getEnabledPostTypes();
        $pp_enabled_taxonomies = $pp->getEnabledTaxonomies();

        $for_type_clause = "AND e.for_item_type IN ('" . implode("','", $pp_enabled_types) . "')";
        $tx_via_type_clause = "AND e.via_item_type IN ('" . implode("','", $pp_enabled_taxonomies) . "')";

        // per-post 'exclude' exceptions
        $att_where = "AND ( e.for_item_type = 'attachment' OR i.item_id IN ( SELECT post_parent FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent > 0 ) )";  // only consider post-specific exceptions on attachments or attachment parents
        $exc_posts = $wpdb->get_col("SELECT i.item_id FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id AND e.via_item_source = 'post' AND e.operation = 'read' AND e.mod_type = 'exclude' $for_type_clause $att_where");

        // per-post 'include' exceptions (To simplify rule generation queries, handle include exceptions by treating all posts of related type as protected.  userCanReadFile() will clear up any false positives.)
        $exc_types = $wpdb->get_col("SELECT DISTINCT e.for_item_type FROM $wpdb->ppc_exceptions AS e INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id AND e.via_item_source = 'post' AND e.operation = 'read' AND e.mod_type = 'include' $for_type_clause");

        // per-term 'include' exceptions
        $exc_taxonomies = $wpdb->get_results("SELECT DISTINCT e.via_item_type, e.for_item_type FROM $wpdb->ppc_exceptions AS e INNER JOIN $wpdb->ppc_exception_items AS i ON e.exception_id = i.exception_id AND e.via_item_source = 'term' AND e.operation = 'read' AND e.mod_type = 'include' $tx_via_type_clause");

        // Get protected post types from per-term 'include' exceptions
        if ($exc_taxonomies) {
            $exc_taxonomies_by_type = [];
            foreach ($exc_taxonomies as $row) {  // for_item_type is post type, via_item_type is taxonomy
                $exc_taxonomies_by_type[$row->for_item_type][$row->via_item_type] = true;
            }

            if (!empty($exc_taxonomies_by_type[''])) {
                // get all related post types for each taxonomy that has universal include exceptions
                foreach (array_keys($exc_taxonomies_by_type['']) as $taxonomy) {
                    $tx_obj = get_taxonomy($taxonomy);
                    $exc_types = array_merge($exc_types, array_intersect((array)$tx_obj->object_type, $pp_enabled_types));
                }

                unset($exc_taxonomies_by_type['']);
            }

            $exc_types = array_merge($exc_types, array_intersect(array_keys($exc_taxonomies_by_type), $pp_enabled_types));
        }

        // per-term 'exclude' exceptions
        $exc_terms = $wpdb->get_results("SELECT i.item_id, e.for_item_type FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id AND e.via_item_source = 'term' AND e.operation = 'read' AND e.mod_type = 'exclude' $tx_via_type_clause");

        // convert per-term excludes to per-post
        if ($exc_terms) {
            $term_assoc_where = [];

            $exc_terms_by_type = [];
            foreach ($exc_terms as $row) {
                $exc_terms_by_type[$row->for_item_type][$row->item_id] = true;
            }

            if (isset($exc_terms_by_type[''])) {
                $term_assoc_where [] = "tr.term_taxonomy_id IN (" . implode(",", array_map('intval', array_keys($exc_terms_by_type['']))) . ")";

                // if a term has an exclude exception for all post types, no need to also query for specific types
                $universal_terms = $exc_terms_by_type[''];
                unset($exc_terms_by_type['']);
            } else
                $universal_terms = [];

            foreach (array_keys($exc_terms_by_type) as $for_type) {
                if ($exc_terms_by_type[$for_type] = array_diff_key($exc_terms_by_type[$for_type], $universal_terms)) {
                    $term_assoc_where [] = "p.post_type = '$for_type' AND tr.term_taxonomy_id IN (" . implode(",", array_map('intval', array_keys($exc_terms_by_type[$for_type]))) . ")";
                }
            }

            $term_assoc_where = 'AND ' . Arr::implode(' OR ', $term_assoc_where);
            $att_where = "AND ( p.post_type = 'attachment' OR p.ID IN ( SELECT post_parent FROM $wpdb->posts WHERE post_type = 'attachment' ) )";
            $exc_posts = array_merge($exc_posts, $wpdb->get_col("SELECT ID FROM $wpdb->posts AS p INNER JOIN $wpdb->term_relationships AS tr ON p.ID = tr.object_id WHERE 1=1 $term_assoc_where $att_where"));
        }

        $exc_clause = '';

        if ($exc_types)
            $exc_clause .= " OR post_type IN ('" . implode("','", array_unique($exc_types)) . "')";

        if ($exc_posts)
            $exc_clause .= " OR ID IN (" . implode(",", array_map('intval', array_unique($exc_posts))) . ")";

        $stati_in = implode("','", array_merge(
            get_post_stati(['private' => true]),
            get_post_stati(['moderation' => true]),
            ['draft', 'pending', 'future', 'trash']
        ));

        // TODO: settings UI, query sanitization
        $exclude_mime_types = (defined('PPFF_EXCLUDE_MIME_TYPES')) ? " AND post_mime_type NOT IN ('" . implode("','", explode(",", PPFF_EXCLUDE_MIME_TYPES)) . "')" : '';
        $include_mime_types = (defined('PPFF_INCLUDE_MIME_TYPES')) ? " AND post_mime_type IN ('" . implode("','", explode(",", PPFF_INCLUDE_MIME_TYPES)) . "')" : '';

        // Special Case: per-file 'additional' exceptions for wp_all metagroup.  These will override implicit privacy (based on parent post) as long as no corresponding per-post exclude exceptions are directly set for the file.
        $all_group = $pp->groups()->getMetagroup('wp_role', 'wp_all');

        $public_attachments = $wpdb->get_col("SELECT i.item_id FROM $wpdb->ppc_exception_items AS i INNER JOIN $wpdb->ppc_exceptions AS e ON e.exception_id = i.exception_id AND e.agent_type = 'pp_group' AND e.agent_id = $all_group->ID AND e.via_item_source = 'post' AND e.operation = 'read' AND e.mod_type = 'additional' AND ( e.for_item_type = 'attachment' ) $for_type_clause");
        if ($public_attachments = array_diff($public_attachments, $exc_posts)) {
            $public_files_clause = " AND ID NOT IN ('" . implode("','", $public_attachments) . "')";
        } else {
            $public_files_clause = '';
        }

        // ==== account for parent post permissions ====
        $results = $wpdb->get_results("SELECT ID, guid, post_mime_type FROM $wpdb->posts WHERE post_type = 'attachment' $exclude_mime_types $include_mime_types $public_files_clause AND ( post_parent IN ( SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_status IN ('$stati_in') $exc_clause ) ) ORDER BY ID DESC", OBJECT_K);

        // ==== account for direct file exceptions ====
        if ($pp->getOption('unattached_files_private') && $pp->getOption('attached_files_private'))
            $attachment_clause = ' OR 1=1';
        elseif ($pp->getOption('unattached_files_private'))
            $attachment_clause = ' OR post_parent < 1';
        elseif ($pp->getOption('attached_files_private'))
            $attachment_clause = ' OR post_parent > 0';
        else
            $attachment_clause = '';

        if ($_results = $wpdb->get_results("SELECT ID, guid, post_mime_type FROM $wpdb->posts WHERE post_type = 'attachment' $exclude_mime_types $include_mime_types $public_files_clause AND ( ID IN ( SELECT $wpdb->posts.ID FROM $wpdb->posts WHERE $wpdb->posts.post_status IN ('$stati_in') $exc_clause ) $attachment_clause ) ORDER BY ID DESC", OBJECT_K)) {
            $results = $results + $_results;
        }

        if ($results) {
            $uploads_ok = (($uploads = wp_upload_dir()) && false === $uploads['error']);

            if ($uploads_ok) {
                $attached_file_meta = $wpdb->get_results("SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_attached_file' AND post_id IN ('" . implode("','", array_keys($results)) . "')");

                // now replace guid with _wp_attached_file postmeta, if stored and valid 
                foreach ($attached_file_meta AS $row) {
                    $filename = $row->meta_value;

                    if (false === strpos($filename, $uploads['baseurl']))
                        $filename = trailingslashit($uploads['baseurl']) . $row->meta_value;

                    if (file_exists(str_replace($uploads['baseurl'], $uploads['basedir'], $filename)))
                        $results[$row->post_id]->guid = $filename;
                }
            }
        }

        return $results;
    }
    
    public static function exceptionsAffectAttachments($eitem)
    {
        if (('post' == $eitem['for_item_source']) && ('read' == $eitem['operation']) && ('additional' != $eitem['mod_type'])) {
            if ('post' == $eitem['via_item_source']) {
                if ('attachment' == $eitem['for_item_type'])  // exception assigned directly to an attachment
                    return true;

                if (!$eitem['item_id'])  // exception assigned for "none"
                    return true;

                static $posts_with_attachment;
                if (!isset($posts_with_attachment)) {
                    global $wpdb;
                    $posts_with_attachment = $wpdb->get_col("SELECT DISTINCT post_parent FROM $wpdb->posts WHERE post_type = 'attachment' AND post_parent > 0");
                }

                if (in_array($eitem['item_id'], $posts_with_attachment)) {
                    return true;
                }
            } elseif ('term' == $eitem['via_item_source']) {
                return true;
            }
        }

        return false;
    }
}
