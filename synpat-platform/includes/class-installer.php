<?php
/**
 * Database Installation and Migration Handler
 * Creates and manages database schema for the platform
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Installer {

	/**
	 * Plugin activation handler
	 */
	public static function activate() {
		self::setup_schema();
		flush_rewrite_rules();
	}

	/**
	 * Create database tables on plugin activation
	 */
	public static function setup_schema() {
		global $wpdb;
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		
		$charset_collate = $wpdb->get_charset_collate();
		$table_schemas = self::get_table_definitions( $charset_collate );
		
		foreach ( $table_schemas as $schema_sql ) {
			dbDelta( $schema_sql );
		}
		
		self::create_indexes();
		self::set_initial_version();
		
		do_action( 'synpat_schema_installed' );
	}

	/**
	 * Define all table structures
	 */
	private static function get_table_definitions( $charset_collate ) {
		global $wpdb;
		$prefix = $wpdb->prefix;
		
		$schemas = [];
		
		// Portfolios table - core patent portfolio data
		$schemas[] = "CREATE TABLE {$prefix}synpat_portfolios (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			title varchar(255) NOT NULL,
			description longtext,
			n_patents int(11) DEFAULT 0,
			essnt int(11) DEFAULT 0,
			n_lic int(11) DEFAULT 0,
			u_upfront decimal(15,2) DEFAULT 0.00,
			status varchar(50) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY status (status),
			KEY created_at (created_at)
		) $charset_collate;";
		
		// Patents table - individual patent records
		$schemas[] = "CREATE TABLE {$prefix}synpat_patents (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			patent_number varchar(50) NOT NULL,
			title varchar(500) NOT NULL,
			abstract longtext,
			assignee varchar(255),
			filing_date date,
			grant_date date,
			expiration_date date,
			forward_citations int(11) DEFAULT 0,
			backward_citations int(11) DEFAULT 0,
			status varchar(50) DEFAULT 'active',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY patent_number (patent_number),
			KEY assignee (assignee),
			KEY status (status)
		) $charset_collate;";
		
		// Portfolio-Patent junction table
		$schemas[] = "CREATE TABLE {$prefix}synpat_portfolio_patents (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			portfolio_id bigint(20) unsigned NOT NULL,
			patent_id bigint(20) unsigned NOT NULL,
			display_order int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY portfolio_patent (portfolio_id, patent_id),
			KEY portfolio_id (portfolio_id),
			KEY patent_id (patent_id)
		) $charset_collate;";
		
		// Licensees table
		$schemas[] = "CREATE TABLE {$prefix}synpat_licensees (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			company_name varchar(255) NOT NULL,
			contact_email varchar(255),
			contact_phone varchar(50),
			license_type varchar(50),
			portfolio_id bigint(20) unsigned,
			status varchar(50) DEFAULT 'potential',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY company_name (company_name),
			KEY portfolio_id (portfolio_id),
			KEY status (status)
		) $charset_collate;";
		
		// Customer wishlist table
		$schemas[] = "CREATE TABLE {$prefix}synpat_customer_wishlist (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			portfolio_id bigint(20) unsigned NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY user_portfolio (user_id, portfolio_id),
			KEY user_id (user_id),
			KEY portfolio_id (portfolio_id)
		) $charset_collate;";
		
		// Technology preferences table
		$schemas[] = "CREATE TABLE {$prefix}synpat_tech_preferences (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_id bigint(20) unsigned NOT NULL,
			technology_area varchar(255) NOT NULL,
			interest_level varchar(50) DEFAULT 'medium',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY user_id (user_id),
			KEY technology_area (technology_area)
		) $charset_collate;";
		
		// Claim charts table (for pro tools)
		$schemas[] = "CREATE TABLE {$prefix}synpat_claim_charts (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			patent_id bigint(20) unsigned NOT NULL,
			chart_title varchar(255) NOT NULL,
			chart_content longtext,
			claim_elements longtext,
			product_mapping longtext,
			expert_notes longtext,
			created_by bigint(20) unsigned,
			status varchar(50) DEFAULT 'draft',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY patent_id (patent_id),
			KEY created_by (created_by),
			KEY status (status)
		) $charset_collate;";
		
		// Prior art reports table (for pro tools)
		$schemas[] = "CREATE TABLE {$prefix}synpat_prior_art_reports (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			patent_id bigint(20) unsigned NOT NULL,
			report_title varchar(255) NOT NULL,
			report_content longtext,
			prior_art_references longtext,
			analysis_summary longtext,
			created_by bigint(20) unsigned,
			status varchar(50) DEFAULT 'draft',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY patent_id (patent_id),
			KEY created_by (created_by),
			KEY status (status)
		) $charset_collate;";
		
		// Expert analysis table (for pro tools)
		$schemas[] = "CREATE TABLE {$prefix}synpat_expert_analysis (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			patent_id bigint(20) unsigned,
			portfolio_id bigint(20) unsigned,
			analysis_type varchar(100),
			analysis_content longtext,
			expert_id bigint(20) unsigned,
			rating int(11) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY patent_id (patent_id),
			KEY portfolio_id (portfolio_id),
			KEY expert_id (expert_id),
			KEY analysis_type (analysis_type)
		) $charset_collate;";
		
		return $schemas;
	}

	/**
	 * Create additional indexes for performance
	 */
	private static function create_indexes() {
		// Additional composite indexes can be added here
		// These are separate from dbDelta to avoid conflicts
	}

	/**
	 * Store the installed database version
	 */
	private static function set_initial_version() {
		update_option( 'synpat_db_version', '1.0.0' );
		update_option( 'synpat_installed_date', current_time( 'mysql' ) );
	}

	/**
	 * Clean up on plugin deactivation (optional)
	 */
	public static function teardown() {
		// Flush rewrite rules
		flush_rewrite_rules();
		
		// Clear any cached data
		wp_cache_flush();
		
		do_action( 'synpat_deactivated' );
	}

	/**
	 * Check if migration is needed from legacy databases
	 */
	public static function needs_migration() {
		global $wpdb;
		
		$portfolios_table = $wpdb->prefix . 'synpat_portfolios';
		$row_count = $wpdb->get_var( "SELECT COUNT(*) FROM {$portfolios_table}" );
		
		return $row_count == 0;
	}

	/**
	 * Migrate data from source database 1 (synpat_backyard)
	 */
	public static function migrate_from_backyard( $connection_params ) {
		// This will connect to Digital Ocean database and import data
		// Implementation would use mysqli or wpdb to connect to external DB
		
		$source_db = new mysqli(
			$connection_params['hostname'],
			$connection_params['username'],
			$connection_params['password'],
			$connection_params['database']
		);
		
		if ( $source_db->connect_error ) {
			return new WP_Error( 'connection_failed', 'Could not connect to source database' );
		}
		
		// Migrate portfolios
		self::import_portfolios_from_legacy( $source_db );
		
		// Migrate patents
		self::import_patents_from_legacy( $source_db );
		
		// Migrate relationships
		self::import_relationships_from_legacy( $source_db );
		
		$source_db->close();
		
		return true;
	}

	/**
	 * Import portfolio records from legacy database
	 */
	private static function import_portfolios_from_legacy( $source_db ) {
		global $wpdb;
		
		$result = $source_db->query( "SELECT * FROM portfolios" );
		
		if ( $result ) {
			while ( $row = $result->fetch_assoc() ) {
				$wpdb->insert(
					$wpdb->prefix . 'synpat_portfolios',
					[
						'title' => sanitize_text_field( $row['title'] ),
						'description' => wp_kses_post( $row['description'] ),
						'n_patents' => absint( $row['n_patents'] ),
						'essnt' => absint( $row['essnt'] ),
						'n_lic' => absint( $row['n_lic'] ),
						'u_upfront' => floatval( $row['u_upfront'] ),
					]
				);
			}
		}
	}

	/**
	 * Import patent records from legacy database
	 */
	private static function import_patents_from_legacy( $source_db ) {
		global $wpdb;
		
		$result = $source_db->query( "SELECT * FROM patents" );
		
		if ( $result ) {
			while ( $row = $result->fetch_assoc() ) {
				$wpdb->insert(
					$wpdb->prefix . 'synpat_patents',
					[
						'patent_number' => sanitize_text_field( $row['patent_number'] ),
						'title' => sanitize_text_field( $row['title'] ),
						'abstract' => wp_kses_post( $row['abstract'] ),
						'assignee' => sanitize_text_field( $row['assignee'] ),
						'filing_date' => sanitize_text_field( $row['filing_date'] ),
						'grant_date' => sanitize_text_field( $row['grant_date'] ),
					]
				);
			}
		}
	}

	/**
	 * Import portfolio-patent relationships
	 */
	private static function import_relationships_from_legacy( $source_db ) {
		global $wpdb;
		
		$result = $source_db->query( "SELECT * FROM portfolio_patents" );
		
		if ( $result ) {
			while ( $row = $result->fetch_assoc() ) {
				$wpdb->insert(
					$wpdb->prefix . 'synpat_portfolio_patents',
					[
						'portfolio_id' => absint( $row['portfolio_id'] ),
						'patent_id' => absint( $row['patent_id'] ),
					]
				);
			}
		}
	}
}
