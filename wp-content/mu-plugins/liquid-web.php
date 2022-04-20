<?php
/**
 * Plugin Name: Liquid Web Managed WordPress Platform Manager
 * Plugin URI:  https://www.liquidweb.com
 * Description: Functionality to support the Liquid Web Managed WordPress and WooCommerce platforms.
 * Author:      Liquid Web
 * Author URI:  https://www.liquidweb.com
 * Text Domain: liquid-web-mwp
 * Domain Path: /languages
 * Version:     1.1.0
 *
 * @package LiquidWeb\MWP
 * @author  Liquid Web
 */

 if ( ! defined( 'MWP_PLUGIN_DIR' ) ) {
	define( 'MWP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'MWP_PLUGIN_URI' ) ) {
	define( 'MWP_PLUGIN_URI', plugin_dir_url( __FILE__ ) );
}

require_once( MWP_PLUGIN_DIR . '/liquid-web/multisite/multisite.php' );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once MWP_PLUGIN_DIR . '/liquid-web/cli/class-command.php';

	require_once MWP_PLUGIN_DIR . '/liquid-web/cli/class-affiliatewp-sub-commands.php';
	require_once MWP_PLUGIN_DIR . '/liquid-web/cli/class-config-sub-commands.php';
}
