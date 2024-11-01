<?php
/**
 * Tapify setup
 *
 * Tapify/includes/class-tapify
 * @since   1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Main Tapify Class.
 *

 */
 class Tapify {

 	public $version = '1.2.1';


 	/**
	 * The single instance of the class.
	 *
	 */
	protected static $_instance = null;



 	/**
	 * Main Tapify Instance.
	 *
	 * Ensures only one instance of Tapify is loaded or can be loaded.
	 *
	 * @since 2.1
	 * @static
	 * @see tpf()
	 * @return Tapify - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}


	/**
	 * Tapify Constructor.
	 */
	public function __construct() {
		$this->define_constants();
		$this->includes();
		$this->init_hooks();

		//do_action( 'woocommerce_loaded' );
	}

	/**
	 * Define tpf Constants.
	 */
	private function define_constants() {
		$upload_dir = wp_upload_dir( null, false );

		$this->define( 'TPF_ABSPATH', dirname( TPF_PLUGIN_FILE ) . '/' );
		$this->define( 'TPF_PLUGIN_BASENAME', plugin_basename( TPF_PLUGIN_FILE ) );
		$this->define( 'TPF_VERSION', $this->version );
	}


	/**
	 * Define constant if not already set.
	 *
	 * @param string      $name  Constant name.
	 * @param string|bool $value Constant value.
	 */
	private function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Include required core files used in admin and on the frontend.
	 */
	public function includes() {
		/**
		 * Class autoloader.
		 */
		include_once TPF_ABSPATH . 'includes/class-tpf-install.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-uninstall.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-handle-variables.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-get-shipping-tax.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-ajax-events.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-wc-apis.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-wc.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-add-quickpay-button.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-cart-widget.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-wc-calculate-shipping.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-add-admin-alert.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-order-complete.php';
		include_once TPF_ABSPATH . 'includes/class-tapify-add-metabox-b2.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-bloggers.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-custom-root.php';
		include_once TPF_ABSPATH . 'includes/class-tpf-shortcode.php';
		
	}

	/**
	 * Hook into actions and filters.
	 *
	 * @since 2.3
	 */
	private function init_hooks() {  
		/*
	     * register activation hook
	     * Chcek woocomerce installed
	     * Check CURL enabled
	     * */  
		register_activation_hook( TPF_PLUGIN_FILE, array( 'TPF_Install', 'install' ) );

		register_deactivation_hook( TPF_PLUGIN_FILE, array( 'TPF_Uninstall', 'unistall' )  );

		add_action( 'activated_plugin', array( 'TPF_Install', 'activated' ) );

		add_action( 'after_setup_theme', array( $this, 'setup_environment' ) );

		/* 
		 * Place iframe code
		 *
		 * */
		// add_action( 'woocommerce_after_add_to_cart_button',array( 'TPF_quickpay_button', 'add_content_after_addtocart' )  );

		// add_action( 'woocommerce_after_cart_totals', array( 'TPF_quickpay_button', 'add_content_after_addtocart' )  );

		add_action('wp_footer' ,array( 'TPF_quickpay_button', 'add_content_after_addtocart' )  );

		add_action('wp_footer' ,array( &$this, 'add_tpf_common_scripts' )  );

		/* 
		 * Enques  scripts - admin 
		 * */  

		add_action('wp_enqueue_scripts', array(&$this, 'tapify_enqueue_scripts'), 99);

		/* 
		 * Enques  scripts - Frontend 
		 *
		 * */
		
		add_action('admin_enqueue_scripts', array(&$this, 'tapify_admin_enqueue_scripts'), 99);

		/* 
		 * Create plugin settings page
		 *
		 * */
		
		add_action( 'admin_menu',  array(&$this, 'tapify_register_plugin_settings_page') );

		/*
		 * Sync cart collection to mongo db
		 *
		 * */

		/*
		 * Wooocmerce available hooks when cart updates, 
		 * 
		 * woocommerce_cart_emptied , woocommerce_add_to_cart ,woocommerce_after_calculate_totals
		 * woocommerce_cart_loaded_from_session , woocommerce_removed_coupon , shutdown 
		 * woocommerce_cart_updated
		 *
		 * */
			  
		add_action( 'woocommerce_check_cart_items', array( 'TPF_cart_widget', 'tapify_update_cart_log' ) );
 		add_action( 'woocommerce_after_calculate_totals',  array( 'TPF_cart_widget', 'tapify_update_cart_log' ) ) ;
 		add_action( 'woocommerce_before_main_content',  array( 'TPF_cart_widget', 'tapify_before_main_content' ) ) ;

		// add_filter( 'woocommerce_package_rates', array(&$this, 'tpf_hide_shipping_when_free_is_available'), 100 );	

 		/* ends here */

 		add_action( 'init', array(&$this , 'tapify_init_hook' ));

 		add_action( 'init', array( 'TPF_shortcode', 'init' ) );

 		/* admin notice - error/success */
 		add_action( 'admin_notices', array( 'TPF_add_admin_alerts' , 'update_admin_about_new_version' ) );
		// add_action( 'admin_notices', array(&$this , 'my_update_notice' ) );


		add_action( 'woocommerce_order_status_completed', array( 'TPF_order_completed' , 'tapify_order_created_hook' ) );

		/**
		 * @snippet       How to Apply a Coupon Programmatically - WooCommerce
		 * @compatible    WC 3.5.4
		 */
		// add_action( 'woocommerce_before_cart',  array(&$this , 'bbloomer_apply_coupon' ) );

		add_action('add_meta_boxes',  array( 'TPF_blogger_v2', 'init'));

		add_action( 'rest_api_init', array( 'TPF_custom_roots' , 'instance' )); 

		add_action ( 'edit_category_form_fields', array( 'TPF_blogger_v2', 'add_sync_button_catgories'));

		add_action( 'wp_trash_post', array(&$this , 'remove_product' ));
		add_action( 'wp_delete_post', array(&$this , 'remove_product' ));
		add_action( 'woocommerce_update_product', array(&$this, 'tapify_create_or_update_product' ), 10, 1);
		add_action( 'save_post_product', array(&$this, 'tapify_create_or_update_product' ), 10, 1);
 		
	}

	/**
	 * Create cookie 
	 */
	public function add_tpf_common_scripts( $post_id ){ ?>
		<script type="text/javascript">
				if ((typeof tapify) === 'undefined') {  tapify = {}; }

				tapify.openPanel = function(XMLHttpRequest, textStatus) {
					console.log('Clicked::');
					var messageObj = { "tpfOpenWidget":true, "data":{} };
					if( typeof tapifyPostMessage ===  'function' ) {
						tapifyPostMessage( messageObj ); 
					}else{
						if( document.getElementById('button-api-iframes') ){
							document.getElementById('button-api-iframe').contentWindow.postMessage( messageObj, '*');
						}
					}
				}

				tapify.closePanel = function(XMLHttpRequest, textStatus) {
					console.log('Clicked::');
					var messageObj = { "tpfCloseWidget":true, "data":{} };
					if( typeof tapifyPostMessage ===  'function' ) {
						tapifyPostMessage( messageObj ); 
					}else{
						if( document.getElementById('button-api-iframes') ){
							document.getElementById('button-api-iframe').contentWindow.postMessage( messageObj, '*');
						}
					}
				}
		</script>
		<?php
	}


	/**
	 * Create cookie 
	 */
	public function tapify_create_or_update_product( $post_id ){
		$storeAccessKey =get_option('tapify_store_access_key');
		try{
			$args = array(
	                    'body'     => json_encode(  
	                        array(  
	                            "id"                    => $post_id ,
	                            "storeAccessKey"        => $storeAccessKey, 
	                        )
	                    ) ,
	                    'blocking' => true,
	                    'headers'  => array( 
	                        'storeAccessKey'  => $storeAccessKey, 
	                        'Content-Type'    => 'application/json' ),
	            );
	           
            $response   = wp_remote_post( TPF_NODE_API_URL . 'v1/wc/product-updated' , $args );
            $json_dec   = array();
            $return     =  array('status'=>false,'message'=> 'No products synced') ;
            
            if( !is_wp_error($response)  && isset($response['body'])){
                $json_dec = json_decode($response['body']);
            }
            return $return;
        } catch ( Exception $e ) {
            wp_send_json( array( "status"=>false, "message"=> $e->getMessage() ) ); die();
        }
	}

	/**
	 * Create cookie 
	 */
	public function remove_product( $post_id ){ 
		$storeAccessKey =get_option('tapify_store_access_key');
		try{
			$args = array(
	                    'body'     => json_encode(  
	                        array(  
	                            "id"                    => $post_id ,
	                            "storeAccessKey"        => $storeAccessKey, 
	                        )
	                    ) ,
	                    'blocking' => true,
	                    'headers'  => array( 
	                        'storeAccessKey'  => $storeAccessKey, 
	                        'Content-Type'    => 'application/json' ),
	            );
	           
            $response   = wp_remote_post( TPF_NODE_API_URL . 'v1/wc/product-deleted' , $args );
            $json_dec   = array();
            $return     =  array('status'=>false,'message'=> 'No products synced') ;
            
            if( !is_wp_error($response)  && isset($response['body'])){
                $json_dec = json_decode($response['body']);
                // print_r( $json_dec ); die("p");
            }
            return $return;
        } catch ( Exception $e ) {
            wp_send_json( array( "status"=>false, "message"=> $e->getMessage() ) ); die();
        }
	}


	/**
	 * Hide shipping rates when free shipping is available.
	 * Updated to support WooCommerce 2.6 Shipping Zones.
	 *
	 * @param array $rates Array of rates found for the package.
	 * @return array
	 */
	public static function tpf_hide_shipping_when_free_is_available( $rates ) {

		$free = array();
		foreach ( $rates as $rate_id => $rate ) {
			if ( 'free_shipping' === $rate->method_id ) {
				$free[ $rate_id ] = $rate;
				break;
			}
		}

		return ! empty( $free ) ? $free : $rates;
	}

	/**
	 * Create cookie 
	 */
	public function tapify_init_hook(){ 
		TPF_Ajax_events::get_unique_id_from_cookie( true );
		$life_long = isset($_COOKIE['tapify_life_long_cookie'])?$_COOKIE['tapify_life_long_cookie']:false;

		if (!session_id()) session_start();

		if(!$life_long): 
			$life_long =  md5( time() . rand() ) ;
			setcookie('tapify_life_long_cookie', $life_long , time() + 7 * 24 * 60 * 60 ,  '/' );
		    $_SESSION['tapify_life_long_cookie'] = $life_long;
		endif;
		if( !isset($_SESSION['tapify_life_long_cookie']) ){
			$_SESSION['tapify_life_long_cookie'] = $life_long;
		}		
	}

	/**
	 * Register a custom menu page.
	 */
	public function tapify_register_plugin_settings_page(){

	    add_menu_page( 
		__( 'Theme Menu Title', 'tapify' ),
		'Tapify',
		'manage_options',
		'tapify_settings',
		 array(&$this, 'tapify_plugin_settings'),
		 plugin_dir_url( __FILE__ ) . '../assets/tapify.png',
		6
	    ); 
	}
	

	public function tapify_plugin_settings() {
		$storeStatus 	= false;
		$keyStatus 	 	= false;

		if($options 	= get_option('tapify_store_access_key')):
			$storeData 	= TPF_Ajax_events::validate_store_access_key( $options);
			$keyStatus 	= true;
			if($storeData && $storeData['status']) :
				if($storeData['data'] ) :
					$storeStatus = isset($storeData['data']->storeStatus)?$storeData['data']->storeStatus:'notConnected';
					/*
					 * Updated the getStoreDeatils api
					 * removed the consumerKey and ConsumerSecret from store object ,for security
					 * Updated on NOV:07:18
					 *
						if( isset($storeData['data']->consumerSecret) && isset($storeData['data']->consumerKey) ) :
							if( $storeData['data']->consumerSecret != NULL && $storeData['data']->consumerKey != NULL ) :
								$storeStatus = $storeData['data']->storeStatus;
								update_option('tapify_store_connection_status' , $storeData['data']->storeStatus );
							endif;
						endif;
					 *
					 */
				endif;
			endif;
		endif;

		include( dirname(__FILE__) . '/../templates/admin/tapify-settings.php');    
	    
	}


	public static function setup_environment(){ 
		
		add_action( 'admin_init', array( 'TPF_Ajax_events', 'tapify_add_ajax_events' ));

        /*
         * Add the same if needed (reserved for future).
         *
         * add_theme_support( 'tapify' );
         * */
	}

	/**
     * add theme styles and scripts
     */
    public function tapify_enqueue_scripts() {
        /*
        * Load JS
        */
       	wp_enqueue_script('jquery'); 
        wp_enqueue_script( 'tapify-custom-js', plugin_dir_url( __FILE__ ).'../assets/js/tapify-main.js?' . rand() );

        wp_enqueue_script( 'tapify-cart-widget-js', plugin_dir_url( __FILE__ ).'../assets/js/tapify-cart-widget.js?'  . rand() );
        /*
        * Localize JS so we can do AJAX calls
        */
        wp_localize_script( 'tapify-custom-js', 'tapifyajax', array( 'ajaxurl' => admin_url('admin-ajax.php'), 'security' => wp_create_nonce('tapify-security-noncey') , 'home_url' => get_home_url() ) );
     
    }

    /**
     * add theme styles and scripts
     */
    public function tapify_admin_enqueue_scripts() {
        /*
        * Load JS
        */
       
        wp_enqueue_script( 'tapify-common-js', plugin_dir_url( __FILE__ ).'../assets/js/common.js?'  . rand()  );
        /*
        * Localize JS so we can do AJAX calls
        */
        wp_localize_script( 'tapify-common-js', 'tapifyajaxAdmin', array( 'ajaxurl' => admin_url('admin-ajax.php'), 'security' => wp_create_nonce('tapify-security-noncey') , 'home_url' => get_home_url() ) );

	}
	

 }
