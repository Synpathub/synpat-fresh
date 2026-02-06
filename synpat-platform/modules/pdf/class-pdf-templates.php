<?php
/**
 * PDF Template Manager
 * Handles loading and rendering of PDF templates
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_PDF_Templates {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Templates directory path
	 */
	private $template_dir;

	/**
	 * Initialize template manager
	 */
	public function __construct() {
		$this->db = new SynPat_Database();
		$this->template_dir = SYNPAT_PLT_ROOT . 'modules/pdf/templates/';
	}

	/**
	 * Get HTML content for a template
	 *
	 * @param string $template_name Template name (without .php extension)
	 * @param array $data Data to pass to template
	 * @return string Template HTML content
	 */
	public function get_template_html( $template_name, $data = [] ) {
		$template_file = $this->locate_template( $template_name );
		
		if ( ! $template_file ) {
			return '';
		}

		// Extract data to make variables available in template
		extract( $data );

		// Start output buffering
		ob_start();
		
		// Include template file
		include $template_file;
		
		// Get buffered content
		$html = ob_get_clean();

		// Apply filters to allow customization
		return apply_filters( 'synpat_pdf_template_html', $html, $template_name, $data );
	}

	/**
	 * Locate a template file
	 *
	 * @param string $template_name Template name
	 * @return string|false Template file path or false
	 */
	private function locate_template( $template_name ) {
		// Allow theme override
		$theme_template = locate_template( [
			'synpat-platform/pdf/' . $template_name . '.php',
		] );

		if ( $theme_template ) {
			return $theme_template;
		}

		// Use plugin template
		$plugin_template = $this->template_dir . $template_name . '.php';
		
		if ( file_exists( $plugin_template ) ) {
			return $plugin_template;
		}

		return false;
	}

	/**
	 * Get CSS styles for PDF templates
	 *
	 * @return string CSS content
	 */
	public function get_pdf_styles() {
		ob_start();
		?>
		<style>
			body {
				font-family: 'Helvetica', 'Arial', sans-serif;
				font-size: 12pt;
				line-height: 1.6;
				color: #333;
				margin: 0;
				padding: 20px;
			}
			
			h1 {
				font-size: 24pt;
				color: #2c3e50;
				margin: 0 0 20px 0;
				padding-bottom: 10px;
				border-bottom: 2px solid #3498db;
			}
			
			h2 {
				font-size: 18pt;
				color: #2c3e50;
				margin: 20px 0 10px 0;
			}
			
			h3 {
				font-size: 14pt;
				color: #34495e;
				margin: 15px 0 8px 0;
			}
			
			.header {
				text-align: center;
				margin-bottom: 30px;
			}
			
			.logo {
				max-width: 200px;
				margin-bottom: 10px;
			}
			
			.section {
				margin-bottom: 25px;
				page-break-inside: avoid;
			}
			
			.metadata {
				background: #f8f9fa;
				padding: 15px;
				border-left: 4px solid #3498db;
				margin-bottom: 20px;
			}
			
			.metadata-item {
				margin: 5px 0;
			}
			
			.metadata-label {
				font-weight: bold;
				display: inline-block;
				width: 150px;
			}
			
			table {
				width: 100%;
				border-collapse: collapse;
				margin: 15px 0;
			}
			
			table th {
				background: #3498db;
				color: white;
				padding: 10px;
				text-align: left;
				font-weight: bold;
			}
			
			table td {
				padding: 8px;
				border-bottom: 1px solid #ddd;
			}
			
			table tr:nth-child(even) {
				background: #f8f9fa;
			}
			
			.footer {
				position: fixed;
				bottom: 0;
				left: 0;
				right: 0;
				text-align: center;
				font-size: 10pt;
				color: #7f8c8d;
				padding: 10px;
				border-top: 1px solid #ddd;
			}
			
			.page-break {
				page-break-after: always;
			}
			
			.claim-text {
				background: #fff;
				padding: 15px;
				border: 1px solid #ddd;
				margin: 10px 0;
			}
			
			.highlight {
				background: #fff3cd;
				padding: 2px 4px;
			}
			
			.note {
				background: #e3f2fd;
				padding: 10px;
				border-left: 4px solid #2196f3;
				margin: 10px 0;
				font-style: italic;
			}
		</style>
		<?php
		$styles = ob_get_clean();
		
		return apply_filters( 'synpat_pdf_styles', $styles );
	}

	/**
	 * Render portfolio PDF template
	 *
	 * @param int $portfolio_id Portfolio ID
	 * @return string HTML content
	 */
	public function render_portfolio_pdf( $portfolio_id ) {
		$portfolio = $this->db->get_portfolio( $portfolio_id );
		
		if ( ! $portfolio ) {
			return '';
		}

		$patents = $this->db->get_portfolio_patents( $portfolio_id );

		return $this->get_template_html( 'portfolio-pdf', [
			'portfolio' => $portfolio,
			'patents' => $patents,
			'styles' => $this->get_pdf_styles(),
		] );
	}

	/**
	 * Render patent detail PDF template
	 *
	 * @param int $patent_id Patent ID
	 * @return string HTML content
	 */
	public function render_patent_pdf( $patent_id ) {
		$patent = $this->db->get_patent( $patent_id );
		
		if ( ! $patent ) {
			return '';
		}

		$claim_charts = $this->db->get_patent_claim_charts( $patent_id );

		return $this->get_template_html( 'patent-detail-pdf', [
			'patent' => $patent,
			'claim_charts' => $claim_charts,
			'styles' => $this->get_pdf_styles(),
		] );
	}

	/**
	 * Render claim chart PDF template
	 *
	 * @param int $chart_id Claim chart ID
	 * @return string HTML content
	 */
	public function render_claim_chart_pdf( $chart_id ) {
		$claim_chart = $this->db->get_claim_chart( $chart_id );
		
		if ( ! $claim_chart ) {
			return '';
		}

		return $this->get_template_html( 'claim-chart-pdf', [
			'claim_chart' => $claim_chart,
			'styles' => $this->get_pdf_styles(),
		] );
	}

	/**
	 * Generate PDF header
	 *
	 * @param string $title Document title
	 * @return string HTML header
	 */
	public function generate_header( $title ) {
		ob_start();
		?>
		<div class="header">
			<?php
			$logo_url = apply_filters( 'synpat_pdf_logo_url', '' );
			if ( $logo_url ) :
			?>
				<img src="<?php echo esc_url( $logo_url ); ?>" alt="Logo" class="logo" />
			<?php endif; ?>
			
			<h1><?php echo esc_html( $title ); ?></h1>
			
			<div class="generated-date">
				<?php
				printf(
					esc_html__( 'Generated: %s', 'synpat-platform' ),
					date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) )
				);
				?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Generate PDF footer
	 *
	 * @return string HTML footer
	 */
	public function generate_footer() {
		ob_start();
		?>
		<div class="footer">
			<?php
			$footer_text = apply_filters(
				'synpat_pdf_footer_text',
				sprintf(
					esc_html__( 'Confidential - Generated by %s', 'synpat-platform' ),
					get_bloginfo( 'name' )
				)
			);
			echo esc_html( $footer_text );
			?>
		</div>
		<?php
		return ob_get_clean();
	}
}
