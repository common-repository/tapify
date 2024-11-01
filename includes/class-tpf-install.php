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
class TPF_Install {

	/**
	 * TPF_Install Constructor.
	 */
	public function __construct() {
		
	}

	/**
	 * Hook in tabs.
	 */
	public static function install() {
		
		/*
		 *
		 * Check if WooCommerce is active,
		 *
		 * else deativate Tapify plugin and throw error
		 *
		 * */
		// if (  !in_array(  'woocommerce/woocommerce.php', 
  //   				apply_filters( 'active_plugins', get_option( 'active_plugins' ) )  )  ) {
		// 	$error_message = __('Tapify plugin requires <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugins to be active!', 'woocommerce');

		// 	deactivate_plugins(__FILE__);

		// 	wp_die($error_message);
		// }

		if( !function_exists('curl_version') ){
			$error_message = __('Curl Isn’t Installed on the Server!please install it by typing "sudo apt-get install php7.0-curl" from your terminal.', 'woocommerce');

			deactivate_plugins(__FILE__);

			wp_die($error_message);
		}
	}

	
	public static function activated( $plugin ) {
		if( $plugin == plugin_basename ( TPF_PLUGIN_FILE )) {
			
			$args = array(
	            'body'  	=> json_encode( array(  "storeUrl" =>  get_home_url() ) ) ,
	            'blocking' 	=> true,
	            'headers'   => array( 'Content-Type' => 'application/json' ),
	            'cookies'  	=> array()
	        );

			$response = wp_remote_post( TPF_NODE_API_URL . 'v1/wp/installed' , $args );
			$json_dec = array();
			if( !is_wp_error($response)  && isset($response['body'])){
				$json_dec = json_decode($response['body']);
				if( $json_dec && isset($json_dec->data)){
				}else{ }
			}
			exit( wp_redirect( admin_url( 'admin.php?page=tapify_settings&auto=true' ) ) );
		}
	}
}



		
