<?php
namespace LiquidWeb\CLI;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * LiquidWeb WP-CLI Commands.
 *
 * @since 1.0.0
 *
 * @see \WP_CLI_Command
 */
class Command extends \WP_CLI_Command {

	/**
	 * Prints information about this WordPress Site.
	 *
	 * @since 1.0.0
	 * @access public
	 */
	public function details( $_, $assoc_args ) {
		global $wp_version;
		global $wpdb;

		\WP_CLI::line();

		self::format_line( __( 'WP Version: %s', 'liquid-web-mwx' ), $wp_version, '%_' );
		self::format_line( __( 'Absolute Path (ABSPATH): %s', 'liquid-web-mwx'), ABSPATH, '%_' );

		// WP Debug Enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$debug_mode = '%G' . _x( 'Enabled', 'wordpress debug enabled', 'liquid-web-mwx' ) . '%N';
		} else {
			$debug_mode = '%R' . _x( 'Disabled', 'wordpress debug enabled', 'liquid-web-mwx' ) . '%N';
		}

		self::format_line( __( 'Debug mode is: %s', 'liquid-web-mwx' ), $debug_mode );
		self::format_line( __( 'WP Memory Limit (WP_MEMORY_LIMIT): %s', 'liquid-web-mwx'), WP_MEMORY_LIMIT, '%_' );

		// WP Multisite
		if ( is_multisite() ) {
			$multisite_enabled = '%G' . _x( 'Enabled', 'wordpress multisite enabled', 'liquid-web-mwx' ) . '%N';
		} else {
			$multisite_enabled = '%R' . _x( 'Disabled', 'wordpress multisite enabled', 'liquid-web-mwx' ) . '%N';
		}
		self::format_line( __( 'Multisite Enabled: %s', 'liquid-web-mwx'),  $multisite_enabled );

		self::format_line( __( 'WPLANG: %s', 'liquid-web-mwx'), defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US', '%_' );

		$permalink_structure = get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default';
		\WP_CLI::line( sprintf( __( 'Permalink Structure: %s', 'liquid-web-mwx'), $permalink_structure ) );
	
		\WP_CLI::line();

		self::format_line( __( 'Domain: %s', 'liquid-web-mwx' ), get_home_url(), '%_' );
		self::format_line( __( 'Admin Email: %s', 'liquid-web-mwx' ), get_option( 'admin_email' ), '%_' );

		\WP_CLI::line();

		self::format_line( __( 'PHP Version (WP): %s', 'liquid-web-mwx' ), phpversion(), '%_' );
		self::format_line( __( 'PHP Version (Env): %s', 'liquid-web-mwx' ), PHP_VERSION, '%_' );
		self::format_line( __( 'MySQL Version: %s', 'liquid-web-mwx' ), $wpdb->get_var( 'SELECT VERSION()' ), '%_' );
		
		\WP_CLI::line();

		self::format_line( __( 'Server Name: %s', 'liquid-web-mwx' ), gethostname(), '%_' );
		self::format_line( __( 'IP: %s', 'liquid-web-mwx' ), gethostbyname( php_uname( 'n' ) ), '%_' );

		\WP_CLI::line();

		self::format_line( __( 'Memory Limit: %s', 'liquid-web-mwx' ), ini_get( 'memory_limit' ), '%_' );
		self::format_line( __( 'Upload Max Filesize: %s', 'liquid-web-mwx' ), ini_get( 'upload_max_filesize' ), '%_' );

		\WP_CLI::line();
	}


	/**
	 * Serves as a shorthand wrapper for \WP_CLI::line() combined with \WP_CLI::colorize().
	 *
	 * @since 1.0.0
	 * @access protected
	 * @static
	 *
	 * @param string $text        Base text with specifier.
	 * @param mixed  $replacement Replacement text used for sprintf().
	 * @param string $color       Optional. Color code. See \WP_CLI::colorize(). Default empty.
	 */
	protected static function format_line( $text, $replacement, $color = '' ) {
		\WP_CLI::line( sprintf( $text, \WP_CLI::colorize( $color . $replacement . '%N' ) ) );
	}
}

\WP_CLI::add_command( 'lwsupport', 'LiquidWeb\CLI\Command' );
