<?php
/**
 * Claim Chart Editor Template
 * Interface for creating and editing claim charts
 *
 * @package SynPat_Pro_Tools
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$chart_id = isset( $chart ) && $chart ? $chart->id : 0;
$title = isset( $chart ) && $chart ? $chart->title : '';
$patent_number = isset( $chart ) && $chart ? $chart->patent_number : '';
$claim_number = isset( $chart ) && $chart ? $chart->claim_number : '';
$claim_text = isset( $chart ) && $chart ? $chart->claim_text : '';
$content = isset( $chart ) && $chart ? $chart->content : '';
?>

<div class="wrap synpat-claim-chart-editor">
	<h1><?php echo $chart_id ? esc_html__( 'Edit Claim Chart', 'synpat-pro' ) : esc_html__( 'Create Claim Chart', 'synpat-pro' ); ?></h1>

	<form id="claim-chart-form" method="post" action="">
		<?php wp_nonce_field( 'synpat_save_claim_chart', 'claim_chart_nonce' ); ?>
		<input type="hidden" name="chart_id" value="<?php echo esc_attr( $chart_id ); ?>">

		<table class="form-table">
			<tbody>
				<tr>
					<th scope="row">
						<label for="chart_title"><?php esc_html_e( 'Chart Title', 'synpat-pro' ); ?></label>
					</th>
					<td>
						<input type="text" id="chart_title" name="title" class="regular-text" value="<?php echo esc_attr( $title ); ?>" required>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="patent_number"><?php esc_html_e( 'Patent Number', 'synpat-pro' ); ?></label>
					</th>
					<td>
						<input type="text" id="patent_number" name="patent_number" class="regular-text" value="<?php echo esc_attr( $patent_number ); ?>" required>
						<p class="description"><?php esc_html_e( 'e.g., US1234567', 'synpat-pro' ); ?></p>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="claim_number"><?php esc_html_e( 'Claim Number', 'synpat-pro' ); ?></label>
					</th>
					<td>
						<input type="text" id="claim_number" name="claim_number" class="small-text" value="<?php echo esc_attr( $claim_number ); ?>">
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="claim_text"><?php esc_html_e( 'Claim Text', 'synpat-pro' ); ?></label>
					</th>
					<td>
						<textarea id="claim_text" name="claim_text" rows="6" class="large-text"><?php echo esc_textarea( $claim_text ); ?></textarea>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="chart_content"><?php esc_html_e( 'Claim Chart Content', 'synpat-pro' ); ?></label>
					</th>
					<td>
						<?php
						$editor_settings = [
							'textarea_name' => 'content',
							'textarea_rows' => 20,
							'tinymce' => true,
							'media_buttons' => true,
						];
						wp_editor( $content, 'chart_content', $editor_settings );
						?>
						<p class="description"><?php esc_html_e( 'Add detailed analysis and mapping for each claim element.', 'synpat-pro' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Claim Chart', 'synpat-pro' ); ?></button>
			<?php if ( $chart_id ) : ?>
				<button type="button" class="button button-secondary" id="delete-chart"><?php esc_html_e( 'Delete', 'synpat-pro' ); ?></button>
			<?php endif; ?>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=synpat-expert-tools' ) ); ?>" class="button"><?php esc_html_e( 'Cancel', 'synpat-pro' ); ?></a>
		</p>
	</form>
</div>

<script>
jQuery(document).ready(function($) {
	$('#claim-chart-form').on('submit', function(e) {
		e.preventDefault();
		
		var formData = {
			action: '<?php echo $chart_id ? 'synpat_update_claim_chart' : 'synpat_create_claim_chart'; ?>',
			nonce: synpatPro.nonce,
			chart_id: $('input[name="chart_id"]').val(),
			chart_data: JSON.stringify({
				title: $('input[name="title"]').val(),
				patent_number: $('input[name="patent_number"]').val(),
				claim_number: $('input[name="claim_number"]').val(),
				claim_text: $('textarea[name="claim_text"]').val(),
				content: tinyMCE.get('chart_content').getContent()
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

	$('#delete-chart').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Are you sure you want to delete this claim chart?', 'synpat-pro' ); ?>')) {
			return;
		}

		$.post(synpatPro.ajaxUrl, {
			action: 'synpat_delete_claim_chart',
			nonce: synpatPro.nonce,
			chart_id: <?php echo absint( $chart_id ); ?>
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
