<?php
/**
 * Patent Content Type Manager
 * Handles patent post type and associated metadata
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Patent_CPT {

	private $type_identifier = 'synpat_patent';
	private $field_definitions;
	private $repository;

	public function __construct() {
		$this->repository = new SynPat_Database();
		$this->initialize_field_definitions();
		$this->hook_into_system();
	}

	private function hook_into_system() {
		add_action( 'init', [ $this, 'register_content_structure' ], 5 );
		add_action( 'add_meta_boxes_' . $this->type_identifier, [ $this, 'setup_metadata_boxes' ] );
		add_action( 'save_post_' . $this->type_identifier, [ $this, 'handle_metadata_persistence' ], 10, 3 );
		add_filter( 'manage_' . $this->type_identifier . '_posts_columns', [ $this, 'define_list_columns' ] );
		add_action( 'manage_' . $this->type_identifier . '_posts_custom_column', [ $this, 'render_column_content' ], 10, 2 );
	}

	private function initialize_field_definitions() {
		$this->field_definitions = [
			'patent_number' => [ 'label' => 'Patent Number', 'widget' => 'text', 'required' => true ],
			'filing_date' => [ 'label' => 'Filing Date', 'widget' => 'date' ],
			'grant_date' => [ 'label' => 'Grant Date', 'widget' => 'date' ],
			'expiration_date' => [ 'label' => 'Expiration Date', 'widget' => 'date' ],
			'inventor_names' => [ 'label' => 'Inventors', 'widget' => 'textarea' ],
			'assignee_entity' => [ 'label' => 'Assignee', 'widget' => 'text' ],
			'ipc_codes' => [ 'label' => 'IPC Classification', 'widget' => 'text' ],
			'cpc_codes' => [ 'label' => 'CPC Classification', 'widget' => 'text' ],
			'abstract_text' => [ 'label' => 'Abstract', 'widget' => 'rich_textarea' ],
			'claim_text' => [ 'label' => 'Claims', 'widget' => 'rich_textarea' ],
			'db_link_id' => [ 'label' => 'Database ID', 'widget' => 'number', 'readonly' => true ],
		];
	}

	public function register_content_structure() {
		$label_set = [
			'name' => _x( 'Patents', 'content type plural', 'synpat-platform' ),
			'singular_name' => _x( 'Patent', 'content type singular', 'synpat-platform' ),
			'add_new' => _x( 'Add New', 'patent item', 'synpat-platform' ),
			'add_new_item' => __( 'Add New Patent', 'synpat-platform' ),
			'edit_item' => __( 'Edit Patent', 'synpat-platform' ),
			'new_item' => __( 'New Patent', 'synpat-platform' ),
			'view_item' => __( 'View Patent', 'synpat-platform' ),
			'search_items' => __( 'Search Patents', 'synpat-platform' ),
			'not_found' => __( 'No patents found', 'synpat-platform' ),
			'not_found_in_trash' => __( 'No patents in trash', 'synpat-platform' ),
		];

		register_post_type( $this->type_identifier, [
			'labels' => $label_set,
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => false,
			'query_var' => true,
			'rewrite' => [ 'slug' => 'patent' ],
			'capability_type' => 'post',
			'has_archive' => true,
			'supports' => [ 'title', 'editor', 'thumbnail' ],
			'show_in_rest' => true,
		] );
	}

	public function setup_metadata_boxes() {
		add_meta_box(
			'patent_core_info',
			__( 'Patent Information', 'synpat-platform' ),
			[ $this, 'render_info_metabox' ],
			$this->type_identifier,
			'normal',
			'high'
		);

		add_meta_box(
			'patent_classification',
			__( 'Classification & Dates', 'synpat-platform' ),
			[ $this, 'render_classification_metabox' ],
			$this->type_identifier,
			'normal',
			'default'
		);

		add_meta_box(
			'patent_content',
			__( 'Legal Content', 'synpat-platform' ),
			[ $this, 'render_content_metabox' ],
			$this->type_identifier,
			'normal',
			'low'
		);
	}

	public function render_info_metabox( $post ) {
		wp_nonce_field( 'patent_meta_action', 'patent_meta_nonce' );
		
		$info_fields = [ 'patent_number', 'inventor_names', 'assignee_entity', 'db_link_id' ];
		
		foreach ( $info_fields as $field_key ) {
			$this->output_field_widget( $post->ID, $field_key );
		}
	}

	public function render_classification_metabox( $post ) {
		$class_fields = [ 'filing_date', 'grant_date', 'expiration_date', 'ipc_codes', 'cpc_codes' ];
		
		foreach ( $class_fields as $field_key ) {
			$this->output_field_widget( $post->ID, $field_key );
		}
	}

	public function render_content_metabox( $post ) {
		$content_fields = [ 'abstract_text', 'claim_text' ];
		
		foreach ( $content_fields as $field_key ) {
			$this->output_field_widget( $post->ID, $field_key );
		}
	}

	private function output_field_widget( $post_id, $field_key ) {
		if ( ! isset( $this->field_definitions[ $field_key ] ) ) {
			return;
		}

		$spec = $this->field_definitions[ $field_key ];
		$meta_key = '_patent_' . $field_key;
		$stored = get_post_meta( $post_id, $meta_key, true );
		$input_name = 'patent_fields[' . $field_key . ']';
		$input_id = 'patent_field_' . $field_key;
		$is_locked = ! empty( $spec['readonly'] );

		echo '<div style="margin-bottom: 15px;">';
		printf( '<label for="%s"><strong>%s</strong>', esc_attr( $input_id ), esc_html( $spec['label'] ) );
		
		if ( ! empty( $spec['required'] ) ) {
			echo ' <span style="color:red;">*</span>';
		}
		
		echo '</label><br>';

		switch ( $spec['widget'] ) {
			case 'text':
				printf(
					'<input type="text" id="%s" name="%s" value="%s" class="widefat" %s />',
					esc_attr( $input_id ),
					esc_attr( $input_name ),
					esc_attr( $stored ),
					$is_locked ? 'readonly' : ''
				);
				break;

			case 'number':
				printf(
					'<input type="number" id="%s" name="%s" value="%s" %s />',
					esc_attr( $input_id ),
					esc_attr( $input_name ),
					esc_attr( $stored ),
					$is_locked ? 'readonly' : ''
				);
				break;

			case 'date':
				printf(
					'<input type="date" id="%s" name="%s" value="%s" />',
					esc_attr( $input_id ),
					esc_attr( $input_name ),
					esc_attr( $stored )
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="%s" name="%s" rows="3" class="widefat">%s</textarea>',
					esc_attr( $input_id ),
					esc_attr( $input_name ),
					esc_textarea( $stored )
				);
				break;

			case 'rich_textarea':
				wp_editor( $stored, $input_id, [
					'textarea_name' => $input_name,
					'textarea_rows' => 10,
					'media_buttons' => false,
				] );
				break;
		}

		echo '</div>';
	}

	public function handle_metadata_persistence( $post_id, $post, $is_update ) {
		if ( ! isset( $_POST['patent_meta_nonce'] ) || ! wp_verify_nonce( $_POST['patent_meta_nonce'], 'patent_meta_action' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['patent_fields'] ) || ! is_array( $_POST['patent_fields'] ) ) {
			return;
		}

		foreach ( $_POST['patent_fields'] as $key => $value ) {
			if ( ! isset( $this->field_definitions[ $key ] ) ) {
				continue;
			}

			$spec = $this->field_definitions[ $key ];
			
			if ( ! empty( $spec['readonly'] ) ) {
				continue;
			}

			$clean_value = $this->clean_field_value( $value, $spec['widget'] );
			update_post_meta( $post_id, '_patent_' . $key, $clean_value );
		}
	}

	private function clean_field_value( $raw, $widget_type ) {
		switch ( $widget_type ) {
			case 'number':
				return absint( $raw );
			
			case 'date':
				return sanitize_text_field( $raw );
			
			case 'rich_textarea':
				return wp_kses_post( $raw );
			
			case 'textarea':
				return sanitize_textarea_field( $raw );
			
			default:
				return sanitize_text_field( $raw );
		}
	}

	public function define_list_columns( $columns ) {
		$new_layout = [
			'cb' => $columns['cb'],
			'title' => $columns['title'],
			'patent_number' => __( 'Patent #', 'synpat-platform' ),
			'filing_date' => __( 'Filed', 'synpat-platform' ),
			'assignee' => __( 'Assignee', 'synpat-platform' ),
			'date' => $columns['date'],
		];
		
		return $new_layout;
	}

	public function render_column_content( $column_name, $post_id ) {
		switch ( $column_name ) {
			case 'patent_number':
				echo esc_html( get_post_meta( $post_id, '_patent_patent_number', true ) );
				break;
			
			case 'filing_date':
				$date = get_post_meta( $post_id, '_patent_filing_date', true );
				if ( $date ) {
					echo esc_html( date_i18n( 'M j, Y', strtotime( $date ) ) );
				}
				break;
			
			case 'assignee':
				echo esc_html( get_post_meta( $post_id, '_patent_assignee_entity', true ) );
				break;
		}
	}
}
