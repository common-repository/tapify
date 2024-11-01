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
class TPF_Uninstall {

	/**
	 * TPF_Install Constructor.
	 */
	public function __construct() {
		
	}

	/**
	 * Hook in tabs.
	 */
	public static function unistall() {
		
		/*
		 *
		 * Check if WooCommerce is active,
		 *
		 * else deativate Tapify plugin and throw error
		 *
		 * */
		if($storeAccessKey = get_option('tapify_store_access_key')) :
			 $args = array(
	            'body'  => json_encode( 
	                  array( 
		                    "storeAccessKey" => $storeAccessKey ,
		                    "storeUrl"       =>  get_home_url()
		                  )
	                ) ,
	            'blocking' 	=> true,
	            'headers'   => array(
		                        'storeAccessKey'        => $storeAccessKey,
		                        'Content-Type'      => 'application/json'
		                  	),
	            'cookies'  => array()
	        );

			$response = wp_remote_post( TPF_NODE_API_URL . 'v1/wp/uninstall' , $args );
			$json_dec = array();
			if( !is_wp_error($response)  && isset($response['body'])){
				$json_dec = json_decode($response['body']);
				if( $json_dec && isset($json_dec->data)){
				}else{ }
			}
	    endif;
          
	}

}



		
