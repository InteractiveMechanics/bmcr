<?php
/**
 * @package PublishPress Multiple Authors
 * @author  PublishPress
 *
 * Copyright (C) 2018 PublishPress
 *
 * This file is part of PublishPress Multiple Authors
 *
 * PublishPress Multiple Authors is free software: you can redistribute it
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

use PublishPress\Addon\Multiple_authors\Classes\Authors_Iterator;
use PublishPress\Addon\Multiple_authors\Classes\Installer;
use PublishPress\Addon\Multiple_authors\Classes\Objects\Author;
use PublishPress\Addon\Multiple_authors\Classes\Utils;
use PublishPress\Addon\Multiple_authors\Factory;
use PublishPress\EDD_License\Core\Setting\Field\License_key as Field_License_key;

if ( ! class_exists( 'PP_Multiple_Authors' ) ) {
	/**
	 * class PP_Multiple_Authors
	 */
	class PP_Multiple_Authors extends PP_Module {

		const SETTINGS_SLUG = 'pp-multiple-authors-settings';

		public $module_name = 'multiple_authors';

		/**
		 * List of post types which supports checklist
		 *
		 * @var array
		 */
		protected $post_types = [];

		/**
		 * WordPress-EDD-License-Integration
		 *
		 * @var License
		 */
		protected $license_manager;

		/**
		 * Instace for the module
		 *
		 * @var stdClass
		 */
		public $module;

		/**
		 * Construct the PP_Multiple_Authors class
		 */
		public function __construct() {
			$this->module_url = $this->get_module_url( __FILE__ );

			// Register the module with PublishPress
			$args = [
				'title'                => __( 'Multiple Authors', 'publishpress-multiple-authors' ),
				'short_description'    => __( 'Add support for multiple authors on your content',
					'publishpress-multiple-authors' ),
				'extended_description' => __( 'Add support for multiple authors on your content',
					'publishpress-multiple-authors' ),
				'module_url'           => $this->module_url,
				'icon_class'           => 'dashicons dashicons-feedback',
				'slug'                 => 'multiple-authors',
				'default_options'      => [
					'enabled'              => 'on',
					'post_types'           => [ 'post' ],
					'append_to_content'    => 'yes',
					'author_for_new_users' => [],
					'layout'               => 'simple_list',
				],
				'configure_page_cb'    => 'print_configure_view',
				'options_page'         => true,
			];

			// Apply a filter to the default options
			$args['default_options'] = apply_filters( 'pp_multiple_authors_default_options', $args['default_options'] );

			$this->module = PublishPress()->register_module( $this->module_name, $args );

			parent::__construct();
		}

		/**
		 * Returns a list of post types the multiple authors module.
		 *
		 * @return array
		 */
		public function get_post_types() {
			if ( empty( $this->post_types ) ) {
				$post_types = [
					'post' => esc_html__( 'Posts', 'publishpress-multiple-authors' ),
					'page' => esc_html__( 'Pages', 'publishpress-multiple-authors' ),
				];

				// Apply filters to the list of requirements
				$this->post_types = apply_filters( 'pp_multiple_authors_post_types', $post_types );

				// Try a more readable name
				foreach ( $this->post_types as $type => $label ) {
					$this->post_types[ $type ] = esc_html__( ucfirst( $label ) );
				}
			}

			return $this->post_types;
		}

		/**
		 * Initialize the module. Conditionally loads if the module is enabled
		 */
		public function init() {
			add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'admin_init', [ $this, 'load_updater' ] );
			add_action( 'admin_init', [ $this, 'handle_action_reset_author_terms' ] );
			add_action( 'admin_notices', [ $this, 'handle_action_reset_author_terms_notice' ] );

			add_action( 'publishpress_delete_mapped_authors', [ $this, 'action_delete_mapped_authors' ] );
			add_action( 'publishpress_delete_guest_authors', [ $this, 'action_delete_guest_authors' ] );
			add_action( 'publishpress_create_post_authors', [ $this, 'action_create_post_authors' ] );
			add_action( 'publishpress_create_role_authors', [ $this, 'action_create_role_authors' ] );

			// Filters the list of authors in the Improved Notifications add-on.
			add_filter(
				'publishpress_notif_workflow_receiver_post_authors',
				[ $this, 'filter_workflow_receiver_post_authors' ],
				10,
				3
			);

			add_filter( 'pp_multiple_authors_author_layouts', [ $this, 'filter_author_layouts' ] );

			add_filter( 'gettext', [ $this, 'filter_get_text' ], 101, 3 );

			// Fix upload permissions for multiple authors.
			add_filter( 'map_meta_cap', [ $this, 'filter_map_meta_cap' ], 10, 4 );
		}

		/**
		 * Print the content of the configure tab.
		 */
		public function print_configure_view() {
			$container = Factory::get_container();
			$twig      = $container['twig'];

			echo $twig->render(
				'settings-tab.twig',
				[
					'form_action'        => menu_page_url( $this->module->settings_slug, false ),
					'options_group_name' => $this->module->options_group_name,
					'module_name'        => $this->module->slug,
				]
			);
		}

		/**
		 * Register settings for notifications so we can partially use the Settings API
		 * (We use the Settings API for form generation, but not saving)
		 */
		public function register_settings() {
			/**
			 *
			 * License
			 *
			 */
			add_settings_section(
				$this->module->options_group_name . '_license',
				__( 'Licensing:', 'publishpress-multiple-authors' ),
				'__return_false',
				$this->module->options_group_name
			);

			add_settings_field(
				'license_key',
				__( 'License key:', 'publishpress-multiple-authors' ),
				[ $this, 'settings_license_key_option' ],
				$this->module->options_group_name,
				$this->module->options_group_name . '_license'
			);

			/**
			 *
			 * Post types
			 */

			add_settings_section(
				$this->module->options_group_name . '_post_types',
				__( 'General:', 'publishpress-multiple-authors' ),
				'__return_false',
				$this->module->options_group_name
			);

			add_settings_field(
				'post_types',
				__( 'Add to these post types:', 'publishpress-multiple-authors' ),
				[ $this, 'settings_post_types_option' ],
				$this->module->options_group_name,
				$this->module->options_group_name . '_post_types'
			);

			add_settings_field(
				'author_for_new_users',
				__( 'Automatically create authors for new users in the following roles:',
					'publishpress-multiple-authors' ),
				[ $this, 'settings_author_for_new_users_option' ],
				$this->module->options_group_name,
				$this->module->options_group_name . '_post_types'
			);

			/**
			 *
			 * Display
			 */

			add_settings_section(
				$this->module->options_group_name . '_display',
				__( 'Display below the content:', 'publishpress-multiple-authors' ),
				'__return_false',
				$this->module->options_group_name
			);

			add_settings_field(
				'append_to_content',
				__( 'Show below the content:', 'publishpress-multiple-authors' ),
				[ $this, 'settings_append_to_content_option' ],
				$this->module->options_group_name,
				$this->module->options_group_name . '_display'
			);

			add_settings_field(
				'title_appended_to_content',
				__( 'Title for the author box:', 'publishpress-multiple-authors' ),
				[ $this, 'settings_title_appended_to_content_option' ],
				$this->module->options_group_name,
				$this->module->options_group_name . '_display'
			);

			add_settings_field(
				'layout',
				__( 'Layout:', 'publishpress-multiple-authors' ),
				[ $this, 'settings_layout_option' ],
				$this->module->options_group_name,
				$this->module->options_group_name . '_display'
			);

			add_settings_field(
				'show_email_link',
				__( 'Show email link:', 'publishpress-multiple-authors' ),
				[ $this, 'settings_show_email_link_option' ],
				$this->module->options_group_name,
				$this->module->options_group_name . '_display'
			);

			add_settings_field(
				'show_site_link',
				__( 'Show site link:', 'publishpress-multiple-authors' ),
				[ $this, 'settings_show_site_link_option' ],
				$this->module->options_group_name,
				$this->module->options_group_name . '_display'
			);

			/**
			 *
			 * Maintenance
			 */

			add_settings_section(
				$this->module->options_group_name . '_maintenance',
				__( 'Maintenance:', 'publishpress-multiple-authors' ),
				'__return_false',
				$this->module->options_group_name
			);

			add_settings_field(
				'reset_author_terms',
				__( 'Reset author terms:', 'publishpress-multiple-authors' ),
				[ $this, 'settings_reset_author_terms_option' ],
				$this->module->options_group_name,
				$this->module->options_group_name . '_maintenance'
			);
		}

		/**
		 * Displays the field to allow select the post types for checklist.
		 */
		public function settings_post_types_option() {
			global $publishpress;

			$publishpress->settings->helper_option_custom_post_type( $this->module );
		}

		/**
		 * Displays the field to choose between display or not the warning icon
		 * close to the submit button
		 *
		 * @param  array
		 */
		public function settings_license_key_option( $args = [] ) {
			$license_key    = isset( $this->module->options->license_key ) ? $this->module->options->license_key : '';
			$license_status = isset( $this->module->options->license_status ) ? $this->module->options->license_status : '';

			$field_args = [
				'options_group_name' => $this->module->options_group_name,
				'name'               => 'license_key',
				'value'              => $license_key,
				'license_status'     => $license_status,
				'link_more_info'     => 'https://publishpress.com/docs/activate-license',
			];
			$field      = new Field_License_key( $field_args );

			echo $field;
		}

		/**
		 * Displays the field to choose display or not the author box at the
		 * end of the content
		 *
		 * @param  array
		 */
		public function settings_append_to_content_option( $args = [] ) {
			$id    = $this->module->options_group_name . '_append_to_content';
			$value = isset( $this->module->options->append_to_content ) ? $this->module->options->append_to_content : 'yes';

			echo '<label for="' . $id . '">';
			echo '<input type="checkbox" value="yes" id="' . $id . '" name="' . $this->module->options_group_name . '[append_to_content]" '
			     . checked( $value, 'yes', false ) . ' />';
			echo '&nbsp;&nbsp;&nbsp;' . esc_html__( 'This will display the authors box at the end of the content.',
					'publishpress-content-checklist' );
			echo '</label>';
		}

		/**
		 * Displays the field to choose the title for the author box at the
		 * end of the content
		 *
		 * @param  array
		 */
		public function settings_title_appended_to_content_option( $args = [] ) {
			$id    = $this->module->options_group_name . '_title_appended_to_content';
			$value = isset( $this->module->options->title_appended_to_content ) ? $this->module->options->title_appended_to_content : esc_html__( 'Author',
				'publishpress-multiple-authors' );

			echo '<label for="' . $id . '">';
			echo '<input type="text" value="' . esc_attr( $value ) . '" id="' . $id . '" name="' . $this->module->options_group_name . '[title_appended_to_content]" class="regular-text" />';
			echo '</label>';
		}

		/**
		 * @param array $args
		 */
		public function settings_layout_option( $args = [] ) {
			$id    = $this->module->options_group_name . '_layout';
			$value = isset( $this->module->options->layout ) ? $this->module->options->layout : 'simple_list';

			echo '<label for="' . $id . '">';

			echo '<select id="' . $id . '" name="' . $this->module->options_group_name . '[layout]">';

			$layouts = apply_filters( 'pp_multiple_authors_author_layouts', [] );

			foreach ( $layouts as $layout => $text ) {
				$selected = $value === $layout ? 'selected="selected"' : '';
				echo '<option value="' . $layout . '" ' . $selected . '>' . $text . '</option>';
			}

			echo '</select>';
			echo '</label>';
		}

		/**
		 * @param array $args
		 */
		public function settings_author_for_new_users_option( $args = [] ) {
			$id     = $this->module->options_group_name . '_author_for_new_users';
			$values = isset( $this->module->options->author_for_new_users ) ? $this->module->options->author_for_new_users : '';

			echo '<label for="' . $id . '">';

			echo '<select id="' . $id . '" name="' . $this->module->options_group_name . '[author_for_new_users][]" multiple="multiple" class="chosen-select">';

			$roles = get_editable_roles();

			foreach ( $roles as $role => $data ) {
				$selected = in_array( $role, $values ) ? 'selected="selected"' : '';
				echo '<option value="' . $role . '" ' . $selected . '>' . $data['name'] . '</option>';
			}

			echo '</select>';
			echo '</label>';
		}

		/**
		 * Displays the field to choose display or not the email link/icon.
		 *
		 * @param  array
		 */
		public function settings_show_email_link_option( $args = [] ) {
			$id    = $this->module->options_group_name . '_show_email_link';
			$value = isset( $this->module->options->show_email_link ) ? $this->module->options->show_email_link : 'yes';

			echo '<label for="' . $id . '">';
			echo '<input type="checkbox" value="yes" id="' . $id . '" name="' . $this->module->options_group_name . '[show_email_link]" '
			     . checked( $value, 'yes', false ) . ' />';
			echo '&nbsp;&nbsp;&nbsp;' . esc_html__( 'This will display the authors email in the author box.',
					'publishpress-content-checklist' );
			echo '</label>';
		}

		/**
		 * Displays the field to choose display or not the email link/icon.
		 *
		 * @param  array
		 */
		public function settings_show_site_link_option( $args = [] ) {
			$id    = $this->module->options_group_name . '_show_site_link';
			$value = isset( $this->module->options->show_site_link ) ? $this->module->options->show_site_link : 'yes';

			echo '<label for="' . $id . '">';
			echo '<input type="checkbox" value="yes" id="' . $id . '" name="' . $this->module->options_group_name . '[show_site_link]" '
			     . checked( $value, 'yes', false ) . ' />';
			echo '&nbsp;&nbsp;&nbsp;' . esc_html__( 'This will display the authors site in the author box.',
					'publishpress-content-checklist' );
			echo '</label>';
		}

		/**
		 * Displays the button to reset the author terms.
		 *
		 * @param  array
		 */
		public function settings_reset_author_terms_option( $args = [] ) {
			$nonce     = wp_create_nonce( 'publishpress_multiple_authors_reset_authors_terms' );
			$base_link = esc_url( admin_url( '/admin.php?page=pp-modules-settings&module=pp-multiple-authors-settings&pp_action=%s&nonce=' . $nonce ) );

			echo '<p>' . __( 'Please be careful clicking these buttons. It should only be done rarely.',
					'publishpress-multiple-authors' );
			echo '&nbsp;<a href="#">' . __( 'Click here for more details.', 'publishpress-multiple-authors' ) . '</a>';
			echo '</p><br>';
			echo '<a id="publishpress_delete_mapped_authors" href="' . sprintf( $base_link,
					'delete_mapped_authors' ) . '" class="button button-danger">' . __( 'Delete all authors mapped to users',
					'publishpress-multiple-authors' ) . '</a><br/>';
			echo '<a id="publishpress_delete_guest_authors" href="' . sprintf( $base_link,
					'delete_guest_authors' ) . '" class="button button-danger">' . __( 'Delete all guest authors',
					'publishpress-multiple-authors' ) . '</a><br/>';
			echo '<a id="publishpress_create_post_authors" href="' . sprintf( $base_link,
					'create_post_authors' ) . '" class="button button-danger">' . __( 'Create missed post authors',
					'publishpress-multiple-authors' ) . '</a><br/>';
			echo '<a id="publishpress_create_role_authors" href="' . sprintf( $base_link,
					'create_role_authors' ) . '" class="button button-danger">' . __( 'Create missed authors from role',
					'publishpress-multiple-authors' ) . '</a><br/>';
		}

		/**
		 * Validate data entered by the user
		 *
		 * @param array $new_options New values that have been entered by the user
		 *
		 * @return array $new_options Form values after they've been sanitized
		 */
		public function settings_validate( $new_options ) {
			$container      = Factory::get_container();
			$licenseManager = $container['edd_container']['license_manager'];

			if ( ! isset( $new_options['license_key'] ) ) {
				$new_options['license_key'] = '';
			}

			$new_options['license_key']    = $licenseManager->sanitize_license_key( $new_options['license_key'] );
			$new_options['license_status'] = $licenseManager->validate_license_key( $new_options['license_key'],
				PP_MULTIPLE_AUTHORS_ITEM_ID );

			// Whitelist validation for the post type options
			if ( ! isset( $new_options['post_types'] ) ) {
				$new_options['post_types'] = [];
			}

			$new_options['post_types'] = $this->clean_post_type_options(
				$new_options['post_types'],
				$this->module->post_type_support
			);

			if ( ! isset( $new_options['append_to_content'] ) ) {
				$new_options['append_to_content'] = 'no';
			}

			if ( ! isset( $new_options['author_for_new_users'] ) || ! is_array( $new_options['author_for_new_users'] ) ) {
				$new_options['author_for_new_users'] = [];
			}

			if ( ! isset( $new_options['show_email_link'] ) ) {
				$new_options['show_email_link'] = 'no';
			}

			if ( ! isset( $new_options['show_site_link'] ) ) {
				$new_options['show_site_link'] = 'no';
			}

			$new_options = apply_filters( 'pp_multiple_authors_validate_settings', $new_options );

			if ( isset( $new_options['layout'] ) ) {
				/**
				 * Filter the list of available layouts.
				 */
				$layouts = apply_filters( 'pp_multiple_authors_author_layouts', [] );

				if ( ! array_key_exists( $new_options['layout'], $layouts ) ) {
					$new_options['layout'] = 'simple_list';
				}
			}

			return $new_options;
		}

		/**
		 * @param $layouts
		 *
		 * @return array
		 */
		public function filter_author_layouts( $layouts ) {

			if ( ! is_array( $layouts ) ) {
				$layouts = [];
			}

			$layouts = array_merge(
				[
					'simple_list' => __( 'Simple list', 'publishpress-multiple-authors' ),
					'boxed'       => __( 'Boxed', 'publishpress-multiple-authors' ),
					'centered'    => __( 'Centered', 'publishpress-multiple-authors' ),
				],
				$layouts
			);

			return $layouts;
		}

		/**
		 * Filters the list of receivers in the notification workflows provided
		 * by the improved notifications add-on.
		 *
		 * @param array   $receivers
		 * @param WP_Post $workflow
		 * @param array   $args
		 *
		 * @return array
		 */
		public function filter_workflow_receiver_post_authors( $receivers, $workflow, $args ) {
			if ( ! function_exists( 'multiple_authors' ) ) {
				require_once PP_MULTIPLE_AUTHORS_PATH_BASE . '/template-tags.php';
			}

			$authors_iterator = new Authors_Iterator( $args['post']->ID );
			while ( $authors_iterator->iterate() ) {
				if ( ! in_array( $authors_iterator->current_author->ID, $receivers ) ) {
					$receivers[] = $authors_iterator->current_author->ID;
				}
			}

			return $receivers;
		}

		/**
		 * Over hide some strings for Authors.
		 *
		 * @param string $translation Translated text.
		 * @param string $text        Text to translate.
		 * @param string $domain      Text domain. Unique identifier for retrieving translated strings.
		 *
		 * @return string
		 */
		public function filter_get_text( $translation, $text, $domain ) {
			if ( ! Utils::is_valid_page() ) {
				return $translation;
			}

			// The description of the field Name
			if ( 'default' === $domain && 'The name is how it appears on your site.' === $translation ) {
				$translation = __( 'This is how the author’s name will appears on your site.',
					'publishpress-multiple-authors' );
			}

			// The name of field Slug, convert to Author URL
			if ( isset( $_GET['taxonomy'] ) && 'author' === $_GET['taxonomy'] ) {
				if ( 'default' === $domain ) {
					if ( 'Slug' === $translation ) {
						$translation = __( 'Author URL', 'publishpress-multiple-authors' );
					}

					if ( 'The &#8220;slug&#8221; is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens.' === $translation ) {
						$translation = __( 'This forms part of the URL for the author’s profile page. If you choose a Mapped User, this URL is taken from the user’s account and can not be changed.',
							'publishpress-multiple-authors' );
					}
				}
			}

			return $translation;
		}

		/**
		 * Handle the action to reset author therms.
		 * Remove all authors and regenerate based on posts' authors and the setting to automatically create authors
		 * for specific roles.
		 */
		public function handle_action_reset_author_terms() {

			$actions = [
				'delete_mapped_authors',
				'delete_guest_authors',
				'create_post_authors',
				'create_role_authors',
			];

			if ( ! isset( $_GET['pp_action'] ) || ! in_array( $_GET['pp_action'],
					$actions ) || isset( $_GET['author_term_reset_notice'] ) ) {
				return;
			}

			$nonce = isset( $_GET['nonce'] ) ? $_GET['nonce'] : '';
			if ( ! wp_verify_nonce( $nonce, 'publishpress_multiple_authors_reset_authors_terms' ) ) {
				wp_redirect( admin_url( '/admin.php?page=pp-modules-settings&module=pp-multiple-authors-settings&author_term_reset_notice=fail' ),
					301 );

				return;
			}

			try {
				$result = do_action( 'publishpress_' . $_GET['pp_action'] );

				wp_redirect( admin_url( '/admin.php?page=pp-modules-settings&module=pp-multiple-authors-settings&author_term_reset_notice=success' ),
					301 );
			} catch ( Exception $e ) {
				wp_redirect( admin_url( '/admin.php?page=pp-modules-settings&module=pp-multiple-authors-settings&author_term_reset_notice=fail' ),
					301 );
			}
		}

		public function action_delete_mapped_authors() {
			global $wpdb;

			$query = '
                SELECT tt.term_id
                FROM `wp_term_taxonomy` AS tt
                WHERE
                tt.taxonomy = \'author\'
                AND (SELECT COUNT(*) FROM `wp_termmeta` AS tm WHERE tm.term_id = tt.term_id AND tm.meta_key = \'user_id\') > 0';

			$terms = $wpdb->get_results( $query );

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					wp_delete_term( $term->term_id, 'author' );
				}
			}
		}

		public function action_delete_guest_authors() {
			global $wpdb;

			$query = '
                SELECT tt.term_id
                FROM `wp_term_taxonomy` AS tt
                WHERE
                tt.taxonomy = \'author\'
                AND (SELECT COUNT(*) FROM `wp_termmeta` AS tm WHERE tm.term_id = tt.term_id AND tm.meta_key = \'user_id\') = 0';

			$terms = $wpdb->get_results( $query );

			if ( ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					wp_delete_term( $term->term_id, 'author' );
				}
			}
		}

		public function action_create_post_authors() {
			Installer::convert_post_author_into_taxonomy();
			Installer::add_author_term_for_posts();
		}

		public function action_create_role_authors() {
			// Create authors for users in the taxonomies selected for automatic creation of authors.
			global $publishpress;
			$roles = (array) $publishpress->modules->multiple_authors->options->author_for_new_users;

			// Check if we have any role selected to create an author for the new user.
			if ( ! empty( $roles ) ) {

				// Get users from roles
				$args  = [
					'role__in' => $roles,
				];
				$users = get_users( $args );

				if ( ! empty( $users ) ) {
					foreach ( $users as $user ) {
						// Create author for this user
						Author::create_from_user( $user->ID );
					}
				}
			}
		}

		/**
		 *
		 */
		public function handle_action_reset_author_terms_notice() {
			if ( ! isset( $_GET['author_term_reset_notice'] ) ) {
				return;
			}

			if ( $_GET['author_term_reset_notice'] === 'fail' ) {
				echo '<div class="notice notice-error is-dismissible">';
				echo '<p>' . __( 'Error. Author terms could not be reseted.',
						'publishpress-multiple-authors' ) . '</p>';
				echo '</div>';

				return;
			}

			if ( $_GET['author_term_reset_notice'] === 'success' ) {
				echo '<div class="notice notice-success is-dismissible">';
				echo '<p>' . __( 'Author terms reseted successfully.', 'publishpress-multiple-authors' ) . '</p>';
				echo '</div>';

				return;
			}
		}

		/**
		 * Fix the upload of media for posts when the user is a secondary author and can't edit others' posts.
		 *
		 * @param $caps
		 * @param $cap
		 * @param $user_id
		 * @param $args
		 *
		 * @return mixed
		 */
		public function filter_map_meta_cap( $caps, $cap, $user_id, $args ) {
			if ($cap === 'edit_post' && in_array('edit_others_posts', $caps)) {
				if (isset($args[0])) {
					$post_id = (int) $args[0];

					// Check if the user is an author for the current post
					if ( is_multiple_author_for_post( $user_id, $post_id )) {
						foreach ($caps as &$item) {
							// If he is an author for this post we should only check edit_posts.
							if ($item === 'edit_others_posts') {
								$item = 'edit_posts';
							}
						}
					}
				}
			}

			return $caps;
		}

		/**
		 * Load the update manager.
		 *
		 * @return mixed
		 */
		public function load_updater() {

			$container = Factory::get_container();

			return $container['edd_container']['update_manager'];
		}
	}
}
