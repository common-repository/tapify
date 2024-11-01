<?php
/**
 * Placing the iframe code next to 'add to cart' button.
 *
 * @package Tapify/Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
require __DIR__ . '/../vendor/autoload.php';

use Automattic\WooCommerce\Client;

/**
 * TPF_Quickpay_button Class.
 */
class TPF_Quickpay_button {

	/**
	 * Hook in tabs.
	 */
	public static function init() {

	}

	public static  function get_shipping_cost(){
		global $woocommerce;
	  		$country_code  		= $woocommerce->customer->get_shipping_country();
		  	$shippingData 		= TPF_Ajax_events::tapify_find_shipping_cost( $country_code );
		  	$shipping_cost 		= TPF_Ajax_events::tapify_get_chosen_shipping_method( $shippingData , $country_code );
		  	return $shipping_cost;

  	}

  	
	public static function add_content_after_addtocart() {
		global $woocommerce;
		TPF_Ajax_events::get_tapify_cookies();
		$current_product_id = NULL; $attributes = NULL;
			/*
			 * Initailize variables
			 * 
			 * **/
			global $product , $post;
			if( $product ) $current_product_id = $product->get_id();

		    
		    $price 				= NULL;
		    $cartClass  		= NULL;
		    $currency 			= get_option('woocommerce_currency');
			$store_access_key 	= get_option('tapify_store_access_key');
			$get_current_user 	= get_current_user_id();
			$cartQuantity 		= 1 ;
			$variation_id 		= 0;
			$show_as_button   	= 'show_as_round';
			$store_status 		= TPF_Ajax_events::isStoreActive();
			$is_synced 			= TPF_for_bloggers::check_product_synced( $post->ID );


			if ( class_exists( 'WooCommerce' ) ) {
				$productObj			= wc_get_product( $current_product_id );
			
			// print_r( WC()->session->get('chosen_shipping_methods') ); 
			// WC()->session->set('chosen_shipping_methods', array( 'purolator_shipping' ) );
			// print_r( $productObj->get_shipping_class_id() ); 
			// print_r( $productObj->get_tax_class() ); 
			// print_r( $productObj->get_tax_status() ); die("pp");
			if( !$store_status) return;

			if( is_page( 'cart' ) || is_cart() || is_product() )  $show_as_button = 'show_as_button';
			
	    	if ( is_product()  ) {

		  		TPF_cart_widget::tapify_update_log_collection_with_product( $current_product_id );

				/*
				 * Chcek if variable product, Only show price afther they select a variation.
	    		 * If not exist in cart, just show the corresponding product price with shipping cost
	    		 * If variable product,keep the price as null
	    		 *
	    		 * **/
				if( $productObj->is_type( 'variable' ) ){
					/*
					 * Check if, add to cart form submit
					 *
					 * */ 
					if(!empty( $_POST ) && isset( $_POST['variation_id'])){

						$variation  	= new WC_Product_Variation( $_POST['variation_id'] );
						foreach ($_POST as $post_key => $post_value) {
							if(substr( $post_key, 0, 9 ) === "attribute")
								$attributes .= $post_key .':'. $post_value .' ,';
						}


						$cartQuantity 	= $_POST['quantity'];

						$theProduct = TPF_cart_widget::tapify_get_product_object( $current_product_id , $_POST['variation_id'] ,$cartQuantity ,$attributes , array());

						if( $theProduct ){
							$price 			= $theProduct['total'];
							$cartQuantity 	= $_POST['quantity'];
							$price 			= $price * $cartQuantity;
						}

					}
	    		}else{

					if(!empty( $_POST ) && isset( $_POST['quantity'])){
						$cartQuantity = $_POST['quantity'];
					}

					$theProduct = TPF_cart_widget::tapify_get_product_object( $current_product_id , '0' ,$cartQuantity , false , $formattedAttributes = array());
					if( $theProduct ){
						$price 			= $theProduct['total'];
						$price 			= $price * $cartQuantity;
					}
	    		}
	    	}else{  
	    		/*
		  		 * Chcek if cart page and add corresponding class to iframe
		  		 * Calculate cart total
		  		 * 
		  		 * **/
		  		$cartClass = "isCart";
		  		if( $woocommerce->cart->total ) $price = $woocommerce->cart->total ;
		  		TPF_cart_widget::tapify_update_log_collection_with_cart( $current_product_id );
		  		if( $woocommerce->cart->total ) $price = $woocommerce->cart->total ;
		  		// if ( WC()->cart->get_cart_contents_count() == 0 )  return ;

	    	}


		  	/* 
		  	 * Custom hook created 
		  	 * for calculating setting up the wc session based on the tapify user address
		  	 * uncomment it after the functionality is over
		  	 * Aug:20
		  	 *
		  	 * **/
	  		do_action( 'tapify_cart_logs', array()  ); 
	 
		    ?>


		    <script type="text/javascript">
		    
		    	jQuery( document ).ready(function($) {
		    		var tpfStoreAccessKey = false, tpfUserId = false ;
			    	jQuery('.cart .qty').bind('keyup change click', function (e) {
					    if (! $(this).data("previousValue") ||   $(this).data("previousValue") != $(this).val() ) {
					        $(this).data("previousValue", $(this).val());
							$('#button-api-iframe').attr('data-pr-qty' , $(this).val() ) ;
							tpfQuantityUpdated();

				   		}	
					});
				});
				var tapifyButtonShape = function() {
					var tpfButtonShape =  <?php echo (is_page( 'cart' ) || is_cart() || is_product())?"'show_as_button'":"'show_as_round'" ?>;
					tapifyPostMessage({"tpfButtonShape":true, "data": tpfButtonShape  });
				}

			    
							
		    </script>

	    <?php } ?>

	    	<script type="text/javascript">
	    		window.tpf_is_woocommrerce 	= <?php echo ( class_exists( 'WooCommerce' ) ) ? 1 :2 ; ?>;
	    		window.tpf_store_access_key = '<?php echo get_option('tapify_store_access_key'); ?>';
	    		window.tpf_store_currency 	= '<?php echo get_option('woocommerce_currency'); ?>';
	    		window.tpf_post_id 		 			= '<?php echo $post->ID; ?>';
	    		window.tpf_is_synced 				= '<?php echo $is_synced; ?>';
	    		window.tpf_widgetStatus			= '<?php echo isset( $store_status->features ) && isset( $store_status->features->widget ) && $store_status->features->widget === true ? 1: 0; ?>';
	    	</script>

			<style type="text/css">
				.tpf-iframe {
					position: fixed;
					right: 0;
					bottom: 0;
					z-index: 9999999999;
					border: none;
					margin: 0;  
					max-width: 550px; 
					width: 300px; /*same value in custom.js under close status*/
					height: 120px;  /*same value in custom.js under close status*/
				}
				#wpadminbar { 
					z-index: 0;
				}
				@media screen and (max-width: 767px){
					.tpf-iframe {
						max-width: 100%; 
					}
					body.widget-open {
					    overflow: hidden;
					    position:fixed;
					    width: 100%;
					    height: 100%;
					    left: 0;
					    top: 0;
					}
				}
		    </style>


		    <?php

				/* 
				 * iframe code
				 */
				
		         echo '<iframe name="frame1" style="display:none;" data-variation = "'. $variation_id .'" data-pr-id ="'.$current_product_id.'"  data-attributes= "'. $attributes .'" data-pr-qty=" '. $cartQuantity .'"  id="button-api-iframe" class="tpf-iframe '. $cartClass . ' '. $show_as_button .'" src="'. TBF_BUTTON_API . '" ></iframe>'; 

	}

}






