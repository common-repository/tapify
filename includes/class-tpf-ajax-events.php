<?php
/**
 * Ajax releted function handles here.
 *
 * @package Tapify/Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * TPF_Ajax_events Class.
 */
class TPF_Ajax_events {

    

  /**
   * TPF_Ajax_events Constructor.
   */
  public function __construct() { 
    
  }

  /**
     * Hook in methods - uses WordPress ajax handlers (admin-ajax).
     */
  public static function tapify_add_ajax_events() {

        $ajax_events = array(
                'quickpay'                  => true,    // proceed order using tapify button
                'tapify_add_product_to_cart'=> true,    // Add product to cart
                'tapif_add_store_access_key'=> true,
                'tapify_update_store_access_key'=> true,
                'tapify_in_cart_page'       => true,
                'validate_country'          => true,
                'show_variable_price'       => true,
                'tapify_add_to_cart'        => true,
                'reset_store_connection'    => true,
                'tpf_apply_coupon_code'     => true,
                'tapify_add_product_to_cart_blogger'=> true,
                'get_post_data'             => true
                
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


public static function get_post_data( ) {
    try{ 
        $postId = $_POST['postId'];
        $post   = get_post( $postId );
        $post->permalink =  urlencode( get_the_permalink( $postId) );
        $return = array( "status" => true , "data" => $post );
        wp_send_json( $return ); die(); 
    } catch ( Exception $e ) {
        wp_send_json( array( "status"=>false, "message"=> $e->getMessage() ) ); die();
    }
}


public static function validationChecker( $type , $variable ){
    $return = false;
    if( $type === 'cookie' ){
        $return = ( isset( $_COOKIE[ $variable ] ) && $_COOKIE[ $variable ] !== null && $_COOKIE[ $variable ]  != "null" && $_COOKIE[ $variable ] !== undefined  && $_COOKIE[ $variable ] != 'undefined') ? $_COOKIE[ $variable ]: false;
    }
    return $return;
}



public static function is_coupon_valid(  $type ,$code , $product_id  ) {
	try { 
        if( $type === "cart"){
          $coupon = new \WC_Coupon( $code );   
          $discounts = new \WC_Discounts( WC()->cart );
          $valid_response = $discounts->is_coupon_valid( $coupon );

          if ( is_wp_error( $valid_response ) ) {  
            return array( "status"=>false, "message"=> $valid_response->get_error_message() );
          } elseif( WC()->cart->has_discount( $code ) ) { die("00s0s4ss");  
            return array( "status"=>false, "message"=> "Coupon code alredy applied" );
          }else  return array( "status"=>true );
        }else{
          $product = wc_get_product( $product_id );
          $subtotal =  $product->get_price() ;
          $coupon = new WC_Coupon($code);
          // $coupon_post = get_post($coupon->id);
          if ( ! $coupon->get_id() && ! $coupon->get_virtual() ) {
            /* translators: %s: coupon code */
            throw new Exception( sprintf( __( 'Coupon "%s" does not exist!', 'woocommerce' ), $coupon->get_code() ), 105 );
          }
          if ( $coupon->get_usage_limit() > 0 && $coupon->get_usage_count() >= $coupon->get_usage_limit() ) {
            throw new Exception( __( 'Coupon usage limit has been reached.', 'woocommerce' ), 106 );
          }
          
          if ( $coupon->get_date_expires() && apply_filters( 'woocommerce_coupon_validate_expiry_date', current_time( 'timestamp', true ) > $coupon->get_date_expires()->getTimestamp(), $coupon, $this ) ) {
            throw new Exception( __( 'This coupon has expired.', 'woocommerce' ), 107 );
          }
          if ( $coupon->get_minimum_amount() > 0 && apply_filters( 'woocommerce_coupon_validate_minimum_amount', $coupon->get_minimum_amount() > $subtotal, $coupon, $subtotal ) ) {
            /* translators: %s: coupon minimum amount */
            throw new Exception( sprintf( __( 'The minimum spend for this coupon is %s.', 'woocommerce' ), wc_price( $coupon->get_minimum_amount() ) ), 108 );
          }
          if ( $coupon->get_maximum_amount() > 0 && apply_filters( 'woocommerce_coupon_validate_maximum_amount', $coupon->get_maximum_amount() < $subtotal, $coupon ) ) {
            /* translators: %s: coupon maximum amount */
            throw new Exception( sprintf( __( 'The maximum spend for this coupon is %s.', 'woocommerce' ), wc_price( $coupon->get_maximum_amount() ) ), 112 );
          }
          if ( count( $coupon->get_product_ids() ) > 0 ) {
            $valid = false;
            if ( $product_id && in_array( $product_id, $coupon->get_product_ids() ) || in_array( $product->get_parent_id(), $coupon->get_product_ids() ) ) {
              $valid = true;
            }
            if ( ! $valid ) {
              throw new Exception( __( 'Sorry, this coupon is not applicable to selected products.', 'woocommerce' ), 109 );
            }
          }
          if ( count( $coupon->get_excluded_product_ids() ) > 0 ) {
            $products = array();
            if ( $product_id && in_array( $product->get_id(), $coupon->get_excluded_product_ids() ) || in_array( $product->get_parent_id(), $coupon->get_excluded_product_ids() ) ) {
              $products[] = $product->get_name();
            }
            if ( ! empty( $products ) ) {
              /* translators: %s: products list */
              throw new Exception( sprintf( __( 'Sorry, this coupon is not applicable to the products: %s.', 'woocommerce' ), implode( ', ', $products ) ), 113 );
            }
          }
          $discount_amount = $coupon->get_amount();
          if( $coupon->get_discount_type() === 'percent'){
            $discount_amount = $subtotal * $coupon->get_amount()/100;
          }
          $cp = array( 
            "id"          => $coupon->get_id() , 
            "coupon_type" => $coupon->get_discount_type() , // percent, fixed_cart and fixed_product. 
            "amount"      => $coupon->get_amount(),
            "coupon_code" => $coupon->get_code(),
            "discount_amount" =>$discount_amount
          );
          return array( "status"=>true , "cp" => $cp );
        }
	} catch ( Exception $e ) {
		return array( "status"=>false, "message"=> $e->getMessage() );
	}
}

    /**
     * @snippet       How to Apply a Coupon Programmatically - WooCommerce
     * @compatible    WC 3.5.4
     */

    public static function tpf_apply_coupon_code( ) {
      try{ 
        $type        = $_POST['type'];
        $product_id  = $_POST['product_id'];
        $coupon_code = $_POST['coupon'];
        $action      = $_POST['event'];
        
        if( $action == "remove" ){
          if( $type == "cart"){
              WC()->cart->remove_coupons();
              WC()->cart->remove_coupon( $coupon_code );
              WC()->cart->calculate_totals();
              self::create_button_log( false , false , null );  
          }
          $returnStatus = array( "status"=>true, "message"=> "Promoced successfully removed!" ,  "tapify_store_access_key" => get_option('tapify_store_access_key') );
        }else{

          $is_valid = self::is_coupon_valid( $type , $coupon_code ,$product_id ); 

          if( isset( $is_valid['status'] ) && $is_valid['status'] === false ) {
            $returnStatus = $is_valid;
          }else{
            if( $type === "cart") {
              WC()->cart->add_discount( $coupon_code );
            }else{
                $coupon = new \WC_Coupon( $coupon_code );  
                $cp = array( 
                    "id"          => $coupon->get_id() , 
                    "coupon_type" => $coupon->get_discount_type() , // percent, fixed_cart and fixed_product. 
                    "amount"      => $coupon->get_amount(),
                    "coupon_code" => $coupon->get_code(),
                    "product_id"  => $product_id
                  );            
            }
            $returnStatus = array( "status"=>true, "message"=> "Promoced successfully applied!" , "cp" => $cp , "tapify_store_access_key" => get_option('tapify_store_access_key') );
          }
        }
        wp_send_json( $returnStatus ); die();
        
      }catch(Exception $e) {
        return array( "status"=>false, "message"=> $e->getMessage() );
      }
    }
     /**
    * show_variable_price
    *
    * Show variable price,afther they choose the variation
    */
    public static function show_variable_price(){ 
      $shipping   = self::tapify_get_shipping_cost();
      $cost       = $_POST['cost'] * $_POST['productQty'];
      $attributes = $_POST['attributes'] ;
      $attributes_array = self::get_variation_as_array( $attributes ) ;

      if(isset( $shipping[0]['total'] )){
          $cost = $cost + $shipping[0]['total'] ;
      }
      wp_send_json(array( "status" => true , "cost" => $cost , 'formatted_attributes' => $attributes_array ) ); die();
    }

    /**
    * show_variable_price
    *
    * Show variable price,afther they choose the variation
    * Array ( [attribute_pa_color] => black, [attribute_pa_size] => small )
    */
    public static function get_variation_as_array( $attributes ){ 
      $attributes_array = array();
      if($attributes && count($attributes) > 0 ){
        $formatted_variation =  wc_get_formatted_variation( $attributes, true );
        $explode  = explode(',', $formatted_variation);
          foreach ( $explode as $key => $value) {
            $explode_secondary  = explode(':', $value);
            if(isset( $explode_secondary[0] ) && isset($explode_secondary[1] ) )
              $attributes_array[ trim( $explode_secondary[0] )] = trim($explode_secondary[1]);

        }
      }

      return $attributes_array ;
    }

    /**
    * tapif_add_store_access_key
    *
    * Add Store access key
    */
    public static function reset_store_connection(){
        $storeAccessKey = get_option('tapify_store_access_key');
        $return = array();
        $args = array(
                'body'  => json_encode( 
                      array( 
                        "storeAccessKey" => $storeAccessKey 
                      )
                    ) ,
                'blocking' => true,
                'headers'   => array(
                            // 'Authorization'         => $_POST['jwtToken'] ,
                            'storeAccessKey'        => $storeAccessKey,
                            'Content-Type'      => 'application/json'
                      ),
                'cookies'  => array()
            );

              $response = wp_remote_post( TPF_NODE_API_URL . 'v1/reset/store' , $args );
              $json_dec = array();
              if( !is_wp_error($response)  && isset($response['body'])){
                  $json_dec = json_decode($response['body']);
                  if( $json_dec && isset($json_dec->data)){
                     $return =  array("status" => true , "message"=>"successfully removed store" );
                  }
              }
        update_option('tapify_store_access_key' ,NULL );
        update_option('tapify_store_connection_status' , NULL );
        
        wp_send_json( $return ); die(); 
    }
 /**
    * tapif_add_store_access_key
    *
    * Add Store access key
    */
    public static function tapif_add_store_access_key(){

        update_option('tapify_store_access_key' , $_POST['storeAccessKey']);
        update_option('tapify_store_connection_status' , 'storeConnected');

        if($storeAccessKey = get_option('tapify_store_access_key')) :
          if( $_POST['is_ecommerce'] && $_POST['is_ecommerce']!== 0 ){
              $store_url = get_home_url();
              $endpoint = '/wc-auth/v1/authorize';
              $params = [
                  'app_name' => TPF_APPNAME,
                  'scope' => 'read_write',
                  'user_id' => $storeAccessKey,
                  'return_url' => $_POST['redirectUrl'],
                  'callback_url' => TPF_CALLBACKURL
              ];

              $query_string = http_build_query( $params );
              $return =  array("status" => true , "url" => $store_url . $endpoint . '?' . $query_string );
          
          }else{

            $args = array(
                'body'  => json_encode( 
                      array( 
                        "storeAccessKey" => $storeAccessKey ,
                        "storeUrl"       =>  get_home_url()
                      )
                    ) ,
                'blocking' => true,
                'headers'   => array(
                            'Authorization'         => $_POST['jwtToken'] ,
                            'storeAccessKey'        => $storeAccessKey,
                            'Content-Type'      => 'application/json'
                      ),
                'cookies'  => array()
            );

              $response = wp_remote_post( TPF_NODE_API_URL . 'v1/set/blogger' , $args );
              $json_dec = array();

              if( !is_wp_error($response)  && isset($response['body'])){
                  $json_dec = json_decode($response['body']);

                  if( $json_dec && isset($json_dec->data)){
                     update_option('tapify_store_access_key' , $json_dec->data);
                     update_option('tapify_store_connection_status' , "connected");
                     $return =  array("status" => true );
                  }else{
                      return  array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'store_access_key','invalid')  ) ;
                  }
              }
          }
        wp_send_json( $return ); die(); 
      endif; 
    }

    /**
    * tapif_add_store_access_key
    *
    * Add Store access key
    */
    public static function tapify_update_store_access_key(){
        if( !isset($_GET['storeAccessKey'])) return false;

        update_option('tapify_store_access_key' , $_GET['storeAccessKey']);
        update_option('tapify_store_connection_status' , 'connected');

        $return =  array("status" => true  ); 
        wp_send_json( $return ); die(); 
    }



     public static function tapify_access_wc_permission( ){ 
        $path = 'admin.php?page=tapify_settings&wc=api';
        $redirectUrl = admin_url($path);

         if($storeAccessKey = get_option('tapify_store_access_key')) :
              $store_url = get_home_url();
              $endpoint = '/wc-auth/v1/authorize';
              $params = [
                  'app_name' => TPF_APPNAME,
                  'scope' => 'read_write',
                  'user_id' => $storeAccessKey,
                  'return_url' => $redirectUrl,
                  'callback_url' => TPF_CALLBACKURL
              ];

              $query_string = http_build_query( $params );
              $return =  array("status" => true , "url" => $store_url . $endpoint . '?' . $query_string );
          else:
             $return =  array("status" => false , "url" => false , "message" => TPF_variables_json::get_response_message( 'store_access_key','failed_to_add') );
          endif;
          return $return;
     }


     public static function validate_store_access_key( $storeAccessKey , $connect = false ){ 
        try{
            $args = array(
                      'body'      => json_encode( 
                              array( 
                                "storeAccessKey" => $storeAccessKey ,
                                "storeUrl"       =>  get_home_url(),
                                "connect"        =>  $connect
                              )
                            ) ,
                      'blocking'  => true,
                      'headers'   => array(
                              'storeAccessKey'        => $storeAccessKey ,
                              'Content-Type'      => 'application/json'
                              ),
                      'cookies'   => array()
                  );

            $response = wp_remote_post( TPF_NODE_API_URL . 'v1/validate/accesssKey' , $args );
            $json_dec = array();


            if( !is_wp_error($response)  && isset($response['body'])){
                $json_dec = json_decode($response['body']);
                
                if( $json_dec && isset($json_dec->_id)){
                    return  array('status'=>true,'message'=> TPF_variables_json::get_response_message( 'store_access_key','valid')  , 'data'=> $json_dec ) ;
                }else{
                    return  array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'store_access_key','invalid')  ) ;
                }
            }
            return  array('status'=>false,'message'=>TPF_variables_json::get_response_message( 'store_access_key','failed_to_add')) ;
        }catch(Exception $e) {

            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }

    public static function get_cart_data( $userStatus ){ 
        try{

          if($storeAccessKey = get_option('tapify_store_access_key')) {
            $args = array(
                      'body'      => json_encode( 
                              array( 
                                "storeAccessKey" => $storeAccessKey ,
                                "storeUrl"       =>  get_home_url(),
                                "userStatus"     => $userStatus
                              )
                            ) ,
                      'blocking'  => true,
                      'headers'   => array(
                              'storeAccessKey'        => $storeAccessKey ,
                              'Content-Type'      => 'application/json'
                              ),
                      'cookies'   => array()
                  );

            $response = wp_remote_post( TPF_NODE_API_URL . 'v1/cart' , $args );
            $json_dec = array();
            print_r($response['body']); die("ppp");

            if( !is_wp_error($response)  && isset($response['body'])){
                $json_dec = json_decode($response['body']);
                
                return $json_dec;
            }
            return  false;
          }
        }catch(Exception $e) {

            return false;
        }
    }


    public static function isStoreActive(){ 
        try{

            $storeAccessKey = get_option('tapify_store_access_key');
            $args = array(
                      'body'      => json_encode( 
                              array( 
                                "storeAccessKey" => $storeAccessKey ,
                                "storeUrl"       =>  get_home_url(),
                              )
                            ) ,
                      'blocking'  => true,
                      'headers'   => array(
                              'storeAccessKey'        => $storeAccessKey ,
                              'Content-Type'      => 'application/json'
                              ),
                      'cookies'   => array()
                  );

            $response = wp_remote_post( TPF_NODE_API_URL . 'v1/store/isActive' , $args );
            $json_dec = array();

 
            if( !is_wp_error($response)  && isset($response['body'])){
                $json_dec = json_decode($response['body']);

                if( $json_dec && isset($json_dec->status) && isset($json_dec->storeStatus) ){
                    if( $json_dec->status !== 'inactive' && $json_dec->storeStatus === 'connected' ) 
                        return $json_dec;
                    else return false;
                }else{
                    return  false;
                }
            }
            return false;
        }catch(Exception $e) {

            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }

    public static function checkProductSynced( $productId =false ){ 
        try{
            if( !$productId ) return false;
            $storeAccessKey = get_option('tapify_store_access_key');
            $args = array(
                      'body'      => json_encode( 
                              array( 
                                "storeAccessKey" => $storeAccessKey ,
                                "_id"       =>  $productId,
                              )
                            ) ,
                      'blocking'  => true,
                      'headers'   => array(
                              'storeAccessKey'        => $storeAccessKey ,
                              'Content-Type'      => 'application/json'
                              ),
                      'cookies'   => array()
                  );

            $response = wp_remote_post( TPF_NODE_API_URL . 'v1/check/productSynced' , $args );
            $json_dec = array();

            if( !is_wp_error($response)  && isset($response['body'])){
                $json_dec = json_decode($response['body']);

                if( $json_dec && isset($json_dec->status) && $json_dec->status === true ){
                        return true;;
                }else{
                    return  false;
                }
            }
            return false;
        }catch(Exception $e) {

            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }
    public static function tapifyAppConfig(){ 
       try{
            $args = array(
                      'body'      => json_encode( 
                              array( 
                                "storeUrl"       =>  get_home_url(),
                              )
                            ) ,
                      'blocking'  => true,
                      'headers'   => array(
                              'Content-Type'      => 'application/json'
                              ),
                      'cookies'   => array()
                  );

            $response = wp_remote_post( TPF_NODE_API_URL . 'v1/config' , $args );
            $json_dec = array();

            $plugin_data = get_plugin_data( __FILE__ );

            if( !is_wp_error($response)  && isset($response['body'])){
                $json_dec = json_decode($response['body']);

                if( $json_dec && isset($json_dec->active)){
                    return $json_dec;
                }else{
                    return  false;
                }
            }
            return false;
        }catch(Exception $e) {

            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }




    /*
     * Main Function to create order through quickpay button
     * @param : product_id, count 
     * 
     * */
    public static function quickpay(){

        $return     = array('status'=>true,'message'=> TPF_variables_json::get_response_message( 'order','success') ) ;


        /*
         * Chcekk if usere is logged in or not
         *
         * Return false if not nogged in
         * */

        /*
          if(!is_user_logged_in()) {
            wp_send_json( array('status'=>false,'message'=>"Sorry you need to login for purchase!") );
            die();
          }
        */

        /*
         * Chcekk if JWT token exist.
         *
         * Return false if not 
         * */

        if(!$_POST['jwtToken'] || !$_POST['storeAccessKey']){
            wp_send_json( array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'jwt','missing')) );
            die();
        }

        /*
         * Fetch billing and shipping address (WC)
         * @params [biiling,shipping]
         */
        
        /* 
         * $billing    = self::tapify_get_address('billing');  
         * $shipping   = self::tapify_get_address('shipping');
         * /

        /*
         * Assuming shipping and billing address are same
         *
         * TODO : make it as different if necessary
         *
         */
        $shipping = self::tapify_make_shipping_address();


        if( $_POST['product_id'] && $_POST['count'] ){
            /*
            * if the user clicks from the product details page
            * Get only the corresponding product,
            * tapify_get_product
            * */

            $products   = self::tapify_get_product( $_POST['product_id'] , $_POST['count'] );
        }else{

            /*
             * Get all products frm cart
             * tapify_get_products_from_cart
             */
            $products   = self::tapify_get_products_from_cart();
        }        

        if($products && $products['status']){

            /*
             * Calculate Shiiping cost 
             *
             * Currently set to wc-customer session
             * TODO : Set Tapify users address as wc-session
             *
             * */
            $shipping_lines = self::tapify_get_shipping_cost();

            /*
             * Ctreate Order 
             *
             * @params [ billing  , shipping , products ]
             * */
            $order      = self::tapify_create_wc_order( $shipping ,$shipping ,$products['data'] , $shipping_lines );
            if($order["status"]){


                /*
                 * Update Order details to tapify db
                 *
                 * */
                $updateTapify= self::tapify_update_od( $order['data'] );

                if($updateTapify['status']){


                    /*
                     * redirect to paymnet reciept page,
                     *
                     * */
                    $redirectrUrl = wc_get_endpoint_url( 'order-received', $order['data']->id , wc_get_page_permalink( 'checkout' ) );

                    $redirectrUrl = $redirectrUrl . '?key=' . $order['data']->order_key;

                    $return     = array('status'=>true,'message'=> TPF_variables_json::get_response_message( 'order','success')  ,'data' => $order , 'redirectrUrl' => $redirectrUrl  ) ;
                }
                else
                $return     = array('status'=>false,'message'=>$updateTapify['message'] ,'data' => [] ) ;
            }   else {
                $return     = array('status'=>false,'message'=> $order["message"] ,'data' => [] ) ;
            }

        }else{

            /*
             * return false if condition failes
             * */

            $return     = array('status'=>false,'message'=>  TPF_variables_json::get_response_message( 'code','server_error')  ) ;

        }

        wp_send_json( $return ); die();
    }

    

    /**
     *
     * Calculate shipping cost 
     * */

    public static  function tapify_get_shipping_cost( $productId = false ){
        global $woocommerce;
        $country_code     = $woocommerce->customer->get_shipping_country();
        
        $shippingData     = self::tapify_find_shipping_cost( $country_code );

// echo "<pre>"; print_r($shippingData);die("p");
        $shipping_cost    = self::tapify_make_shipping_method_array( $shippingData , $country_code ,$productId );
        // $setted    = self::tapify_set_chosen_shipping_method( $method_id  );
        return $shipping_cost;

    }

    /**
     *
     * Calculate shipping cost 
     * */

    public static  function tapify_get_shipping_cost_from_session( $productId = false ){
        global $woocommerce;
        $country_code     = $woocommerce->customer->get_shipping_country();
      
        $shippingData     = self::tapify_find_shipping_cost( $country_code );


        $shipping_cost    = self::tapify_make_shipping_method_array_session( $shippingData , $country_code , $productId );
        // $setted    = self::tapify_set_chosen_shipping_method( $method_id  );
        return $shipping_cost;

    }



    public static function tapify_make_shipping_method_array( $data = array() , $country_code , $productId = false ){

      $actualCost = $defaulCost =  $actualCost1 = NULL; 
      $chosen_shipping_methods = false; $listedCountryStatus = false;  $concatenatedAddess = false;
      global $woocommerce;
      if(WC()->session->get('chosen_shipping_methods')) {
        $chosen_shipping_methods = (WC()->session->get('chosen_shipping_methods')[0])?WC()->session->get('chosen_shipping_methods')[0]:false;
      }
      $state_code       = $woocommerce->customer->get_shipping_state();
      if( $state_code ) $concatenatedAddess = $country_code .':'. $state_code;

      if( is_array($data) && count($data) > 0 ) {
        foreach ($data as $key => $value) {
          if(isset( $value['zone_location_name']) && strtolower($value['zone_location_name']) == 'everywhere'){
            $defaultMethods  = $value['shipping_methods'] ;
          }
          if(isset( $value['zone_locations']) && count( $value['zone_locations'] ) > 0 ){
            foreach ( $value['zone_locations'] as $shipKey => $shipValue) {
             
              if(isset($shipValue->code) && $shipValue->code == $country_code ) {
                // $rate_id = self::get_shipping_method( $value['shipping_methods'] );
                $listedCountryStatus = true;
                $actualCost = self::tpf_get_shipping_method( $value['shipping_methods'] , $chosen_shipping_methods , $productId );
              }
              if( $concatenatedAddess ) {

                  if(isset($shipValue->code) && $shipValue->code == $concatenatedAddess ) {
                    // $rate_id = self::get_shipping_method( $value['shipping_methods'] );
                    $listedCountryStatus = true;
                    $actualCost1 = self::tpf_get_shipping_method( $value['shipping_methods'] , $chosen_shipping_methods , $productId );
                  }
              }
            }
          }
        }
      }
      if( !$listedCountryStatus && count( $defaultMethods ) > 0 ){
        $defaulCost = self::tpf_get_shipping_method( $defaultMethods, $chosen_shipping_methods , $productId );
      }

      // if(!$rate_id)  $rate_id = ($defaulId)?$defaulId:false ;
      if($actualCost1)  $actualCost = $actualCost1;
      if(!$actualCost)  $actualCost = ($defaulCost)?$defaulCost:NULL ;

      return $actualCost;
    }

     public static function tapify_make_shipping_method_array_session( $data = array() , $country_code ,$productId = false ){

      $actualCost = NULL; $exist = false; $chosen_shipping_methods = false ; $actualCost1 = false;
      $defaulCost = array() ; $defaultMethods = false; $concatenatedAddess = false;
      global $woocommerce;
      if(WC()->session->get('tpf_current_shipping_method')) {
        $chosen_shipping_methods = (WC()->session->get('tpf_current_shipping_method'))?WC()->session->get('tpf_current_shipping_method'):false;
      }

      $state_code       = $woocommerce->customer->get_shipping_state();
      if( $state_code ) $concatenatedAddess = $country_code .':'. $state_code;

      // print_r($chosen_shipping_methods); die("p");
      $listedCountryStatus = false;
      if( is_array($data) && count($data) > 0 ) {
        foreach ($data as $key => $value) {
          if(isset( $value['zone_location_name']) && strtolower($value['zone_location_name']) == 'everywhere'){
            
            /*
             * Additionaly added change 
             * Comented the below code and added the same at the end of the loop with a condition
             **/
            $defaultMethods  = $value['shipping_methods'] ;
          
          }
          if( $exist ) break;
          if(isset( $value['zone_locations']) && count( $value['zone_locations'] ) > 0 ){
            foreach ( $value['zone_locations'] as $shipKey => $shipValue) {
              if(isset($shipValue->code) && $shipValue->code == $country_code ) {
                // $rate_id = self::get_shipping_method( $value['shipping_methods'] );
                $listedCountryStatus = true;

                $returnCost = self::tpf_get_shipping_method( $value['shipping_methods'] , $chosen_shipping_methods , $productId );

                if( isset($returnCost[0]) && count( $returnCost[0] ) > 0 ) {
                    $actualCost = $returnCost;
                    $exist = true;
                    break;
                }
              }

              if( $concatenatedAddess ) {

                  if(isset($shipValue->code) && $shipValue->code == $concatenatedAddess ) {
                    // $rate_id = self::get_shipping_method( $value['shipping_methods'] );
                    $listedCountryStatus = true;
                    $actualCost1 = self::tpf_get_shipping_method( $value['shipping_methods'] , $chosen_shipping_methods , $productId );
                  }
              }


            }
          }
        }
      }
      if( !$listedCountryStatus && $chosen_shipping_methods ){
        $defaulCost = self::tpf_get_shipping_method( $defaultMethods, $chosen_shipping_methods , $productId );
      }

      // if(!$rate_id)  $rate_id = ($defaulId)?$defaulId:false ;
      if($actualCost1)  $actualCost = $actualCost1;
      if(!$actualCost)  $actualCost = ($defaulCost)?$defaulCost:NULL ;

      return $actualCost;
    }

    public static function tpf_get_shipping_method( $shipping_methods  ,$chosen_shipping_methods ,$productId = false ) {
      $shippings  = array();
      $product   = $shipping_class_id = false;
      if( $productId ) $product = wc_get_product( $productId );
      if( $product)  $shipping_class_id = $product->get_shipping_class_id(); 

      if( $shipping_methods && count($shipping_methods ) > 0 ){

        foreach( $shipping_methods as $method){
          // $rate_id = $method['rate_id'];
          if(!$chosen_shipping_methods){
              if(isset($method['$method_id'] ) && $method['$method_id'] == 'free_shipping' ) continue;

              if(isset($method['$method_id'] ) && $method['$method_id'] !== 'free_shipping' ){
                  WC()->session->set( 'tpf_current_shipping_method' , $method['rate_id'] );
              } 
              $cost = isset($method['cost'])?$method['cost']:0;
              if( isset( $method['classes_&_costs'] ) && count( $method['classes_&_costs'] ) > 0){
                if( $shipping_class_id ){
                  foreach ( $method['classes_&_costs'] as $keycc => $valuecc) {
                      if( $shipping_class_id == $keycc ){
                        $cost = $cost + $valuecc['cost'];
                      }
                  }
                }
              }
              $shippings = array( 
                  'method_id' => isset($method['rate_id'])?$method['rate_id']:NULL,
                  'method_title' => isset($method['custom_name'])?$method['custom_name']:NULL,
                  'total' => "$cost",
                  'tax_status' => isset($method['tax_status'])?$method['tax_status']:NULL,
                );
              break;
          }else{
            
            if( $method['rate_id'] ==  $chosen_shipping_methods ){

              if(isset($method['$method_id'] ) && $method['$method_id'] !== 'free_shipping' ){
                  WC()->session->set( 'tpf_current_shipping_method' , $method['rate_id'] );
              }
              $cost = isset($method['cost'])?$method['cost']:0;              

              if( isset( $method['classes_&_costs'] ) && count( $method['classes_&_costs'] ) > 0){
                if( !$shipping_class_id ) $shipping_class_id = '0';
                foreach ( $method['classes_&_costs'] as $keycc => $valuecc) {
                     if( $shipping_class_id == $keycc ){
                      $classCost = $valuecc['cost']?$valuecc['cost']:0;
                      $cost = $cost + $classCost;
                    }
                }
                
              }
              $shippings = array( 
                  'method_id' => isset($method['rate_id'])?$method['rate_id']:NULL,
                  'method_title' => isset($method['custom_name'])?$method['custom_name']:NULL,
                  'total' => "$cost",
                  'tax_status' => isset($method['tax_status'])?$method['tax_status']:NULL,
                );

              break;
            }
          }
          
        }
      }

      $shipping_lines[] =  $shippings;

      return $shipping_lines;
    }

  /********************************* Shipping cost calculation ends here ****************************************/

    public static function tapify_make_shipping_address(){
        if(!$_POST['shippingAddress']) return false;

        $address = $_POST['shippingAddress'];

        $shipping = array(
                "first_name"=> $address['firstName'],
                "last_name" => $address['lastName'],
                "company"   => ($address['company'])?$address['company']:"",
                "country"   => $address['country'],
                "address_1" => ($address['address1'])?$address['address1']:"", 
                "address_2" => ($address['address2'])?$address['address2']:"", 
                "city"      => $address['city'],
                "state"     => $address['state'],
                "postcode"  => $address['zipcode'],
                "phone"     => $address['phoneNumber'],
                "email"     => $address['email']
            );

        return $shipping;
    }

    public static function tapify_update_od( $orders ){ 
        try{
            $args = array(
                    'body'      => json_encode(  array(  "orderId" => (int)$orders->id ) ) ,
                    'blocking'  => true,
                    'headers'   => array(
                            'Authorization'         => $_POST['jwtToken'] ,
                            'storeAccessKey'        => $_POST['storeAccessKey'],
                            'Content-Type'      => 'application/json'
                            ),
                    'cookies'   => array()
                );

            $response = wp_remote_post( TPF_NODE_API_URL .'v1/wc/orders' , $args );
            $json_dec = array();
            if(isset($response['body'])){
                $json_dec = json_decode($response['body']);
                if(isset($json_dec->result->ok)){
                    return  array('status'=>true,'message'=>"Updated to Tapify (mongodb)!") ;
                }else{
                    return  array('status'=>false,'message'=> $response['body'] ) ;
                }
            }
            return  array('status'=>false,'message'=>"Failed to Update to Tapify (mongodb)!") ;
        }catch(Exception $e) {

            /*
            * Return error
            * TODO : Refund  from tapify
            *
            * FYI : Shipping details required parameters must satisfy the validations
            * eg :  phone must be integer 
            * */ 
            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }

    
    /*
     * tapify_get_product
     *
     * Get only the corresponding product.
     * @return : array
     *
     * */
    public static function tapify_get_product( $product_id , $count  ,$variation_id ){
      $product_array = array();

      if(!$product_id ) {

        /*
         * Rteurn false if cart is empty
         * */
       return  array('false'=>false,'message'=>"Produt missing!") ;
      }

      foreach(WC()->cart->get_cart() as $item => $values) {

          if( $product_id == $values['product_id'] ) {
            if($variation_id && $variation_id != "0" ){
              if($variation_id != $values['variation_id']) continue;
            }



            $product_array['product_id']    = $values['product_id'];
            $product_array['quantity']      = $values['quantity'];

            /*
             * Chcek if variable product exist
             * if exist add to the product array
             *
             * */
            if(isset($variation_id) && $variation_id != 0 ) 
                $product_array['variation_id']      = $variation_id;


            $returnArray[] = $product_array;

            /*
             * remove corresonding prodcut from the cart, 
             * after we got all params  for the create order api
             *
             * */
            WC()->cart->remove_cart_item( $item );
          }

      }

      /*
       * Rteurn false if cart is empty
       * */
     return  array('status'=>true,'data'=> $returnArray ) ;

    }

    /*
     * tapify_get_products_from_cart
     *
     * Get all products from cart and create an array.
     * @return : array
     *
     * */
    public static function tapify_get_products_from_cart(){
        global $woocommerce;
        $items          = $woocommerce->cart->get_cart();
        $product_array  = array();
        $returnArray    = array();

      
        if(!empty($items)){
            foreach($items as $item => $values) {

                /*
                 * Create return array
                 *
                 * */
                if(isset($values['product_id'])){
                    $product_array['product_id']    = $values['product_id'];
                    $product_array['quantity']      = $values['quantity'];

                        /*
                         * Chcek if variable product exist
                         * if exist add to the product array
                         *
                         * */
                     if(isset($values['variation_id']) && $values['variation_id'] != 0 ) 
                        $product_array['variation_id']      = $values['variation_id'];

                    $returnArray[] = $product_array;
                }
            }
            /*
             * Clear cart afther we got all params  for the create order api.
             *
             * */
            self::tapify_empty_cart();
            
        }else{

            /*
             * Rteurn false if cart is empty
             * */
           return  array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'cart','empty')) ;
        }
        return  array( 'status'=>true,'data'=> $returnArray ) ;
    }

    /**
     * Get  address.
     *
     * @param string $load_address
     */
    public static function tapify_get_address( $load_address = 'billing' , $html = false ) {

        $current_user = wp_get_current_user();
        $load_address = sanitize_key( $load_address );

        $address = WC()->countries->get_address_fields( get_user_meta( get_current_user_id(), $load_address . '_country', true ), $load_address . '_' );

        $return = array();

        /*
         * Prepare values
         * return array
         */
        foreach ( $address as $key => $field ) {

            $value = get_user_meta( get_current_user_id(), $key, true );

            if ( ! $value ) {
                switch ( $key ) {
                    case 'billing_email' :
                    case 'shipping_email' :
                        $value = $current_user->user_email;
                    break;
                    case 'billing_country' :
                    case 'shipping_country' :
                        $value = WC()->countries->get_base_country();
                    break;
                    case 'billing_state' :
                    case 'shipping_state' :
                        $value = WC()->countries->get_base_state();
                    break;
                }
            }
            $return[str_replace($load_address."_","",$key)] = $value;
            
        }
        return $return;
    }

    public static function tapify_empty_cart(){ 
        if ( class_exists( 'WooCommerce' ) ) {
          global $woocommerce;
          $woocommerce->cart->empty_cart();
        }
        return true;
    }

    /**
    * tapify_in_cart_page
    *
    * Add product to cart
    */
    public static function tapify_in_cart_page(){
        global $woocommerce;
        $cartKey        = NULL;

        /*
        * check if user byes the Cart
        * No nedd to update any thing just pass the wc session uuid
        *
        * */

        $cart_cookie = self::get_unique_id_from_cookie();
        self::create_button_log( false , false , false );   
        if ( WC()->cart->get_cart_contents_count() == 0 ){ 
          $return   = array( 'status'=>false , 'message' =>  TPF_variables_json::get_response_message( 'cart','empty' ) ) ;
          wp_send_json( $return ); die();
        }

        if(!$cart_cookie )
        $return  = array('status'=>false , 'message' => TPF_variables_json::get_response_message( 'cart','cookie_missing')  ) ;
        else{
            $data     = array('uuid' =>  $cart_cookie , 'key' => $cartKey );
            $return   = array( 'status'=>true , 'data' => $data ) ;
        }

        wp_send_json( $return ); die();
    }


    /**
    * validate_country
    *
    * Validate country - Confirm Users shipping country is in the list of wc allowed countries
    */
    public static function get_unique_id_from_cookie( $create = false ){

        try{ 
            $tapify_cookies       = TPF_Ajax_events::get_tapify_cookies();
            if( $tapify_cookies && isset( $tapify_cookies['status'] ) && $tapify_cookies['status'] == 1 && isset( $tapify_cookies['data']) ){

                $tapifyCookies   = $tapify_cookies['data'];

                return isset( $tapifyCookies->tapify_cart_cookie_key )?$tapifyCookies->tapify_cart_cookie_key:NULL;
            }else throw new Exception( "Missing tapify cookies" ,  105 );                     
        }catch(Exception $e) {
            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }


    /**
    * validate_country
    *
    * Validate country - Confirm Users shipping country is in the list of wc allowed countries
    */
    public static function validate_country(){
      global $woocommerce;
      $allowed_countries = self::get_allowed_countries();
      $args = array(
                  'body'      => json_encode(array()) ,
                  'blocking'  => true,
                  'headers'   => array(
              'Authorization'     => $_POST['token'] ,
                          'Content-Type'      => 'application/json'
                          ),
                  'cookies'   => array()
              );

      $response = wp_remote_post( TPF_NODE_API_URL . 'v1/user/country' , $args );
      $json_dec = array();

      if(isset($response['body'])){
        $json_dec = json_decode($response['body']);
        if(isset($json_dec->country)){
          if (array_key_exists(strtoupper( $json_dec->country ), $allowed_countries )){
            $return = array('status'=>true,'message'=> TPF_variables_json::get_response_message( 'shipping_address','valid')  ) ;
          }else{
            $return =  array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'shipping_address','no_shipping') ) ;
          }   
        }else{
            $return =  array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'shipping_address','no_shipping')  ) ;
        }
      }else{
        $return =  array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'shipping_address','no_shipping')  ) ;
      }
      wp_send_json( $return );
      die();
    }

     /**
    * tapify_add_product_to_cart
    *
    * Add product to cart (set new quantity)
    */
    public static function tapify_add_product_to_cart(){ 
        global $woocommerce;

        $productId      = $_POST['product_id'];
        $tabStatus      = $_POST['tabStatus'];
        $cOunt          = isset($_POST['count'])?$_POST['count']:1;
        $variationId    = isset($_POST['variation_id'])?$_POST['variation_id']:false;
        $items          = $woocommerce->cart->get_cart();
        $exist          = false;
        $product        = wc_get_product( $productId );
        $cartKey        = NULL;
 
        if( $tabStatus == 'cart' ){

          /*
           * check if user byes the Cart
           * No nedd to update any thing just pass the wc session uuid
           *
           * */

          if ( WC()->cart->get_cart_contents_count() == 0 ){ 
             $return   = array( 'status'=>false , 'data' => "Cart is empty!" ) ;
          }else{
            $cart_cookie = self::get_unique_id_from_cookie();
            self::create_button_log( false , false  );   
            if(!$cart_cookie )
                $return  = array('status'=>false , 'message' => "Missing session!"   ) ;
            else{
                $data     = array('uuid' =>  $cart_cookie , 'key' => $cartKey );
                $return   = array( 'status'=>true , 'data' => $data ) ;
            }
          }
          
          
          wp_send_json( $return );
          die();
        }
        
        $this_product = self::tapify_get_this_product($_POST);

        if($this_product && count($this_product) > 0 ){ 
          self::create_button_log( $this_product , false ); 
        }

        $cart_cookie = self::get_unique_id_from_cookie();
        if(!$cart_cookie )
            $return  = array('status'=>false , 'message' => TPF_variables_json::get_response_message( 'cart','cookie_missing')   ) ;
        else{
            $data     = array('uuid' =>  $cart_cookie , 'key' => $cartKey );
            $return  = array('status'=>true , 'data' => $data  ) ;
        }
        
        wp_send_json( $return );
        die();
    }


    /**
    * tapify_add_product_to_cart_blogger
    *
    * Add product to cart (set new quantity)
    */
    public static function tapify_add_product_to_cart_blogger(){ 
      try{
          global $woocommerce;

          $postId         = $_POST['postId'];
          $_id            = $_POST['_id'];
          $tabStatus      = $_POST['tabStatus'];
          $quantity       = isset($_POST['count'])?$_POST['count']:1;
          $selectedVariations = [];
          if( isset( $_POST['selectedVariations'])) 
              $selectedVariations = $_POST['selectedVariations'];
          // $variationId    = isset($_POST['variation_id'])?$_POST['variation_id']:false;
          $userStatus     = isset($_POST['userStatus'])?$_POST['userStatus']:NULL;
          $exist          = false;
          $cartKey        = NULL;

          if( $tabStatus == 'cart' ){ }
          
          $this_product   = self::get_and_process_bloggers_product( $quantity , $postId , $_id , $selectedVariations , $userStatus ); 

          if( isset( $this_product['product'] ) && isset( $this_product['store'] ) ){
            $product      = $this_product['product'];
            $store        = $this_product['store'];
            $type         = $this_product['type']; 
            TPF_for_bloggers::create_button_log_bloggers( $product , $userStatus , $store , $type , false ,  false );
            $cart_cookie = self::get_unique_id_from_cookie();
            if(!$cart_cookie )
              $return  = array('status'=>false , 'message' => TPF_variables_json::get_response_message( 'cart','cookie_missing')   ) ;
            else{
              $data     = array(
                            'uuid' =>  $cart_cookie , 'key' => $cartKey , 'storeAccessKey' => $store['storeAccessKey'] ,
                            'orderPlacedFrom' =>  get_option('tapify_store_access_key'));
              $return  = array('status'=>true , 'data' => $data   ) ;
            }
            wp_send_json( $return );
            die();
         }
      } catch ( Exception $e ) {
        wp_send_json( array( "status"=>false, "message"=> $e->getMessage() ) ); die();
      }
    }


    /**
    * create_current_product_object
    *
    * Xreate current product object to  log collection
    */
    public static function create_current_product_object( $productId ){
      $thisProduct  = array();
      $variations = array();
      if( $productId ) {
          $image_url = wc_placeholder_img_src();
          $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $productId  ,'full' ) );
          if($thumbnail && isset($thumbnail[0])) $image_url =  $thumbnail[0] ;

          $current_product =  wc_get_product( $productId );
          if( $current_product->is_type( 'variable' ) ) {
              $children   = $current_product->get_children( $args = '', $output = OBJECT ); 
              foreach ($children as $key=>$value) {
                  $product_variatons = new WC_Product_Variation($value);
                  if ( $product_variatons->exists() && $product_variatons->variation_is_visible() ) {
                      $variations[$value] = $product_variatons->get_variation_attributes();
                  }
              }     
          }
          $thisProduct['_id']         = $productId;
          $thisProduct['variations']  = $variations;
          $thisProduct['name']        = $current_product->get_title();
          $thisProduct['url']         = get_permalink( $productId );
          $thisProduct['price']       = number_format( $current_product->get_price() , 2 );
          $thisProduct['currency']    = get_option('woocommerce_currency');
          $thisProduct['price_inc_tax']= number_format( TPF_wc_fn::tpf_get_product_price_include_tax( $productId ) , 2 );
          $thisProduct['image']       = $image_url;
      }
      return $thisProduct; 
    }

    /**
    * tapify_add_product_to_cart
    *
    * Add product to cart (set new quantity)
    */
    public static function tapify_get_this_product( $postArray ){

        $productId    = isset($postArray['product_id'])?$postArray['product_id']:false;
        $variation_id = isset($postArray['variation_id'])?$postArray['variation_id']:false;
        $quantity     = isset($postArray['count'])?$postArray['count']:false;
        $thisProduct  = array();
        if( $productId ) {
            $current_product =  wc_get_product( $productId );
            if( $current_product->is_type( 'variable' ) ) {
                    if( $variation_id && $variation_id != 0 ) {

                      $thisProduct = TPF_cart_widget::tapify_get_product_object( $productId , $variation_id ,$quantity ,$attributes  = '' , $formattedAttributes = array());
                               
                    }else{
                      $thisProduct['variation_validation']         = true ;
                    }
              
            }else{

              $thisProduct = TPF_cart_widget::tapify_get_product_object( $productId , $variation_id ,$quantity ,$attributes  = '' , $formattedAttributes = array());
            
          }

          if( isset($thisProduct['product_id'])) {
              $thisProduct['_id']         = $thisProduct['product_id'];
              $thisProduct['count']       = $thisProduct['quantity'];
              unset($thisProduct['product_id']);
              unset($thisProduct['quantity']);
          }

      }

      
      return $thisProduct;
    }

   
    /**
    * tapify_add_to_cart
    *
    * Add product to cart, (increase quantity)
    */
    public static function tapify_add_to_cart(){
        global $woocommerce;

        $productId      = $_POST['product_id'];
        $cOunt          = isset($_POST['count'])?$_POST['count']:1;
        $variationId    = isset($_POST['variation_id'])?$_POST['variation_id']:false;
        $items = $woocommerce->cart->get_cart();
        $exist = false;
        $attributes = array();
        $product  = wc_get_product( $productId );
       
        if( $product->is_type( 'variable' ) ){ 

          $attributes = self::tpf_get_product_attributes( $productId , $variationId );

          if(isset( $_POST['attributes'])) {
            $explode_attr = explode(',', $_POST['attributes'] );
            foreach ($explode_attr as $attrKey => $attrValue) {
              $explode_attr_val = explode(':', $attrValue );
              if( $explode_attr_val && count( $explode_attr_val )> 0 ){
                if(isset( $explode_attr_val[0] ) && isset( $explode_attr_val[1 ]))
                  $attributes[ $explode_attr_val[0] ] = $explode_attr_val[1];
              }
            }
          }

          if(!empty($items)){
              foreach($items as $item => $values) {
                  if($values['product_id'] == $productId  && $variationId == $values['variation_id']  ){
                      $quantity = $values['quantity'] + $cOunt;
                      $woocommerce->cart->set_quantity($item,$quantity);
                      $return  = array('status'=>true,'message'=> TPF_variables_json::get_response_message( 'cart','product_added')  , "amount" => $woocommerce->cart->total ) ;
                      $exist = true;
                      break;
                  }
              }
              if(!$exist){
                $woocommerce->cart->add_to_cart( $productId, $cOunt , $variationId , $attributes  );
                $return  = array('status'=>true,'message'=> TPF_variables_json::get_response_message( 'cart','product_added') , "amount" => $woocommerce->cart->total ) ;
              }
          }else{
             $woocommerce->cart->add_to_cart( $productId, $cOunt , $variationId , $attributes  );
          }
        }else{ 
           if(!empty($items)){
              foreach($items as $item => $values) {
                  if($values['product_id'] == $productId){
                      $quantity = $values['quantity'] + $cOunt;
                      $woocommerce->cart->set_quantity($item,$quantity);
                      $return  = array('status'=>true,'message'=> TPF_variables_json::get_response_message( 'cart','product_added') , "amount" => $woocommerce->cart->total ) ;
                      $exist = true;
                      break;
                  }
              }
              if(!$exist){
                     $woocommerce->cart->add_to_cart( $productId,$cOunt );
                     $return  = array('status'=>true,'message'=>TPF_variables_json::get_response_message( 'cart','product_added') , "amount" => $woocommerce->cart->total ) ;
                  }
          }else{

             $woocommerce->cart->add_to_cart( $productId,$cOunt );
          }
        }
       
        $cartTotal  = self::getProductCartTotal($productId ,$variationId ); 
        $return  = array('status'=>true,'message'=> TPF_variables_json::get_response_message( 'cart','product_added') ,"amount" => $cartTotal ) ;

        /*
         * This task need to be run background
         */
        // self::create_button_log( $productId , $cOunt , $userStatus);
        wp_send_json( $return );
        die();
    }

    public static function tpf_get_product_attributes( $productId , $variation_id ){
      $product  = wc_get_product( $productId );
      $variations = $product->get_available_variations();
      $attributes = array();
      foreach ($variations as $key => $value) {
        if( $variation_id == $value['variation_id']){
          $attributes = $value['attributes'];
          break;
        }
      }
      return $attributes;

    }
    public static function get_available_shipping_methods(){
      $shipping_methods = [];
      if(  $packages = WC()->shipping->get_packages() ){       
        foreach ( $packages as $i => $package ) {
          foreach ( $package["rates"] as $key => $value ) {
            $shipping_method["id"] = $value->id;            
            $shipping_method["method"] = $value->method_id;
            $shipping_method["name"] = $value->label;
            $shipping_method["price"] = $value->cost;
            $shipping_methods[] = (object)$shipping_method;
          }
        }
      }
      return $shipping_methods;     
    }
    
    /**
     * @parms : 
     * thisProduct : only fill value when customer buy this product tab
     * count : cart qty
     * userStatus : Tapify userID
     * current_product : current product
     * cart_products : cart_products
     * tapify_life_long_cookie : for guest users.
     * */

    public static function create_button_log( $thisProduct = array() ,$count = false , $userStatus = NULL , $current_product = false ,$cart_products = false , $tapify_life_long_cookie = false , $visitorKey = false ){ 
        try{ 
            if( !class_exists( 'WooCommerce' ) ) return false;
            $meta_data_attr     = [];
            $tapify_cookies     = self::get_tapify_cookies();

            if( $tapify_cookies && isset( $tapify_cookies['status'] ) && $tapify_cookies['status'] == 1 && isset( $tapify_cookies['data']) ){
                $tapifyCookies   = $tapify_cookies['data'];
                /*
                 * Check for user is LOGGED IN OR NOT in Tapify
                 *
                 * */
                if( !$userStatus ) 
                    $userStatus     = isset( $tapifyCookies->tpfUserStatus )?$tapifyCookies->tpfUserStatus:NULL;
                if( isset( $_COOKIE['_tpref'] ) )
                    $meta_data_attr[] = (object)array( 'key' => "tpref" , "value" => $_COOKIE['_tpref'] );
                
                /*
                 * Hold visitor key along with cart object
                 * visitor key Created from button API
                 */
                if( !$visitorKey ) 
                    $visitorKey     = isset( $tapifyCookies->visitorKey )?$tapifyCookies->visitorKey:NULL;
                if( $visitorKey )
                    $meta_data_attr[]= (object)array( 'key' => "tpvk" , "value" => $visitorKey );

                $sessionId          = $collection =  NULL;
                $sessionData        = TPF_cart_widget::tapify_get_session_cookie();
                if( !$sessionData ) $sessionData = array();
                $type = $cookie_hash= false; 
                if(count($sessionData) > 0 && isset( $sessionData[0]) ){
                    $sessionId      = $sessionData[0];
                    $cookie_hash    = $sessionData[3];
                }

                 /*
                 * For checking the cart entry per day wise/ currently not handling in node sever
                 * */
                if( !$sessionId ) 
                    $sessionId = isset($tapifyCookies->tapify_24_hr_cookie )?$tapifyCookies->tapify_24_hr_cookie :false;

                /*
                 * This handles the guest user and the related things
                 * */
                if(!$tapify_life_long_cookie) {
                    if( isset($_SESSION['tapify_life_long_cookie']) && $_SESSION['tapify_life_long_cookie'] !== null ){
                        $tapify_life_long_cookie = $_SESSION['tapify_life_long_cookie'];
                    } 
                }
                if( $current_product > 0 ) $type = "PV";
      
                $free_shipping_needs    = TPF_cart_widget::tapify_free_shipping_cart_notice_zones();
                global $woocommerce, $wp;

                $productArray   = $product  = $cart = $currentProduct =  $carts = array();
                $cart_count     = $cartTotal=  $shippinCost = $taxTotal =  0 ;

                $items          = $woocommerce->cart->get_cart();

                /*
                 * Unique id ( cart key : tapify_cart_cookie_key )
                 * */
                $cart_cookie    = isset($tapifyCookies->tapify_cart_cookie_key )?$tapifyCookies->tapify_cart_cookie_key:false;

                $shipping       = self::tapify_get_shipping_cost();
                if($shipping && isset($shipping[0]['total'])) {
                  $shipping[0]['total'] = number_format( $shipping[0]['total'] ,2 );
                  $shipping     = $shipping[0];
                  $shippinCost  = $shipping['total'];
                }else{
                  $shipping = (object)array();
                }

                if( $thisProduct )  $currentProduct = $thisProduct;

                 /*
                 * Creating cart Object
                 * */
                if(!empty($items) || WC()->cart->get_cart_contents_count() != 0 ){

                    $shippingFromCart = TPF_wc_calculate_shipping::tpf_get_shipping_object() ;
                    if( $shippingFromCart && isset( $shippingFromCart['total'])){
                        $shipping = $shippingFromCart;
                        if(isset( $shipping['taxes'])) unset( $shipping['taxes']);
                        if(isset( $shipping['tax_status'])) unset( $shipping['tax_status']);
                        $shippinCost = $shipping['total'];
                    }

                    foreach($items as $item => $values) {

                        $_product  =  wc_get_product( $values['product_id'] );
                        $image_url = wc_placeholder_img_src();
                        $thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $values['product_id'] ,'full' ));

                        if($thumbnail && isset($thumbnail[0])) $image_url =  $thumbnail[0] ;

                        $product = array( 
                            "url"         => get_permalink( $values['product_id'] ),
                            "name"        => $_product->get_title(),
                            "price"       => number_format($_product->get_price() , 2) ,
                            "currency"    => get_option('woocommerce_currency'),
                            "image"       => $image_url,
                        );
                        
                        if( $values['variation'] && count( $values['variation'] ) > 0 ){
                            $product['formatted_attributes'] = self::get_variation_as_array( $values['variation'] ) ;
                        }

                        $cart_count     = $cart_count + $values['quantity'];
                        $cartTotal      = $cartTotal + $values['line_total'] ;
                        $product        = array_merge($product,$values); 
                        $productArray[] = $product;
                    }

                    $freeShippingExist = TPF_cart_widget::tapify_free_shipping_exist_for_current_address();
                    $carts['freeShippingExist']     = $freeShippingExist;
                    $carts['free_shipping_needs']   = $free_shipping_needs;

                    if( $freeShippingExist ){
                        if( !isset($carts['free_shipping_needs']->amount) )
                          $carts['free_shipping_needs']->freeShipping = true ;
                    }

                    $carts['items']     = $productArray;
                    $carts['taxTotal']  = 0 ;

                    foreach ( WC()->cart->get_tax_totals() as $key => $value) {
                        $cart['cart_contents_total'] = WC()->cart->cart_contents_total;
                        $carts['taxTotal']  = $carts['taxTotal'] + $value->amount ;
                        $taxTotal           = $carts['taxTotal'] ;
                    }
                    $carts['cart_contents_total_with_tax'] = WC()->cart->cart_contents_total + $taxTotal;              

                    if( $woocommerce->cart->total )  $carts['total']      = number_format($woocommerce->cart->total , 2 );
                    else $carts['total']      = number_format($cartTotal + $shippinCost + $taxTotal , 2 ) ;

                    if( $cart_total = WC()->cart->get_total("without_format")  ){
                        $check_no   = floatval($cart_total); 
                        if( is_numeric($check_no) ) $carts['total'] = number_format($check_no , 2 );
                    }

                   /* 
                   * cart total value shown in prioduct and cart pages seperately
                   * need to double chevck the same.
                   * For a temporary fix added the below code
                   */
                    $carts['total']      = number_format($cartTotal + $shippinCost + $taxTotal , 2 ) ;
                    $carts['total']      = WC()->cart->total; //added on coupon implementation to get cart total
                } 

                /*
                 * Tracking the wp user object
                 */
                if( is_user_logged_in() ) {
                    $current_user = wp_get_current_user();
                    $user = array( 
                        "_id"   =>  $current_user->ID , 
                        "name"  => $current_user->display_name ,
                        "email" => $current_user->user_email
                    );
                }else $user = (object)array();

                 /*
                 * Coupon related stuffs
                 * */
                $applied_coupons = [];
                if( WC()->cart->get_coupons() ) {
                    $coupons = WC()->cart->get_coupons();
                  
                    foreach( $coupons as $coupon ){
                        $discount_amount        = 0;
                        if( $coupon->get_discount_type() === 'percent'){
                            $discount_value     = (float)$cartTotal * $coupon->get_amount()/100;
                            $discount_amount    = $discount_amount + $discount_value;

                        }else $discount_amount  = $discount_amount + $coupon->get_amount();
                        $cp = array( 
                            "id"          => $coupon->get_id() , 
                            "coupon_type" => $coupon->get_discount_type() , // percent, fixed_cart and fixed_product. 
                            "amount"      => $coupon->get_amount(),
                            "coupon_code" => $coupon->get_code(),
                            "discount_amount" => WC()->cart->get_coupon_discount_amount( $coupon->get_code() ,true )
                        );
                        array_push( $applied_coupons , (object)$cp );
                    }
                } 

                 $carts[ "applied_coupons" ] =  $applied_coupons;

                /*
                 * tracking the current page type and the collection
                 * */
                if( $cart_products ){
                    $type = "OT";
                    if( is_product_category() ) {
                        $collection = single_cat_title('', false);
                        $type = "CL";
                    }elseif( is_page( 'cart' ) || is_cart() )  $type = "CV";

                    $cart_products = isset($carts['items'])?$carts['items']:(object)array();
                }

                $available_methods  = self::get_available_shipping_methods();
                $storeAccessKey     = get_option('tapify_store_access_key');
                $headers    = [ 
                    'storeAccessKey'   => $storeAccessKey , 'Content-Type'      => 'application/json' ,
                    'externalIp'       => TPF_cart_widget::tapify_get_the_user_ip(),
                    'user-agent'       => $_SERVER['HTTP_USER_AGENT']
                ];

                $args = array(
                    'body'  => json_encode( 
                        array( 
                            "cartKey"       => $cart_cookie ,
                            "store"         => $storeAccessKey,
                            "user"          => $user,
                            "product"       => !empty($currentProduct)?$currentProduct:(object)$currentProduct,
                            "cart"          => $carts,
                            "userStatus"    => $userStatus,
                            "shipping"      => $shipping ,
                            "current_product" => $current_product,
                            "sessionId"     => $sessionId,
                            "life_long_cookie" => $tapify_life_long_cookie,
                            "type"          => $type,
                            "cart_products" => $cart_products,
                            "cookie_hash"   => $cookie_hash,
                            "current_url"   => home_url($wp->request),
                            "collection"    => $collection,
                            "meta_data_attr"=> $meta_data_attr ,
                            "available_methods" => $available_methods,
                            "visitorKey"    => $visitorKey,
                            "requested"     => "woocommerce"
                          )
                        ) ,
                        'blocking'  => true,
                        'headers'   => $headers,
                        'cookies'   => array()
                    );

                $response = wp_remote_post( TPF_NODE_API_URL . 'v1/create/cart' , $args );
                $json_dec = array();

                if( !is_wp_error($response) && isset($response['body'])){
                    $json_dec = json_decode($response['body']);
                    if( $json_dec && isset($json_dec->_id)){
                        return  array('status'=>true, 'message'=> TPF_variables_json::get_response_message( 'cart_collection','success')  , 'data'=> $json_dec ) ;
                    }else{
                        return  array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'cart_collection','failed') ) ;
                    }
                }
                return  array('status'=>false,'message'=> TPF_variables_json::get_response_message( 'cart_collection','failed') ) ;
            }else throw new Exception( "Missing tapify cookies" ,  105 );                     
        }catch(Exception $e) {
            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }


    public static function getProductCartTotal( $current_product_id , $variation_id ){
       
        $in_cart = WC()->cart->find_product_in_cart( $product_cart_id );

       
        foreach(WC()->cart->get_cart() as $item => $values) {

          if( $current_product_id == $values['product_id'] ) {
            if($variation_id && $variation_id != "0" ){
              if($variation_id != $values['variation_id']) continue;
            }

            $price    = $values['line_total'];
            $tax      = $values['line_tax'];
            $price    = $price + $tax;
            $packages = WC()->shipping->get_packages();
            foreach ($packages as $i => $package) {

              $chosen_method = isset(WC()->session->chosen_shipping_methods[$i]) ? WC()->session->chosen_shipping_methods[$i] : '';
              $shippings = array( 
                'method_id' => $package['rates'][$chosen_method]->method_id,
                'method_title' => $package['rates'][$chosen_method]->label,
                'total' => $package['rates'][$chosen_method]->cost
              );
            }
            if($shippings && $shippings['total']) $price = $price + $shippings['total'];
          }
        }
        return $price;
    }


    public static function tapify_create_wc_order($billing , $shipping , $products ,$shipping_lines ){

        TPF_wc_apis::init();
        global $tapifyWcApi;

        // echo "<pre>"; print_r($shipping_lines); print_r($shipping); die();

        $data = [
            'payment_method'    => 'Credit Card',
            'payment_method_title' => 'Credit Card',
            'status'            => 'completed',
            'set_paid'          => true,
            'billing'           => $billing ,
            'shipping'          => $shipping,
            'line_items'        => $products ,
            'shipping_lines'  => $shipping_lines
        ];
        try{

            /*
            * Create order
            * @global object  : tapifyWcApi
            * */ 
            $order = $tapifyWcApi->post('orders', $data) ;
            return array( "status"=>true , "data"=> $order );
        }catch(Exception $e) {

            /*
            * Return error
            * TODO : Refund  from tapify
            *
            * FYI : Shipping details required parameters must satisfy the validations
            * eg :  phone must be integer 
            * */ 
            return array( "status"=>false, "message"=> $e->getMessage() );
        }
    }

    public static function tapify_set_chosen_shipping_method(  $method_rate_id ){
        if( !$method_rate_id ) return false;
        WC()->session->set( 'chosen_shipping_methods', $method_rate_id );
        return true;
    }

    public static function tapify_get_chosen_shipping_method( $data = array() , $country_code ){

      $actualCost = NULL; 
      $chosen_shipping_methods = false;
      if(WC()->session->get('chosen_shipping_methods')) {
        $chosen_shipping_methods = (WC()->session->get('chosen_shipping_methods')[0])?WC()->session->get('chosen_shipping_methods')[0]:false;
      }
      if( is_array($data) && count($data) > 0 ) {
        foreach ($data as $key => $value) {
          if(isset( $value['zone_location_name']) && strtolower($value['zone_location_name']) == 'everywhere'){
            // $defaulId = self::get_shipping_method( $value['shipping_methods'] );
            $defaulCost = self::get_shipping_method( $value['shipping_methods'] , $chosen_shipping_methods );
          }
          if(isset( $value['zone_locations']) && count( $value['zone_locations'] ) > 0 ){
            foreach ( $value['zone_locations'] as $shipKey => $shipValue) {
              if(isset($shipValue->code) && $shipValue->code == $country_code ) {
                // $rate_id = self::get_shipping_method( $value['shipping_methods'] );
                $actualCost = self::get_shipping_method( $value['shipping_methods'] , $chosen_shipping_methods );
              }
            }
          }
        }
      }

      // if(!$rate_id)  $rate_id = ($defaulId)?$defaulId:false ;
      if(!$actualCost)  $actualCost = ($defaulCost)?$defaulCost:NULL ;

      return $actualCost;
    }

    public static function get_shipping_method( $shipping_methods  ,$chosen_shipping_methods) {
      $cost = NULL;

      if( $shipping_methods && count($shipping_methods ) > 0 ){
        foreach( $shipping_methods as $method){
          // $rate_id = $method['rate_id'];
          if(!$chosen_shipping_methods){
              $cost = isset($method['cost'])?$method['cost']:NULL;
              break;
          }else{
            if( $method['rate_id'] ==  $chosen_shipping_methods ){
              $cost = isset($method['cost'])?$method['cost']:NULL;
              break;
            }
          }
          
        }
      }

      return $cost;
    }


    public static function tapify_find_shipping_cost( $new_country_code ){
      ## 1. WC_session: set customer billing and shipping country

      // Get the data
      $customer_session = WC()->session->get( 'customer' );
      // Change some data
      $customer_session['country'] = $new_country_code; // Billing
      $customer_session['shipping_country'] = $new_country_code; // Shipping
      // Set the changed data
      $customer_session = WC()->session->set( 'customer', $customer_session );

      ## 2. WC_Customer: set customer billing and shipping country

      WC()->customer->set_billing_country( $new_country_code );
      WC()->customer->set_shipping_country( $new_country_code );

      // Initializing variable
      $zones = $data = $classes_keys = array();

      // Rest of the World zone
      $zone                                              = new \WC_Shipping_Zone(0);
      $zones[$zone->get_id()]                            = $zone->get_data();
      $zones[$zone->get_id()]['formatted_zone_location'] = $zone->get_formatted_location();
      $zones[$zone->get_id()]['shipping_methods']        = $zone->get_shipping_methods();

      // Merging shipping zones
      $shipping_zones = array_merge( $zones, WC_Shipping_Zones::get_zones() );

      // Shipping Classes
      $shipping           = new \WC_Shipping();
      $shipping_classes   = $shipping->get_shipping_classes();


      // The Shipping Classes for costs in "Flat rate" Shipping Method
      foreach($shipping_classes as $shipping_class) {
          //
          $key_class_cost = 'class_cost_'.$shipping_class->term_id;

          // The shipping classes
          $classes_keys[$shipping_class->term_id] = array(
              'term_id' => $shipping_class->term_id,
              'name' => $shipping_class->name,
              'slug' => $shipping_class->slug,
              'count' => $shipping_class->count,
              'key_cost' => $key_class_cost
          );
      }

      // For 'No class" cost
      $classes_keys[0] = array(
          'term_id' => '',
          'name' =>  'No shipping class',
          'slug' => 'no_class',
          'count' => '',
          'key_cost' => 'no_class_cost'
      );

    foreach ( $shipping_zones as $shipping_zone ) {
        $zone_id = $shipping_zone['id'];
        $zone_name = $zone_id == '0' ? __('Rest of the word', 'woocommerce') : $shipping_zone['zone_name'];
        $zone_locations = $shipping_zone['zone_locations']; // array
        $zone_location_name = $shipping_zone['formatted_zone_location'];

        // Set the data in an array:
        $data[$zone_id]= array(
            'zone_id'               => $zone_id,
            'zone_name'             => $zone_name,
            'zone_location_name'    => $zone_location_name,
            'zone_locations'        => $zone_locations,
            'shipping_methods'      => array()
        );

      foreach ( $shipping_zone['shipping_methods'] as $sm_obj ) {
        // echo "<pre>"; print_r($sm_obj); 
        $method_id   = $sm_obj->id;
        $instance_id = $sm_obj->get_instance_id();
        $enabled = $sm_obj->is_enabled() ? true : 0;
        // Settings specific to each shipping method
        $instance_settings = $sm_obj->instance_settings;
        if( $enabled ){
          $data[$zone_id]['shipping_methods'][$instance_id] = array(
              '$method_id'    => $sm_obj->id,
              'instance_id'   => $instance_id,
              'rate_id'       => $sm_obj->get_rate_id(),
              'default_name'  => $sm_obj->get_method_title(),
              'custom_name'   => $sm_obj->get_title(),
          );

          if( $method_id == 'free_shipping' ){
              $data[$zone_id]['shipping_methods'][$instance_id]['requires'] = isset($instance_settings['requires'])?$instance_settings['requires']:'';
              $data[$zone_id]['shipping_methods'][$instance_id]['min_amount'] = ($instance_settings['min_amount'])?$instance_settings['min_amount']:'';
          }
          if( $method_id == 'flat_rate' || $method_id == 'local_pickup' ){
              $data[$zone_id]['shipping_methods'][$instance_id]['tax_status'] = isset($instance_settings['tax_status'])?$instance_settings['tax_status']:'';
              $data[$zone_id]['shipping_methods'][$instance_id]['cost'] = $sm_obj->cost;
          }
          if( $method_id == 'flat_rate' ){
              $data[$zone_id]['shipping_methods'][$instance_id]['class_costs'] = isset($instance_settings['class_costs'])?$instance_settings['class_costs']:'';
              $data[$zone_id]['shipping_methods'][$instance_id]['calculation_type'] = isset($instance_settings['type'])?$instance_settings['type']:'';
              $classes_keys[0]['cost'] = isset($instance_settings['no_class_cost'])?$instance_settings['no_class_cost']:'';
              foreach( $instance_settings as $key => $setting )
                  if ( strpos( $key, 'class_cost_') !== false ){
                      $class_id = str_replace('class_cost_', '', $key );
                      $classes_keys[$class_id]['cost'] = $setting;
                  }

              $data[$zone_id]['shipping_methods'][$instance_id]['classes_&_costs'] = $classes_keys;
          }
        }
      }
    }

// echo "<pre>"; print_r($data); die();
    return $data; 
  }
  



  
  public static  function get_allowed_countries() {

    global $woocommerce;
    $countries_obj   = new WC_Countries();
    $countries   = $countries_obj->__get('countries');

    if ( 'all' === get_option( 'woocommerce_allowed_countries' ) ) {
      return apply_filters( 'woocommerce_countries_allowed_countries', $countries );
    }

    if ( 'all_except' === get_option( 'woocommerce_allowed_countries' ) ) {
      $except_countries = get_option( 'woocommerce_all_except_countries', array() );

      if ( ! $except_countries ) {
        return $countries;
      } else {
        $all_except_countries = $countries;
        foreach ( $except_countries as $country ) {
          unset( $all_except_countries[ $country ] );
        }
        return apply_filters( 'woocommerce_countries_allowed_countries', $all_except_countries );
      }
    }

    // $countries = array();
    $allowed_countries = array();

    $raw_countries = get_option( 'woocommerce_specific_allowed_countries', array() );
  

    if ( $raw_countries ) {
      foreach ( $raw_countries as $country ) {
        $allowed_countries[ $country ] = $countries[ $country ];
      }
    }

    return apply_filters( 'woocommerce_countries_allowed_countries', $allowed_countries );
  }

  public static function tpf_get_shipping_countries() {
    global $woocommerce;
    $countries_obj   = new WC_Countries();
    $countries   = $countries_obj->__get('countries');

    if ( '' === get_option( 'woocommerce_ship_to_countries' ) ) {
      return self::get_allowed_countries();
    }

    if ( 'all' === get_option( 'woocommerce_ship_to_countries' ) ) {
      return $countries;
    }

    $return_countries = array();

    $raw_countries = get_option( 'woocommerce_specific_ship_to_countries' );
    

    if ( $raw_countries ) {
      foreach ( $raw_countries as $country ) {
        $return_countries[ $country ] = $countries[ $country ];
      }
    }

    return apply_filters( 'woocommerce_countries_shipping_countries', $return_countries );
  }

  /**
    * tapif_add_store_access_key
    *
    * Add Store access key
    */
   
    public static function get_tapify_cookies( $tapify_life_long_cookie =false ){

        $storeAccessKey = get_option('tapify_store_access_key');
        $return         = array(); $json_dec   = array();

        if(!$tapify_life_long_cookie) {
            if( isset($_SESSION['tapify_life_long_cookie']) && $_SESSION['tapify_life_long_cookie'] !== null ){
                $tapify_life_long_cookie = $_SESSION['tapify_life_long_cookie'];
            } 
        }

        $headers    = [ 'storeAccessKey'    => $storeAccessKey , 'Content-Type'      => 'application/json'];
        $args       = [ 'blocking'  => true, 'headers'   => $headers ];
        $response   = wp_remote_get( TPF_NODE_API_URL . 'v1/cookie?life_long_cookie='. $tapify_life_long_cookie, $args );
        
        if( !is_wp_error($response)  && isset($response['body'])){
            $json_dec = json_decode($response['body']);

            if( $json_dec && isset($json_dec->_id)){
                $return =  array("status" => true , "data"=> $json_dec );
            }else $return =  array("status" => false , "message"=> "Tapify cookies missing!" );
        }
        
        return $return;
    }

}




