<?php
/*
Plugin Name: Liquid Web Managed WordPress
Plugin URI: http://liquidweb.com
Description: Liquid Web Managed WordPress platform services.
Version: 1.4
Author: Liquid Web <support@liquidweb.com>
Author URI: http://liquidweb.com
*/
/**
 * Class LiquidWebMWP
 */
class LiquidWebMWP {
	var $pluginPath,
		$apiToken,
		$apiTokenURL,
		$requestStartTime;
	/**
	 * An array of must-use plugins that live in their own sub-directories within the
	 * wp-content/mu-plugins/ directory.
	 *
	 * Paths should be relative to the mu-plugins directory, with a leading slash.
	 *
	 * @var array
	 */
	protected $required_plugins = [
		'/wp-redis/wp-redis.php',
		'/plugin-reports/package.php',
		'/integrations/jetpack.php',
		'/woocommerce-upper-limits/woocommerce-upper-limits.php',
	];

	/**
	 * LiquidWebMWP constructor.
	 *
	 * @since 1.0
	 *
	 * @uses add_action() add_filter()
	 */
	public function __construct() {
		// Set Plugin Path
		$this->pluginPath = dirname( __FILE__ );
		// Set the API Token URL
		if ( defined( 'LWMWP_SITE_ENDPOINT' ) ) {
			$this->apiTokenURL = LWMWP_SITE_ENDPOINT;
		}
		// Set the API Token
		if ( defined( 'LWMWP_API_TOKEN' ) ) {
			$this->apiToken = LWMWP_API_TOKEN;
		}

		// Load any other required plugins.
		$this->load_required_plugins();

		// Fix for BackWPUp blocking PHP from reloading.
		// Filter defined in backwpup/inc/class-job.php, and handle is not spelled correctly.
		add_filter( 'backwpup_job_signals_to_handel', array( $this, 'bypass_pcntl_signals' ), PHP_INT_MAX, 1 );

		// add the actions
		add_action( '_core_updated_successfully', array( $this, 'core_update_callback' ), 10, 1 );

		// Fix Stale Cache for Autoload options
		add_action( 'added_option', array( $this, '_maybe_clear_alloptions_options_cache' ) );
		add_action( 'updated_option', array( $this, '_maybe_clear_alloptions_options_cache' ) );
		add_action( 'deleted_option', array( $this, '_maybe_clear_alloptions_options_cache' ) );

		// Check if the Object Cache and OpCache should be cleared
		add_action( 'muplugins_loaded', array( $this, '_maybe_flush_cache' ) );

		// Listen for WooCommerce installs and notify the platform
		add_action( 'woocommerce_installed', array( $this, 'mwp_woocommerce_notification' ) );

		// Make a delayed call to MWP to avoid holding up requests for external calls.
		add_action( 'call_mwp_endpoint', array( $this, 'call_update_endpoint' ), 10, 1 );

		// Add a whitelist for known good plugins on compatability checks
		add_filter( 'phpcompat_whitelist', array( $this, '_update_compatibility_whitelist' ) );

		// Disable search engine indexing for staging and regression sites
		if ( ( defined( 'LWMWP_REGRESSION_SITE' ) && LWMWP_REGRESSION_SITE ) || ( defined( 'LWMWP_STAGING_SITE' ) && LWMWP_STAGING_SITE ) ) {
			add_action( 'pre_option_blog_public', '__return_zero' );
		}

		// Add a timer and display it near the footer.
		if ( isset( $_GET['lw-monitor'] ) ) {
			$this->requestStartTime = isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ? $_SERVER['REQUEST_TIME_FLOAT'] : microtime(true);
			add_action( 'wp_footer', array( $this, 'show_monitor_info' ) );
		}

		// register wp-cli package(s)
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once $this->pluginPath . '/wp-cli-packages/regression_urls_command.php';
		}
	}

	/**
	 * Load any standard plugins that should be run as must-use plugins ("mu-plugins").
	 */
	public function load_required_plugins() {
		foreach ( $this->required_plugins as $plugin ) {
			$path = $this->pluginPath . $plugin;

			// Verify that the file exists before we attempt to load it.
			if ( file_exists( $path ) ) {
				require_once $path;
			}
		}
	}

	/**
	 * Remove SIGTERM from the list of signals which backupwp captures to avoid
	 * issues shutting down
	 *
	 * @since 1.3
	 *
	 * @param array $signals List of signals to be captured.
	 *
	 * @return array List of signals to be captured
	 */
	public function bypass_pcntl_signals( $signals ) {
		$index = array_search( 'SIGTERM', $signals );
		if ( $index !== false ) {
			unset( $signals[$index] );
		}
		return $signals;
	}

	/**
	 * Schedule an event to send a notification to MWP when WooCommerce is installed.
	 *
	 * @since 1.1
	 *
	 * @uses wp_schedule_single_event()
	 */
	public function mwp_woocommerce_notification() {
		$data = array(
			'is_ecommerce' => true,
		);
		wp_schedule_single_event( time(), 'call_mwp_endpoint', array( $data ) );
	}

	/**
	 * Send an update to MWP when the WordPress version of this site is updated.
	 *
	 * @since 1.0
	 *
	 * @param $wp_version
	 */
	public function core_update_callback( $wp_version ) {
		$this->update_lw_manager_site_info( $wp_version );
	}


	/**
	 * Add our custom whitelist entries to the php-compatibility-checker plugin
	 *
	 * @since 1.0
	 *
	 * @param array $whitelist
	 *
	 * @return array List of plugins or individual files which can be ignored for compatability checks.
	 */
	public function _update_compatibility_whitelist( $whitelist ) {
		return array_merge( $whitelist, array(
			'*/woocommerce-pdf-invoices-packing-slips/vendor/dompdf/dompdf/src/Adapter/CPDF.php',
			'*/woocommerce-pdf-invoices-packing-slips/vendor/phenx/php-svg-lib/src/Svg/Surface/SurfaceCpdf.php',
			'*/ithemes-sync/functions.php',
		) );
	}

	/**
	 * Schedule a notification to send information on this site's details back to MWP.
	 *
	 * @since 1.0
	 *
	 * @param $wp_version
	 *
	 * @uses wp_schedule_single_event()
	 *
	 */
	public function update_lw_manager_site_info( $wp_version ) {
		global $table_prefix;
		$data = array(
			'installed_version' => $wp_version,
			'target_version'    => $wp_version,
			'wp_table_prefix'   => $table_prefix,
		);
		wp_schedule_single_event( time(), 'call_mwp_endpoint', array( $data ) );
	}

	/**
	 * Send a PATCH request to the MWP instance to update parameters for more efficient management.
	 *
	 * @since 1.0
	 *
	 * @uses wp_remote_request()
	 *
	 * @param array $data
	 */
	public function call_update_endpoint( $data = array() ) {
		$headers = array(
			'Content-Type'   => 'application/json',
			'Authorization'  => 'Token ' . $this->apiToken,
			'Content-Length' => strlen( json_encode( $data ) )
		);
		wp_remote_request( $this->apiTokenURL, array(
			'method'  => 'PATCH', // The manager API using the LWMWP_SITE_ENDPOINT will accept a PATCH request
			'body'    => json_encode( $data ),
			'headers' => $headers,
		) );
	}

	/**
	 * Fix a race condition in alloptions (autoloaded options) caching
	 *
	 * See https://core.trac.wordpress.org/ticket/31245
	 *
	 * This code should be able to be removed when the referenced ticket is closed.
	 */
	public function _maybe_clear_alloptions_options_cache( $option ) {
		if ( ! wp_installing() ) {
			$alloptions = wp_load_alloptions(); // alloptions should be cached at this point
			if ( isset( $alloptions[ $option ] ) ) { // only if option is among alloptions
				wp_cache_delete( 'alloptions', 'options' );
			}
		}
	}

	/**
	 * Flush cache if the .flush-cache file is found in web root
	 *
	 * This function is responsible for checking if the `.flush-cache` file exists
	 * and clearing the OpCache and Object Cache if it is found. It will delete the `.flush-cache`
	 * once these actions are completed.
	 *
	 * This handles a case when a migration is executed which directly
	 * manipulates the database and file system. This leaves the cache in a state
	 * which is still populated with the original theme, plugins, and site options. When this occurs
	 * the original site theme is still displayed.
	 */
	public function _maybe_flush_cache() {
		$filepath = ABSPATH . '/.flush-cache';
		if ( false === file_exists( $filepath ) ) {
			return;
		}
		// Default to true if opcache isn't enabled.
		$opcache_reset_result = true;
		$wp_cache_flush_result = wp_cache_flush();
		$opcache_status = function_exists( 'opcache_get_status' ) ? opcache_get_status() : false;
		// Only try to reset OpCache if it's available and enabled.
		if ( false !== $opcache_status && ! empty( $opcache_status['opcache_enabled'] ) ) {
			$opcache_reset_result = opcache_reset();
		}
		if ( $wp_cache_flush_result && $opcache_reset_result ) {
			unlink( $filepath );
		}
	}

	/**
	 * Output Info for Monitoring
	 *
	 * @since 1.2
	 */
	public function show_monitor_info() {
		$totaltime = microtime(true) - $this->requestStartTime;
		$generatedtime = number_format( $totaltime, 3 );
		printf( "<!--\nLW Monitor Response:\n\nPage generated in %s seconds\n-->", esc_html( $generatedtime ) );
	}
}//end of class


$LiquidWebMWP = new LiquidWebMWP; //initialize
// Handles a GET request to report back the current installed WP version
if ( ( array_key_exists( 'managerapi', $_REQUEST ) ) && stristr( $_REQUEST['managerapi'], 'update' ) ) {
	global $wp_version;
	$LiquidWebMWP->update_lw_manager_site_info( $wp_version );
}
