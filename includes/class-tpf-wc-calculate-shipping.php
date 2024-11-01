<?php
/**
 * Calculate shipping
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
class TPF_wc_calculate_shipping{

	/**
	 * TPF_wc_calculate_shipping Constructor.
	 */
	public function __construct() {
	}


	/**
	 * Hook in tabs.
	 */
	public static function init() {
		
	}


	/**
	 * Calculate shipping for the cart.
	 *
	 * @throws Exception When some data is invalid.
	 */
	public static function calculate_shipping( $country , $state , $postcode , $city ) {
        try {
        	global $woocommerce;
        	$woocommerce->session->set_customer_session_cookie(true);
            WC()->shipping->reset_shipping();

            $country  = isset( $country ) ? wc_clean( wp_unslash( $country ) ) : ''; // WPCS: input var ok, CSRF ok, sanitization ok.
            $state    = isset( $state ) ? wc_clean( wp_unslash( $state ) ) : ''; // WPCS: input var ok, CSRF ok, sanitization ok.
            $postcode = isset( $postcode ) ? wc_clean( wp_unslash( $postcode ) ) : ''; // WPCS: input var ok, CSRF ok, sanitization ok.
	    	$postcode = false;

            $city     = isset( $city ) ? wc_clean( wp_unslash( $city ) ) : ''; // WPCS: input var ok, CSRF ok, sanitization ok.

            if ( $postcode && ! WC_Validation::is_postcode( $postcode, $country ) ) {
                throw new Exception( __( 'Please enter a valid postcode / ZIP.', 'woocommerce' ) );
            } elseif ( $postcode ) {
                $postcode = wc_format_postcode( $postcode, $country );
            }
            
            if ( $country ) {
                WC()->customer->set_shipping_location( $country, $state, $postcode, $city );
                WC()->customer->set_billing_location( $country, $state, $postcode, $city );
            } else {
                WC()->customer->set_billing_address_to_base();
                WC()->customer->set_shipping_address_to_base();
            }

            WC()->customer->set_calculated_shipping( true );
            WC()->customer->save();

            // wc_add_notice( __( 'Shipping costs updated.', 'woocommerce' ), 'notice' );

            do_action( 'woocommerce_calculated_shipping' );

        } catch ( Exception $e ) {
            if ( ! empty( $e ) ) {
                wc_add_notice( $e->getMessage(), 'error' );
            }
        }
    }

    /**
	 * Calculate shipping for the cart.
	 *
	 * @throws Exception When some data is invalid.
	 */
	public static function tpf_wc_calculate_shipping( $token = false ) {
	    if( !$token ) $token = isset( $_COOKIE['tpfUserStatus'] )? $_COOKIE['tpfUserStatus'] : false;
	    if( !$token )  return false;

	    $check_shipping_allowed = self::tpf_check_shipping_allowed( $token ) ;


	    if( isset($check_shipping_allowed['status'] ) ) {
	    	$status = $check_shipping_allowed['status'];

	    	switch ($status) {
			    case "1":
			    	$data  		= $check_shipping_allowed['address']->data ;
			    	$country 	= isset($data->country) ? $data->country: '';
			    	$state 		= isset($data->state) ? $data->state: '';
			    	$city 		= isset($data->city) ? $data->city: '';
			    	$postcode 	= isset($data->zipcode) ? $data->zipcode: '';
			    	self::calculate_shipping( $country ,$state ,$postcode ,$city );
			    	$return = array( 'status' => true , "message"=> TPF_variables_json::get_response_message( 'shipping_address','valid') ) ;
			    	WC()->cart->calculate_totals();
    				TPF_Ajax_events::create_button_log( false , false , $token);
    				TPF_cart_widget::get_unique_id();
			        break;
			    case "2":
			        $return = array( 'status' => false , "message"=> TPF_variables_json::get_response_message( 'shipping_address','no_shipping') ) ;
			        break;
			    case "3":
			        $return = array( 'status' => true , "message"=> TPF_variables_json::get_response_message( 'shipping_address','address_not_added') ) ;
			        break;
			    default:
			       $return = array( 'status' => true , "message"=> TPF_variables_json::get_response_message( 'shipping_address','address_not_added') ) ;
			}
		}
	    wp_send_json( $return ); die();
	}

    /**
	 * Calculate shipping for the cart.
	 *
	 * @throws Exception When some data is invalid.
	 */
	public static function tpf_check_shipping_allowed( $token = false ) {
	    if( !$token )  return false;

	    global $woocommerce; 
		$allowed_countries = TPF_Ajax_events::tpf_get_shipping_countries();

		$args = array(
		          'body'      => json_encode(array( 'allowedCountries' =>  $allowed_countries )) ,
		          'blocking'  => true,
		          'headers'   => array(
		          'Authorization'     => $token ,
		                  'Content-Type'      => 'application/json'
		                  ),
		          'cookies'   => array()
		      );

		// $response = wp_remote_post( TPF_NODE_API_URL . 'v1/user/country' , $args );
		$response = wp_remote_post( TPF_NODE_API_URL . 'v1/check/shippingExist' , $args );

		$json_dec = array();

		if( !is_wp_error($response) && isset($response['body'])){
			$json_dec = json_decode($response['body']);

			if(isset($json_dec->status)){
				$return = array('status'=>1, 'address' => $json_dec ,'message'=> TPF_variables_json::get_response_message( 'shipping_address','valid') ) ;
			}elseif (isset($json_dec->errType) &&  $json_dec->errType == 1 ) {
				$return =  array('status'=>2, 'address' => $json_dec , 'message'=> TPF_variables_json::get_response_message( 'shipping_address','no_shipping') ) ;
			}elseif (isset($json_dec->errType) &&  $json_dec->errType == 2 ) {
				$return =  array('status'=>3,'message', 'address' => NULL ,  TPF_variables_json::get_response_message( 'shipping_address','address_not_added') ) ;
			}
		}else{
			$return =  array('status'=>3,'message', 'address' => NULL ,  TPF_variables_json::get_response_message( 'shipping_address','address_not_added') ) ;
		}

		return $return;
	}
	public static function tpf_get_shipping_object( $key = false ){
		foreach ( WC()->cart->calculate_shipping() as $key => $shipping_object ) {
			
			if( !$key ) {
				$taxes = array();
				if( isset($shipping_object->taxes) && count( $shipping_object->taxes )> 0 ){
					$taxes = array_values($shipping_object->taxes);
					$taxes = $taxes[0];
				}
				$shippings = array( 
                  'method_id' => isset($shipping_object->id)?$shipping_object->id:NULL,
                  'method_title' => isset($shipping_object->label)?$shipping_object->label:NULL,
                  'total' => isset($shipping_object->cost)?$shipping_object->cost:NULL,
                  'taxes' => $taxes,
                  'tax_status' => isset($shipping_object->tax_status)?$shipping_object->tax_status:NULL,
                );
                return $shippings;
			}

			return $shipping_object->$key;

		}
	}

}

TPF_wc_calculate_shipping::init();


