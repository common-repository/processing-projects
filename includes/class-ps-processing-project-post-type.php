<?php
/**
 *
 * Registers the processing-shortcode post type
 *
 * @class     Processing_Projects_Post_Type
 * @Version: 0.0.1
 * @package   ProcessingProjects/Classes
 * @category  Class
 * @author    Rocketship Multimedia
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Processing_Projects_Post_Type Class.
 */
class Processing_Projects_Post_Type {

    public static $default_fields = array(
        'iframe_width' => 960,
        'iframe_height' => 500,
        'index_file' => 'index.html',
    );

    /**
	 * Meta box error messages.
	 *
	 * @var array
	 */
	public static $meta_box_errors  = array();

    /**
	 * Is meta boxes saved once?
	 *
	 * @var boolean
	 */
	private static $saved_meta_boxes = false;

    /**
	 * Constructor.
	 */
	public function __construct() {
        $this->includes();

        add_action( 'init', array( $this, 'register_post_type' ), 5 );

		add_action( 'post_edit_form_tag', array( $this, 'allow_file_uploads'));
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ), 30 );
        add_action( 'save_post', array( $this, 'save_meta_boxes' ), 1, 2 );

        // Error handling (for showing errors from meta boxes on next page load)
		add_action( 'admin_notices', array( $this, 'output_errors' ) );
		add_action( 'shutdown', array( $this, 'save_errors' ) );

        add_filter( 'manage_processing-project_posts_columns', array( $this, 'processing_project_table_columns' ) );
        add_action( 'manage_processing-project_posts_custom_column', array( $this, 'render_processing_project_table_columns' ), 2 );
        add_action( 'before_delete_post', array( $this, 'remove_project_dir' ));
	}

    public function includes(){
        require_once(ABSPATH .'/wp-admin/includes/class-wp-filesystem-base.php');
        require_once(ABSPATH .'/wp-admin/includes/class-wp-filesystem-direct.php');
        require_once(ABSPATH .'/wp-admin/includes/file.php');
        require_once(ABSPATH .'/wp-admin/includes/class-file-upload-upgrader.php');
    }

    /**
     * Register core post type.
     */
    public function register_post_type() {
        if ( post_type_exists('processing-project') ) {
            return;
        }

        $processingProjectLabels = array(
                        'name'                  => __( 'Processing Projects', 'processing-shortcodes' ),
                        'singular_name'         => _x(
                                                        'Processing Project',
                                                        'Processing Project post type singular name',
                                                        'processing-shortcodes'
                                                    ),
                        'add_new'               => __( 'Add Processing Project', 'processing-shortcodes' ),
                        'add_new_item'          => __( 'Add New Processing Project', 'processing-shortcodes' ),
                        'edit'                  => __( 'Edit', 'processing-shortcodes' ),
                        'edit_item'             => __( 'Edit Processing Project', 'processing-shortcodes' ),
                        'new_item'              => __( 'New Processing Project', 'processing-shortcodes' ),
                        'view'                  => __( 'View Processing Project', 'processing-shortcodes' ),
                        'view_item'             => __( 'View Processing Project', 'processing-shortcodes' ),
                        'search_items'          => __( 'Search Processing Projects', 'processing-shortcodes' ),
                        'not_found'             => __( 'No Processing Projects found', 'processing-shortcodes' ),
                        'not_found_in_trash'    => __( 'No Processing Projects found in trash', 'processing-shortcodes' ),
                        'parent'                => __( 'Parent Processing Projects', 'processing-shortcodes' ),
                        'menu_name'             => _x( 'Processing Projects', 'Admin menu name', 'processing-shortcodes' ),
                        'filter_items_list'     => __( 'Filter Processing Projects', 'processing-shortcodes' ),
                        'items_list_navigation' => __( 'Processing Projects navigation', 'processing-shortcodes' ),
                        'items_list'            => __( 'Processing Projects list', 'processing-shortcodes' ),
                    );

        register_post_type('processing-project',
            array(
                'labels'              => $processingProjectLabels,
                'description'         => __( 'This is where Processing Projects are stored.', 'processing-shortcodes' ),
                'public'              => false,
                'show_ui'             => true,
                'capability_type'     => 'page',
                'map_meta_cap'        => true,
                'publicly_queryable'  => false,
                'exclude_from_search' => true,
                'show_in_menu'        => current_user_can( 'edit_posts' ),
                'hierarchical'        => false,
                'show_in_nav_menus'   => false,
                'rewrite'             => false,
                'query_var'           => false,
                'supports'            => array('author'),
                'has_archive'         => false,
            )
        );
    }

    public function allow_file_uploads(){
        global $post;
		if ( empty( $post )) {
			return;
		}
        if ( in_array( $post->post_type, array( 'processing-project' ) ) ) {
             echo ' enctype="multipart/form-data"';
	    }
    }

    /**
	 * Add Meta boxes.
	 */
	public function add_meta_boxes() {
		add_meta_box(
            'ps-processing-shortcode',
            'Processing Project',
            'Processing_Projects_Meta_Box::output',
            'processing-project',
            'normal',
            'high'
        );
	}

    /**
	 * Check if we're saving, the trigger an action based on the post type.
	 *
	 * @param  int $post_id
	 * @param  object $post
	 */
	public function save_meta_boxes( $post_id, $post ) {

		// $post_id and $post are required
		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		// Dont' save meta boxes for revisions or autosaves
		if ( defined( 'DOING_AUTOSAVE' ) || is_int( wp_is_post_revision( $post ) ) || is_int( wp_is_post_autosave( $post ) ) ) {
			return;
		}

        if (!wp_mkdir_p( PROCESSING_PROJECTS_UPLOAD_DIR )) {
            Processing_Projects_Post_Type::add_error('This project can not be saved because your content directory is not currently writtable.');
            return;
        }

		// Check the nonce
		if ( empty( $_POST['ps_meta_nonce'] ) || ! wp_verify_nonce( $_POST['ps_meta_nonce'], 'ps_save_data' ) ) {
			return;
		}

		// Check the post being saved == the $post_id to prevent triggering this call for other save_post events
		if ( empty( $_POST['post_ID'] ) || $_POST['post_ID'] != $post_id ) {
			return;
		}

		// Check user has permission to edit
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

        self::$saved_meta_boxes = true;

		// Check the post type
		if ( in_array( $post->post_type, array( 'processing-project' ) ) ) {
			$project = Processing_Projects_Meta_Box::save( $post_id, $post );
		}

        $this_post = array(
            'ID'           => $post->ID,
            'post_title'   => $project['title'],
            'post_content' => $project['content']
        );
        wp_update_post( $this_post );
	}

    /**
	 * When a processing project post is deleted we also need to remove the project dir
	 */
    public function remove_project_dir($post_id){
        if(get_post_type($post_id) === 'processing-project'){
            $project_dir = get_post_meta($post_id, '_pp_project_dir', TRUE);
            $filesystem = new WP_Filesystem_Direct();
            $filesystem->rmdir($project_dir, true);
        }
    }

    /**
	 * Add an error message.
	 * @param string $text
	 */
	public static function add_error( $text ) {
		self::$meta_box_errors[] = $text;
	}

	/**
	 * Save errors to an option.
	 */
	public function save_errors() {
		update_option( 'pp_meta_box_errors', self::$meta_box_errors );
	}

	/**
	 * Show any stored error messages.
	 */
	public function output_errors() {
		$errors = maybe_unserialize( get_option( 'pp_meta_box_errors' ) );

		if ( ! empty( $errors ) ) {

			echo '<div class="notice notice-error is-dismissible">';

			foreach ( $errors as $error ) {
				echo '<p>' . wp_kses_post( $error ) . '</p>';
			}

			echo '</div>';

			// Clear
			delete_option( 'pp_meta_box_errors' );
		}
	}

    /**
	 * Define custom columns for processing projects.
	 * @param  array $existing_columns
	 * @return array
	 */
	public function processing_project_table_columns( $existing_columns ) {
		$columns                     = array();
		$columns['cb']               = $existing_columns['cb'];
		$columns['title']            = $existing_columns['title'];
		$columns['shortcode']        = 'Shortcode';
		$columns['author']           = $existing_columns['author'];
		$columns['date']             = $existing_columns['date'];
		return $columns;
	}

    /**
	 * Output custom columns for processing projects.
	 * @param string $column
	 */
	public function render_processing_project_table_columns( $column ) {
		global $post;

		switch ( $column ) {
			case 'shortcode' :
                $content_post = get_post($post->ID);
                echo $content_post->post_content;
			break;
        }
    }
}

new Processing_Projects_Post_Type();
