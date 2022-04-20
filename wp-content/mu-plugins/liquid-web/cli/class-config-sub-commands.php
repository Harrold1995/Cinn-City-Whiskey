<?php
namespace LiquidWeb\Config\CLI;

use \WP_CLI\Utils;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * WP-CLI sub-commands for modifying WP Config.
 *
 * @since 1.1
 *
 */
class Sub_Commands {

	/**
	 * Regenerate the WP_CACHE_KEY_SALT
	 * 
	 * ## EXAMPLES
	 *
 	 * $ wp lwsupport config regenerate-cache-key
 	 * Success: WP_CACHE_KEY_SALT regenerated.
	 *
	 * @since  1.1
	 * @access public
	 * 
	 * @subcommand regenerate-cache-key
	 * @when before_wp_load
	 */
	public function regenerate_cache_key( $args ) {
	
		try {
			$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_ []{}<>~`+=,.;:/?|';
			$max   = strlen( $chars ) - 1;
			$key = '';
			for ( $j = 0; $j < 64; $j++ ) {
				$key .= substr( $chars, random_int( 0, $max ), 1 );
			}
		} catch ( Exception $ex ) {
			$key = wp_generate_password( 64, true, true );
		}

		$path = $this->get_config_path();

		try {
			$config_transformer = new \WPConfigTransformer( $path );
			$config_transformer->update( 'constant', 'WP_CACHE_KEY_SALT', (string) $key );
		} catch ( Exception $exception ) {
			\WP_CLI::error( "Could not process the 'wp-config.php' transformation.\nReason: {$exception->getMessage()}" );
		}
		
		\WP_CLI::success( __( 'WP_CACHE_KEY_SALT regenerated.', 'liquid-web-mwx' ) );
	}

	/**
	 * Gets the path to the wp-config.php file or gives a helpful error if none found.
	 *
	 * @return string Path to wp-config.php file.
	 */
	private function get_config_path() {
		$path = Utils\locate_wp_config();
		if ( ! $path ) {
			\WP_CLI::error( "'wp-config.php' not found.\nEither create one manually or use `wp config create`." );
		}
		return $path;
	}
}
\WP_CLI::add_command( 'lwsupport config', 'LiquidWeb\Config\CLI\Sub_Commands' );
