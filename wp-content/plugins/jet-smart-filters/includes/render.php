<?php
/**
 * Data class
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Smart_Filters_Render' ) ) {

	/**
	 * Define Jet_Smart_Filters_Render class
	 */
	class Jet_Smart_Filters_Render {

		private $_rendered_providers = array();

		/**
		 * Constructor for the class
		 */
		public function __construct() {

			add_action( 'init', array( $this, 'maybe_apply_filters' ) );

			add_action( 'wp_ajax_jet_smart_filters', array( $this, 'ajax_apply_filters' ) );
			add_action( 'wp_ajax_nopriv_jet_smart_filters', array( $this, 'ajax_apply_filters' ) );

			add_action( 'wp_ajax_jet_smart_filters_get_hierarchy_level', array( $this, 'hierarchy_level' ) );
			add_action( 'wp_ajax_nopriv_jet_smart_filters_get_hierarchy_level', array( $this, 'hierarchy_level' ) );

		}

		/**
		 * Update hierarchy levels starting from depth
		 * @return [type] [description]
		 */
		public function hierarchy_level() {

			$depth     = isset( $_REQUEST['depth'] ) ? absint( $_REQUEST['depth'] ) : false;
			$filter_id = isset( $_REQUEST['filter_id'] ) ? absint( $_REQUEST['filter_id'] ) : 0;

			if ( ! $filter_id ) {
				wp_send_json_error();
			}

			$values  = ! empty( $_REQUEST['values'] ) ? $_REQUEST['values'] : array();
			$args    = ! empty( $_REQUEST['args'] ) ? $_REQUEST['args'] : array();
			$indexer = isset( $_REQUEST['indexer'] ) ? $_REQUEST['indexer'] : false;

			require jet_smart_filters()->plugin_path( 'includes/hierarchy.php' );

			$hierarchy = new Jet_Smart_Filters_Hierarchy(
				$filter_id,
				$depth,
				$values,
				$args,
				$indexer
			);

			wp_send_json_success( $hierarchy->get_levels() );

		}

		/**
		 * Returns requested provider ID
		 *
		 * @return string
		 */
		public function request_provider( $return = null ) {
			return jet_smart_filters()->query->get_current_provider( $return );
		}

		/**
		 * Maybe apply filters in request.
		 */
		public function maybe_apply_filters() {

			if ( empty( $_REQUEST['jet-smart-filters'] ) ) {
				return;
			}

			$provider_id = $this->request_provider( 'provider' );
			$provider    = jet_smart_filters()->providers->get_providers( $provider_id );

			if ( ! $provider ) {
				return;
			}

			if ( is_callable( array( $provider, 'apply_filters_in_request' ) ) ) {
				jet_smart_filters()->query->get_query_from_request();
				$provider->apply_filters_in_request();
			}

		}

		/**
		 * Apply filters in AJAX request
		 *
		 * @return [type] [description]
		 */
		public function ajax_apply_filters() {

			$provider_id = $this->request_provider( 'provider' );
			$query_id    = $this->request_provider( 'query_id' );
			$apply_type  = ! empty( $_REQUEST['apply_type'] ) ? $_REQUEST['apply_type'] : 'ajax';
			$provider    = jet_smart_filters()->providers->get_providers( $provider_id );

			if ( ! $provider ) {
				return;
			}

			jet_smart_filters()->query->get_query_from_request();

			if ( ! empty( $_REQUEST['props'] ) ) {

				jet_smart_filters()->query->set_props(
					$provider_id,
					$_REQUEST['props'],
					$query_id
				);

			}

			$args = array(
				'content'    => $this->render_content( $provider ),
				'pagination' => jet_smart_filters()->query->get_current_query_props()
			);

			$args = apply_filters( 'jet-smart-filters/render/ajax/data', $args );

			wp_send_json( $args );

		}

		/**
		 * Render content
		 *
		 * @return string
		 */
		public function render_content( $provider ) {

			ob_start();

			if ( is_callable( array( $provider, 'ajax_get_content' ) ) ) {
				$provider->ajax_get_content();
			} else {
				_e( 'Incorrect input data', 'jet-smart-filters' );
			}

			return ob_get_clean();

		}

	}

}
