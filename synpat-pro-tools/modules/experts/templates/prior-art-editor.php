<?php
/**
 * Prior Art Editor Template
 * Interface for creating and editing prior art reports
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Note: Text domain is 'synpat-pro-tools' but shortened to 'synpat-pro' for brevity in UI strings

$report_id = isset( $report ) && $report ? $report->id : 0;
$title = isset( $report ) && $report ? $report->title : '';
$target_patent = isset( $report ) && $report ? $report->target_patent : '';
$analysis = isset( $report ) && $report ? $report->analysis : '';
?>

<div class="wrap synpat-prior-art-editor">
	<h1><?php echo $report_id ? esc_html__( 'Edit Prior Art Report', 'synpat-pro' ) : esc_html__( 'Create Prior Art Report', 'synpat-pro' ); ?></h1>

	<form id="prior-art-form" method="post" action="">
		<?php wp_nonce_field( 'synpat_save_prior_art', 'prior_art_nonce' ); ?>
		<input type="hidden" name="report_id" value="<?php echo esc_attr( $report_id ); ?>">

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="report_title"><?php esc_html_e( 'Report Title', 'synpat-pro' ); ?></label>
					</th>
					<td>
						<input type="text" id="report_title" name="title" class="regular-text" value="<?php echo esc_attr( $title ); ?>" required>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="target_patent"><?php esc_html_e( 'Target Patent', 'synpat-pro' ); ?></label>
					</th>
					<td>
						<input type="text" id="target_patent" name="target_patent" class="regular-text" value="<?php echo esc_attr( $target_patent ); ?>" required>
						<p class="description"><?php esc_html_e( 'Patent being analyzed for prior art', 'synpat-pro' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Prior Art Search', 'synpat-pro' ); ?></label>
					</th>
					<td>
						<div id="prior-art-search">
							<input type="text" id="search_keywords" class="regular-text" placeholder="<?php esc_attr_e( 'Search keywords', 'synpat-pro' ); ?>">
							<button type="button" class="button" id="search-prior-art"><?php esc_html_e( 'Search', 'synpat-pro' ); ?></button>
						</div>
						<div id="search-results" style="margin-top: 15px;"></div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label><?php esc_html_e( 'Selected References', 'synpat-pro' ); ?></label>
					</th>
					<td>
						<div id="selected-references">
							<p class="description"><?php esc_html_e( 'No references selected yet.', 'synpat-pro' ); ?></p>
						</div>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="report_analysis"><?php esc_html_e( 'Analysis', 'synpat-pro' ); ?></label>
					</th>
					<td>
						<?php
						$editor_settings = [
							'textarea_name' => 'analysis',
							'textarea_rows' => 20,
							'tinymce' => true,
							'media_buttons' => true,
						];
						wp_editor( $analysis, 'report_analysis', $editor_settings );
						?>
						<p class="description"><?php esc_html_e( 'Detailed analysis of prior art references and their relevance.', 'synpat-pro' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Report', 'synpat-pro' ); ?></button>
			<?php if ( $report_id ) : ?>
				<button type="button" class="button button-secondary" id="delete-report"><?php esc_html_e( 'Delete', 'synpat-pro' ); ?></button>
			<?php endif; ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-expert-tools' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'synpat-pro' ); ?></a>
		</p>
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	var selectedReferences = [];

	$('#search-prior-art').on('click', function() {
		var keywords = $('#search_keywords').val();
		
		if (!keywords) {
			alert('<?php esc_html_e( 'Please enter search keywords', 'synpat-pro' ); ?>');
			return;
		}

		$.post(synpatPro.ajaxUrl, {
			action: 'synpat_search_prior_art',
			nonce: synpatPro.nonce,
			search_params: JSON.stringify({
				keywords: keywords
			})
		}, function(response) {
			if (response.success) {
				displaySearchResults(response.data);
			}
		});
	});

	function displaySearchResults(results) {
		var html = '<h4>' + results.total + ' <?php esc_html_e( 'references found', 'synpat-pro' ); ?></h4><ul class="prior-art-results">';
		
		results.references.forEach(function(ref, index) {
			html += '<li>';
			html += '<strong>' + ref.patent_number + '</strong>: ' + ref.title;
			html += ' <button class="button button-small add-reference" data-index="' + index + '"><?php esc_html_e( 'Add', 'synpat-pro' ); ?></button>';
			html += '</li>';
		});
		
		html += '</ul>';
		$('#search-results').html(html);

		$('.add-reference').on('click', function() {
			var index = $(this).data('index');
			addReference(results.references[index]);
		});
	}

	function addReference(reference) {
		selectedReferences.push(reference);
		updateSelectedReferences();
	}

	function updateSelectedReferences() {
		var html = '<ul>';
		selectedReferences.forEach(function(ref, index) {
			html += '<li>';
			html += '<strong>' + ref.patent_number + '</strong>: ' + ref.title;
			html += ' <button class="button button-small remove-reference" data-index="' + index + '"><?php esc_html_e( 'Remove', 'synpat-pro' ); ?></button>';
			html += '</li>';
		});
		html += '</ul>';
		$('#selected-references').html(html);

		$('.remove-reference').on('click', function() {
			var index = $(this).data('index');
			selectedReferences.splice(index, 1);
			updateSelectedReferences();
		});
	}

	$('#prior-art-form').on('submit', function(e) {
		e.preventDefault();
		
		var formData = {
			action: '<?php echo $report_id ? 'synpat_update_prior_art' : 'synpat_create_prior_art'; ?>',
			nonce: synpatPro.nonce,
			report_id: $('input[name="report_id"]').val(),
			report_data: JSON.stringify({
				title: $('input[name="title"]').val(),
				target_patent: $('input[name="target_patent"]').val(),
				references: selectedReferences,
				analysis: tinyMCE.get('report_analysis').getContent()
			})
		};

		$.post(synpatPro.ajaxUrl, formData, function(response) {
			if (response.success) {
				alert(response.data.message);
				window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=synpat-expert-tools' ) ); ?>';
			} else {
				alert(response.data.message);
			}
		});
	});

	$('#delete-report').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Are you sure you want to delete this report?', 'synpat-pro' ); ?>')) {
			return;
		}

		$.post(synpatPro.ajaxUrl, {
			action: 'synpat_delete_prior_art',
			nonce: synpatPro.nonce,
			report_id: <?php echo absint( $report_id ); ?>
		}, function(response) {
			if (response.success) {
				window.location.href = '<?php echo esc_url( admin_url( 'admin.php?page=synpat-expert-tools' ) ); ?>';
			} else {
				alert(response.data.message);
			}
		});
	});
});
</script>
