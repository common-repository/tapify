<?php
/**
 * Add button for syncing products .
 *
 * @package Tapify/Classes
 * @version 1.1.2
 */

defined( 'ABSPATH' ) || exit;

/**
 * TPF_blogger_v2 Class.
 */
class TPF_blogger_v2 {

    /**
     * TPF_blogger_v2 Constructor.
     */
    public function __construct() {
        
    }

    public static function init() { 
        $post_types = array ( 'post', 'page', 'event' );
        add_meta_box( 
            'tpf-sync-product', __( 'Tapify', 'textdomain' ), 
            array( 'TPF_blogger_v2', 'tpf_sync_product_callback'), 
            $post_types ,'side' ,'high' , null
        );

         add_meta_box( 
            'tpf-sync-product-normal', __( 'Tapify: Connect a product to your post', 'textdomain' ), 
            array( 'TPF_blogger_v2', 'tpf_sync_product_normal_callback'), 
            $post_types ,'normal' ,'high' , null
        );
    }
    
    public static function tpf_sync_product_callback(){
        global $post; 
        $synced_product =  self::get_synced_product( $post->ID );
        $store_status   = TPF_Ajax_events::isStoreActive();
        $storeConnected = false;
       if( isset( $store_status->status ) && $store_status->status === 'active' && isset( $store_status->storeStatus ) && $store_status->storeStatus === 'connected'){
            $storeConnected = true;
       }
          
        ?>
            <center> 
                <?php if( !$storeConnected ) { ?>
                    <button type="button" id='tpf-redirect-to-settings' class="components-button  is-button  is-primary is-large">Click to Sync your product</button>
                <?php }else{ ?>
                    <?php if( $synced_product ) { ?>
                        <button type="button" id="tpf-manage-product-btn"   class="components-button  is-button  is-primary is-large">Manage your synced product</button>
                    <?php }else { ?>
                        <button type="button"  id="tpf-sync-product-btn" class="components-button  is-button  is-primary is-large">Click to Sync your product</button>
                    <?php } ?>
                <?php } ?>
            </center>
            <script>
                jQuery("#tpf-sync-product-btn").click( function(){
                    var dashboad_url    = '<?php echo TPF_DASHBOARD_BASE_URL ; ?>';
                    var tpf_post_id     = '<?php echo $post->ID  ; ?>';
                    jQuery.ajax({
                        type: 'POST',
                        url: tapifyajaxAdmin.ajaxurl,
                        data : { action:"get_post_data" , postId:tpf_post_id },
                        success: function(response) {
                            if( response.status ) {
                                var data = response.data;
                                var tpf_post_url    = data.permalink;
                                var tpf_post_title  = data.post_title;
                                window.open( dashboad_url + 'sync-products?post_id=' + tpf_post_id + '&post_url=' +tpf_post_url+ '&post_title='+tpf_post_title +'&type=post', '_blank' );
                            }
                        }
                    });
                    
                })
                jQuery("#tpf-manage-product-btn").click( function(){
                    var dashboad_url    = '<?php echo TPF_DASHBOARD_BASE_URL ; ?>';
                    var tpf_post_id     = '<?php echo $post->ID  ; ?>';
                    window.open( dashboad_url + 'bloggers/synced-products' );
                })
                jQuery("#tpf-redirect-to-settings").click( function(){
                    var dashboad_url    = '<?php echo admin_url( 'admin.php?page=tapify_settings' ); ?>';
                    window.open( dashboad_url );
                });
            </script>
        <?php
    }
    public static function get_synced_product( $postId ){ 
        $synced_products = get_post_meta( $postId, 'tpf_synced_product' );
        $synced_product  = false;
        if( $synced_products && $synced_products !== null ){
            if( is_array( $synced_products ) && count( $synced_products ) > 0 ){
                $synced_product = $synced_products[0];
            }else if( is_string( $synced_products) ){
                $synced_product = $synced_products;
            }
        }
        if( TPF_Ajax_events::checkProductSynced( $synced_product ) ) {
            return $synced_product;
        }else return false;
       
    }

    public static function add_sync_button_catgories( $tag ) { 
        $synced_product = get_term_meta($tag->term_id , 'tpf_synced_product' , true);
        if( !TPF_Ajax_events::checkProductSynced( $synced_product ) ) {
            $synced_product = false;
        }
       
       ?>

        <?php if( $synced_product && $synced_product !== null ) { ?>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="cat_Image_url"><?php _e('Click to unsync your product'); ?></label></th>
                <td>
                    <button type="button" id="tpf-manage-product-btn"   class="components-button  is-button  is-primary is-large">Manage your synced product</button>
                    <br />  <span class="description"><?php _e('To unsync the product with corresponding category '); ?></span>
                </td>
            </tr>
        <?php }else { ?>
            <tr class="form-field">
                <th scope="row" valign="top"><label for="cat_Image_url"><?php _e('Click to sync your product'); ?></label></th>
                <td>
                    <button type="button" id="tpf-sync-product-btn"   class="components-button  is-button  is-primary is-large">Click to Sync your product</button>
                    <br />  <span class="description"><?php _e('To sync the product with corresponding category '); ?></span>
                </td>
            </tr>
        <?php } ?>

         <script>
                jQuery("#tpf-sync-product-btn").click( function(){
                    var dashboad_url    = '<?php echo TPF_DASHBOARD_BASE_URL ; ?>';
                    var tpf_post_id     = '<?php echo $tag->term_id  ; ?>';
                    var tpf_post_url    = '<?php echo get_category_link( $tag->term_id )  ; ?>';
                    var tpf_post_title  = '<?php echo $tag->name  ; ?>';
                    window.open( dashboad_url + 'sync-products?post_id=' + tpf_post_id + '&post_url=' +tpf_post_url+ '&post_title='+tpf_post_title +'&type=category', '_blank' );
                });
                jQuery("#tpf-manage-product-btn").click( function(){
                    var dashboad_url    = '<?php echo TPF_DASHBOARD_BASE_URL ; ?>';
                    var tpf_post_id     = '<?php echo $tag->term_id  ; ?>';
                    window.open( dashboad_url + 'bloggers/synced-products' );
                })
                jQuery("#tpf-redirect-to-settings").click( function(){

                    var dashboad_url    = '<?php echo admin_url( 'admin.php?page=tapify_settings' ); ?>';
                    console.log( "dashboad_url"  , dashboad_url )
                    window.open( dashboad_url );
                });
        </script>
    <?php
    }

    public static function tpf_sync_product_normal_callback(){
        global $post; 
        $synced_product =  self::get_synced_product( $post->ID );
        // print_r($synced_product);
        $store_status   = TPF_Ajax_events::isStoreActive();
        $storeConnected = false;
        if( isset( $store_status->status ) && $store_status->status === 'active' && isset( $store_status->storeStatus ) && $store_status->storeStatus === 'connected'){
            $storeConnected = true;
        }
          
        ?>
            <center> 
                <?php if( !$storeConnected ) { ?>
                    <button type="button" id='tpf-redirect-to-settings-normal' class="components-button  is-button  is-primary is-large">Click to Sync your product</button>
                <?php }else{ ?>
                    <?php if( $synced_product ) { ?>
                        <button type="button" id="tpf-manage-product-btn-normal"   class="components-button  is-button  is-primary is-large">Manage your synced product</button>
                    <?php }else { ?>
                        <button type="button"  id="tpf-sync-product-btn-normal" class="components-button  is-button  is-primary is-large">Click to Sync your product</button>
                    <?php } ?>
                <?php } ?>
            </center>
            <script>
                jQuery("#tpf-sync-product-btn-normal").click( function(){
                    var dashboad_url    = '<?php echo TPF_DASHBOARD_BASE_URL ; ?>';
                    var tpf_post_id     = '<?php echo $post->ID  ; ?>';
                    jQuery.ajax({
                        type: 'POST',
                        url: tapifyajaxAdmin.ajaxurl,
                        data : { action:"get_post_data" , postId:tpf_post_id },
                        success: function(response) {
                            if( response.status ) {
                                var data = response.data;
                                var tpf_post_url    = data.permalink;
                                var tpf_post_title  = data.post_title;
                                window.open( dashboad_url + 'sync-products?post_id=' + tpf_post_id + '&post_url=' +tpf_post_url+ '&post_title='+tpf_post_title +'&type=post', '_blank' );
                            }
                        }
                    });
                    
                })
                jQuery("#tpf-manage-product-btn-normal").click( function(){
                    var dashboad_url    = '<?php echo TPF_DASHBOARD_BASE_URL ; ?>';
                    var tpf_post_id     = '<?php echo $post->ID  ; ?>';
                    window.open( dashboad_url + 'bloggers/synced-products' );
                });
                jQuery("#tpf-redirect-to-settings-normal").click( function(){
                    var dashboad_url    = '<?php echo admin_url( 'admin.php?page=tapify_settings' ); ?>';
                    window.open( dashboad_url );
                });
            </script>
        <?php
    }
}


    