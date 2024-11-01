<?php
/**
 * Function included incart-widget
 *
 * @package Tapify/classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WC_Install Class.
 */
class TPF_cart_widget {

	/**
	 * TPF_Install Constructor.
	 */
	public function __construct() {
		
	}

	/**
	 * Hook in tabs.
	 */
	public static function init() {
		$ajax_events = array(
            'create_cart_object'        => true,
            'tapify_update_shipping_method'     => true,
            'tapify_remove_product_from_cart'   => true,
            'tapify_calculate_product_total'    => true,
            'get_unique_id'                     => true,
            'tpf_order_complete'                => true,
            'tapify_save_language'              => true,
            'tapify_sync_address'               => true,
            'tapify_update_cart_collection'     => true,
            'tapify_update_cart_quantity'		=> true,
        );
        /*
        * Adding ajax functions
        */
        foreach ( $ajax_events as $ajax_event => $nopriv ) {
            add_action( 'wp_ajax_' . $ajax_event,  array( __CLASS__, $ajax_event ) );

            if ( $nopriv ) {
                add_action( 'wp_ajax_nopriv_' . $ajax_event,  array( __CLASS__, $ajax_event ) );
            }
        }
		
	}

    /**
     * Get the session cookie, if set. Otherwise return false.
     *
     * Session cookies without a customer ID are invalid.
     *
     * @return bool|array
     */
    public static function tapify_get_session_cookie() {

        if(!defined( 'COOKIEHASH' )) return false;
        
        $cookie = apply_filters( 'woocommerce_cookie', 'wp_woocommerce_session_' . COOKIEHASH );

        $cookie_value = isset( $_COOKIE[ $cookie ] ) ? wp_unslash( $_COOKIE[ $cookie ] ) : false; // @codingStandardsIgnoreLine.

        if ( empty( $cookie_value ) || ! is_string( $cookie_value ) ) {
            return false;
        }

        list( $customer_id, $session_expiration, $session_expiring, $cookie_hash ) = explode( '||', $cookie_value );

        if ( empty( $customer_id ) ) {
            return false;
        }

        // Validate hash.
        $to_hash = $customer_id . '|' . $session_expiration;
        $hash    = hash_hmac( 'md5', $to_hash, wp_hash( $to_hash ) );

        if ( empty( $cookie_hash ) || ! hash_equals( $hash, $cookie_hash ) ) {
            return false;
        }

        return array( $customer_id, $session_expiration, $session_expiring, $cookie_hash );
    }


   


    /**
    * tpf_order_complete
    * method :GET
    * remove product from cart
    * parms  : uuid 
    */
    public static function tpf_order_complete(){ 

        if( $_POST['currentTab'] && $_POST['currentTab'] == 'cart') {

            /*
             * Clear cart afther succesful order
             *
             * */
            TPF_Ajax_events::tapify_empty_cart();        
        }

        /*
         * Clear custom session stored in the wc session
         *
         * */

        self::tpf_unset_wc_cookie( 'tapify_cart_cookie_key' );

        $storeAccessKey = get_option('tapify_store_access_key');
        $uuid = TPF_Ajax_events::get_unique_id_from_cookie(true);
        self::tapify_update_cart_log();
        wp_send_json(  array( "status" => true ,  "uuid" => $uuid , 'store_access_key' => $storeAccessKey ) ); 
        die(); 
    }

	/**
    * tapify_sync_address
    * method :POST
    * Updsate wc session shipping address with the tapify user's address
    * parms  : token 
    */
    public static function tapify_sync_address(){ 

        if( $_POST['token'] && $_POST['token'] != NULl ) { 

            /*
             * Clear cart afther succesful order
             *
             * */
            $is_default = TPF_wc_calculate_shipping::tpf_wc_calculate_shipping($_POST['token']); 


        }

        // wp_send_json(  array( "status" => true ,  "uuid" => $uuid , 'store_access_key' => $storeAccessKey ) ); 
        die(); 
    }

    /**
    * tpf_unset_wc_session
    *
    * Remove session from wc
    */
    public static function tapify_save_language( ){
        $selected_language =  $_POST['tpfLanguage'];

        if( !$selected_language ) {
             $return = array( "status" => true , 'message' =>  TPF_variables_json::get_response_message( 'settings','missing_language') );
            wp_send_json( $return );
            die(); 
        }

        $filepath = 'includes/variables/'. $selected_language .'.json' ;
        $path =  TPF_ABSPATH . $filepath ;

        if( !file_exists( $path ) ) {
            $return = array( "status" => true , 'message' =>  TPF_variables_json::get_response_message( 'settings','language_not_found') );
            wp_send_json( $return );
            die(); 
        }

        $updated = update_option('tapify_default_language' , $_POST['tpfLanguage']);

        $return = array( "status" => true , 'message' =>  TPF_variables_json::get_response_message( 'settings','language_switched_success') );

        wp_send_json( $return );
        die(); 

    }

    /**
    * tpf_unset_wc_session
    *
    * Remove session from wc
    */
    public static function tpf_unset_wc_session( $session_name ){

        WC()->session->__unset( $session_name );
        if( WC()->session->get( $session_name ) )
            WC()->session->set( $session_name, null );

        return true;
    }

    /**
    * tpf_unset_wc_cookie
    *
    * Remove cookie 
    */
    public static function tpf_unset_wc_cookie( $session_name ){

        if(isset($_COOKIE[$session_name]))
            setcookie( $session_name , "", time()-3600);

        return true;
    }


    /**
    * get_unique_id
    *
    * Fwt uniques id from cookie
    */
    public static function get_unique_id(){
        try{ 
            $tapify_cookies     = TPF_Ajax_events::get_tapify_cookies();
            if( $tapify_cookies && isset( $tapify_cookies['status'] ) && $tapify_cookies['status'] == 1 && isset( $tapify_cookies['data']) ){

                $tapifyCookies   = $tapify_cookies['data'];
                $uuid   = isset( $tapifyCookies->tapify_24_hr_cookie )?$tapifyCookies->tapify_24_hr_cookie:false;
                $storeAccessKey  = get_option('tapify_store_access_key');
                wp_send_json(  array( "status" => true ,  "uuid" => $uuid , 'store_access_key' => $storeAccessKey ) ); 
                die(); 

            }else throw new Exception( "Missing tapify cookies" ,  105 );                     
        }catch(Exception $e) {
            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }

     /**
    * tapify_remove_product_from_cart
    *
    * remove product from cart
    */
    public static function tapify_remove_product_from_cart(){ 
    	$this_product = false;
    	if(!$_POST['key']){
    		wp_send_json( array( "status" => false , "message" =>  TPF_variables_json::get_response_message( 'cart','key_missing') ) ); die(); 
    	}
    	if( $_POST['productId']) {
    		foreach(WC()->cart->get_cart() as $cart_item_key => $cart_item) {
    			if( $cart_item_key == $_POST['key']){
    				if( $cart_item['product_id'] == $_POST['productId']) $this_product = true;
    			}
    		}
    	}

    	WC()->cart->remove_cart_item( $_POST['key'] );
        self::tapify_update_cart_log();
       
    	wp_send_json( 
    		array(
	    		"status" => true , 
	    		"message" => TPF_variables_json::get_response_message( 'cart','product_removed') ,
	    		"this_product" => $this_product
    		) 
    	); 
    	die(); 

    }

    public static function my_custom_show_sale_price_at_checkout( $product , $quantity) {

    $regular_price = $sale_price = $suffix = '';

    if ( $product->is_taxable() ) {

        if ( 'excl' === WC()->cart->tax_display_cart ) {

            $regular_price = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_regular_price(), 'qty' => $quantity ) );
            $sale_price    = wc_get_price_excluding_tax( $product, array( 'price' => $product->get_sale_price(), 'qty' => $quantity ) );

            if ( WC()->cart->prices_include_tax && WC()->cart->tax_total > 0 ) {
                $suffix .= ' <small class="tax_label">' . WC()->countries->ex_tax_or_vat() . '</small>';
            }
        } else {

            $regular_price = wc_get_price_including_tax( $product, array( 'price' => $product->get_regular_price(), 'qty' => $quantity ) );
            $sale_price = wc_get_price_including_tax( $product, array( 'price' => $product->get_sale_price(), 'qty' => $quantity ) );

            if ( ! WC()->cart->prices_include_tax && WC()->cart->tax_total > 0 ) {
                $suffix .= ' <small class="tax_label">' . WC()->countries->inc_tax_or_vat() . '</small>';
            }
        }
    } else {
        $regular_price    = $product->get_price() * $quantity;
        $sale_price       = $product->get_sale_price() * $quantity;
    }

    if ( $product->is_on_sale() && ! empty( $sale_price ) ) {
        $price = wc_format_sale_price(
                     wc_get_price_to_display( $product, array( 'price' => $product->get_regular_price(), 'qty' => $quantity ) ),
                     wc_get_price_to_display( $product, array( 'qty' => $quantity ) )
                 ) . $product->get_price_suffix();
    } else {
        $price = wc_price( $regular_price ) . $product->get_price_suffix();
    }

    // VAT suffix
    $price = $price . $suffix;

    return $price;

}

public static function wc_get_price_including_tax( $product, $args = array() ) {
    $args = wp_parse_args( $args, array(
        'qty'   => '',
        'price' => '',
    ) );

    $price = '' !== $args['price'] ? max( 0.0, (float) $args['price'] ) : $product->get_price();
    $qty   = '' !== $args['qty'] ? max( 0.0, (float) $args['qty'] ) : 1;

    if ( '' === $price ) {
        return '';
    } elseif ( empty( $qty ) ) {
        return 0.0;
    }

    $line_price   = $price * $qty;
    $return_price = $line_price;

    if ( $product->is_taxable() ) {
        if ( ! wc_prices_include_tax() ) {
            $tax_rates    = WC_Tax::get_rates( $product->get_tax_class() );
            $taxes        = WC_Tax::calc_tax( $line_price, $tax_rates, false );
            $tax_amount   = WC_Tax::get_tax_total( $taxes );
            $return_price = round( $line_price + $tax_amount, wc_get_price_decimals() );
        } else {
            $tax_rates      = WC_Tax::get_rates( $product->get_tax_class() );
            $base_tax_rates = WC_Tax::get_base_tax_rates( $product->get_tax_class( 'unfiltered' ) );

            /**
             * If the customer is excempt from VAT, remove the taxes here.
             * Either remove the base or the user taxes depending on woocommerce_adjust_non_base_location_prices setting.
             */
            if ( ! empty( WC()->customer ) && WC()->customer->get_is_vat_exempt() ) { // @codingStandardsIgnoreLine.
                $remove_taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ? WC_Tax::calc_tax( $line_price, $base_tax_rates, true ) : WC_Tax::calc_tax( $line_price, $tax_rates, true );
                $remove_tax   = array_sum( $remove_taxes );
                $return_price = round( $line_price - $remove_tax, wc_get_price_decimals() );

            /**
             * The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing with out of base locations.
             * e.g. If a product costs 10 including tax, all users will pay 10 regardless of location and taxes.
             * This feature is experimental @since 2.4.7 and may change in the future. Use at your risk.
             */
            } elseif ( $tax_rates !== $base_tax_rates && apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
                $base_taxes   = WC_Tax::calc_tax( $line_price, $base_tax_rates, true );
                $modded_taxes = WC_Tax::calc_tax( $line_price - array_sum( $base_taxes ), $tax_rates, false );
                $return_price = round( $line_price - array_sum( $base_taxes ) + wc_round_tax_total( array_sum( $modded_taxes ), wc_get_price_decimals() ), wc_get_price_decimals() );
            }
        }
    }
    return apply_filters( 'woocommerce_get_price_including_tax', $return_price, $qty, $product );
}

    /**
    * Display User IP 
    *
    * Function should return users ip
    */
    public static function tapify_get_the_user_ip() {
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
        //check ip from share internet
        $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
        //to check ip is pass from proxy
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
        $ip = $_SERVER['REMOTE_ADDR'];
        }
        return apply_filters( 'wpb_get_ip', $ip );
    }

    /**
    * tapify_update_shipping_method
    *
    * Update shippingfmethod
    */
    public static function tapify_update_shipping_method(){ 
        $id     = $_POST['id'];
        $tab    = $_POST['tab'];
        $return = array( "status" =>false , "message" => "required field id or tab missing!");

        if(! WC()->session->has_session() )
             WC()->session->set_customer_session_cookie( true );

        if( $id && $tab ){
            $return = array( "status" => true , "message" => "Successfully updated the shipping method!");
            if( $tab === "product") {
                WC()->session->set('chosen_shipping_methods_for_product', array( $id ) ); 
            }
            if( $tab === "cart"){
                WC()->session->set('chosen_shipping_methods', array( $id ) ); 
                WC()->cart->calculate_totals();
                TPF_Ajax_events::create_button_log( false , false , false);
                TPF_cart_widget::get_unique_id();
            }
               
        }
        wp_send_json( $return ); 
        die();
            
    }

	/**
    * create_cart_object
    *
    * Create cart object for showwing cart widget in tapify button
    */
    public static function create_cart_object(){ 
    	$cart_items 	=  $carts   = $return = $thisProduct = array();
    	$shippinCost 	= 0; $exist = false; $cart_count  = 0; $shippinCustomCost = 0;
    	global $woocommerce;           

    	if($_POST['productId'] && !$exist ) {
    		$image_url  = wc_placeholder_img_src();
    		$thumbnail  = wp_get_attachment_image_src( get_post_thumbnail_id( $_POST['productId']  ,'full' ) );
    		if($thumbnail && isset($thumbnail[0])) $image_url =  $thumbnail[0] ;

    		$current_product =  wc_get_product( $_POST['productId'] );
    		if( $current_product->is_type( 'variable' ) ) {
                if( $_POST['variation_id']  && $_POST['variation_id'] != 0 ) {

                    $thisProduct = self::tapify_get_product_object( $_POST['productId'] , $_POST['variation_id'] ,$_POST['productQty'] , $_POST['attributes'] , $formattedAttributes = $_POST['formattedAttributes'] );
                }else{
                	$thisProduct['variation_validation']         = true ;
                }
    			
    		}else{

                $thisProduct = self::tapify_get_product_object( $_POST['productId'] , $_POST['variation_id'] ,$_POST['productQty'] , false , $formattedAttributes = array());		
				
			}
    	}

		if( count( $thisProduct)>0 ) $return['product'] = (object) $thisProduct;
		wp_send_json( $return );
        die();
    }


    public static function tapify_check_for_free_shipping( $total ,$productId = false ){ 

        $shipping       = TPF_Ajax_events::tapify_get_shipping_cost( $productId );
        $shippingCustom = TPF_Ajax_events::tapify_get_shipping_cost_from_session( $productId );
        $shippinCustomCost = $shippinCost  = $wc_shipping_tax_custom = $wc_shipping_tax_cart = $shippingCustomTaxStatus =   0 ;
        $thisProduct['wc_shipping_tax']    = 0;
        $shippingTaxStatus = false;

        $freeShippingExist = self::tapify_free_shipping_exist_for_current_address();
        if($shipping && isset($shipping[0]['total'])) {
            $shipping[0]['total']   = number_format( $shipping[0]['total'] ,2 );
            $shipping               = $shipping[0];
            $shippingTaxStatus      = isset($shipping['tax_status'])?$shipping['tax_status']:NULL;
            $shippinCost            = $shipping['total'];
            unset( $shipping['tax_status'] );
            
        }else{ 
            $shipping = (object)array();
        }

        if($shippingCustom && isset($shippingCustom[0]['total'])) {
            $shippingCustom[0]['total'] = number_format( $shippingCustom[0]['total'] ,2 );
            $shippingCustom             = $shippingCustom[0];
            $shippingCustomTaxStatus    = isset($shippingCustom['tax_status'])?$shippingCustom['tax_status']:NULL;
            $shippinCustomCost          = $shippingCustom['total'];
            unset( $shippingCustom['tax_status'] );
           
        }else{
            $shippingCustom = (object)array();
        }
        if( WC()->cart->get_shipping_tax() ){
            if( $shippingTaxStatus && $shippingTaxStatus == 'taxable' )
                $wc_shipping_tax_cart =  WC()->cart->get_shipping_tax() ;
        }

        $tax_rates            = WC_Tax::get_rates();
        if( !empty( $tax_rates) && count( $tax_rates)> 0 ){
            $shippingTax = WC_Tax::calc_tax( $shippinCustomCost, $tax_rates, false );
            if( is_array( $shippingTax )) {
                $shippingTaxValue=  array_values( $shippingTax );
                if( $shippingCustomTaxStatus && $shippingCustomTaxStatus == 'taxable' )
                    $wc_shipping_tax_custom = isset($shippingTaxValue[0])?$shippingTaxValue[0]:0;
            }
            
        }



        if( WC()->cart->get_cart_contents_count() == 0 )
                $wc_shipping_tax_cart =  $wc_shipping_tax_custom ;
        
        $thisProduct['free_shipping_needs'] = TPF_cart_widget::tapify_free_shipping_cart_notice_zones( $total );

        $freeShippingNotExist =  TPF_cart_widget::tapify_free_shipping_cart_notice_zones();

        $tapify_check_free_shipping_exist =  TPF_cart_widget::tapify_check_free_shipping_exist();
       
      
        $current_shipping_session = false;
        if(WC()->session->get('chosen_shipping_methods')) {
            $chosen_shipping_methods = (WC()->session->get('chosen_shipping_methods')[0])?WC()->session->get('chosen_shipping_methods')[0]:false;

            $explode = explode(':', $chosen_shipping_methods );
            $current_shipping_session = isset($explode[0])?$explode[0]:false;

        }


       if( $thisProduct['free_shipping_needs']  && isset($thisProduct['free_shipping_needs']->amount ) ) {
                $thisProduct['shipping']    = $shipping;
                $thisProduct['shippinCost'] = $shippinCost;
                $thisProduct['wc_shipping_tax'] = $wc_shipping_tax_cart;
            if( $current_shipping_session == 'free_shipping' ){
                $thisProduct['shipping']    = $shippingCustom;
                $thisProduct['shippinCost'] = $shippinCustomCost;
                $thisProduct['wc_shipping_tax'] = $wc_shipping_tax_custom;
            }
       }elseif( $tapify_check_free_shipping_exist && $current_shipping_session != 'free_shipping' ){
           $thisProduct['shipping']    = $tapify_check_free_shipping_exist;
           $thisProduct['shippinCost'] = 0;
           $thisProduct['wc_shipping_tax'] = 0;
       }else{
            $thisProduct['shipping']    = $shipping;
            $thisProduct['shippinCost'] = $shippinCost;
            $thisProduct['wc_shipping_tax'] = $wc_shipping_tax_cart;
       }

       $thisProduct['freeShippingExist'] = $freeShippingExist;


        return $thisProduct;

    }

    /**
    * tapify_remove_product_from_cart
    *
    * remove product from cart
    */
    public static function tapify_calculate_product_total(){ 

    	$productId      = $_POST['product_id'];
        $quantity       = isset($_POST['count'])?$_POST['count']:1;
        $variationId    = isset($_POST['variation_id'])?$_POST['variation_id']:false;
        $current_product= wc_get_product( $_POST['product_id'] );

        $return = self::tapify_get_product_object( $productId , $variationId ,$quantity ,$attributes  = '' , $formattedAttributes = array());
        wp_send_json( $return );
        die();

    }


    /**
    * tapify_update_cart_log
    *
    * Update cart log(node-mongodb)
    */
    public static function tapify_get_product_object( $productId , $variationId ,$quantity ,$attributes  = '' , $formattedAttributes = array() , $coupon = false ){

        global $woocommerce;
        $shippinCost    = 0; $wc_shipping_tax =0;
        $current_product= wc_get_product( $productId );

        if( $variationId  && $variationId != 0 ) {
            $current_product = new WC_Product_Variation( $variationId );
        }

        $image_url = wc_placeholder_img_src();
        $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $productId  ,'full' ) );
        if($thumbnail && isset($thumbnail[0])) $image_url =  $thumbnail[0] ;

        $thisProduct['key']         = NULL ;
        $thisProduct['_id']         = (int)$productId;
        $thisProduct['variation_id']= $variationId;
        $thisProduct['name']        = $current_product->get_title();
        $thisProduct['url']         =  get_permalink( $productId );
        $thisProduct['currency']    = get_option('woocommerce_currency');
        $thisProduct['attributes']  = $attributes;
        $thisProduct['formatted_attributes'] = $formattedAttributes;
        $thisProduct['count']       = (int)$quantity;
        $thisProduct['price_inc_tax']= TPF_wc_fn::tpf_get_product_price_include_tax( $current_product );
        $thisProduct['price_inc_tax_with_qty']= $thisProduct['price_inc_tax'] * $quantity;
        $thisProduct['price']       =   $current_product->get_price();
        $thisProduct['line_total']  = $quantity * $current_product->get_price() ;
        $display_price              = self::tpf_get_price_to_display( $current_product ) * $quantity;
        $thisProduct['display_price']  = number_format( $display_price , 2);
        $thisProduct['shipping_class']  = $current_product->get_shipping_class();
        $thisProduct['shipping_class_id']  = $current_product->get_shipping_class_id();
        $thisProduct['tax_status']  = $current_product->get_tax_status();
        $thisProduct['tax_class']   = ($current_product->get_tax_class())?$current_product->get_tax_class():'standard';
        $product_total              = $display_price;
        if(self::tapify_get_tax_rates())
            $product_total = $thisProduct['price_inc_tax_with_qty'];

        $thisProduct['total']       = ( $current_product->get_price() * $quantity);
        $thisProduct['taxtotal']    = 0 ;

        

        $shippingClassid = $current_product->get_shipping_class_id() ;
        $taxClass        = $current_product->get_tax_class() ;
        $taxStatus       = $current_product->get_tax_status() ;

        $shipping_tax = TPF_get_shipping_tax::tpf_get_shipping_tax_rates( false , $shippingClassid , $taxClass , $taxStatus , false , $thisProduct['total'] , 'product' );


        $thisProduct['shippingMethods'] = array();
        $thisProduct['free_shipping_needs'] = array();
        if( isset( $shipping_tax['status']) &&  $shipping_tax['status'] == 1 ){
            /*
             * Manually calculate shipping
             * */
            if(isset( $shipping_tax['data']->freeShippingNeeds ) && count( $shipping_tax['data']->freeShippingNeeds) > 0  ){
                $thisProduct['free_shipping_needs']    = array_shift( $shipping_tax['data']->freeShippingNeeds );

            }
            if(isset( $shipping_tax['data']->shippingMethods ) && count( $shipping_tax['data']->shippingMethods) > 0 ){ 
                $thisProduct['shippingMethods'] = $shipping_tax['data']->shippingMethods ;
                $shippingData    =  array_shift( $shipping_tax['data']->shippingMethods  );

                $thisProduct['shipping']    =  array( 
                    'method_id' => $shippingData->id ,
                    'method_title' => $shippingData->name ,
                    'total' => $shippingData->price
                );
                if( isset( $thisProduct['shipping']['total'] )){
                    $thisProduct['total'] = $thisProduct['total'] + $thisProduct['shipping']['total'];
                    /*
                     * Manually calculate shipping tax
                     * */
                    if(isset( $shipping_tax['data']->shippingTax ) && count( $shipping_tax['data']->shippingTax) > 0  ){
                        $thisProduct['shipping_tax_total'] = TPF_get_shipping_tax::tpf_calulate_tax_basdeon_rates( $shipping_tax['data']->shippingTax  , $thisProduct['shipping']['total']  , 'shipping');
                        $thisProduct['taxtotal'] = $thisProduct['taxtotal']+$thisProduct['shipping_tax_total'] ;
                    }
                    $thisProduct['shipping']['total'] = number_format ( $thisProduct['shipping']['total'] ,2 );
                }
            }

            /*
             * Manually calculate tax data
             * */
            if( !wc_prices_include_tax()){
                if(isset( $shipping_tax['data']->taxData ) && count( $shipping_tax['data']->taxData) > 0  ){
                    $thisProduct['lineItemsTax'] = TPF_get_shipping_tax::tpf_calulate_tax_basdeon_rates( $shipping_tax['data']->taxData  , $quantity * $current_product->get_price() );
                    $thisProduct['taxtotal']  =  $thisProduct['taxtotal'] +  $thisProduct['lineItemsTax']  ;
                    if( isset( $thisProduct['free_shipping_needs']->amount )) {
                        $thisProduct['free_shipping_needs']->amount  = number_format( $thisProduct['free_shipping_needs']->amount  - $thisProduct['lineItemsTax']  , 2);
                       
                        $thisProduct['free_shipping_needs']->percentage  = ( ( $current_product->get_price() * $quantity)  + $thisProduct['lineItemsTax'] )/$thisProduct['free_shipping_needs']->total  * 100 ;
                    }
                }
            }
        }

        $thisProduct['total']       = $thisProduct['total'] + $thisProduct['taxtotal'];
        $thisProduct['total']       = $thisProduct['total'] ;
        $thisProduct['subtotal']    = $thisProduct['total'] ;

        if(  isset( $_COOKIE['tapify_applied_cp'] )  ) {
            $coupon_obj = $_COOKIE['tapify_applied_cp'];
            $coupon_obj = json_decode( html_entity_decode( stripslashes ($coupon_obj ) ) );
            
            if( $coupon_obj && $coupon_obj->product_id == $productId ){
                $cp_status = TPF_Ajax_events::is_coupon_valid( 'product' ,$coupon_obj->coupon_code ,$coupon_obj->product_id ) ;
                if( $cp_status && $cp_status['status'] === true && $cp_status['cp']  ){
                    $thisProduct['applied_coupons']  = $cp_status['cp'];
                        if( isset($cp_status['cp']['discount_amount']) ){
                            $thisProduct['total']  = $thisProduct['total'] - $cp_status['cp']['discount_amount'];
                             $thisProduct['line_subtotal']  =  $thisProduct['line_total']  - $cp_status['cp']['discount_amount'];
                        }
                    }
            }
        }
        
        $thisProduct['image']   = $image_url;

        $attributes_array       = TPF_Ajax_events::get_variation_as_array( $attributes ) ;

        $thisProduct['formatted_attributes'] = (!empty( $attributes_array))? $attributes_array :(object)array();

        return  $thisProduct ;
    }


    /**
     * Returns the price including or excluding tax, based on the 'woocommerce_tax_display_shop' setting.
     * @return float
     */

    public static function tpf_get_price_to_display( $product, $args = array() ) {
        $args = wp_parse_args( $args, array(
            'qty'   => 1,
            'price' => $product->get_price(),
        ) );

        $price = $args['price'];
        $qty   = $args['qty'];

        return 'incl' === get_option( 'woocommerce_tax_display_shop' ) ?
            wc_get_price_including_tax( $product, array(
                'qty'   => $qty,
                'price' => $price,
            ) ) :
            wc_get_price_excluding_tax( $product, array(
                'qty'   => $qty,
                'price' => $price,
            ) );
    }


    /**
    * tapify_update_cart_log
    *
    * Update cart log(node-mongodb)
    */
    public static function tapify_get_tax_rates( ){
        $return = WC_Tax::get_rates();

        if( count( $return ) > 0 ) return $return;
        return false;
    }


    /**
    * tapify_before_main_content
    *
    * before mail content load
    */
    public static function tapify_before_main_content( ){ 
        try{ 
            if( $_POST && isset($_POST['billing'])) return false;
            TPF_Ajax_events::create_button_log( false , false );        
        }catch(Exception $e) {
            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }
    /**
    * tapify_update_cart_log
    *
    * Update cart log(node-mongodb)
    */
    public static function tapify_update_cart_log( ){ 
        try{ 
            TPF_Ajax_events::create_button_log( false , false );                    
        }catch(Exception $e) {
            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }

   /**
    * tapify_update_cart_log
    *
    * Update cart log(node-mongodb)
    */
    public static function tapify_update_cart_collection( ){
        try{ 
            TPF_Ajax_events::create_button_log( false , false );
            self::get_unique_id();
        }catch(Exception $e) {
            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }

    /**
    * tapify_update_log_collection_with_product
    *
    * Update cart log with product(node-mongodb)
    */
    public static function tapify_update_log_collection_with_product( $productId ){ 
        if( !$productId ) return false;
        try{ 
            TPF_Ajax_events::create_button_log( false , false ,false , $productId );                   
        }catch(Exception $e) {
            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }

     /**
    * tapify_update_log_collection_with_cart
    *
    * Update cart log with cart data(node-mongodb)
    */
    public static function tapify_update_log_collection_with_cart(){
        try{ 
            $tapify_cookies     = TPF_Ajax_events::get_tapify_cookies();
            if( $tapify_cookies && isset( $tapify_cookies['status'] ) && $tapify_cookies['status'] == 1 && isset( $tapify_cookies['data']) ){

                $tapifyCookies   = $tapify_cookies['data'];
                
                $userStatus  = isset( $tapifyCookies->tpfUserStatus )?$tapifyCookies->tpfUserStatus:NULL;
                TPF_Ajax_events::create_button_log( false , false , $userStatus , false , true  );
            }else throw new Exception( "Missing tapify cookies" ,  105 );                     
        }catch(Exception $e) {
            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }

   /**
    * @snippet Notice with $$$ remaining to Free Shipping @ WooCommerce Cart
    * @testedwith WooCommerce 3.4.2
    */
    public static function tapify_free_shipping_cart_notice_zones( $currentTotal  = false ) {
     
        global $woocommerce;

        $min_amounts = $return = array(); $defaultMethods = array();
        
        $free_shipping_exist = self::tapify_free_shipping_exist_for_current_address( $currentTotal );
        if( !$free_shipping_exist ) return (object)($return );
        
        /* 
         * Get Free Shipping Methods for all other ZONES & populate array $min_amounts
         * Need to double check the logic
         * Commented on Oct 31
         * 
         
            $default_zone = new WC_Shipping_Zone(0);
            $default_methods = $default_zone->get_shipping_methods();

            foreach( $default_methods as $key => $value ) {

                if ( $value->id === "free_shipping" ) {
                  if ( $value->min_amount > 0 ) $min_amounts[] = $value->min_amount;
                }
            }
            $delivery_zones = WC_Shipping_Zones::get_zones();
            foreach ( $delivery_zones as $key => $delivery_zone ) {
              foreach ( $delivery_zone['shipping_methods'] as $key => $value ) {
                if ( $value->id === "free_shipping" ) {
                if ( $value->min_amount > 0 ) $min_amounts[] = $value->min_amount;
                }
              }
            }
         /*
         * */

        foreach ( $free_shipping_exist as $key => $shipping ) {
            if( isset( $shipping['requires'] ) && $shipping['requires'] == 'min_amount' ){
                $min_amounts[] =  $shipping['min_amount'] ;
            }
            if(  isset( $shipping['requires'] ) && $shipping['requires'] == 'either' ){
                $min_amounts[] =  $shipping['min_amount'] ;
            }                    
        }

        /*
         * Find lowest min_amount
         */
        if ( is_array($min_amounts) && count( $min_amounts  ) > 0 ) { 
         
            $min_amount = min($min_amounts);

            if ( $currentTotal ) { 
                /* 
                 * For fining the same for 'this product'
                 */
                $current = $currentTotal;


            }else{

                /*
                 *
                 * Get Cart Subtotal inc. Tax excl. Shipping
                 * */

                if ( self::tapify_get_tax_rates() )
                    $total = ( WC()->cart->cart_contents_total + array_sum(  WC()->cart->get_taxes() ) ) - WC()->cart->get_shipping_tax() ;
                else
                    $total = WC()->cart->cart_contents_total;

                $current = $total - (  WC()->cart->get_cart_discount_total() );
            } 
      
             /*
              *
              * If Subtotal < Min Amount Echo Notice
              * and add "Continue Shopping" button
              *
              * */

            if ( $current < $min_amount ) {
                $return ['amount']      =  number_format( $min_amount - $current, 2 );
                $return ['percentage']  =  $current / $min_amount * 100 ;
                
            }
         
        }
        return (object)($return );         
    }


    /**
    * @snippet Notice with $$$ remaining to Free Shipping @ WooCommerce Cart
    * @testedwith WooCommerce 3.4.2
    */
    public static function tapify_check_free_shipping_exist( $currentTotal  = false ) {
     
        global $woocommerce;
        
        $min_amounts = $return = array();

        $free_shipping_exist = self::tapify_free_shipping_exist_for_current_address( $currentTotal );
        if( !$free_shipping_exist ) return false;
        
        $default_zone = new WC_Shipping_Zone(0);
        $default_methods = $default_zone->get_shipping_methods();

        foreach( $default_methods as $key => $value ) {
            if ( $value->id === "free_shipping" ) {
              if ( $value->min_amount > 0 ) $min_amounts[] = $value->min_amount;
            }
        }
         

        /*
         * Get Free Shipping Methods for all other ZONES & populate array $min_amounts
         */
         
        $delivery_zones = WC_Shipping_Zones::get_zones();
         
        foreach ( $delivery_zones as $key => $delivery_zone ) {
          foreach ( $delivery_zone['shipping_methods'] as $key => $value ) {
            if ( isset( $value->id ) && $value->id === "free_shipping" ) {
                if ( isset( $value->min_amount ) && $value->min_amount > 0 ) 
                    $min_amounts[] = $value->min_amount;
            }
          }
        }
         

        /*
         * Find lowest min_amount
         */
         
        if ( is_array($min_amounts) && count( $min_amounts  ) > 0 )  return $free_shipping_exist;
         
        return false;         
    }

    /**
    * @snippet Notice with $$$ remaining to Free Shipping @ WooCommerce Cart
    * @testedwith WooCommerce 3.4.2
    */
    public static function tapify_free_shipping_exist_for_current_address( $currentTotal  = false ) {
     
        global $woocommerce;

        $min_amounts = $return = array() ;$defaultMethods = array(); $freeShippingArray = array();
        $free_shipping_exist = false; $listedCountryStatus = false;
         
        /*
         * Get Free Shipping Methods for Rest of the World Zone & populate array $min_amounts
         */

        $country_code     = $woocommerce->customer->get_shipping_country();
        $shippingData     = TPF_Ajax_events::tapify_find_shipping_cost( $country_code );

        $actualCost = NULL; 
        $chosen_shipping_methods = false;
        if(WC()->session->get('chosen_shipping_methods')) {
        $chosen_shipping_methods = (WC()->session->get('chosen_shipping_methods')[0])?WC()->session->get('chosen_shipping_methods')[0]:false;
        }
        $country_satisfies = false;;
        if( is_array($shippingData) && count($shippingData) > 0 ) {
            foreach ($shippingData as $key => $value) {


                if(isset( $value['zone_location_name']) && strtolower($value['zone_location_name']) == 'everywhere'){
                    $defaultMethods  = $value['shipping_methods'] ;
                }

                if($country_satisfies ) break;

                if(isset( $value['zone_locations']) && count( $value['zone_locations'] ) > 0 ){

                    $free_shipping_exist = false;
                    foreach ( $value['zone_locations'] as $shipKey => $shipValue) {
                        if( $free_shipping_exist ) break;

                        if(isset($shipValue->code) && $shipValue->code == $country_code ) {
                            $listedCountryStatus = true;
                            foreach ( $value['shipping_methods'] as $methodKey => $methodValue ) {
                                if( $methodValue['$method_id']  && $methodValue['$method_id'] == 'free_shipping'){
                                    $cost = isset($methodValue['cost'])?$methodValue['cost']:'0.00';
                                    $freeShippingArray[] = array( 
                                          'method_id' => isset($methodValue['rate_id'])?$methodValue['rate_id']:NULL,
                                          'method_title' => isset($methodValue['custom_name'])?$methodValue['custom_name']:NULL,
                                          'total' => "$cost",
                                          'min_amount' => isset($methodValue['min_amount'])?$methodValue['min_amount']:NULL ,
                                          'requires' => isset( $methodValue['requires'] ) ? $methodValue['requires'] : NULL ,
                                    );
                                    $free_shipping_exist = true;
                                    $country_satisfies = true;

                                }
                            }
                        }
                    }
                }
            }
        }

        if( !$listedCountryStatus && count( $defaultMethods ) > 0 ){
            foreach ( $defaultMethods as $dMethodKey => $dMethodValue ) {
                if( $dMethodValue['$method_id']  && $dMethodValue['$method_id'] == 'free_shipping'){

                    $cost = isset($dMethodValue['cost'])?$dMethodValue['cost']:'0.00';
                    $freeShippingArray = array( 
                                              'method_id'    => isset($dMethodValue['rate_id'])?$dMethodValue['rate_id']:NULL,
                                              'method_title' => isset($dMethodValue['custom_name'])?$dMethodValue['custom_name']:NULL,
                                              'total'        => "$cost",
                                              'min_amount'   => $dMethodValue['min_amount']
                                        );
                    $free_shipping_exist = true;
                    break;

                }
            }
        }


        if( !$free_shipping_exist ) return false;

        return $freeShippingArray;
    }


    /**
    * tapify_update_cart
    *
    * Update cart quantity
    */
    public static function tapify_update_cart_quantity(){
        global $woocommerce;
        $count      = isset($_POST['count'])?$_POST['count']:1;
        $key        = isset($_POST['key'])?$_POST['key']:false;
        $userStatus = isset($_POST['userStatus'])?$_POST['userStatus']:NULL;
        $return = array('status' => false , "message" => "Required filed Cart key or count missing!" ); 
        if( $count && $key ){
            $return = array('status' => true , "message" => "Cart quantity Successfully updated" );
            $woocommerce->cart->set_quantity($key, $count );
            TPF_Ajax_events::create_button_log( false , false , $userStatus);
            TPF_cart_widget::get_unique_id();
        }
        wp_send_json( $return ); die();
    }

    
    
}

TPF_cart_widget::init();

