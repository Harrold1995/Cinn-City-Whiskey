<?php
/**
 * Functions tied to the site adding / removing.
 *
 * @package LiquidWebMultisiteSync
 */
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * Start our engines.
 */
class LWMSA_Sync_Network {
	/**
	 * Call our hooks.
	 *
	 * @return void
	 */
	public function init() {

		// Call our actions.
		add_action( 'wpmu_new_blog',           array( $this, 'sync_change_on_add'          ),  99      );
		add_action( 'delete_blog',             array( $this, 'sync_change_on_delete'       ),  99      );
		add_action( 'admin_init',              array( $this, 'sync_change_on_update'       )           );

		// Integration with WP Ultimo and Mercator
		add_action('mercator.mapping.created', array($this, 'sync_change_on_add'          ),  99      );
		add_action('mercator.mapping.updated', array($this, 'sync_change_on_update'       ),  99      );
		add_action('mercator.mapping.deleted', array($this, 'sync_change_on_delete'       )           );
	}
	/**
	 * Handle running the notification for newly added sites.
	 *
	 * @param  integer $blog_id  The blog ID.
	 *
	 * @return void
	 */
	public function sync_change_on_add( $blog_id ) {
		// Bail without a blog ID.
		if ( empty( $blog_id ) ) {
			return;
		}
		// Fetch my site URL.
		$site_url   = get_site_url( absint( $blog_id ) );
		// Now we do the notification to the LW dashboard.
		lwmsa_sync()->send_notification( 'add', array( 'url' => esc_url( $site_url ), 'blog_id' => absint( $blog_id )) );
	}
	/**
	 * Handle running the notification for deleted sites.
	 *
	 * @param  integer $blog_id  The blog ID being deleted.
	 * @param  boolean $drop     True if blog's table should be dropped. Default is false.
	 *
	 * @return void
	 */
	public function sync_change_on_delete( $blog_id ) {
		// Bail without a blog ID.
		if ( empty( $blog_id ) ) {
			return;
		}
		// Fetch my site URL.
		$site_url   = get_site_url( absint( $blog_id ) );
		// Now we do the notification to the LW dashboard.
		lwmsa_sync()->send_notification( 'delete', array( 'url' => esc_url( $site_url ), 'blog_id' => absint( $blog_id )) );
	}
	/**
	 * Handle running the notification for updated sites.
	 *
	 * @return void
	 */
	public function sync_change_on_update() {

		// Check the action coming through.
		if ( ! isset( $_REQUEST['action'] ) || 'update-site' !== sanitize_text_field( $_REQUEST['action'] ) ) {
			return;
		}
		// We should have an ID and blog URL being passed, but to be sure.
		if ( empty( $_POST['id'] ) || empty( $_POST['blog'] ) || empty( $_POST['blog']['url'] ) ) {
			return;
		}
		// Fetch my current site URL based on the ID.
		$old_url    = get_site_url( absint( $_POST['id'] ) );
		$new_url    = esc_url( $_POST['blog']['url'] );
		$blog_id    = absint( $_POST['id'] );
		// Run the comparison. If they
		if ( false === $compare = lwmsa_sync()->maybe_domain_change( $old_url, $new_url ) ) {
			return;
		}
		// Set my data array.
		$setup  = array(
			'old_url'   => esc_url( $old_url ),
			'new_url'   => esc_url( $new_url ),
			'blog_id'   => absint( $blog_id ),
		);
		// Now we do the notification to the LW dashboard.
		lwmsa_sync()->send_notification( 'update', $setup );
	}
	// End our class.
}
// Call our class.
$LWMSA_Sync_Network = new LWMSA_Sync_Network();
$LWMSA_Sync_Network->init();
