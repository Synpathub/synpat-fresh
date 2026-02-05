<?php
/**
 * Uninstall Handler
 * Clean up when plugin is deleted
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

// Ensure this is being executed through WordPress uninstall process
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Remove all plugin data on uninstall
 * Only executes when user explicitly deletes the plugin
 */
function synpat_platform_uninstall() {
	global $wpdb;
	
	// Get confirmation from option (set in admin settings)
	$remove_data = get_option( 'synpat_remove_data_on_uninstall', false );
	
	if ( ! $remove_data ) {
		// Keep data for safety unless explicitly requested to remove
		return;
	}
	
	// Drop all custom tables
	$prefix = $wpdb->prefix;
	$tables_to_remove = [
		$prefix . 'synpat_portfolios',
		$prefix . 'synpat_patents',
		$prefix . 'synpat_portfolio_patents',
		$prefix . 'synpat_licensees',
		$prefix . 'synpat_customer_wishlist',
		$prefix . 'synpat_tech_preferences',
		$prefix . 'synpat_claim_charts',
		$prefix . 'synpat_prior_art_reports',
		$prefix . 'synpat_expert_analysis',
	];
	
	foreach ( $tables_to_remove as $table_name ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );
	}
	
	// Remove all plugin options
	$option_patterns = [
		'synpat_%',
	];
	
	foreach ( $option_patterns as $pattern ) {
		$wpdb->query( 
			$wpdb->prepare( 
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$pattern
			)
		);
	}
	
	// Clear any cached data
	wp_cache_flush();
	
	// Remove custom capabilities
	$roles_to_clean = [ 'administrator', 'editor' ];
	
	foreach ( $roles_to_clean as $role_name ) {
		$role = get_role( $role_name );
		if ( $role ) {
			$role->remove_cap( 'manage_synpat_portfolios' );
			$role->remove_cap( 'manage_synpat_patents' );
			$role->remove_cap( 'manage_synpat_licensees' );
		}
	}
}

synpat_platform_uninstall();
