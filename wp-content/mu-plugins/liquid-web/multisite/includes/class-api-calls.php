<?php
/**
 * Our actual API calls.
 *
 * @package LiquidWebMultisiteSync
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Start our engines.
 */
class LWMSA_Sync_API_Calls {
	/**
	 * Set up and return the headers for the API calls.
	 *
	 * @param  string $type  What type of API call is being made.
	 *
	 * @return array
	 */
	private static function get_api_headers( $type = '' ) {
		// Set the base array of args to pass.
		$args   = array(
			'cache-control' => 'no-cache',
			'content-type'  => 'application/json',
			'Authorization' => 'Token ' . LWMWP_API_TOKEN
		);
		// Return it, filtered.
		return apply_filters( 'lwmsa_sync_api_headers', $args, $type );
	}
	/**
	 * Make the actual POST call.
	 *
	 * @param  array $args  The data array.
	 *
	 * @return boolean
	 */
	private static function call( $args = array() ) {
		// Bail without our setup args.
		if ( empty( $args ) || empty( $args['method'] ) || empty( $args['body'] ) ) {
			return false;
		}

		// Get my endpoint.
		$endpoint   = lwmsa_sync()->api_endpoint();
		// Make the remote call itself.
		$request    = wp_remote_post( esc_url( $endpoint ), $args );

		// Bail without a return.
		if ( empty( $request ) || is_wp_error( $request ) ) {
			return false; // @@todo  error return somewhere?
		}
		// Fetch my response code.
		$response   = wp_remote_retrieve_response_code( $request );

		// Return a success on a 202 or false otherwise.
		return ! empty( absint( $response ) ) && 202 === absint( $response ) ? true : false;
	}
	/**
	 * Send the notification for an added site.
	 *
	 * @param  array $data  The data array (the site URL being added).
	 *
	 * @return void
	 */
	public static function add( $data = array() ) {
		// Bail if we don't have the required piece.
		if ( empty( $data ) || empty( $data['url'] ) || empty( $data['blog_id'] ) ) {
			return false;
		}
		// Create the data I wanna send.
		$body   = array( 'url' => esc_url( $data['url'] ), 'blog_id' => absint( $data['blog_id'] ) );
		// Set up my API args.
		$setup  = array(
			'method'    => 'POST',
			'headers'   => self::get_api_headers( 'add' ),
			'body'      => json_encode( $body ),
		);
		// Make the remote call itself.
		return self::call( $setup );
	}
	/**
	 * Send the notification for an updated site.
	 *
	 * @param  array $data  The data array (the old and new site URLs being updated).
	 *
	 * @return void
	 */
	public static function update( $data = array() ) {
		// Bail if we don't have the required pieces.
		if ( empty( $data ) || empty( $data['old_url'] ) || empty( $data['new_url'] ) || empty( $data['blog_id'] ) ) {
			return false;
		}
		// Create the data I wanna send.
		$body   = array(
		    'url' => esc_url( $data['new_url'] ),
		    'blog_id' => absint( $data['blog_id'] )
		);
		// Set up my API args.
		$setup  = array(
			'method'    => 'PUT',
			'headers'   => self::get_api_headers( 'update' ),
			'body'      => json_encode( $body ),
		);
		// Make the remote call itself.
		return self::call( $setup );
	}
	/**
	 * Send the notification for a deleted site.
	 *
	 * @param  array $data  The data array (the site URL being deleted).
	 *
	 * @return void
	 */
	public static function delete( $data = array() ) {
		// Bail if we don't have the required piece.
		if ( empty( $data ) || empty( $data['url'] ) || empty( $data['blog_id'] ) ) {
			return false;
		}
		// Create the data I wanna send.
		$body   = array( 'url' => esc_url( $data['url'] ), 'blog_id' => absint( $data['blog_id'] ) );
		// Set up my API args.
		$setup  = array(
			'method'    => 'DELETE',
			'headers'   => self::get_api_headers( 'delete' ),
			'body'      => json_encode( $body ),
		);
		// Make the remote call itself.
		return self::call( $setup );
	}
	// End our class.
}
// Call our class.
new LWMSA_Sync_API_Calls();
