<?php
/**
 * @package PublishPress
 * @author  PublishPress
 *
 * Copyright (c) 2018 PublishPress
 *
 * ------------------------------------------------------------------------------
 * Based on Edit Flow
 * Author: Daniel Bachhuber, Scott Bressler, Mohammad Jangda, Automattic, and
 * others
 * Copyright (c) 2009-2016 Mohammad Jangda, Daniel Bachhuber, et al.
 * ------------------------------------------------------------------------------
 *
 * This file is part of PublishPress
 *
 * PublishPress is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * PublishPress is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with PublishPress.  If not, see <http://www.gnu.org/licenses/>.
 */

use PublishPress\Addon\Permissions\Factory;
use PublishPress\EDD_License\Core\Setting\Field\License_key as Field_License_key;
use PublishPress\Notifications\Traits\Dependency_Injector;

if ( ! class_exists( 'PP_Permissions' ) ) {
	/**
	 * class PP_Permissions
	 */
	class PP_Permissions extends PP_Module {
		use Dependency_Injector;

		const VALUE_YES = 'yes';

		const VALUE_NO = 'no';

		/**
		 * @var string
		 */
		const MENU_SLUG = 'pp-manage-capabilities';

		public $module_name = 'permissions';

		/**
		 * WordPress-EDD-License-Integration
		 *
		 * @var License
		 */
		protected $license_manager;

		public $module;

		/**
		 * Cache for the filter_user_has_cap method.
		 * Stores the result for each capability.
		 *
		 * @var array
		 */
		protected $user_has_cap_cache = [];

		/**
		 * Cache for the check_user_can_edit_metadata method.
		 *
		 * @var bool
		 */
		protected $check_user_can_edit_metadata_cache;

		/**
		 * Cache for the custom statuses.
		 *
		 * @var array
		 */
		protected $custom_statuses_cache;

		/**
		 * Cache for the custom user groups.
		 *
		 * @var array
		 */
		protected $users_roles_cache;

		/**
		 * Cache for the user's user groups.
		 *
		 * @var array
		 */
		protected $user_roles_cache;

		/**
		 * Cache for the capabilities relevants for this plugin.
		 *
		 * @var array
		 */
		protected $relevant_capabilities_cache;

		/**
		 * Cache for the permissions options.
		 *
		 * @var array
		 */
		protected $permissions_cache;

		/**
		 * @var string
		 */
		public $cap_manage_capabilities = 'pp_manage_capabilities';

		/**
		 * Construct the PP_Permissions class
		 */
		public function __construct() {
			$this->twigPath = dirname( dirname( dirname( __FILE__ ) ) ) . '/twig';

			$this->module_url = $this->get_module_url( __FILE__ );

			// Register the module with PublishPress
			$args = [
				'title'             => __( 'Permissions', 'publishpress-permissions' ),
				'module_url'        => $this->module_url,
				'icon_class'        => 'dashicons dashicons-feedback',
				'slug'              => 'permissions',
				'default_options'   => [
					'enabled'        => 'on',
					'edit_metadata'  => [
						'administrator' => [
							'global' => 'yes',
						],
					],
					'status_change'  => [
						'administrator' => [],
					],
					'license_key'    => '',
					'license_status' => '',
				],
				'configure_page_cb' => 'print_configure_view',
				'options_page'      => true,
			];

			// Apply a filter to the default options
			$args['default_options'] = apply_filters( 'pp_permissions_default_options', $args['default_options'] );

			$this->module = PublishPress()->register_module( $this->module_name, $args );

			parent::__construct();

			$this->configure_twig();
		}

		protected function configure_twig() {
			$function = new Twig_SimpleFunction( 'settings_fields', function () {
				return settings_fields( $this->module->options_group_name );
			} );
			$this->twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'nonce_field', function ( $context ) {
				return wp_nonce_field( $context );
			} );
			$this->twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'submit_button', function () {
				return submit_button();
			} );
			$this->twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'do_settings_sections', function ( $section ) {
				return do_settings_sections( $section );
			} );
			$this->twig->addFunction( $function );
		}

		/**
		 * Initialize the module. Conditionally loads if the module is enabled
		 */
		public function init() {
			add_action( 'admin_init', [ $this, 'register_settings' ] );
			add_action( 'admin_init', [ $this, 'load_updater' ] );
			add_action( 'admin_init', [ $this, 'save_capabilities_form' ] );

			add_filter( 'pp_editorial_metadata_user_can_edit', [ $this, 'check_user_can_edit_metadata' ] );
			add_filter( 'pp_custom_status_list', [ $this, 'filter_custom_status_list' ], 10, 2 );
			add_filter( 'publishpress_all_capabilities_groups', [ $this, 'filter_all_capabilities_groups' ], 10 );
			add_filter( 'publishpress_all_capabilities', [ $this, 'filter_all_capabilities' ], 10 );

			if ( $this->isWooCommerceActivated() ) {
				add_filter( 'publishpress_all_capabilities_groups', [ $this, 'filter_wc_capabilities_groups' ], 11 );
				add_filter( 'publishpress_all_capabilities', [ $this, 'filter_wc_capabilities' ], 11 );
			}

			// Menu
			add_filter( 'publishpress_admin_menu_slug', [ $this, 'filter_admin_menu_slug' ], 50 );
			add_action( 'publishpress_admin_menu_page', [ $this, 'action_admin_menu_page' ], 50 );
			add_action( 'publishpress_admin_submenu', [ $this, 'action_admin_submenu' ], 50 );

			add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );
		}

		/**
		 * @return bool
		 */
		protected function isWooCommerceActivated() {
			return class_exists( 'woocommerce' );
		}

		/**
		 * Print the content of the configure tab.
		 */
		public function print_configure_view() {
			echo $this->twig->render(
				'settings-tab.twig',
				[
					'form_action'        => menu_page_url( $this->module->settings_slug, false ),
					'options_group_name' => $this->module->options_group_name,
					'module_name'        => $this->module->slug,
				]
			);
		}

		/**
		 * Filters the menu slug.
		 *
		 * @param $menu_slug
		 *
		 * @return string
		 */
		public function filter_admin_menu_slug( $menu_slug ) {
			if ( empty( $menu_slug ) && $this->module_enabled( 'permissions' ) ) {
				$menu_slug = self::MENU_SLUG;
			}

			return $menu_slug;
		}

		/**
		 * Creates the admin menu if there is no menu set.
		 */
		public function action_admin_menu_page() {

			$publishpress = $this->get_service( 'publishpress' );

			if ( $publishpress->get_menu_slug() !== self::MENU_SLUG ) {
				return;
			}

			$publishpress->add_menu_page(
				esc_html__( 'Permissions', 'publishpress' ),
				apply_filters( 'pp_manage_capabilities_cap', $this->cap_manage_capabilities ),
				self::MENU_SLUG,
				[ $this, 'render_admin_page' ]
			);
		}

		/**
		 * Add necessary things to the admin menu
		 */
		public function action_admin_submenu() {
			$publishpress = $this->get_service( 'publishpress' );

			if ( isset( $_GET['aha'] ) ) {
				var_dump( current_user_can( $this->cap_manage_capabilities ) );
				die;
			}

			// Main Menu
			add_submenu_page(
				$publishpress->get_menu_slug(),
				esc_html__( 'Permissions', 'publishpress' ),
				esc_html__( 'Permissions', 'publishpress' ),
				apply_filters( 'pp_manage_capabilities_cap', $this->cap_manage_capabilities ),
				self::MENU_SLUG,
				[ $this, 'render_admin_page' ]
			);
		}

		/**
		 * Enqueue necessary admin styles, but only on the proper pages
		 */
		public function enqueue_admin_scripts() {
			if ( function_exists( 'get_current_screen' ) ) {
				if ( isset( $_GET['page'] ) && 'pp-manage-capabilities' === $_GET['page'] ) {
					wp_enqueue_script( 'publishpress-permissions-js', $this->module_url . 'assets/js/capabilities.js',
						[ 'jquery' ], PUBLISHPRESS_VERSION );

					wp_enqueue_style( 'publishpress-permissions-css', $this->module_url . 'assets/css/capabilities.css',
						[], PUBLISHPRESS_VERSION );
				}
			}
		}

		/**
		 * Return the groups for capabilities.
		 *
		 * @param $groups
		 *
		 * @return array
		 */
		public function filter_all_capabilities_groups( $groups ) {
			$newGroups = [
				'publishpress' => __( 'PublishPress', 'publishpress' ),
				'posts'        => __( 'Posts', 'publishpress' ),
				'pages'        => __( 'Pages', 'publishpress' ),
			];

			$groups = array_merge( $groups, $newGroups );

			return $groups;
		}

		/**
		 * Return the groups for WooCommerce capabilities.
		 *
		 * @param $groups
		 *
		 * @return array
		 */
		public function filter_wc_capabilities_groups( $groups ) {
			$newGroups = [
				'woocommerce' => __( 'WooCommerce', 'publishpress' ),
			];

			$groups = array_merge( $groups, $newGroups );

			return $groups;
		}

		/**
		 * Return all the capabilities
		 *
		 * @param $capabilities
		 *
		 * @return array
		 */
		public function filter_all_capabilities( $capabilities ) {
			$newCapabilities = [
				'pp_manage_roles'              => [
					'groups' => [ 'publishpress' ],
					'label'  => __( 'Manage roles', 'publishpress-permissions' ),
				],
				$this->cap_manage_capabilities => [
					'groups' => [ 'publishpress' ],
					'label'  => __( 'Manage permissions', 'publishpress-permissions' ),
				],
				'pp_view_calendar'             => [
					'groups' => [ 'publishpress' ],
					'label'  => __( 'View the calendar', 'publishpress-permissions' ),
				],
				'pp_view_content_overview'     => [
					'groups' => [ 'publishpress' ],
					'label'  => __( 'View the content overview', 'publishpress-permissions' ),
				],
				'edit_post_subscriptions'      => [
					'groups' => [ 'publishpress' ],
					'label'  => __( 'Edit notification status for posts', 'publishpress-permissions' ),
				],
				'edit_metadata'                => [
					'groups' => [ 'publishpress' ],
					'label'  => __( 'Edit post metadata', 'publishpress-permissions' ),
				],

				'delete_posts'           => [
					'groups' => [ 'posts' ],
					'label'  => __( 'Delete own posts', 'publishpress-permissions' ),
				],
				'delete_others_posts'    => [
					'groups' => [ 'posts' ],
					'label'  => __( 'Delete others posts', 'publishpress-permissions' ),
				],
				'delete_private_posts'   => [
					'groups' => [ 'posts' ],
					'label'  => __( 'Delete private posts', 'publishpress-permissions' ),
				],
				'delete_published_posts' => [
					'groups' => [ 'posts' ],
					'label'  => __( 'Delete published posts', 'publishpress-permissions' ),
				],
				'edit_posts'             => [
					'groups' => [ 'posts' ],
					'label'  => __( 'Edit own posts', 'publishpress-permissions' ),
				],
				'edit_others_posts'      => [
					'groups' => [ 'posts' ],
					'label'  => __( 'Edit other posts', 'publishpress-permissions' ),
				],
				'edit_private_posts'     => [
					'groups' => [ 'posts' ],
					'label'  => __( 'Edit private posts', 'publishpress-permissions' ),
				],
				'edit_published_posts'   => [
					'groups' => [ 'posts' ],
					'label'  => __( 'Edit published posts', 'publishpress-permissions' ),
				],
				'publish_posts'          => [
					'groups' => [ 'posts' ],
					'label'  => __( 'Publish posts', 'publishpress-permissions' ),
				],
				'read_private_posts'     => [
					'groups' => [ 'posts' ],
					'label'  => __( 'Read private posts', 'publishpress-permissions' ),
				],

				'delete_pages'           => [
					'groups' => [ 'pages' ],
					'label'  => __( 'Delete pages', 'publishpress-permissions' ),
				],
				'delete_others_pages'    => [
					'groups' => [ 'pages' ],
					'label'  => __( 'Delete others pages', 'publishpress-permissions' ),
				],
				'delete_private_pages'   => [
					'groups' => [ 'pages' ],
					'label'  => __( 'Delete private pages', 'publishpress-permissions' ),
				],
				'delete_published_pages' => [
					'groups' => [ 'pages' ],
					'label'  => __( 'Delete published pages', 'publishpress-permissions' ),
				],
				'edit_pages'             => [
					'groups' => [ 'pages' ],
					'label'  => __( 'Edit pages', 'publishpress-permissions' ),
				],
				'edit_others_pages'      => [
					'groups' => [ 'pages' ],
					'label'  => __( 'Edit others pages', 'publishpress-permissions' ),
				],
				'edit_private_pages'     => [
					'groups' => [ 'pages' ],
					'label'  => __( 'Edit private pages', 'publishpress-permissions' ),
				],
				'edit_published_pages'   => [
					'groups' => [ 'pages' ],
					'label'  => __( 'Edit published pages', 'publishpress-permissions' ),
				],
				'publish_pages'          => [
					'groups' => [ 'pages' ],
					'label'  => __( 'Publish pages', 'publishpress-permissions' ),
				],
				'read_private_pages'     => [
					'groups' => [ 'pages' ],
					'label'  => __( 'Read private pages', 'publishpress-permissions' ),
				],
			];

			// Permissions for custom statuses
			global $publishpress;
			$customStatuses = $publishpress->custom_status->get_custom_statuses();

			foreach ( $customStatuses as $status ) {
				$slug = str_replace( '-', '_', $status->slug );

				$newCapabilities[ 'status_change_' . $slug ] = [
					'groups' => [ 'publishpress' ],
					'label'  => sprintf( __( 'Change post status to "%s"', 'publishpress-permissions' ),
						$status->name ),
				];
			}

			$capabilities = array_merge( $capabilities, $newCapabilities );

			return $capabilities;
		}

		/**
		 * Return WooCommerce the capabilities
		 *
		 * @param $capabilities
		 *
		 * @return array
		 */
		public function filter_wc_capabilities( $capabilities ) {
			$newCapabilities = [
				'assign_shop_coupon_terms'      => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Assign shop coupon terms', 'publishpress-permissions' ),
				],
				'assign_product_terms'          => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Assign product terms', 'publishpress-permissions' ),
				],
				'assign_shop_order_terms'       => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Assign shop order terms', 'publishpress-permissions' ),
				],
				'delete_others_products'        => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete others products', 'publishpress-permissions' ),
				],
				'delete_others_shop_coupons'    => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete others shop coupons', 'publishpress-permissions' ),
				],
				'delete_others_shop_orders'     => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete others shop orders', 'publishpress-permissions' ),
				],
				'delete_private_products'       => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete private products', 'publishpress-permissions' ),
				],
				'delete_private_shop_coupons'   => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete private shop coupons', 'publishpress-permissions' ),
				],
				'delete_private_shop_orders'    => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete private shop orders', 'publishpress-permissions' ),
				],
				'delete_product'                => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete product', 'publishpress-permissions' ),
				],
				'delete_product_terms'          => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete product terms', 'publishpress-permissions' ),
				],
				'delete_products'               => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete products', 'publishpress-permissions' ),
				],
				'delete_published_products'     => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete published products', 'publishpress-permissions' ),
				],
				'delete_published_shop_coupons' => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete published shop coupons', 'publishpress-permissions' ),
				],
				'delete_published_shop_orders'  => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete published shop orders', 'publishpress-permissions' ),
				],
				'delete_shop_coupon'            => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete shop coupon', 'publishpress-permissions' ),
				],
				'delete_shop_coupon_terms'      => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete shop coupon terms', 'publishpress-permissions' ),
				],
				'delete_shop_coupons'           => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete shop coupons', 'publishpress-permissions' ),
				],
				'delete_shop_order'             => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete shop order', 'publishpress-permissions' ),
				],
				'delete_shop_order_terms'       => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete shop order terms', 'publishpress-permissions' ),
				],
				'delete_shop_orders'            => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Delete shop orders', 'publishpress-permissions' ),
				],
				'edit_others_products'          => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit others products', 'publishpress-permissions' ),
				],
				'edit_others_shop_coupons'      => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit others shop coupons', 'publishpress-permissions' ),
				],
				'edit_others_shop_orders'       => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit others shop orders', 'publishpress-permissions' ),
				],
				'edit_private_products'         => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit private products', 'publishpress-permissions' ),
				],
				'edit_private_shop_coupons'     => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit private shop coupons', 'publishpress-permissions' ),
				],
				'edit_private_shop_orders'      => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit private shop orders', 'publishpress-permissions' ),
				],
				'edit_product'                  => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit product', 'publishpress-permissions' ),
				],
				'edit_product_terms'            => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit product terms', 'publishpress-permissions' ),
				],
				'edit_products'                 => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit products', 'publishpress-permissions' ),
				],
				'edit_published_products'       => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit publihsed products', 'publishpress-permissions' ),
				],
				'edit_published_shop_coupons'   => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit published shop coupons', 'publishpress-permissions' ),
				],
				'edit_published_shop_orders'    => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit published shop orders', 'publishpress-permissions' ),
				],
				'edit_shop_coupon'              => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit shop coupon', 'publishpress-permissions' ),
				],
				'edit_shop_coupon_terms'        => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit shop coupon terms', 'publishpress-permissions' ),
				],
				'edit_shop_coupons'             => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit shop coupons', 'publishpress-permissions' ),
				],
				'edit_shop_order'               => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit shop order', 'publishpress-permissions' ),
				],
				'edit_shop_order_terms'         => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit shop order terms', 'publishpress-permissions' ),
				],
				'edit_shop_orders'              => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Edit shop orders', 'publishpress-permissions' ),
				],
				'manage_product_terms'          => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Manange product terms', 'publishpress-permissions' ),
				],
				'manage_shop_coupon_terms'      => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Manage shop coupon terms', 'publishpress-permissions' ),
				],
				'manage_shop_order_terms'       => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Manage shop order terms', 'publishpress-permissions' ),
				],
				'manage_woocommerce'            => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Manage WooCommerce', 'publishpress-permissions' ),
				],
				'publish_products'              => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Publish products', 'publishpress-permissions' ),
				],
				'publish_shop_coupons'          => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Publish shop coupons', 'publishpress-permissions' ),
				],
				'publish_shop_orders'           => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Publish shop orders', 'publishpress-permissions' ),
				],
				'read_private_products'         => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Read private products', 'publishpress-permissions' ),
				],
				'read_private_shop_coupons'     => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Read private shop coupons', 'publishpress-permissions' ),
				],
				'read_private_shop_orders'      => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Read private shop orders', 'publishpress-permissions' ),
				],
				'read_product'                  => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Read product', 'publishpress-permissions' ),
				],
				'read_shop_coupon'              => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Read shop coupon', 'publishpress-permissions' ),
				],
				'read_shop_order'               => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'Read shop order', 'publishpress-permissions' ),
				],
				'view_woocommerce_reports'      => [
					'groups' => [ 'woocommerce' ],
					'label'  => __( 'View WooCommerce reports', 'publishpress-permissions' ),
				],
			];

			$capabilities = array_merge( $capabilities, $newCapabilities );

			return $capabilities;
		}

		/**
		 *
		 */
		public function render_admin_page() {
			global $publishpress;

			$roles = get_editable_roles();

			$rolesNewList = [];
			foreach ( $roles as $role => $data ) {
				$rolesNewList[] = [
					'name'         => $role,
					'display_name' => $data['name'],
				];
			}
			unset( $roles );

			if ( isset( $_GET['role'] ) ) {
				$currentRole = sanitize_title( $_GET['role'] );
			} else {
				$currentRole = 'administrator';
			}

			// Get the current role's capabilities.
			$role                = get_role( $currentRole );
			$currentCapabilities = array_keys( $role->capabilities );

			$publishpress->settings->print_default_header( $publishpress->modules->permissions );

			echo $this->twig->render(
				'admin-page.twig',
				[
					'form_action'          => '#',
					'roles'                => $rolesNewList,
					'current_role'         => $currentRole,
					'current_capabilities' => $currentCapabilities,
					'capabilities'         => apply_filters( 'publishpress_all_capabilities', [] ),
					'groups'               => apply_filters( 'publishpress_all_capabilities_groups', [] ),
					'labels'               => [
						'groups'      => __( 'Groups of Permissions', 'publishpress' ),
						'permissions' => __( 'Permissions', 'publishpress' ),
						'loading'     => __( 'Loading...', 'publishpress' ),
					],
				]
			);

			$publishpress->settings->print_default_footer( $publishpress->modules->permissions );

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
				__( 'Licensing:', 'publishpress-permissions' ),
				'__return_false',
				$this->module->options_group_name
			);

			add_settings_field(
				'license_key',
				__( 'License key:', 'publishpress-permissions' ),
				[ $this, 'settings_license_key_option' ],
				$this->module->options_group_name,
				$this->module->options_group_name . '_license'
			);
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
				'link_more_info'     => 'https://publishpress.com/publishpress/docs/activate-license',
			];
			$field      = new Field_License_key( $field_args );

			echo $field;
		}

		public function get_custom_statuses() {
			if ( empty( $this->custom_statuses_cache ) ) {
				$publishpress = publishpress::instance();
				$statuses     = $publishpress->custom_status->get_custom_statuses();

				// Check if we don't have the publish status on the array
				$has_publish = false;
				foreach ( $statuses as $status ) {
					if ( 'publish' === $status->term_id ) {
						$has_publish = true;
					}
				}

				if ( ! $has_publish ) {
					$statuses[] = (object) [
						'slug' => 'publish',
						'name' => __( 'Publish', 'publishpress-permissions' ),
					];
				}

				$this->custom_statuses_cache = $statuses;
			}


			return $this->custom_statuses_cache;
		}

		protected function getRolesForUser( $user ) {
			if ( empty( $this->user_roles_cache ) ) {
				$this->user_roles_cache = [];

				// Get the user's roles
				foreach ( $user->roles as $role ) {
					$roleObj = new \WP_Role( $role, [] );

					$this->user_roles_cache[] = $roleObj->name;
				}
			}

			return $this->user_roles_cache;
		}

		/**
		 * Returns the capability for the specific status.
		 * If is the publish status, it returns a list of required capabilities.
		 *
		 * @param  string $status
		 *
		 * @return mixed
		 */
		public function get_status_capability_name( $status ) {
			if ( 'publish' === $status ) {
				$capabilities = [
					'publish_posts',
					'edit_published_posts',
					'delete_published_posts',
				];

				return $capabilities;
			}

			$capabilities = [
				'edit_posts',
				'status_change_' . $status,
			];

			return $capabilities;
		}

		/**
		 * Adds one or more capabilities to the given role.
		 *
		 * @param WP_Role|string $role
		 * @param mixed          $capability
		 */
		protected function role_add_cap( $role, $capability ) {
			if ( is_string( $role ) ) {
				$role = get_role( $role );
			}

			if ( ! is_object( $role ) ) {
				error_log( 'PP_Permissions::role_add_cap the role ' . maybe_serialize( $role ) . ' is not a valid object, or was not found.' );

				return;
			}

			if ( is_array( $capability ) ) {
				foreach ( $capability as $cap ) {
					$this->role_add_cap( $role, $cap );
				}

				return;
			}

			if ( is_string( $capability ) ) {
				$role->add_cap( $capability, true );
			}
		}

		/**
		 * Removes one or more capabilities to the given role.
		 *
		 * @param WP_Role $role
		 * @param mixed   $capability
		 */
		protected function role_remove_cap( $role, $capability ) {
			if ( is_array( $capability ) ) {
				foreach ( $capability as $cap ) {
					$this->role_remove_cap( $role, $cap );
				}

				return;
			}

			if ( is_string( $capability ) ) {
				$role->remove_cap( $capability );
			}
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
				PP_PERMISSIONS_ITEM_ID );

			$new_options = apply_filters( 'pp_permissions_validate_settings', $new_options );

			return $new_options;
		}

		/**
		 * Saves the form with role's capabilities.
		 */
		public function save_capabilities_form() {
			if ( 'POST' !== $_SERVER['REQUEST_METHOD'] ) {
				return;
			}

			if ( ( ! isset( $_GET['page'] ) || 'pp-manage-capabilities' !== $_GET['page'] )
			     || ( ! isset( $_POST['action'] ) || 'save' !== $_POST['action'] )
			) {
				return;
			}

			if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'manage_capabilities' ) ) {
				wp_die( $this->module->messages['nonce-failed'] );
			}

			if ( ! current_user_can( $this->cap_manage_capabilities ) ) {
				wp_die( $this->module->messages['invalid-permissions'] );
			}

			$roleName = sanitize_title( ( ! isset( $_GET['role'] ) || empty( $_GET['role'] ) ) ? 'administrator' : $_GET['role'] );

			// Get all capabilities to check which one was selected or unselected.
			$newCapabilities = array_keys( $_POST['capabilities'] );

			$role                = get_role( $roleName );
			$currentCapabilities = array_keys( $role->capabilities );

			// Check which capabilities we should to remove.
			$toRemove = array_diff( $currentCapabilities, $newCapabilities );

			// Ignore capabilities which we don't support. Specially capabilities from 3rd party plugins.
			$supportedCapabilities = array_keys( apply_filters( 'publishpress_all_capabilities', [] ) );
			$toRemove              = array_intersect( $toRemove, $supportedCapabilities );

			// Check if the user is in danger to lockout removing permissions needed for this screen
			$user = wp_get_current_user();

			if ( in_array( $roleName, $user->roles ) && in_array( $this->cap_manage_capabilities, $toRemove ) ) {
				$index = array_search( $this->cap_manage_capabilities, $toRemove );

				if ( ! empty( $index ) ) {
					// @todo Show a friendly warning message
					unset( $toRemove[ $index ] );
				}
			}

			// Remove the capabilities.
			if ( ! empty( $toRemove ) ) {
				foreach ( $toRemove as $capability ) {
					$role->remove_cap( $capability );
				}
			}

			// Check the capabilities we need to add
			$toAdd = array_diff( $newCapabilities, $currentCapabilities );

			if ( ! empty( $toAdd ) ) {
				foreach ( $toAdd as $capability ) {
					$role->add_cap( $capability, true );
				}
			}
		}

		/**
		 * Check if the current user can edit metadata
		 *
		 * @param string $can_edit
		 *
		 * @return bool
		 */
		public function check_user_can_edit_metadata( $can_edit ) {
			if ( null !== $this->check_user_can_edit_metadata_cache ) {
				return $this->check_user_can_edit_metadata_cache;
			}

			$this->check_user_can_edit_metadata_cache = current_user_can( 'edit_metadata' );

			return $this->check_user_can_edit_metadata_cache;
		}

		/**
		 * Sanitizes the slug replacing dashes to underscores
		 *
		 * @param  string $slug
		 *
		 * @return string
		 */
		protected function sanitize_slug( $slug ) {
			return str_replace( '-', '_', $slug );
		}

		/**
		 * Filters the list of custom statuses
		 *
		 * @param  array   $custom_statuses
		 * @param  WP_Post $post
		 *
		 * @return  array
		 */
		public function filter_custom_status_list( $custom_statuses, $post ) {
			$filtered       = [];
			$option_group   = 'global';
			$publishpress   = publishpress::instance();
			$default_status = $publishpress->custom_status->module->options->default_status;

			if ( ! is_null( $post ) ) {
				// Adding a new post? Set the correct default status
				if ( 'auto-draft' === $post->post_status ) {
					$post->post_status = $default_status;
				}
			}

			foreach ( $custom_statuses as &$status ) {
				$slug = $this->sanitize_slug( $status->slug );

				// Check if the user, or any of his user groups are capable to use the status. If not, but it is the
				// current status, we still display it.
				if (
					current_user_can( 'status_change_' . $slug )
					|| ( is_null( $post ) ? false : $status->slug === $post->post_status )
					|| $status->slug === $default_status
				) {
					$filtered[] = $status;
				}
			}

			return $filtered;
		}

		/**
		 * Loads the EDD updater
		 */
		public function load_updater() {

			$container = Factory::get_container();

			return $container['edd_container']['update_manager'];
		}
	}
}// End if().
