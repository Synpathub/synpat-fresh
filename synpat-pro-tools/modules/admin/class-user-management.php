<?php
/**
 * User Management Module
 * Advanced user capabilities and permissions for patent operations
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_User_Management {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Custom role definitions
	 */
	private $custom_roles = [];

	/**
	 * Initialize user management
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->define_custom_roles();
		$this->wire_event_listeners();
	}

	/**
	 * Define custom role configurations
	 */
	private function define_custom_roles() {
		$this->custom_roles = [
			'patent_analyst' => [
				'display_label' => __( 'Patent Analyst', 'synpat-pro' ),
				'capabilities' => [
					'access_backyard_tools' => true,
					'analyze_patents' => true,
					'import_patent_data' => true,
					'export_reports' => true,
				],
			],
			'expert_reviewer' => [
				'display_label' => __( 'Expert Reviewer', 'synpat-pro' ),
				'capabilities' => [
					'create_claim_charts' => true,
					'generate_prior_art' => true,
					'review_analysis' => true,
					'publish_expert_reports' => true,
				],
			],
		];
	}

	/**
	 * Wire event listeners
	 */
	private function wire_event_listeners() {
		add_action( 'wp_ajax_synpat_grant_capability', [ $this, 'handle_capability_grant' ] );
		add_action( 'wp_ajax_synpat_revoke_capability', [ $this, 'handle_capability_revoke' ] );
		add_action( 'user_register', [ $this, 'on_user_registration' ] );
	}

	/**
	 * Provision custom role
	 */
	public function provision_role( $role_key ) {
		if ( ! isset( $this->custom_roles[ $role_key ] ) ) {
			return new WP_Error( 'invalid_role', __( 'Role configuration not found', 'synpat-pro' ) );
		}

		$config = $this->custom_roles[ $role_key ];
		
		add_role(
			$role_key,
			$config['display_label'],
			$config['capabilities']
		);

		return true;
	}

	/**
	 * Deprovision custom role
	 */
	public function deprovision_role( $role_key ) {
		remove_role( $role_key );
	}

	/**
	 * Grant capability to user
	 */
	public function grant_user_capability( $user_identifier, $capability_key ) {
		$user_obj = get_user_by( 'id', $user_identifier );

		if ( ! $user_obj ) {
			return new WP_Error( 'user_not_found', __( 'User does not exist', 'synpat-pro' ) );
		}

		$user_obj->add_cap( $capability_key );

		// Log capability change
		$this->log_capability_change( $user_identifier, $capability_key, 'granted' );

		return true;
	}

	/**
	 * Revoke capability from user
	 */
	public function revoke_user_capability( $user_identifier, $capability_key ) {
		$user_obj = get_user_by( 'id', $user_identifier );

		if ( ! $user_obj ) {
			return new WP_Error( 'user_not_found', __( 'User does not exist', 'synpat-pro' ) );
		}

		$user_obj->remove_cap( $capability_key );

		// Log capability change
		$this->log_capability_change( $user_identifier, $capability_key, 'revoked' );

		return true;
	}

	/**
	 * Log capability modifications
	 */
	private function log_capability_change( $user_identifier, $capability_key, $action_type ) {
		global $wpdb;

		$wpdb->insert(
			$wpdb->prefix . 'synpat_capability_log',
			[
				'user_id' => absint( $user_identifier ),
				'capability' => sanitize_key( $capability_key ),
				'action' => sanitize_key( $action_type ),
				'modified_by' => get_current_user_id(),
				'timestamp' => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Retrieve user activity metrics
	 */
	public function fetch_user_metrics( $user_identifier ) {
		global $wpdb;

		$metrics = [
			'charts_created' => 0,
			'reports_generated' => 0,
			'analyses_completed' => 0,
			'data_imports' => 0,
		];

		// Count claim charts
		$metrics['charts_created'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}synpat_claim_charts WHERE created_by = %d",
			$user_identifier
		) );

		// Count prior art reports
		$metrics['reports_generated'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}synpat_prior_art_reports WHERE created_by = %d",
			$user_identifier
		) );

		// Count analyses
		$metrics['analyses_completed'] = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->prefix}synpat_expert_analysis 
			WHERE created_at IN (
				SELECT created_at FROM {$wpdb->prefix}synpat_expert_analysis 
				WHERE id IN (SELECT id FROM {$wpdb->prefix}postmeta WHERE meta_value = %d)
			)",
			$user_identifier
		) );

		return $metrics;
	}

	/**
	 * Handle AJAX capability grant
	 */
	public function handle_capability_grant() {
		check_ajax_referer( 'synpat_pro_nonce', 'security_token' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'notification' => __( 'Insufficient permissions', 'synpat-pro' ) ] );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$capability = isset( $_POST['capability'] ) ? sanitize_key( $_POST['capability'] ) : '';

		$outcome = $this->grant_user_capability( $user_id, $capability );

		if ( is_wp_error( $outcome ) ) {
			wp_send_json_error( [ 'notification' => $outcome->get_error_message() ] );
		}

		wp_send_json_success( [ 'notification' => __( 'Capability granted successfully', 'synpat-pro' ) ] );
	}

	/**
	 * Handle AJAX capability revoke
	 */
	public function handle_capability_revoke() {
		check_ajax_referer( 'synpat_pro_nonce', 'security_token' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'notification' => __( 'Insufficient permissions', 'synpat-pro' ) ] );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( $_POST['user_id'] ) : 0;
		$capability = isset( $_POST['capability'] ) ? sanitize_key( $_POST['capability'] ) : '';

		$outcome = $this->revoke_user_capability( $user_id, $capability );

		if ( is_wp_error( $outcome ) ) {
			wp_send_json_error( [ 'notification' => $outcome->get_error_message() ] );
		}

		wp_send_json_success( [ 'notification' => __( 'Capability revoked successfully', 'synpat-pro' ) ] );
	}

	/**
	 * On user registration callback
	 */
	public function on_user_registration( $user_id ) {
		// Initialize user metrics
		update_user_meta( $user_id, 'synpat_pro_initialized', time() );
	}
}
