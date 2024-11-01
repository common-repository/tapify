<?php
/**
 * variable declaration and other stuffs need to configure the plugin.
 *
 * @package Tapify/config
 * @version 1.0.0
 * */


// $var = 'stag';
$var = 'loc';
// $var = 'pro';
if ( ! defined( 'TPF_PLUGIN_FILE' ) ) {

	define( 'TPF_PLUGIN_FILE', __FILE__ );
	if( $var == 'stag') {
	    if ( ! defined( 'TPF_NODE_API_URL' ) )  define('TPF_NODE_API_URL', "https://stagingapi.tapify.io/" ); 
	    if ( ! defined( 'TBF_BUTTON_API' ) )  define('TBF_BUTTON_API' , "https://stagingbtn.tapify.io/" );
	    if ( ! defined( 'TPF_DASHBOARD_URL' ) )  define('TPF_DASHBOARD_URL' , "https://staging.tapify.io/account-login" );
	    define('TPF_CALLBACKURL' , "https://stagingapi.tapify.io/v1/wc/tpfxwqvcs"); 
	}elseif( $var == 'pro' ){
		if ( ! defined( 'TPF_NODE_API_URL' ) )  define('TPF_NODE_API_URL', "https://api.tapify.io/" ); 
	    if ( ! defined( 'TBF_BUTTON_API' ) )  define('TBF_BUTTON_API' , "https://btn.tapify.io/" );
	    if ( ! defined( 'TPF_DASHBOARD_URL' ) )  define('TPF_DASHBOARD_URL' , "https://app.tapify.io/account-login" );
	    define('TPF_CALLBACKURL' , "https://api.tapify.io/v1/wc/tpfxwqvcs"); 
	}else{
		if ( ! defined( 'TPF_NODE_API_URL' ) )  define('TPF_NODE_API_URL', "http://192.168.2.69:4000/" ); 
	    if ( ! defined( 'TBF_BUTTON_API' ) )  define('TBF_BUTTON_API' , "http://localhost:5000" );
	    if ( ! defined( 'TPF_DASHBOARD_URL' ) )  define('TPF_DASHBOARD_URL' , "http://localhost:5000/account-login" );

		// if ( ! defined( 'TPF_NODE_API_URL' ) )  define('TPF_NODE_API_URL', "https://1a3b96b0.ngrok.io/" ); 
	 //    if ( ! defined( 'TBF_BUTTON_API' ) )  define('TBF_BUTTON_API' , "https://25829384.ngrok.io" );
	 //    if ( ! defined( 'TPF_DASHBOARD_URL' ) )  define('TPF_DASHBOARD_URL' , "http://localhost:5000/account-login" );
	}

    define('TPF_APPNAME' , "Tapify");
    
}


if ( ! defined( 'TPF_PLUGIN_FILE' ) ) {

	// define( 'TPF_PLUGIN_FILE', __FILE__ );
 //    if ( ! defined( 'TPF_NODE_API_URL' ) )  define('TPF_NODE_API_URL', "https://api.tapify.io/" ); 
 //    if ( ! defined( 'TBF_BUTTON_API' ) )  define('TBF_BUTTON_API' , "https://btn.tapify.io/" );
    // if ( ! defined( 'TPF_DASHBOARD_URL' ) )  define('TPF_DASHBOARD_URL' , "https://app.tapify.io/account-login" );
	

 //    define('TPF_APPNAME' , "Tapify");
 //    define('TPF_CALLBACKURL' , "https://api.tapify.io/v1/wc/tpfxwqvcs"); 
}

// define( 'TPF_PLUGIN_FILE', __FILE__ );
// if ( ! defined( 'TPF_NODE_API_URL' ) )  define('TPF_NODE_API_URL', "http://192.168.2.69:4000/" ); 
// if ( ! defined( 'TBF_BUTTON_API' ) )  define('TBF_BUTTON_API' , "http://localhost:5000" );
// if ( ! defined( 'TPF_DASHBOARD_URL' ) )  define('TPF_DASHBOARD_URL' , "http://localhost:3000/account-login" );

define( 'TPF_PLUGIN_FILE', __FILE__ );
define('TPF_APPNAME' , "Tapify");

if ( ! defined( 'TPF_NODE_API_URL' ) )  define('TPF_NODE_API_URL', "https://stagingapi.tapify.io/" ); 
if ( ! defined( 'TBF_BUTTON_API' ) )  define('TBF_BUTTON_API' , "https://stagingbtn.tapify.io/" );
if ( ! defined( 'TPF_DASHBOARD_URL' ) )  define('TPF_DASHBOARD_URL' , "https://staging.tapify.io/account-login" );
	    define('TPF_CALLBACKURL' , "https://stagingapi.tapify.io/v1/wc/tpfxwqvcs"); 
?>