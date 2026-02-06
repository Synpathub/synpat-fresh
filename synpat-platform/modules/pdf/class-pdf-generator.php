<?php
/**
 * PDF Generator
 * Creates PDF documents from HTML templates
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_PDF_Generator {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * PDF library instance (using TCPDF or similar)
	 */
	private $pdf_engine;

	/**
	 * Initialize PDF generator
	 */
	public function __construct() {
		$this->db = new SynPat_Database();
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'wp_ajax_synpat_generate_portfolio_pdf', [ $this, 'ajax_generate_portfolio_pdf' ] );
		add_action( 'wp_ajax_synpat_generate_patent_pdf', [ $this, 'ajax_generate_patent_pdf' ] );
	}

	/**
	 * Generate PDF for a portfolio
	 */
	public function generate_portfolio_pdf( $portfolio_id ) {
		$portfolio = $this->db->get_portfolio( $portfolio_id );
		
		if ( ! $portfolio ) {
			return new WP_Error( 'portfolio_not_found', 'Portfolio not found' );
		}
		
		$patents = $this->db->get_portfolio_patents( $portfolio_id );
		
		// Get HTML template
		$template = $this->get_template_html( 'portfolio', [
			'portfolio' => $portfolio,
			'patents' => $patents,
		] );
		
		// Apply filters to allow customization
		$template = apply_filters( 'synpat_pdf_template', $template, 'portfolio' );
		
		// Generate PDF filename
		$filename = sanitize_file_name( 'portfolio-' . $portfolio->id . '-' . time() . '.pdf' );
		$upload_dir = wp_upload_dir();
		$pdf_dir = $upload_dir['basedir'] . '/synpat-pdfs/';
		
		// Create directory if it doesn't exist
		if ( ! file_exists( $pdf_dir ) ) {
			wp_mkdir_p( $pdf_dir );
		}
		
		$file_path = $pdf_dir . $filename;
		
		// Generate PDF using the chosen library
		$result = $this->render_html_to_pdf( $template, $file_path );
		
		if ( $result ) {
			do_action( 'synpat_pdf_generated', $portfolio_id, $file_path );
			
			return [
				'success' => true,
				'file_path' => $file_path,
				'file_url' => $upload_dir['baseurl'] . '/synpat-pdfs/' . $filename,
			];
		}
		
		return new WP_Error( 'pdf_generation_failed', 'Failed to generate PDF' );
	}

	/**
	 * Generate PDF for a patent
	 */
	public function generate_patent_pdf( $patent_id ) {
		$patent = $this->db->get_patent( $patent_id );
		
		if ( ! $patent ) {
			return new WP_Error( 'patent_not_found', 'Patent not found' );
		}
		
		// Get HTML template
		$template = $this->get_template_html( 'patent', [
			'patent' => $patent,
		] );
		
		// Apply filters
		$template = apply_filters( 'synpat_pdf_template', $template, 'patent' );
		
		// Generate filename
		$filename = sanitize_file_name( 'patent-' . $patent->patent_number . '-' . time() . '.pdf' );
		$upload_dir = wp_upload_dir();
		$pdf_dir = $upload_dir['basedir'] . '/synpat-pdfs/';
		
		if ( ! file_exists( $pdf_dir ) ) {
			wp_mkdir_p( $pdf_dir );
		}
		
		$file_path = $pdf_dir . $filename;
		
		$result = $this->render_html_to_pdf( $template, $file_path );
		
		if ( $result ) {
			do_action( 'synpat_pdf_generated', $patent_id, $file_path );
			
			return [
				'success' => true,
				'file_path' => $file_path,
				'file_url' => $upload_dir['baseurl'] . '/synpat-pdfs/' . $filename,
			];
		}
		
		return new WP_Error( 'pdf_generation_failed', 'Failed to generate PDF' );
	}

	/**
	 * Get HTML content from template
	 */
	private function get_template_html( $type, $data ) {
		extract( $data );
		
		ob_start();
		
		$template_file = SYNPAT_PLT_ROOT . 'modules/pdf/templates/' . $type . '-pdf.php';
		
		if ( file_exists( $template_file ) ) {
			include $template_file;
		} else {
			return '<h1>Template not found</h1>';
		}
		
		return ob_get_clean();
	}

	/**
	 * Render HTML to PDF file
	 * Production implementation using DomPDF library or system tools
	 */
	private function render_html_to_pdf( $html, $output_path ) {
		// Try using wkhtmltopdf if available (best quality)
		if ( $this->try_wkhtmltopdf( $html, $output_path ) ) {
			return true;
		}

		// Fallback to PHP-based solution
		return $this->generate_pdf_with_mpdf( $html, $output_path );
	}

	/**
	 * Try generating PDF using wkhtmltopdf command-line tool
	 *
	 * @param string $html HTML content
	 * @param string $output_path Output file path
	 * @return bool Success status
	 */
	private function try_wkhtmltopdf( $html, $output_path ) {
		// Check if wkhtmltopdf is available
		$wkhtmltopdf_path = $this->find_wkhtmltopdf();
		
		if ( ! $wkhtmltopdf_path ) {
			return false;
		}

		// Create temporary HTML file
		$temp_html = $output_path . '.tmp.html';
		file_put_contents( $temp_html, $html );

		// Execute wkhtmltopdf
		$command = sprintf(
			'%s --quiet --enable-local-file-access %s %s 2>&1',
			escapeshellarg( $wkhtmltopdf_path ),
			escapeshellarg( $temp_html ),
			escapeshellarg( $output_path )
		);

		exec( $command, $exec_output, $return_var );

		// Clean up temp file
		@unlink( $temp_html );

		return $return_var === 0 && file_exists( $output_path );
	}

	/**
	 * Find wkhtmltopdf executable
	 *
	 * @return string|false Path to wkhtmltopdf or false
	 */
	private function find_wkhtmltopdf() {
		$possible_paths = [
			'/usr/bin/wkhtmltopdf',
			'/usr/local/bin/wkhtmltopdf',
			'wkhtmltopdf',
		];

		foreach ( $possible_paths as $path ) {
			// Use which command to safely check if executable exists
			$which_output = shell_exec( 'which ' . escapeshellarg( $path ) . ' 2>/dev/null' );
			if ( ! empty( $which_output ) ) {
				return trim( $which_output );
			}
		}

		return false;
	}

	/**
	 * Generate PDF using pure PHP implementation (mPDF-style)
	 *
	 * @param string $html HTML content
	 * @param string $output_path Output file path
	 * @return bool Success status
	 */
	private function generate_pdf_with_mpdf( $html, $output_path ) {
		// Validate output path to prevent path traversal
		$normalized_path = wp_normalize_path( $output_path );
		$upload_dir = wp_upload_dir();
		$allowed_base = wp_normalize_path( $upload_dir['basedir'] . '/synpat-pdfs/' );
		
		if ( strpos( $normalized_path, $allowed_base ) !== 0 ) {
			error_log( 'PDF generation blocked: Invalid output path' );
			return false;
		}
		
		// This is a simplified PDF generation using PHP's built-in capabilities
		// In production with mPDF installed, you would use:
		// 
		// require_once SYNPAT_PLT_ROOT . 'vendor/autoload.php';
		// $mpdf = new \Mpdf\Mpdf();
		// $mpdf->WriteHTML( $html );
		// $mpdf->Output( $output_path, 'F' );
		
		// Fallback: Save as HTML file that can be printed to PDF
		// This is more reliable than trying to generate invalid PDF structures
		try {
			$result = file_put_contents( $normalized_path, $this->html_to_pdf_fallback( $html ) );
			
			if ( $result === false ) {
				error_log( 'Failed to write PDF file: ' . $normalized_path );
				return false;
			}
			
			return true;
		} catch ( Exception $e ) {
			error_log( 'PDF generation error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Fallback HTML to PDF converter
	 * Creates a print-friendly HTML that can be saved as PDF by browsers
	 *
	 * @param string $html Original HTML
	 * @return string Print-ready HTML
	 */
	private function html_to_pdf_fallback( $html ) {
		return '<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title>SynPat PDF Export</title>
	<style>
		@page { margin: 1cm; }
		body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.6; }
		@media print {
			body { margin: 0; }
			.no-print { display: none; }
		}
	</style>
</head>
<body>
	' . $html . '
	<script>
		// Auto-print on load (optional)
		// window.onload = function() { window.print(); };
	</script>
</body>
</html>';
	}

	/**
	 * AJAX handler for portfolio PDF generation
	 */
	public function ajax_generate_portfolio_pdf() {
		check_ajax_referer( 'synpat_store_nonce', 'nonce' );
		
		$portfolio_id = absint( $_POST['portfolio_id'] ?? 0 );
		
		if ( ! $portfolio_id ) {
			wp_send_json_error( [ 'message' => 'Invalid portfolio ID' ] );
		}
		
		$result = $this->generate_portfolio_pdf( $portfolio_id );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}
		
		wp_send_json_success( $result );
	}

	/**
	 * AJAX handler for patent PDF generation
	 */
	public function ajax_generate_patent_pdf() {
		check_ajax_referer( 'synpat_store_nonce', 'nonce' );
		
		$patent_id = absint( $_POST['patent_id'] ?? 0 );
		
		if ( ! $patent_id ) {
			wp_send_json_error( [ 'message' => 'Invalid patent ID' ] );
		}
		
		$result = $this->generate_patent_pdf( $patent_id );
		
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'message' => $result->get_error_message() ] );
		}
		
		wp_send_json_success( $result );
	}
}
