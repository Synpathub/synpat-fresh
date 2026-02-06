<?php
/**
 * Admin Interface Manager
 * Manages the main admin interface for SynPat Platform
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Admin {

	/**
	 * Admin menu slug
	 */
	private $menu_slug = 'synpat-platform';

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Initialize admin interface
	 */
	public function __construct() {
		$this->db = new SynPat_Database();
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );
		add_action( 'admin_init', [ $this, 'handle_admin_actions' ] );
		add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );
	}

	/**
	 * Register admin menu and pages
	 */
	public function register_admin_menu() {
		// Main menu page
		add_menu_page(
			__( 'SynPat Platform', 'synpat-platform' ),
			__( 'SynPat Platform', 'synpat-platform' ),
			'manage_options',
			$this->menu_slug,
			[ $this, 'render_dashboard' ],
			'dashicons-portfolio',
			30
		);

		// Dashboard submenu (same as main menu)
		add_submenu_page(
			$this->menu_slug,
			__( 'Dashboard', 'synpat-platform' ),
			__( 'Dashboard', 'synpat-platform' ),
			'manage_options',
			$this->menu_slug,
			[ $this, 'render_dashboard' ]
		);

		// Settings submenu
		add_submenu_page(
			$this->menu_slug,
			__( 'Settings', 'synpat-platform' ),
			__( 'Settings', 'synpat-platform' ),
			'manage_options',
			'synpat-settings',
			[ $this, 'render_settings' ]
		);

		// Migration submenu
		add_submenu_page(
			$this->menu_slug,
			__( 'Data Migration', 'synpat-platform' ),
			__( 'Migration', 'synpat-platform' ),
			'manage_options',
			'synpat-migration',
			[ $this, 'render_migration' ]
		);

		// Allow addons to register their own submenus
		do_action( 'synpat_admin_menu_registered', $this->menu_slug );
	}

	/**
	 * Render dashboard page
	 */
	public function render_dashboard() {
		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		$stats = $this->get_dashboard_stats();

		require_once SYNPAT_PLT_ROOT . 'admin/views/dashboard.php';
	}

	/**
	 * Render settings page
	 */
	public function render_settings() {
		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		require_once SYNPAT_PLT_ROOT . 'admin/views/settings.php';
	}

	/**
	 * Render migration page
	 */
	public function render_migration() {
		// Check user permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'synpat-platform' ) );
		}

		require_once SYNPAT_PLT_ROOT . 'admin/views/migration.php';
	}

	/**
	 * Get dashboard statistics
	 */
	private function get_dashboard_stats() {
		global $wpdb;

		$stats = [
			'portfolios_count' => 0,
			'patents_count' => 0,
			'licensees_count' => 0,
			'claim_charts_count' => 0,
		];

		// Get portfolios count
		$portfolios_table = $this->db->table( 'portfolios' );
		if ( $portfolios_table ) {
			$stats['portfolios_count'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$portfolios_table}" );
		}

		// Get patents count
		$patents_table = $this->db->table( 'patents' );
		if ( $patents_table ) {
			$stats['patents_count'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$patents_table}" );
		}

		// Get licensees count
		$licensees_table = $this->db->table( 'licensees' );
		if ( $licensees_table ) {
			$stats['licensees_count'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$licensees_table}" );
		}

		// Get claim charts count
		$claim_charts_table = $this->db->table( 'claim_charts' );
		if ( $claim_charts_table ) {
			$stats['claim_charts_count'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$claim_charts_table}" );
		}

		return $stats;
	}

	/**
	 * Handle admin actions
	 */
	public function handle_admin_actions() {
		// Check for admin actions
		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['page'] ) ) {
			return;
		}

		// Verify nonce for security
		if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( $_GET['_wpnonce'], 'synpat_admin_action' ) ) {
			return;
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$action = sanitize_key( $_GET['action'] );

		switch ( $action ) {
			case 'flush_cache':
				$this->flush_cache();
				break;

			case 'sync_data':
				$this->sync_data();
				break;

			default:
				do_action( 'synpat_admin_action_' . $action );
				break;
		}
	}

	/**
	 * Flush platform cache
	 */
	private function flush_cache() {
		wp_cache_flush();
		
		$redirect_url = add_query_arg( [
			'page' => 'synpat-platform',
			'notice' => 'cache_flushed',
		], admin_url( 'admin.php' ) );

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Sync data with external sources
	 */
	private function sync_data() {
		// Trigger sync action
		do_action( 'synpat_sync_data' );

		$redirect_url = add_query_arg( [
			'page' => 'synpat-platform',
			'notice' => 'data_synced',
		], admin_url( 'admin.php' ) );

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Display admin notices
	 */
	public function display_admin_notices() {
		if ( ! isset( $_GET['notice'] ) || ! isset( $_GET['page'] ) ) {
			return;
		}

		// Only show on our admin pages
		if ( strpos( $_GET['page'], 'synpat' ) !== 0 ) {
			return;
		}

		$notice_type = sanitize_key( $_GET['notice'] );
		$message = '';
		$type = 'success';

		switch ( $notice_type ) {
			case 'cache_flushed':
				$message = __( 'Cache flushed successfully.', 'synpat-platform' );
				break;

			case 'data_synced':
				$message = __( 'Data synchronized successfully.', 'synpat-platform' );
				break;

			case 'config_updated':
				$message = __( 'Settings saved successfully.', 'synpat-platform' );
				break;

			case 'migration_complete':
				$message = __( 'Data migration completed successfully.', 'synpat-platform' );
				break;

			case 'error':
				$message = __( 'An error occurred. Please try again.', 'synpat-platform' );
				$type = 'error';
				break;
		}

		if ( $message ) {
			printf(
				'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
				esc_attr( $type ),
				esc_html( $message )
			);
		}
	}

	/**
	 * Get admin menu slug
	 */
	public function get_menu_slug() {
		return $this->menu_slug;
	}

	/**
	 * Add quick action link
	 */
	public function add_quick_link( $title, $url, $capability = 'manage_options' ) {
		// Implementation for adding quick links to dashboard
		// This would be used by the dashboard view
	}
}
