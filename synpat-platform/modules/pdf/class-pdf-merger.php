<?php
/**
 * PDF Merger
 * Combines multiple PDF documents into a single file
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_PDF_Merger {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Temporary directory for PDF processing
	 */
	private $temp_dir;

	/**
	 * Initialize PDF merger
	 */
	public function __construct() {
		$this->db = new SynPat_Database();
		$this->setup_temp_directory();
	}

	/**
	 * Setup temporary directory for PDF operations
	 */
	private function setup_temp_directory() {
		$upload_dir = wp_upload_dir();
		$this->temp_dir = $upload_dir['basedir'] . '/synpat-pdfs/temp/';
		
		if ( ! file_exists( $this->temp_dir ) ) {
			wp_mkdir_p( $this->temp_dir );
		}
	}

	/**
	 * Merge multiple PDF files into one
	 *
	 * @param array $pdf_paths Array of file paths to merge
	 * @param string $output_path Output file path for merged PDF
	 * @return array|WP_Error Result with file path or error
	 */
	public function merge_pdfs( $pdf_paths, $output_path = '' ) {
		if ( empty( $pdf_paths ) || ! is_array( $pdf_paths ) ) {
			return new WP_Error( 'invalid_input', __( 'No PDF files provided', 'synpat-platform' ) );
		}

		// Validate all files exist
		foreach ( $pdf_paths as $path ) {
			if ( ! file_exists( $path ) ) {
				return new WP_Error( 'file_not_found', sprintf( __( 'File not found: %s', 'synpat-platform' ), basename( $path ) ) );
			}
		}

		// Generate output path if not provided
		if ( empty( $output_path ) ) {
			$upload_dir = wp_upload_dir();
			$filename = 'merged-' . time() . '.pdf';
			$output_path = $upload_dir['basedir'] . '/synpat-pdfs/' . $filename;
		}

		// Use PHP's built-in functionality or external library
		$result = $this->perform_merge( $pdf_paths, $output_path );

		if ( $result ) {
			do_action( 'synpat_pdfs_merged', $pdf_paths, $output_path );

			return [
				'success' => true,
				'file_path' => $output_path,
				'file_url' => str_replace( wp_upload_dir()['basedir'], wp_upload_dir()['baseurl'], $output_path ),
			];
		}

		return new WP_Error( 'merge_failed', __( 'Failed to merge PDF files', 'synpat-platform' ) );
	}

	/**
	 * Perform the actual PDF merge operation
	 *
	 * @param array $pdf_paths Paths to PDF files
	 * @param string $output_path Output file path
	 * @return bool Success status
	 */
	private function perform_merge( $pdf_paths, $output_path ) {
		// Check if FPDI library is available
		if ( ! class_exists( 'setasign\Fpdi\Fpdi' ) && ! class_exists( 'FPDI' ) ) {
			// Fallback: Use system command if available
			return $this->merge_using_system_command( $pdf_paths, $output_path );
		}

		try {
			// Use FPDI library for merging
			return $this->merge_using_fpdi( $pdf_paths, $output_path );
		} catch ( Exception $e ) {
			error_log( 'PDF merge error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Merge PDFs using FPDI library
	 *
	 * @param array $pdf_paths PDF file paths
	 * @param string $output_path Output file path
	 * @return bool Success status
	 */
	private function merge_using_fpdi( $pdf_paths, $output_path ) {
		// This would use FPDI/FPDF library - placeholder implementation
		// In production, you would use: setasign/fpdf and setasign/fpdi packages
		
		/**
		 * Example implementation with FPDI:
		 * 
		 * $pdf = new \setasign\Fpdi\Fpdi();
		 * 
		 * foreach ( $pdf_paths as $file ) {
		 *     $page_count = $pdf->setSourceFile( $file );
		 *     for ( $i = 1; $i <= $page_count; $i++ ) {
		 *         $template = $pdf->importPage( $i );
		 *         $pdf->AddPage();
		 *         $pdf->useTemplate( $template );
		 *     }
		 * }
		 * 
		 * $pdf->Output( $output_path, 'F' );
		 */

		// For now, return false to indicate library not available
		return apply_filters( 'synpat_pdf_merge_fpdi', false, $pdf_paths, $output_path );
	}

	/**
	 * Merge PDFs using system command (pdftk, ghostscript, etc.)
	 *
	 * @param array $pdf_paths PDF file paths
	 * @param string $output_path Output file path
	 * @return bool Success status
	 */
	private function merge_using_system_command( $pdf_paths, $output_path ) {
		// Check if ghostscript is available
		$gs_path = $this->find_ghostscript();
		
		if ( $gs_path ) {
			$files = implode( ' ', array_map( 'escapeshellarg', $pdf_paths ) );
			$output = escapeshellarg( $output_path );
			
			$command = sprintf(
				'%s -dBATCH -dNOPAUSE -q -sDEVICE=pdfwrite -sOutputFile=%s %s',
				$gs_path,
				$output,
				$files
			);
			
			exec( $command, $exec_output, $return_var );
			
			return $return_var === 0 && file_exists( $output_path );
		}

		return false;
	}

	/**
	 * Find ghostscript executable
	 *
	 * @return string|false Path to ghostscript or false
	 */
	private function find_ghostscript() {
		$possible_paths = [ 'gs', '/usr/bin/gs', '/usr/local/bin/gs' ];
		
		foreach ( $possible_paths as $path ) {
			exec( "command -v $path 2>/dev/null", $output, $return_var );
			if ( $return_var === 0 ) {
				return $path;
			}
		}
		
		return false;
	}

	/**
	 * Merge portfolio PDF with all its patent PDFs
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @param array $patent_ids Optional array of specific patent IDs to include
	 * @return array|WP_Error Result or error
	 */
	public function merge_portfolio_with_patents( $portfolio_id, $patent_ids = [] ) {
		$portfolio_id = absint( $portfolio_id );
		
		if ( ! $portfolio_id ) {
			return new WP_Error( 'invalid_portfolio', __( 'Invalid portfolio ID', 'synpat-platform' ) );
		}

		// Generate portfolio PDF
		$pdf_generator = new SynPat_PDF_Generator();
		$portfolio_pdf = $pdf_generator->generate_portfolio_pdf( $portfolio_id );
		
		if ( is_wp_error( $portfolio_pdf ) ) {
			return $portfolio_pdf;
		}

		$pdf_paths = [ $portfolio_pdf['file_path'] ];

		// Get patents to include
		if ( empty( $patent_ids ) ) {
			$patents = $this->db->get_portfolio_patents( $portfolio_id );
			$patent_ids = wp_list_pluck( $patents, 'id' );
		}

		// Generate PDF for each patent
		foreach ( $patent_ids as $patent_id ) {
			$patent_pdf = $pdf_generator->generate_patent_pdf( $patent_id );
			
			if ( ! is_wp_error( $patent_pdf ) ) {
				$pdf_paths[] = $patent_pdf['file_path'];
			}
		}

		// Merge all PDFs
		$upload_dir = wp_upload_dir();
		$filename = sanitize_file_name( 'portfolio-' . $portfolio_id . '-complete-' . time() . '.pdf' );
		$output_path = $upload_dir['basedir'] . '/synpat-pdfs/' . $filename;

		$result = $this->merge_pdfs( $pdf_paths, $output_path );

		// Clean up temporary PDFs
		foreach ( $pdf_paths as $temp_pdf ) {
			if ( file_exists( $temp_pdf ) ) {
				@unlink( $temp_pdf );
			}
		}

		return $result;
	}

	/**
	 * Clean up temporary files older than specified time
	 *
	 * @param int $max_age Maximum age in seconds (default 24 hours)
	 */
	public function cleanup_temp_files( $max_age = 86400 ) {
		$files = glob( $this->temp_dir . '*.pdf' );
		$current_time = time();
		
		foreach ( $files as $file ) {
			if ( is_file( $file ) && ( $current_time - filemtime( $file ) ) >= $max_age ) {
				@unlink( $file );
			}
		}
	}
}
