<?php
/**
 * Claim Chart Creator
 * Create and manage patent claim charts
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Claim_Chart {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Initialize claim chart module
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'wp_ajax_synpat_create_claim_chart', [ $this, 'ajax_create_claim_chart' ] );
		add_action( 'wp_ajax_synpat_update_claim_chart', [ $this, 'ajax_update_claim_chart' ] );
		add_action( 'wp_ajax_synpat_delete_claim_chart', [ $this, 'ajax_delete_claim_chart' ] );
		add_action( 'wp_ajax_synpat_get_claim_chart', [ $this, 'ajax_get_claim_chart' ] );
		
		// Register custom rendering filter
		add_filter( 'synpat_render_claim_chart', [ $this, 'render_claim_chart' ], 10, 2 );
	}

	/**
	 * Create new claim chart
	 */
	public function create_claim_chart( $data ) {
		global $wpdb;
		$table = $this->db->table( 'claim_charts' );

		$chart_data = $this->prepare_chart_data( $data );

		$result = $wpdb->insert( $table, $chart_data );

		if ( $result ) {
			$chart_id = $wpdb->insert_id;
			do_action( 'synpat_claim_chart_created', $chart_id, $chart_data );
			return $chart_id;
		}

		return new WP_Error( 'creation_failed', __( 'Failed to create claim chart', 'synpat-pro' ) );
	}

	/**
	 * Update existing claim chart
	 */
	public function update_claim_chart( $chart_id, $data ) {
		global $wpdb;
		$table = $this->db->table( 'claim_charts' );

		$chart_data = $this->prepare_chart_data( $data );
		$chart_data['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->update(
			$table,
			$chart_data,
			[ 'id' => absint( $chart_id ) ]
		);

		if ( $result !== false ) {
			do_action( 'synpat_claim_chart_updated', $chart_id, $chart_data );
			return true;
		}

		return new WP_Error( 'update_failed', __( 'Failed to update claim chart', 'synpat-pro' ) );
	}

	/**
	 * Delete claim chart
	 */
	public function delete_claim_chart( $chart_id ) {
		global $wpdb;
		$table = $this->db->table( 'claim_charts' );

		$result = $wpdb->delete( $table, [ 'id' => absint( $chart_id ) ] );

		if ( $result ) {
			do_action( 'synpat_claim_chart_deleted', $chart_id );
			return true;
		}

		return false;
	}

	/**
	 * Get claim chart by ID
	 */
	public function get_claim_chart( $chart_id ) {
		return $this->db->get_claim_chart( $chart_id );
	}

	/**
	 * Get all claim charts for a patent
	 */
	public function get_patent_claim_charts( $patent_id ) {
		return $this->db->get_patent_claim_charts( $patent_id );
	}

	/**
	 * Prepare claim chart data for database
	 */
	private function prepare_chart_data( $data ) {
		return [
			'patent_id' => isset( $data['patent_id'] ) ? absint( $data['patent_id'] ) : 0,
			'portfolio_id' => isset( $data['portfolio_id'] ) ? absint( $data['portfolio_id'] ) : 0,
			'title' => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
			'patent_number' => isset( $data['patent_number'] ) ? sanitize_text_field( $data['patent_number'] ) : '',
			'claim_number' => isset( $data['claim_number'] ) ? sanitize_text_field( $data['claim_number'] ) : '',
			'claim_text' => isset( $data['claim_text'] ) ? wp_kses_post( $data['claim_text'] ) : '',
			'product_features' => isset( $data['product_features'] ) ? wp_kses_post( $data['product_features'] ) : '',
			'mapping' => isset( $data['mapping'] ) ? wp_json_encode( $data['mapping'] ) : '',
			'content' => isset( $data['content'] ) ? wp_kses_post( $data['content'] ) : '',
			'analysis' => isset( $data['analysis'] ) ? wp_kses_post( $data['analysis'] ) : '',
			'status' => isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'draft',
			'created_by' => get_current_user_id(),
			'created_at' => current_time( 'mysql' ),
		];
	}

	/**
	 * Render claim chart for display
	 */
	public function render_claim_chart( $content, $chart_id ) {
		$chart = $this->get_claim_chart( $chart_id );

		if ( ! $chart ) {
			return $content;
		}

		$mapping = json_decode( $chart->mapping, true );

		ob_start();
		include SYNPAT_PRO_ROOT . 'modules/experts/templates/claim-chart-display.php';
		return ob_get_clean();
	}

	/**
	 * Generate claim chart from patent claims
	 */
	public function generate_from_claims( $patent_id, $claim_numbers = [] ) {
		$patent = $this->db->get_patent( $patent_id );

		if ( ! $patent ) {
			return new WP_Error( 'patent_not_found', __( 'Patent not found', 'synpat-pro' ) );
		}

		$charts = [];

		foreach ( $claim_numbers as $claim_number ) {
			$claim_text = $this->extract_claim_text( $patent, $claim_number );

			if ( $claim_text ) {
				$chart_data = [
					'patent_id' => $patent_id,
					'title' => sprintf( __( 'Claim Chart for %s - Claim %s', 'synpat-pro' ), $patent->patent_number, $claim_number ),
					'patent_number' => $patent->patent_number,
					'claim_number' => $claim_number,
					'claim_text' => $claim_text,
					'status' => 'draft',
				];

				$chart_id = $this->create_claim_chart( $chart_data );
				
				if ( ! is_wp_error( $chart_id ) ) {
					$charts[] = $chart_id;
				}
			}
		}

		return $charts;
	}

	/**
	 * Extract specific claim text from patent
	 */
	private function extract_claim_text( $patent, $claim_number ) {
		$claims_text = isset( $patent->claims ) ? $patent->claims : '';
		
		// Find claim by number
		$pattern = '/\b' . preg_quote( $claim_number, '/' ) . '\.\s+(.+?)(?=\n\d+\.|$)/s';
		
		if ( preg_match( $pattern, $claims_text, $matches ) ) {
			return trim( $matches[1] );
		}

		return '';
	}

	/**
	 * Parse claim elements for mapping
	 */
	public function parse_claim_elements( $claim_text ) {
		// Split claim into elements (simplified parsing)
		$elements = [];
		
		// Split by semicolons and "wherein" clauses
		$parts = preg_split( '/[;,]|wherein|whereby/', $claim_text );
		
		foreach ( $parts as $index => $part ) {
			$part = trim( $part );
			if ( ! empty( $part ) ) {
				$elements[] = [
					'id' => 'element_' . ( $index + 1 ),
					'text' => $part,
					'mapping' => '',
				];
			}
		}

		return $elements;
	}

	/**
	 * AJAX handler to create claim chart
	 */
	public function ajax_create_claim_chart() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$data = isset( $_POST['chart_data'] ) ? json_decode( stripslashes( $_POST['chart_data'] ), true ) : [];

		if ( empty( $data ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid data', 'synpat-pro' ) ] );
		}

		$chart_id = $this->create_claim_chart( $data );

		if ( is_wp_error( $chart_id ) ) {
			wp_send_json_error( [ 'message' => $chart_id->get_error_message() ] );
		}

		wp_send_json_success( [
			'chart_id' => $chart_id,
			'message' => __( 'Claim chart created successfully', 'synpat-pro' ),
		] );
	}

	/**
	 * AJAX handler to update claim chart
	 */
	public function ajax_update_claim_chart() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$chart_id = isset( $_POST['chart_id'] ) ? absint( $_POST['chart_id'] ) : 0;
		$data = isset( $_POST['chart_data'] ) ? json_decode( stripslashes( $_POST['chart_data'] ), true ) : [];

		if ( ! $chart_id || empty( $data ) ) {
			wp_send_json_error( [ 'message' => __( 'Invalid data', 'synpat-pro' ) ] );
		}

		$result = $this->update_claim_chart( $chart_id, $data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( [ 'message' => __( 'Claim chart updated successfully', 'synpat-pro' ) ] );
	}

	/**
	 * AJAX handler to delete claim chart
	 */
	public function ajax_delete_claim_chart() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'delete_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$chart_id = isset( $_POST['chart_id'] ) ? absint( $_POST['chart_id'] ) : 0;

		if ( ! $chart_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid chart ID', 'synpat-pro' ) ] );
		}

		$result = $this->delete_claim_chart( $chart_id );

		if ( ! $result ) {
			wp_send_json_error( [ 'message' => __( 'Failed to delete claim chart', 'synpat-pro' ) ] );
		}

		wp_send_json_success( [ 'message' => __( 'Claim chart deleted successfully', 'synpat-pro' ) ] );
	}

	/**
	 * AJAX handler to get claim chart
	 */
	public function ajax_get_claim_chart() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'read' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$chart_id = isset( $_POST['chart_id'] ) ? absint( $_POST['chart_id'] ) : 0;

		if ( ! $chart_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid chart ID', 'synpat-pro' ) ] );
		}

		$chart = $this->get_claim_chart( $chart_id );

		if ( ! $chart ) {
			wp_send_json_error( [ 'message' => __( 'Claim chart not found', 'synpat-pro' ) ] );
		}

		wp_send_json_success( $chart );
	}
}
