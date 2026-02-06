<?php
/**
 * Admin Controller
 * Manages WordPress admin interface and menu structure
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Admin {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Auth handler
	 */
	private $auth;

	/**
	 * Current admin page
	 */
	private $current_page = '';

	/**
	 * Initialize admin interface
	 */
	public function __construct() {
		$this->db = new SynPat_Database();
		$this->auth = new SynPat_Auth();
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );
	}

	/**
	 * Register admin menu pages
	 */
	public function register_admin_menu() {
		// Main menu page
		add_menu_page(
			esc_html__( 'SynPat Platform', 'synpat-platform' ),
			esc_html__( 'SynPat', 'synpat-platform' ),
			'manage_options',
			'synpat-platform',
			[ $this, 'render_dashboard_page' ],
			'dashicons-portfolio',
			30
		);

		// Dashboard (same as main page)
		add_submenu_page(
			'synpat-platform',
			esc_html__( 'Dashboard', 'synpat-platform' ),
			esc_html__( 'Dashboard', 'synpat-platform' ),
			'manage_options',
			'synpat-platform',
			[ $this, 'render_dashboard_page' ]
		);

		// Settings page
		add_submenu_page(
			'synpat-platform',
			esc_html__( 'Settings', 'synpat-platform' ),
			esc_html__( 'Settings', 'synpat-platform' ),
			'manage_options',
			'synpat-settings',
			[ $this, 'render_settings_page' ]
		);

		// Migration tools
		add_submenu_page(
			'synpat-platform',
			esc_html__( 'Data Migration', 'synpat-platform' ),
			esc_html__( 'Migration', 'synpat-platform' ),
			'manage_options',
			'synpat-migration',
			[ $this, 'render_migration_page' ]
		);

		// Allow other modules to add submenu items
		do_action( 'synpat_admin_menu_registered' );
	}

	/**
	 * Enqueue admin CSS and JavaScript
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on our admin pages
		if ( strpos( $hook, 'synpat' ) === false ) {
			return;
		}

		// Admin styles
		wp_enqueue_style(
			'synpat-admin-styles',
			SYNPAT_PLT_URI . 'admin/css/admin-styles.css',
			[],
			SYNPAT_PLT_VER
		);

		// Admin scripts
		wp_enqueue_script(
			'synpat-admin-scripts',
			SYNPAT_PLT_URI . 'admin/js/admin-scripts.js',
			[ 'jquery', 'wp-api' ],
			SYNPAT_PLT_VER,
			true
		);

		// Localize script with admin data
		wp_localize_script( 'synpat-admin-scripts', 'synpatAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'synpat_admin_nonce' ),
			'restUrl' => rest_url( 'synpat/v1/' ),
			'restNonce' => wp_create_nonce( 'wp_rest' ),
			'strings' => [
				'confirm_delete' => esc_html__( 'Are you sure you want to delete this item?', 'synpat-platform' ),
				'error_occurred' => esc_html__( 'An error occurred. Please try again.', 'synpat-platform' ),
				'success' => esc_html__( 'Operation completed successfully.', 'synpat-platform' ),
			],
		] );

		// Allow modules to enqueue their assets
		do_action( 'synpat_admin_enqueue_scripts', $hook );
	}

	/**
	 * Render dashboard page
	 */
	public function render_dashboard_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		// Get statistics for dashboard
		$stats = $this->get_dashboard_stats();

		// Load dashboard template
		include SYNPAT_PLT_ROOT . 'admin/views/dashboard.php';
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		// Load settings template
		include SYNPAT_PLT_ROOT . 'admin/views/settings.php';
	}

	/**
	 * Render migration page
	 */
	public function render_migration_page() {
		// Check user capabilities
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		// Load migration template
		include SYNPAT_PLT_ROOT . 'admin/views/migration.php';
	}

	/**
	 * Get dashboard statistics
	 *
	 * @return array Dashboard stats
	 */
	private function get_dashboard_stats() {
		global $wpdb;

		$prefix = $wpdb->prefix;

		// Get portfolio count
		$portfolio_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$prefix}synpat_portfolios"
		);

		// Get patent count
		$patent_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$prefix}synpat_patents"
		);

		// Get licensee count
		$licensee_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$prefix}synpat_licensees"
		);

		// Get recent activity (portfolios added in last 30 days)
		$recent_portfolios = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$prefix}synpat_portfolios WHERE created_at >= %s",
				date( 'Y-m-d H:i:s', strtotime( '-30 days' ) )
			)
		);

		return [
			'portfolios' => absint( $portfolio_count ),
			'patents' => absint( $patent_count ),
			'licensees' => absint( $licensee_count ),
			'recent_portfolios' => absint( $recent_portfolios ),
		];
	}

	/**
	 * Display admin notices
	 */
	public function display_admin_notices() {
		// Check if we're on a SynPat admin page
		$screen = get_current_screen();
		if ( ! $screen || strpos( $screen->id, 'synpat' ) === false ) {
			return;
		}

		// Display success messages
		if ( isset( $_GET['synpat_success'] ) ) {
			$message = $this->get_success_message( sanitize_key( $_GET['synpat_success'] ) );
			if ( $message ) {
				printf(
					'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
					esc_html( $message )
				);
			}
		}

		// Display error messages
		if ( isset( $_GET['synpat_error'] ) ) {
			$message = $this->get_error_message( sanitize_key( $_GET['synpat_error'] ) );
			if ( $message ) {
				printf(
					'<div class="notice notice-error is-dismissible"><p>%s</p></div>',
					esc_html( $message )
				);
			}
		}

		// Allow modules to add their notices
		do_action( 'synpat_admin_notices' );
	}

	/**
	 * Get success message by key
	 *
	 * @param string $key Message key
	 * @return string Success message
	 */
	private function get_success_message( $key ) {
		$messages = [
			'settings_saved' => esc_html__( 'Settings saved successfully.', 'synpat-platform' ),
			'portfolio_created' => esc_html__( 'Portfolio created successfully.', 'synpat-platform' ),
			'portfolio_updated' => esc_html__( 'Portfolio updated successfully.', 'synpat-platform' ),
			'portfolio_deleted' => esc_html__( 'Portfolio deleted successfully.', 'synpat-platform' ),
			'patent_created' => esc_html__( 'Patent created successfully.', 'synpat-platform' ),
			'patent_updated' => esc_html__( 'Patent updated successfully.', 'synpat-platform' ),
			'patent_deleted' => esc_html__( 'Patent deleted successfully.', 'synpat-platform' ),
			'migration_complete' => esc_html__( 'Data migration completed successfully.', 'synpat-platform' ),
		];

		return isset( $messages[ $key ] ) ? $messages[ $key ] : '';
	}

	/**
	 * Get error message by key
	 *
	 * @param string $key Message key
	 * @return string Error message
	 */
	private function get_error_message( $key ) {
		$messages = [
			'settings_error' => esc_html__( 'Failed to save settings.', 'synpat-platform' ),
			'portfolio_error' => esc_html__( 'Failed to process portfolio.', 'synpat-platform' ),
			'patent_error' => esc_html__( 'Failed to process patent.', 'synpat-platform' ),
			'migration_error' => esc_html__( 'Data migration failed.', 'synpat-platform' ),
			'permission_denied' => esc_html__( 'Permission denied.', 'synpat-platform' ),
			'invalid_data' => esc_html__( 'Invalid data provided.', 'synpat-platform' ),
		];

		return isset( $messages[ $key ] ) ? $messages[ $key ] : esc_html__( 'An error occurred.', 'synpat-platform' );
	}

	/**
	 * Get database handler
	 *
	 * @return SynPat_Database Database instance
	 */
	public function get_database() {
		return $this->db;
	}

	/**
	 * Get auth handler
	 *
	 * @return SynPat_Auth Auth instance
	 */
	public function get_auth() {
		return $this->auth;
	}
}
