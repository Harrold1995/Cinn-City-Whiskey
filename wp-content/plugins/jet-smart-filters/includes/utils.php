<?php
/**
 * Utils class
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! class_exists( 'Jet_Smart_Filters_Utils' ) ) {

	/**
	 * Define Jet_Smart_Filters_Utils class
	 */
	class Jet_Smart_Filters_Utils {

		/**
		 * Returns template content as string
		 *
		 * @return string
		 */
		public function get_template_html( $template ) {

			ob_start();
			include jet_smart_filters()->get_template( $template );
			$html = ob_get_clean();

			return preg_replace('~>\\s+<~m', '><', $html);

		}

		/**
		 * Returns parsed template
		 *
		 * @return string
		 */
		public function template_parse( $template ) {

			$html_template = $this->get_template_html( $template );

			preg_match_all( '/<%(.+?)%>/', $html_template, $matches, PREG_SET_ORDER );
			foreach ( $matches as $item ) {
				$prefix = ! preg_match( '/(if|for|else|{|})/', $item[0] ) ? 'echo ' : '';
				$html_template = str_replace( $item[0], '<?php ' . $prefix . trim( $item[1] ) . ' ?>', $html_template );
			}

			return $html_template;

		}

		/**
		 * Returns additional providers
		 *
		 * @return string
		 */
		public function get_additional_providers( $settings ) {

			if ( $settings['additional_providers_enabled'] !== 'yes' ) {
				return '';
			}
		
			if ( ! empty( $settings['additional_providers_list'] ) ) {
				$additional_providers = $settings['additional_providers_list'];
			} else if ( ! empty( $settings['additional_providers'] ) ) {
				// backward compatibility
				$additional_providers = array_map( function ( $additional_provider ) {
					return array( 'additional_provider' => $additional_provider );
				}, $settings['additional_providers'] );
			} else {
				return '';
			}
		
			$output_data      = [];
			$default_query_id = ! empty( $settings['query_id'] ) ? $settings['query_id'] : 'default';
		
			foreach ( $additional_providers as $additional_provider ) {
				$provider = ! empty( $additional_provider['additional_provider'] ) ? $additional_provider['additional_provider'] : false;
				$query_id = ! empty( $additional_provider['additional_query_id'] ) ? $additional_provider['additional_query_id'] : $default_query_id;
		
				if ( $provider ) {
					$output_data[] = $provider . ( $query_id ? '/' . $query_id : '' );
				}
			}
		
			return $output_data ? htmlspecialchars( json_encode( $output_data ) ) : '';
		
		}

	}

}
