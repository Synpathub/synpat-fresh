<?php
/**
 * Plugin Name: SynPat Pro Tools
 * Plugin URI: https://synpat.com/pro-tools
 * Description: Advanced patent analysis, claim chart creation, and expert tools - Requires SynPat Platform
 * Version: 1.0.0
 * Author: SynPat
 * Author URI: https://synpat.com
 * License: GPL-2.0+
 * Text Domain: synpat-pro-tools
 * Requires at least: 5.0
 * Requires PHP: 7.4
 *
 * @package SynPat_Pro_Tools
 */

defined( 'ABSPATH' ) || exit( 'Direct access not permitted' );

// Version and path constants
define( 'SYNPAT_PRO_VER', '1.0.0' );
define( 'SYNPAT_PRO_ROOT', plugin_dir_path( __FILE__ ) );
define( 'SYNPAT_PRO_URI', plugin_dir_url( __FILE__ ) );
define( 'SYNPAT_PRO_FILE', plugin_basename( __FILE__ ) );

/**
 * Check if main platform plugin is active
 */
function synpat_pro_check_dependencies() {
	if ( ! defined( 'SYNPAT_PLT_VER' ) ) {
		add_action( 'admin_notices', 'synpat_pro_dependency_notice' );
		return false;
	}
	return true;
}

/**
 * Display dependency warning
 */
function synpat_pro_dependency_notice() {
	?>
	<div class="notice notice-error">
		<p>
			<?php esc_html_e( 'SynPat Pro Tools requires the SynPat Platform plugin to be installed and activated.', 'synpat-pro-tools' ); ?>
		</p>
	</div>
	<?php
}

/**
 * Initialize Pro Tools on plugin activation
 */
function synpat_pro_activate() {
	if ( ! synpat_pro_check_dependencies() ) {
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die( 
			esc_html__( 'SynPat Pro Tools requires the SynPat Platform plugin to be active.', 'synpat-pro-tools' ),
			esc_html__( 'Plugin Activation Error', 'synpat-pro-tools' ),
			[ 'back_link' => true ]
		);
	}
	
	// Run any setup needed
	flush_rewrite_rules();
}

/**
 * Cleanup on deactivation
 */
function synpat_pro_deactivate() {
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'synpat_pro_activate' );
register_deactivation_hook( __FILE__, 'synpat_pro_deactivate' );

// Only load if dependencies are met
if ( synpat_pro_check_dependencies() ) {
	require_once SYNPAT_PRO_ROOT . 'includes/class-pro-tools.php';
	
	// Initialize the addon (uses singleton pattern)
	add_action( 'plugins_loaded', function() {
		SynPat_Pro_Tools::get_instance();
	}, 20 );
}
