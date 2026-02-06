<?php
/**
 * Data Import Module
 * Import patent data from external sources and databases
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Data_Import {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Supported import sources
	 */
	private $sources = [ 'csv', 'json', 'xml', 'api' ];

	/**
	 * Initialize data import
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'wp_ajax_synpat_import_data', [ $this, 'ajax_import_data' ] );
		add_action( 'wp_ajax_synpat_validate_import', [ $this, 'ajax_validate_import' ] );
	}

	/**
	 * Import data from file
	 */
	public function import_from_file( $file_path, $format = 'csv' ) {
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', __( 'Import file not found', 'synpat-pro' ) );
		}

		$data = $this->parse_file( $file_path, $format );

		if ( is_wp_error( $data ) ) {
			return $data;
		}

		return $this->process_import( $data );
	}

	/**
	 * Parse import file
	 */
	private function parse_file( $file_path, $format ) {
		switch ( $format ) {
			case 'csv':
				return $this->parse_csv( $file_path );
			case 'json':
				return $this->parse_json( $file_path );
			case 'xml':
				return $this->parse_xml( $file_path );
			default:
				return new WP_Error( 'unsupported_format', __( 'Unsupported file format', 'synpat-pro' ) );
		}
	}

	/**
	 * Parse CSV file
	 */
	private function parse_csv( $file_path ) {
		$data = [];
		$handle = fopen( $file_path, 'r' );

		if ( ! $handle ) {
			return new WP_Error( 'file_read_error', __( 'Cannot read CSV file', 'synpat-pro' ) );
		}

		// Get headers
		$headers = fgetcsv( $handle );

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			if ( count( $row ) === count( $headers ) ) {
				$data[] = array_combine( $headers, $row );
			}
		}

		fclose( $handle );

		return $data;
	}

	/**
	 * Parse JSON file
	 */
	private function parse_json( $file_path ) {
		$content = file_get_contents( $file_path );
		$data = json_decode( $content, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error( 'json_parse_error', __( 'Invalid JSON file', 'synpat-pro' ) );
		}

		return $data;
	}

	/**
	 * Parse XML file
	 */
	private function parse_xml( $file_path ) {
		libxml_use_internal_errors( true );
		$xml = simplexml_load_file( $file_path );

		if ( ! $xml ) {
			return new WP_Error( 'xml_parse_error', __( 'Invalid XML file', 'synpat-pro' ) );
		}

		// Convert to array
		$json = wp_json_encode( $xml );
		return json_decode( $json, true );
	}

	/**
	 * Process and import data
	 */
	private function process_import( $data ) {
		$results = [
			'total' => count( $data ),
			'imported' => 0,
			'skipped' => 0,
			'errors' => [],
		];

		foreach ( $data as $index => $record ) {
			$result = $this->import_record( $record );

			if ( is_wp_error( $result ) ) {
				$results['errors'][] = [
					'row' => $index + 1,
					'error' => $result->get_error_message(),
				];
				$results['skipped']++;
			} else {
				$results['imported']++;
			}
		}

		// Log import results
		$this->log_import( $results );

		return $results;
	}

	/**
	 * Import single record
	 */
	private function import_record( $record ) {
		// Validate required fields
		$validation = $this->validate_record( $record );

		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		// Map fields to database structure
		$patent_data = $this->map_fields( $record );

		// Check if patent already exists
		$existing = $this->find_existing_patent( $patent_data );

		if ( $existing ) {
			// Update existing patent
			return $this->update_patent( $existing->id, $patent_data );
		} else {
			// Create new patent
			return $this->create_patent( $patent_data );
		}
	}

	/**
	 * Validate import record
	 */
	private function validate_record( $record ) {
		$required_fields = [ 'patent_number', 'title' ];

		foreach ( $required_fields as $field ) {
			if ( empty( $record[ $field ] ) ) {
				return new WP_Error( 'missing_field', sprintf( __( 'Missing required field: %s', 'synpat-pro' ), $field ) );
			}
		}

		// Validate patent number format
		if ( ! preg_match( '/^[A-Z]{2}\d+/', $record['patent_number'] ) ) {
			return new WP_Error( 'invalid_format', __( 'Invalid patent number format', 'synpat-pro' ) );
		}

		return true;
	}

	/**
	 * Map import fields to database structure
	 */
	private function map_fields( $record ) {
		return [
			'patent_number' => sanitize_text_field( $record['patent_number'] ),
			'title' => sanitize_text_field( $record['title'] ),
			'abstract' => isset( $record['abstract'] ) ? wp_kses_post( $record['abstract'] ) : '',
			'filing_date' => isset( $record['filing_date'] ) ? sanitize_text_field( $record['filing_date'] ) : '',
			'grant_date' => isset( $record['grant_date'] ) ? sanitize_text_field( $record['grant_date'] ) : '',
			'inventor' => isset( $record['inventor'] ) ? sanitize_text_field( $record['inventor'] ) : '',
			'assignee' => isset( $record['assignee'] ) ? sanitize_text_field( $record['assignee'] ) : '',
			'classification' => isset( $record['classification'] ) ? sanitize_text_field( $record['classification'] ) : '',
			'claims' => isset( $record['claims'] ) ? wp_kses_post( $record['claims'] ) : '',
		];
	}

	/**
	 * Find existing patent by number
	 */
	private function find_existing_patent( $patent_data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_patents';

		return $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE patent_number = %s",
			$patent_data['patent_number']
		) );
	}

	/**
	 * Create new patent
	 */
	private function create_patent( $patent_data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_patents';

		$patent_data['created_at'] = current_time( 'mysql' );

		$result = $wpdb->insert( $table, $patent_data );

		if ( $result ) {
			return $wpdb->insert_id;
		}

		return new WP_Error( 'insert_failed', __( 'Failed to insert patent', 'synpat-pro' ) );
	}

	/**
	 * Update existing patent
	 */
	private function update_patent( $patent_id, $patent_data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_patents';

		$patent_data['updated_at'] = current_time( 'mysql' );

		$result = $wpdb->update(
			$table,
			$patent_data,
			[ 'id' => absint( $patent_id ) ]
		);

		if ( $result !== false ) {
			return $patent_id;
		}

		return new WP_Error( 'update_failed', __( 'Failed to update patent', 'synpat-pro' ) );
	}

	/**
	 * Import from external API
	 */
	public function import_from_api( $patent_number, $api_source = 'uspto' ) {
		$api_data = $this->fetch_from_api( $patent_number, $api_source );

		if ( is_wp_error( $api_data ) ) {
			return $api_data;
		}

		return $this->import_record( $api_data );
	}

	/**
	 * Fetch data from external API
	 */
	private function fetch_from_api( $patent_number, $api_source ) {
		switch ( $api_source ) {
			case 'uspto':
				return $this->fetch_uspto_data( $patent_number );
			case 'epo':
				return $this->fetch_epo_data( $patent_number );
			default:
				return new WP_Error( 'unsupported_api', __( 'Unsupported API source', 'synpat-pro' ) );
		}
	}

	/**
	 * Fetch data from USPTO API
	 */
	private function fetch_uspto_data( $patent_number ) {
		// Placeholder for USPTO API integration
		// In production, this would make actual API calls
		return new WP_Error( 'not_implemented', __( 'USPTO API integration not yet implemented', 'synpat-pro' ) );
	}

	/**
	 * Fetch data from EPO API
	 */
	private function fetch_epo_data( $patent_number ) {
		// Placeholder for EPO API integration
		return new WP_Error( 'not_implemented', __( 'EPO API integration not yet implemented', 'synpat-pro' ) );
	}

	/**
	 * Log import results
	 */
	private function log_import( $results ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'synpat_import_logs',
			[
				'total_records' => $results['total'],
				'imported' => $results['imported'],
				'skipped' => $results['skipped'],
				'errors' => wp_json_encode( $results['errors'] ),
				'imported_at' => current_time( 'mysql' ),
				'user_id' => get_current_user_id(),
			]
		);
	}

	/**
	 * AJAX handler for data import
	 */
	public function ajax_import_data() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		if ( empty( $_FILES['import_file'] ) ) {
			wp_send_json_error( [ 'message' => __( 'No file uploaded', 'synpat-pro' ) ] );
		}

		$file = $_FILES['import_file'];
		$format = isset( $_POST['format'] ) ? sanitize_text_field( $_POST['format'] ) : 'csv';

		// Handle file upload
		$upload = wp_handle_upload( $file, [ 'test_form' => false ] );

		if ( isset( $upload['error'] ) ) {
			wp_send_json_error( [ 'message' => $upload['error'] ] );
		}

		$results = $this->import_from_file( $upload['file'], $format );

		if ( is_wp_error( $results ) ) {
			wp_send_json_error( [ 'message' => $results->get_error_message() ] );
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler for import validation
	 */
	public function ajax_validate_import() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$record = isset( $_POST['record'] ) ? json_decode( stripslashes( $_POST['record'] ), true ) : [];

		$validation = $this->validate_record( $record );

		if ( is_wp_error( $validation ) ) {
			wp_send_json_error( [ 'message' => $validation->get_error_message() ] );
		}

		wp_send_json_success( [ 'valid' => true ] );
	}
}
