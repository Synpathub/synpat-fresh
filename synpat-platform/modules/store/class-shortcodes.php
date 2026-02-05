<?php
/**
 * Shortcode Handlers
 * Register and handle all public shortcodes
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Shortcodes {

	/**
	 * Store frontend instance
	 */
	private $store;

	/**
	 * Initialize shortcodes
	 */
	public function __construct() {
		$this->store = new SynPat_Store_Frontend();
		$this->register_all_shortcodes();
	}

	/**
	 * Register shortcodes with WordPress
	 */
	private function register_all_shortcodes() {
		add_shortcode( 'portfolio_catalog', [ $this, 'portfolio_catalog_handler' ] );
		add_shortcode( 'portfolio_details', [ $this, 'portfolio_details_handler' ] );
		add_shortcode( 'patent_inner_room', [ $this, 'patent_inner_room_handler' ] );
		add_shortcode( 'customer_wishlist', [ $this, 'customer_wishlist_handler' ] );
		
		// Pro tools shortcodes (only if addon is active)
		if ( $this->is_pro_tools_active() ) {
			add_shortcode( 'claim_chart', [ $this, 'claim_chart_handler' ] );
			add_shortcode( 'prior_art', [ $this, 'prior_art_handler' ] );
			add_shortcode( 'expert_analysis', [ $this, 'expert_analysis_handler' ] );
		}
	}

	/**
	 * Handle [portfolio_catalog] shortcode
	 *
	 * Usage: [portfolio_catalog per_page="12" orderby="id" order="DESC"]
	 */
	public function portfolio_catalog_handler( $atts ) {
		$attributes = shortcode_atts( [
			'per_page' => 12,
			'orderby' => 'id',
			'order' => 'DESC',
			'category' => '',
		], $atts );
		
		return $this->store->render_catalog( $attributes );
	}

	/**
	 * Handle [portfolio_details] shortcode
	 *
	 * Usage: [portfolio_details id="123"]
	 */
	public function portfolio_details_handler( $atts ) {
		$attributes = shortcode_atts( [
			'id' => 0,
		], $atts );
		
		$portfolio_id = absint( $attributes['id'] );
		
		if ( ! $portfolio_id ) {
			// Try to get from query parameter
			$portfolio_id = absint( $_GET['portfolio_id'] ?? 0 );
		}
		
		if ( ! $portfolio_id ) {
			return '<div class="synpat-error">' . 
			       esc_html__( 'Portfolio ID is required', 'synpat-platform' ) . 
			       '</div>';
		}
		
		return $this->store->render_portfolio_single( $portfolio_id );
	}

	/**
	 * Handle [patent_inner_room] shortcode
	 *
	 * Usage: [patent_inner_room id="456"]
	 */
	public function patent_inner_room_handler( $atts ) {
		$attributes = shortcode_atts( [
			'id' => 0,
		], $atts );
		
		$patent_id = absint( $attributes['id'] );
		
		if ( ! $patent_id ) {
			// Try to get from query parameter
			$patent_id = absint( $_GET['patent_id'] ?? 0 );
		}
		
		if ( ! $patent_id ) {
			return '<div class="synpat-error">' . 
			       esc_html__( 'Patent ID is required', 'synpat-platform' ) . 
			       '</div>';
		}
		
		return $this->store->render_patent_details( $patent_id );
	}

	/**
	 * Handle [customer_wishlist] shortcode
	 *
	 * Usage: [customer_wishlist]
	 */
	public function customer_wishlist_handler( $atts ) {
		return $this->store->render_wishlist();
	}

	/**
	 * Handle [claim_chart] shortcode (Pro Tools)
	 *
	 * Usage: [claim_chart id="789"]
	 */
	public function claim_chart_handler( $atts ) {
		$attributes = shortcode_atts( [
			'id' => 0,
		], $atts );
		
		$chart_id = absint( $attributes['id'] );
		
		if ( ! $chart_id ) {
			return '<div class="synpat-error">' . 
			       esc_html__( 'Claim chart ID is required', 'synpat-platform' ) . 
			       '</div>';
		}
		
		// Allow pro tools to handle rendering
		$output = apply_filters( 'synpat_render_claim_chart', '', $chart_id );
		
		if ( empty( $output ) ) {
			return '<div class="synpat-notice">' . 
			       esc_html__( 'Claim chart not available', 'synpat-platform' ) . 
			       '</div>';
		}
		
		return $output;
	}

	/**
	 * Handle [prior_art] shortcode (Pro Tools)
	 *
	 * Usage: [prior_art id="789"]
	 */
	public function prior_art_handler( $atts ) {
		$attributes = shortcode_atts( [
			'id' => 0,
		], $atts );
		
		$report_id = absint( $attributes['id'] );
		
		if ( ! $report_id ) {
			return '<div class="synpat-error">' . 
			       esc_html__( 'Prior art report ID is required', 'synpat-platform' ) . 
			       '</div>';
		}
		
		// Allow pro tools to handle rendering
		$output = apply_filters( 'synpat_render_prior_art', '', $report_id );
		
		if ( empty( $output ) ) {
			return '<div class="synpat-notice">' . 
			       esc_html__( 'Prior art report not available', 'synpat-platform' ) . 
			       '</div>';
		}
		
		return $output;
	}

	/**
	 * Handle [expert_analysis] shortcode (Pro Tools)
	 *
	 * Usage: [expert_analysis id="789"]
	 */
	public function expert_analysis_handler( $atts ) {
		$attributes = shortcode_atts( [
			'id' => 0,
			'type' => 'all',
		], $atts );
		
		$analysis_id = absint( $attributes['id'] );
		
		if ( ! $analysis_id ) {
			return '<div class="synpat-error">' . 
			       esc_html__( 'Analysis ID is required', 'synpat-platform' ) . 
			       '</div>';
		}
		
		// Allow pro tools to handle rendering
		$output = apply_filters( 'synpat_render_expert_analysis', '', $analysis_id, $attributes['type'] );
		
		if ( empty( $output ) ) {
			return '<div class="synpat-notice">' . 
			       esc_html__( 'Expert analysis not available', 'synpat-platform' ) . 
			       '</div>';
		}
		
		return $output;
	}

	/**
	 * Check if Pro Tools addon is active
	 */
	private function is_pro_tools_active() {
		return defined( 'SYNPAT_PRO_TOOLS_VERSION' );
	}
}
