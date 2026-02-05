<?php
/**
 * AJAX Request Handlers
 * Handle all AJAX operations for wishlist and user interactions
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Ajax_Handlers {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Auth handler
	 */
	private $auth;

	/**
	 * Initialize AJAX handlers
	 */
	public function __construct() {
		$this->db = new SynPat_Database();
		$this->auth = new SynPat_Auth();
		$this->register_ajax_actions();
	}

	/**
	 * Register all AJAX endpoints
	 */
	private function register_ajax_actions() {
		// Logged in users
		add_action( 'wp_ajax_synpat_add_to_wishlist', [ $this, 'handle_add_to_wishlist' ] );
		add_action( 'wp_ajax_synpat_remove_from_wishlist', [ $this, 'handle_remove_from_wishlist' ] );
		add_action( 'wp_ajax_synpat_get_wishlist', [ $this, 'handle_get_wishlist' ] );
		add_action( 'wp_ajax_synpat_search_portfolios', [ $this, 'handle_search_portfolios' ] );
		add_action( 'wp_ajax_synpat_save_tech_preference', [ $this, 'handle_save_tech_preference' ] );
		
		// Public (logged out users)
		add_action( 'wp_ajax_nopriv_synpat_search_portfolios', [ $this, 'handle_search_portfolios' ] );
	}

	/**
	 * Add portfolio to wishlist
	 */
	public function handle_add_to_wishlist() {
		// Verify nonce
		if ( ! $this->auth->verify_ajax_nonce( $_POST['nonce'] ?? '', 'synpat_store_nonce' ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed', 'synpat-platform' ),
			], 403 );
		}
		
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [
				'message' => __( 'You must be logged in', 'synpat-platform' ),
			], 401 );
		}
		
		$portfolio_id = absint( $_POST['portfolio_id'] ?? 0 );
		
		if ( ! $portfolio_id ) {
			wp_send_json_error( [
				'message' => __( 'Invalid portfolio ID', 'synpat-platform' ),
			], 400 );
		}
		
		$user_id = get_current_user_id();
		$result = $this->db->add_to_wishlist( $user_id, $portfolio_id );
		
		if ( $result ) {
			wp_send_json_success( [
				'message' => __( 'Added to wishlist', 'synpat-platform' ),
				'wishlist_id' => $result,
			] );
		} else {
			wp_send_json_error( [
				'message' => __( 'Failed to add to wishlist', 'synpat-platform' ),
			], 500 );
		}
	}

	/**
	 * Remove portfolio from wishlist
	 */
	public function handle_remove_from_wishlist() {
		// Verify nonce
		if ( ! $this->auth->verify_ajax_nonce( $_POST['nonce'] ?? '', 'synpat_store_nonce' ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed', 'synpat-platform' ),
			], 403 );
		}
		
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [
				'message' => __( 'You must be logged in', 'synpat-platform' ),
			], 401 );
		}
		
		$portfolio_id = absint( $_POST['portfolio_id'] ?? 0 );
		
		if ( ! $portfolio_id ) {
			wp_send_json_error( [
				'message' => __( 'Invalid portfolio ID', 'synpat-platform' ),
			], 400 );
		}
		
		$user_id = get_current_user_id();
		$result = $this->db->remove_from_wishlist( $user_id, $portfolio_id );
		
		if ( $result ) {
			wp_send_json_success( [
				'message' => __( 'Removed from wishlist', 'synpat-platform' ),
			] );
		} else {
			wp_send_json_error( [
				'message' => __( 'Failed to remove from wishlist', 'synpat-platform' ),
			], 500 );
		}
	}

	/**
	 * Get user's wishlist
	 */
	public function handle_get_wishlist() {
		// Verify nonce
		if ( ! $this->auth->verify_ajax_nonce( $_POST['nonce'] ?? '', 'synpat_store_nonce' ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed', 'synpat-platform' ),
			], 403 );
		}
		
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [
				'message' => __( 'You must be logged in', 'synpat-platform' ),
			], 401 );
		}
		
		$user_id = get_current_user_id();
		$wishlist = $this->db->get_user_wishlist( $user_id );
		
		wp_send_json_success( [
			'wishlist' => $wishlist,
			'count' => count( $wishlist ),
		] );
	}

	/**
	 * Search portfolios
	 */
	public function handle_search_portfolios() {
		$search_term = sanitize_text_field( $_POST['search'] ?? '' );
		
		if ( empty( $search_term ) ) {
			wp_send_json_error( [
				'message' => __( 'Search term is required', 'synpat-platform' ),
			], 400 );
		}
		
		$results = $this->db->search_portfolios( $search_term );
		
		wp_send_json_success( [
			'results' => $results,
			'count' => count( $results ),
		] );
	}

	/**
	 * Save technology preference
	 */
	public function handle_save_tech_preference() {
		// Verify nonce
		if ( ! $this->auth->verify_ajax_nonce( $_POST['nonce'] ?? '', 'synpat_store_nonce' ) ) {
			wp_send_json_error( [
				'message' => __( 'Security check failed', 'synpat-platform' ),
			], 403 );
		}
		
		// Check if user is logged in
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( [
				'message' => __( 'You must be logged in', 'synpat-platform' ),
			], 401 );
		}
		
		global $wpdb;
		
		$user_id = get_current_user_id();
		$technology = sanitize_text_field( $_POST['technology'] ?? '' );
		$interest_level = sanitize_text_field( $_POST['interest_level'] ?? 'medium' );
		
		if ( empty( $technology ) ) {
			wp_send_json_error( [
				'message' => __( 'Technology area is required', 'synpat-platform' ),
			], 400 );
		}
		
		$table = $wpdb->prefix . 'synpat_tech_preferences';
		
		$result = $wpdb->insert( $table, [
			'user_id' => $user_id,
			'technology_area' => $technology,
			'interest_level' => $interest_level,
		] );
		
		if ( $result ) {
			wp_send_json_success( [
				'message' => __( 'Preference saved', 'synpat-platform' ),
				'id' => $wpdb->insert_id,
			] );
		} else {
			wp_send_json_error( [
				'message' => __( 'Failed to save preference', 'synpat-platform' ),
			], 500 );
		}
	}
}
