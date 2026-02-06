<?php
/**
 * Template: Customer Wishlist
 * Displays user's saved portfolios
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="synpat-wishlist">
	<div class="wishlist-header">
		<h2 class="wishlist-title"><?php esc_html_e( 'My Wishlist', 'synpat-platform' ); ?></h2>
		<div class="wishlist-count">
			<?php 
			printf( 
				esc_html( _n( '%s portfolio', '%s portfolios', count( $wishlist ), 'synpat-platform' ) ),
				count( $wishlist )
			); 
			?>
		</div>
	</div>
	
	<?php if ( ! empty( $wishlist ) ) : ?>
	<div class="wishlist-items">
		<?php foreach ( $wishlist as $portfolio ) : ?>
			<div class="wishlist-item" data-portfolio-id="<?php echo esc_attr( $portfolio->id ); ?>">
				<div class="item-header">
					<h3 class="item-title">
						<a href="<?php echo esc_url( add_query_arg( 'portfolio_id', $portfolio->id, get_permalink() ) ); ?>">
							<?php echo esc_html( $portfolio->title ); ?>
						</a>
					</h3>
					
					<button class="btn-remove" 
					        data-portfolio-id="<?php echo esc_attr( $portfolio->id ); ?>"
					        aria-label="<?php esc_attr_e( 'Remove from wishlist', 'synpat-platform' ); ?>">
						×
					</button>
				</div>
				
				<div class="item-body">
					<div class="portfolio-metrics">
						<span class="metric">
							<strong><?php echo esc_html( $portfolio->n_patents ); ?></strong> 
							<?php esc_html_e( 'patents', 'synpat-platform' ); ?>
						</span>
						<span class="metric">
							<strong><?php echo esc_html( $portfolio->essnt ); ?></strong> 
							<?php esc_html_e( 'essential', 'synpat-platform' ); ?>
						</span>
						<span class="metric">
							<strong><?php echo esc_html( $portfolio->n_lic ); ?></strong> 
							<?php esc_html_e( 'licensees', 'synpat-platform' ); ?>
						</span>
					</div>
					
					<div class="portfolio-snippet">
						<?php echo wp_kses_post( wp_trim_words( $portfolio->description, 20 ) ); ?>
					</div>
				</div>
				
				<div class="item-actions">
					<a href="<?php echo esc_url( add_query_arg( 'portfolio_id', $portfolio->id, get_permalink() ) ); ?>" 
					   class="btn btn-primary">
						<?php esc_html_e( 'View Portfolio', 'synpat-platform' ); ?>
					</a>
					
					<button class="btn btn-secondary generate-pdf" 
					        data-portfolio-id="<?php echo esc_attr( $portfolio->id ); ?>">
						<?php esc_html_e( 'Generate PDF', 'synpat-platform' ); ?>
					</button>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php else : ?>
	<div class="wishlist-empty">
		<p><?php esc_html_e( 'Your wishlist is empty', 'synpat-platform' ); ?></p>
		<a href="<?php echo esc_url( home_url( '/portfolios/' ) ); ?>" class="btn btn-primary">
			<?php esc_html_e( 'Browse Portfolios', 'synpat-platform' ); ?>
		</a>
	</div>
	<?php endif; ?>
</div>
