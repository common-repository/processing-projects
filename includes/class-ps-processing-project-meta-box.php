<?php
/**
 * Processing_Projects_Meta_Box class
 *
 * Displays the processing project meta box,
 * this is used to upload the zip file containing the processing project
 * and set the width/height of the iframe in which it will be displayed
 *
 * @class     Processing_Projects_Meta_Box
 * @Version:  0.0.1
 * @package   ProcessingProjects/Classes
 * @category  Class
 * @author    Rocketship Multimedia
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Processing_Projects_Meta_Box Class.
 */
class Processing_Projects_Meta_Box {

	/**
	 * Output the metabox.
	 *
	 * @param WP_Post $post
	 */
	public static function output( $post ) {
		global $post;
        $project_url = get_post_meta($post->ID, '_pp_project_url', TRUE);
        $iframe_width = get_post_meta($post->ID, '_pp_iframe_width', TRUE);
        if(!$iframe_width){
            $iframe_width = 960;
        }
        $iframe_height = get_post_meta($post->ID, '_pp_iframe_height', TRUE);
        if(!$iframe_height){
            $iframe_height = 500;
        }
        $index_file = get_post_meta($post->ID, '_pp_index_file', TRUE);
        if(!$index_file){
            $index_file = 'index.html';
        }
		?>
        <div id="upload-project" class="form-wrap">
            <?php
                wp_nonce_field( 'ps_save_data', 'ps_meta_nonce' );
                if($project_url){
                    ?>
                    <div>
                        <p>
                            <strong>Project Location: </strong>
                            <a href="<?php echo $project_url; ?>" target="_blank"><?php echo get_the_title($post->ID); ?></a></br>
                        </p>
                    </div>
                    <?php
                }
                else {
                    ?>
                    <p class="description">
						Upload your p5.js Processing Project in .zip format here.</br>
						<a href="http://rocketship.co.nz/wp-content/example.zip">Download</a> an example zip file.
					</p>
            		<input name="project_zip" type="file"/>
					<p class="description">The maximum upload size is <?php echo size_format( wp_max_upload_size(), 1 ); ?>. You can increase this by changing the values for upload_max_filesize and post_max_size in your php.ini file.</p>
                    <?php
                }
            ?>
            <label for="iframe_width">Iframe Width</label>
        	<input type="number" step="1" name="iframe_width" value="<?php echo $iframe_width; ?>"/>
        	<label for="iframe_height">Iframe Height</label>
        	<input type="number" step="1" name="iframe_height" value="<?php echo $iframe_height; ?>"/>
        	<label for="index_file">HTML File</label>
            <input type="text" name="index_file" value="<?php echo $index_file; ?>"/>
            <p class="description">The value of this field only needs to changed if your projects main file is not index.html.</p>
        </div>
		<?php
	}

	/**
	 * Save meta box data.
	 */
	public static function save( $post_id, $post ) {
        $shortcode_args = array();
        $title = get_the_title($post->ID) == 'Auto Draft' ? 'P5.js Project - Project Folder Missing' : get_the_title($post->ID);

        foreach(Processing_Projects_Post_Type::$default_fields as $field_name => $default_value ){
            if(isset($_POST[$field_name])){
                $shortcode_args[$field_name] = $_POST[$field_name];
            }
            else {
                $shortcode_args[$field_name] = $default_value;
            }
            update_post_meta($post->ID, '_pp_'.$field_name, $shortcode_args[$field_name]);
        }

        if(!empty($_FILES['project_zip']['name'])) {
            // Setup the array of supported file types. In this case, it has to be a zip file.
            $supported_types = array('application/zip', 'application/zip-compressed', 'application/x-zip-compressed');

            // Get the file type of the upload
            $uploaded_type = $_FILES['project_zip']['type'];
            $temp_name = $_FILES["project_zip"]["tmp_name"];
            if(in_array($uploaded_type, $supported_types)) {
    			add_filter('filesystem_method', array( 'Processing_Projects_Meta_Box', '_return_direct'));
                global $wp_filesystem;
                if ( ! $wp_filesystem ) {
                    WP_Filesystem();
                }
				remove_filter('filesystem_method', array( 'Processing_Projects_Meta_Box', '_return_direct'));
                $file_upload = new File_Upload_Upgrader('project_zip', 'package');
                $zip = new ZipArchive;
                if ($zip->open($file_upload->package) === true) {
                    $first_file = $zip->getNameIndex(0);
                    $zip_path = pathinfo( $first_file, PATHINFO_DIRNAME );
                    $zipped_folder = $zip_path === '.' ? $first_file : $zip_path . '/';
                    $remote_destination = PROCESSING_PROJECTS_UPLOAD_DIR . $zipped_folder;
                    $exiting_project = $wp_filesystem->exists($remote_destination);
                    if($exiting_project){
                        Processing_Projects_Post_Type::add_error('This zip file can not be uploaded because the following path already exists:</br>' . $remote_destination);
                    }
                    else {
                        $upload = unzip_file($file_upload->package, PROCESSING_PROJECTS_UPLOAD_DIR);
						if ( is_wp_error( $upload ) ) {
							Processing_Projects_Post_Type::add_error('There was an error unzipping your file.<br/> ' . $upload->get_error_message());
						}
                        else {
                            add_post_meta($post->ID, '_pp_project_dir', $remote_destination);
                            add_post_meta($post->ID, '_pp_project_url', PROCESSING_PROJECTS_UPLOAD_URL . $zipped_folder . $shortcode_args['index_file']);
                            $title = 'P5.js Project - ' . str_replace('/', '', $zipped_folder);
                        }
                    }
                }
                else {
                    Processing_Projects_Post_Type::add_error($zip->open($file_upload->package));
                }
                $file_upload->cleanup();
            }
            else {
				if(isset($_FILES['project_zip']['error'])){
					Processing_Projects_Post_Type::add_error(
						'Uploaded file exceeds the maximum size.<br/>'.
						' File needs to be smaller than '. size_format( wp_max_upload_size(), 1 )
					);
				}
				else {
                	Processing_Projects_Post_Type::add_error(
						'The uploaded file must be a zip file. Please try again.'
					);
				}
            }
        }
        $project_url = get_post_meta($post->ID, '_pp_project_url', TRUE);
        if ($project_url && strpos($project_url, $shortcode_args['index_file']) === false) {
            $index_pos = strripos ( $project_url , '/' ) + 1;
            $new_url = substr( $project_url, 0, $index_pos ) . $shortcode_args['index_file'];
            update_post_meta($post->ID, '_pp_project_url', $new_url);
        }

        $shortcode = '[pp-shortcode id="'.$post->ID.'" width="'.$shortcode_args['iframe_width'].'" height="'.$shortcode_args['iframe_height'].'"]';
        return array('title' => $title, 'content' => $shortcode);
	}

	public static function _return_direct() {
		return 'direct';
	}
}
