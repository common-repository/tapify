<?php
/**
 * Wc related functions and actions.
 *
 * @package Tapify/Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * TPF_wc_fn Class.
 */

class TPF_order_completed {

	/**
	 * TPF_add_wc_settings_tap Constructor.
	 */
	public function __construct() {
		
	}
	
	public static function tapify_order_created_hook(  $order_id  ) {
		// $order = wc_get_order( $order_id );
		// WC()->session->set('tapify_applied_cp' , null );
		if($storeAccessKey = get_option('tapify_store_access_key')) {
			$args = array(
				'body'  	=> json_encode(  array( 
					"storeAccessKey" => $storeAccessKey ,
					"order_id" => $order_id  ) 
					) ,
				'blocking' 	=> true,
				'headers'   => array(
							'storeAccessKey'    => $storeAccessKey,
							'Content-Type'      => 'application/json'
					),
				'cookies'  => array()
			);

			$response = wp_remote_post( TPF_NODE_API_URL . 'v1/wc/save/order' , $args );
			$json_dec = array();
			
			// if( !is_wp_error($response)  && isset($response['body'])){
			// 	$json_dec = json_decode($response['body']);
			// 	if( $json_dec && isset($json_dec->data)){
			// 		$return =  array("status" => true );
			// 	}else{
			// 		return  array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'store_access_key','invalid')  ) ;
			// 	}
			// }
		}
		// return $array;
		
	}

	

}

