<?php

namespace LW\PluginCollector;

use WC_Report_Sales_By_Date;

add_filter( 'lw\site_report', function ( $report ) {

	try {
		// Bail if WooCommerce isn't installed
		if ( empty( $report['plugins']['woocommerce/woocommerce.php'] ) ) {
			return $report;
		}

		// Bail if WooCommerce isn't active
		if ( empty( $report['plugins']['woocommerce/woocommerce.php']['active'] ) ) {
			return $report;
		}

		$wc_admin_report_path         = WP_PLUGIN_DIR . '/woocommerce/includes/admin/reports/class-wc-admin-report.php';
		$wc_report_sales_by_date_path = WP_PLUGIN_DIR . '/woocommerce/includes/admin/reports/class-wc-report-sales-by-date.php';

		if ( file_exists( $wc_admin_report_path ) && file_exists( $wc_report_sales_by_date_path ) ) {
			include_once $wc_admin_report_path;
			include_once $wc_report_sales_by_date_path;

			// Gather woocommerce stats
			$report['plugins']['woocommerce/woocommerce.php']['stats']    = gather_woocommerce_stats();
			$report['plugins']['woocommerce/woocommerce.php']['currency'] = get_woocommerce_currency();
		}
	} catch ( \Exception $e ) {
	}

	return $report;
} );

function gather_woocommerce_stats() {

	// Get current year
	$year = (int) ( new \DateTime() )->format( 'Y' );

	$stats = [];

	// Gather report on past 4 years
	for ( $i = 0; $i < 4; $i ++ ) {
		$stats[ $year ] = gather_woocommerce_stats_year( $year );

		$year -= 1;
	}

	$stats['overall'] = gather_woocommerce_stats_overall();

	return $stats;
}

function gather_woocommerce_stats_overall() {
	$products = wc_get_products( [
		'return'      => 'ids',
		'limit'       => -1,
		'post_status' => 'publish',
	] );

	$stats = [
		'products' => count( $products )
	];

	return $stats;
}

function gather_woocommerce_stats_year( $year ) {
	$date_created_string = sprintf( '%s-01-01...%s-12-31',
		$year,
		$year
	);

	$products = wc_get_products( [
		'return'       => 'ids',
		'limit'        => -1,
		'date_created' => $date_created_string,
	] );

	$sales_by_date                 = new WC_Report_Sales_By_Date();
	$sales_by_date->start_date     = strtotime( $year . '-01-01' );
	$sales_by_date->end_date       = strtotime( $year . '-12-31' );
	$sales_by_date->group_by_query = 'YEAR(posts.post_date)';

	$data = $sales_by_date->get_report_data();

	$year_stats = [
		'products'    => count( $products ),
		'order_count' => $data->total_orders,
		'revenue'     => $data->total_sales,
	];

	return $year_stats;
}
