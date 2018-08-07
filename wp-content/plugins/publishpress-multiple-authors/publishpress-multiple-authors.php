<?php
/**
 * PublishPress Multiple Authors plugin bootstrap file.
 *
 * @link        https://publishpress.com/multiple-authors/
 * @package     PublishPress\multiple_authors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 *
 * @publishpress-multiple-authors
 * Plugin Name: PublishPress Multiple Authors
 * Plugin URI:  https://publishpress.com/
 * Version: 2.1.3
 * Description: Add support for multiple authors
 * Author:      PublishPress
 * Author URI:  https://publishpress.com
 *
 * Based on Co-Authors Plus
 *  - Author: Mohammad Jangda, Daniel Bachhuber, Automattic
 *  - Copyright: 2008-2015 Shared and distributed between  Mohammad Jangda, Daniel Bachhuber, Weston Ruter
 */

use PublishPress\Addon\Multiple_authors\Classes\Objects\Author;
use PublishPress\Addon\Multiple_authors\Classes\Utils;
use PublishPress\Addon\Multiple_authors\Traits\Author_box;
use PublishPress\Notifications\Traits\Dependency_Injector;
use PublishPress\Util as PP_Util;

require_once __DIR__ . '/includes.php';

if ( defined( 'PP_MULTIPLE_AUTHORS_LOADED' ) ) {

	class PP_Multiple_authors_plugin {

		use Dependency_Injector;
		use Author_box;

		// Name for the taxonomy we're suing to store relationships
		// and the post type we're using to store co-authors
		public $coauthor_taxonomy = 'author';

		public $coreauthors_meta_box_name = 'authordiv';

		public $coauthors_meta_box_name = 'coauthorsdiv';

		public $gravatar_size = 25;

		public $ajax_search_fields = [
			'display_name',
			'first_name',
			'last_name',
			'user_login',
			'user_nicename',
			'ID',
			'user_email',
		];

		public $having_terms = '';

		/**
		 * __construct()
		 */
		public function __construct() {

			// Register our models
			add_action( 'init', [ $this, 'action_init' ] );
			add_action( 'init', [ $this, 'action_init_late' ], 100 );

			// Installation hooks
			add_action( 'pp_multiple_authors_install',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Installer', 'install' ] );
			add_action( 'pp_multiple_authors_upgrade',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Installer', 'upgrade' ] );

			add_action( 'init', [ $this, 'manage_installation' ], 2000 );

			// Load admin_init function
			add_action( 'admin_init', [ $this, 'admin_init' ] );

			add_filter( 'get_usernumposts', [ $this, 'filter_count_user_posts' ], 10, 2 );
			add_filter( 'get_authornumposts', [ $this, 'filter_count_author_posts' ], 10, 2 );

			// Filter to allow coauthors to edit posts
			add_filter( 'user_has_cap', [ $this, 'filter_user_has_cap' ], 10, 3 );

			// Restricts WordPress from blowing away term order on bulk edit
			add_filter( 'wp_get_object_terms', [ $this, 'filter_wp_get_object_terms' ], 10, 4 );

			// Support for Edit Flow's calendar and story budget
			add_filter( 'ef_calendar_item_information_fields', [ $this, 'filter_ef_calendar_item_information_fields' ],
				10,
				2 );
			add_filter( 'ef_story_budget_term_column_value', [ $this, 'filter_ef_story_budget_term_column_value' ], 10,
				3 );

			// Support Jetpack Open Graph Tags
			add_filter( 'jetpack_open_graph_tags', [ $this, 'filter_jetpack_open_graph_tags' ], 10, 2 );

			// Filter to send comment moderation notification e-mail to multiple authors
			add_filter( 'comment_moderation_recipients', 'cap_filter_comment_moderation_email_recipients', 10, 2 );

			// Delete CoAuthor Cache on Post Save & Post Delete
			add_action( 'save_post', [ $this, 'clear_cache' ] );
			add_action( 'delete_post', [ $this, 'clear_cache' ] );
			add_action( 'set_object_terms', [ $this, 'clear_cache_on_terms_set' ], 10, 6 );

			$plugin = new PublishPress\Addon\Multiple_authors\Classes\Plugin;
			$plugin->init();

			// Widget support
			add_action( 'widgets_init', [ $this, 'action_widget_init' ] );

			// Author box to the content
			add_filter( 'the_content', [ $this, 'filter_the_content' ] );

			// Shortcodes
			add_shortcode( 'author_box', [ $this, 'shortcode_author_box' ] );

			// Action to display the author box
			add_action( 'pp_multiple_authors_show_author_box', [ $this, 'action_echo_author_box' ] );

			add_action( 'publishpress_admin_submenu', [ $this, 'action_admin_submenu' ], 50 );

			/*
			 * @todo: Improve hooks to only add them if post type is selected or if it is an admin page.
			 */

			// Fix the author page.
			// Use posts_selection since it's after WP_Query has built the request and before it's queried any posts
			add_filter( 'posts_selection', [ $this, 'fix_author_page' ] );
			add_action( 'the_post', [ $this, 'fix_post' ], 10, 2 );

			// Init Bylines features
			add_action( 'init', [
				'PublishPress\\Addon\\Multiple_authors\\Classes\\Content_Model',
				'action_init_late_register_taxonomy_for_object_type',
			], 100 );
			add_filter( 'term_link',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Content_Model', 'filter_term_link' ], 10, 3 );
			add_filter( 'author_link',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Content_Model', 'filter_author_link' ], 10, 3 );
			add_filter( 'the_author_display_name',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Content_Model', 'filter_author_display_name' ], 10, 2 );
			add_filter( 'update_term_metadata',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Content_Model', 'filter_update_term_metadata' ], 10,
				4 );
			add_action( 'parse_request',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Content_Model', 'action_parse_request' ] );

			// Admin customizations.
			add_action( 'admin_init',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Post_Editor', 'action_admin_init' ] );
			add_filter( 'manage_edit-author_columns',
				[
					'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor',
					'filter_manage_edit_author_columns',
				] );
			add_filter( 'list_table_primary_column',
				[
					'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor',
					'filter_list_table_primary_column',
				] );
			add_filter( 'manage_author_custom_column',
				[
					'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor',
					'filter_manage_author_custom_column',
				],
				10, 3 );
			add_filter( 'user_row_actions',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor', 'filter_user_row_actions' ], 10, 2 );
			add_filter( 'author_row_actions',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor', 'filter_author_row_actions' ], 10,
				2 );
			add_action( 'author_edit_form_fields',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor', 'action_author_edit_form_fields' ] );
			add_action( 'edited_author',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor', 'action_edited_author' ] );
			add_action( 'user_register',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor', 'action_user_register' ], 20 );
			add_action( 'author_term_new_form_tag',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor', 'action_new_form_tag' ], 10 );
			add_filter( 'wp_insert_term_data',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor', 'filter_insert_term_data' ], 10, 3 );
			add_filter( 'created_author',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor', 'action_created_author' ], 10, 2 );

			add_filter( 'bulk_actions-edit-author',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor', 'filter_author_bulk_actions' ] );
			add_filter( 'handle_bulk_actions-edit-author',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor', 'handle_author_bulk_actions' ], 10,
				3 );
			add_action( 'admin_notices',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Author_Editor', 'admin_notices' ] );

			// Query modifications.
			add_action( 'pre_get_posts',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Query', 'action_pre_get_posts' ] );
			add_filter( 'posts_where',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Query', 'filter_posts_where' ],
				10, 2 );
			add_filter( 'posts_join', [ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Query', 'filter_posts_join' ],
				10,
				2 );
			add_filter( 'posts_groupby',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Query', 'filter_posts_groupby' ], 10, 2 );

			// Author search
			add_action( 'wp_ajax_authors_search',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Admin_Ajax', 'handle_authors_search' ] );
			add_action( 'wp_ajax_authors_users_search',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Admin_Ajax', 'handle_users_search' ] );
			add_action( 'wp_ajax_author_create_from_user',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Admin_Ajax', 'handle_author_create_from_user' ] );
			add_action( 'wp_ajax_author_get_user_data',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Admin_Ajax', 'handle_author_get_user_data' ] );

			// Post integration
			add_action( 'add_meta_boxes',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Post_Editor', 'action_add_meta_boxes_late' ], 100 );
			add_action( 'save_post',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Post_Editor', 'action_save_post_authors_metabox' ],
				10,
				2 );
			add_action( 'save_post',
				[
					'PublishPress\\Addon\\Multiple_authors\\Classes\\Post_Editor',
					'action_save_post_set_initial_author',
				],
				10, 3 );

			// Notification Workflow support
			add_filter( 'pp_get_author_data',
				[ 'PublishPress\\Addon\\Multiple_authors\\Classes\\Content_Model', 'filter_pp_get_author_data' ], 10,
				3 );

			// Theme template tag filters.
			add_filter( 'get_the_archive_title',
				[
					'PublishPress\\Addon\\Multiple_authors\\Classes\\Integrations\\Theme',
					'filter_get_the_archive_title',
				] );
			add_filter( 'get_the_archive_description', [
				'PublishPress\\Addon\\Multiple_authors\\Classes\\Integrations\\Theme',
				'filter_get_the_archive_description',
			] );
		}

		/**
		 * Manages the installation detecting if this is the first time this module runs or is an upgrade.
		 * If no version is stored in the options, we treat as a new installation. Otherwise, we check the
		 * last version. If different, it is an upgrade or downgrade.
		 */
		public function manage_installation() {
			$option_name = 'publishpress_multiple_authors_version';

			$previous_version = get_option( $option_name );
			$current_version  = PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION;

			if ( empty( $previous_version ) ) {
				/**
				 * Action called when the module is installed.
				 *
				 * @param string $current_version
				 */
				do_action( 'pp_multiple_authors_install', $current_version );
			} elseif ( version_compare( $previous_version, $current_version, '>' ) ) {

				/**
				 * Action called when the module is downgraded.
				 *
				 * @param string $previous_version
				 */
				do_action( 'pp_multiple_authors_downgrade', $previous_version );
			} elseif ( version_compare( $previous_version, $current_version, '<' ) ) {

				/**
				 * Action called when the module is upgraded.
				 *
				 * @param string $previous_version
				 */
				do_action( 'pp_multiple_authors_upgrade', $previous_version );
			}

			if ( $current_version !== $previous_version ) {
				update_option( $option_name, $current_version, true );
			}
		}

		/**
		 * Register the taxonomy used for managing relationships,
		 * and the custom post type to store the author data.
		 */
		public function action_init() {

			// Allow PublishPress Multiple Authors to be easily translated
			load_plugin_textdomain( 'publishpress-multiple-authors', null,
				dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

			// Maybe automatically apply our template tags
			if ( apply_filters( 'coauthors_auto_apply_template_tags', false ) ) {
				global $multiple_authors_addon_template_filters;
				$multiple_authors_addon_template_filters = new Multiple_Authors_Template_Filters;
			}
		}

		/**
		 * Register the 'author' taxonomy and add post type support
		 */
		public function action_init_late() {

			// Check if the module is enabled, before register the taxonomy.
			$options = get_option( 'publishpress_multiple_authors_options', null );

			if ( ! is_object( $options ) ) {
				return;
			}

			if ( ! isset( $options->enabled ) || $options->enabled !== 'on' ) {
				return;
			}

			// Register new taxonomy so that we can store all of the relationships
			$args = [
				'labels'             => [
					'name'                       => _x( 'Authors', 'taxonomy general name',
						'publishpress-multiple-authors' ),
					'singular_name'              => _x( 'Author', 'taxonomy singular name',
						'publishpress-multiple-authors' ),
					'search_items'               => __( 'Search authors', 'publishpress-multiple-authors' ),
					'popular_items'              => __( 'Popular authors', 'publishpress-multiple-authors' ),
					'all_items'                  => __( 'All authors', 'publishpress-multiple-authors' ),
					'parent_item'                => __( 'Parent author', 'publishpress-multiple-authors' ),
					'parent_item_colon'          => __( 'Parent author:', 'publishpress-multiple-authors' ),
					'edit_item'                  => __( 'Edit author', 'publishpress-multiple-authors' ),
					'view_item'                  => __( 'View author', 'publishpress-multiple-authors' ),
					'update_item'                => __( 'Update author', 'publishpress-multiple-authors' ),
					'add_new_item'               => __( 'New author', 'publishpress-multiple-authors' ),
					'new_item_name'              => __( 'New author', 'publishpress-multiple-authors' ),
					'separate_items_with_commas' => __( 'Separate authors with commas',
						'publishpress-multiple-authors' ),
					'add_or_remove_items'        => __( 'Add or remove authors', 'publishpress-multiple-authors' ),
					'choose_from_most_used'      => __( 'Choose from the most used authors',
						'publishpress-multiple-authors' ),
					'not_found'                  => __( 'No authors found.', 'publishpress-multiple-authors' ),
					'menu_name'                  => __( 'Author', 'publishpress-multiple-authors' ),
					'back_to_items'              => __( 'Back to Authors', 'publishpress-multiple-authors' ),
				],
				'public'             => false,
				'hierarchical'       => false,
				'sort'               => true,
				'args'               => [
					'orderby' => 'term_order',
				],
				'capabilities'       => [
					'manage_terms' => 'list_users',
					'edit_terms'   => 'list_users',
					'delete_terms' => 'list_users',
					'assign_terms' => 'edit_others_posts',
				],
				'show_ui'            => true,
				'show_in_quick_edit' => false,
				'meta_box_cb'        => false,
                'query_var'          => 'ppma_author',
                'rewrite'            => false,
			];

			// If we use the nasty SQL query, we need our custom callback. Otherwise, we still need to flush cache.
			if ( ! apply_filters( 'coauthors_plus_should_query_post_author', true ) ) {
				add_action( 'edited_term_taxonomy', [ $this, 'action_edited_term_taxonomy_flush_cache' ], 10, 2 );
			}

			$supported_post_types = Utils::get_supported_post_types();
			register_taxonomy( $this->coauthor_taxonomy, $supported_post_types, $args );
		}

		/**
		 * Initialize the plugin for the admin
		 */
		public function admin_init() {
			// Add the main JS script and CSS file
			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

			// Add quick-edit author select field
			add_action( 'quick_edit_custom_box', [ $this, '_action_quick_edit_custom_box' ], 10, 2 );

			// Hooks to modify the published post number count on the Users WP List Table
			add_filter( 'manage_users_columns', [ $this, '_filter_manage_users_columns' ] );

			// Apply some targeted filters
			add_action( 'load-edit.php', [ $this, 'load_edit' ] );
		}

		/**
		 * Add the authors admin menu.
		 */
		public function action_admin_submenu() {
			global $submenu;

			$publishpress = $this->get_service( 'publishpress' );

			// Check if the author module is enabled before add the submenu.
			$options = get_option( 'publishpress_multiple_authors_options', null );

			if ( ! is_object( $options ) ) {
				return;
			}

			if ( ! isset( $options->enabled ) || $options->enabled !== 'on' ) {
				return;
			}

			// Remove the submenus automatically added to post types menus.
			remove_submenu_page( 'edit.php', 'edit-tags.php?taxonomy=author' );
			remove_submenu_page( 'edit.php?post_type=page', 'edit-tags.php?taxonomy=author&post_type=page' );

			// Remove the author taxonomy from the all post types
      foreach ( $submenu as $menu => $items ) {
          if ( is_array( $items ) && ! empty( $items ) ) {
              foreach ( $items as $item ) {
                  if ( isset( $item[2] ) && preg_match( '/[\?&]taxonomy=author[^a-zA-Z0-9_-]/', $item[2] ) ) {
                      unset( $submenu[ $menu ] );
                  }
              }
            }
        }

        // Add the submenu to the PublishPress menu.
        add_submenu_page(
            $publishpress->get_menu_slug(),
            esc_html__( 'Authors', 'publishpress-multiple-authors' ),
            esc_html__( 'Authors', 'publishpress-multiple-authors' ),
            apply_filters( 'pp_view_authors_cap', 'list_users' ),
            'edit-tags.php?taxonomy=author'
        );
		}

		public function action_widget_init() {
			register_widget( 'PublishPress\\Addon\\Multiple_authors\\Widget' );
		}

		/**
		 * Get a co-author object by a specific type of key
		 *
		 * @param string $key   Key to search by (slug,email)
		 * @param string $value Value to search for
		 *
		 * @return object|false $coauthor The co-author on success, false on failure
		 */
		public function get_coauthor_by( $key, $value, $force = false ) {

			switch ( $key ) {
				case 'id':
				case 'login':
				case 'user_login':
				case 'email':
				case 'user_nicename':
				case 'user_email':
					if ( 'user_login' == $key ) {
						$key = 'login';
					}
					if ( 'user_email' == $key ) {
						$key = 'email';
					}
					if ( 'user_nicename' == $key ) {
						$key = 'slug';
					}
					// Ensure we aren't doing the lookup by the prefixed value
					if ( 'login' == $key || 'slug' == $key ) {
						$value = preg_replace( '#^cap\-#', '', $value );
					}
					$user = get_user_by( $key, $value );
					if ( ! $user ) {
						return false;
					}
					$user->type = 'wpuser';

					return $user;
					break;
			}

			return false;

		}

		/**
		 * Add coauthors to author column on edit pages
		 *
		 * @param array $post_columns
		 */
		public function _filter_manage_posts_columns( $posts_columns ) {

			$new_columns = [];
			if ( ! Utils::is_post_type_enabled() ) {
				return $posts_columns;
			}

			foreach ( $posts_columns as $key => $value ) {
				$new_columns[ $key ] = $value;
				if ( 'title' === $key ) {
					$new_columns['coauthors'] = __( 'Authors', 'publishpress-multiple-authors' );
				}

				if ( 'author' === $key ) {
					unset( $new_columns[ $key ] );
				}
			}

			return $new_columns;
		}

		/**
		 * Unset the post count column because it's going to be inaccurate and provide our own
		 */
		public function _filter_manage_users_columns( $columns ) {

			$new_columns = [];
			// Unset and add our column while retaining the order of the columns
			foreach ( $columns as $column_name => $column_title ) {
				if ( 'posts' != $column_name ) {
					$new_columns[ $column_name ] = $column_title;
				}
			}

			return $new_columns;
		}

		/**
		 * Quick Edit co-authors box.
		 */
		public function _action_quick_edit_custom_box( $column_name, $post_type ) {
			if ( 'author' != $column_name || ! Utils::is_post_type_enabled( $post_type ) || ! Utils::current_user_can_set_authors() ) {
				return;
			}
			?>
            <label class="inline-edit-group inline-edit-coauthors">
                <span class="title"><?php esc_html_e( 'Authors', 'publishpress-multiple-authors' ) ?></span>
                <div id="coauthors-edit" class="hide-if-no-js">
                    <p><?php echo wp_kses( __( 'Click on an author to change them. Drag to change their order.',
							'publishpress-multiple-authors' ), [ 'strong' => [] ] ); ?></p>
                </div>
				<?php wp_nonce_field( 'coauthors-edit', 'coauthors-nonce' ); ?>
            </label>
			<?php
		}

		/**
		 * If we're forcing PublishPress Multiple Authors to just do taxonomy queries, we still
		 * need to flush our special cache after a taxonomy term has been updated
		 *
		 * @since 3.1
		 */
		public function action_edited_term_taxonomy_flush_cache( $tt_id, $taxonomy ) {
			global $wpdb;

			if ( $this->coauthor_taxonomy != $taxonomy ) {
				return;
			}

			$term_id = $wpdb->get_results( $wpdb->prepare( "SELECT term_id FROM $wpdb->term_taxonomy WHERE term_taxonomy_id = %d ",
				$tt_id ) );

			$term     = get_term_by( 'id', $term_id[0]->term_id, $taxonomy );
			$coauthor = $this->get_coauthor_by( 'user_nicename', $term->slug );
			if ( ! $coauthor ) {
				return new WP_Error( 'missing-coauthor',
					__( 'No co-author exists for that term', 'publishpress-multiple-authors' ) );
			}

			wp_cache_delete( 'author-term-' . $coauthor->user_nicename, 'publishpress-multiple-authors' );
		}

		/**
		 * Add one or more co-authors as bylines for a post
		 *
		 * @param int
		 * @param array
		 * @param bool
		 */
		public function add_coauthors( $post_id, $coauthors, $append = false ) {
			global $current_user, $wpdb;

			$post_id = (int) $post_id;
			$insert  = false;

			// Best way to persist order
			if ( $append ) {
				$existing_coauthors = wp_list_pluck( get_multiple_authors( $post_id ), 'user_login' );
			} else {
				$existing_coauthors = [];
			}

			// A co-author is always required
			if ( empty( $coauthors ) ) {
				$coauthors = [ $current_user->user_login ];
			}

			// Set the coauthors
			$coauthors        = array_unique( array_merge( $existing_coauthors, $coauthors ) );
			$coauthor_objects = [];
			foreach ( $coauthors as &$author_name ) {

				$author             = $this->get_coauthor_by( 'user_nicename', $author_name );
				$coauthor_objects[] = $author;
				$term               = $this->update_author_term( $author );
				$author_name        = $term->slug;
			}
			wp_set_post_terms( $post_id, $coauthors, $this->coauthor_taxonomy, false );

			// If the original post_author is no longer assigned,
			// update to the first WP_User $coauthor
			$post_author_user = get_user_by( 'id', get_post( $post_id )->post_author );
			if ( empty( $post_author_user )
			     || ! in_array( $post_author_user->user_login, $coauthors ) ) {
				foreach ( $coauthor_objects as $coauthor_object ) {
					if ( 'wpuser' == $coauthor_object->type ) {
						$new_author = $coauthor_object;
						break;
					}
				}
				// Uh oh, no WP_Users assigned to the post
				if ( empty( $new_author ) ) {
					return false;
				}

				$wpdb->update( $wpdb->posts, [ 'post_author' => $new_author->ID ], [ 'ID' => $post_id ] );
				clean_post_cache( $post_id );
			}

			return true;

		}

		/**
		 * Restrict WordPress from blowing away author order when bulk editing terms
		 *
		 * @since 2.6
		 * @props kingkool68, http://wordpress.org/support/topic/plugin-publishpress-multiple-authors-making-authors-sortable
		 */
		public function filter_wp_get_object_terms( $terms, $object_ids, $taxonomies, $args ) {

			if ( ! isset( $_REQUEST['bulk_edit'] ) || "'author'" !== $taxonomies ) {
				return $terms;
			}

			global $wpdb;
			$orderby       = 'ORDER BY tr.term_order';
			$order         = 'ASC';
			$object_ids    = (int) $object_ids;
			$query         = $wpdb->prepare( "SELECT t.name, t.term_id, tt.term_taxonomy_id FROM $wpdb->terms AS t INNER JOIN $wpdb->term_taxonomy AS tt ON tt.term_id = t.term_id INNER JOIN $wpdb->term_relationships AS tr ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy IN (%s) AND tr.object_id IN (%s) $orderby $order",
				$this->coauthor_taxonomy, $object_ids );
			$raw_coauthors = $wpdb->get_results( $query );
			$terms         = [];
			foreach ( $raw_coauthors as $author ) {
				if ( true === is_array( $args ) && true === isset( $args['fields'] ) ) {
					switch ( $args['fields'] ) {
						case 'names' :
							$terms[] = $author->name;
							break;
						case 'tt_ids' :
							$terms[] = $author->term_taxonomy_id;
							break;
						case 'all' :
						default :
							$terms[] = get_term( $author->term_id, $this->coauthor_taxonomy );
							break;
					}
				} else {
					$terms[] = get_term( $author->term_id, $this->coauthor_taxonomy );
				}
			}

			return $terms;

		}

		/**
		 * Filter the number of author posts. The author can be mapped to a user or not.
		 *
		 * @return int
		 */
		public function filter_count_author_posts( $count, $term_id ) {
			global $wpdb;

			$items = $wpdb->get_results(
				"SELECT *
                     FROM {$wpdb->posts} AS p
                     INNER JOIN {$wpdb->term_relationships} AS tr ON (tr.`object_id` = p.ID)
                     WHERE
                      p.post_type = 'post'
                      AND p.post_status NOT IN ('trash', 'auto-draft')
                      AND tr.`term_taxonomy_id` = {$term_id}"
			);

			$count = count( $items );

			return $count;
		}

		/**
		 * Filter the count_users_posts() core function to include our correct count.
		 * The author is always mapped to a user.
		 *
		 * @return int
		 */
		public function filter_count_user_posts( $count, $user_id ) {
			global $wpdb;

			$author = Author::get_by_user_id( $user_id );

			if ( ! is_object( $author ) ) {
				return 0;
			}

			$count = apply_filters( 'get_authornumposts', $count, $author->term_id );

			return $count;
		}

		/**
		 * Fix for author pages 404ing or not properly displaying on author pages
		 *
		 * If an author has no posts, we only want to force the queried object to be
		 * the author if they're a member of the blog.
		 *
		 * If the author does have posts, it doesn't matter that they're not an author.
		 *
		 * Alternatively, on an author archive, if the first story has coauthors and
		 * the first author is NOT the same as the author for the archive,
		 * the query_var is changed.
		 *
		 * @param string $query_str
		 */
		public function fix_author_page( $query_str ) {
			global $publishpress;

			if ( empty( $publishpress ) || ! isset( $publishpress->multiple_authors ) || ! Utils::is_post_type_enabled() ) {
				return;
			}

			global $wp_query;
			global $authordata;

			if ( ! is_object( $wp_query ) ) {
				return;
			}

			if ( ! is_author() && ( empty( $wp_query->query ) || ! array_key_exists( 'author', $wp_query->query ) ) ) {
				return;
			}

			$author_name = sanitize_title( get_query_var( 'author_name' ) );

			$author = null;
			$term   = null;

			if ( ! empty( $author_name ) ) {
				$author = Author::get_by_term_slug( $author_name );

				$wp_query->set( 'author_name', $author_name );
			} else {
				$author_id = (int) get_query_var( 'author' );

				$author = Author::get_by_user_id( $author_id );

				$wp_query->set( 'author', $author_id );
			}

			if ( is_object( $author ) ) {

				$authordata = $author;

				$wp_query->queried_object    = $author;
				$wp_query->queried_object_id = $author->ID;
				$wp_query->is_404            = false;
			} else {
				$wp_query->queried_object    = null;
				$wp_query->queried_object_id = null;
				$wp_query->is_author         = false;
				$wp_query->is_archive        = false;
			}
		}

		public function fix_post( WP_Post $post, WP_Query $query ) {
			global $publishpress;

			if ( empty( $publishpress ) || ! isset( $publishpress->multiple_authors ) || ! Utils::is_post_type_enabled() ) {
				return $post;
			}

			global $authordata;

			$authors = get_multiple_authors( $post );

			if ( empty( $authors ) ) {
				return $post;
			}

			$author = $authors[0];

			// Includes the name of all authors in the author name.
			$authors = array_map( function ( $value ) {
				if ( is_object( $value ) && isset( $value->display_name ) ) {
					$value = $value->display_name;
				}

				return $value;
			}, $authors );

			$authors_str = implode( ', ', $authors );

			// Get the first author and update the author in the post
			if ( isset( $author->user_id ) ) {
				$author_id = $author->user_id;

				$user = get_user_by( 'ID', $author_id );

				if ( is_object( $user ) ) {
					$author_slug = $user->user_nicename;
				} else {
					$author_slug = $author->slug;
				}
			} else {
				$author_id   = 0;
				$author_slug = $author->slug;
			}

			if ( ! empty( $authordata ) ) {
				$authordata->display_name  = $authors_str;
				$authordata->ID            = $author_id;
				$authordata->user_nicename = $author_slug;
			}

			$post->post_author = $author_id;

			return $post;
		}

		/**
		 * Get matching authors based on a search value
		 */
		public function search_authors( $search = '', $ignored_authors = [] ) {
			// Since 2.7, we're searching against the term description for the fields
			// instead of the user details. If the term is missing, we probably need to
			// backfill with user details. Let's do this first... easier than running
			// an upgrade script that could break on a lot of users
			$args = [
				'count_total'   => false,
				'search'        => sprintf( '*%s*', $search ),
				'search_fields' => [
					'ID',
					'display_name',
					'user_email',
					'user_login',
				],
				'fields'        => 'all_with_meta',
			];
			add_action( 'pre_user_query', [ $this, 'action_pre_user_query' ] );
			$found_users = get_users( $args );
			remove_action( 'pre_user_query', [ $this, 'action_pre_user_query' ] );

			foreach ( $found_users as $found_user ) {
				$term = $this->get_author_term( $found_user );
				if ( empty( $term ) || empty( $term->description ) ) {
					$this->update_author_term( $found_user );
				}
			}

			$args = [
				'search' => $search,
				'get'    => 'all',
				'number' => 10,
			];
			$args = apply_filters( 'coauthors_search_authors_get_terms_args', $args );
			add_filter( 'terms_clauses', [ $this, 'filter_terms_clauses' ] );
			$found_terms = get_terms( $this->coauthor_taxonomy, $args );
			remove_filter( 'terms_clauses', [ $this, 'filter_terms_clauses' ] );

			if ( empty( $found_terms ) ) {
				return [];
			}

			// Get the co-author objects
			$found_users = [];
			foreach ( $found_terms as $found_term ) {
				$found_user = $this->get_coauthor_by( 'user_nicename', $found_term->slug );
				if ( ! empty( $found_user ) ) {
					$found_users[ $found_user->user_login ] = $found_user;
				}
			}

			// Allow users to always filter out certain users if needed (e.g. administrators)
			$ignored_authors = apply_filters( 'coauthors_edit_ignored_authors', $ignored_authors );
			foreach ( $found_users as $key => $found_user ) {
				// Make sure the user is contributor and above (or a custom cap)
				if ( in_array( $found_user->user_login, $ignored_authors ) ) {
					unset( $found_users[ $key ] );
				} else {
					if ( 'wpuser' === $found_user->type && false === $found_user->has_cap( apply_filters( 'coauthors_edit_author_cap',
							'edit_posts' ) ) ) {
						unset( $found_users[ $key ] );
					}
				}
			}

			return (array) $found_users;
		}

		/**
		 * Modify get_users() to search display_name instead of user_nicename
		 */
		public function action_pre_user_query( &$user_query ) {

			if ( is_object( $user_query ) ) {
				$user_query->query_where = str_replace( 'user_nicename LIKE', 'display_name LIKE',
					$user_query->query_where );
			}

		}

		/**
		 * Modify get_terms() to LIKE against the term description instead of the term name
		 *
		 * @since 3.0
		 */
		public function filter_terms_clauses( $pieces ) {

			$pieces['where'] = str_replace( 't.name LIKE', 'tt.description LIKE', $pieces['where'] );

			return $pieces;
		}

		/**
		 * Functions to add scripts and css
		 */
		public function enqueue_scripts( $hook_suffix ) {
			if ( ! Utils::is_valid_page() ) {
				return;
			}

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'jquery-ui-sortable' );
			wp_enqueue_style( 'multiple-authors-style', plugins_url( 'assets/css/multiple-authors.css', __FILE__ ), [],
				PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION );

			wp_enqueue_script( 'multiple-authors-select2',
				plugins_url( 'assets/lib/select2/js/select2.min.js', __FILE__ ), [ 'jquery' ],
				PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION );
			wp_add_inline_script( 'multiple-authors-select2',
				'var existingSelect2 = jQuery.fn.select2 || null; if (existingSelect2) { delete jQuery.fn.select2; }',
				'before' );
			wp_add_inline_script( 'multiple-authors-select2',
				'jQuery.fn["authorsSelect2"] = jQuery.fn.select2; if (existingSelect2) { delete jQuery.fn.select2; jQuery.fn.select2 = existingSelect2; }',
				'after' );
			wp_enqueue_style( 'multiple-authors-select2',
				plugins_url( 'assets/lib/select2/css/select2.min.css', __FILE__ ), [],
				PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION );

			wp_enqueue_script( 'multiple-authors-js', plugins_url( 'assets/js/multiple-authors.js', __FILE__ ),
				[ 'jquery', 'suggest' ], PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION, true );

			$nonce = wp_create_nonce( "author_get_user_data_nonce" );

			$publishpress = $this->get_service( 'publishpress' );
			$js_strings   = [
				'edit_label'               => __( 'Edit', 'publishpress-multiple-authors' ),
				'confirm_delete'           => __( 'Are you sure you want to remove this author?',
					'publishpress-multiple-authors' ),
				'input_box_title'          => __( 'Click to change this author, or drag to change their position',
					'publishpress-multiple-authors' ),
				'search_box_text'          => __( 'Search for an author', 'publishpress-multiple-authors' ),
				'help_text'                => __( 'Click on an author to change them. Drag to change their order. Click on <strong>Remove</strong> to remove them.',
					'publishpress-multiple-authors' ),
                'confirm_delete_mapped_authors'    => __( 'Are you sure you want to delete the authors terms mapped to users? This action can\'t be undone.', 'publishpress-multiple-authors'),
                'confirm_delete_guest_authors'     => __( 'Are you sure you want to delete the guest authors terms? This action can\'t be undone.', 'publishpress-multiple-authors'),
                'confirm_create_post_authors'      => __( 'Are you sure you want to create authors for the missed post authors?', 'publishpress-multiple-authors'),
                'confirm_create_role_authors'      => __( 'Are you sure you want to create authors for the selected roles?', 'publishpress-multiple-authors'),
				'ajax_get_author_data_url' => admin_url( 'admin-ajax.php?action=author_get_user_data&nonce=' . $nonce ),
				'menu_slug'                => $publishpress->get_menu_slug(),
			];

			wp_localize_script( 'multiple-authors-js', 'MultipleAuthorsStrings', $js_strings );
		}

		/**
		 * load-edit.php is when the screen has been set up
		 */
		public function load_edit() {

			$screen               = get_current_screen();
			$supported_post_types = Utils::get_supported_post_types();
			if ( in_array( $screen->post_type, $supported_post_types ) ) {
				add_filter( 'views_' . $screen->id, [ $this, 'filter_views' ] );
			}
		}

		/**
		 * Filter the view links that appear at the top of the Manage Posts view
		 *
		 * @since 3.0
		 */
		public function filter_views( $views ) {

			if ( array_key_exists( 'mine', $views ) ) {
				return $views;
			}

			$views     = array_reverse( $views );
			$all_view  = array_pop( $views );
			$mine_args = [
				'author_name' => wp_get_current_user()->user_nicename,
			];
			if ( 'post' != PP_Util::get_current_post_type() ) {
				$mine_args['post_type'] = PP_Util::get_current_post_type();
			}
			if ( ! empty( $_REQUEST['author_name'] ) && wp_get_current_user()->user_nicename == $_REQUEST['author_name'] ) {
				$class = ' class="current"';
			} else {
				$class = '';
			}
			$views['mine'] = $view_mine = '<a' . $class . ' href="' . esc_url( add_query_arg( array_map( 'rawurlencode',
					$mine_args ), admin_url( 'edit.php' ) ) ) . '">' . __( 'Mine',
					'publishpress-multiple-authors' ) . '</a>';

			$views['all'] = str_replace( $class, '', $all_view );
			$views        = array_reverse( $views );

			return $views;
		}

		/**
		 * Allows coauthors to edit the post they're coauthors of
		 */
		public function filter_user_has_cap( $allcaps, $caps, $args ) {

			$cap     = $args[0];
			$user_id = isset( $args[1] ) ? $args[1] : 0;
			$post_id = isset( $args[2] ) ? $args[2] : 0;

			$obj = get_post_type_object( PP_Util::get_current_post_type( $post_id ) );
			if ( ! $obj || 'revision' == $obj->name ) {
				return $allcaps;
			}

			$caps_to_modify = [
				$obj->cap->edit_post,
				'edit_post', // Need to filter this too, unfortunately: http://core.trac.wordpress.org/ticket/22415
				$obj->cap->edit_others_posts, // This as well: http://core.trac.wordpress.org/ticket/22417
			];
			if ( ! in_array( $cap, $caps_to_modify ) ) {
				return $allcaps;
			}

			// We won't be doing any modification if they aren't already a co-author on the post
			if ( ! is_user_logged_in() || ! is_multiple_author_for_post( $user_id, $post_id ) ) {
				return $allcaps;
			}

			$current_user = wp_get_current_user();
			if ( 'publish' == get_post_status( $post_id ) &&
			     ( isset( $obj->cap->edit_published_posts ) && ! empty( $current_user->allcaps[ $obj->cap->edit_published_posts ] ) ) ) {
				$allcaps[ $obj->cap->edit_published_posts ] = true;
			} elseif ( 'private' == get_post_status( $post_id ) &&
			           ( isset( $obj->cap->edit_private_posts ) && ! empty( $current_user->allcaps[ $obj->cap->edit_private_posts ] ) ) ) {
				$allcaps[ $obj->cap->edit_private_posts ] = true;
			}

			$allcaps[ $obj->cap->edit_others_posts ] = true;

			return $allcaps;
		}

		/**
		 * Get the author term for a given co-author
		 *
		 * @since 3.0
		 *
		 * @param object $coauthor The co-author object
		 *
		 * @return object|false $author_term The author term on success
		 */
		public function get_author_term( $coauthor ) {

			if ( ! is_object( $coauthor ) ) {
				return;
			}

			$cache_key = 'author-term-' . $coauthor->user_nicename;
			if ( false !== ( $term = wp_cache_get( $cache_key, 'publishpress-multiple-authors' ) ) ) {
				return $term;
			}

			// See if the prefixed term is available, otherwise default to just the nicename
			$term = get_term_by( 'slug', $coauthor->user_nicename, $this->coauthor_taxonomy );

			wp_cache_set( $cache_key, $term, 'publishpress-multiple-authors' );

			return $term;
		}

		/**
		 * Update the author term for a given co-author
		 *
		 * @since 3.0
		 *
		 * @param object $coauthor The co-author object
		 *
		 * @return object|false $success Term object if successful, false if not
		 */
		public function update_author_term( $coauthor ) {

			if ( ! is_object( $coauthor ) ) {
				return false;
			}

			// Update the taxonomy term to include details about the user for searching
			$search_values = [];
			foreach ( $this->ajax_search_fields as $search_field ) {
				$search_values[] = $coauthor->$search_field;
			}

			$term_description = implode( ' ', $search_values );

			if ( $term = $this->get_author_term( $coauthor ) ) {
				if ( $term->description != $term_description ) {
					wp_update_term( $term->term_id, $this->coauthor_taxonomy,
						[ 'description' => $term_description ] );
				}
			} else {
				$args = [
					'slug'        => $coauthor->user_nicename,
					'description' => $term_description,
				];

				$new_term = wp_insert_term( $coauthor->user_login, $this->coauthor_taxonomy, $args );
			}
			wp_cache_delete( 'author-term-' . $coauthor->user_nicename, 'publishpress-multiple-authors' );

			return $this->get_author_term( $coauthor );
		}

		/**
		 * Filter Edit Flow's 'ef_calendar_item_information_fields' to add co-authors
		 *
		 * @param array $information_fields
		 * @param int   $post_id
		 *
		 * @return array
		 */
		public function filter_ef_calendar_item_information_fields( $information_fields, $post_id ) {

			// Don't add the author row again if another plugin has removed
			if ( ! array_key_exists( 'author', $information_fields ) ) {
				return $information_fields;
			}

			$co_authors = get_multiple_authors( $post_id );
			if ( count( $co_authors ) > 1 ) {
				$information_fields['author']['label'] = __( 'Authors', 'publishpress-multiple-authors' );
			}
			$co_authors_names = '';
			foreach ( $co_authors as $co_author ) {
				$co_authors_names .= $co_author->display_name . ', ';
			}
			$information_fields['author']['value'] = rtrim( $co_authors_names, ', ' );

			return $information_fields;
		}

		/**
		 * Filter Edit Flow's 'ef_story_budget_term_column_value' to add co-authors to the story budget
		 *
		 * @param string $column_name
		 * @param object $post
		 * @param object $parent_term
		 *
		 * @return string
		 */
		public function filter_ef_story_budget_term_column_value( $column_name, $post, $parent_term ) {

			// We only want to modify the 'author' column
			if ( 'author' != $column_name ) {
				return $column_name;
			}

			$co_authors       = get_multiple_authors( $post->ID );
			$co_authors_names = '';
			foreach ( $co_authors as $co_author ) {
				$co_authors_names .= $co_author->display_name . ', ';
			}

			return rtrim( $co_authors_names, ', ' );
		}

		/**
		 * Filter non-native users added by Co-Author-Plus in Jetpack
		 *
		 * @since 3.1
		 *
		 * @param array $og_tags          Required. Array of Open Graph Tags.
		 * @param array $image_dimensions Required. Dimensions for images used.
		 *
		 * @return array Open Graph Tags either as they were passed or updated.
		 */
		public function filter_jetpack_open_graph_tags( $og_tags, $image_dimensions ) {

			if ( is_author() ) {
				$author                        = get_queried_object();
				$og_tags['og:title']           = $author->display_name;
				$og_tags['og:url']             = get_author_posts_url( $author->ID, $author->user_nicename );
				$og_tags['og:description']     = $author->description;
				$og_tags['profile:first_name'] = $author->first_name;
				$og_tags['profile:last_name']  = $author->last_name;
				if ( isset( $og_tags['article:author'] ) ) {
					$og_tags['article:author'] = get_author_posts_url( $author->ID, $author->user_nicename );
				}
			} else {
				if ( is_singular() && Utils::is_post_type_enabled() ) {
					$authors = get_multiple_authors();
					if ( ! empty( $authors ) ) {
						$author = array_shift( $authors );
						if ( isset( $og_tags['article:author'] ) ) {
							$og_tags['article:author'] = get_author_posts_url( $author->ID,
								$author->user_nicename );
						}
					}
				}
			}

			// Send back the updated Open Graph Tags
			return apply_filters( 'coauthors_open_graph_tags', $og_tags );
		}

		/**
		 * Retrieve a list of coauthor terms for a single post.
		 *
		 * Grabs a correctly ordered list of authors for a single post, appropriately
		 * cached because it requires `wp_get_object_terms()` to succeed.
		 *
		 * @param int $post_id ID of the post for which to retrieve authors.
		 *
		 * @return array Array of coauthor WP_Term objects
		 */
		public function get_coauthor_terms_for_post( $post_id ) {

			if ( ! $post_id ) {
				return [];
			}

			$cache_key      = 'coauthors_post_' . $post_id;
			$coauthor_terms = wp_cache_get( $cache_key, 'publishpress-multiple-authors' );

			if ( false === $coauthor_terms ) {
				$coauthor_terms = wp_get_object_terms( $post_id, $this->coauthor_taxonomy, [
					'orderby' => 'term_order',
					'order'   => 'ASC',
				] );

				// This usually happens if the taxonomy doesn't exist, which should never happen, but you never know.
				if ( is_wp_error( $coauthor_terms ) ) {
					return [];
				}

				wp_cache_set( $cache_key, $coauthor_terms, 'publishpress-multiple-authors' );
			}

			return $coauthor_terms;

		}

		/**
		 * Callback to clear the cache on post save and post delete.
		 *
		 * @param $post_id The Post ID.
		 */
		public function clear_cache( $post_id ) {
			wp_cache_delete( 'coauthors_post_' . $post_id, 'publishpress-multiple-authors' );
		}

		/**
		 * Callback to clear the cache when an object's terms are changed.
		 *
		 * @param $post_id The Post ID.
		 */
		public function clear_cache_on_terms_set( $object_id, $terms, $tt_ids, $taxonomy, $append, $old_tt_ids ) {

			// We only care about the coauthors taxonomy
			if ( $this->coauthor_taxonomy !== $taxonomy ) {
				return;
			}

			wp_cache_delete( 'coauthors_post_' . $object_id, 'publishpress-multiple-authors' );

		}

		/**
		 * Callback for the filter to add the author box to the end of the content
		 *
		 * @return string
		 */
		public function filter_the_content( $content ) {
			global $publishpress;

			// Check if it is configured to append to the content
			$append_to_content = 'yes' === $publishpress->modules->multiple_authors->options->append_to_content;

			if ( $this->should_display_author_box() && $append_to_content ) {
				$content .= $this->get_author_box_markup( 'the_content' );
			}

			return $content;
		}

		/**
		 * Shortcode to get the author box
		 *
		 * @return string
		 */
		public function shortcode_author_box( $atts ) {
			return $this->get_author_box_markup( 'shortcode' );
		}

		/**
		 * Action to display the author box
		 */
		public function action_echo_author_box() {
			if ( $this->should_display_author_box() ) {
				echo $this->get_author_box_markup( 'action' );
			}
		}
	}

	global $multiple_authors_addon;
	$multiple_authors_addon = new PP_Multiple_authors_plugin();

	if ( ! function_exists( 'wp_notify_postauthor' ) ) :
		/**
		 * Notify a co-author of a comment/trackback/pingback to one of their posts.
		 * This is a modified version of the core function in wp-includes/pluggable.php that
		 * supports notifs to multiple co-authors. Unfortunately, this is the best way to do it :(
		 *
		 * @since 2.6.2
		 *
		 * @param int    $comment_id   Comment ID
		 * @param string $comment_type Optional. The comment type either 'comment' (default), 'trackback', or 'pingback'
		 *
		 * @return bool False if user email does not exist. True on completion.
		 */
		function wp_notify_postauthor( $comment_id, $comment_type = '' ) {
			$comment   = get_comment( $comment_id );
			$post      = get_post( $comment->comment_post_ID );
			$coauthors = get_multiple_authors( $post->ID );
			foreach ( $coauthors as $author ) {

				// The comment was left by the co-author
				if ( $comment->user_id == $author->ID ) {
					return false;
				}

				// The co-author moderated a comment on his own post
				if ( $author->ID == get_current_user_id() ) {
					return false;
				}

				// If there's no email to send the comment to
				if ( '' == $author->user_email ) {
					return false;
				}

				$comment_author_domain = @gethostbyaddr( $comment->comment_author_IP );

				// The blogname option is escaped with esc_html on the way into the database in sanitize_option
				// we want to reverse this for the plain text arena of emails.
				$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

				if ( empty( $comment_type ) ) {
					$comment_type = 'comment';
				}

				if ( 'comment' == $comment_type ) {
					$notify_message = sprintf( __( 'New comment on your post "%s"' ), $post->post_title ) . "\r\n";
					/* translators: 1: comment author, 2: author IP, 3: author domain */
					$notify_message .= sprintf( __( 'Author : %1$s (IP: %2$s , %3$s)' ), $comment->comment_author,
							$comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					$notify_message .= sprintf( __( 'E-mail : %s' ), $comment->comment_author_email ) . "\r\n";
					$notify_message .= sprintf( __( 'URL    : %s' ), $comment->comment_author_url ) . "\r\n";
					$notify_message .= sprintf( __( 'Whois  : http://whois.arin.net/rest/ip/%s' ),
							$comment->comment_author_IP ) . "\r\n";
					$notify_message .= __( 'Comment: ' ) . "\r\n" . $comment->comment_content . "\r\n\r\n";
					$notify_message .= __( 'You can see all comments on this post here: ' ) . "\r\n";
					/* translators: 1: blog name, 2: post title */
					$subject = sprintf( __( '[%1$s] Comment: "%2$s"' ), $blogname, $post->post_title );
				} elseif ( 'trackback' == $comment_type ) {
					$notify_message = sprintf( __( 'New trackback on your post "%s"' ), $post->post_title ) . "\r\n";
					/* translators: 1: website name, 2: author IP, 3: author domain */
					$notify_message .= sprintf( __( 'Website: %1$s (IP: %2$s , %3$s)' ), $comment->comment_author,
							$comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					$notify_message .= sprintf( __( 'URL    : %s' ), $comment->comment_author_url ) . "\r\n";
					$notify_message .= __( 'Excerpt: ' ) . "\r\n" . $comment->comment_content . "\r\n\r\n";
					$notify_message .= __( 'You can see all trackbacks on this post here: ' ) . "\r\n";
					/* translators: 1: blog name, 2: post title */
					$subject = sprintf( __( '[%1$s] Trackback: "%2$s"' ), $blogname, $post->post_title );
				} elseif ( 'pingback' == $comment_type ) {
					$notify_message = sprintf( __( 'New pingback on your post "%s"' ), $post->post_title ) . "\r\n";
					/* translators: 1: comment author, 2: author IP, 3: author domain */
					$notify_message .= sprintf( __( 'Website: %1$s (IP: %2$s , %3$s)' ), $comment->comment_author,
							$comment->comment_author_IP, $comment_author_domain ) . "\r\n";
					$notify_message .= sprintf( __( 'URL    : %s' ), $comment->comment_author_url ) . "\r\n";
					$notify_message .= __( 'Excerpt: ' ) . "\r\n" . sprintf( '[...] %s [...]',
							$comment->comment_content ) . "\r\n\r\n";
					$notify_message .= __( 'You can see all pingbacks on this post here: ' ) . "\r\n";
					/* translators: 1: blog name, 2: post title */
					$subject = sprintf( __( '[%1$s] Pingback: "%2$s"' ), $blogname, $post->post_title );
				}
				$notify_message .= get_permalink( $comment->comment_post_ID ) . "#comments\r\n\r\n";
				$notify_message .= sprintf( __( 'Permalink: %s' ),
						get_permalink( $comment->comment_post_ID ) . '#comment-' . $comment_id ) . "\r\n";
				if ( EMPTY_TRASH_DAYS ) {
					$notify_message .= sprintf( __( 'Trash it: %s' ),
							admin_url( "comment.php?action=trash&c=$comment_id" ) ) . "\r\n";
				} else {
					$notify_message .= sprintf( __( 'Delete it: %s' ),
							admin_url( "comment.php?action=delete&c=$comment_id" ) ) . "\r\n";
				}
				$notify_message .= sprintf( __( 'Spam it: %s' ),
						admin_url( "comment.php?action=spam&c=$comment_id" ) ) . "\r\n";

				$wp_email = 'wordpress@' . preg_replace( '#^www\.#', '', strtolower( $_SERVER['SERVER_NAME'] ) );

				if ( '' == $comment->comment_author ) {
					$from = "From: \"$blogname\" <$wp_email>";
					if ( '' != $comment->comment_author_email ) {
						$reply_to = "Reply-To: $comment->comment_author_email";
					}
				} else {
					$from = "From: \"$comment->comment_author\" <$wp_email>";
					if ( '' != $comment->comment_author_email ) {
						$reply_to = "Reply-To: \"$comment->comment_author_email\" <$comment->comment_author_email>";
					}
				}

				$message_headers = "$from\n"
				                   . 'Content-Type: text/plain; charset="' . get_option( 'blog_charset' ) . "\"\n";

				if ( isset( $reply_to ) ) {
					$message_headers .= $reply_to . "\n";
				}

				$notify_message  = apply_filters( 'comment_notification_text', $notify_message, $comment_id );
				$subject         = apply_filters( 'comment_notification_subject', $subject, $comment_id );
				$message_headers = apply_filters( 'comment_notification_headers', $message_headers, $comment_id );

				@wp_mail( $author->user_email, $subject, $notify_message, $message_headers );
			}

			return true;
		}
	endif;

	/**
	 * Filter array of moderation notification email addresses
	 *
	 * @param array $recipients
	 * @param int   $comment_id
	 *
	 * @return array
	 */
	function cap_filter_comment_moderation_email_recipients( $recipients, $comment_id ) {
		$comment = get_comment( $comment_id );
		$post_id = $comment->comment_post_ID;

		if ( isset( $post_id ) ) {
			$coauthors        = get_multiple_authors( $post_id );
			$extra_recipients = [];
			foreach ( $coauthors as $user ) {
				if ( ! empty( $user->user_email ) ) {
					$extra_recipients[] = $user->user_email;
				}
			}

			return array_unique( array_merge( $recipients, $extra_recipients ) );
		}

		return $recipients;
	}

	/**
	 * Retrieve a list of coauthor terms for a single post.
	 *
	 * Grabs a correctly ordered list of authors for a single post, appropriately
	 * cached because it requires `wp_get_object_terms()` to succeed.
	 *
	 * @param int $post_id ID of the post for which to retrieve authors.
	 *
	 * @return array Array of coauthor WP_Term objects
	 */
	function cap_get_coauthor_terms_for_post( $post_id ) {
		global $multiple_authors_addon;

		return $multiple_authors_addon->get_coauthor_terms_for_post( $post_id );
	}
}
