<?php
/**
 * Prior Art Report Builder
 * Create and manage prior art reports
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Prior_Art {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Initialize prior art module
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'wp_ajax_synpat_create_prior_art', [ $this, 'ajax_create_prior_art' ] );
		add_action( 'wp_ajax_synpat_update_prior_art', [ $this, 'ajax_update_prior_art' ] );
		add_action( 'wp_ajax_synpat_delete_prior_art', [ $this, 'ajax_delete_prior_art' ] );
		add_action( 'wp_ajax_synpat_get_prior_art', [ $this, 'ajax_get_prior_art' ] );
		add_action( 'wp_ajax_synpat_search_prior_art', [ $this, 'ajax_search_prior_art' ] );

		// Register custom rendering filter
		add_filter( 'synpat_render_prior_art', [ $this, 'render_prior_art' ], 10, 2 );
	}

	/**
	 * Create new prior art report
	 */
	public function create_prior_art_report( $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_prior_art_reports';

		$report_data = $this->prepare_report_data( $data );

		$result = $wpdb->insert( $table, $report_data );

		if ( $result ) {
			$report_id = $wpdb->insert_id;
			do_action( 'synpat_prior_art_created', $report_id, $report_data );
			return $report_id;
		}

		return new WP_Error( 'creation_failed', __( 'Failed to create prior art report', 'synpat-pro' ) );
	}

	/**
	 * Update prior art report
	 */
	public function update_prior_art_report( $report_id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_prior_art_reports';

		$report_data = $this->prepare_report_data( $data );
		$report_data['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->update(
			$table,
			$report_data,
			[ 'id' => absint( $report_id ) ]
		);

		if ( $result !== false ) {
			do_action( 'synpat_prior_art_updated', $report_id, $report_data );
			return true;
		}

		return new WP_Error( 'update_failed', __( 'Failed to update prior art report', 'synpat-pro' ) );
	}

	/**
	 * Delete prior art report
	 */
	public function delete_prior_art_report( $report_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_prior_art_reports';

		$result = $wpdb->delete( $table, [ 'id' => absint( $report_id ) ] );

		if ( $result ) {
			do_action( 'synpat_prior_art_deleted', $report_id );
			return true;
		}

		return false;
	}

	/**
	 * Get prior art report by ID
	 */
	public function get_prior_art_report( $report_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_prior_art_reports';

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$report_id
		) );
	}

	/**
	 * Get all prior art reports for a patent
	 */
	public function get_patent_prior_art_reports( $patent_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_prior_art_reports';

		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE target_patent_id = %d ORDER BY created_at DESC",
			$patent_id
		) );
	}

	/**
	 * Prepare report data for database
	 */
	private function prepare_report_data( $data ) {
		return [
			'target_patent_id' => isset( $data['target_patent_id'] ) ? absint( $data['target_patent_id'] ) : 0,
			'target_patent' => isset( $data['target_patent'] ) ? sanitize_text_field( $data['target_patent'] ) : '',
			'title' => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
			'references' => isset( $data['references'] ) ? wp_json_encode( $data['references'] ) : '',
			'analysis' => isset( $data['analysis'] ) ? wp_kses_post( $data['analysis'] ) : '',
			'relevance_score' => isset( $data['relevance_score'] ) ? floatval( $data['relevance_score'] ) : 0,
			'status' => isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'draft',
			'created_by' => get_current_user_id(),
			'created_at' => current_time( 'mysql' ),
		];
	}

	/**
	 * Render prior art report for display
	 */
	public function render_prior_art( $content, $report_id ) {
		$report = $this->get_prior_art_report( $report_id );

		if ( ! $report ) {
			return $content;
		}

		$references = json_decode( $report->references, true );

		ob_start();
		include SYNPAT_PRO_ROOT . 'modules/experts/templates/prior-art-display.php';
		return ob_get_clean();
	}

	/**
	 * Search for prior art references
	 */
	public function search_prior_art( $search_params ) {
		$results = [
			'total' => 0,
			'references' => [],
		];

		// Search in database
		$db_results = $this->search_database( $search_params );
		$results['references'] = array_merge( $results['references'], $db_results );

		// Apply relevance scoring
		$results['references'] = $this->score_references( $results['references'], $search_params );

		// Sort by relevance
		usort( $results['references'], function( $a, $b ) {
			return $b['relevance'] - $a['relevance'];
		} );

		$results['total'] = count( $results['references'] );

		return $results;
	}

	/**
	 * Search database for prior art
	 */
	private function search_database( $search_params ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_patents';

		$keywords = isset( $search_params['keywords'] ) ? $search_params['keywords'] : '';
		$classification = isset( $search_params['classification'] ) ? $search_params['classification'] : '';
		$date_before = isset( $search_params['date_before'] ) ? $search_params['date_before'] : '';

		$where_clauses = [];
		$prepare_values = [];

		if ( ! empty( $keywords ) ) {
			$search_pattern = '%' . $wpdb->esc_like( $keywords ) . '%';
			$where_clauses[] = '(title LIKE %s OR abstract LIKE %s)';
			$prepare_values[] = $search_pattern;
			$prepare_values[] = $search_pattern;
		}

		if ( ! empty( $classification ) ) {
			$where_clauses[] = 'classification LIKE %s';
			$prepare_values[] = '%' . $wpdb->esc_like( $classification ) . '%';
		}

		if ( ! empty( $date_before ) ) {
			$where_clauses[] = 'filing_date < %s';
			$prepare_values[] = $date_before;
		}

		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';

		$query = "SELECT * FROM {$table} {$where_sql} LIMIT 50";

		if ( ! empty( $prepare_values ) ) {
			$query = $wpdb->prepare( $query, $prepare_values );
		}

		$patents = $wpdb->get_results( $query );

		$references = [];
		foreach ( $patents as $patent ) {
			$references[] = [
				'type' => 'patent',
				'patent_number' => $patent->patent_number,
				'title' => $patent->title,
				'abstract' => $patent->abstract,
				'filing_date' => $patent->filing_date,
				'inventor' => $patent->inventor,
				'relevance' => 0,
			];
		}

		return $references;
	}

	/**
	 * Score references by relevance
	 */
	private function score_references( $references, $search_params ) {
		$keywords = isset( $search_params['keywords'] ) ? strtolower( $search_params['keywords'] ) : '';

		foreach ( $references as &$reference ) {
			$score = 0;

			// Title match
			if ( ! empty( $keywords ) && stripos( $reference['title'], $keywords ) !== false ) {
				$score += 50;
			}

			// Abstract match
			if ( ! empty( $keywords ) && isset( $reference['abstract'] ) && stripos( $reference['abstract'], $keywords ) !== false ) {
				$score += 30;
			}

			// Date proximity (older is generally better for prior art)
			if ( isset( $reference['filing_date'] ) && isset( $search_params['date_before'] ) ) {
				$ref_date = strtotime( $reference['filing_date'] );
				$target_date = strtotime( $search_params['date_before'] );
				$years_diff = ( $target_date - $ref_date ) / ( 365 * 24 * 60 * 60 );

				if ( $years_diff > 0 ) {
					$score += min( 20, $years_diff * 2 );
				}
			}

			$reference['relevance'] = $score;
		}

		return $references;
	}

	/**
	 * Add reference to report
	 */
	public function add_reference( $report_id, $reference ) {
		$report = $this->get_prior_art_report( $report_id );

		if ( ! $report ) {
			return new WP_Error( 'report_not_found', __( 'Report not found', 'synpat-pro' ) );
		}

		$references = json_decode( $report->references, true );
		if ( ! is_array( $references ) ) {
			$references = [];
		}

		$references[] = $reference;

		return $this->update_prior_art_report( $report_id, [
			'references' => $references,
		] );
	}

	/**
	 * Remove reference from report
	 */
	public function remove_reference( $report_id, $reference_index ) {
		$report = $this->get_prior_art_report( $report_id );

		if ( ! $report ) {
			return new WP_Error( 'report_not_found', __( 'Report not found', 'synpat-pro' ) );
		}

		$references = json_decode( $report->references, true );
		if ( ! is_array( $references ) ) {
			return false;
		}

		if ( isset( $references[ $reference_index ] ) ) {
			unset( $references[ $reference_index ] );
			$references = array_values( $references ); // Re-index array
		}

		return $this->update_prior_art_report( $report_id, [
			'references' => $references,
		] );
	}

	/**
	 * AJAX handler to create prior art report
	 */
	public function ajax_create_prior_art() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$data = isset( $_POST['report_data'] ) ? json_decode( stripslashes( $_POST['report_data'] ), true ) : [];

		if ( empty( $data ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid data', 'synpat-pro' ) ] );
		}

		$report_id = $this->create_prior_art_report( $data );

		if ( is_wp_error( $report_id ) ) {
			wp_send_json_error( [ 'message' => $report_id->get_error_message() ] );
		}

		wp_send_json_success( [
			'report_id' => $report_id,
			'message' => __( 'Prior art report created successfully', 'synpat-pro' ),
		] );
	}

	/**
	 * AJAX handler to update prior art report
	 */
	public function ajax_update_prior_art() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$report_id = isset( $_POST['report_id'] ) ? absint( $_POST['report_id'] ) : 0;
		$data = isset( $_POST['report_data'] ) ? json_decode( stripslashes( $_POST['report_data'] ), true ) : [];

		if ( ! $report_id || empty( $data ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid data', 'synpat-pro' ) ] );
		}

		$result = $this->update_prior_art_report( $report_id, $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [ 'message' => __( 'Prior art report updated successfully', 'synpat-pro' ) ] );
	}

	/**
	 * AJAX handler to delete prior art report
	 */
	public function ajax_delete_prior_art() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'delete_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$report_id = isset( $_POST['report_id'] ) ? absint( $_POST['report_id'] ) : 0;

		if ( ! $report_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid report ID', 'synpat-pro' ) ] );
		}

		$result = $this->delete_prior_art_report( $report_id );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Failed to delete prior art report', 'synpat-pro' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Prior art report deleted successfully', 'synpat-pro' ) ] );
	}

	/**
	 * AJAX handler to get prior art report
	 */
	public function ajax_get_prior_art() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$report_id = isset( $_POST['report_id'] ) ? absint( $_POST['report_id'] ) : 0;

		if ( ! $report_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid report ID', 'synpat-pro' ) ] );
		}

		$report = $this->get_prior_art_report( $report_id );

		if ( ! $report ) {
			wp_send_json_error( [ 'message' => __( 'Prior art report not found', 'synpat-pro' ) ] );
		}

		wp_send_json_success( $report );
	}

	/**
	 * AJAX handler to search for prior art
	 */
	public function ajax_search_prior_art() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$search_params = isset( $_POST['search_params'] ) ? json_decode( stripslashes( $_POST['search_params'] ), true ) : [];

		$results = $this->search_prior_art( $search_params );

		wp_send_json_success( $results );
	}
}
