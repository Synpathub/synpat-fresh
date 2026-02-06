<?php
/**
 * Plugin Name: SynPat Platform
 * Plugin URI: https://github.com/Synpathub/synpat-fresh
 * Description: Modern WordPress platform for patent portfolio licensing with public store, PDF generation, and extensibility hooks.
 * Version: 1.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: SynPat
 * Author URI: https://synpat.com
 * License: GPL v2 or later
 * Text Domain: synpat-platform
 * Domain Path: /languages
 *
 * @package SynPat_Platform
 */

// Prevent direct file access
defined( 'ABSPATH' ) || exit;

// Define plugin constants
define( 'SYNPAT_PLATFORM_VERSION', '1.0.0' );
define( 'SYNPAT_PLT_VER', '1.0.0' );
define( 'SYNPAT_PLATFORM_PATH', plugin_dir_path( __FILE__ ) );
define( 'SYNPAT_PLT_ROOT', plugin_dir_path( __FILE__ ) );
define( 'SYNPAT_PLATFORM_URL', plugin_dir_url( __FILE__ ) );
define( 'SYNPAT_PLT_URI', plugin_dir_url( __FILE__ ) );

/**
 * Activation hook callback
 * Runs on plugin activation
 */
function activate_synpat_platform() {
	require_once SYNPAT_PLT_ROOT . 'includes/class-installer.php';
	SynPat_Installer::activate();
}

/**
 * Deactivation hook callback
 * Runs on plugin deactivation
 */
function deactivate_synpat_platform() {
	// Flush rewrite rules on deactivation
	flush_rewrite_rules();
	
	// Hook for additional deactivation tasks
	do_action( 'synpat_platform_deactivate' );
}

// Register activation and deactivation hooks
register_activation_hook( __FILE__, 'activate_synpat_platform' );
register_deactivation_hook( __FILE__, 'deactivate_synpat_platform' );

/**
 * Bootstrap the plugin
 * Initializes the core platform class
 */
function run_synpat_platform() {
	require_once SYNPAT_PLT_ROOT . 'includes/class-synpat-platform.php';
	
	$plugin = new SynPat_Platform();
	$plugin->initialize();
}

// Execute the plugin
run_synpat_platform();
