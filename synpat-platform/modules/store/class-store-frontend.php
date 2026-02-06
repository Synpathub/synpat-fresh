<?php
/**
 * Store Frontend Controller
 * Manages public-facing patent portfolio catalog
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

class SynPat_Store_Frontend {

	/**
	 * Database handler instance
	 */
	private $db;

	/**
	 * Initialize store frontend
	 */
	public function __construct() {
		$this->db = new SynPat_Database();
		$this->register_hooks();
	}

	/**
	 * Register WordPress hooks
	 */
	private function register_hooks() {
		add_action( 'wp_enqueue_scripts', [ $this, 'load_assets' ] );
		add_filter( 'the_content', [ $this, 'inject_portfolio_content' ] );
	}

	/**
	 * Enqueue frontend assets
	 */
	public function load_assets() {
		if ( $this->is_portfolio_page() ) {
			wp_enqueue_style( 
				'synpat-store', 
				SYNPAT_PLT_URI . 'public/css/store-frontend.css',
				[],
				SYNPAT_PLT_VER
			);
			
			wp_enqueue_script(
				'synpat-store-js',
				SYNPAT_PLT_URI . 'public/js/store-frontend.js',
				[ 'jquery' ],
				SYNPAT_PLT_VER,
				true
			);
			
			wp_localize_script( 'synpat-store-js', 'synpatStore', [
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'synpat_store_nonce' ),
				'userId' => get_current_user_id(),
			]);
		}
	}

	/**
	 * Check if current page displays portfolio content
	 */
	private function is_portfolio_page() {
		global $post;
		
		if ( ! $post ) {
			return false;
		}
		
		return has_shortcode( $post->post_content, 'portfolio_catalog' ) ||
		       has_shortcode( $post->post_content, 'portfolio_details' ) ||
		       has_shortcode( $post->post_content, 'patent_inner_room' );
	}

	/**
	 * Render portfolio catalog grid
	 */
	public function render_catalog( $attributes = [] ) {
		$defaults = [
			'per_page' => 12,
			'orderby' => 'id',
			'order' => 'DESC',
		];
		
		$args = wp_parse_args( $attributes, $defaults );
		
		$portfolios = $this->db->get_portfolios( [
			'limit' => absint( $args['per_page'] ),
			'orderby' => sanitize_key( $args['orderby'] ),
			'order' => sanitize_key( $args['order'] ),
		] );
		
		ob_start();
		include SYNPAT_PLT_ROOT . 'modules/store/templates/portfolio-catalog.php';
		return ob_get_clean();
	}

	/**
	 * Render single portfolio details
	 */
	public function render_portfolio_single( $portfolio_id ) {
		$portfolio = $this->db->get_portfolio( $portfolio_id );
		
		if ( ! $portfolio ) {
			return '<div class="synpat-error">' . 
			       esc_html__( 'Portfolio not found', 'synpat-platform' ) . 
			       '</div>';
		}
		
		$patents = $this->db->get_portfolio_patents( $portfolio_id );
		
		ob_start();
		include SYNPAT_PLT_ROOT . 'modules/store/templates/portfolio-single.php';
		return ob_get_clean();
	}

	/**
	 * Render patent details (inner room)
	 */
	public function render_patent_details( $patent_id ) {
		$patent = $this->db->get_patent( $patent_id );
		
		if ( ! $patent ) {
			return '<div class="synpat-error">' . 
			       esc_html__( 'Patent not found', 'synpat-platform' ) . 
			       '</div>';
		}
		
		ob_start();
		include SYNPAT_PLT_ROOT . 'modules/store/templates/patent-single.php';
		return ob_get_clean();
	}

	/**
	 * Render user's wishlist
	 */
	public function render_wishlist() {
		if ( ! is_user_logged_in() ) {
			return '<div class="synpat-notice">' . 
			       esc_html__( 'Please log in to view your wishlist', 'synpat-platform' ) . 
			       '</div>';
		}
		
		$user_id = get_current_user_id();
		$wishlist = $this->db->get_user_wishlist( $user_id );
		
		ob_start();
		include SYNPAT_PLT_ROOT . 'modules/store/templates/wishlist.php';
		return ob_get_clean();
	}

	/**
	 * Inject portfolio content into the_content
	 */
	public function inject_portfolio_content( $content ) {
		// This can be used to modify content automatically
		return $content;
	}

	/**
	 * Get portfolio card HTML
	 */
	public function get_portfolio_card_html( $portfolio ) {
		ob_start();
		?>
		<div class="synpat-portfolio-card" data-portfolio-id="<?php echo esc_attr( $portfolio->id ); ?>">
			<div class="portfolio-header">
				<h3 class="portfolio-title">
					<?php echo esc_html( $portfolio->title ); ?>
				</h3>
			</div>
			
			<div class="portfolio-metrics">
				<div class="metric">
					<span class="label"><?php esc_html_e( 'Patents:', 'synpat-platform' ); ?></span>
					<span class="value"><?php echo esc_html( $portfolio->n_patents ); ?></span>
				</div>
				<div class="metric">
					<span class="label"><?php esc_html_e( 'Essential:', 'synpat-platform' ); ?></span>
					<span class="value"><?php echo esc_html( $portfolio->essnt ); ?></span>
				</div>
				<div class="metric">
					<span class="label"><?php esc_html_e( 'Licensees:', 'synpat-platform' ); ?></span>
					<span class="value"><?php echo esc_html( $portfolio->n_lic ); ?></span>
				</div>
			</div>
			
			<div class="portfolio-description">
				<?php echo wp_kses_post( wp_trim_words( $portfolio->description, 30 ) ); ?>
			</div>
			
			<div class="portfolio-actions">
				<a href="<?php echo esc_url( $this->get_portfolio_url( $portfolio->id ) ); ?>" 
				   class="btn-view-details">
					<?php esc_html_e( 'View Details', 'synpat-platform' ); ?>
				</a>
				
				<?php if ( is_user_logged_in() ) : ?>
				<button class="btn-add-wishlist" data-portfolio-id="<?php echo esc_attr( $portfolio->id ); ?>">
					<?php esc_html_e( 'Add to Wishlist', 'synpat-platform' ); ?>
				</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get URL for portfolio details page
	 */
	private function get_portfolio_url( $portfolio_id ) {
		// This would link to a page with [portfolio_details] shortcode
		// For now, return a simple query var
		return add_query_arg( 'portfolio_id', $portfolio_id, home_url( '/portfolio/' ) );
	}
}
