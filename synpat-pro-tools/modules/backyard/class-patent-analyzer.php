<?php
/**
 * Patent Analyzer
 * Advanced patent analysis tools for detailed examination
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Patent_Analyzer {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Analysis cache
	 */
	private $cache = [];

	/**
	 * Initialize analyzer
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'wp_ajax_synpat_analyze_patent', [ $this, 'ajax_analyze_patent' ] );
		add_action( 'wp_ajax_synpat_analyze_claims', [ $this, 'ajax_analyze_claims' ] );
		add_action( 'synpat_analysis_complete', [ $this, 'cache_analysis' ] );
	}

	/**
	 * Perform comprehensive patent analysis
	 */
	public function analyze_patent( $patent_id ) {
		$patent = $this->db->get_patent( $patent_id );

		if ( ! $patent ) {
			return new WP_Error( 'patent_not_found', __( 'Patent not found', 'synpat-pro' ) );
		}

		$analysis = [
			'patent_id' => $patent_id,
			'timestamp' => current_time( 'mysql' ),
			'claim_analysis' => $this->analyze_claims( $patent ),
			'prior_art_analysis' => $this->analyze_prior_art( $patent ),
			'technical_analysis' => $this->analyze_technical_details( $patent ),
			'citation_analysis' => $this->analyze_citations( $patent ),
			'strength_score' => $this->calculate_strength_score( $patent ),
		];

		// Store analysis results
		$this->store_analysis( $patent_id, $analysis );

		// Trigger completion hook
		do_action( 'synpat_analysis_complete', $analysis );

		return $analysis;
	}

	/**
	 * Analyze patent claims
	 */
	private function analyze_claims( $patent ) {
		$claims = $this->extract_claims( $patent );

		$analysis = [
			'total_claims' => count( $claims ),
			'independent_claims' => 0,
			'dependent_claims' => 0,
			'claim_length_avg' => 0,
			'complexity_score' => 0,
		];

		$total_length = 0;

		foreach ( $claims as $claim ) {
			if ( $this->is_independent_claim( $claim ) ) {
				$analysis['independent_claims']++;
			} else {
				$analysis['dependent_claims']++;
			}

			$length = str_word_count( $claim );
			$total_length += $length;
		}

		if ( count( $claims ) > 0 ) {
			$analysis['claim_length_avg'] = $total_length / count( $claims );
			$analysis['complexity_score'] = $this->calculate_complexity( $claims );
		}

		return $analysis;
	}

	/**
	 * Extract claims from patent data
	 */
	private function extract_claims( $patent ) {
		// Parse claims from patent text or metadata
		$claims_text = isset( $patent->claims ) ? $patent->claims : '';
		
		// Split by claim numbers (1., 2., etc.)
		$claims = preg_split( '/\n\d+\.\s+/', $claims_text );
		
		return array_filter( $claims );
	}

	/**
	 * Check if claim is independent
	 */
	private function is_independent_claim( $claim ) {
		// Independent claims don't reference other claims
		return ! preg_match( '/claim\s+\d+/i', $claim );
	}

	/**
	 * Calculate claim complexity score
	 */
	private function calculate_complexity( $claims ) {
		$score = 0;

		foreach ( $claims as $claim ) {
			// Factor in word count
			$score += str_word_count( $claim ) * 0.1;

			// Factor in technical terms
			$technical_terms = $this->count_technical_terms( $claim );
			$score += $technical_terms * 2;

			// Factor in nested clauses
			$nesting_level = substr_count( $claim, 'wherein' ) + substr_count( $claim, 'whereby' );
			$score += $nesting_level * 5;
		}

		return round( $score / count( $claims ), 2 );
	}

	/**
	 * Count technical terms in text
	 */
	private function count_technical_terms( $text ) {
		$technical_terms = [
			'apparatus', 'method', 'system', 'device', 'mechanism',
			'configured', 'coupled', 'comprising', 'plurality',
		];

		$count = 0;
		foreach ( $technical_terms as $term ) {
			$count += substr_count( strtolower( $text ), $term );
		}

		return $count;
	}

	/**
	 * Analyze prior art references
	 */
	private function analyze_prior_art( $patent ) {
		return [
			'cited_references' => $this->get_cited_references( $patent ),
			'examiner_citations' => $this->get_examiner_citations( $patent ),
			'applicant_citations' => $this->get_applicant_citations( $patent ),
		];
	}

	/**
	 * Get cited references
	 */
	private function get_cited_references( $patent ) {
		// Extract from patent metadata or external source
		return isset( $patent->cited_patents ) ? json_decode( $patent->cited_patents, true ) : [];
	}

	/**
	 * Get examiner citations
	 */
	private function get_examiner_citations( $patent ) {
		// Filter citations added by examiner
		$all_citations = $this->get_cited_references( $patent );
		return array_filter( $all_citations, function( $citation ) {
			return isset( $citation['source'] ) && $citation['source'] === 'examiner';
		} );
	}

	/**
	 * Get applicant citations
	 */
	private function get_applicant_citations( $patent ) {
		// Filter citations added by applicant
		$all_citations = $this->get_cited_references( $patent );
		return array_filter( $all_citations, function( $citation ) {
			return isset( $citation['source'] ) && $citation['source'] === 'applicant';
		} );
	}

	/**
	 * Analyze technical details
	 */
	private function analyze_technical_details( $patent ) {
		return [
			'technology_class' => isset( $patent->classification ) ? $patent->classification : '',
			'field_of_invention' => $this->extract_field_of_invention( $patent ),
			'key_terms' => $this->extract_key_terms( $patent ),
		];
	}

	/**
	 * Extract field of invention
	 */
	private function extract_field_of_invention( $patent ) {
		// Parse from patent abstract or description
		return isset( $patent->field ) ? $patent->field : 'General';
	}

	/**
	 * Extract key terms from patent
	 */
	private function extract_key_terms( $patent ) {
		$text = ( $patent->title ?? '' ) . ' ' . ( $patent->abstract ?? '' );
		
		// Simple keyword extraction (can be enhanced with NLP)
		$words = str_word_count( strtolower( $text ), 1 );
		$word_freq = array_count_values( $words );
		arsort( $word_freq );
		
		return array_slice( array_keys( $word_freq ), 0, 10 );
	}

	/**
	 * Analyze citation network
	 */
	private function analyze_citations( $patent ) {
		return [
			'forward_citations' => $this->get_forward_citations( $patent->id ),
			'backward_citations' => $this->get_cited_references( $patent ),
			'citation_score' => $this->calculate_citation_score( $patent ),
		];
	}

	/**
	 * Get patents that cite this patent
	 */
	private function get_forward_citations( $patent_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_patents';

		$results = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE cited_patents LIKE %s",
			'%"' . $patent_id . '"%'
		) );

		return $results;
	}

	/**
	 * Calculate citation impact score
	 */
	private function calculate_citation_score( $patent ) {
		$forward = count( $this->get_forward_citations( $patent->id ) );
		$backward = count( $this->get_cited_references( $patent ) );

		// Simple scoring formula
		return ( $forward * 2 ) + ( $backward * 0.5 );
	}

	/**
	 * Calculate overall patent strength score
	 */
	private function calculate_strength_score( $patent ) {
		$claim_analysis = $this->analyze_claims( $patent );
		$citation_analysis = $this->analyze_citations( $patent );

		$score = 0;

		// Claim strength (0-40 points)
		$score += min( 40, $claim_analysis['independent_claims'] * 10 );

		// Citation impact (0-40 points)
		$score += min( 40, $citation_analysis['citation_score'] * 2 );

		// Complexity factor (0-20 points)
		$score += min( 20, $claim_analysis['complexity_score'] );

		return round( $score, 2 );
	}

	/**
	 * Store analysis results in database
	 */
	private function store_analysis( $patent_id, $analysis ) {
		global $wpdb;
		$table = $wpdb->prefix . 'synpat_expert_analysis';

		$wpdb->replace(
			$table,
			[
				'patent_id' => absint( $patent_id ),
				'analysis_data' => wp_json_encode( $analysis ),
				'strength_score' => $analysis['strength_score'],
				'analysis_status' => 'completed',
				'updated_at' => current_time( 'mysql' ),
			]
		);
	}

	/**
	 * Cache analysis results
	 */
	public function cache_analysis( $analysis_data ) {
		if ( isset( $analysis_data['patent_id'] ) ) {
			$this->cache[ $analysis_data['patent_id'] ] = $analysis_data;
		}
	}

	/**
	 * AJAX handler for patent analysis
	 */
	public function ajax_analyze_patent() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$patent_id = isset( $_POST['patent_id'] ) ? absint( $_POST['patent_id'] ) : 0;

		if ( ! $patent_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid patent ID', 'synpat-pro' ) ] );
		}

		$analysis = $this->analyze_patent( $patent_id );

		if ( is_wp_error( $analysis ) ) {
			wp_send_json_error( [ 'message' => $analysis->get_error_message() ] );
		}

		wp_send_json_success( $analysis );
	}

	/**
	 * AJAX handler for claims analysis
	 */
	public function ajax_analyze_claims() {
		check_ajax_referer( 'synpat_pro_nonce', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied', 'synpat-pro' ) ] );
		}

		$patent_id = isset( $_POST['patent_id'] ) ? absint( $_POST['patent_id'] ) : 0;

		if ( ! $patent_id ) {
			wp_send_json_error( [ 'message' => __( 'Invalid patent ID', 'synpat-pro' ) ] );
		}

		$patent = $this->db->get_patent( $patent_id );
		$analysis = $this->analyze_claims( $patent );

		wp_send_json_success( $analysis );
	}
}
