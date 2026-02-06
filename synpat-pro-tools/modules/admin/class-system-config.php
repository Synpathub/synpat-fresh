<?php
/**
 * System Configuration Module
 * Manages advanced settings and configurations for Pro Tools
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_System_Config {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Configuration registry
	 */
	private $config_registry = [];

	/**
	 * Option namespace
	 */
	const OPTION_NAMESPACE = 'synpat_pro_config_';

	/**
	 * Initialize system configuration
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->bootstrap_config_registry();
		$this->attach_handlers();
	}

	/**
	 * Bootstrap configuration options
	 */
	private function bootstrap_config_registry() {
		$this->config_registry = [
			'analysis_thresholds' => [
				'strength_score_minimum' => 50,
				'complexity_weight' => 0.3,
				'citation_multiplier' => 2.5,
			],
			'import_settings' => [
				'batch_limit' => 100,
				'timeout_seconds' => 300,
				'auto_validate' => true,
			],
			'export_preferences' => [
				'default_format' => 'csv',
				'include_metadata' => true,
				'compression_enabled' => false,
			],
			'editor_configurations' => [
				'autosave_interval' => 60,
				'version_control' => true,
				'collaborative_mode' => false,
			],
		];
	}

	/**
	 * Attach event handlers
	 */
	private function attach_handlers() {
		add_action( 'wp_ajax_synpat_update_config', [ $this, 'handle_config_update' ] );
		add_action( 'wp_ajax_synpat_reset_config', [ $this, 'handle_config_reset' ] );
	}

	/**
	 * Retrieve configuration value
	 */
	public function retrieve_config( $config_key, $sub_key = null ) {
		$stored_value = get_option( self::OPTION_NAMESPACE . $config_key );

		if ( false === $stored_value ) {
			// Return default from registry
			$stored_value = isset( $this->config_registry[ $config_key ] ) 
				? $this->config_registry[ $config_key ] 
				: [];
		}

		if ( null !== $sub_key ) {
			return isset( $stored_value[ $sub_key ] ) ? $stored_value[ $sub_key ] : null;
		}

		return $stored_value;
	}

	/**
	 * Persist configuration value
	 */
	public function persist_config( $config_key, $value_data ) {
		$sanitized_data = $this->sanitize_config_data( $config_key, $value_data );

		update_option( self::OPTION_NAMESPACE . $config_key, $sanitized_data );

		do_action( 'synpat_pro_config_updated', $config_key, $sanitized_data );

		return true;
	}

	/**
	 * Sanitize configuration data
	 */
	private function sanitize_config_data( $config_key, $value_data ) {
		if ( ! is_array( $value_data ) ) {
			return sanitize_text_field( $value_data );
		}

		$sanitized = [];

		foreach ( $value_data as $key => $val ) {
			if ( is_numeric( $val ) ) {
				$sanitized[ $key ] = is_float( $val ) ? floatval( $val ) : absint( $val );
			} elseif ( is_bool( $val ) ) {
				$sanitized[ $key ] = (bool) $val;
			} else {
				$sanitized[ $key ] = sanitize_text_field( $val );
			}
		}

		return $sanitized;
	}

	/**
	 * Reset configuration to defaults
	 */
	public function reset_to_defaults( $config_key ) {
		if ( ! isset( $this->config_registry[ $config_key ] ) ) {
			return new WP_Error( 'invalid_config', __( 'Configuration key not found', 'synpat-pro' ) );
		}

		delete_option( self::OPTION_NAMESPACE . $config_key );

		do_action( 'synpat_pro_config_reset', $config_key );

		return true;
	}

	/**
	 * Export all configurations
	 */
	public function export_all_configs() {
		$export_data = [];

		foreach ( array_keys( $this->config_registry ) as $config_key ) {
			$export_data[ $config_key ] = $this->retrieve_config( $config_key );
		}

		return $export_data;
	}

	/**
	 * Import configurations from data
	 */
	public function import_configs( $import_data ) {
		$imported_count = 0;

		foreach ( $import_data as $config_key => $value_data ) {
			if ( isset( $this->config_registry[ $config_key ] ) ) {
				$this->persist_config( $config_key, $value_data );
				$imported_count++;
			}
		}

		return [
			'imported' => $imported_count,
			'total' => count( $import_data ),
		];
	}

	/**
	 * Validate configuration schema
	 */
	public function validate_schema( $config_key, $value_data ) {
		if ( ! isset( $this->config_registry[ $config_key ] ) ) {
			return new WP_Error( 'unknown_config', __( 'Unknown configuration key', 'synpat-pro' ) );
		}

		$schema = $this->config_registry[ $config_key ];

		foreach ( $value_data as $key => $val ) {
			if ( ! array_key_exists( $key, $schema ) ) {
				return new WP_Error( 'invalid_key', sprintf( __( 'Invalid key: %s', 'synpat-pro' ), $key ) );
			}

			$expected_type = gettype( $schema[ $key ] );
			$actual_type = gettype( $val );

			if ( $expected_type !== $actual_type ) {
				return new WP_Error( 'type_mismatch', sprintf( 
					__( 'Type mismatch for %s: expected %s, got %s', 'synpat-pro' ), 
					$key, 
					$expected_type, 
					$actual_type 
				) );
			}
		}

		return true;
	}

	/**
	 * Handle AJAX configuration update
	 */
	public function handle_config_update() {
		check_ajax_referer( 'synpat_pro_nonce', 'security_token' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'notification' => __( 'Insufficient permissions', 'synpat-pro' ) ] );
		}

		$config_key = isset( $_POST['config_key'] ) ? sanitize_key( $_POST['config_key'] ) : '';
		$config_data = isset( $_POST['config_data'] ) ? json_decode( stripslashes( $_POST['config_data'] ), true ) : [];

		if ( empty( $config_key ) || empty( $config_data ) ) {
			wp_send_json_error( [ 'notification' => __( 'Invalid configuration data', 'synpat-pro' ) ] );
		}

		$validation = $this->validate_schema( $config_key, $config_data );

		if ( is_wp_error( $validation ) ) {
			wp_send_json_error( [ 'notification' => $validation->get_error_message() ] );
		}

		$this->persist_config( $config_key, $config_data );

		wp_send_json_success( [ 'notification' => __( 'Configuration updated successfully', 'synpat-pro' ) ] );
	}

	/**
	 * Handle AJAX configuration reset
	 */
	public function handle_config_reset() {
		check_ajax_referer( 'synpat_pro_nonce', 'security_token' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'notification' => __( 'Insufficient permissions', 'synpat-pro' ) ] );
		}

		$config_key = isset( $_POST['config_key'] ) ? sanitize_key( $_POST['config_key'] ) : '';

		if ( empty( $config_key ) ) {
			wp_send_json_error( [ 'notification' => __( 'Invalid configuration key', 'synpat-pro' ) ] );
		}

		$result = $this->reset_to_defaults( $config_key );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( [ 'notification' => $result->get_error_message() ] );
		}

		wp_send_json_success( [ 
			'notification' => __( 'Configuration reset to defaults', 'synpat-pro' ),
			'defaults' => $this->config_registry[ $config_key ],
		] );
	}
}
