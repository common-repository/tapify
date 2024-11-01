<?php
/**
 * Calculate shipping and tax
 *
 * Used when tapify users ligin/signup and add/update/change address.
 *
 * @package Tapify/classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Shortcode cart class.
 */
class TPF_get_shipping_tax{

	/**
	 * TPF_get_shipping_tax Constructor.
	 */
	public function __construct() {
	}


    /**
	 * Calculate shipping and tax from user address.
	 *
	 * @throws Exception When some data is invalid.
	 */
	public static function tpf_get_shipping_tax_rates( $token = false ,  $shippingClassid , $taxClass , $taxStatus , $coupon = false , $total , $tab = 'product' , $storeAccessKey = false ) {
		$tapify_cookies     = TPF_Ajax_events::get_tapify_cookies();
		$tapifyCookies 		= false;
        if( $tapify_cookies && isset( $tapify_cookies['status'] ) && $tapify_cookies['status'] == 1 && isset( $tapify_cookies['data']) ){
        	$tapifyCookies  = $tapify_cookies['data'];
        }


	    if( !$token ) {
	    	if( $tapifyCookies )
            	$token    	= isset( $tapifyCookies->tpfUserStatus )?$tapifyCookies->tpfUserStatus:NULL;
            
	    }
	    $chosen_shipping_methods = '';
	    if( class_exists( 'WooCommerce' )  && $tab && $tab === 'product' ) { 
	    	if(WC()->session->get('chosen_shipping_methods_for_product')) {
		        $chosen_shipping_methods = (WC()->session->get('chosen_shipping_methods_for_product')[0])?WC()->session->get('chosen_shipping_methods_for_product')[0]:false;
		    }
	    } elseif (!class_exists( 'WooCommerce' ) ) {
	    	$chosen_shipping_methods =  isset( $_COOKIE['tpfChosenProductShipping'] ) && $_COOKIE['tpfChosenProductShipping'] != "null" ? $_COOKIE['tpfChosenProductShipping'] : '';
	    }

	    $shippingClass 	= 'no_class_cost';
	    $response 		= array();
	    if( $shippingClassid ) $shippingClass = 'class_cost_' . $shippingClassid;
	    if( !$taxClass ) $taxClass 	= 'standard';
	    if( !$coupon ) $coupon 		= "notExist";
		$args = array(
	           'body'      => '' ,
	           'blocking'  => true,
	           'headers'   => array(
	          		'Authorization'     => $token ,
					'Content-Type'      => 'application/json',
					'storeAccessKey' 	=> ($storeAccessKey)?$storeAccessKey: get_option('tapify_store_access_key')
              	),
	            'cookies'   => array()
		      );

		$queryString = '?shippingClass=' .$shippingClass . '&price=' .$total . '&taxStatus=' .$taxStatus. '&taxClass=' .$taxClass .'&coupon=' . $coupon .'&chosenShipping=' . $chosen_shipping_methods ;


	    if( !$token ||  $token === 'null' || $token === null  )  {

			$json_dec 	= $tapifyCookies->location;
 
			if( $json_dec && isset($json_dec->country)){
				$queryString .= '&country_code=' . $json_dec->country . '&province_code=' . $json_dec->region_code . '&zip=' . $json_dec->postal;

				$response = wp_remote_get( TPF_NODE_API_URL . 'v1/wc/guest/shippingMethod'.$queryString , $args );
			}
	    }else{
	    	$response 	= wp_remote_get( TPF_NODE_API_URL . 'v1/wc/user/shippingMethod'.$queryString , $args );
	    }
		$json_dec 		= $return = array();

		if( !is_wp_error($response) && isset($response['body'])){
			$json_dec 	= json_decode($response['body']);

			if(isset($json_dec)){
				$return = array('status'=>1, 'data' => $json_dec ,'message'=> TPF_variables_json::get_response_message( 'shipping_address','valid') ) ;
			}
		}else{
			$return 	=  array('status'=>3,'message', 'data' => NULL ,  TPF_variables_json::get_response_message( 'shipping_address','address_not_added') ) ;
		}
		return $return;
	}

	public static function tpf_get_current_location() {
		

        
    }

	 /**
	 * Claculate tax based on tax rate
	 *
	 * @throws Exception When some data is invalid.
	 */
	public static function tpf_calulate_tax_basdeon_rates( $taxes = array() , $price , $type = 'product' ) {

	    if( is_array( $taxes ) && count( $taxes ) > 0  ) {
	    	$is_compound = false; $total = 0; $tax = 0;
	    	foreach ($taxes as $key => $field) {
	    		if ( isset($field->provinces->compound) && $field->provinces->compound ){
            	 	$is_compound = true; break;
	    		}
	    	}
	    	foreach ($taxes as $key => $value) {
	    		$taxObj = $value->provinces;
	    		if( $type == 'shipping'){
	    			if( !$taxObj->shipping ) continue;

	    		}
	    		if( $is_compound ){
	    			$tax = ( $tax + $price )* $taxObj->tax;
	    			// $tax = $price * $taxObj->tax;
	    		}else{
	    			$tax = $price * $taxObj->tax;
	    		}
	    		$total = $total + $tax;
	    	}
	    	return $total;
	    }else return 0;
	    
	}
}


