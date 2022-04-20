<?php

namespace LW\PluginCollector;

/**
 * Shortcut for development/testing
 */
\WP_CLI::add_command( 'lw-plugin-report', function( $args, $assoc_args ) {
	$site_report = gather_site_report();

	send_site_report( $site_report );

	\WP_CLI::log( 'Site report sent' );
} );

