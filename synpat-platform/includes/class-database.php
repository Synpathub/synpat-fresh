<?php
/**
 * Database Abstraction Layer
 * Handles all database operations with proper escaping and prepared statements
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Database {

	/**
	 * WordPress database object
	 */
	private $wpdb;

	/**
	 * Table name mappings
	 */
	private $tables = [];

	/**
	 * Initialize database handler
	 */
	public function __construct() {
		global $wpdb;
		$this->wpdb = $wpdb;
		$this->setup_table_references();
	}

	/**
	 * Define all custom table names
	 */
	private function setup_table_references() {
		$prefix = $this->wpdb->prefix;
		
		$this->tables = [
			'portfolios'         => $prefix . 'synpat_portfolios',
			'patents'            => $prefix . 'synpat_patents',
			'portfolio_patents'  => $prefix . 'synpat_portfolio_patents',
			'licensees'          => $prefix . 'synpat_licensees',
			'wishlist'           => $prefix . 'synpat_customer_wishlist',
			'tech_prefs'         => $prefix . 'synpat_tech_preferences',
			'claim_charts'       => $prefix . 'synpat_claim_charts',
			'prior_art'          => $prefix . 'synpat_prior_art_reports',
			'expert_analysis'    => $prefix . 'synpat_expert_analysis',
		];
	}

	/**
	 * Get table name by key
	 */
	public function table( $key ) {
		return isset( $this->tables[ $key ] ) ? $this->tables[ $key ] : '';
	}

	/**
	 * Fetch a single portfolio by ID
	 */
	public function get_portfolio( $portfolio_id ) {
		$table = $this->table( 'portfolios' );
		
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$portfolio_id
		);
		
		$result = $this->wpdb->get_row( $query );
		
		return apply_filters( 'synpat_get_portfolio', $result, $portfolio_id );
	}

	/**
	 * Fetch all portfolios with optional filters
	 */
	public function get_portfolios( $args = [] ) {
		$defaults = [
			'limit'    => 20,
			'offset'   => 0,
			'orderby'  => 'id',
			'order'    => 'DESC',
			'status'   => 'active',
		];
		
		$args = wp_parse_args( $args, $defaults );
		$table = $this->table( 'portfolios' );
		
		$where_clauses = [];
		$prepare_values = [];
		
		if ( ! empty( $args['status'] ) ) {
			$where_clauses[] = 'status = %s';
			$prepare_values[] = $args['status'];
		}
		
		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';
		
		$orderby = esc_sql( $args['orderby'] );
		$order = esc_sql( $args['order'] );
		$prepare_values[] = absint( $args['limit'] );
		$prepare_values[] = absint( $args['offset'] );
		
		$query = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		
		if ( ! empty( $prepare_values ) ) {
			$query = $this->wpdb->prepare( $query, $prepare_values );
		}
		
		return $this->wpdb->get_results( $query );
	}

	/**
	 * Fetch a single patent by ID
	 */
	public function get_patent( $patent_id ) {
		$table = $this->table( 'patents' );
		
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$patent_id
		);
		
		return $this->wpdb->get_row( $query );
	}

	/**
	 * Fetch patents belonging to a portfolio
	 */
	public function get_portfolio_patents( $portfolio_id ) {
		$patents_table = $this->table( 'patents' );
		$junction_table = $this->table( 'portfolio_patents' );
		
		$query = $this->wpdb->prepare(
			"SELECT p.* FROM {$patents_table} p
			INNER JOIN {$junction_table} pp ON p.id = pp.patent_id
			WHERE pp.portfolio_id = %d
			ORDER BY p.patent_number",
			$portfolio_id
		);
		
		return $this->wpdb->get_results( $query );
	}

	/**
	 * Insert a new portfolio record
	 */
	public function create_portfolio( $data ) {
		$table = $this->table( 'portfolios' );
		
		$sanitized_data = $this->sanitize_portfolio_data( $data );
		
		$result = $this->wpdb->insert( $table, $sanitized_data );
		
		if ( $result ) {
			$portfolio_id = $this->wpdb->insert_id;
			do_action( 'synpat_portfolio_created', $portfolio_id );
			return $portfolio_id;
		}
		
		return false;
	}

	/**
	 * Update an existing portfolio
	 */
	public function update_portfolio( $portfolio_id, $data ) {
		$table = $this->table( 'portfolios' );
		
		$sanitized_data = $this->sanitize_portfolio_data( $data );
		
		$result = $this->wpdb->update(
			$table,
			$sanitized_data,
			[ 'id' => absint( $portfolio_id ) ]
		);
		
		return $result !== false;
	}

	/**
	 * Sanitize portfolio data before database operation
	 */
	private function sanitize_portfolio_data( $data ) {
		$clean_data = [];
		
		$allowed_fields = [
			'title' => 'sanitize_text_field',
			'description' => 'wp_kses_post',
			'n_patents' => 'absint',
			'essnt' => 'absint',
			'n_lic' => 'absint',
			'u_upfront' => 'floatval',
			'status' => 'sanitize_key',
		];
		
		foreach ( $allowed_fields as $field => $sanitizer ) {
			if ( isset( $data[ $field ] ) ) {
				$clean_data[ $field ] = call_user_func( $sanitizer, $data[ $field ] );
			}
		}
		
		return $clean_data;
	}

	/**
	 * Add item to customer wishlist
	 */
	public function add_to_wishlist( $user_id, $portfolio_id ) {
		$table = $this->table( 'wishlist' );
		
		// Check if already exists
		$exists = $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d AND portfolio_id = %d",
			$user_id,
			$portfolio_id
		) );
		
		if ( $exists ) {
			return $exists;
		}
		
		$result = $this->wpdb->insert( $table, [
			'user_id' => absint( $user_id ),
			'portfolio_id' => absint( $portfolio_id ),
			'created_at' => current_time( 'mysql' ),
		] );
		
		return $result ? $this->wpdb->insert_id : false;
	}

	/**
	 * Remove item from wishlist
	 */
	public function remove_from_wishlist( $user_id, $portfolio_id ) {
		$table = $this->table( 'wishlist' );
		
		return $this->wpdb->delete( $table, [
			'user_id' => absint( $user_id ),
			'portfolio_id' => absint( $portfolio_id ),
		] );
	}

	/**
	 * Get user's wishlist portfolios
	 */
	public function get_user_wishlist( $user_id ) {
		$portfolios_table = $this->table( 'portfolios' );
		$wishlist_table = $this->table( 'wishlist' );
		
		$query = $this->wpdb->prepare(
			"SELECT p.* FROM {$portfolios_table} p
			INNER JOIN {$wishlist_table} w ON p.id = w.portfolio_id
			WHERE w.user_id = %d
			ORDER BY w.created_at DESC",
			$user_id
		);
		
		return $this->wpdb->get_results( $query );
	}

	/**
	 * Execute a custom search query
	 */
	public function search_portfolios( $search_term ) {
		$table = $this->table( 'portfolios' );
		
		$search_pattern = '%' . $this->wpdb->esc_like( $search_term ) . '%';
		
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$table} 
			WHERE title LIKE %s 
			OR description LIKE %s
			ORDER BY title ASC",
			$search_pattern,
			$search_pattern
		);
		
		$results = $this->wpdb->get_results( $query );
		
		return apply_filters( 'synpat_search_results', $results, $search_term );
	}

	/**
	 * Get claim chart by ID
	 */
	public function get_claim_chart( $chart_id ) {
		$table = $this->table( 'claim_charts' );
		
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$table} WHERE id = %d",
			$chart_id
		);
		
		return $this->wpdb->get_row( $query );
	}

	/**
	 * Get all claim charts for a patent
	 */
	public function get_patent_claim_charts( $patent_id ) {
		$table = $this->table( 'claim_charts' );
		
		$query = $this->wpdb->prepare(
			"SELECT * FROM {$table} WHERE patent_id = %d ORDER BY created_at DESC",
			$patent_id
		);
		
		return $this->wpdb->get_results( $query );
	}
}
