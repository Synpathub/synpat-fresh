<?php
/**
 * Hook Registry System
 * Manages WordPress hooks for addon plugin integration
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Hooks {

	/**
	 * Registry of registered addon modules
	 */
	private $registered_modules = [];

	/**
	 * Initialize hook system
	 */
	public function __construct() {
		$this->setup_platform_hooks();
	}

	/**
	 * Define core platform hooks
	 */
	private function setup_platform_hooks() {
		// Allow addons to register themselves
		add_action( 'synpat_register_modules', [ $this, 'collect_module_registrations' ], 5 );
	}

	/**
	 * Collect module registrations from addons
	 */
	public function collect_module_registrations() {
		$this->registered_modules = apply_filters( 'synpat_addon_modules', [] );
	}

	/**
	 * Get list of registered addon modules
	 */
	public function get_registered_modules() {
		return $this->registered_modules;
	}

	/**
	 * Fire hook after portfolio is created
	 */
	public static function trigger_portfolio_created( $portfolio_id ) {
		do_action( 'synpat_portfolio_created', $portfolio_id );
	}

	/**
	 * Fire hook after PDF is generated
	 */
	public static function trigger_pdf_generated( $pdf_id, $file_path ) {
		do_action( 'synpat_pdf_generated', $pdf_id, $file_path );
	}

	/**
	 * Fire hook after analysis completes
	 */
	public static function trigger_analysis_complete( $analysis_data ) {
		do_action( 'synpat_analysis_complete', $analysis_data );
	}

	/**
	 * Filter portfolio data before returning
	 */
	public static function filter_portfolio_data( $portfolio, $portfolio_id ) {
		return apply_filters( 'synpat_get_portfolio', $portfolio, $portfolio_id );
	}

	/**
	 * Filter PDF template before rendering
	 */
	public static function filter_pdf_template( $template, $type ) {
		return apply_filters( 'synpat_pdf_template', $template, $type );
	}

	/**
	 * Filter search results
	 */
	public static function filter_search_results( $results, $query ) {
		return apply_filters( 'synpat_search_results', $results, $query );
	}

	/**
	 * Check if a specific module is registered
	 */
	public function is_module_active( $module_name ) {
		return isset( $this->registered_modules[ $module_name ] );
	}
}
