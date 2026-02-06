#!/usr/bin/env php
<?php
/**
 * Test Script for SynPat Platform and Pro Tools
 * Verifies that all classes load without fatal errors
 */

error_reporting( E_ALL );
ini_set( 'display_errors', '1' );

// Mock WordPress environment
define( 'ABSPATH', __DIR__ . '/' );
define( 'WPINC', 'wp-includes' );
define( 'WP_CONTENT_DIR', __DIR__ . '/wp-content' );
define( 'WP_PLUGIN_DIR', __DIR__ . '/wp-content/plugins' );

// Platform constants
define( 'SYNPAT_PLT_VER', '1.0.0' );
define( 'SYNPAT_PLT_ROOT', __DIR__ . '/synpat-platform/' );
define( 'SYNPAT_PLT_URI', 'http://example.com/wp-content/plugins/synpat-platform/' );
define( 'SYNPAT_PLT_FILE', __DIR__ . '/synpat-platform/synpat-platform.php' );

// Pro Tools constants
define( 'SYNPAT_PRO_VER', '1.0.0' );
define( 'SYNPAT_PRO_ROOT', __DIR__ . '/synpat-pro-tools/' );
define( 'SYNPAT_PRO_URI', 'http://example.com/wp-content/plugins/synpat-pro-tools/' );
define( 'SYNPAT_PRO_FILE', __DIR__ . '/synpat-pro-tools/synpat-pro-tools.php' );

// Mock essential WordPress functions
$mock_functions = [
	'esc_html__' => function( $text, $domain = '' ) { return $text; },
	'esc_html' => function( $text ) { return htmlspecialchars( $text ); },
	'esc_html_e' => function( $text, $domain = '' ) { echo $text; },
	'esc_attr' => function( $text ) { return htmlspecialchars( $text, ENT_QUOTES ); },
	'esc_url' => function( $url ) { return filter_var( $url, FILTER_SANITIZE_URL ); },
	'esc_js' => function( $text ) { return addslashes( $text ); },
	'__' => function( $text, $domain = '' ) { return $text; },
	'_e' => function( $text, $domain = '' ) { echo $text; },
	'add_action' => function() { return true; },
	'add_filter' => function() { return true; },
	'remove_action' => function() { return true; },
	'remove_filter' => function() { return true; },
	'do_action' => function() {},
	'apply_filters' => function( $tag, $value ) { return $value; },
	'has_action' => function() { return false; },
	'has_filter' => function() { return false; },
	'add_shortcode' => function() { return true; },
	'add_menu_page' => function() {},
	'add_submenu_page' => function() {},
	'wp_enqueue_style' => function() {},
	'wp_enqueue_script' => function() {},
	'wp_localize_script' => function() {},
	'wp_register_style' => function() {},
	'wp_register_script' => function() {},
	'admin_url' => function( $path = '' ) { return 'http://example.com/wp-admin/' . $path; },
	'home_url' => function( $path = '' ) { return 'http://example.com/' . $path; },
	'site_url' => function( $path = '' ) { return 'http://example.com/' . $path; },
	'wp_create_nonce' => function( $action ) { return 'nonce_' . $action; },
	'wp_verify_nonce' => function( $nonce, $action ) { return 1; },
	'check_ajax_referer' => function() { return 1; },
	'check_admin_referer' => function() { return 1; },
	'current_user_can' => function() { return true; },
	'is_user_logged_in' => function() { return true; },
	'get_current_user_id' => function() { return 1; },
	'user_can' => function() { return true; },
	'get_role' => function() { return new stdClass(); },
	'wp_upload_dir' => function() {
		return [
			'path' => __DIR__ . '/wp-content/uploads',
			'url' => 'http://example.com/wp-content/uploads',
			'subdir' => '',
			'basedir' => __DIR__ . '/wp-content/uploads',
			'baseurl' => 'http://example.com/wp-content/uploads',
			'error' => false,
		];
	},
	'wp_mkdir_p' => function( $path ) { return @mkdir( $path, 0755, true ); },
	'wp_send_json_success' => function( $data ) { echo json_encode( [ 'success' => true, 'data' => $data ] ); },
	'wp_send_json_error' => function( $data, $status_code = null ) { echo json_encode( [ 'success' => false, 'data' => $data ] ); },
	'sanitize_text_field' => function( $str ) { return strip_tags( $str ); },
	'sanitize_key' => function( $key ) { return strtolower( preg_replace( '/[^a-z0-9_-]/', '', $key ) ); },
	'sanitize_file_name' => function( $filename ) { return preg_replace( '/[^a-z0-9._-]/i', '', $filename ); },
	'absint' => function( $maybeint ) { return abs( intval( $maybeint ) ); },
	'wp_die' => function( $message ) { die( $message ); },
	'wp_parse_args' => function( $args, $defaults ) { return array_merge( $defaults, $args ); },
	'wp_strip_all_tags' => function( $string ) { return strip_tags( $string ); },
	'wp_trim_words' => function( $text, $num_words = 55 ) {
		$words = preg_split( '/[\s]+/', $text, $num_words + 1, PREG_SPLIT_NO_EMPTY );
		if ( count( $words ) > $num_words ) {
			array_pop( $words );
			return implode( ' ', $words ) . '...';
		}
		return implode( ' ', $words );
	},
	'wp_kses_post' => function( $data ) { return strip_tags( $data, '<p><a><br><strong><em><ul><ol><li>' ); },
	'shortcode_atts' => function( $pairs, $atts ) { return array_merge( $pairs, $atts ); },
	'has_shortcode' => function() { return false; },
	'get_bloginfo' => function( $show = '' ) { return 'SynPat Platform'; },
	'get_option' => function( $option, $default = false ) { return $default; },
	'update_option' => function() { return true; },
	'delete_option' => function() { return true; },
	'register_activation_hook' => function() {},
	'register_deactivation_hook' => function() {},
	'flush_rewrite_rules' => function() {},
	'is_admin' => function() { return true; },
	'get_current_screen' => function() { return null; },
	'locate_template' => function() { return false; },
	'date_i18n' => function( $format, $timestamp = null ) {
		return date( $format, $timestamp ?? time() );
	},
	'get_post_meta' => function() { return ''; },
	'update_post_meta' => function() { return true; },
	'delete_post_meta' => function() { return true; },
	'wp_list_pluck' => function( $list, $field ) {
		return array_map( function( $item ) use ( $field ) {
			return is_object( $item ) ? $item->$field : $item[ $field ];
		}, $list );
	},
];

// Define mock functions
foreach ( $mock_functions as $name => $callback ) {
	if ( ! function_exists( $name ) ) {
		eval( "function $name(...\$args) { return \$GLOBALS['mock_functions']['$name'](...\$args); }" );
	}
}

// Mock wpdb
class wpdb {
	public $prefix = 'wp_';
	public $insert_id = 1;
	
	public function prepare( $query, ...$args ) { return vsprintf( str_replace( '%d', '%d', str_replace( '%s', "'%s'", $query ) ), $args ); }
	public function get_var( $query ) { return 0; }
	public function get_results( $query ) { return []; }
	public function get_row( $query ) { return null; }
	public function insert( $table, $data ) { return 1; }
	public function update( $table, $data, $where ) { return 1; }
	public function delete( $table, $where ) { return 1; }
	public function query( $query ) { return 1; }
}

$GLOBALS['wpdb'] = new wpdb();

// Mock WP_Error
class WP_Error {
	private $errors = [];
	
	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( $code ) {
			$this->errors[ $code ] = [ $message ];
		}
	}
	
	public function get_error_message() {
		return reset( $this->errors )[0] ?? '';
	}
}

// Test Platform Files
echo "=== Testing SynPat Platform Files ===\n\n";

$platform_files = [
	'includes/class-database.php',
	'includes/class-hooks.php',
	'includes/class-auth.php',
	'admin/class-admin.php',
	'admin/class-settings.php',
	'admin/class-portfolio-cpt.php',
	'admin/class-patent-cpt.php',
	'modules/store/class-store-frontend.php',
	'modules/store/class-shortcodes.php',
	'modules/store/class-ajax-handlers.php',
	'modules/pdf/class-pdf-generator.php',
	'modules/pdf/class-pdf-merger.php',
	'modules/pdf/class-pdf-templates.php',
];

$platform_success = true;
foreach ( $platform_files as $file ) {
	$full_path = SYNPAT_PLT_ROOT . $file;
	if ( ! file_exists( $full_path ) ) {
		echo "✗ MISSING: $file\n";
		$platform_success = false;
		continue;
	}
	
	try {
		require_once $full_path;
		echo "✓ Loaded: $file\n";
	} catch ( Throwable $e ) {
		echo "✗ ERROR in $file: " . $e->getMessage() . "\n";
		$platform_success = false;
	}
}

// Test Platform Classes
echo "\n=== Testing Platform Classes ===\n\n";

$platform_classes = [
	'SynPat_Database',
	'SynPat_Hooks',
	'SynPat_Auth',
	'SynPat_Admin',
	'SynPat_Settings',
	'SynPat_Portfolio_CPT',
	'SynPat_Patent_CPT',
	'SynPat_Store_Frontend',
	'SynPat_Shortcodes',
	'SynPat_Ajax_Handlers',
	'SynPat_PDF_Generator',
	'SynPat_PDF_Merger',
	'SynPat_PDF_Templates',
];

foreach ( $platform_classes as $class ) {
	if ( class_exists( $class ) ) {
		echo "✓ Class exists: $class\n";
	} else {
		echo "✗ Class missing: $class\n";
		$platform_success = false;
	}
}

// Test Pro Tools Files
echo "\n=== Testing SynPat Pro Tools Files ===\n\n";

$pro_files = [
	'includes/class-integration.php',
	'includes/class-pro-tools.php',
	'modules/backyard/class-patent-analyzer.php',
	'modules/backyard/class-data-import.php',
	'modules/backyard/class-batch-processor.php',
	'modules/backyard/controllers/class-analysis-controller.php',
	'modules/experts/class-claim-chart.php',
	'modules/experts/class-prior-art.php',
	'modules/experts/class-expert-editor.php',
	'modules/admin/class-user-management.php',
	'modules/admin/class-system-config.php',
	'modules/admin/class-reporting.php',
];

$pro_success = true;
foreach ( $pro_files as $file ) {
	$full_path = SYNPAT_PRO_ROOT . $file;
	if ( ! file_exists( $full_path ) ) {
		echo "✗ MISSING: $file\n";
		$pro_success = false;
		continue;
	}
	
	try {
		require_once $full_path;
		echo "✓ Loaded: $file\n";
	} catch ( Throwable $e ) {
		echo "✗ ERROR in $file: " . $e->getMessage() . "\n";
		$pro_success = false;
	}
}

// Test Pro Tools Classes
echo "\n=== Testing Pro Tools Classes ===\n\n";

$pro_classes = [
	'SynPat_Pro_Integration',
	'SynPat_Pro_Tools',
];

foreach ( $pro_classes as $class ) {
	if ( class_exists( $class ) ) {
		echo "✓ Class exists: $class\n";
	} else {
		echo "✗ Class missing: $class\n";
		$pro_success = false;
	}
}

// Final results
echo "\n=== Test Results ===\n\n";

if ( $platform_success ) {
	echo "✅ SynPat Platform: All files and classes loaded successfully!\n";
} else {
	echo "❌ SynPat Platform: Some files or classes failed to load\n";
}

if ( $pro_success ) {
	echo "✅ SynPat Pro Tools: All files and classes loaded successfully!\n";
} else {
	echo "❌ SynPat Pro Tools: Some files or classes failed to load\n";
}

if ( $platform_success && $pro_success ) {
	echo "\n🎉 ALL TESTS PASSED! Both plugins are ready for activation.\n";
	exit( 0 );
} else {
	echo "\n⚠️  SOME TESTS FAILED! Please review the errors above.\n";
	exit( 1 );
}
