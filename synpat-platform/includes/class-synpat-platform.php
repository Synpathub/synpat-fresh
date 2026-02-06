<?php
/**
 * Core Platform Orchestrator
 * Manages all subsystems and coordinates plugin functionality
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Platform {

	/**
	 * Collection of loaded subsystems
	 */
	private $subsystems = [];

	/**
	 * Hook manager for addon integration
	 */
	private $hook_registry;

	/**
	 * Database operations handler
	 */
	private $db_handler;

	/**
	 * Bootstrap the platform and wire up components
	 */
	public function initialize() {
		$this->load_dependencies();
		$this->configure_database();
		$this->register_subsystems();
		$this->attach_wordpress_hooks();
		$this->signal_ready();
	}

	/**
	 * Import required component files
	 */
	private function load_dependencies() {
		$core_files = [
			'includes/class-database.php',
			'includes/class-hooks.php',
			'includes/class-auth.php',
			'admin/class-admin.php',
			'admin/class-settings.php',
			'admin/class-portfolio-cpt.php',
			'admin/class-patent-cpt.php',
			'modules/store/class-store-frontend.php',
			'modules/store/class-shortcodes.php',
			'modules/store/class-ajax-handlers.php',
			'modules/pdf/class-pdf-generator.php',
			'modules/pdf/class-pdf-merger.php',
			'modules/pdf/class-pdf-templates.php',
		];

		foreach ( $core_files as $file_path ) {
			$full_path = SYNPAT_PLT_ROOT . $file_path;
			if ( file_exists( $full_path ) ) {
				require_once $full_path;
			}
		}
	}

	/**
	 * Initialize database layer
	 */
	private function configure_database() {
		$this->db_handler = new SynPat_Database();
	}

	/**
	 * Activate all functional modules
	 */
	private function register_subsystems() {
		$this->hook_registry = new SynPat_Hooks();
		
		if ( is_admin() ) {
			$this->subsystems['admin'] = new SynPat_Admin();
			$this->subsystems['settings'] = new SynPat_Settings();
			$this->subsystems['portfolio_cpt'] = new SynPat_Portfolio_CPT();
			$this->subsystems['patent_cpt'] = new SynPat_Patent_CPT();
		}

		$this->subsystems['store_frontend'] = new SynPat_Store_Frontend();
		$this->subsystems['shortcodes'] = new SynPat_Shortcodes();
		$this->subsystems['ajax'] = new SynPat_Ajax_Handlers();
		$this->subsystems['pdf_gen'] = new SynPat_PDF_Generator();
	}

	/**
	 * Connect to WordPress action and filter system
	 */
	private function attach_wordpress_hooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_public_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'init', [ $this, 'register_custom_content_types' ] );
	}

	/**
	 * Load frontend styles and scripts
	 */
	public function enqueue_public_assets() {
		wp_enqueue_style( 
			'synpat-store-ui', 
			SYNPAT_PLT_URI . 'public/css/store-frontend.css',
			[],
			SYNPAT_PLT_VER
		);

		wp_enqueue_script(
			'synpat-store-logic',
			SYNPAT_PLT_URI . 'public/js/store-frontend.js',
			[ 'jquery' ],
			SYNPAT_PLT_VER,
			true
		);

		wp_localize_script( 'synpat-store-logic', 'synpatAjax', [
			'endpoint' => admin_url( 'admin-ajax.php' ),
			'security' => wp_create_nonce( 'synpat_ajax_nonce' ),
		]);
	}

	/**
	 * Load admin panel assets
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_style( 
			'synpat-admin-ui', 
			SYNPAT_PLT_URI . 'admin/css/admin-styles.css',
			[],
			SYNPAT_PLT_VER
		);
	}

	/**
	 * Register custom post types for WordPress
	 */
	public function register_custom_content_types() {
		// Portfolios and Patents CPTs are registered by their respective classes
		do_action( 'synpat_register_custom_types' );
	}

	/**
	 * Signal that platform is fully loaded
	 */
	private function signal_ready() {
		do_action( 'synpat_platform_loaded', $this );
		do_action( 'synpat_register_modules' );
	}

	/**
	 * Retrieve database handler instance
	 */
	public function get_database() {
		return $this->db_handler;
	}

	/**
	 * Retrieve hook registry instance
	 */
	public function get_hooks() {
		return $this->hook_registry;
	}
}
