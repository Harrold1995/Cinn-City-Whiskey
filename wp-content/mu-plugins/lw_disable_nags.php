<?php
/**
 * @package WordPress_Plugins
 * @subpackage LW_Disable_WordPress_Updates
 */

/*
Plugin Name: Liquid Web Update Manager
Description: Managed WordPress Update Manager
Plugin URI: http://liquidweb.com
Version: 1.0
Author: Liquid Web <support@liquidweb.com>
Author URI: http://liquidweb.com
*/


/**
 * Define the plugin version
 */
define("LWDWPUVERSION", "1.0");

/**
 * The LW_Disable_WordPress_Updates class
 *
 * @package     WordPress_Plugins
 * @subpackage  LW_Disable_WordPress_Updates
 * @since       1.0
 * @author
 */
class LW_Disable_WordPress_Updates {

        function __construct() {

                add_filter( 'pre_site_transient_update_core', array($this, 'remove_core_updates') );           // check wordpress version remove core updates
                add_filter( 'auto_core_update_send_email', '__return_false' );                                 // mute core update send email
                add_filter( 'send_core_update_notification_email', '__return_false' );                         // mute core update email
                if ( defined('LWMWP_REGRESSION_SITE') && LWMWP_REGRESSION_SITE ) {
                        add_filter( 'send_email_change_email', '__return_false' );                             // disable email notification when email changed
                }
                remove_action( 'try_gutenberg_panel', 'wp_try_gutenberg_panel' );                              // Disable Gutenberg Dashboard Widget

                // add_filter( 'automatic_updates_send_debug_email', '__return_false' );                          // mute debug email
                // add_filter( 'allow_major_auto_core_updates', '__return_false' );                               // disable auto core updates
                // add_filter( 'auto_update_core', '__return_true' );                                             // disable auto core update
                // add_filter( 'allow_minor_auto_core_updates', '__return_true' );                                // should be return true to allow minor
                // add_filter( 'wp_auto_update_core', '__return_false' );                                         // disable auto core update
                // add_filter( 'auto_update_plugin', '__return_false' );                                          // don't allow auto update plugins
                // add_filter( 'auto_update_theme', '__return_false' );                                           // don't allow auto theme updates
                // add_filter( 'auto_update_translation', '__return_false' );                                     // don't allow auto translation updates
                // add_filter( 'allow_dev_auto_core_updates', '__return_false' );                                 // don't allow dev releases
                // add_filter( 'automatic_updates_is_vcs_checkout', '__return_true' );                            // disable auto udpates from version control
                // add_filter( 'automatic_updater_disabled', '__return_false' );                                  // should be return false to allow minor
                // if( !defined( 'AUTOMATIC_UPDATER_DISABLED' ) ) define( 'AUTOMATIC_UPDATER_DISABLED', false );  // should be return false to allow minor
                // if( !defined( 'WP_AUTO_UPDATE_CORE') ) define( 'WP_AUTO_UPDATE_CORE', false );
        }

        public function remove_core_updates() {
                global $wp_version;
                return (object) array(
                        'last_checked'          => time(),
                        'updates'               => array(),
                        'version_checked'       => $wp_version
                );
        }
}

if ( class_exists('LW_Disable_WordPress_Updates') ) {
        $LW_Disable_WordPress_Updates = new LW_Disable_WordPress_Updates();
}
?>
