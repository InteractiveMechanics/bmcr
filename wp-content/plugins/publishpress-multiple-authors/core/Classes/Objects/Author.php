<?php
/**
 * @package     PublishPress\Multiple_authors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.1.0
 */

namespace PublishPress\Addon\Multiple_authors\Classes\Objects;

use WP_Error;

/**
 * Representation of an individual author.
 */
class Author {

	/**
	 * ID for the correlated term.
	 *
	 * @var integer
	 */
	private $term_id;

	/**
	 * Instantiate a new author object
	 *
	 * Authors are always fetched by static fetchers.
	 *
	 * @param integer $term_id ID for the correlated term.
	 */
	private function __construct( $term_id ) {
		$this->term_id = (int) $term_id;
	}

	/**
	 * Create a new author object from an existing WordPress user.
	 *
	 * @param WP_User|integer $user WordPress user to clone.
	 *
	 * @return Author|WP_Error
	 */
	public static function create_from_user( $user ) {
		if ( is_int( $user ) ) {
			$user = get_user_by( 'id', $user );
		}
		if ( ! is_a( $user, 'WP_User' ) ) {
			return new WP_Error( 'missing-user', __( "User doesn't exist", 'publishpress-multiple-authors' ) );
		}
		$existing = self::get_by_user_id( $user->ID );
		if ( $existing ) {
			return new WP_Error( 'existing-author',
				__( 'User already has a author.', 'publishpress-multiple-authors' ) );
		}
		$author = self::create(
			[
				'display_name' => $user->display_name,
				'slug'         => $user->user_nicename,
			]
		);
		if ( is_wp_error( $author ) ) {
			return $author;
		}

		self::update_author_from_user( $author->term_id, $user->ID );

		return $author;
	}

	/**
	 * Get a author object based on its user id.
	 *
	 * @param integer $user_id ID for the author's user.
	 *
	 * @return Author|false
	 */
	public static function get_by_user_id( $user_id ) {
		global $wpdb;

		$term_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT te.term_id
				 FROM {$wpdb->termmeta} AS te
				 LEFT JOIN {$wpdb->term_taxonomy} AS ta ON (te.term_id = ta.term_id)
				 WHERE  ta.taxonomy = 'author' AND meta_key=%s",
				'user_id_' . $user_id
			)
		);

		if ( ! $term_id ) {
			return false;
		}

		return new Author( $term_id );
	}

	/**
	 * Create a new author object
	 *
	 * @param array $args Arguments with which to create the new object.
	 *
	 * @return Author|WP_Error
	 */
	public static function create( $args ) {
		if ( empty( $args['slug'] ) ) {
			return new WP_Error( 'missing-slug',
				__( "'slug' is a required argument", 'publishpress-multiple-authors' ) );
		}
		if ( empty( $args['display_name'] ) ) {
			return new WP_Error( 'missing-display_name',
				__( "'display_name' is a required argument", 'publishpress-multiple-authors' ) );
		}
		$term = wp_insert_term(
			$args['display_name'], 'author', [
				'slug' => $args['slug'],
			]
		);
		if ( is_wp_error( $term ) ) {
			return $term;
		}
		$author = new Author( $term['term_id'] );

		return $author;
	}

	/**
	 * Update the author's data based on the user's data.
	 *
	 * @param $term_id
	 * @param $user_id
	 */
	public static function update_author_from_user( $term_id, $user_id ) {
		$user = get_user_by( 'id', (int) $user_id );

		if ( empty( $user ) || is_wp_error( $user ) ) {
			return;
		}

        wp_update_term( $term_id, 'author', [
            'slug' => $user->user_nicename,
        ]);

		// Clone applicable user fields.
		$user_fields = [
			'first_name',
			'last_name',
			'user_email',
			'user_login',
			'user_url',
			'description',
		];
		update_term_meta( $term_id, 'user_id', $user->ID );
		foreach ( $user_fields as $field ) {
			update_term_meta( $term_id, $field, $user->$field );
		}
	}

	/**
	 * Get a author object based on its term id.
	 *
	 * @param integer $term_id ID for the author term.
	 *
	 * @return Author|false
	 */
	public static function get_by_term_id( $term_id ) {
		return new Author( $term_id );
	}

	/**
	 * Get a author object based on its term slug.
	 *
	 * @param string $slug Slug for the author term.
	 *
	 * @return Author|false
	 */
	public static function get_by_term_slug( $slug ) {
		$term = get_term_by( 'slug', $slug, 'author' );
		if ( ! $term || is_wp_error( $term ) ) {
			return false;
		}

		return new Author( $term->term_id );
	}

	/**
	 * @param $name
	 *
	 * @return bool
	 */
	public function __isset( $name ) {
		$properties = get_object_vars( $this );

		$properties['link']          = true;
		$properties['user_nicename'] = true;
		$properties['name']          = true;
		$properties['slug']          = true;
		$properties['user_email']    = true;
		$properties['description']   = true;
		$properties['user_url']      = true;
		$properties['user_id']       = true;
		$properties['ID']            = true;
		$properties['first_name']    = true;
		$properties['last_name']     = true;
		$properties['user_url']      = true;
		$properties['user_email']    = true;

		return array_key_exists( $name, $properties );
	}

	/**
	 * Get an object attribute.
	 *
	 * @param string $attribute Attribute name.
	 *
	 * @return mixed
	 */
	public function __get( $attribute ) {

		// Underscore prefix means protected.
		if ( '_' === $attribute[0] ) {
			return null;
		}

		if ( 'ID' === $attribute ) {
			return $this->user_id;
		}

		if ( 'term_id' === $attribute ) {
			return $this->term_id;
		}

		if ( 'link' === $attribute ) {
			$user_id = get_term_meta( $this->term_id, 'user_id', true );

			// Is a user mapped to this author?
			if ( ! empty( $user_id ) ) {
				return get_author_posts_url( $user_id );
			}

			return get_term_link( $this->term_id, 'author' );
		}

		// These two fields are actually on the Term object.
		if ( 'display_name' === $attribute ) {
			$attribute = 'name';
		}

		if ( 'user_nicename' === $attribute ) {
			$attribute = 'slug';
		}

		if ( in_array( $attribute, [ 'name', 'slug' ], true ) ) {
			return get_term_field( $attribute, $this->term_id, 'author', 'raw' );
		}

		return get_term_meta( $this->term_id, $attribute, true );
	}

}
