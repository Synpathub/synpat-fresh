<?php
/**
 * Expert Tools View
 * Interface for claim charts and prior art reports
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$pro_tools = SynPat_Pro_Tools::get_instance();
$claim_chart_module = $pro_tools->get_module( 'claim_chart' );
$prior_art_module = $pro_tools->get_module( 'prior_art' );

// Handle action parameter
$current_action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';
$item_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

// Route to appropriate view
if ( 'edit_chart' === $current_action && $item_id ) {
	$editor = $pro_tools->get_module( 'expert_editor' );
	$editor->render_claim_chart_editor( $item_id );
	return;
} elseif ( 'new_chart' === $current_action ) {
	$editor = $pro_tools->get_module( 'expert_editor' );
	$editor->render_claim_chart_editor();
	return;
} elseif ( 'edit_report' === $current_action && $item_id ) {
	$editor = $pro_tools->get_module( 'expert_editor' );
	$editor->render_prior_art_editor( $item_id );
	return;
} elseif ( 'new_report' === $current_action ) {
	$editor = $pro_tools->get_module( 'expert_editor' );
	$editor->render_prior_art_editor();
	return;
}

// Default list view
?>

<div class="wrap synpat-expert-tools">
	<h1><?php esc_html_e( 'Expert Tools', 'synpat-pro' ); ?></h1>

	<div class="synpat-tools-nav">
		<ul class="subsubsub">
			<li><a href="#claim-charts" class="current" data-tab="charts"><?php esc_html_e( 'Claim Charts', 'synpat-pro' ); ?></a> |</li>
			<li><a href="#prior-art" data-tab="reports"><?php esc_html_e( 'Prior Art Reports', 'synpat-pro' ); ?></a></li>
		</ul>
	</div>

	<!-- Claim Charts Section -->
	<div id="charts-section" class="tool-section active">
		<div class="section-header">
			<h2><?php esc_html_e( 'Claim Charts', 'synpat-pro' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-expert-tools&action=new_chart' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Create New Chart', 'synpat-pro' ); ?>
			</a>
		</div>

		<div class="charts-filters">
			<input type="text" id="chart-search" placeholder="<?php esc_attr_e( 'Search claim charts...', 'synpat-pro' ); ?>" class="regular-text">
			<select id="chart-status-filter">
				<option value=""><?php esc_html_e( 'All Statuses', 'synpat-pro' ); ?></option>
				<option value="draft"><?php esc_html_e( 'Draft', 'synpat-pro' ); ?></option>
				<option value="review"><?php esc_html_e( 'In Review', 'synpat-pro' ); ?></option>
				<option value="published"><?php esc_html_e( 'Published', 'synpat-pro' ); ?></option>
			</select>
		</div>

		<table class="wp-list-table widefat fixed striped" id="charts-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'synpat-pro' ); ?></th>
					<th><?php esc_html_e( 'Patent Number', 'synpat-pro' ); ?></th>
					<th><?php esc_html_e( 'Claim', 'synpat-pro' ); ?></th>
					<th><?php esc_html_e( 'Status', 'synpat-pro' ); ?></th>
					<th><?php esc_html_e( 'Created', 'synpat-pro' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'synpat-pro' ); ?></th>
				</tr>
			</thead>
			<tbody id="charts-list">
				<tr class="loading-row">
					<td colspan="6"><?php esc_html_e( 'Loading claim charts...', 'synpat-pro' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>

	<!-- Prior Art Reports Section -->
	<div id="reports-section" class="tool-section">
		<div class="section-header">
			<h2><?php esc_html_e( 'Prior Art Reports', 'synpat-pro' ); ?></h2>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-expert-tools&action=new_report' ) ); ?>" class="button button-primary">
				<?php esc_html_e( 'Create New Report', 'synpat-pro' ); ?>
			</a>
		</div>

		<div class="reports-filters">
			<input type="text" id="report-search" placeholder="<?php esc_attr_e( 'Search reports...', 'synpat-pro' ); ?>" class="regular-text">
		</div>

		<table class="wp-list-table widefat fixed striped" id="reports-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Title', 'synpat-pro' ); ?></th>
					<th><?php esc_html_e( 'Target Patent', 'synpat-pro' ); ?></th>
					<th><?php esc_html_e( 'References', 'synpat-pro' ); ?></th>
					<th><?php esc_html_e( 'Relevance', 'synpat-pro' ); ?></th>
					<th><?php esc_html_e( 'Created', 'synpat-pro' ); ?></th>
					<th><?php esc_html_e( 'Actions', 'synpat-pro' ); ?></th>
				</tr>
			</thead>
			<tbody id="reports-list">
				<tr class="loading-row">
					<td colspan="6"><?php esc_html_e( 'Loading prior art reports...', 'synpat-pro' ); ?></td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

<style>
.synpat-tools-nav {
	margin: 20px 0;
}
.tool-section {
	display: none;
}
.tool-section.active {
	display: block;
}
.section-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin: 20px 0;
}
.charts-filters, .reports-filters {
	margin: 15px 0;
	display: flex;
	gap: 10px;
}
.status-badge {
	padding: 3px 8px;
	border-radius: 3px;
	font-size: 11px;
	font-weight: 600;
	text-transform: uppercase;
}
.status-draft { background: #f0f0f1; color: #646970; }
.status-review { background: #fcf9e8; color: #826200; }
.status-published { background: #edfaef; color: #1e8c39; }
.item-actions a {
	margin-right: 10px;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Tab navigation
	$('.synpat-tools-nav a').on('click', function(e) {
		e.preventDefault();
		var targetTab = $(this).data('tab');
		
		$('.synpat-tools-nav a').removeClass('current');
		$(this).addClass('current');
		
		$('.tool-section').removeClass('active');
		$('#' + targetTab + '-section').addClass('active');
		
		if (targetTab === 'charts') {
			loadClaimCharts();
		} else if (targetTab === 'reports') {
			loadPriorArtReports();
		}
	});

	// Load initial data
	loadClaimCharts();

	function loadClaimCharts() {
		$.post(synpatPro.ajaxUrl, {
			action: 'synpat_list_claim_charts',
			nonce: synpatPro.nonce
		}, function(response) {
			if (response.success && response.data) {
				displayClaimCharts(response.data);
			} else {
				$('#charts-list').html('<tr><td colspan="6">No claim charts found.</td></tr>');
			}
		});
	}

	function loadPriorArtReports() {
		$.post(synpatPro.ajaxUrl, {
			action: 'synpat_list_prior_art',
			nonce: synpatPro.nonce
		}, function(response) {
			if (response.success && response.data) {
				displayPriorArtReports(response.data);
			} else {
				$('#reports-list').html('<tr><td colspan="6">No prior art reports found.</td></tr>');
			}
		});
	}

	function displayClaimCharts(charts) {
		var html = '';
		if (charts.length === 0) {
			html = '<tr><td colspan="6">No claim charts found.</td></tr>';
		} else {
			charts.forEach(function(chart) {
				html += '<tr>';
				html += '<td><strong>' + chart.title + '</strong></td>';
				html += '<td>' + chart.patent_number + '</td>';
				html += '<td>' + chart.claim_number + '</td>';
				html += '<td><span class="status-badge status-' + chart.status + '">' + chart.status + '</span></td>';
				html += '<td>' + formatDate(chart.created_at) + '</td>';
				html += '<td class="item-actions">';
				html += '<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-expert-tools&action=edit_chart&id=' ) ); ?>' + chart.id + '">Edit</a>';
				html += '<a href="#" class="delete-chart" data-id="' + chart.id + '">Delete</a>';
				html += '</td>';
				html += '</tr>';
			});
		}
		$('#charts-list').html(html);
	}

	function displayPriorArtReports(reports) {
		var html = '';
		if (reports.length === 0) {
			html = '<tr><td colspan="6">No prior art reports found.</td></tr>';
		} else {
			reports.forEach(function(report) {
				var references = JSON.parse(report.references || '[]');
				html += '<tr>';
				html += '<td><strong>' + report.title + '</strong></td>';
				html += '<td>' + report.target_patent + '</td>';
				html += '<td>' + references.length + '</td>';
				html += '<td>' + (report.relevance_score || 0) + '%</td>';
				html += '<td>' + formatDate(report.created_at) + '</td>';
				html += '<td class="item-actions">';
				html += '<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-expert-tools&action=edit_report&id=' ) ); ?>' + report.id + '">Edit</a>';
				html += '<a href="#" class="delete-report" data-id="' + report.id + '">Delete</a>';
				html += '</td>';
				html += '</tr>';
			});
		}
		$('#reports-list').html(html);
	}

	function formatDate(dateString) {
		var date = new Date(dateString);
		return date.toLocaleDateString();
	}

	// Delete handlers
	$(document).on('click', '.delete-chart', function(e) {
		e.preventDefault();
		if (confirm('<?php esc_html_e( 'Delete this claim chart?', 'synpat-pro' ); ?>')) {
			var chartId = $(this).data('id');
			deleteClaimChart(chartId);
		}
	});

	$(document).on('click', '.delete-report', function(e) {
		e.preventDefault();
		if (confirm('<?php esc_html_e( 'Delete this prior art report?', 'synpat-pro' ); ?>')) {
			var reportId = $(this).data('id');
			deletePriorArtReport(reportId);
		}
	});

	function deleteClaimChart(chartId) {
		$.post(synpatPro.ajaxUrl, {
			action: 'synpat_delete_claim_chart',
			nonce: synpatPro.nonce,
			chart_id: chartId
		}, function(response) {
			if (response.success) {
				loadClaimCharts();
			} else {
				alert(response.data.message);
			}
		});
	}

	function deletePriorArtReport(reportId) {
		$.post(synpatPro.ajaxUrl, {
			action: 'synpat_delete_prior_art',
			nonce: synpatPro.nonce,
			report_id: reportId
		}, function(response) {
			if (response.success) {
				loadPriorArtReports();
			} else {
				alert(response.data.message);
			}
		});
	}
});
</script>
