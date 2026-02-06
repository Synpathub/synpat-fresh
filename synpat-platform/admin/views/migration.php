<?php
/**
 * Migration Tools Interface
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wrap">
	<h1><?php esc_html_e( 'Data Migration Tools', 'synpat-platform' ); ?></h1>

	<div style="background: white; padding: 25px; margin: 20px 0;">
		<h2><?php esc_html_e( 'Import Portfolios from JSON', 'synpat-platform' ); ?></h2>
		
		<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="synpat_import_json">
			<?php wp_nonce_field( 'synpat_import_action' ); ?>
			
			<p>
				<input type="file" name="import_file" accept=".json" required>
			</p>
			
			<p>
				<label>
					<input type="checkbox" name="overwrite_existing" value="1">
					<?php esc_html_e( 'Overwrite existing records', 'synpat-platform' ); ?>
				</label>
			</p>
			
			<?php submit_button( __( 'Import Data', 'synpat-platform' ), 'primary', 'submit', false ); ?>
		</form>
	</div>

	<div style="background: white; padding: 25px; margin: 20px 0;">
		<h2><?php esc_html_e( 'Sync with Custom Tables', 'synpat-platform' ); ?></h2>
		
		<p><?php esc_html_e( 'Synchronize WordPress posts with custom database tables.', 'synpat-platform' ); ?></p>
		
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="synpat_sync_tables">
			<?php wp_nonce_field( 'synpat_sync_action' ); ?>
			
			<p>
				<label>
					<input type="radio" name="sync_direction" value="posts_to_tables" checked>
					<?php esc_html_e( 'WordPress Posts → Custom Tables', 'synpat-platform' ); ?>
				</label>
			</p>
			
			<p>
				<label>
					<input type="radio" name="sync_direction" value="tables_to_posts">
					<?php esc_html_e( 'Custom Tables → WordPress Posts', 'synpat-platform' ); ?>
				</label>
			</p>
			
			<?php submit_button( __( 'Start Sync', 'synpat-platform' ), 'secondary', 'submit', false ); ?>
		</form>
	</div>

	<div style="background: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin: 20px 0;">
		<strong><?php esc_html_e( 'Important:', 'synpat-platform' ); ?></strong>
		<?php esc_html_e( 'Always backup your database before performing migration operations.', 'synpat-platform' ); ?>
	</div>
</div>
