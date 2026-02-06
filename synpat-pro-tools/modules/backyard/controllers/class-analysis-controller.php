<?php
/**
 * Analysis Controller
 * Coordinates patent analysis operations
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Analysis_Controller {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Patent analyzer instance
	 */
	private $analyzer;

	/**
	 * Initialize controller
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->analyzer = new SynPat_Patent_Analyzer( $db );
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'wp_ajax_synpat_get_analysis', [ $this, 'ajax_get_analysis' ] );
		add_action( 'wp_ajax_synpat_save_analysis', [ $this, 'ajax_save_analysis' ] );
		add_action( 'wp_ajax_synpat_delete_analysis', [ $this, 'ajax_delete_analysis' ] );
	}

	/**
	 * Get analysis for a patent
	 */
	public function get_analysis( $patent_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_expert_analysis';

		$analysis = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE patent_id = %d",
			$patent_id
		) );

		if ( ! $analysis ) {
			// No existing analysis, generate new one
			return $this->analyzer->analyze_patent( $patent_id );
		}

		// Return cached analysis
		$data = json_decode( $analysis->analysis_data, true );
		return $data;
	}

	/**
	 * Save or update analysis
	 */
	public function save_analysis( $patent_id, $analysis_data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_expert_analysis';

		$data = [
			'patent_id' => absint( $patent_id ),
			'analysis_data' => wp_json_encode( $analysis_data ),
			'strength_score' => isset( $analysis_data['strength_score'] ) ? floatval( $analysis_data['strength_score'] ) : 0,
			'analysis_status' => 'completed',
			'updated_at' => current_time( 'mysql' ),
		];

		// Check if exists
		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM {$table} WHERE patent_id = %d",
			$patent_id
		) );

		if ( $exists ) {
			$result = $wpdb->update(
				$table,
				$data,
				[ 'patent_id' => $patent_id ]
			);
		} else {
			$data['created_at'] = current_time( 'mysql' );
			$result = $wpdb->insert( $table, $data );
		}

		return $result !== false;
	}

	/**
	 * Delete analysis
	 */
	public function delete_analysis( $patent_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_expert_analysis';

		return $wpdb->delete( $table, [ 'patent_id' => absint( $patent_id ) ] );
	}

	/**
	 * Get portfolio analysis summary
	 */
	public function get_portfolio_analysis( $portfolio_id ) {
		$patents = $this->db->get_portfolio_patents( $portfolio_id );

		$summary = [
			'total_patents' => count( $patents ),
			'analyzed_patents' => 0,
			'avg_strength_score' => 0,
			'patent_analyses' => [],
		];

		$total_score = 0;

		foreach ( $patents as $patent ) {
			$analysis = $this->get_analysis( $patent->id );

			if ( $analysis && ! is_wp_error( $analysis ) ) {
				$summary['analyzed_patents']++;
				$total_score += isset( $analysis['strength_score'] ) ? $analysis['strength_score'] : 0;
				$summary['patent_analyses'][] = [
					'patent_id' => $patent->id,
					'patent_number' => $patent->patent_number,
					'strength_score' => $analysis['strength_score'] ?? 0,
				];
			}
		}

		if ( $summary['analyzed_patents'] > 0 ) {
			$summary['avg_strength_score'] = round( $total_score / $summary['analyzed_patents'], 2 );
		}

		return $summary;
	}

	/**
	 * Compare multiple patents
	 */
	public function compare_patents( $patent_ids ) {
		$comparison = [
			'patents' => [],
			'metrics' => [],
		];

		foreach ( $patent_ids as $patent_id ) {
			$patent = $this->db->get_patent( $patent_id );
			$analysis = $this->get_analysis( $patent_id );

			if ( $patent && $analysis && ! is_wp_error( $analysis ) ) {
				$comparison['patents'][] = [
					'id' => $patent->id,
					'patent_number' => $patent->patent_number,
					'title' => $patent->title,
					'strength_score' => $analysis['strength_score'] ?? 0,
					'claim_count' => $analysis['claim_analysis']['total_claims'] ?? 0,
					'citation_score' => $analysis['citation_analysis']['citation_score'] ?? 0,
				];
			}
		}

		// Calculate comparative metrics
		if ( ! empty( $comparison['patents'] ) ) {
			$scores = array_column( $comparison['patents'], 'strength_score' );
			$comparison['metrics'] = [
				'highest_score' => max( $scores ),
				'lowest_score' => min( $scores ),
				'average_score' => array_sum( $scores ) / count( $scores ),
			];
		}

		return $comparison;
	}

	/**
	 * AJAX handler to get analysis
	 */
	public function ajax_get_analysis() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$patent_id = isset( $_POST['patent_id'] ) ? absint( $_POST['patent_id'] ) : 0;

		if ( ! $patent_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid patent ID', 'synpat-pro' ) ] );
		}

		$analysis = $this->get_analysis( $patent_id );

		if ( is_wp_error( $analysis ) ) {
			wp_send_json_error( [ 'message' => $analysis->get_error_message() ] );
		}

		wp_send_json_success( $analysis );
	}

	/**
	 * AJAX handler to save analysis
	 */
	public function ajax_save_analysis() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$patent_id = isset( $_POST['patent_id'] ) ? absint( $_POST['patent_id'] ) : 0;
		$analysis_data = isset( $_POST['analysis_data'] ) ? json_decode( stripslashes( $_POST['analysis_data'] ), true ) : [];

		if ( ! $patent_id || empty( $analysis_data ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid data', 'synpat-pro' ) ] );
		}

		$result = $this->save_analysis( $patent_id, $analysis_data );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Failed to save analysis', 'synpat-pro' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Analysis saved successfully', 'synpat-pro' ) ] );
	}

	/**
	 * AJAX handler to delete analysis
	 */
	public function ajax_delete_analysis() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$patent_id = isset( $_POST['patent_id'] ) ? absint( $_POST['patent_id'] ) : 0;

		if ( ! $patent_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid patent ID', 'synpat-pro' ) ] );
		}

		$result = $this->delete_analysis( $patent_id );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Failed to delete analysis', 'synpat-pro' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Analysis deleted successfully', 'synpat-pro' ) ] );
	}
}
