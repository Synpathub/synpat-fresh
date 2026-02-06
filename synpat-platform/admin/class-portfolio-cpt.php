<?php
/**
 * Portfolio Custom Post Type Handler
 * Manages portfolio content type registration and meta fields
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Portfolio_CPT {

	private $content_type_slug = 'synpat_portfolio';
	private $taxonomy_slug = 'portfolio_category';
	private $meta_field_registry;
	private $data_bridge;

	public function __construct() {
		$this->data_bridge = new SynPat_Database();
		$this->setup_meta_fields();
		$this->bind_wordpress_lifecycle();
	}

	private function bind_wordpress_lifecycle() {
		add_action( 'init', [ $this, 'declare_content_type' ], 5 );
		add_action( 'init', [ $this, 'declare_taxonomies' ], 6 );
		add_action( 'add_meta_boxes', [ $this, 'attach_meta_containers' ] );
		add_action( 'save_post_' . $this->content_type_slug, [ $this, 'persist_meta_values' ], 10, 2 );
		add_filter( 'manage_' . $this->content_type_slug . '_posts_columns', [ $this, 'customize_column_headers' ] );
		add_action( 'manage_' . $this->content_type_slug . '_posts_custom_column', [ $this, 'populate_column_data' ], 10, 2 );
	}

	private function setup_meta_fields() {
		$this->meta_field_registry = [
			'portfolio_db_id' => [
				'label' => 'Database ID',
				'type' => 'number',
				'readonly' => true,
			],
			'patent_total' => [
				'label' => 'Total Patents',
				'type' => 'number',
				'min' => 0,
			],
			'essential_count' => [
				'label' => 'Essential Patents',
				'type' => 'number',
				'min' => 0,
			],
			'licensee_total' => [
				'label' => 'Current Licensees',
				'type' => 'number',
				'min' => 0,
			],
			'upfront_amount' => [
				'label' => 'Upfront Fee (USD)',
				'type' => 'number',
				'step' => '0.01',
			],
			'portfolio_status' => [
				'label' => 'Availability Status',
				'type' => 'select',
				'options' => [
					'active' => 'Active',
					'pending' => 'Pending',
					'archived' => 'Archived',
				],
			],
		];
	}

	public function declare_content_type() {
		$type_labels = [
			'name' => esc_html_x( 'Portfolios', 'Post type general name', 'synpat-platform' ),
			'singular_name' => esc_html_x( 'Portfolio', 'Post type singular name', 'synpat-platform' ),
			'add_new' => esc_html_x( 'Add New', 'portfolio', 'synpat-platform' ),
			'add_new_item' => esc_html__( 'Add New Portfolio', 'synpat-platform' ),
			'edit_item' => esc_html__( 'Edit Portfolio', 'synpat-platform' ),
			'new_item' => esc_html__( 'New Portfolio', 'synpat-platform' ),
			'view_item' => esc_html__( 'View Portfolio', 'synpat-platform' ),
			'search_items' => esc_html__( 'Search Portfolios', 'synpat-platform' ),
			'not_found' => esc_html__( 'No portfolios found', 'synpat-platform' ),
			'not_found_in_trash' => esc_html__( 'No portfolios found in Trash', 'synpat-platform' ),
			'menu_name' => esc_html__( 'Portfolios', 'synpat-platform' ),
		];

		$type_configuration = [
			'labels' => $type_labels,
			'public' => true,
			'publicly_queryable' => true,
			'show_ui' => true,
			'show_in_menu' => false,
			'query_var' => true,
			'rewrite' => [ 'slug' => 'patent-portfolio' ],
			'capability_type' => 'post',
			'has_archive' => true,
			'hierarchical' => false,
			'menu_position' => null,
			'supports' => [ 'title', 'editor', 'thumbnail', 'excerpt' ],
			'show_in_rest' => true,
		];

		register_post_type( $this->content_type_slug, $type_configuration );
	}

	public function declare_taxonomies() {
		$category_labels = [
			'name' => esc_html_x( 'Portfolio Categories', 'taxonomy general name', 'synpat-platform' ),
			'singular_name' => esc_html_x( 'Portfolio Category', 'taxonomy singular name', 'synpat-platform' ),
			'search_items' => esc_html__( 'Search Categories', 'synpat-platform' ),
			'all_items' => esc_html__( 'All Categories', 'synpat-platform' ),
			'edit_item' => esc_html__( 'Edit Category', 'synpat-platform' ),
			'update_item' => esc_html__( 'Update Category', 'synpat-platform' ),
			'add_new_item' => esc_html__( 'Add New Category', 'synpat-platform' ),
			'new_item_name' => esc_html__( 'New Category Name', 'synpat-platform' ),
			'menu_name' => esc_html__( 'Categories', 'synpat-platform' ),
		];

		register_taxonomy( $this->taxonomy_slug, [ $this->content_type_slug ], [
			'hierarchical' => true,
			'labels' => $category_labels,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true,
			'rewrite' => [ 'slug' => 'portfolio-category' ],
			'show_in_rest' => true,
		] );
	}

	public function attach_meta_containers() {
		add_meta_box(
			'synpat_portfolio_details',
			esc_html__( 'Portfolio Details', 'synpat-platform' ),
			[ $this, 'render_details_container' ],
			$this->content_type_slug,
			'normal',
			'high'
		);

		add_meta_box(
			'synpat_portfolio_sync',
			esc_html__( 'Database Synchronization', 'synpat-platform' ),
			[ $this, 'render_sync_container' ],
			$this->content_type_slug,
			'side',
			'default'
		);
	}

	public function render_details_container( $post_object ) {
		wp_nonce_field( 'synpat_portfolio_meta_save', 'synpat_portfolio_meta_nonce' );
		
		echo '<div class="synpat-meta-fields">';
		
		foreach ( $this->meta_field_registry as $field_key => $field_config ) {
			$stored_value = get_post_meta( $post_object->ID, '_synpat_' . $field_key, true );
			$field_id = 'synpat_meta_' . $field_key;
			
			echo '<div class="synpat-field-group">';
			printf( '<label for="%s"><strong>%s</strong></label><br>', esc_attr( $field_id ), esc_html( $field_config['label'] ) );
			
			$this->render_field_input( $field_id, $field_key, $field_config, $stored_value );
			
			echo '</div>';
		}
		
		echo '</div>';
	}

	private function render_field_input( $field_id, $field_key, $field_config, $current_value ) {
		$field_name = 'synpat_meta[' . $field_key . ']';
		$is_readonly = ! empty( $field_config['readonly'] );
		
		if ( $field_config['type'] === 'select' ) {
			printf( '<select id="%s" name="%s" %s>', 
				esc_attr( $field_id ), 
				esc_attr( $field_name ),
				disabled( $is_readonly, true, false )
			);
			
			foreach ( $field_config['options'] as $opt_value => $opt_label ) {
				printf(
					'<option value="%s" %s>%s</option>',
					esc_attr( $opt_value ),
					selected( $current_value, $opt_value, false ),
					esc_html( $opt_label )
				);
			}
			
			echo '</select>';
		} else {
			$input_attrs = sprintf(
				'id="%s" name="%s" value="%s" %s',
				esc_attr( $field_id ),
				esc_attr( $field_name ),
				esc_attr( $current_value ),
				disabled( $is_readonly, true, false )
			);
			
			if ( isset( $field_config['min'] ) ) {
				$input_attrs .= sprintf( ' min="%s"', esc_attr( $field_config['min'] ) );
			}
			
			if ( isset( $field_config['step'] ) ) {
				$input_attrs .= sprintf( ' step="%s"', esc_attr( $field_config['step'] ) );
			}
			
			printf( '<input type="%s" %s class="widefat" />', 
				esc_attr( $field_config['type'] ), 
				$input_attrs 
			);
		}
	}

	public function render_sync_container( $post_object ) {
		$db_record_id = get_post_meta( $post_object->ID, '_synpat_portfolio_db_id', true );
		
		echo '<div class="synpat-sync-status">';
		
		if ( $db_record_id ) {
			$database_record = $this->data_bridge->get_portfolio( $db_record_id );
			
			if ( $database_record ) {
				echo '<p><span class="dashicons dashicons-yes-alt" style="color: green;"></span> ';
				printf( esc_html__( 'Synced with DB record #%d', 'synpat-platform' ), absint( $db_record_id ) );
				echo '</p>';
				
				printf(
					'<p><a href="%s" class="button button-small">%s</a></p>',
					esc_url( wp_nonce_url( 
						admin_url( 'admin-post.php?action=synpat_sync_portfolio&post_id=' . $post_object->ID ),
						'synpat_sync_action'
					) ),
					esc_html__( 'Refresh from Database', 'synpat-platform' )
				);
			} else {
				echo '<p><span class="dashicons dashicons-warning" style="color: orange;"></span> ';
				esc_html_e( 'Database record not found', 'synpat-platform' );
				echo '</p>';
			}
		} else {
			echo '<p><span class="dashicons dashicons-info" style="color: gray;"></span> ';
			esc_html_e( 'Not linked to database', 'synpat-platform' );
			echo '</p>';
			
			printf(
				'<p><a href="%s" class="button button-small">%s</a></p>',
				esc_url( wp_nonce_url( 
					admin_url( 'admin-post.php?action=synpat_create_db_record&post_id=' . $post_object->ID ),
					'synpat_create_action'
				) ),
				esc_html__( 'Create Database Record', 'synpat-platform' )
			);
		}
		
		echo '</div>';
	}

	public function persist_meta_values( $post_id, $post_object ) {
		// Security validations
		if ( ! isset( $_POST['synpat_portfolio_meta_nonce'] ) ) {
			return;
		}
		
		if ( ! wp_verify_nonce( $_POST['synpat_portfolio_meta_nonce'], 'synpat_portfolio_meta_save' ) ) {
			return;
		}
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Process meta fields
		if ( isset( $_POST['synpat_meta'] ) && is_array( $_POST['synpat_meta'] ) ) {
			foreach ( $_POST['synpat_meta'] as $meta_key => $meta_value ) {
				if ( ! isset( $this->meta_field_registry[ $meta_key ] ) ) {
					continue;
				}
				
				$field_spec = $this->meta_field_registry[ $meta_key ];
				
				if ( ! empty( $field_spec['readonly'] ) ) {
					continue;
				}
				
				$sanitized_value = $this->sanitize_meta_value( $meta_value, $field_spec['type'] );
				update_post_meta( $post_id, '_synpat_' . $meta_key, $sanitized_value );
			}
		}
	}

	private function sanitize_meta_value( $raw_value, $field_type ) {
		switch ( $field_type ) {
			case 'number':
				return is_numeric( $raw_value ) ? floatval( $raw_value ) : 0;
			
			case 'select':
				return sanitize_key( $raw_value );
			
			default:
				return sanitize_text_field( $raw_value );
		}
	}

	public function customize_column_headers( $existing_columns ) {
		$reordered_columns = [];
		
		$reordered_columns['cb'] = $existing_columns['cb'];
		$reordered_columns['title'] = $existing_columns['title'];
		$reordered_columns['patent_count'] = esc_html__( 'Patents', 'synpat-platform' );
		$reordered_columns['essential_count'] = esc_html__( 'Essential', 'synpat-platform' );
		$reordered_columns['status_indicator'] = esc_html__( 'Status', 'synpat-platform' );
		$reordered_columns['date'] = $existing_columns['date'];
		
		return $reordered_columns;
	}

	public function populate_column_data( $column_id, $post_id ) {
		switch ( $column_id ) {
			case 'patent_count':
				$count = get_post_meta( $post_id, '_synpat_patent_total', true );
				echo esc_html( $count ? $count : '0' );
				break;
			
			case 'essential_count':
				$essential = get_post_meta( $post_id, '_synpat_essential_count', true );
				echo esc_html( $essential ? $essential : '0' );
				break;
			
			case 'status_indicator':
				$status = get_post_meta( $post_id, '_synpat_portfolio_status', true );
				$status = $status ? $status : 'active';
				$badge_color = $status === 'active' ? 'green' : ( $status === 'pending' ? 'orange' : 'gray' );
				printf(
					'<span style="color: %s;">● %s</span>',
					esc_attr( $badge_color ),
					esc_html( ucfirst( $status ) )
				);
				break;
		}
	}
}
