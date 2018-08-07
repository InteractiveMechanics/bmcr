<?php
/**
 * @package     PublishPress\Multiple_authors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.1.0
 */

namespace PublishPress\Addon\Multiple_authors\Classes;

use PublishPress\Addon\Multiple_authors\Classes\Objects\Author;
use PublishPress\Util as PP_Util;
use WP_Error;

/**
 * Utility methods for managing authors
 *
 * Based on Bylines.
 *
 * @package PublishPress\Addon\Multiple_authors\Classes
 *
 */
class Utils {

	/**
	 * @var array
	 */
	protected static $supported_post_types = [];

	/**
	 * @var array
	 */
	protected static $pages_whitelist = [
		'post.php',
		'post-new.php',
		'edit.php',
		'edit-tags.php',
		'term.php',
		'admin.php',
	];

	/**
	 * Convert co-authors to authors on a post.
	 *
	 * Errors if the post already has authors. To re-convert, remove authors
	 * from the post.
	 *
	 * @param integer $post_id ID for the post to convert.
	 *
	 * @return object|WP_Error Result object if successful; WP_Error on error.
	 */
	public static function convert_post_coauthors( $post_id ) {
		if ( ! function_exists( 'get_coauthors' ) ) {
			return new WP_Error( 'authors_missing_cap',
				__( 'Co-Authors Plus must be installed and active.', 'publishpress-multiple-authors' ) );
		}
		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'authors_missing_post', "Invalid post: {$post_id}" );
		}
		$authors = get_the_terms( $post_id, 'author' );
		if ( $authors && ! is_wp_error( $authors ) ) {
			return new WP_Error( 'authors_post_has_authors', "Post {$post_id} already has authors." );
		}
		$authors          = [];
		$result           = new \stdClass;
		$result->created  = 0;
		$result->existing = 0;
		$result->post_id  = 0;
		$coauthors        = get_coauthors( $post_id );
		foreach ( $coauthors as $coauthor ) {
			switch ( $coauthor->type ) {
				case 'wpuser':
					$author = Author::get_by_user_id( $coauthor->ID );
					if ( $author ) {
						$authors[] = $author;
						$result->existing ++;
					} else {
						$author = Author::create_from_user( $coauthor->ID );
						if ( is_wp_error( $author ) ) {
							return $author;
						}
						$authors[] = $author;
						$result->created ++;
					}
					break;
				case 'guest-author':
					$author = Author::get_by_term_slug( $coauthor->user_nicename );
					if ( $author ) {
						$authors[] = $author;
						$result->existing ++;
					} else {
						$args   = [
							'display_name' => $coauthor->display_name,
							'slug'         => $coauthor->user_nicename,
						];
						$author = Author::create( $args );
						if ( is_wp_error( $author ) ) {
							return $author;
						}
						$ignored = [
							'ID',
							'display_name',
							'user_nicename',
							'user_login',
						];
						foreach ( $coauthor as $key => $value ) {
							if ( in_array( $key, $ignored, true ) ) {
								continue;
							}
							if ( 'linked_account' === $key ) {
								$key   = 'user_id';
								$user  = get_user_by( 'login', $value );
								$value = $user ? $user->ID : '';
							}
							if ( '' !== $value ) {
								update_term_meta( $author->term_id, $key, $value );
							}
						}
						$authors[] = $author;
						$result->created ++;
					}
					break;
			} // End switch().
		} // End foreach().
		if ( empty( $authors ) || count( $coauthors ) !== count( $authors ) ) {
			return new WP_Error( 'authors_post_missing_coauthors',
				"Failed to convert some authors for post {$post_id}." );
		}
		Utils::set_post_authors( $post_id, $authors );

		return $result;
	}

	/**
	 * Set the authors for a post
	 *
	 * @param integer $post_id ID for the post to modify.
	 * @param array   $authors Bylines to set on the post.
	 */
	public static function set_post_authors( $post_id, $authors ) {
		$authors = wp_list_pluck( $authors, 'term_id' );
		wp_set_object_terms( $post_id, $authors, 'author' );
	}

	/**
	 * Helper to only add javascript to necessary pages. Avoids bloat in admin.
	 *
	 * @return bool
	 */
	public static function is_valid_page() {
		global $pagenow;

		$valid = (bool) in_array( $pagenow, self::$pages_whitelist );


		if ( ! $valid ) {
			return false;
		}

		if ( in_array( $pagenow, [ 'edit-tags.php', 'term.php' ] ) ) {
			$taxonomy = isset( $_GET['taxonomy'] ) ? $_GET['taxonomy'] : null;

			if ( 'author' !== $taxonomy ) {
				return false;
			}
		} elseif ( in_array( $pagenow, [ 'admin.php' ] ) ) {
			if ( isset( $_GET['page'] ) && $_GET['page'] === 'pp-modules-settings' && isset( $_GET['module'] ) && $_GET['module'] === 'pp-multiple-authors-settings' ) {
				return true;
			}
		} else {
			return self::is_post_type_enabled() && self::current_user_can_set_authors();
		}

		return true;
	}

	/**
	 * Whether or not PublishPress Multiple Authors is enabled for this post type
	 * Must be called after init
	 *
	 * @since 3.0
	 *
	 * @param string $post_type The name of the post type we're considering
	 *
	 * @return bool Whether or not it's enabled
	 */
	public static function is_post_type_enabled( $post_type = null ) {
		global $publishpress;

		if ( empty( self::$supported_post_types ) ) {
			self::$supported_post_types = self::get_supported_post_types();
		}

		if ( ! $post_type ) {
			$post_type = PP_Util::get_current_post_type();
		}

		$supported = (bool) in_array( $post_type, self::$supported_post_types );

		$post_types = \PublishPress\Util::get_post_types_for_module( $publishpress->multiple_authors->module );

		$is_enabled = (bool) in_array( $post_type, $post_types );

		return $supported && $is_enabled;
	}

	/**
	 * Returns a list of post types which supports authors.
	 */
	public static function get_supported_post_types() {
		if ( empty( self::$supported_post_types ) ) {
			// Get the post types which supports authors
			$post_types_with_authors = array_values( get_post_types() );

			foreach ( $post_types_with_authors as $key => $name ) {
				if ( ! post_type_supports( $name, 'author' ) || in_array( $name, [ 'revision', 'attachment' ] ) ) {
					unset( $post_types_with_authors[ $key ] );
				}
			}

			self::$supported_post_types = apply_filters( 'coauthors_supported_post_types', $post_types_with_authors );
		}

		return self::$supported_post_types;
	}

	/**
	 * Checks to see if the current user can set authors or not
	 */
	public static function current_user_can_set_authors( $post = null ) {
		if ( ! $post ) {
			$post = get_post();
			if ( ! $post ) {
				return false;
			}
		}

		$post_type = $post->post_type;

		// TODO: need to fix this; shouldn't just say no if don't have post_type
		if ( ! $post_type ) {
			return false;
		}

		$current_user = wp_get_current_user();
		if ( ! $current_user ) {
			return false;
		}
		// Super admins can do anything
		if ( function_exists( 'is_super_admin' ) && is_super_admin() ) {
			return true;
		}

		$can_set_authors = isset( $current_user->allcaps['edit_others_posts'] ) ? $current_user->allcaps['edit_others_posts'] : false;

		return apply_filters( 'coauthors_plus_edit_authors', $can_set_authors );
	}
}
