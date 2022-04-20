<?php

namespace LW\PluginCollector;

function send_site_report( $site_report ) {

	$site_report['key'] = 'ZTuhNKgzgmAAtZNNjRyqVuzQbv9NyWNJMf7';

	$url = get_plugin_reporter_url() . '/api/site_report';

	$args = [
		'timeout' => 900,
		'headers' => [
			'Content-Type' => 'application/json',
			'Accept' => 'application/json'
		],
		'body'    => json_encode( $site_report ),
	];

	$response = wp_remote_post( $url, $args );

	return $response;
}
