<?php
/**
 * @package PublishPress Multiple Authors Pro
 * @author  PublishPress
 *
 * Copyright (C) 2018 PublishPress
 *
 * This file is part of PublishPress Multiple Authors Pro
 *
 * PublishPress Multiple Authors Pro is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License as
 * published by the Free Software Foundation, either version 3 of the License,
 * or (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace MultipleAuthors\Classes;

use MultipleAuthors\Classes\Objects\Author;

class Installer
{
    /**
     * Runs methods when the plugin is running for the first time.
     *
     * @param string $current_version
     */
    public static function install($current_version)
    {
        self::convert_bylines_taxonomy();
        self::convert_post_author_into_taxonomy();
        self::add_author_term_for_posts();
        self::fix_author_url();
        self::flush_permalinks();
        self::create_default_layouts();
    }

    /**
     * Converts any byline taxonomy found.
     */
    public static function convert_bylines_taxonomy()
    {
        global $wpdb;

        $wpdb->update(
            $wpdb->term_taxonomy,
            [
                'taxonomy' => 'author',
            ],
            [
                'taxonomy' => 'byline',
            ]
        );
    }

    /**
     * Creates terms for users found as authors in the content.
     */
    public static function convert_post_author_into_taxonomy()
    {
        global $wpdb;

        // Get a list of authors (users) from the posts which has no terms.
        $authors = $wpdb->get_results(
            "SELECT DISTINCT p.post_author, u.display_name, u.user_nicename, u.user_email, u.user_url
				 FROM {$wpdb->posts} as p
				 LEFT JOIN {$wpdb->users} AS u ON (post_author = u.ID)
				 WHERE
				     p.post_status NOT IN ('trash') AND
				 	 p.post_author NOT IN (
					     SELECT meta.`meta_value`
						 FROM {$wpdb->terms} AS term
						 INNER JOIN {$wpdb->term_taxonomy} AS tax ON (term.`term_id` = tax.`term_id`)
						 INNER JOIN {$wpdb->termmeta} AS meta ON (term.term_id = meta.`term_id`)
						 WHERE tax.`taxonomy` = 'author'
						 AND meta.meta_key = 'user_id'
						 AND meta.meta_value <> 0
				 	)
				 	AND p.post_type = 'post'
				 	AND p.post_author <> 0
					AND u.display_name != ''
			    "
        );

        // Check if the authors have a term. If not, create one.
        if ( ! empty($authors)) {
            foreach ($authors as $author) {
                $term = wp_insert_term(
                    $author->display_name,
                    'author',
                    [
                        'slug' => $author->user_nicename,
                    ]
                );

                // Get user's description
                $description = get_user_meta($author->post_author, 'description', true);
                if (empty($description)) {
                    $description = '';
                }

                if (is_wp_error($term)) {
                    continue;
                }

                $first_name = get_user_meta($author->post_author, 'first_name', true);
                $last_name  = get_user_meta($author->post_author, 'last_name', true);

                $meta = [
                    'first_name'                      => $first_name,
                    'last_name'                       => $last_name,
                    'user_email'                      => $author->user_email,
                    'user_id_' . $author->post_author => 'user_id',
                    'user_id'                         => $author->post_author,
                    'user_url'                        => $author->user_url,
                    'description'                     => $description,
                ];

                foreach ($meta as $key => $value) {
                    add_term_meta($term['term_id'], $key, $value);
                }
            }
        }
    }

    /**
     * Add author term for posts which only have the post_author.
     */
    public static function add_author_term_for_posts()
    {
        global $wpdb;

        // Add the relationship into the term and the post
        $posts_to_update = $wpdb->get_results(
            "SELECT p.ID, p.post_author
				FROM {$wpdb->posts} as p WHERE ID NOT IN (
					SELECT DISTINCT p.ID
					FROM {$wpdb->posts} AS p
					INNER JOIN {$wpdb->termmeta} AS meta ON (p.post_author = meta.meta_value)
					INNER JOIN {$wpdb->term_taxonomy} AS tax ON (meta.term_id = tax.term_id)
					INNER JOIN {$wpdb->term_relationships} AS rel ON (tax.term_id = rel.term_taxonomy_id)
					WHERE
						p.post_status NOT IN ('trash')
						AND p.post_author <> 0
						AND p.post_type = 'post'
						AND meta.meta_key = 'user_id'
						AND tax.taxonomy = 'author'
						AND rel.object_id = p.id
				)
				AND	p.post_type = 'post'
				AND p.post_status NOT IN ('trash')"
        );

        if ( ! empty($posts_to_update)) {
            foreach ($posts_to_update as $post_data) {
                $author = Author::get_by_user_id($post_data->post_author);

                if (is_object($author)) {
                    $authors = [$author];
                    $authors = wp_list_pluck($authors, 'term_id');
                    wp_set_object_terms($post_data->ID, $authors, 'author');
                }
            }
        }
    }

    /**
     * Fix the author URL for authors with mapped users, to make sure the slug is the same.
     */
    public static function fix_author_url()
    {
        global $wpdb;

        // Get list of authors with mapped users.
        $authors = $wpdb->get_results(
            "SELECT term.term_id, meta.meta_value AS user_id, term.slug, `user`.user_nicename
						 FROM {$wpdb->terms} AS term
						 INNER JOIN {$wpdb->term_taxonomy} AS tax ON (term.term_id = tax.term_id)
						 INNER JOIN {$wpdb->termmeta} AS meta ON (term.term_id = meta.`term_id`)
						 INNER JOIN {$wpdb->users} AS `user` ON (meta.meta_value = `user`.ID)
						 WHERE tax.`taxonomy` = 'author'
						 AND meta.meta_key = 'user_id'
						 AND meta.meta_value > 0
			    "
        );

        if ( ! empty($authors)) {
            foreach ($authors as $author) {
                if ($author->slug !== $author->user_nicename) {
                    wp_update_term($author->term_id, 'author', ['slug' => $author->user_nicename]);
                }
            }
        }
    }

    /**
     * Flushes the permalinks rules.
     */
    protected static function flush_permalinks()
    {
        global $wp_rewrite;

        if (is_object($wp_rewrite)) {
            $wp_rewrite->flush_rules();
        }
    }

    /**
     * Create the default author layouts.
     */
    protected static function create_default_layouts()
    {
        \MA_Author_Custom_Layouts::createDefaultLayouts();
    }

    /**
     * Runs methods when the plugin is being upgraded to a most recent version.
     *
     * @param string $previous_version
     */
    public static function upgrade($previous_version)
    {
        if (version_compare($previous_version, '2.0.2', '<')) {
            self::convert_bylines_taxonomy();
            self::convert_post_author_into_taxonomy();
            self::add_author_term_for_posts();
            self::fix_author_url();
        }

        if (version_compare($previous_version, '2.2.0', '<')) {
            $role = get_role('administrator');
            $role->add_cap('ppma_edit_orphan_post');
        }

        if (version_compare($previous_version, '2.3.0', '<=')) {
            self::create_default_layouts();
        }

        if (version_compare($previous_version, '2.4.0', '<=')) {
            self::add_post_custom_fields();
        }

        self::flush_permalinks();
    }

    /**
     * Add custom field with authors' name on all posts.
     *
     * @since 2.4.0
     */
    protected static function add_post_custom_fields()
    {
        global $wpdb;

        // Get the authors
        $terms = static::get_all_author_terms();
        $names = static::get_terms_author_names($terms);

        // Get all different combinations of authors to make a cache and save connections to the db.
        $posts_author_names = static::get_post_author_names($names);

        // Update all posts.
        foreach ($posts_author_names as $post_id => $post_names) {
            $post_names = implode(', ', $post_names);

            update_post_meta($post_id, 'ppma_authors_name', $post_names);
        }
    }

    /**
     * Return a list with al the author terms.
     *
     * @return array
     *
     * @since 2.4.0
     */
    protected static function get_all_author_terms()
    {
        global $wpdb;

        // Get list of authors with mapped users.
        $authors = $wpdb->get_results(
            "SELECT taxonomy.term_id
						 FROM {$wpdb->term_taxonomy} AS taxonomy
						 WHERE taxonomy.`taxonomy` = 'author'
			    "
        );

        return $authors;
    }

    /**
     * Map a list of author terms to a list of author names indexed by the term id.
     *
     * @param array $authors
     *
     * @return array
     *
     * @since 2.4.0
     */
    protected static function get_terms_author_names($authors)
    {
        if (empty($authors)) {
            return;
        }

        $mappedList = [];

        foreach ($authors as $term) {
            $author = Author::get_by_term_id($term->term_id);

            $mappedList[$term->term_id] = $author->name;
        }

        return $mappedList;
    }

    /**
     * @param array $author_names
     *
     * @return array
     *
     * @since 2.4.0
     */
    protected static function get_post_author_names($author_names)
    {
        $term_ids          = array_keys($author_names);
        $combination_names = [];

        $combinations = static::get_taxonomy_ids_combinations($term_ids);
        foreach ($combinations as $combination_str) {
            $combination_list = explode(',', $combination_str->taxonomy_ids);

            $names = array_map(function ($id) use ($author_names) {
                return $author_names[$id];
            }, $combination_list);

            $combination_names[$combination_str->object_id] = $names;
        }

        return $combination_names;
    }

    /**
     *
     * @param array $term_ids
     *
     * @return mixed
     *
     * @since 2.4.0
     */
    protected static function get_taxonomy_ids_combinations($term_ids)
    {
        global $wpdb;

        $term_ids = implode(',', $term_ids);

        $ids = $wpdb->get_results(
            "SELECT object_id, group_concat(r.term_taxonomy_id) as taxonomy_ids
                FROM TrB7dXiiH_term_relationships AS r
                WHERE r.term_taxonomy_id in ({$term_ids})
                GROUP BY r.object_id
                ORDER BY r.term_order"
        );

        return $ids;
    }
}
