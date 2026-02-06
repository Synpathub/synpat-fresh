<?php
/**
 * Batch Processor
 * Handle bulk operations on patents and portfolios
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Batch_Processor {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Batch size for processing
	 */
	private $batch_size = 50;

	/**
	 * Delay before processing batch job (seconds)
	 */
	const BATCH_JOB_DELAY = 60;

	/**
	 * Initialize batch processor
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'wp_ajax_synpat_batch_analyze', [ $this, 'ajax_batch_analyze' ] );
		add_action( 'wp_ajax_synpat_batch_export', [ $this, 'ajax_batch_export' ] );
		add_action( 'synpat_pro_batch_process', [ $this, 'process_batch_job' ] );
	}

	/**
	 * Process batch analysis on multiple patents
	 */
	public function batch_analyze_patents( $patent_ids ) {
		$results = [
			'total' => count( $patent_ids ),
			'processed' => 0,
			'failed' => 0,
			'errors' => [],
		];

		// Split into batches
		$batches = array_chunk( $patent_ids, $this->batch_size );

		foreach ( $batches as $batch_index => $batch ) {
			foreach ( $batch as $patent_id ) {
				$analyzer = new SynPat_Patent_Analyzer( $this->db );
				$analysis = $analyzer->analyze_patent( $patent_id );

				if ( is_wp_error( $analysis ) ) {
					$results['failed']++;
					$results['errors'][] = [
						'patent_id' => $patent_id,
						'error' => $analysis->get_error_message(),
					];
				} else {
					$results['processed']++;
				}
			}

			// Allow other processes to run
			if ( $batch_index < count( $batches ) - 1 ) {
				sleep( 1 );
			}
		}

		return $results;
	}

	/**
	 * Batch update patent metadata
	 */
	public function batch_update_metadata( $patent_ids, $metadata ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_patents';

		$results = [
			'total' => count( $patent_ids ),
			'updated' => 0,
			'failed' => 0,
		];

		foreach ( $patent_ids as $patent_id ) {
			$result = $wpdb->update(
				$table,
				$metadata,
				[ 'id' => absint( $patent_id ) ]
			);

			if ( $result !== false ) {
				$results['updated']++;
			} else {
				$results['failed']++;
			}
		}

		return $results;
	}

	/**
	 * Batch export patents to file
	 */
	public function batch_export_patents( $patent_ids, $format = 'csv' ) {
		$patents = [];

		foreach ( $patent_ids as $patent_id ) {
			$patent = $this->db->get_patent( $patent_id );
			if ( $patent ) {
				$patents[] = $patent;
			}
		}

		switch ( $format ) {
			case 'csv':
				return $this->export_to_csv( $patents );
			case 'json':
				return $this->export_to_json( $patents );
			case 'xml':
				return $this->export_to_xml( $patents );
			default:
				return new WP_Error( 'unsupported_format', __( 'Unsupported export format', 'synpat-pro' ) );
		}
	}

	/**
	 * Export patents to CSV
	 */
	private function export_to_csv( $patents ) {
		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/synpat-exports/';

		if ( ! file_exists( $export_dir ) ) {
			wp_mkdir_p( $export_dir );
		}

		$filename = 'patents-export-' . time() . '.csv';
		$file_path = $export_dir . $filename;

		$handle = fopen( $file_path, 'w' );

		if ( ! $handle ) {
			return new WP_Error( 'file_create_error', __( 'Cannot create export file', 'synpat-pro' ) );
		}

		// Write headers
		$headers = [ 'ID', 'Patent Number', 'Title', 'Abstract', 'Filing Date', 'Grant Date', 'Inventor', 'Assignee' ];
		fputcsv( $handle, $headers );

		// Write data
		foreach ( $patents as $patent ) {
			fputcsv( $handle, [
				$patent->id,
				$patent->patent_number,
				$patent->title,
				$patent->abstract,
				$patent->filing_date,
				$patent->grant_date,
				$patent->inventor,
				$patent->assignee,
			] );
		}

		fclose( $handle );

		return [
			'success' => true,
			'file_path' => $file_path,
			'file_url' => $upload_dir['baseurl'] . '/synpat-exports/' . $filename,
		];
	}

	/**
	 * Export patents to JSON
	 */
	private function export_to_json( $patents ) {
		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/synpat-exports/';

		if ( ! file_exists( $export_dir ) ) {
			wp_mkdir_p( $export_dir );
		}

		$filename = 'patents-export-' . time() . '.json';
		$file_path = $export_dir . $filename;

		$json = wp_json_encode( $patents, JSON_PRETTY_PRINT );

		if ( file_put_contents( $file_path, $json ) === false ) {
			return new WP_Error( 'file_write_error', __( 'Cannot write export file', 'synpat-pro' ) );
		}

		return [
			'success' => true,
			'file_path' => $file_path,
			'file_url' => $upload_dir['baseurl'] . '/synpat-exports/' . $filename,
		];
	}

	/**
	 * Export patents to XML
	 */
	private function export_to_xml( $patents ) {
		$xml = new SimpleXMLElement( '<patents></patents>' );

		foreach ( $patents as $patent ) {
			$patent_node = $xml->addChild( 'patent' );
			
			foreach ( $patent as $key => $value ) {
				$patent_node->addChild( $key, htmlspecialchars( $value ) );
			}
		}

		$upload_dir = wp_upload_dir();
		$export_dir = $upload_dir['basedir'] . '/synpat-exports/';

		if ( ! file_exists( $export_dir ) ) {
			wp_mkdir_p( $export_dir );
		}

		$filename = 'patents-export-' . time() . '.xml';
		$file_path = $export_dir . $filename;

		if ( $xml->asXML( $file_path ) === false ) {
			return new WP_Error( 'file_write_error', __( 'Cannot write XML file', 'synpat-pro' ) );
		}

		return [
			'success' => true,
			'file_path' => $file_path,
			'file_url' => $upload_dir['baseurl'] . '/synpat-exports/' . $filename,
		];
	}

	/**
	 * Schedule batch job for background processing
	 */
	public function schedule_batch_job( $job_type, $params ) {
		$job_id = wp_generate_uuid4();

		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'synpat_batch_jobs',
			[
				'job_id' => $job_id,
				'job_type' => $job_type,
				'params' => wp_json_encode( $params ),
				'status' => 'pending',
				'created_at' => current_time( 'mysql' ),
			]
		);

		// Schedule WP-Cron job
		wp_schedule_single_event( time() + self::BATCH_JOB_DELAY, 'synpat_pro_batch_process', [ $job_id ] );

		return $job_id;
	}

	/**
	 * Process scheduled batch job
	 */
	public function process_batch_job( $job_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_batch_jobs';

		$job = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE job_id = %s",
			$job_id
		) );

		if ( ! $job ) {
			return;
		}

		// Update status to processing
		$wpdb->update(
			$table,
			[ 'status' => 'processing', 'started_at' => current_time( 'mysql' ) ],
			[ 'job_id' => $job_id ]
		);

		$params = json_decode( $job->params, true );
		$result = null;

		// Execute job based on type
		switch ( $job->job_type ) {
			case 'analyze':
				$result = $this->batch_analyze_patents( $params['patent_ids'] );
				break;
			case 'export':
				$result = $this->batch_export_patents( $params['patent_ids'], $params['format'] );
				break;
		}

		// Update job status
		$wpdb->update(
			$table,
			[
				'status' => 'completed',
				'result' => wp_json_encode( $result ),
				'completed_at' => current_time( 'mysql' ),
			],
			[ 'job_id' => $job_id ]
		);
	}

	/**
	 * AJAX handler for batch analysis
	 */
	public function ajax_batch_analyze() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$patent_ids = isset( $_POST['patent_ids'] ) ? array_map( 'absint', $_POST['patent_ids'] ) : [];

		if ( empty( $patent_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No patents selected', 'synpat-pro' ) ] );
		}

		// Schedule batch job
		$job_id = $this->schedule_batch_job( 'analyze', [ 'patent_ids' => $patent_ids ] );

		wp_send_json_success( [
			'job_id' => $job_id,
			'message' => __( 'Batch analysis scheduled', 'synpat-pro' ),
		] );
	}

	/**
	 * AJAX handler for batch export
	 */
	public function ajax_batch_export() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$patent_ids = isset( $_POST['patent_ids'] ) ? array_map( 'absint', $_POST['patent_ids'] ) : [];
		$format = isset( $_POST['format'] ) ? sanitize_text_field( $_POST['format'] ) : 'csv';

		if ( empty( $patent_ids ) ) {
			wp_send_json_error( [ 'message' => __( 'No patents selected', 'synpat-pro' ) ] );
		}

		$result = $this->batch_export_patents( $patent_ids, $format );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}

		wp_send_json_success( $result );
	}
}
