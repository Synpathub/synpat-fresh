<?php
/**
 * Template: Portfolio Catalog
 * Displays grid of patent portfolios
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="synpat-portfolio-catalog">
	<div class="catalog-header">
		<h2 class="catalog-title"><?php esc_html_e( 'Patent Portfolio Catalog', 'synpat-platform' ); ?></h2>
		
		<div class="catalog-search">
			<input type="text" 
			       id="portfolio-search" 
			       placeholder="<?php esc_attr_e( 'Search portfolios...', 'synpat-platform' ); ?>" 
			       class="search-input" />
			<button type="button" class="btn-search">
				<?php esc_html_e( 'Search', 'synpat-platform' ); ?>
			</button>
		</div>
	</div>
	
	<div class="catalog-filters">
		<select id="filter-orderby" class="filter-select">
			<option value="id"><?php esc_html_e( 'Sort by: ID', 'synpat-platform' ); ?></option>
			<option value="title"><?php esc_html_e( 'Sort by: Title', 'synpat-platform' ); ?></option>
			<option value="n_patents"><?php esc_html_e( 'Sort by: # Patents', 'synpat-platform' ); ?></option>
		</select>
	</div>
	
	<div class="portfolio-grid">
		<?php if ( ! empty( $portfolios ) ) : ?>
			<?php foreach ( $portfolios as $portfolio ) : ?>
				<div class="portfolio-card" data-portfolio-id="<?php echo esc_attr( $portfolio->id ); ?>">
					<div class="card-header">
						<h3 class="card-title">
							<a href="<?php echo esc_url( add_query_arg( 'portfolio_id', $portfolio->id, get_permalink() ) ); ?>">
								<?php echo esc_html( $portfolio->title ); ?>
							</a>
						</h3>
					</div>
					
					<div class="card-body">
						<div class="portfolio-metrics">
							<div class="metric-item">
								<span class="metric-label"><?php esc_html_e( 'Patents', 'synpat-platform' ); ?></span>
								<span class="metric-value"><?php echo esc_html( $portfolio->n_patents ); ?></span>
							</div>
							
							<div class="metric-item">
								<span class="metric-label"><?php esc_html_e( 'Essential', 'synpat-platform' ); ?></span>
								<span class="metric-value"><?php echo esc_html( $portfolio->essnt ); ?></span>
							</div>
							
							<div class="metric-item">
								<span class="metric-label"><?php esc_html_e( 'Licensees', 'synpat-platform' ); ?></span>
								<span class="metric-value"><?php echo esc_html( $portfolio->n_lic ); ?></span>
							</div>
						</div>
						
						<div class="portfolio-description">
							<?php echo wp_kses_post( wp_trim_words( $portfolio->description, 25 ) ); ?>
						</div>
					</div>
					
					<div class="card-footer">
						<a href="<?php echo esc_url( add_query_arg( 'portfolio_id', $portfolio->id, get_permalink() ) ); ?>" 
						   class="btn btn-primary">
							<?php esc_html_e( 'View Portfolio', 'synpat-platform' ); ?>
						</a>
						
						<?php if ( is_user_logged_in() ) : ?>
						<button class="btn btn-secondary add-to-wishlist" 
						        data-portfolio-id="<?php echo esc_attr( $portfolio->id ); ?>">
							<?php esc_html_e( 'Add to Wishlist', 'synpat-platform' ); ?>
						</button>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="no-portfolios">
				<p><?php esc_html_e( 'No portfolios found', 'synpat-platform' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>
