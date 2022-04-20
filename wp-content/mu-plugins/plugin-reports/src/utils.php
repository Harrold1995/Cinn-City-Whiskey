<?php

namespace LW\PluginCollector;

function maybe_require_files() {
	require_once( ABSPATH . WPINC . '/version.php' );
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}
}
