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
	 * This is a placeholder - actual implementation would use TCPDF, mPDF, or Dompdf
	 */
	private function render_html_to_pdf( $html, $output_path ) {
		// For production, you would use a library like:
		// - TCPDF (included in WordPress)
		// - mPDF
		// - Dompdf
		
		// Placeholder implementation
		// In real scenario, this would initialize the PDF library and generate the file
		
		/*
		Example with TCPDF:
		require_once( ABSPATH . 'wp-includes/class-phpass.php' );
		require_once( 'path/to/tcpdf/tcpdf.php' );
		
		$pdf = new TCPDF();
		$pdf->AddPage();
		$pdf->writeHTML( $html, true, false, true, false, '' );
		$pdf->Output( $output_path, 'F' );
		*/
		
		// For now, save HTML as a file for demonstration
		return file_put_contents( $output_path . '.html', $html );
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
