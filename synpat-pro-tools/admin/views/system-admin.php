<?php
/**
 * System Administration View
 * Advanced configuration and reporting interface
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$pro_tools = SynPat_Pro_Tools::get_instance();
$system_config = $pro_tools->get_module( 'system_config' );
$reporting = $pro_tools->get_module( 'reporting' );
$user_mgmt = $pro_tools->get_module( 'user_management' );

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'configuration';
?>

<div class="wrap synpat-system-admin">
	<h1><?php esc_html_e( 'System Administration', 'synpat-pro' ); ?></h1>

	<nav class="nav-tab-wrapper">
		<a href="?page=synpat-system-admin&tab=configuration" class="nav-tab <?php echo 'configuration' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Configuration', 'synpat-pro' ); ?>
		</a>
		<a href="?page=synpat-system-admin&tab=reporting" class="nav-tab <?php echo 'reporting' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'Reports', 'synpat-pro' ); ?>
		</a>
		<a href="?page=synpat-system-admin&tab=users" class="nav-tab <?php echo 'users' === $active_tab ? 'nav-tab-active' : ''; ?>">
			<?php esc_html_e( 'User Management', 'synpat-pro' ); ?>
		</a>
	</nav>

	<div class="tab-content">
		<?php if ( 'configuration' === $active_tab ) : ?>
			<!-- Configuration Tab -->
			<div class="config-panel">
				<h2><?php esc_html_e( 'System Configuration', 'synpat-pro' ); ?></h2>

				<!-- Analysis Settings -->
				<div class="config-section">
					<h3><?php esc_html_e( 'Analysis Thresholds', 'synpat-pro' ); ?></h3>
					<?php $analysis_config = $system_config->retrieve_config( 'analysis_thresholds' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="strength_min"><?php esc_html_e( 'Minimum Strength Score', 'synpat-pro' ); ?></label></th>
							<td>
								<input type="number" id="strength_min" name="strength_score_minimum" 
									value="<?php echo esc_attr( $analysis_config['strength_score_minimum'] ?? 50 ); ?>" 
									min="0" max="100" class="small-text">
								<p class="description"><?php esc_html_e( 'Minimum acceptable patent strength score (0-100)', 'synpat-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="complexity_weight"><?php esc_html_e( 'Complexity Weight', 'synpat-pro' ); ?></label></th>
							<td>
								<input type="number" id="complexity_weight" name="complexity_weight" 
									value="<?php echo esc_attr( $analysis_config['complexity_weight'] ?? 0.3 ); ?>" 
									step="0.1" min="0" max="1" class="small-text">
								<p class="description"><?php esc_html_e( 'Weight factor for claim complexity in scoring (0-1)', 'synpat-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="citation_mult"><?php esc_html_e( 'Citation Multiplier', 'synpat-pro' ); ?></label></th>
							<td>
								<input type="number" id="citation_mult" name="citation_multiplier" 
									value="<?php echo esc_attr( $analysis_config['citation_multiplier'] ?? 2.5 ); ?>" 
									step="0.5" min="0" class="small-text">
								<p class="description"><?php esc_html_e( 'Multiplier for citation impact in scoring', 'synpat-pro' ); ?></p>
							</td>
						</tr>
					</table>
					<button type="button" class="button button-primary save-config" data-config="analysis_thresholds">
						<?php esc_html_e( 'Save Analysis Settings', 'synpat-pro' ); ?>
					</button>
				</div>

				<!-- Import Settings -->
				<div class="config-section">
					<h3><?php esc_html_e( 'Import Preferences', 'synpat-pro' ); ?></h3>
					<?php $import_config = $system_config->retrieve_config( 'import_settings' ); ?>
					<table class="form-table">
						<tr>
							<th><label for="batch_limit"><?php esc_html_e( 'Batch Limit', 'synpat-pro' ); ?></label></th>
							<td>
								<input type="number" id="batch_limit" name="batch_limit" 
									value="<?php echo esc_attr( $import_config['batch_limit'] ?? 100 ); ?>" 
									min="10" max="1000" class="small-text">
								<p class="description"><?php esc_html_e( 'Maximum records per batch import', 'synpat-pro' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="auto_validate"><?php esc_html_e( 'Auto-Validate', 'synpat-pro' ); ?></label></th>
							<td>
								<input type="checkbox" id="auto_validate" name="auto_validate" 
									<?php checked( $import_config['auto_validate'] ?? true ); ?>>
								<label for="auto_validate"><?php esc_html_e( 'Automatically validate imported data', 'synpat-pro' ); ?></label>
							</td>
						</tr>
					</table>
					<button type="button" class="button button-primary save-config" data-config="import_settings">
						<?php esc_html_e( 'Save Import Settings', 'synpat-pro' ); ?>
					</button>
				</div>
			</div>

		<?php elseif ( 'reporting' === $active_tab ) : ?>
			<!-- Reporting Tab -->
			<div class="reporting-panel">
				<h2><?php esc_html_e( 'Analytics & Reports', 'synpat-pro' ); ?></h2>

				<div class="report-generator">
					<h3><?php esc_html_e( 'Generate Report', 'synpat-pro' ); ?></h3>
					<table class="form-table">
						<tr>
							<th><label for="report_type"><?php esc_html_e( 'Report Type', 'synpat-pro' ); ?></label></th>
							<td>
								<select id="report_type" class="regular-text">
									<option value="portfolio_overview"><?php esc_html_e( 'Portfolio Overview', 'synpat-pro' ); ?></option>
									<option value="analysis_summary"><?php esc_html_e( 'Analysis Summary', 'synpat-pro' ); ?></option>
									<option value="user_activity"><?php esc_html_e( 'User Activity', 'synpat-pro' ); ?></option>
									<option value="trend_analysis"><?php esc_html_e( 'Trend Analysis', 'synpat-pro' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="date_range"><?php esc_html_e( 'Date Range', 'synpat-pro' ); ?></label></th>
							<td>
								<input type="date" id="start_date" class="regular-text">
								<span> to </span>
								<input type="date" id="end_date" class="regular-text">
							</td>
						</tr>
					</table>
					<button type="button" class="button button-primary" id="generate-report">
						<?php esc_html_e( 'Generate Report', 'synpat-pro' ); ?>
					</button>
					<button type="button" class="button button-secondary" id="export-report" style="display:none;">
						<?php esc_html_e( 'Export Report', 'synpat-pro' ); ?>
					</button>
				</div>

				<div id="report-output" style="display:none; margin-top:30px;">
					<h3><?php esc_html_e( 'Report Results', 'synpat-pro' ); ?></h3>
					<div id="report-content" class="report-container"></div>
				</div>
			</div>

		<?php elseif ( 'users' === $active_tab ) : ?>
			<!-- User Management Tab -->
			<div class="user-management-panel">
				<h2><?php esc_html_e( 'User Management', 'synpat-pro' ); ?></h2>

				<div class="role-provisioning">
					<h3><?php esc_html_e( 'Custom Roles', 'synpat-pro' ); ?></h3>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Role', 'synpat-pro' ); ?></th>
								<th><?php esc_html_e( 'Capabilities', 'synpat-pro' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'synpat-pro' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td><?php esc_html_e( 'Patent Analyst', 'synpat-pro' ); ?></td>
								<td><?php esc_html_e( 'Access backyard tools, analyze patents, import/export data', 'synpat-pro' ); ?></td>
								<td><button class="button provision-role" data-role="patent_analyst"><?php esc_html_e( 'Provision', 'synpat-pro' ); ?></button></td>
							</tr>
							<tr>
								<td><?php esc_html_e( 'Expert Reviewer', 'synpat-pro' ); ?></td>
								<td><?php esc_html_e( 'Create claim charts, generate prior art, publish reports', 'synpat-pro' ); ?></td>
								<td><button class="button provision-role" data-role="expert_reviewer"><?php esc_html_e( 'Provision', 'synpat-pro' ); ?></button></td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>

		<?php endif; ?>
	</div>
</div>

<style>
.config-section, .report-generator, .role-provisioning {
	background: #fff;
	padding: 20px;
	margin: 20px 0;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
}
.config-section h3, .report-generator h3, .role-provisioning h3 {
	margin-top: 0;
	border-bottom: 1px solid #eee;
	padding-bottom: 10px;
}
.report-container {
	background: #f9f9f9;
	padding: 20px;
	border: 1px solid #ddd;
	border-radius: 4px;
	max-height: 500px;
	overflow-y: auto;
}
.stat-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 15px;
	margin: 20px 0;
}
.stat-box {
	background: #fff;
	padding: 15px;
	border-left: 4px solid #2271b1;
	box-shadow: 0 1px 3px rgba(0,0,0,.1);
}
.stat-box .label {
	font-size: 12px;
	color: #666;
	text-transform: uppercase;
}
.stat-box .value {
	font-size: 28px;
	font-weight: bold;
	color: #2271b1;
	margin-top: 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
	var currentReportData = null;

	// Save configuration
	$('.save-config').on('click', function() {
		var configKey = $(this).data('config');
		var configData = {};
		var $section = $(this).closest('.config-section');

		$section.find('input, select').each(function() {
			var $input = $(this);
			var name = $input.attr('name');
			var value = $input.attr('type') === 'checkbox' ? $input.is(':checked') : $input.val();
			
			if (name) {
				if ($.isNumeric(value)) {
					value = parseFloat(value);
				}
				configData[name] = value;
			}
		});

		$.post(synpatPro.ajaxUrl, {
			action: 'synpat_update_config',
			security_token: synpatPro.nonce,
			config_key: configKey,
			config_data: JSON.stringify(configData)
		}, function(response) {
			if (response.success) {
				alert(response.data.notification);
			} else {
				alert('Error: ' + response.data.notification);
			}
		});
	});

	// Generate report
	$('#generate-report').on('click', function() {
		var reportType = $('#report_type').val();
		var criteria = {
			start_date: $('#start_date').val(),
			end_date: $('#end_date').val()
		};

		$(this).prop('disabled', true).text('<?php esc_html_e( 'Generating...', 'synpat-pro' ); ?>');

		$.post(synpatPro.ajaxUrl, {
			action: 'synpat_generate_report',
			security_token: synpatPro.nonce,
			report_type: reportType,
			criteria: JSON.stringify(criteria)
		}, function(response) {
			$('#generate-report').prop('disabled', false).text('<?php esc_html_e( 'Generate Report', 'synpat-pro' ); ?>');
			
			if (response.success) {
				currentReportData = response.data.report;
				displayReport(response.data.report);
				$('#export-report').show();
			} else {
				alert('Error: ' + response.data.notification);
			}
		});
	});

	// Export report
	$('#export-report').on('click', function() {
		if (!currentReportData) return;

		$.post(synpatPro.ajaxUrl, {
			action: 'synpat_export_report',
			security_token: synpatPro.nonce,
			report_data: JSON.stringify(currentReportData),
			format: 'json'
		}, function(response) {
			if (response.success) {
				window.location.href = response.data.url;
			}
		});
	});

	function displayReport(reportData) {
		var html = '<div class="stat-grid">';
		
		for (var section in reportData) {
			if (reportData.hasOwnProperty(section)) {
				var sectionData = reportData[section];
				
				if (typeof sectionData === 'object') {
					for (var key in sectionData) {
						if (sectionData.hasOwnProperty(key)) {
							html += '<div class="stat-box">';
							html += '<div class="label">' + formatLabel(key) + '</div>';
							html += '<div class="value">' + formatValue(sectionData[key]) + '</div>';
							html += '</div>';
						}
					}
				}
			}
		}
		
		html += '</div>';
		$('#report-content').html(html);
		$('#report-output').slideDown();
	}

	function formatLabel(str) {
		return str.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
	}

	function formatValue(val) {
		if (typeof val === 'number') {
			return val.toFixed(2);
		}
		return val;
	}

	// Provision role
	$('.provision-role').on('click', function() {
		var roleKey = $(this).data('role');
		if (confirm('<?php esc_html_e( 'Provision this custom role?', 'synpat-pro' ); ?>')) {
			alert('<?php esc_html_e( 'Role provisioning feature coming soon', 'synpat-pro' ); ?>');
		}
	});
});
</script>
