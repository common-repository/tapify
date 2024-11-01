<?php
/**
 * Add Admin Alerts and Error Messages to the Backend of WordPress.
 *
 * @package Tapify/Classes
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * TPF_add_admin_alerts Class.
 */
class TPF_add_admin_alerts {

	/**
	 * TPF_add_admin_alerts Constructor.
	 */
	public function __construct() {
		
	}

	/**
	 * Hook in tabs.
	 */
	public static function update_admin_about_new_version() {
		global $tapify;

		$tapify_config = TPF_Ajax_events::tapifyAppConfig();
		if( $tapify_config && isset($tapify_config->latestWPPluginVersion) && isset( $tapify_config->outDatedMessage )){

			if( version_compare( $tapify->version, $tapify_config->latestWPPluginVersion, "<" ) ) { ?>

			    <div class="error notice is-dismissible">
			        <p><?php _e( $tapify_config->outDatedMessage, 'tapify' ); ?></p>
			    </div>

			    <?php
			}
		}
	    
	}

}



		
