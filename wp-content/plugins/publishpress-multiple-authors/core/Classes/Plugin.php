<?php
/**
 * File responsible for defining basic addon class
 *
 * @package     PublishPress\Multiple_authors
 * @author      PublishPress <help@publishpress.com>
 * @copyright   Copyright (C) 2018 PublishPress. All rights reserved.
 * @license     GPLv2 or later
 * @since       1.0.0
 */

namespace PublishPress\Addon\Multiple_authors\Classes;

use Twig_Environment;
use Twig_Extension_Debug;
use Twig_Loader_Filesystem;

defined( 'ABSPATH' ) or die( 'No direct script access allowed.' );

class Plugin {
	/**
	 * Twig instance
	 *
	 * @var Twig
	 */
	protected $twig;

	/**
	 * Flag for debug
	 *
	 * @var boolean
	 */
	protected $debug = false;

	/**
	 * The constructor
	 */
	public function __construct() {
		$twigPath = PP_MULTIPLE_AUTHORS_PATH_BASE . 'twig';

		$loader = new Twig_Loader_Filesystem( $twigPath );
		$this->twig = new Twig_Environment( $loader, array(
			'debug' => $this->debug,
		) );

		if ( $this->debug ) {
			$this->twig->addExtension( new Twig_Extension_Debug() );
		}
	}

	/**
	 * The method which runs the plugin
	 */
	public function init() {
		if ( ! $this->checkRequirements() ) {
			add_action( 'admin_notices', array( $this, 'warning_requirements' ) );

			return false;
		}

		add_filter( 'pp_module_dirs', array( $this, 'filter_module_dirs' ) );
	}

	/**
	 * Add custom module directory
	 *
	 * @param  array
	 * @return array
	 */
	public function filter_module_dirs( $dirs ) {
		$dirs['multiple-authors'] = rtrim( PP_MULTIPLE_AUTHORS_PATH_BASE, '/' );

		return $dirs;
	}

	/**
	 * Check if the system complies the requirements
	 *
	 * @return bool
	 */
	protected function checkRequirements() {
		return defined( 'PUBLISHPRESS_VERSION' ) && version_compare( PUBLISHPRESS_VERSION, '1.6.1', 'ge' );
	}

	public function warning_requirements() {
		echo $this->twig->render(
			'requirements-warning.twig',
			array(
				'lang' => array(
					'publishpress' => __( 'PublishPress', 'publishpress-multiple-authors' ),
					'warning' => __('PublishPress Multiple Authors requires __plugin__ 1.6.1 or later. Please, update.', 'publishpress-multiple-authors' ),
				),
			)
		);
	}
}
