<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Processing_Projects_Shortcode class
 *
 * @class       Processing_Projects_Shortcode
 * @version     1.0.0
 * @package     ProcessingProjects/Classes
 * @category    Class
 * @author     Rocketship Multimedia
 */
class Processing_Projects_Shortcode {

	/**
	 * Init shortcode.
	 */
	public static function init() {
		add_shortcode('pp-shortcode', 'Processing_Projects_Shortcode::output' );
	}


    /**
	 * outputs the shortcode html
	 * Example [pp-shortcode id="155" width="1920" height="1080"].
	 *
	 * @param array $atts
	 * @return html
	 */
	public static function output( $atts ) {
        $atts = shortcode_atts(
            array(
    			'id'  => '',
    			'width'   => '960',
    			'height'   => '500'
		    ),
            $atts,
            'pp-shortcode'
        );
        $project_url = get_post_meta($atts['id'], '_pp_project_url', TRUE);
        $html ='<iframe src="'.$project_url.'" width="'.$atts['width'].'" height="'.$atts['height'].'"></iframe>';
		return $html;
	}



}
