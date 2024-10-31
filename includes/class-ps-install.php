<?php
/**
 * Installation related functions and actions.
 *
 * @class    Processing_Projects_Install
 * @version  0.0.1
 * @package  ProcessingProjects/Classes
 * @category Admin
 * @author   Rocketship Multimedia
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Processing_Projects_Install Class.
 */
class Processing_Projects_Install {


    /**
     *  The activation test, automatically disable the plugin wp- is not
     *  installed or this is not a multisite installation
     */
    public static function activation_test($network_wide)
    {
        $upload_dir = wp_upload_dir();
        if (!wp_mkdir_p( PROCESSING_PROJECTS_UPLOAD_DIR )) {
            trigger_error('This plugin cannot be activated because your uploads directory is not currently writable', E_USER_ERROR);
        }
        Processing_Projects_Install::create_files();
    }

    /**
	 * Create files/directories.
	 */
	private static function create_files() {
        $files = array(
			array(
				'base' 		=> PROCESSING_PROJECTS_UPLOAD_DIR,
				'file' 		=> 'index.html',
				'content' 	=> '',
			),
			array(
				'base' 		=> PROCESSING_PROJECTS_UPLOAD_DIR,
				'file' 		=> 'index.php',
				'content' 	=> '//silence is golden',
			),
		);

        foreach ( $files as $file ) {
			if ( wp_mkdir_p( $file['base'] ) && ! file_exists( trailingslashit( $file['base'] ) . $file['file'] ) ) {
				if ( $file_handle = @fopen( trailingslashit( $file['base'] ) . $file['file'], 'w' ) ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}
		}
    }
}
