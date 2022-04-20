<?php

namespace LW\PluginCollector;

function gather_site_report() {
	maybe_require_files();

	global $wp_version;

	$site_report = [
		'domain'            => get_home_url(),
		'admin_email'       => get_option( 'admin_email' ),
		'php_version'       => phpversion(),
		'wp_version'        => $wp_version,
		'server_info'       => get_server_info(),
		'lw_info'           => get_lw_info(),
		'php_info'          => get_php_info(),
		'wp_info'           => get_wp_info(),
		'server_name'       => gethostname(),
		'ip'                => gethostbyname( php_uname( 'n' ) ),
		'plugins'           => [],
	];

	foreach ( get_plugins() as $path => $plugin ) {
		$plugin['active'] = is_plugin_active( $path );

		$site_report['plugins'][ $path ] = $plugin;
	}

	// Allow for updating/further adding/modifying of report (say for adding woocommerce stats)
	$site_report = apply_filters( 'lw\site_report', $site_report );

	return $site_report;
}

function get_lw_info() {
	return [
		'plan_name'       => defined( 'LWMWP_PLAN_NAME' ) ? LWMWP_PLAN_NAME : 'None',
		'mwch_site'       => defined( 'LWMWP_MWCH_SITE' ) ? LWMWP_MWCH_SITE : false,
		'regression_site' => defined( 'LWMWP_REGRESSION_SITE' ) ? LWMWP_REGRESSION_SITE : false,
		'staging_site'    => defined( 'LWMWP_STAGING_SITE' ) ? LWMWP_STAGING_SITE : false,
	];
}

function get_server_info() {
	global $wpdb;

	return [
		'php_version'   => PHP_VERSION,
		'mysql_version' => $wpdb->get_var( 'SELECT VERSION()' ),
		'web_server'    => $_SERVER['SERVER_SOFTWARE'],
	];
}

function get_php_info() {
	return [
		'memory_limit'        => ini_get( 'memory_limit' ),
		'upload_max_filesize' => ini_get( 'upload_max_filesize' ),
	];
}

function get_wp_info() {
	return [
		'version'             => get_bloginfo( 'version' ),
		'language'            => defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US',
		'permalink_structure' => get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default',
		'abspath'             => ABSPATH,
		'wp_debug'            => defined( 'WP_DEBUG' ) && WP_DEBUG ? true : false,
		'wp_memory_limit'     => WP_MEMORY_LIMIT,
		'multisite'           => is_multisite() ? true : false,
	];
}
