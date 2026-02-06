<?php
/**
 * Authentication and Authorization Handler
 * Manages user permissions and capability checks
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Auth {

	/**
	 * Capability mappings for different operations
	 */
	private $capability_map = [];

	/**
	 * Initialize auth system
	 */
	public function __construct() {
		$this->define_capabilities();
		$this->register_hooks();
	}

	/**
	 * Define capability requirements
	 */
	private function define_capabilities() {
		$this->capability_map = [
			'view_portfolios'       => 'read',
			'edit_portfolios'       => 'edit_posts',
			'delete_portfolios'     => 'delete_posts',
			'manage_patents'        => 'edit_posts',
			'view_claim_charts'     => 'read',
			'create_claim_charts'   => 'edit_posts',
			'manage_licensees'      => 'manage_options',
			'export_data'           => 'manage_options',
			'import_data'           => 'manage_options',
			'run_analysis'          => 'edit_posts',
		];
	}

	/**
	 * Register authentication hooks
	 */
	private function register_hooks() {
		add_action( 'init', [ $this, 'register_custom_capabilities' ] );
	}

	/**
	 * Add custom capabilities to roles
	 */
	public function register_custom_capabilities() {
		$admin_role = get_role( 'administrator' );
		$editor_role = get_role( 'editor' );
		
		if ( $admin_role ) {
			$admin_role->add_cap( 'manage_synpat_portfolios' );
			$admin_role->add_cap( 'manage_synpat_patents' );
			$admin_role->add_cap( 'manage_synpat_licensees' );
		}
		
		if ( $editor_role ) {
			$editor_role->add_cap( 'manage_synpat_portfolios' );
			$editor_role->add_cap( 'manage_synpat_patents' );
		}
	}

	/**
	 * Check if current user can perform an operation
	 */
	public function user_can( $operation, $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}
		
		if ( ! isset( $this->capability_map[ $operation ] ) ) {
			return false;
		}
		
		$required_cap = $this->capability_map[ $operation ];
		return user_can( $user_id, $required_cap );
	}

	/**
	 * Verify nonce for AJAX requests
	 */
	public function verify_ajax_nonce( $nonce, $action = 'synpat_ajax_nonce' ) {
		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Check admin referer for form submissions
	 */
	public function verify_admin_request( $action ) {
		check_admin_referer( $action );
	}

	/**
	 * Get current user permissions summary
	 */
	public function get_user_permissions( $user_id = null ) {
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}
		
		$permissions = [];
		
		foreach ( $this->capability_map as $operation => $capability ) {
			$permissions[ $operation ] = user_can( $user_id, $capability );
		}
		
		return $permissions;
	}

	/**
	 * Restrict access to admin functions
	 */
	public function require_admin_access() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 
				esc_html__( 'You do not have permission to access this page.', 'synpat-platform' ),
				esc_html__( 'Access Denied', 'synpat-platform' ),
				[ 'response' => 403 ]
			);
		}
	}

	/**
	 * Check if user owns a resource
	 */
	public function user_owns_resource( $user_id, $resource_type, $resource_id ) {
		global $wpdb;
		
		$table_map = [
			'wishlist' => $wpdb->prefix . 'synpat_customer_wishlist',
		];
		
		if ( ! isset( $table_map[ $resource_type ] ) ) {
			return false;
		}
		
		$table = $table_map[ $resource_type ];
		
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE user_id = %d AND id = %d",
			$user_id,
			$resource_id
		) );
		
		return $count > 0;
	}
}
