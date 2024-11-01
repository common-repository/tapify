<?php
/**
 * Wc related functions and actions.
 *
 * @package Tapify/Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;


/**
 * TPF_for_bloggers Class.
 */

class TPF_for_bloggers {

	/**
	 * TPF_for_bloggers Constructor.
	 */
	public function __construct() {
		
	}


	/**
     * Returns the price including or excluding tax, based on the 'woocommerce_tax_display_shop' setting.
     * @return float
     */

    public static function tpf_is_price_included_tax( $store ) {
        if( $store && isset( $store->taxSettings )){
            $taxSettings = $store->taxSettings;
            if( isset( $taxSettings->priceIncludedTax ) ){
                if( $taxSettings->priceIncludedTax === 'no' ){
                    return false;
                }else{
                    return true;
                }
            }
        }
    }

    public static function tpf_get_product_price_include_tax( $product , $quantity , $store ) {

    	if( self::tpf_is_price_included_tax( $store )){
    		//minnus
    	}else{
    		

    	}
		if( $store && isset( $store->taxSettings )){
            $taxSettings = $store->taxSettings;
            if( isset( $taxSettings->priceIncludedTax ) ){
                if( $taxSettings->priceIncludedTax === 'no' ){
                    return false;
                }else{
                    return true;
                }
            }
        }
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

    public static function check_product_synced( $id  ) {
        $synced_product = TPF_blogger_v2::get_synced_product( $id );
        if( !$synced_product ){
            $categories = get_the_category( $id );
            foreach ($categories as $key => $tag) {
               $synced_product =  get_term_meta($tag->term_id , 'tpf_synced_product' , true);
               if( $synced_product ) break;
            }
        }
        return $synced_product;
    }


     /**
     * @parms : 
     * thisProduct : only fill value when customer buyy this product tab
     * count : cart qty
     * userStatus : Tapify userID
     * current_product : current product
     * cart_products : cart_products
     * tapify_life_long_cookie : for guest users.
     * */

    public static function create_button_log_bloggers( $thisProduct = array(), $userStatus = NULL , $store = false , $product_type , $tapify_life_long_cookie = false , $visitorKey = false ){ 
        try{ 

          

            
          $requested = "woocommerce";
          if( !$userStatus ) 
            $userStatus  = isset( $_COOKIE['tpfUserStatus'] )?$_COOKIE['tpfUserStatus']:NULL;
          $meta_data_attr = [];
          if( isset( $_COOKIE['_tpref'] ) ){
            $meta_data_attr[] = (object)array( 'key' => "tpref" , "value" => $_COOKIE['_tpref'] );
          }

          if( !$visitorKey ) 
            $visitorKey  = isset( $_COOKIE['tpfVisitorKey'] )?$_COOKIE['tpfVisitorKey']:NULL;
          if( $visitorKey ){
            $meta_data_attr[] = (object)array( 'key' => "tpvk" , "value" => $visitorKey );
          }
          $sessionId = $collection =  NULL;
          $type = $cookie_hash = false; 
          if( !$sessionId ) 
              $sessionId = isset($_COOKIE['tapify_24_hr_cookie'])?$_COOKIE['tapify_24_hr_cookie']:false;

          if(!$tapify_life_long_cookie) {
            if( isset($_SESSION['tapify_life_long_cookie']) && $_SESSION['tapify_life_long_cookie'] !== null ){
              $tapify_life_long_cookie = $_SESSION['tapify_life_long_cookie'];
            } 
          }

          if( $current_product > 0 ) $type = "PV";
    
          $productArray= $product = $cart = $currentProduct =  $carts = $available_methods = array();
          $cart_count = $cartTotal =  $shippinCost = $taxTotal =  0 ;
          $cart_cookie = TPF_Ajax_events::get_unique_id_from_cookie();
          
          $shipping = (object)array();
         
          if( $thisProduct )  {
              $currentProduct = $thisProduct;
              if( isset( $currentProduct->requested ) ) $requested = $currentProduct->requested ;
              if( isset( $currentProduct->product_type ) && $currentProduct->product_type === 'variable' ){
                  if( isset( $currentProduct->variation_id ) ){
                    if( $currentProduct->variation_id == "0" || $currentProduct->variation_id == 0 )
                      throw new Exception( "Please select any Option to countinue!" ,  105 );
                  }else throw new Exception( "Missing variation id!" ,  105 );
                  
              }
          }
          $carts['items']   = $productArray;
          $carts['taxTotal'] = 0 ;

          if( is_user_logged_in() ) {
            $current_user = wp_get_current_user();
            $user = array( 
                  "_id" =>  $current_user->ID , 
                  "name" => $current_user->display_name ,
                  "email" => $current_user->user_email
                );
          }else{
            $user = (object)array();
          }
          $applied_coupons = [];
          $carts[ "applied_coupons" ] =  $applied_coupons;
          if( $store && isset( $store['storeAccessKey'] )){
              $storeAccessKey =  $store['storeAccessKey'];
          }else throw new Exception("Missing store access key!", 1);
          
          if( $product_type && $product_type === "ownProduct" ){
              $type       = "OTHERS";
              $requested  = "others";
          }
          
          $args = array(
                    'body'  => json_encode( 
                            array( 
                              "cartKey"     => $cart_cookie ,
                              "store"       => $storeAccessKey,
                              "user"        => $user,
                              "product"     => !empty($currentProduct)?$currentProduct:(object)$currentProduct,
                              "cart"        => $carts,
                              
                              "userStatus"  => $userStatus,
                              "shipping"    => $shipping ,
                              "current_product" => $current_product,
                              "sessionId"   => $sessionId,
                              "life_long_cookie" => $tapify_life_long_cookie,
                              "type"        => $type,
                              "cart_products" => $cart_products,
                              "cookie_hash" => $cookie_hash,
                              "current_url" => home_url($wp->request),
                              "collection"  => $collection,
                              "meta_data_attr"=> $meta_data_attr ,
                              "available_methods" => $available_methods,
                              "visitorKey"  => $visitorKey,
                              "requested"   => $requested
                            )
                          ) ,
                    'blocking'  => true,
                    'headers'   => array(
                            'storeAccessKey'    => $storeAccessKey ,
                            'Content-Type'      => 'application/json',
                            'externalIp'        => TPF_cart_widget::tapify_get_the_user_ip(),
                             'user-agent'       => $_SERVER['HTTP_USER_AGENT']
                            ),
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
        }catch(Exception $e) {
            wp_send_json( array( "status"=>false, "message"=> $e->getMessage() ) ); die();
        }
    }


}



 
