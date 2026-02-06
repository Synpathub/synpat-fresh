<?php
/**
 * Admin Interface Coordinator
 * Manages admin menus, dashboard, and admin-specific functionality
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Admin {

	/**
	 * Database handler instance
	 */
	private $db;

	/**
	 * Initialize admin interface
	 */
	public function __construct() {
		if ( class_exists( 'SynPat_Database' ) ) {
			$this->db = new SynPat_Database();
		}
		$this->register_hooks();
	}

	/**
	 * Register WordPress admin hooks
	 */
	private function register_hooks() {
		add_action( 'admin_menu', [ $this, 'register_admin_menus' ] );
		add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'admin_post_synpat_purge_cache', [ $this, 'handle_purge_cache' ] );
		add_action( 'admin_post_synpat_rebuild_pdfs', [ $this, 'handle_rebuild_pdfs' ] );
		add_action( 'admin_post_synpat_download_export', [ $this, 'handle_download_export' ] );
	}

	/**
	 * Register admin menu pages
	 */
	public function register_admin_menus() {
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

		// Dashboard submenu (same as parent)
		add_submenu_page(
			'synpat-platform',
			esc_html__( 'Dashboard', 'synpat-platform' ),
			esc_html__( 'Dashboard', 'synpat-platform' ),
			'manage_options',
			'synpat-platform',
			[ $this, 'render_dashboard_page' ]
		);

		// Portfolios submenu
		add_submenu_page(
			'synpat-platform',
			esc_html__( 'Portfolios', 'synpat-platform' ),
			esc_html__( 'Portfolios', 'synpat-platform' ),
			'manage_options',
			'edit.php?post_type=synpat_portfolio'
		);

		// Patents submenu
		add_submenu_page(
			'synpat-platform',
			esc_html__( 'Patents', 'synpat-platform' ),
			esc_html__( 'Patents', 'synpat-platform' ),
			'manage_options',
			'edit.php?post_type=synpat_patent'
		);

		// Settings submenu
		add_submenu_page(
			'synpat-platform',
			esc_html__( 'Settings', 'synpat-platform' ),
			esc_html__( 'Settings', 'synpat-platform' ),
			'manage_options',
			'synpat-settings',
			[ $this, 'render_settings_page' ]
		);

		// Migration submenu
		add_submenu_page(
			'synpat-platform',
			esc_html__( 'Migration', 'synpat-platform' ),
			esc_html__( 'Migration', 'synpat-platform' ),
			'manage_options',
			'synpat-migration',
			[ $this, 'render_migration_page' ]
		);
	}

	/**
	 * Render dashboard page
	 */
	public function render_dashboard_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		// Gather dashboard metrics
		$metrics = $this->gather_dashboard_metrics();

		// Handle quick actions
		if ( isset( $_GET['synpat_action'] ) && isset( $_GET['_wpnonce'] ) ) {
			$action = sanitize_key( $_GET['synpat_action'] );
			if ( wp_verify_nonce( $_GET['_wpnonce'], 'synpat_backend_action' ) ) {
				$this->handle_quick_action( $action );
			}
		}

		// Load dashboard view
		include SYNPAT_PLT_ROOT . 'admin/views/dashboard.php';
	}

	/**
	 * Render settings page
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		// Load settings view
		include SYNPAT_PLT_ROOT . 'admin/views/settings.php';
	}

	/**
	 * Render migration page
	 */
	public function render_migration_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		// Load migration view
		include SYNPAT_PLT_ROOT . 'admin/views/migration.php';
	}

	/**
	 * Gather metrics for dashboard display
	 *
	 * @return array Dashboard metrics
	 */
	private function gather_dashboard_metrics() {
		global $wpdb;

		$metrics = [
			'portfolio_count' => 0,
			'portfolio_active' => 0,
			'patent_count' => 0,
			'wishlist_count' => 0,
			'latest_portfolios' => [],
		];

		// Get portfolio counts from custom tables
		$portfolios_table = $this->db->table( 'portfolios' );
		if ( ! empty( $portfolios_table ) ) {
			$metrics['portfolio_count'] = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM `{$portfolios_table}`"
			);

			$metrics['portfolio_active'] = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM `{$portfolios_table}` WHERE status = %s",
					'active'
				)
			);

			// Get latest portfolios
			$metrics['latest_portfolios'] = $wpdb->get_results(
				"SELECT id, title, n_patents, status 
				FROM `{$portfolios_table}` 
				ORDER BY id DESC 
				LIMIT 5"
			);
		}

		// Get patent count
		$patents_table = $this->db->table( 'patents' );
		if ( ! empty( $patents_table ) ) {
			$metrics['patent_count'] = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM `{$patents_table}`"
			);
		}

		// Get wishlist count
		$wishlist_table = $this->db->table( 'wishlist' );
		if ( ! empty( $wishlist_table ) ) {
			$metrics['wishlist_count'] = (int) $wpdb->get_var(
				"SELECT COUNT(*) FROM `{$wishlist_table}`"
			);
		}

		return apply_filters( 'synpat_dashboard_metrics', $metrics );
	}

	/**
	 * Handle quick actions from dashboard
	 *
	 * @param string $action Action identifier
	 */
	private function handle_quick_action( $action ) {
		switch ( $action ) {
			case 'purge_cache':
				$this->purge_platform_cache();
				$this->add_admin_notice( 'cache_purged', 'success' );
				break;

			case 'rebuild_pdfs':
				$this->rebuild_all_pdfs();
				$this->add_admin_notice( 'pdfs_rebuilt', 'success' );
				break;

			case 'download_export':
				$this->export_platform_data();
				break;
		}
	}

	/**
	 * Purge all platform caches
	 */
	private function purge_platform_cache() {
		// Clear WordPress transients
		global $wpdb;
		
		$like_pattern = $wpdb->esc_like( '_transient_synpat_' ) . '%';
		$like_pattern_timeout = $wpdb->esc_like( '_transient_timeout_synpat_' ) . '%';
		
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} 
				WHERE option_name LIKE %s 
				OR option_name LIKE %s",
				$like_pattern,
				$like_pattern_timeout
			)
		);

		// Clear object cache if available
		if ( function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		do_action( 'synpat_cache_purged' );
	}

	/**
	 * Rebuild all PDF documents
	 */
	private function rebuild_all_pdfs() {
		if ( ! class_exists( 'SynPat_PDF_Generator' ) ) {
			return;
		}

		$portfolios = $this->db->get_portfolios( [ 'limit' => -1 ] );
		$pdf_generator = new SynPat_PDF_Generator();

		foreach ( $portfolios as $portfolio ) {
			$pdf_generator->generate_portfolio_pdf( $portfolio->id );
		}

		do_action( 'synpat_pdfs_rebuilt', count( $portfolios ) );
	}

	/**
	 * Export platform data as JSON
	 */
	private function export_platform_data() {
		$export_data = [
			'portfolios' => $this->db->get_portfolios( [ 'limit' => -1 ] ),
			'export_date' => current_time( 'mysql' ),
			'version' => SYNPAT_PLT_VERSION,
		];

		$json_output = wp_json_encode( $export_data, JSON_PRETTY_PRINT );
		$filename = 'synpat-export-' . gmdate( 'Y-m-d-His' ) . '.json';

		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Length: ' . strlen( $json_output ) );

		echo $json_output;
		exit;
	}

	/**
	 * Enqueue admin assets
	 *
	 * @param string $hook Current admin page hook
	 */
	public function enqueue_admin_assets( $hook ) {
		// Only load on SynPat admin pages
		if ( strpos( $hook, 'synpat' ) === false && 
		     strpos( $hook, 'synpat_portfolio' ) === false && 
		     strpos( $hook, 'synpat_patent' ) === false ) {
			return;
		}

		// Enqueue admin styles
		wp_enqueue_style(
			'synpat-admin',
			SYNPAT_PLT_URI . 'admin/css/admin-styles.css',
			[],
			SYNPAT_PLT_VER
		);

		// Enqueue admin scripts
		wp_enqueue_script(
			'synpat-admin',
			SYNPAT_PLT_URI . 'admin/js/admin-scripts.js',
			[ 'jquery' ],
			SYNPAT_PLT_VER,
			true
		);

		// Localize script with admin data
		wp_localize_script( 'synpat-admin', 'synpatAdmin', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'synpat_admin_nonce' ),
			'strings' => [
				'confirmDelete' => esc_html__( 'Are you sure you want to delete this item?', 'synpat-platform' ),
				'confirmPurge' => esc_html__( 'This will clear all cached data. Continue?', 'synpat-platform' ),
			],
		] );
	}

	/**
	 * Display admin notices
	 */
	public function display_admin_notices() {
		// Check for notice parameter
		if ( ! isset( $_GET['notice'] ) ) {
			return;
		}

		$notice_type = sanitize_key( $_GET['notice'] );
		self::render_notification( $notice_type );
	}

	/**
	 * Render a specific notification
	 *
	 * @param string $notice_type Notice type identifier
	 */
	public static function render_notification( $notice_type ) {
		$notices = [
			'config_updated' => [
				'type' => 'success',
				'message' => esc_html__( 'Configuration saved successfully.', 'synpat-platform' ),
			],
			'cache_purged' => [
				'type' => 'success',
				'message' => esc_html__( 'Cache cleared successfully.', 'synpat-platform' ),
			],
			'pdfs_rebuilt' => [
				'type' => 'success',
				'message' => esc_html__( 'PDFs regenerated successfully.', 'synpat-platform' ),
			],
			'import_success' => [
				'type' => 'success',
				'message' => esc_html__( 'Data imported successfully.', 'synpat-platform' ),
			],
			'import_error' => [
				'type' => 'error',
				'message' => esc_html__( 'Import failed. Please check the file format.', 'synpat-platform' ),
			],
			'sync_complete' => [
				'type' => 'success',
				'message' => esc_html__( 'Synchronization completed successfully.', 'synpat-platform' ),
			],
		];

		if ( ! isset( $notices[ $notice_type ] ) ) {
			return;
		}

		$notice = $notices[ $notice_type ];
		
		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $notice['type'] ),
			esc_html( $notice['message'] )
		);
	}

	/**
	 * Add an admin notice to be displayed on next page load
	 *
	 * @param string $notice_type Notice type
	 * @param string $type Notice category (success, error, warning, info)
	 */
	private function add_admin_notice( $notice_type, $type = 'info' ) {
		set_transient( 'synpat_admin_notice_' . get_current_user_id(), [
			'type' => $notice_type,
			'category' => $type,
		], 30 );
	}

	/**
	 * Handle purge cache action
	 */
	public function handle_purge_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'synpat-platform' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'synpat_backend_action' ) ) {
			wp_die( esc_html__( 'Security check failed', 'synpat-platform' ) );
		}

		$this->purge_platform_cache();

		wp_safe_redirect( add_query_arg( [
			'page' => 'synpat-platform',
			'notice' => 'cache_purged',
		], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handle rebuild PDFs action
	 */
	public function handle_rebuild_pdfs() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'synpat-platform' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'synpat_backend_action' ) ) {
			wp_die( esc_html__( 'Security check failed', 'synpat-platform' ) );
		}

		$this->rebuild_all_pdfs();

		wp_safe_redirect( add_query_arg( [
			'page' => 'synpat-platform',
			'notice' => 'pdfs_rebuilt',
		], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Handle download export action
	 */
	public function handle_download_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'synpat-platform' ) );
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'synpat_backend_action' ) ) {
			wp_die( esc_html__( 'Security check failed', 'synpat-platform' ) );
		}

		$this->export_platform_data();
	}
}
