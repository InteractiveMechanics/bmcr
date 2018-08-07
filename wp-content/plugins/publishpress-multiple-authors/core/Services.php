<?php
/**
 * @package     PublishPress\Multiple_authors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Addon\Multiple_authors;

use Pimple\Container as Pimple;
use Pimple\ServiceProviderInterface;
use PP_Multiple_Authors;
use PublishPress\EDD_License\Core\Container as EDDContainer;
use PublishPress\EDD_License\Core\Services as EDDServices;
use PublishPress\EDD_License\Core\ServicesConfig as EDDServicesConfig;
use Twig_Environment;
use Twig_Loader_Filesystem;
use Twig_SimpleFunction;

defined( 'ABSPATH' ) or die( 'No direct script access allowed.' );

/**
 * Class Services
 */
class Services implements ServiceProviderInterface {
	/**
	 * @since 1.2.3
	 * @var PP_Multiple_Authors
	 */
	protected $module;

	/**
	 * Services constructor.
	 *
	 * @since 1.2.3
	 *
	 * @param PP_Multiple_Authors $module
	 */
	public function __construct( PP_Multiple_Authors $module ) {
		$this->module = $module;
	}

	/**
	 * Registers services on the given container.
	 *
	 * This method should only be used to configure services and parameters.
	 * It should not get services.
	 *
	 * @since 1.2.3
	 *
	 * @param Pimple $container A container instance
	 */
	public function register( Pimple $container ) {
		$container['module'] = function ( $c ) {
			return $this->module;
		};

		$container['LICENSE_KEY'] = function ( $c ) {
			$key = '';
			if ( isset( $c['module']->module->options->license_key ) ) {
				$key = $c['module']->module->options->license_key;
			}

			return $key;
		};

		$container['LICENSE_STATUS'] = function ( $c ) {
			$status = '';

			if ( isset( $c['module']->module->options->license_status ) ) {
				$status = $c['module']->module->options->license_status;
			}

			return $status;
		};

		$container['edd_container'] = function ( $c ) {
			$config = new EDDServicesConfig();
			$config->setApiUrl( 'https://publishpress.com' );
			$config->setLicenseKey( $c['LICENSE_KEY'] );
			$config->setLicenseStatus( $c['LICENSE_STATUS'] );
			$config->setPluginVersion( PUBLISHPRESS_MULTIPLE_AUTHORS_VERSION );
			$config->setEddItemId( PP_MULTIPLE_AUTHORS_ITEM_ID );
			$config->setPluginAuthor( 'PublishPress' );
			$config->setPluginFile( PP_MULTIPLE_AUTHORS_FILE );

			$services = new EDDServices( $config );

			$eddContainer = new EDDContainer();
			$eddContainer->register( $services );

			return $eddContainer;
		};

		$container['twig_loader'] = function ( $c ) {
			$loader = new Twig_Loader_Filesystem( PP_MULTIPLE_AUTHORS_PATH_BASE . '/twig' );

			return $loader;
		};

		$container['twig'] = function ( $c ) {
			$twig = new Twig_Environment( $c['twig_loader'], [
				'debug' => true,
			] );

			$twig->addExtension(new \Twig_Extension_Debug());

			$function = new Twig_SimpleFunction( 'settings_fields', function () use ( $c ) {
				return settings_fields( 'publishpress_multiple_authors_options' );
			} );
			$twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'nonce_field', function ( $context ) {
				return wp_nonce_field( $context );
			} );
			$twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'submit_button', function () {
				return submit_button();
			} );
			$twig->addFunction( $function );

			$function = new Twig_SimpleFunction( '__', function ( $id ) {
				return __( $id, 'publishpress-multiple-authors' );
			} );
			$twig->addFunction( $function );

			$function = new Twig_SimpleFunction( 'do_settings_sections', function ( $section ) {
				return do_settings_sections( $section );
			} );
			$twig->addFunction( $function );

			$function = new \Twig_SimpleFunction( 'esc_attr', function ( $string ) {
				return esc_attr( $string );
			} );
			$twig->addFunction( $function );

			$function = new \Twig_SimpleFunction( 'get_avatar', function ( $user_email, $size = 35) {
				return get_avatar( $user_email, $size );
			} );
			$twig->addFunction( $function );

			return $twig;
		};
	}
}
