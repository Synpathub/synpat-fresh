<?php
/**
 * Admin Dashboard View
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Display notifications
if ( isset( $_GET['notice'] ) ) {
	SynPat_Admin::render_notification( sanitize_key( $_GET['notice'] ) );
}
?>

<div class="wrap synpat-dashboard">
	<h1><?php esc_html_e( 'SynPat Platform Dashboard', 'synpat-platform' ); ?></h1>

	<div class="synpat-stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;">
		
		<div class="stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #2271b1; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<div style="font-size: 14px; color: #666; margin-bottom: 5px;"><?php esc_html_e( 'Total Portfolios', 'synpat-platform' ); ?></div>
			<div style="font-size: 32px; font-weight: bold; color: #2271b1;"><?php echo absint( $metrics['portfolio_count'] ); ?></div>
		</div>

		<div class="stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #00a32a; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<div style="font-size: 14px; color: #666; margin-bottom: 5px;"><?php esc_html_e( 'Active Portfolios', 'synpat-platform' ); ?></div>
			<div style="font-size: 32px; font-weight: bold; color: #00a32a;"><?php echo absint( $metrics['portfolio_active'] ); ?></div>
		</div>

		<div class="stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #d63638; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<div style="font-size: 14px; color: #666; margin-bottom: 5px;"><?php esc_html_e( 'Total Patents', 'synpat-platform' ); ?></div>
			<div style="font-size: 32px; font-weight: bold; color: #d63638;"><?php echo absint( $metrics['patent_count'] ); ?></div>
		</div>

		<div class="stat-card" style="background: #fff; padding: 20px; border-left: 4px solid #f0b849; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<div style="font-size: 14px; color: #666; margin-bottom: 5px;"><?php esc_html_e( 'Wishlist Items', 'synpat-platform' ); ?></div>
			<div style="font-size: 32px; font-weight: bold; color: #f0b849;"><?php echo absint( $metrics['wishlist_count'] ); ?></div>
		</div>

	</div>

	<div class="synpat-dashboard-panels" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-top: 30px;">
		
		<div class="panel-recent" style="background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1;">
				<?php esc_html_e( 'Recent Portfolios', 'synpat-platform' ); ?>
			</h2>
			
			<?php if ( ! empty( $metrics['latest_portfolios'] ) ) : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'ID', 'synpat-platform' ); ?></th>
							<th><?php esc_html_e( 'Title', 'synpat-platform' ); ?></th>
							<th><?php esc_html_e( 'Patents', 'synpat-platform' ); ?></th>
							<th><?php esc_html_e( 'Status', 'synpat-platform' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $metrics['latest_portfolios'] as $portfolio ) : ?>
							<tr>
								<td><?php echo absint( $portfolio->id ); ?></td>
								<td><strong><?php echo esc_html( $portfolio->title ); ?></strong></td>
								<td><?php echo absint( $portfolio->n_patents ); ?></td>
								<td>
									<span class="status-badge" style="padding: 3px 8px; border-radius: 3px; font-size: 11px; 
										background: <?php echo $portfolio->status === 'active' ? '#00a32a' : '#dba617'; ?>; 
										color: white;">
										<?php echo esc_html( ucfirst( $portfolio->status ) ); ?>
									</span>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<p style="color: #666; font-style: italic;"><?php esc_html_e( 'No portfolios found.', 'synpat-platform' ); ?></p>
			<?php endif; ?>
		</div>

		<div class="panel-actions" style="background: #fff; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
			<h2 style="margin-top: 0; padding-bottom: 10px; border-bottom: 2px solid #2271b1;">
				<?php esc_html_e( 'Quick Actions', 'synpat-platform' ); ?>
			</h2>
			
			<div style="margin-top: 15px;">
				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=synpat-platform&synpat_action=purge_cache' ), 'synpat_backend_action' ) ); ?>" 
				   class="button button-secondary" style="width: 100%; text-align: center; margin-bottom: 10px;">
					<span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
					<?php esc_html_e( 'Clear Cache', 'synpat-platform' ); ?>
				</a>

				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=synpat-platform&synpat_action=rebuild_pdfs' ), 'synpat_backend_action' ) ); ?>" 
				   class="button button-secondary" style="width: 100%; text-align: center; margin-bottom: 10px;">
					<span class="dashicons dashicons-media-document" style="margin-top: 3px;"></span>
					<?php esc_html_e( 'Regenerate PDFs', 'synpat-platform' ); ?>
				</a>

				<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=synpat-platform&synpat_action=download_export' ), 'synpat_backend_action' ) ); ?>" 
				   class="button button-secondary" style="width: 100%; text-align: center; margin-bottom: 10px;">
					<span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
					<?php esc_html_e( 'Export Data', 'synpat-platform' ); ?>
				</a>

				<hr style="margin: 20px 0;">

				<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-settings' ) ); ?>" 
				   class="button button-primary" style="width: 100%; text-align: center;">
					<span class="dashicons dashicons-admin-generic" style="margin-top: 3px;"></span>
					<?php esc_html_e( 'Settings', 'synpat-platform' ); ?>
				</a>
			</div>
		</div>

	</div>

	<div style="margin-top: 30px; padding: 15px; background: #e7f5fe; border-left: 4px solid #2271b1;">
		<h3 style="margin-top: 0;"><?php esc_html_e( 'System Information', 'synpat-platform' ); ?></h3>
		<p style="margin: 0;">
			<strong><?php esc_html_e( 'Version:', 'synpat-platform' ); ?></strong> <?php echo esc_html( SYNPAT_PLT_VERSION ); ?><br>
			<strong><?php esc_html_e( 'Database Tables:', 'synpat-platform' ); ?></strong> <?php esc_html_e( 'All tables operational', 'synpat-platform' ); ?><br>
			<strong><?php esc_html_e( 'Pro Tools:', 'synpat-platform' ); ?></strong> 
			<?php echo defined( 'SYNPAT_PRO_VERSION' ) ? esc_html__( 'Installed', 'synpat-platform' ) : esc_html__( 'Not installed', 'synpat-platform' ); ?>
		</p>
	</div>
</div>
