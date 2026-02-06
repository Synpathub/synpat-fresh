<?php
/**
 * Expert Editor
 * Rich text editor for creating expert reports
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Expert_Editor {

	/**
	 * Database handler
	 */
	private $db;

	/**
	 * Initialize expert editor
	 */
	public function __construct( $db ) {
		$this->db = $db;
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_editor_assets' ] );
		add_filter( 'mce_buttons', [ $this, 'add_editor_buttons' ] );
		add_filter( 'mce_external_plugins', [ $this, 'add_editor_plugins' ] );
	}

	/**
	 * Enqueue editor assets
	 */
	public function enqueue_editor_assets( $hook ) {
		if ( ! in_array( $hook, [ 'synpat-platform_page_synpat-expert-tools' ], true ) ) {
			return;
		}

		// Enqueue WordPress editor
		wp_enqueue_editor();
		wp_enqueue_media();

		// Custom editor styles
		wp_enqueue_style(
			'synpat-expert-editor',
			SYNPAT_PRO_URI . 'admin/css/expert-editor.css',
			[],
			SYNPAT_PRO_VER
		);

		// Custom editor scripts
		wp_enqueue_script(
			'synpat-expert-editor',
			SYNPAT_PRO_URI . 'admin/js/expert-editor.js',
			[ 'jquery', 'wp-tinymce' ],
			SYNPAT_PRO_VER,
			true
		);
	}

	/**
	 * Add custom buttons to TinyMCE
	 */
	public function add_editor_buttons( $buttons ) {
		array_push( $buttons, 'synpat_claim_element', 'synpat_reference', 'synpat_highlight' );
		return $buttons;
	}

	/**
	 * Add custom TinyMCE plugins
	 */
	public function add_editor_plugins( $plugins ) {
		$plugins['synpat_tools'] = SYNPAT_PRO_URI . 'admin/js/tinymce-plugin.js';
		return $plugins;
	}

	/**
	 * Get editor settings for claim charts
	 */
	public function get_claim_chart_editor_settings() {
		return [
			'textarea_name' => 'claim_chart_content',
			'textarea_rows' => 20,
			'tinymce' => [
				'toolbar1' => 'formatselect,bold,italic,underline,strikethrough,bullist,numlist,link,unlink',
				'toolbar2' => 'alignleft,aligncenter,alignright,outdent,indent,forecolor,backcolor,synpat_claim_element,synpat_highlight',
				'plugins' => 'lists,link,textcolor,paste,synpat_tools',
			],
			'quicktags' => true,
			'media_buttons' => true,
		];
	}

	/**
	 * Get editor settings for prior art reports
	 */
	public function get_prior_art_editor_settings() {
		return [
			'textarea_name' => 'prior_art_content',
			'textarea_rows' => 20,
			'tinymce' => [
				'toolbar1' => 'formatselect,bold,italic,underline,bullist,numlist,link,unlink,table',
				'toolbar2' => 'alignleft,aligncenter,alignright,outdent,indent,forecolor,synpat_reference',
				'plugins' => 'lists,link,table,textcolor,paste,synpat_tools',
			],
			'quicktags' => true,
			'media_buttons' => true,
		];
	}

	/**
	 * Render claim chart editor interface
	 */
	public function render_claim_chart_editor( $chart_id = 0 ) {
		$chart = null;
		if ( $chart_id ) {
			$claim_chart_module = new SynPat_Claim_Chart( $this->db );
			$chart = $claim_chart_module->get_claim_chart( $chart_id );
		}

		include SYNPAT_PRO_ROOT . 'modules/experts/templates/claim-chart-editor.php';
	}

	/**
	 * Render prior art editor interface
	 */
	public function render_prior_art_editor( $report_id = 0 ) {
		$report = null;
		if ( $report_id ) {
			$prior_art_module = new SynPat_Prior_Art( $this->db );
			$report = $prior_art_module->get_prior_art_report( $report_id );
		}

		include SYNPAT_PRO_ROOT . 'modules/experts/templates/prior-art-editor.php';
	}

	/**
	 * Format claim element for display
	 */
	public function format_claim_element( $text, $element_id ) {
		return sprintf(
			'<span class="claim-element" data-element-id="%s">%s</span>',
			esc_attr( $element_id ),
			esc_html( $text )
		);
	}

	/**
	 * Format reference citation
	 */
	public function format_reference( $text, $reference_id ) {
		return sprintf(
			'<cite class="prior-art-reference" data-reference-id="%s">%s</cite>',
			esc_attr( $reference_id ),
			esc_html( $text )
		);
	}

	/**
	 * Format highlighted text
	 */
	public function format_highlight( $text, $color = 'yellow' ) {
		return sprintf(
			'<mark class="highlight highlight-%s">%s</mark>',
			esc_attr( $color ),
			esc_html( $text )
		);
	}

	/**
	 * Parse editor content and extract elements
	 */
	public function parse_content_elements( $content ) {
		$elements = [];

		// Extract claim elements
		preg_match_all( '/<span class="claim-element" data-element-id="([^"]+)">([^<]+)<\/span>/', $content, $matches );
		
		if ( ! empty( $matches[0] ) ) {
			foreach ( $matches[1] as $index => $element_id ) {
				$elements['claim_elements'][] = [
					'id' => $element_id,
					'text' => $matches[2][ $index ],
				];
			}
		}

		// Extract references
		preg_match_all( '/<cite class="prior-art-reference" data-reference-id="([^"]+)">([^<]+)<\/cite>/', $content, $ref_matches );
		
		if ( ! empty( $ref_matches[0] ) ) {
			foreach ( $ref_matches[1] as $index => $ref_id ) {
				$elements['references'][] = [
					'id' => $ref_id,
					'text' => $ref_matches[2][ $index ],
				];
			}
		}

		return $elements;
	}
}
