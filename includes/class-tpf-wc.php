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

class TPF_wc_fn {

	/**
	 * TPF_wc_fn Constructor.
	 */
	public function __construct() {
		
	}


	/**
	 * Hook in tabs.
	 */
	public static function init() {
		
		
	}

	public static function tpf_get_product_price_include_tax( $product_id ) {
		if( !$product_id ) return NULL;
		
		$product 	= wc_get_product( $product_id );
		$price 		= NULL;
		if( self::tpf_woocommerce_version_check() ) {
		    if( $product ) $price = wc_get_price_including_tax( $product );
		} else {  if( $product ) $price = $product->get_price_including_tax() ; }

		return $price;
	}

	public static function tpf_woocommerce_version_check( $version = '3.0' ) {
	  if ( class_exists( 'WooCommerce' ) ) {
	    global $woocommerce;
	    if( version_compare( $woocommerce->version, $version, ">=" ) ) {
	      return true;
	    }
	  }
	  return false;
	}


}

TPF_wc_fn::init();
