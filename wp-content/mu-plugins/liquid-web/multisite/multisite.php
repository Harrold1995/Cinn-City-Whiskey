<?php
/**
 * Plugin Name: Liquid Web Managed WordPress Platform Multisite Admin Sync
 * Plugin URI:  https://liquidweb.com/
 * Description: Keeping the Managed WordPress Platform dashboard and multisite network in sync
 * Version:     0.0.1
 * Author:      Liquid Web
 * Author URI:  https://www.liquidweb.com
 * Text Domain: lw-multisite-admin-sync
 * Domain Path: /languages
 * License:     MIT
 * License URI: https://opensource.org/licenses/MIT
 *
 * @package LiquidWebMultisiteSync
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Call our class.
 */
final class LWMSA_Sync_Core {
	/**
	 * LWMSA_Sync_Core instance.
	 *
	 * @access private
	 * @since  1.0
	 * @var    LWMSA_Sync_Core The one true LWMSA_Sync_Core
	 */
	private static $instance;
	/**
	 * The version number of LWMSA_Sync_Core.
	 *
	 * @access private
	 * @since  1.0
	 * @var    string
	 */
	private $version = '0.0.1';
	/**
	 * If an instance exists, this returns it.  If not, it creates one and
	 * retuns it.
	 *
	 * @return $instance
	 */
	public static function instance() {
		// Run the check to see if we have the instance yet.
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof LWMSA_Sync_Core ) ) {
			// Set our instance.
			self::$instance = new LWMSA_Sync_Core;
			// Set my plugin constants.
			self::$instance->setup_constants();
			// Set my file includes.
			self::$instance->includes();
			// Load our textdomain.
			add_action( 'plugins_loaded', array( self::$instance, 'load_textdomain' ) );
		}
		// And return the instance.
		return self::$instance;
	}
	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.0
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'lw-multisite-admin-sync' ), '1.0' );
	}
	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.0
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'lw-multisite-admin-sync' ), '1.0' );
	}
	/**
	 * Setup plugin constants
	 *
	 * @access private
	 * @since 1.0
	 * @return void
	 */
	private function setup_constants() {
		// Define our file base.
		if ( ! defined( 'LWMSA_SYNC_BASE' ) ) {
			define( 'LWMSA_SYNC_BASE', plugin_basename( __FILE__ ) );
		}
		// Set our base directory constant.
		if ( ! defined( 'LWMSA_SYNC_DIR' ) ) {
			define( 'LWMSA_SYNC_DIR', plugin_dir_path( __FILE__ ) );
		}
		// Plugin Folder URL.
		if ( ! defined( 'LWMSA_SYNC_URL' ) ) {
			define( 'LWMSA_SYNC_URL', plugin_dir_url( __FILE__ ) );
		}
		// Plugin root file.
		if( ! defined( 'LWMSA_SYNC_FILE' ) ) {
			define( 'LWMSA_SYNC_FILE', __FILE__ );
		}
		// Set our includes directory constant.
		if ( ! defined( 'LWMSA_SYNC_INCLS' ) ) {
			define( 'LWMSA_SYNC_INCLS', __DIR__ . '/includes' );
		}
		// Set our version constant.
		if ( ! defined( 'LWMSA_SYNC_VERS' ) ) {
			define( 'LWMSA_SYNC_VERS', $this->version );
		}
	}
	/**
	 * Load our actual files in the places they belong.
	 *
	 * @return void
	 */
	public function includes() {
		// If we aren't a multisite install, bail.
		if ( ! is_multisite() ) {
			return;
		}
		// Load our various classes.
		require_once LWMSA_SYNC_INCLS . '/class-network.php';
		require_once LWMSA_SYNC_INCLS . '/class-api-calls.php';
	}
	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 1.0
	 * @return void
	 */
	public function load_textdomain() {
		// Set filter for plugin's languages directory.
		$lang_dir = dirname( plugin_basename( LWMSA_SYNC_FILE ) ) . '/languages/';
		/**
		 * Filters the languages directory path to use for Liquid Web Multisite Admin Sync.
		 *
		 * @param string $lang_dir The languages directory path.
		 */
		$lang_dir = apply_filters( 'lwmsa_sync_languages_dir', $lang_dir );
		// Traditional WordPress plugin locale filter.
		global $wp_version;
		// Fetch my locale.
		$get_locale = $wp_version >= 4.7 ? get_user_locale() : get_locale();
		/**
		 * Defines the plugin language locale used in Liquid Web Multisite Admin Sync.
		 *
		 * @var $get_locale The locale to use. Uses get_user_locale()` in WordPress 4.7 or greater,
		 *                  otherwise uses `get_locale()`.
		 */
		$locale = apply_filters( 'plugin_locale', $get_locale, 'lw-multisite-admin-sync' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'lw-multisite-admin-sync', $locale );
		// Setup paths to current locale file.
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/lw-multisite-admin-sync/' . $mofile;
		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/lw-multisite-admin-sync/ folder
			load_textdomain( 'lw-multisite-admin-sync', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/lw-multisite-admin-sync/languages/ folder
			load_textdomain( 'lw-multisite-admin-sync', $mofile_local );
		} else {
			// Load the default language files.
			load_plugin_textdomain( 'lw-multisite-admin-sync', false, $lang_dir );
		}
	}
	/**
	 * Make sure we don't have a trailing slash on a URL.
	 *
	 * @param  string $url  The string (url) we wanna strip.
	 *
	 * @return string       The (possibly) modified URL.
	 */
	public function strip_trailing_slash( $url = '' ) {
		return substr( $url, -1 ) == '/' ? substr( $url, 0, -1 ) : $url;
	}
	/**
	 * Check if the domain has actually changed.
	 *
	 * @param  string $old_url  The original URL.
	 * @param  string $new_url  The (potential) new domain.
	 *
	 * @return boolean
	 */
	public function maybe_domain_change( $old_url = '', $new_url = '' ) {
		// Bail without either piece we want.
		if ( empty( $old_url ) || empty( $new_url ) ) {
			return false;
		}
		// Escape each one to make sure they aren't malformed and return boolean based on matching.
		return esc_attr( esc_url( $old_url ) ) !== esc_attr( esc_url( $new_url ) );
	}
	/**
	 * Get my endpoint for the call.
	 *
	 * @return string
	 */
	public function api_endpoint() {
		// Get my base URL endpoint from the constant, making sure not to double slash it.
		$base_url   = lwmsa_sync()->strip_trailing_slash( LWMWP_SITE_ENDPOINT );
		// Build the endpoint, making sure not to double slash it.
		$endpoint   = $base_url . '/multisites/';
		// Return the endpoint.
		return apply_filters( 'lwmsa_site_api_endpoint', esc_url( $endpoint ) );
	}
	/**
	 * Send the appropriate API call based on the request.
	 *
	 * @param  string $type  What type of call we are making.
	 * @param  array  $data  The data args we are passing.
	 *
	 * @return boolean
	 */
	public function send_notification( $type = '', $data = array() ) {
		// Bail if no data was passed, or a type wasn't passed, or it wasn't the right kind.
		if ( empty( $data ) || empty( $type ) || ! in_array( sanitize_text_field( $type ), array( 'add', 'update', 'delete' ) ) ) {
			return false;
		}
		// Handle the before.
		do_action( 'lwmsa_before_notification', $data, $type );
		// Handle my different notification types.
		switch ( sanitize_text_field( $type ) ) {
			case 'add' :
				LWMSA_Sync_API_Calls::add( $data );
				break;
			case 'update' :
				LWMSA_Sync_API_Calls::update( $data );
				break;
			case 'delete' :
				LWMSA_Sync_API_Calls::delete( $data );
				break;
			// End all case breaks.
		}
		// Handle the after.
		do_action( 'lwmsa_after_notification', $data, $type );
	}
	// End our class.
}
/**
 * The main function responsible for returning the one true LWMSA_Sync_Core
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $lwmsa_sync = lwmsa_sync(); ?>
 *
 * @since 1.0
 * @return LWMSA_Sync_Core The one true LWMSA_Sync_Core Instance
 */
function lwmsa_sync() {
	// Run our version compare.
	if ( version_compare( PHP_VERSION, '5.6', '>' ) && defined( 'LWMWP_SITE_ENDPOINT' ) && defined( 'LWMWP_API_TOKEN' ) ) {
		return LWMSA_Sync_Core::instance();
	}
}
lwmsa_sync();
