<?php
/**
 * Template: Single Portfolio Details
 * Displays detailed view of a patent portfolio
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="synpat-portfolio-single" data-portfolio-id="<?php echo esc_attr( $portfolio->id ); ?>">
	<div class="portfolio-header">
		<h1 class="portfolio-title"><?php echo esc_html( $portfolio->title ); ?></h1>
		
		<div class="portfolio-actions">
			<?php if ( is_user_logged_in() ) : ?>
			<button class="btn btn-primary add-to-wishlist" 
			        data-portfolio-id="<?php echo esc_attr( $portfolio->id ); ?>">
				<?php esc_html_e( 'Add to Wishlist', 'synpat-platform' ); ?>
			</button>
			<?php endif; ?>
			
			<button class="btn btn-secondary generate-pdf" 
			        data-portfolio-id="<?php echo esc_attr( $portfolio->id ); ?>">
				<?php esc_html_e( 'Generate PDF', 'synpat-platform' ); ?>
			</button>
		</div>
	</div>
	
	<div class="portfolio-overview">
		<div class="metrics-grid">
			<div class="metric-card">
				<div class="metric-value"><?php echo esc_html( $portfolio->n_patents ); ?></div>
				<div class="metric-label"><?php esc_html_e( 'Total Patents', 'synpat-platform' ); ?></div>
			</div>
			
			<div class="metric-card">
				<div class="metric-value"><?php echo esc_html( $portfolio->essnt ); ?></div>
				<div class="metric-label"><?php esc_html_e( 'Essential Patents', 'synpat-platform' ); ?></div>
			</div>
			
			<div class="metric-card">
				<div class="metric-value"><?php echo esc_html( $portfolio->n_lic ); ?></div>
				<div class="metric-label"><?php esc_html_e( 'Current Licensees', 'synpat-platform' ); ?></div>
			</div>
			
			<div class="metric-card">
				<div class="metric-value">$<?php echo esc_html( number_format( $portfolio->u_upfront, 2 ) ); ?></div>
				<div class="metric-label"><?php esc_html_e( 'Upfront Value', 'synpat-platform' ); ?></div>
			</div>
		</div>
	</div>
	
	<div class="portfolio-description">
		<h2><?php esc_html_e( 'Description', 'synpat-platform' ); ?></h2>
		<div class="description-content">
			<?php echo wp_kses_post( $portfolio->description ); ?>
		</div>
	</div>
	
	<div class="portfolio-patents">
		<h2><?php esc_html_e( 'Patents in Portfolio', 'synpat-platform' ); ?></h2>
		
		<?php if ( ! empty( $patents ) ) : ?>
		<div class="patents-list">
			<?php foreach ( $patents as $patent ) : ?>
				<div class="patent-item">
					<div class="patent-number">
						<strong><?php echo esc_html( $patent->patent_number ); ?></strong>
					</div>
					<div class="patent-title">
						<?php echo esc_html( $patent->title ); ?>
					</div>
					<div class="patent-meta">
						<span class="assignee"><?php echo esc_html( $patent->assignee ); ?></span>
						<?php if ( $patent->grant_date ) : ?>
						<span class="grant-date">
							<?php echo esc_html( date_i18n( 'M d, Y', strtotime( $patent->grant_date ) ) ); ?>
						</span>
						<?php endif; ?>
					</div>
					<div class="patent-actions">
						<a href="<?php echo esc_url( add_query_arg( 'patent_id', $patent->id, get_permalink() ) ); ?>" 
						   class="btn-view-patent">
							<?php esc_html_e( 'View Details', 'synpat-platform' ); ?>
						</a>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
		<?php else : ?>
		<p class="no-patents"><?php esc_html_e( 'No patents in this portfolio', 'synpat-platform' ); ?></p>
		<?php endif; ?>
	</div>
</div>
