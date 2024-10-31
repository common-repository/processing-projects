<?php
/**
 * Plugin Name: Processing Projects
 * Description: This plugin allows users to upload Processing Projects (in zip format) and easily add them throughout their website using shortcodes.
 * Version: 1.0.2
 * Author: Shane Watters
 * Author URI: http://rocketship.co.nz
 * Requires at least: 4.9
 * Tested up to: 4.9
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'ProcessingProjects' ) ) {

	/**
	 * Main ProcessingProjects Class.
	 *
	 * @class ProcessingProjects
	 * @version	0.0.1
	 */
	final class ProcessingProjects {

		/**
		 * ProcessingProjects version.
		 *
		 * @var string
		 */
		public $version = '1.0.0';


		/**
		 * ProcessingProjects Constructor.
		 */
		public function __construct()
		{
            $this->define_constants();
			$this->includes();
            $this->init_hooks();
		}
        /**
		 * Hook into actions and filters.
		 */
		private function init_hooks()
		{
			register_activation_hook( __FILE__, array( 'Processing_Projects_Install', 'activation_test' ) );
            add_action( 'init', array( 'Processing_Projects_Shortcode', 'init' ) );
		}

        /**
    	 * Define ProcessingProjects Constants.
    	 */
    	private function define_constants() {
			$upload_dir = wp_upload_dir();
			if ( ! defined( 'PROCESSING_PROJECTS_PLUGIN_URL' ) ) {
    			define( 'PROCESSING_PROJECTS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    		}
            if ( ! defined( 'PROCESSING_PROJECTS_UPLOAD_DIR' ) ) {
    			define( 'PROCESSING_PROJECTS_UPLOAD_DIR', $upload_dir['basedir'] . '/processing-projects/' );
    		}
            if ( ! defined( 'PROCESSING_PROJECTS_UPLOAD_URL' ) ) {
    			define( 'PROCESSING_PROJECTS_UPLOAD_URL',  $upload_dir['baseurl'] . '/processing-projects/' );
    		}
        }

		/**
		 * Include required core files used in admin and on the frontend.
		 */
		public function includes()
		{
			if (is_admin()) {
                include_once( 'includes/class-ps-install.php' );
                include_once( 'includes/class-ps-processing-project-post-type.php' );
				include_once( 'includes/class-ps-processing-project-meta-box.php' );
			}
            include_once( 'includes/class-ps-processing-project-shortcode.php' );
		}

	}

}

new ProcessingProjects();
