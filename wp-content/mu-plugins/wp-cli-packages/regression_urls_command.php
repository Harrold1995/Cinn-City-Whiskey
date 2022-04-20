<?php

if ( ! class_exists( 'WP_CLI' ) ) {
	return;
}

/**
 * Returns regression URLs in JSON format
 */
$regression_urls_command = function() {

	// post list
	$response = WP_CLI::launch_self( 'post list', array(), array( 'post_type' => 'post', 'posts_per_page' => 10, 'fields' => 'url,ID', 'format' => 'json', 'post_status' => 'publish' ), false, true );
	$posts = json_decode( $response->stdout );

	// potentially only to be used for relative URLs
	$response_get_home = WP_CLI::launch_self( 'option get home', array(), array('format' => 'json'), false, true );
	$home_path = json_decode( $response_get_home->stdout );

	// term list : they use the "archive" template
	$response_terms = WP_CLI::launch_self( 'term list', array('category'), array('fields' => 'slug,count,url', 'format' => 'json'), false, true );
	$term_list = json_decode( $response_terms->stdout );
	$term_path = str_replace($home_path, "", $term_list[0]->url); //convert to relative path

	// get post details
	if (is_array($posts)) {
		$post = $posts[0];
		$post_url = str_replace($home_path, "", $posts[0]->url); //convert to relative path
	}

	$links = array(
		"post" => $post_url,
		"category" => $term_path
	);

	WP_CLI::log( json_encode($links) );
};
WP_CLI::add_command( 'regression-urls list', $regression_urls_command );
