<?php
/**
 * Installation related functions and actions.
 *
 * @package Tapify/Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Install Class.
 */
class TPF_variables_json {

	public $json_data;

	/**
	 * TPF_add_wc_settings_tap Constructor.
	 */
	public function __construct() { 
		
	    // $vulns 	= $json_data->CVE_Items[0];
	}


	/**
	 * Hook in tabs.
	 */

	public static function init() {
		ob_start(); 
		$selected_language  = get_option('tapify_default_language');
		if( !$selected_language ) $selected_language = 'EN';

		$filepath = 'includes/variables/'. $selected_language .'.json' ;
	    include TPF_ABSPATH . $filepath ;
	    $contents = ob_get_clean();
	    $json_data = json_decode($contents, true);

		return $json_data;
	}

	public static function get_response_message( $arrayIndex1 = flase , $arrayIndex2 = false ) {
		if( !$arrayIndex1 || !$arrayIndex2 ) return 'Request malformed!' ;
		$messages = self::init(); 
		if( $messages && count( $messages ) > 0 ){
			return $messages[$arrayIndex1][$arrayIndex2];
		}else{
			return 'Request malformed!';
		}

	}
}

