<?php
/**
 * Shortcodes
 *
 * @package Tapify/Classes
 * @version 1.1.5
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tapify Shortcodes class.
 */
class TPF_shortcode {

	/**
	 * Init shortcodes.
	 */
	public static function init() {
		$shortcodes = array(
			'tapify'                    => __CLASS__ . '::tapify_actions',
			'tapify_element'           => __CLASS__ . '::tapify_element',
		);

		foreach ( $shortcodes as $shortcode => $function ) { 
			add_shortcode( apply_filters( "{$shortcode}_shortcode_tag", $shortcode ), $function );
		}

	}

	/**
	 * widget open on clicking shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function tapify_actions( $atts ) {

		$tag  		= [ 'action' => "open-panel" , "text"=>"Click to buy"  ];
		$atts 		= array_change_key_case((array)$atts, CASE_LOWER);
		$tpf_args	= shortcode_atts( $tag, $atts);		

		if( $tpf_args && isset( $tpf_args['text']) )  $text 	= $tpf_args['text'];
		if( $tpf_args && isset( $tpf_args['action']) ) $action 	= $tpf_args['action'];
		if( $action && $action === 'open-panel'){
			ob_start(); ?>

				<!-- Contact section start  -->
				<a href="javascript:void(0);" onclick="tapify.openPanel( );"><?php echo $text; ?></a>
				<!-- Get contents and stop output buffering -->

			<?php  $output .= ob_get_contents();
			ob_end_clean();
		}
		return $output;
	}

	/**
	 * widget open on clicking shortcode.
	 *
	 * @param array $atts Attributes.
	 * @return string
	 */
	public static function tapify_element( $atts = [], $content = null){

		$tag  		= [ 'action' => "open-panel" , "text"=>"Click to buy"  ];
		$atts 		= array_change_key_case((array)$atts, CASE_LOWER);
		$tpf_args	= shortcode_atts( $tag, $atts);		

		if( $tpf_args && isset( $tpf_args['text']) )  $text 	= $tpf_args['text'];
		if( $tpf_args && isset( $tpf_args['action']) ) $action 	= $tpf_args['action'];
		if( $action && $action === 'open-panel'){
			ob_start(); ?>

				<!-- Contact section start  -->
				<a href="javascript:void(0);" onclick="tapify.openPanel( );"><?php echo $content; ?></a>
				<!-- Get contents and stop output buffering -->

			<?php  $output .= ob_get_contents();
			ob_end_clean();
		}
		return $output;
	}

		


}