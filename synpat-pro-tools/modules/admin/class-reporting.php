<?php
/**
 * Reporting and Analytics Module
 * Generate insights and metrics for patent portfolio management
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Reporting {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Report generators registry
	 */
	private $report_generators = [];

	/**
	 * Initialize reporting module
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->register_report_generators();
		$this->setup_event_listeners();
	}

	/**
	 * Register available report types
	 */
	private function register_report_generators() {
		$this->report_generators = [
			'portfolio_overview' => [ $this, 'compile_portfolio_overview' ],
			'analysis_summary' => [ $this, 'compile_analysis_summary' ],
			'user_activity' => [ $this, 'compile_user_activity' ],
			'trend_analysis' => [ $this, 'compile_trend_analysis' ],
		];
	}

	/**
	 * Setup event listeners
	 */
	private function setup_event_listeners() {
		add_action( 'wp_ajax_synpat_generate_report', [ $this, 'handle_report_generation' ] );
		add_action( 'wp_ajax_synpat_export_report', [ $this, 'handle_report_export' ] );
	}

	/**
	 * Compile portfolio overview report
	 */
	public function compile_portfolio_overview( $criteria = [] ) {
		global $wpdb;

		$timeframe_start = isset( $criteria['start_date'] ) ? $criteria['start_date'] : date( 'Y-m-d', strtotime( '-30 days' ) );
		$timeframe_end = isset( $criteria['end_date'] ) ? $criteria['end_date'] : date( 'Y-m-d' );

		$portfolio_table = $wpdb->prefix . 'synpat_portfolios';
		$patent_table = $wpdb->prefix . 'synpat_patents';

		$overview = [
			'summary' => [],
			'top_performers' => [],
			'recent_additions' => [],
		];

		// Summary statistics
		$overview['summary'] = [
			'total_portfolios' => $wpdb->get_var( "SELECT COUNT(*) FROM {$portfolio_table}" ),
			'active_portfolios' => $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$portfolio_table} WHERE status = %s",
				'active'
			) ),
			'total_patents' => $wpdb->get_var( "SELECT COUNT(*) FROM {$patent_table}" ),
			'avg_portfolio_size' => $wpdb->get_var( "SELECT AVG(n_patents) FROM {$portfolio_table}" ),
		];

		// Top performing portfolios
		$overview['top_performers'] = $wpdb->get_results(
			"SELECT id, title, n_patents, essnt, u_upfront 
			FROM {$portfolio_table} 
			ORDER BY u_upfront DESC 
			LIMIT 5"
		);

		// Recent additions
		$overview['recent_additions'] = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, title, created_at 
			FROM {$portfolio_table} 
			WHERE created_at BETWEEN %s AND %s 
			ORDER BY created_at DESC 
			LIMIT 10",
			$timeframe_start,
			$timeframe_end
		) );

		return $overview;
	}

	/**
	 * Compile analysis summary report
	 */
	public function compile_analysis_summary( $criteria = [] ) {
		global $wpdb;

		$analysis_table = $wpdb->prefix . 'synpat_expert_analysis';

		$summary = [
			'totals' => [],
			'score_distribution' => [],
			'completion_rate' => 0,
		];

		// Total analyses
		$summary['totals'] = [
			'completed' => $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$analysis_table} WHERE analysis_status = %s",
				'completed'
			) ),
			'pending' => $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$analysis_table} WHERE analysis_status = %s",
				'pending'
			) ),
			'average_score' => $wpdb->get_var( "SELECT AVG(strength_score) FROM {$analysis_table}" ),
		];

		// Score distribution
		$score_ranges = [
			'high' => [ 80, 100 ],
			'medium' => [ 50, 79 ],
			'low' => [ 0, 49 ],
		];

		foreach ( $score_ranges as $range_label => $bounds ) {
			$summary['score_distribution'][ $range_label ] = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$analysis_table} 
				WHERE strength_score BETWEEN %d AND %d",
				$bounds[0],
				$bounds[1]
			) );
		}

		// Completion rate
		$total_count = $summary['totals']['completed'] + $summary['totals']['pending'];
		if ( $total_count > 0 ) {
			$summary['completion_rate'] = round( ( $summary['totals']['completed'] / $total_count ) * 100, 2 );
		}

		return $summary;
	}

	/**
	 * Compile user activity report
	 */
	public function compile_user_activity( $criteria = [] ) {
		global $wpdb;

		$chart_table = $wpdb->prefix . 'synpat_claim_charts';
		$prior_art_table = $wpdb->prefix . 'synpat_prior_art_reports';

		$activity = [
			'active_users' => [],
			'contribution_stats' => [],
		];

		// Get active users with their contributions
		$active_users = $wpdb->get_results(
			"SELECT DISTINCT created_by as user_id 
			FROM {$chart_table} 
			UNION 
			SELECT DISTINCT created_by as user_id 
			FROM {$prior_art_table}"
		);

		foreach ( $active_users as $user_record ) {
			$user_info = get_userdata( $user_record->user_id );
			
			if ( $user_info ) {
				$activity['active_users'][] = [
					'user_id' => $user_record->user_id,
					'display_name' => $user_info->display_name,
					'charts_count' => $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$chart_table} WHERE created_by = %d",
						$user_record->user_id
					) ),
					'reports_count' => $wpdb->get_var( $wpdb->prepare(
						"SELECT COUNT(*) FROM {$prior_art_table} WHERE created_by = %d",
						$user_record->user_id
					) ),
				];
			}
		}

		// Overall contribution statistics
		$activity['contribution_stats'] = [
			'total_charts' => $wpdb->get_var( "SELECT COUNT(*) FROM {$chart_table}" ),
			'total_reports' => $wpdb->get_var( "SELECT COUNT(*) FROM {$prior_art_table}" ),
			'unique_contributors' => count( $active_users ),
		];

		return $activity;
	}

	/**
	 * Compile trend analysis report
	 */
	public function compile_trend_analysis( $criteria = [] ) {
		global $wpdb;

		$months_back = isset( $criteria['months'] ) ? absint( $criteria['months'] ) : 6;

		$trends = [
			'monthly_growth' => [],
			'category_trends' => [],
		];

		// Monthly growth analysis
		for ( $i = $months_back; $i >= 0; $i-- ) {
			$month_start = date( 'Y-m-01', strtotime( "-{$i} months" ) );
			$month_end = date( 'Y-m-t', strtotime( "-{$i} months" ) );

			$portfolios_added = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}synpat_portfolios 
				WHERE created_at BETWEEN %s AND %s",
				$month_start,
				$month_end . ' 23:59:59'
			) );

			$trends['monthly_growth'][] = [
				'month' => date( 'M Y', strtotime( $month_start ) ),
				'additions' => absint( $portfolios_added ),
			];
		}

		return $trends;
	}

	/**
	 * Generate report by type
	 */
	public function generate_report( $report_type, $criteria = [] ) {
		if ( ! isset( $this->report_generators[ $report_type ] ) ) {
			return new WP_Error( 'invalid_type', __( 'Unknown report type', 'synpat-pro' ) );
		}

		$generator_callback = $this->report_generators[ $report_type ];
		$report_data = call_user_func( $generator_callback, $criteria );

		// Store report metadata
		$this->archive_report_metadata( $report_type, $report_data );

		return $report_data;
	}

	/**
	 * Archive report metadata for future reference
	 */
	private function archive_report_metadata( $report_type, $report_data ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'synpat_report_archive',
			[
				'report_type' => sanitize_key( $report_type ),
				'data_snapshot' => wp_json_encode( $report_data ),
				'generated_by' => get_current_user_id(),
				'generated_at' => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Export report to file format
	 */
	public function export_report_data( $report_data, $export_format = 'json' ) {
		$upload_directory = wp_upload_dir();
		$export_path = $upload_directory['basedir'] . '/synpat-reports/';

		if ( ! file_exists( $export_path ) ) {
			wp_mkdir_p( $export_path );
		}

		$filename = 'report-' . time() . '.' . $export_format;
		$full_path = $export_path . $filename;

		switch ( $export_format ) {
			case 'json':
				file_put_contents( $full_path, wp_json_encode( $report_data, JSON_PRETTY_PRINT ) );
				break;
			case 'csv':
				$this->write_csv_report( $full_path, $report_data );
				break;
			default:
				return new WP_Error( 'unsupported_format', __( 'Export format not supported', 'synpat-pro' ) );
		}

		return [
			'filepath' => $full_path,
			'url' => $upload_directory['baseurl'] . '/synpat-reports/' . $filename,
		];
	}

	/**
	 * Write report data to CSV format
	 */
	private function write_csv_report( $filepath, $report_data ) {
		$file_handle = fopen( $filepath, 'w' );

		foreach ( $report_data as $section_key => $section_data ) {
			fputcsv( $file_handle, [ strtoupper( $section_key ) ] );

			if ( is_array( $section_data ) ) {
				foreach ( $section_data as $row ) {
					if ( is_array( $row ) || is_object( $row ) ) {
						fputcsv( $file_handle, (array) $row );
					} else {
						fputcsv( $file_handle, [ $row ] );
					}
				}
			}

			fputcsv( $file_handle, [] ); // Blank line between sections
		}

		fclose( $file_handle );
	}

	/**
	 * Handle AJAX report generation
	 */
	public function handle_report_generation() {
		check_ajax_referer( 'synpat_pro_nonce', 'security_token' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'notification' => __( 'Insufficient permissions', 'synpat-pro' ) ] );
		}

		$report_type = isset( $_POST['report_type'] ) ? sanitize_key( $_POST['report_type'] ) : '';
		$criteria = isset( $_POST['criteria'] ) ? json_decode( stripslashes( $_POST['criteria'] ), true ) : [];

		$report_data = $this->generate_report( $report_type, $criteria );

		if ( is_wp_error( $report_data ) ) {
			wp_send_json_error( [ 'notification' => $report_data->get_error_message() ] );
		}

		wp_send_json_success( [ 'report' => $report_data ] );
	}

	/**
	 * Handle AJAX report export
	 */
	public function handle_report_export() {
		check_ajax_referer( 'synpat_pro_nonce', 'security_token' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'notification' => __( 'Insufficient permissions', 'synpat-pro' ) ] );
		}

		$report_data = isset( $_POST['report_data'] ) ? json_decode( stripslashes( $_POST['report_data'] ), true ) : [];
		$export_format = isset( $_POST['format'] ) ? sanitize_key( $_POST['format'] ) : 'json';

		$export_result = $this->export_report_data( $report_data, $export_format );

		if ( is_wp_error( $export_result ) ) {
			wp_send_json_error( [ 'notification' => $export_result->get_error_message() ] );
		}

		wp_send_json_success( $export_result );
	}
}
