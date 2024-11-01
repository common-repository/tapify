<?php
/*
 * Theme settings page
 * Connect store : Iframe load from dashborad insted of button-api
 * */

$iframe_url     = TPF_DASHBOARD_URL .'?loaded_from=wp';
$iframe_connected_url     = TPF_DASHBOARD_BASE_URL .'settings';
$states         = array();
$filepath       = 'includes/variables/languages.php' ;
$selectedLan    = get_option('tapify_default_language');

if( !$selectedLan ) $selectedLan= 'EN';

include TPF_ABSPATH . $filepath ;


?>

<!--home-contact-form start-->
     <div class="tpf-wrap">
         <div class="tapify-plugin-settings">
           <header class="tapify-settings-header">
               <h1><?php echo TPF_variables_json::get_response_message( 'settings','title') ; ?></h1>
           </header>
           <div class="tapify-settings-content">
           		<?php if( !$storeStatus || $storeStatus && $storeStatus =="notConnected" ) : ?>
                    <iframe name="store-connect"  id="tapify-connect-store" class="tapify-connect-store" src="<?php echo $iframe_url; ?>" ></iframe>
                <?php 
                    elseif($storeStatus && $storeStatus == "storeConnected" && !isset($_GET['wc']) ): 
                    $redirectUrl = TPF_Ajax_events::tapify_access_wc_permission(); 
                    if($redirectUrl && $redirectUrl['url'] ) :
                        wp_redirect( $redirectUrl['url'] );
                        exit();
                    endif;
                ?>
	            <?php elseif($storeStatus && $storeStatus == "connected" ): ?>
                
                    <iframe name="store-connect"  id="tapify-connect-store" class="tapify-connect-store" src="<?php echo $iframe_connected_url; ?>" ></iframe>
               
	                <!-- <div class="tapify-settings-success"> -->
	                    <!-- <p><span class="dashicons dashicons-yes"></span> 
                            <?php echo TPF_variables_json::get_response_message( 'store_access_key','connected') ; ?>
                        </p> <br> <a stylehref="javascript:void(0)" style="cursor: pointer;" class="reset-tapify-settings">Delete and reconnect</a> -->
	                <!-- </div> -->
	            <?php else: ?>
                     <iframe name="store-connect"  id="tapify-connect-store" class="tapify-connect-store" src="<?php echo $iframe_url; ?>" ></iframe>
                <?php endif; ?>
            </div>
        </div>
	</div>
<!-- ends here -->

<style type="text/css">
    #tapify-connect-store{
        width: 100%;
        min-height: 500px;
    }
	.disabled-class{
		opacity: .5;
   		pointer-events: none;
	} 
	.tpf-wrap{
		padding-top: 50px;
	}
    .tapify-plugin-settings{
        background-color: #fff;
        -webkit-box-shadow: 0 -3px 25px rgba(0, 0, 0, 0.1);
        box-shadow: 0 -3px 25px rgba(0, 0, 0, 0.1);
        -moz-border-radius: 4px;
        -webkit-border-radius: 4px;
        border-radius: 4px;
	padding-top
    }
    .tapify-plugin-settings .tapify-settings-header{
        padding: 10px 20px;
        border-bottom: solid 1px #eee;
    }
    .tapify-plugin-settings .tapify-settings-header h1{
        margin: 0;
        font-size: 16px;
        padding: 0;
        line-height: normal;
    }
    .tapify-plugin-settings .tapify-settings-content{
        padding: 10px 20px 20px;
    }
    .tapify-plugin-settings .store-key-label{
        display: block;
    }
    .tapify-plugin-settings .store-key-input{
        width: 100%;
        max-width: 300px;
    }
    .tapify-plugin-settings .tapify-settings-btn{
        height: 36px;
    }
    .tapify-plugin-settings .form-table th{
        width: 130px;
    }
    .tapify-plugin-settings .tapify-settings-success p{
        display: inline-block;
        padding: 15px 30px 15px 5px;
        border-left: solid 5px #02b875;
        font-weight: 600;
        -webkit-box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.15);
    }
    .tapify-plugin-settings .tapify-settings-success .dashicons{
        font-size: 24px;
        width: auto;
        height: auto;
        line-height: 14px;
        display: inline-block;
        vertical-align: middle;
    }

    #tapify_lang_switch_language{
        width: 150px;
    }



    /*
    initialize rotation key frames,  
    */
    @keyframes kf_spinner {
        to {
            transform: rotate(360deg)
        }
    }
 
    /*
    The page overlay DIV, you can style it as you like, 
    */
    #page-overlay {
        /*
        basic styles
        */
        text-align: center;
        color: #1e73be;
        padding-top: 10px;
        font-size: .7em;
        display: block;
        background-color: #fefefe;
        
        /*
        important to work properly
        */
        width: 100%;
        height: 100%;
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        bottom: 0;
        z-index: 999999999999;/* highest top level layer */
        
        /*
        required for fade-out/fade-in animation effect
        */
        -webkit-transition: opacity 1s ease-in-out;
        -moz-transition: opacity 1s ease-in-out;
        -ms-transition: opacity 1s ease-in-out;
        -o-transition: opacity 1s ease-in-out
    }
    
    /*
    show our loading layer
    */
    #page-overlay.loading {
        opacity: 1;
        visibility: visible
    }
    
    /*
    hide our loading layer
    */
    #page-overlay.loaded,
    #page-overlay>span {
        opacity: 0
    }
    
    /*
    create the animated spinner
    */
    #page-overlay.loading:before {
        /*
        required to work
        */
        content: '';
        box-sizing: border-box;
        position: absolute;
    
        /*
        centering the spinner on the page
        */
        top: 50%;
        left: 50%;
        width: 20px;
        height: 20px;
        margin-top: -10px;
        margin-left: -10px;
        border-radius: 50%;
    
        /*
        create the spinner with css, no image required 🙂
        */
        border-top: 2px solid #1e73be;
        border-right: 2px solid transparent;
    
        /*
        animate the spinner
        */
        animation: kf_spinner .6s linear infinite
    }
        
</style>

<script>
    jQuery(document).ready(function() {	
    	var home_url = "<?php echo get_home_url(); ?>";  
        window.tpf_is_woocommrerce = <?php echo ( class_exists( 'WooCommerce' ) ) ? 1 :0 ; ?>; 	
    });
</script>

