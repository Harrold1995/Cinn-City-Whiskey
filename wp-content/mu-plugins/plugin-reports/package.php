<?php

// This should only ever run via wp-cli, if no WP_CLI, abort
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

require_once __DIR__ . '/src/models.php';
require_once __DIR__ . '/src/utils.php';
require_once __DIR__ . '/src/gather.php';
require_once __DIR__ . '/src/send-report.php';
require_once __DIR__ . '/src/init.php';

require_once __DIR__ . '/src/plugin-stats/woocommerce.php';
