<?php
/**
 * @package     PublishPress\Permissions\
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.3
 */

namespace PublishPress\Addon\Permissions;

defined( 'ABSPATH' ) or die( 'No direct script access allowed.' );


/**
 * Class Installer
 */
class Installer {
	public static function install() {
		$role = get_role( 'administrator' );

		$role->add_cap( 'edit_metadata', true );
		$role->add_cap( 'pp_manage_capabilities', true );

		// Give permission to manipulate all statuses
		$role->add_cap( 'status_change_pitch', true );
		$role->add_cap( 'status_change_assigned', true );
		$role->add_cap( 'status_change_in_progress', true );
		$role->add_cap( 'status_change_draft', true );
		$role->add_cap( 'status_change_pending', true );
		$role->add_cap( 'status_change_publish', true );
		$role->add_cap( 'status_change_private', true );
		$role->add_cap( 'status_change_future', true );
	}

	/**
	 * @param $previous_version
	 */
	public static function upgrade( $previous_version ) {
		if ( version_compare( $previous_version, '1.0.4', '<=' ) ) {
			self::convert_user_group_permissions_to_role_capabilities();

			$role = get_role( 'administrator' );
			$role->add_cap( 'pp_manage_capabilities', true );
		}

		if ( version_compare( $previous_version, '2.0.4', '<' ) ) {
			$role = get_role( 'administrator' );
			$role->add_cap( 'pp_manage_capabilities', true );
		}
	}

	/**
	 * Convert the User Group's permissions into role's capabilities.
	 * This is related to the Permissions add-on, but since we remove the
	 * legacy User Groups after upgrading, if we leave this migration into
	 * the permissions add-on we wouldn't be able to restore the groups and
	 * permissions.
	 * We only migrate if the add-on is installed.
	 */
	protected static function convert_user_group_permissions_to_role_capabilities() {
		$container   = Factory::get_container();
		$permissions = $container['module'];

		if ( ! $permissions->module_enabled( 'permissions' ) ) {
			return;
		}

		// Start checking the remaining caps
		$options = $permissions->module->options;

		/**
		 * Status change
		 */
		foreach ( $options->status_change as $userGroup => $statuses ) {
			foreach ( $statuses as $status => $data ) {
				$selected = $data['global'] === 'yes';

				if ( $selected ) {
					$role = get_role( $userGroup );

					if ( is_object( $role ) ) {
						if ( $status === 'publish' ) {
							// Set capabilities related to publishing
							// @todo Allow to set each capability
							$role->add_cap( 'edit_metadata', true );
							$role->add_cap( 'edit_posts', true );
							$role->add_cap( 'publish_posts', true );
							$role->add_cap( 'edit_published_posts', true );
							$role->add_cap( 'edit_private_posts', true );
							$role->add_cap( 'edit_others_posts', true );
							$role->add_cap( 'delete_published_posts', true );
							$role->add_cap( 'delete_private_posts', true );
							$role->add_cap( 'delete_others_posts', true );
						} else {
							$role->add_cap( 'edit_posts', true );
							$role->add_cap( 'status_change_' . $status, true );
						}
					}
				}
			}
		}

		/**
		 * Edit metadata
		 */
		foreach ( $options->edit_metadata as $userGroup => $data ) {
			$selected = $data['global'] === 'yes';

			if ( $selected ) {
				$role = get_role( $userGroup );

				if ( is_object( $role ) ) {
					$role->add_cap( 'edit_metadata', true );
				}
			}
		}
	}
}
