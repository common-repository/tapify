<?php
/**
 * Wc rest api related functions and actions.
 *
 * @package Tapify/Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;


require __DIR__ . '/../vendor/autoload.php';

use Automattic\WooCommerce\Client;

/**
 * WC_Install Class.
 */

class TPF_wc_apis {

	/**
	 * TPF_wc_apis Constructor.
	 */
	public function __construct() {
		
	}

	/**
	 * Hook in tabs.
	 */
	public static function init() { 
		
		/*
		 * Setup for the new WP REST API integration
		 *
		 * */
		
		
		if($storeAccessKey 	= get_option('tapify_store_access_key')) :
	        try{
	            $args = array(
	                    'body'      => json_encode( 
						array( 
							"storeAccessKey" => $storeAccessKey 
						)
					) ,
	                    'blocking'  => true,
	                    'headers'   => array(
	                            'storeAccessKey'        => $storeAccessKey ,
    				    		'Content-Type' 	    => 'application/json'
	                            ),
	                    'cookies'   => array()
	                );

	            $response = wp_remote_post( 'https://api.tapify.io/v1/validate/accesssKey' , $args );
	            $json_dec = array();

	            if( !is_wp_error($response) && isset($response['body'])){
	                $json_dec = json_decode($response['body']);
	                
	                if( $json_dec && isset($json_dec->_id)){
	                    $woocommerce = new Client(
			    			get_home_url(), 
						    $json_dec->consumerKey, 
						    $json_dec->consumerSecret,
					     	[
						        'wp_api' => true,
						        'version' => 'wc/v2',
						    ]
						);

						/*
						 *
						 * Set the woocommrec api instance globally
						 *
						 * so we can run wc api's
						 */	

						$GLOBALS['tapifyWcApi'] = $woocommerce; 

	                }else{
	                    array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'store_access_key','invalid')  ) ;
	                }
	            }
	            array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'store_access_key','add_store_url_failed')  ) ;
	        }catch(Exception $e) {

	            array( "status"=>false, "message"=> $e->getMessage() );
	        }
			
		endif;
		
	}

}





		

