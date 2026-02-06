<?php
/**
 * SynPat Pro Tools - Main Addon Class
 * Integrates professional tools with the SynPat Platform
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Pro_Tools {

	/**
	 * Singleton instance
	 */
	private static $instance = null;

	/**
	 * Database handler from main platform
	 */
	private $db;

	/**
	 * Integration manager
	 */
	private $integration;

	/**
	 * Loaded modules
	 */
	private $modules = [];

	/**
	 * Get singleton instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize Pro Tools addon
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->initialize_integration();
		$this->load_modules();
		$this->register_hooks();
	}

	/**
	 * Load required files
	 */
	private function load_dependencies() {
		// Core integration
		require_once SYNPAT_PRO_ROOT . 'includes/class-integration.php';

		// Backyard module
		require_once SYNPAT_PRO_ROOT . 'modules/backyard/class-patent-analyzer.php';
		require_once SYNPAT_PRO_ROOT . 'modules/backyard/class-data-import.php';
		require_once SYNPAT_PRO_ROOT . 'modules/backyard/class-batch-processor.php';
		require_once SYNPAT_PRO_ROOT . 'modules/backyard/controllers/class-analysis-controller.php';

		// Experts module
		require_once SYNPAT_PRO_ROOT . 'modules/experts/class-claim-chart.php';
		require_once SYNPAT_PRO_ROOT . 'modules/experts/class-prior-art.php';
		require_once SYNPAT_PRO_ROOT . 'modules/experts/class-expert-editor.php';

		// Admin module
		require_once SYNPAT_PRO_ROOT . 'modules/admin/class-user-management.php';
		require_once SYNPAT_PRO_ROOT . 'modules/admin/class-system-config.php';
		require_once SYNPAT_PRO_ROOT . 'modules/admin/class-reporting.php';
	}

	/**
	 * Initialize platform integration
	 */
	private function initialize_integration() {
		$this->integration = new SynPat_Pro_Integration();
		$this->db = $this->integration->get_database();
	}

	/**
	 * Load and initialize all modules
	 */
	private function load_modules() {
		// Backyard module
		$this->modules['patent_analyzer'] = new SynPat_Patent_Analyzer( $this->db );
		$this->modules['data_import'] = new SynPat_Data_Import( $this->db );
		$this->modules['batch_processor'] = new SynPat_Batch_Processor( $this->db );
		$this->modules['analysis_controller'] = new SynPat_Analysis_Controller( $this->db );

		// Experts module
		$this->modules['claim_chart'] = new SynPat_Claim_Chart( $this->db );
		$this->modules['prior_art'] = new SynPat_Prior_Art( $this->db );
		$this->modules['expert_editor'] = new SynPat_Expert_Editor( $this->db );

		// Admin module
		if ( is_admin() ) {
			$this->modules['user_management'] = new SynPat_User_Management( $this->db );
			$this->modules['system_config'] = new SynPat_System_Config( $this->db );
			$this->modules['reporting'] = new SynPat_Reporting( $this->db );
		}
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		// Admin menu
		add_action( 'admin_menu', [ $this, 'register_admin_pages' ], 20 );

		// Enqueue assets
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );

		// Platform integration
		add_filter( 'synpat_addon_modules', [ $this, 'register_pro_modules' ] );
	}

	/**
	 * Register admin menu pages
	 */
	public function register_admin_pages() {
		// Backyard Dashboard
		add_submenu_page(
			'synpat-platform',
			__( 'Backyard Tools', 'synpat-pro' ),
			__( 'Backyard', 'synpat-pro' ),
			'manage_options',
			'synpat-backyard',
			[ $this, 'render_backyard_dashboard' ]
		);

		// Expert Tools
		add_submenu_page(
			'synpat-platform',
			__( 'Expert Tools', 'synpat-pro' ),
			__( 'Expert Tools', 'synpat-pro' ),
			'edit_posts',
			'synpat-expert-tools',
			[ $this, 'render_expert_tools' ]
		);

		// System Admin
		add_submenu_page(
			'synpat-platform',
			__( 'System Administration', 'synpat-pro' ),
			__( 'System Admin', 'synpat-pro' ),
			'manage_options',
			'synpat-system-admin',
			[ $this, 'render_system_admin' ]
		);
	}

	/**
	 * Render Backyard Dashboard page
	 */
	public function render_backyard_dashboard() {
		require_once SYNPAT_PRO_ROOT . 'admin/views/backyard-dashboard.php';
	}

	/**
	 * Render Expert Tools page
	 */
	public function render_expert_tools() {
		require_once SYNPAT_PRO_ROOT . 'admin/views/expert-tools.php';
	}

	/**
	 * Render System Admin page
	 */
	public function render_system_admin() {
		require_once SYNPAT_PRO_ROOT . 'admin/views/system-admin.php';
	}

	/**
	 * Enqueue admin assets
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our pages
		if ( ! in_array( $hook, [ 'synpat-platform_page_synpat-backyard', 'synpat-platform_page_synpat-expert-tools', 'synpat-platform_page_synpat-system-admin' ], true ) ) {
			return;
		}

		wp_enqueue_style(
			'synpat-pro-admin',
			SYNPAT_PRO_URI . 'admin/css/admin-styles.css',
			[],
			SYNPAT_PRO_VER
		);

		wp_enqueue_script(
			'synpat-pro-admin',
			SYNPAT_PRO_URI . 'admin/js/admin-scripts.js',
			[ 'jquery' ],
			SYNPAT_PRO_VER,
			true
		);

		wp_localize_script( 'synpat-pro-admin', 'synpatPro', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'synpat_pro_nonce' ),
		] );
	}

	/**
	 * Register Pro Tools modules with the platform
	 */
	public function register_pro_modules( $modules ) {
		$modules['synpat_pro_tools'] = [
			'name' => 'SynPat Pro Tools',
			'version' => SYNPAT_PRO_VER,
			'description' => 'Professional patent analysis and expert tools',
			'modules' => [
				'backyard' => 'Patent Analysis & Data Import',
				'experts' => 'Claim Charts & Prior Art',
				'admin' => 'Advanced Administration',
			],
		];

		return $modules;
	}

	/**
	 * Get a loaded module instance
	 */
	public function get_module( $module_name ) {
		return isset( $this->modules[ $module_name ] ) ? $this->modules[ $module_name ] : null;
	}

	/**
	 * Get database handler
	 */
	public function get_database() {
		return $this->db;
	}
}
