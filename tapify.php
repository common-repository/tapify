<?php
/**
 * Plugin Name: Tapify
 * Description: Tapify is the easiest way to reduce shopping cart abandonment for your site .
 * Version:  1.2.1
 * Author: Tapify Technologies Inc.
 * Author URI: https://www.tapify.io/
 * Text Domain: tapify
 *
 */



if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}


if ( ! defined( 'TPF_PLUGIN_FILE' ) ) {

    define( 'TPF_PLUGIN_FILE', __FILE__ );
    define('TPF_APPNAME' , "Tapify");
    if ( ! defined( 'TPF_NODE_API_URL' ) )  define('TPF_NODE_API_URL', "https://api.tapify.io/" ); 
    if ( ! defined( 'TBF_BUTTON_API' ) )  define('TBF_BUTTON_API' , "https://btn.tapify.io/" );
    if ( ! defined( 'TPF_DASHBOARD_URL' ) )  define('TPF_DASHBOARD_URL' , "https://app.tapify.io/account-login" );
    if ( ! defined( 'TPF_DASHBOARD_BASE_URL' ) )  define('TPF_DASHBOARD_BASE_URL' , "https://app.tapify.io/" );
    if ( ! defined( 'TPF_CALLBACKURL' ) ) define('TPF_CALLBACKURL' , "https://api.tapify.io/v1/wc/tpfxwqvcs"); 

}

// Include the main Tapify class.
if ( ! class_exists( 'Tapify' ) ) {
    include_once dirname( __FILE__ ) . '/includes/class-tapify.php';
}

/**
 * Main instance of Tapify.
 *
 * Returns the main instance of tpf to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return Tapify
 */
function tpf() {
    return tapify::instance();
}

// Global for backwards compatibility.
$GLOBALS['tapify'] = tpf();
