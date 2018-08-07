<?php
/**
 * File responsible for defining basic general constants used by the plugin.
 *
 * @package     PublishPress\Multiple_authors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	die( 'No direct script access allowed.' );
}

if ( ! defined( 'PP_MULTIPLE_AUTHORS_LOADED' ) ) {
	$autoloadPath = __DIR__ . '/vendor/autoload.php';
	if ( file_exists( $autoloadPath ) ) {
		require_once $autoloadPath;
	}

	if ( ! defined( 'PP_MULTIPLE_AUTHORS_PLUGIN_NAME' ) ) {
		define( 'PP_MULTIPLE_AUTHORS_PLUGIN_NAME', 'PublishPress Multiple Authors' );
	}

	if ( ! defined( 'PP_MULTIPLE_AUTHORS_MIN_PARENT_VERSION' ) ) {
		define( 'PP_MULTIPLE_AUTHORS_MIN_PARENT_VERSION', '1.14.0' );
	}

	$initializer = new PublishPressAddonFramework\Initializer(
		PP_MULTIPLE_AUTHORS_PLUGIN_NAME,
		PP_MULTIPLE_AUTHORS_MIN_PARENT_VERSION
	);

	if ( $initializer->isPublishPressInstalled() ) {
		define( 'PP_MULTIPLE_AUTHORS_ITEM_ID', '7203' );
		define( 'PP_MULTIPLE_AUTHORS_PATH_BASE', plugin_dir_path( __FILE__ ) );

		define( 'PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION', '2.1.3' );
		define( 'PP_MULTIPLE_AUTHORS_FILE',
			'publishpress-multiple-authors/publishpress-multiple-authors.php' );
		define( 'PP_MULTIPLE_AUTHORS_MODULE_PATH', __DIR__ . '/modules/multiple-authors' );
		define( 'PP_MULTIPLE_AUTHORS_ITEM_NAME', 'Multiple Authors for PublishPress' );
		define( 'PP_MULTIPLE_AUTHORS_LIB_PATH', PP_MULTIPLE_AUTHORS_PATH_BASE . '/core' );
		define( 'PP_MULTIPLE_AUTHORS_LOADED', 1 );

		/**
		 * @deprecated
		 */
		define( 'PP_MULTIPLE_AUTHORS_VERSION', PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION );

		if ( ! class_exists( 'PP_Module' ) ) {
			require_once( PUBLISHPRESS_ROOT . '/common/php/class-module.php' );
		}

		// Load the modules
		if ( ! class_exists( 'PP_Multiple_Authors' ) ) {
			require_once PP_MULTIPLE_AUTHORS_MODULE_PATH . '/multiple-authors.php';
		}

		require_once __DIR__ . '/template-tags.php';
		require_once __DIR__ . '/integrations/amp.php';

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::add_command( 'publishpress-multiple-authors', 'PublishPress\\Addon\\Multiple_authors\\WP_Cli' );
		}
	}
}
