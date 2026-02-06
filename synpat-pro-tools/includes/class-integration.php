<?php
/**
 * Platform Integration Handler
 * Manages integration with SynPat Platform core plugin
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Pro_Integration {

	/**
	 * Platform database instance
	 */
	private $db;

	/**
	 * Platform hooks registry
	 */
	private $hooks;

	/**
	 * Initialize integration
	 */
	public function __construct() {
		$this->check_platform_loaded();
		$this->setup_integration();
		$this->register_platform_hooks();
	}

	/**
	 * Verify platform is loaded
	 */
	private function check_platform_loaded() {
		if ( ! function_exists( 'synpat_platform' ) && ! class_exists( 'SynPat_Platform' ) ) {
			add_action( 'admin_notices', [ $this, 'platform_missing_notice' ] );
			return false;
		}
		return true;
	}

	/**
	 * Display admin notice if platform not found
	 */
	public function platform_missing_notice() {
		?>
		<div class="notice notice-error">
			<p>
				<?php esc_html_e( 'SynPat Pro Tools requires SynPat Platform to be installed and activated.', 'synpat-pro' ); ?>
			</p>
		</div>
		<?php
	}

	/**
	 * Setup integration with platform
	 */
	private function setup_integration() {
		// Get platform database instance
		if ( class_exists( 'SynPat_Database' ) ) {
			$this->db = new SynPat_Database();
		}

		// Get platform hooks instance
		if ( class_exists( 'SynPat_Hooks' ) ) {
			$this->hooks = new SynPat_Hooks();
		}
	}

	/**
	 * Register with platform hook system
	 */
	private function register_platform_hooks() {
		// Listen to platform events
		add_action( 'synpat_platform_loaded', [ $this, 'on_platform_loaded' ] );
		add_action( 'synpat_portfolio_created', [ $this, 'on_portfolio_created' ] );
		add_action( 'synpat_pdf_generated', [ $this, 'on_pdf_generated' ], 10, 2 );

		// Register custom filters
		add_filter( 'synpat_render_claim_chart', [ $this, 'render_claim_chart' ], 10, 2 );
		add_filter( 'synpat_render_prior_art', [ $this, 'render_prior_art' ], 10, 2 );
		add_filter( 'synpat_get_portfolio', [ $this, 'enhance_portfolio_data' ], 10, 2 );
	}

	/**
	 * Handle platform loaded event
	 */
	public function on_platform_loaded( $platform ) {
		// Platform is ready, perform any initialization
		do_action( 'synpat_pro_tools_ready', $platform );
	}

	/**
	 * Handle portfolio creation
	 */
	public function on_portfolio_created( $portfolio_id ) {
		// Initialize pro tools data for new portfolio
		$this->initialize_portfolio_pro_data( $portfolio_id );
	}

	/**
	 * Initialize pro tools data for portfolio
	 */
	private function initialize_portfolio_pro_data( $portfolio_id ) {
		global $wpdb;

		// Add metadata for pro tools tracking
		$wpdb->insert(
			$wpdb->prefix . 'synpat_expert_analysis',
			[
				'portfolio_id' => absint( $portfolio_id ),
				'analysis_status' => 'pending',
				'created_at' => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Handle PDF generated event
	 */
	public function on_pdf_generated( $pdf_id, $file_path ) {
		// Log PDF generation for analytics
		do_action( 'synpat_pro_pdf_logged', $pdf_id, $file_path );
	}

	/**
	 * Render claim chart with pro tools enhancements
	 */
	public function render_claim_chart( $content, $chart_id ) {
		$claim_chart = $this->db->get_claim_chart( $chart_id );

		if ( ! $claim_chart ) {
			return $content;
		}

		// Enhanced rendering with pro tools
		ob_start();
		?>
		<div class="synpat-claim-chart-pro">
			<div class="claim-chart-header">
				<h3><?php echo esc_html( $claim_chart->title ); ?></h3>
				<div class="claim-chart-meta">
					<span class="patent-number"><?php echo esc_html( $claim_chart->patent_number ); ?></span>
					<span class="claim-number"><?php echo esc_html( $claim_chart->claim_number ); ?></span>
				</div>
			</div>
			<div class="claim-chart-content">
				<?php echo wp_kses_post( $claim_chart->content ); ?>
			</div>
			<div class="claim-chart-analysis">
				<?php echo wp_kses_post( $claim_chart->analysis ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Render prior art report with pro tools enhancements
	 */
	public function render_prior_art( $content, $report_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_prior_art_reports';

		$report = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$report_id
		) );

		if ( ! $report ) {
			return $content;
		}

		// Enhanced rendering
		ob_start();
		?>
		<div class="synpat-prior-art-pro">
			<div class="prior-art-header">
				<h3><?php echo esc_html( $report->title ); ?></h3>
				<div class="prior-art-meta">
					<span class="target-patent"><?php echo esc_html( $report->target_patent ); ?></span>
					<span class="report-date"><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $report->created_at ) ) ); ?></span>
				</div>
			</div>
			<div class="prior-art-references">
				<?php echo wp_kses_post( $report->references ); ?>
			</div>
			<div class="prior-art-analysis">
				<?php echo wp_kses_post( $report->analysis ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Enhance portfolio data with pro tools information
	 */
	public function enhance_portfolio_data( $portfolio, $portfolio_id ) {
		if ( ! $portfolio ) {
			return $portfolio;
		}

		global $wpdb;

		// Add expert analysis status
		$analysis = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$wpdb->prefix}synpat_expert_analysis WHERE portfolio_id = %d",
			$portfolio_id
		) );

		if ( $analysis ) {
			$portfolio->expert_analysis = $analysis;
		}

		// Add claim charts count
		$claim_charts_count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}synpat_claim_charts WHERE portfolio_id = %d",
			$portfolio_id
		) );

		$portfolio->claim_charts_count = absint( $claim_charts_count );

		return $portfolio;
	}

	/**
	 * Get platform database instance
	 */
	public function get_database() {
		return $this->db;
	}

	/**
	 * Get platform hooks instance
	 */
	public function get_hooks() {
		return $this->hooks;
	}
}
