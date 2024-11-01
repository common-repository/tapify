<?php
/**
 * Wc related functions and actions.
 *
 * @package Tapify/Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;
define( 'AUTH_CUSTOM_NAMESPACE', 'tpf/v1' );

/**
 * TPF_custom_roots Class.
 */

class TPF_custom_roots {

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
	 * TPF_custom_roots Constructor.
	 */
	public function __construct() {
		$this->register_routes();
	}

	public static function register_routes() {
		register_rest_route( AUTH_CUSTOM_NAMESPACE , 'sync-products', array(
		    'methods' => 'POST',
		    'callback' => array( 'TPF_custom_roots' , 'sync_products' )
	  	) );

	  	register_rest_route( AUTH_CUSTOM_NAMESPACE , 'unsync-products', array(
		    'methods' => 'POST',
		    'callback' => array( 'TPF_custom_roots' , 'unsync_products' )
	  	) );

	  	register_rest_route( AUTH_CUSTOM_NAMESPACE , 'get-post', array(
		    'methods' => 'GET',
		    'callback' => array( 'TPF_custom_roots' , 'get_post_by_id' )
	  	) );

	  	register_rest_route( AUTH_CUSTOM_NAMESPACE , 'get-all-posts', array(
		    'methods' => 'GET',
		    'callback' => array( 'TPF_custom_roots' , 'get_all_posts_pages' )
	  	) );
	}

	public  static function sync_products( WP_REST_Request $request ) {
		global $wpdb, $wp_hasher;
	    $postId  	= $request->get_param('postId');
	    $productId  = $request->get_param('productId');
	    $pageType  	= $request->get_param('pageType');
	    if( !$postId ) return array( "status"=> false , "message" => "Missing required param postId");
	    if( !$productId ) return array( "status"=> false , "message" => "Missing required param productId");
	    if( !$pageType ) return array( "status"=> false , "message" => "Missing required param pageType");
	    if( $pageType === "post" || $pageType === "page" )
	    	update_post_meta( $postId, 'tpf_synced_product', $productId );
	    else
	    	update_term_meta( $postId , 'tpf_synced_product' , $productId);

		return array( "status"=> true , "message" => "sucessfully synced the product!!!");
	}

	public  static function unsync_products( WP_REST_Request $request ) {
		global $wpdb, $wp_hasher;
	    $postId  	= $request->get_param('postId');
	    $pageType  	= $request->get_param('pageType');
	    if( !$postId ) return array( "status"=> false , "message" => "Missing required param postId");
	    if( !$pageType ) return array( "status"=> false , "message" => "Missing required param pageType");
	    if( $pageType === "post" )
	    	update_post_meta( $postId, 'tpf_synced_product', null );
	    else
	    	update_term_meta( $postId , 'tpf_synced_product' , null);

		return array( "status"=> true , "message" => "sucessfully un-synced the products!");
	}

	public  static function get_post_by_id( WP_REST_Request $request ) {
		global $wpdb, $wp_hasher;
	    $postId  	= $request->get_param('postId');
	    if( !$postId ) return array( "status"=> false , "message" => "Missing required param postId");
	   	$postData = get_post( $postId );

		return array( "status"=> true , "message" => "success" , "data"=> $postData );
	}

	public  static function get_all_posts_pages( WP_REST_Request $request ) {
		global $wpdb, $wp_hasher;
	    $args = array(
		    'post_type'    => array( 'page', 'post' ),
		    'orderby' => 'date',
    		'order' => 'DESC',
		    'posts_per_page'   => -1
		);
		$query = new WP_Query ( $args );
		if ( $query->have_posts() ) {
			return array( "status"=> true , "message" => "success" , "data"=> $query->posts );
		}else return array( "status"=> true , "message" => "success" , "data"=> [] );
	}
}