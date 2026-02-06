<?php
/**
 * Backyard Dashboard View
 * Patent analysis and data management interface
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Initialize modules
$pro_tools = SynPat_Pro_Tools::get_instance();
$analyzer = $pro_tools->get_module( 'patent_analyzer' );
$batch_processor = $pro_tools->get_module( 'batch_processor' );
?>

<div class="wrap synpat-backyard-dashboard">
	<h1><?php esc_html_e( 'Backyard Tools - Patent Analysis Suite', 'synpat-pro' ); ?></h1>

	<div class="synpat-dashboard-grid">
		<!-- Quick Actions Panel -->
		<div class="synpat-panel quick-actions">
			<h2><?php esc_html_e( 'Quick Actions', 'synpat-pro' ); ?></h2>
			<div class="action-buttons">
				<button class="button button-primary" id="btn-analyze-patent">
					<span class="dashicons dashicons-search"></span>
					<?php esc_html_e( 'Analyze Patent', 'synpat-pro' ); ?>
				</button>
				<button class="button button-secondary" id="btn-import-data">
					<span class="dashicons dashicons-upload"></span>
					<?php esc_html_e( 'Import Data', 'synpat-pro' ); ?>
				</button>
				<button class="button button-secondary" id="btn-batch-process">
					<span class="dashicons dashicons-database"></span>
					<?php esc_html_e( 'Batch Process', 'synpat-pro' ); ?>
				</button>
			</div>
		</div>

		<!-- Analysis Dashboard -->
		<div class="synpat-panel analysis-overview">
			<h2><?php esc_html_e( 'Analysis Overview', 'synpat-pro' ); ?></h2>
			<div id="analysis-stats" class="stats-container">
				<div class="stat-item">
					<span class="stat-label"><?php esc_html_e( 'Patents Analyzed', 'synpat-pro' ); ?></span>
					<span class="stat-value" id="total-analyzed">--</span>
				</div>
				<div class="stat-item">
					<span class="stat-label"><?php esc_html_e( 'Average Strength', 'synpat-pro' ); ?></span>
					<span class="stat-value" id="avg-strength">--</span>
				</div>
				<div class="stat-item">
					<span class="stat-label"><?php esc_html_e( 'Pending Analysis', 'synpat-pro' ); ?></span>
					<span class="stat-value" id="pending-count">--</span>
				</div>
			</div>
		</div>

		<!-- Recent Analyses -->
		<div class="synpat-panel recent-analyses">
			<h2><?php esc_html_e( 'Recent Analyses', 'synpat-pro' ); ?></h2>
			<div id="recent-list" class="analyses-list">
				<p class="loading-indicator"><?php esc_html_e( 'Loading...', 'synpat-pro' ); ?></p>
			</div>
		</div>

		<!-- Data Import Section -->
		<div class="synpat-panel import-section" style="display:none;" id="import-panel">
			<h2><?php esc_html_e( 'Import Patent Data', 'synpat-pro' ); ?></h2>
			<form id="import-form" enctype="multipart/form-data">
				<table class="form-table">
					<tr>
						<th><label for="import-file"><?php esc_html_e( 'Select File', 'synpat-pro' ); ?></label></th>
						<td>
							<input type="file" id="import-file" name="import_file" accept=".csv,.json,.xml" required>
						</td>
					</tr>
					<tr>
						<th><label for="import-format"><?php esc_html_e( 'File Format', 'synpat-pro' ); ?></label></th>
						<td>
							<select id="import-format" name="format">
								<option value="csv">CSV</option>
								<option value="json">JSON</option>
								<option value="xml">XML</option>
							</select>
						</td>
					</tr>
				</table>
				<p class="submit">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Import Now', 'synpat-pro' ); ?></button>
					<button type="button" class="button" id="cancel-import"><?php esc_html_e( 'Cancel', 'synpat-pro' ); ?></button>
				</p>
			</form>
			<div id="import-progress" style="display:none;">
				<div class="progress-bar"><div class="progress-fill"></div></div>
				<p class="progress-status"></p>
			</div>
		</div>
	</div>
</div>

<style>
.synpat-dashboard-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
	gap: 20px;
	margin-top: 20px;
}
.synpat-panel {
	background: #fff;
	padding: 20px;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.synpat-panel h2 {
	margin-top: 0;
	border-bottom: 1px solid #eee;
	padding-bottom: 10px;
}
.action-buttons {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
}
.action-buttons .button {
	flex: 1;
	min-width: 150px;
}
.stats-container {
	display: flex;
	justify-content: space-around;
	margin-top: 20px;
}
.stat-item {
	text-align: center;
}
.stat-label {
	display: block;
	font-size: 12px;
	color: #666;
	margin-bottom: 5px;
}
.stat-value {
	display: block;
	font-size: 32px;
	font-weight: bold;
	color: #2271b1;
}
.analyses-list {
	max-height: 300px;
	overflow-y: auto;
}
.progress-bar {
	background: #f0f0f1;
	height: 24px;
	border-radius: 3px;
	overflow: hidden;
}
.progress-fill {
	background: #2271b1;
	height: 100%;
	width: 0%;
	transition: width 0.3s;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Load initial statistics
	loadDashboardStats();

	// Quick action handlers
	$('#btn-analyze-patent').on('click', function() {
		window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=synpat-expert-tools&action=analyze' ) ); ?>';
	});

	$('#btn-import-data').on('click', function() {
		$('.import-section').slideDown();
	});

	$('#cancel-import').on('click', function() {
		$('.import-section').slideUp();
		$('#import-form')[0].reset();
	});

	$('#btn-batch-process').on('click', function() {
		showBatchDialog();
	});

	// Import form submission
	$('#import-form').on('submit', function(e) {
		e.preventDefault();
		performDataImport();
	});

	function loadDashboardStats() {
		$.post(synpatPro.ajaxUrl, {
			action: 'synpat_get_analysis',
			nonce: synpatPro.nonce
		}, function(response) {
			if (response.success) {
				updateStatsDisplay(response.data);
			}
		});
	}

	function updateStatsDisplay(stats) {
		$('#total-analyzed').text(stats.total || 0);
		$('#avg-strength').text(stats.average_score ? stats.average_score.toFixed(1) : '--');
		$('#pending-count').text(stats.pending || 0);
	}

	function performDataImport() {
		var formData = new FormData($('#import-form')[0]);
		formData.append('action', 'synpat_import_data');
		formData.append('nonce', synpatPro.nonce);

		$('#import-progress').show();
		$('.progress-fill').css('width', '10%');

		$.ajax({
			url: synpatPro.ajaxUrl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function(response) {
				$('.progress-fill').css('width', '100%');
				if (response.success) {
					$('.progress-status').text('Import completed: ' + response.data.imported + ' records imported');
					setTimeout(function() {
						$('.import-section').slideUp();
						loadDashboardStats();
					}, 2000);
				} else {
					$('.progress-status').text('Error: ' + response.data.message);
				}
			}
		});
	}

	function showBatchDialog() {
		// Implement batch processing dialog
		alert('<?php esc_html_e( 'Batch processing interface coming soon', 'synpat-pro' ); ?>');
	}
});
</script>
