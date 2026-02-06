<?php
/**
 * Settings Manager
 * Configuration interface for platform options
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Settings {

	/**
	 * Option key namespace
	 */
	private $option_namespace = 'synpat_config';

	/**
	 * Settings schema
	 */
	private $configuration_schema = [];

	/**
	 * Wire up settings system
	 */
	public function __construct() {
		$this->define_configuration_schema();
		$this->integrate_with_wordpress();
	}

	/**
	 * Connect to WordPress settings API
	 */
	private function integrate_with_wordpress() {
		add_action( 'admin_init', [ $this, 'initialize_settings_groups' ] );
		add_action( 'admin_post_synpat_save_config', [ $this, 'persist_configuration' ] );
	}

	/**
	 * Define available configuration options
	 */
	private function define_configuration_schema() {
		$this->configuration_schema = [
			'pdf_settings' => [
				'label' => esc_html__( 'PDF Generation', 'synpat-platform' ),
				'fields' => [
					'pdf_logo_url' => [
						'label' => esc_html__( 'PDF Logo URL', 'synpat-platform' ),
						'type' => 'text',
						'default' => '',
						'description' => esc_html__( 'URL for logo to appear on generated PDFs', 'synpat-platform' ),
					],
					'pdf_footer_text' => [
						'label' => esc_html__( 'PDF Footer Text', 'synpat-platform' ),
						'type' => 'text',
						'default' => get_bloginfo( 'name' ),
						'description' => esc_html__( 'Text to display in PDF footers', 'synpat-platform' ),
					],
					'pdf_watermark_enabled' => [
						'label' => esc_html__( 'Enable PDF Watermark', 'synpat-platform' ),
						'type' => 'checkbox',
						'default' => false,
					],
				],
			],
			'store_settings' => [
				'label' => esc_html__( 'Store Front', 'synpat-platform' ),
				'fields' => [
					'catalog_items_per_page' => [
						'label' => esc_html__( 'Items Per Page', 'synpat-platform' ),
						'type' => 'number',
						'default' => 20,
						'description' => esc_html__( 'Number of portfolios to display per page', 'synpat-platform' ),
					],
					'enable_wishlist_feature' => [
						'label' => esc_html__( 'Enable Wishlist', 'synpat-platform' ),
						'type' => 'checkbox',
						'default' => true,
					],
					'require_login_for_details' => [
						'label' => esc_html__( 'Require Login for Details', 'synpat-platform' ),
						'type' => 'checkbox',
						'default' => false,
					],
				],
			],
			'advanced_settings' => [
				'label' => esc_html__( 'Advanced', 'synpat-platform' ),
				'fields' => [
					'cache_duration_hours' => [
						'label' => esc_html__( 'Cache Duration (hours)', 'synpat-platform' ),
						'type' => 'number',
						'default' => 24,
						'description' => esc_html__( 'How long to cache portfolio data', 'synpat-platform' ),
					],
					'delete_data_on_uninstall' => [
						'label' => esc_html__( 'Delete Data on Uninstall', 'synpat-platform' ),
						'type' => 'checkbox',
						'default' => false,
						'description' => esc_html__( 'WARNING: This will remove all portfolios, patents, and related data when plugin is deleted', 'synpat-platform' ),
					],
				],
			],
		];

		$this->configuration_schema = apply_filters( 'synpat_settings_schema', $this->configuration_schema );
	}

	/**
	 * Register settings with WordPress
	 */
	public function initialize_settings_groups() {
		foreach ( $this->configuration_schema as $section_key => $section_config ) {
			$section_identifier = $this->option_namespace . '_' . $section_key;
			
			register_setting(
				$this->option_namespace,
				$section_identifier,
				[
					'sanitize_callback' => [ $this, 'sanitize_section_data' ],
				]
			);

			foreach ( $section_config['fields'] as $field_key => $field_spec ) {
				$full_option_key = $section_identifier . '[' . $field_key . ']';
				
				add_settings_field(
					$field_key,
					$field_spec['label'],
					[ $this, 'render_configuration_field' ],
					$this->option_namespace,
					$section_key,
					[
						'field_key' => $field_key,
						'field_spec' => $field_spec,
						'section_key' => $section_key,
						'option_name' => $full_option_key,
					]
				);
			}
		}
	}

	/**
	 * Render individual configuration field
	 *
	 * @param array $field_args Field arguments
	 */
	public function render_configuration_field( $field_args ) {
		$field_spec = $field_args['field_spec'];
		$field_key = $field_args['field_key'];
		$section_key = $field_args['section_key'];
		
		$current_value = $this->retrieve_option_value( $section_key, $field_key, $field_spec['default'] );
		$field_id = esc_attr( $section_key . '_' . $field_key );
		$field_name = esc_attr( $this->option_namespace . '_' . $section_key . '[' . $field_key . ']' );

		switch ( $field_spec['type'] ) {
			case 'text':
				printf(
					'<input type="text" id="%s" name="%s" value="%s" class="regular-text" />',
					$field_id,
					$field_name,
					esc_attr( $current_value )
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%s" name="%s" value="%s" class="small-text" />',
					$field_id,
					$field_name,
					esc_attr( $current_value )
				);
				break;

			case 'checkbox':
				printf(
					'<label><input type="checkbox" id="%s" name="%s" value="1" %s /> %s</label>',
					$field_id,
					$field_name,
					checked( $current_value, true, false ),
					esc_html__( 'Enable', 'synpat-platform' )
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="%s" name="%s" rows="5" class="large-text">%s</textarea>',
					$field_id,
					$field_name,
					esc_textarea( $current_value )
				);
				break;
		}

		if ( ! empty( $field_spec['description'] ) ) {
			printf( '<p class="description">%s</p>', esc_html( $field_spec['description'] ) );
		}
	}

	/**
	 * Retrieve configuration value
	 *
	 * @param string $section_key Section identifier
	 * @param string $field_key Field identifier
	 * @param mixed $fallback_value Default value if not set
	 * @return mixed Configuration value
	 */
	public function retrieve_option_value( $section_key, $field_key, $fallback_value = '' ) {
		$section_identifier = $this->option_namespace . '_' . $section_key;
		$section_data = get_option( $section_identifier, [] );
		
		if ( isset( $section_data[ $field_key ] ) ) {
			return $section_data[ $field_key ];
		}
		
		return $fallback_value;
	}

	/**
	 * Sanitize section configuration data
	 *
	 * @param array $input_data Raw input data
	 * @return array Sanitized data
	 */
	public function sanitize_section_data( $input_data ) {
		if ( ! is_array( $input_data ) ) {
			return [];
		}

		$cleaned_data = [];

		foreach ( $input_data as $key => $value ) {
			if ( is_numeric( $value ) ) {
				$cleaned_data[ $key ] = absint( $value );
			} elseif ( filter_var( $value, FILTER_VALIDATE_URL ) ) {
				$cleaned_data[ $key ] = esc_url_raw( $value );
			} else {
				$cleaned_data[ $key ] = sanitize_text_field( $value );
			}
		}

		return $cleaned_data;
	}

	/**
	 * Save configuration via admin_post handler
	 */
	public function persist_configuration() {
		// Verify security token
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'synpat_config_save' ) ) {
			wp_die( esc_html__( 'Security check failed', 'synpat-platform' ) );
		}

		// Verify permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions', 'synpat-platform' ) );
		}

		// Process each section
		foreach ( $this->configuration_schema as $section_key => $section_config ) {
			$section_identifier = $this->option_namespace . '_' . $section_key;
			
			if ( isset( $_POST[ $section_identifier ] ) ) {
				$sanitized_data = $this->sanitize_section_data( $_POST[ $section_identifier ] );
				update_option( $section_identifier, $sanitized_data );
			}
		}

		// Navigate back with success notification
		wp_safe_redirect( add_query_arg( [
			'page' => 'synpat-settings',
			'notice' => 'config_updated',
		], admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Get all settings as associative array
	 *
	 * @return array All configuration values
	 */
	public function fetch_all_configuration() {
		$all_config = [];

		foreach ( $this->configuration_schema as $section_key => $section_config ) {
			foreach ( $section_config['fields'] as $field_key => $field_spec ) {
				$value = $this->retrieve_option_value( $section_key, $field_key, $field_spec['default'] );
				$all_config[ $field_key ] = $value;
			}
		}

		return $all_config;
	}

	/**
	 * Get settings schema for external use
	 *
	 * @return array Configuration schema
	 */
	public function get_schema() {
		return $this->configuration_schema;
	}
}
