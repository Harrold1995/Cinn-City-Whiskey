<?php
/**
 * Filters manager class
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Smart_Filters_Filter_Manager' ) ) {

	/**
	 * Define Jet_Smart_Filters_Filter_Manager class
	 */
	class Jet_Smart_Filters_Filter_Manager {

		private $_filter_types = array();

		/**
		 * Constructor for the class
		 */
		public function __construct() {
			$this->register_filter_types();
			add_action( 'elementor/frontend/before_enqueue_scripts', array( $this, 'filter_scripts' ) );
			add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'filter_styles' ) );
			add_action( 'elementor/editor/after_enqueue_styles', array( $this, 'filter_editor_styles' ) );
			add_action( 'elementor/preview/enqueue_styles', array( $this, 'filter_editor_styles' ) );
		}

		/**
		 * Enqueue filter scripts
		 */
		public function filter_scripts() {

			$dependencies = array( 'jquery' );

			foreach ( $this->get_filter_types() as $filter ) {

				$assets = $filter->get_scripts();

				if ( $assets ) {
					$dependencies = array_merge( $dependencies, $assets );
				}

			}

			wp_enqueue_script(
				'jet-smart-filters',
				jet_smart_filters()->plugin_url( 'assets/js/public.js' ),
				$dependencies,
				jet_smart_filters()->get_version(),
				true
			);

			$localized_data = apply_filters( 'jet-smart-filters/filters/localized-data', array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'siteurl'   => get_site_url(),
				'selectors' => jet_smart_filters()->data->get_provider_selectors(),
				'queries'   => jet_smart_filters()->query->get_default_queries(),
				'settings'  => jet_smart_filters()->providers->get_provider_settings(),
				'misc'      => array(
					'week_start' => get_option( 'start_of_week' ),
				),
				'props'     => jet_smart_filters()->query->get_query_props(),
				'templates' => $this->get_localization_templates()
			) );

			wp_localize_script( 'jet-smart-filters', 'JetSmartFilterSettings', $localized_data );

		}

		public function get_localization_templates() {

			$templates = [];

			$templates['active_filter'] = jet_smart_filters()->utils->get_template_html( 'for-js/active-filter.php' );
			$templates['active_tag'] = jet_smart_filters()->utils->get_template_html( 'for-js/active-tag.php' );
			$templates['pagination_item'] = jet_smart_filters()->utils->get_template_html( 'for-js/pagination-item.php' );
			$templates['pagination_item_dots'] = jet_smart_filters()->utils->get_template_html( 'for-js/pagination-item-dots.php' );

			return $templates;

		}

		/**
		 * Enqueue filter styles
		 */
		public function filter_styles() {

			wp_enqueue_style(
				'jet-smart-filters',
				jet_smart_filters()->plugin_url( 'assets/css/public.css' ),
				array(),
				jet_smart_filters()->get_version()
			);

		}

		/**
		 * Enqueue editor filter styles
		 */
		public function filter_editor_styles() {

			wp_enqueue_style(
				'jet-smart-filters-icons-font',
				jet_smart_filters()->plugin_url( 'assets/css/lib/jet-smart-filters-icons/jet-smart-filters-icons.css' ),
				array(),
				jet_smart_filters()->get_version()
			);

		}

		/**
		 * Register all providers.
		 *
		 * @return void
		 */
		public function register_filter_types() {

			$base_path = jet_smart_filters()->plugin_path( 'includes/filters/' );

			$default_filter_types = array(
				'Jet_Smart_Filters_Checkboxes_Filter'  => $base_path . 'checkboxes.php',
				'Jet_Smart_Filters_Select_Filter'      => $base_path . 'select.php',
				'Jet_Smart_Filters_Range_Filter'       => $base_path . 'range.php',
				'Jet_Smart_Filters_Check_Range_Filter' => $base_path . 'check-range.php',
				'Jet_Smart_Filters_Date_Range_Filter'  => $base_path . 'date-range.php',
				'Jet_Smart_Filters_Radio_Filter'       => $base_path . 'radio.php',
				'Jet_Smart_Filters_Rating_Filter'      => $base_path . 'rating.php',
				'Jet_Smart_Filters_Search_Filter'      => $base_path . 'search.php',
				'Jet_Smart_Filters_Color_Image_Filter' => $base_path . 'color-image.php',
			);

			require $base_path . 'base.php';

			foreach ( $default_filter_types as $filter_class => $filter_file ) {
				$this->register_filter_type( $filter_class, $filter_file );
			}

			/**
			 * Register custom filter types on this hook
			 */
			do_action( 'jet-smart-filters/filter-types/register', $this );

		}

		/**
		 * Register new filter.
		 *
		 * @param  string $filter_class Filter class name.
		 * @param  string $filter_file Path to file with filter class.
		 *
		 * @return void
		 */
		public function register_filter_type( $filter_class, $filter_file ) {

			if ( ! file_exists( $filter_file ) ) {
				return;
			}

			require $filter_file;

			if ( class_exists( $filter_class ) ) {
				$instance                                   = new $filter_class();
				$this->_filter_types[ $instance->get_id() ] = $instance;
			}

		}

		/**
		 * Return all filter types list or specific filter by ID
		 *
		 * @param  string $filter optional, filter ID.
		 *
		 * @return array|filter object|false
		 */
		public function get_filter_types( $filter = null ) {

			if ( $filter ) {
				return isset( $this->_filter_types[ $filter ] ) ? $this->_filter_types[ $filter ] : false;
			}

			return $this->_filter_types;

		}

		/**
		 * Return suffix for query modify
		 *
		 * @param  string $filter, filter ID.
		 *
		 * @return string query_var_suffix for filter
		 */
		public function get_filter_query_var_suffix( $filter ) {

			$query_var_suffix   = array();
			$type               = get_post_meta( $filter, '_filter_type', true );
			$query_var          = get_post_meta( $filter, '_query_var', true );
			$data_source        = get_post_meta( $filter, '_data_source', true );
			$is_hierarchical    = false;
			$is_custom_checkbox = false;

			if ( 'select' === $type ) {
				$is_hierarchical = filter_var( get_post_meta( $filter, '_is_hierarchical', true ), FILTER_VALIDATE_BOOLEAN );
			}

			if ( in_array( $type, ['checkboxes', 'select', 'radio', 'color-image'] ) ) {
				$is_custom_checkbox = filter_var( get_post_meta( $filter, '_is_custom_checkbox', true ), FILTER_VALIDATE_BOOLEAN );
			}

			if ( in_array( $type, ['search', 'range', 'date-range', 'check-range', 'rating'] ) ) {
				$query_var_suffix[] = $type;
			}

			if ( $is_custom_checkbox ) {
				$query_var_suffix[] = 'is_custom_checkbox';
			}

			if ( $query_var && ! $is_hierarchical && ! $is_custom_checkbox ) {
				if ( in_array( $type, ['select', 'radio'] ) && 'taxonomies' !== $data_source ) {
					$query_compare      = get_post_meta( $filter, '_query_compare', true );

					if ( 'equal' !== $query_compare ) {
						$query_var_suffix[] = 'compare::' . $query_compare;
					}
				}
			}

			if ( 'rating' === $type ) {
				$query_compare      = get_post_meta( $filter, '_rating_compare_operand', true );
				$query_var_suffix[] = 'compare::' . $query_compare;
			}

			return $query_var_suffix ? implode( ',', $query_var_suffix ) : false;

		}

		/**
		 * Returns filter instance by filter post ID
		 *
		 * @param  [type] $filter_id [description]
		 * @return [type]            [description]
		 */
		public function get_filter_instance( $filter_id, $type = null, $args = array() ) {

			if ( null === $type ) {
				$type = get_post_meta( $filter_id, '_filter_type', true );
			}

			if ( ! $type ) {
				return false;
			}

			if ( ! class_exists( 'Jet_Smart_Filters_Filter_Instance' ) ) {
				require_once jet_smart_filters()->plugin_path( 'includes/filters/instance.php' );
			}

			return new Jet_Smart_Filters_Filter_Instance( $filter_id, $type, $args );

		}

		/**
		 * Render fiter type template
		 *
		 * @param  int $filter_id filter ID.
		 * @param  array $args arguments.
		 *
		 * @return void
		 */
		public function render_filter_template( $filter_type, $args = array() ) {

			$filter = $this->get_filter_instance( $args['filter_id'], $filter_type, $args );
			$filter->render();

		}

	}

}
