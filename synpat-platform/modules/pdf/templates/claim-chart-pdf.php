<?php
/**
 * PDF Template: Claim Chart
 * Generates a claim chart PDF (placeholder for Pro Tools integration)
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Variables available: $claim_chart, $styles
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php esc_html_e( 'Claim Chart Analysis', 'synpat-platform' ); ?></title>
	<?php echo $styles; ?>
</head>
<body>
	<?php
	$template_manager = new SynPat_PDF_Templates();
	echo $template_manager->generate_header( __( 'Claim Chart Analysis', 'synpat-platform' ) );
	?>
	
	<div class="section metadata">
		<h2><?php esc_html_e( 'Claim Chart Information', 'synpat-platform' ); ?></h2>
		
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Chart ID:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $claim_chart->id ); ?></span>
		</div>
		
		<?php if ( ! empty( $claim_chart->patent_id ) ) : ?>
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Patent ID:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $claim_chart->patent_id ); ?></span>
		</div>
		<?php endif; ?>
		
		<?php if ( ! empty( $claim_chart->product_name ) ) : ?>
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Product/Standard:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $claim_chart->product_name ); ?></span>
		</div>
		<?php endif; ?>
		
		<?php if ( ! empty( $claim_chart->claim_number ) ) : ?>
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Claim Number:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $claim_chart->claim_number ); ?></span>
		</div>
		<?php endif; ?>
		
		<?php if ( ! empty( $claim_chart->created_at ) ) : ?>
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Created:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $claim_chart->created_at ) ) ); ?></span>
		</div>
		<?php endif; ?>
	</div>
	
	<?php if ( ! empty( $claim_chart->claim_text ) ) : ?>
	<div class="section">
		<h2><?php esc_html_e( 'Claim Text', 'synpat-platform' ); ?></h2>
		<div class="claim-text">
			<?php echo wp_kses_post( wpautop( $claim_chart->claim_text ) ); ?>
		</div>
	</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $claim_chart->product_description ) ) : ?>
	<div class="section">
		<h2><?php esc_html_e( 'Product Description', 'synpat-platform' ); ?></h2>
		<div class="content">
			<?php echo wp_kses_post( wpautop( $claim_chart->product_description ) ); ?>
		</div>
	</div>
	<?php endif; ?>
	
	<div class="section">
		<h2><?php esc_html_e( 'Claim Chart Mapping', 'synpat-platform' ); ?></h2>
		
		<?php if ( ! empty( $claim_chart->mapping_data ) ) : ?>
			<?php
			// Decode JSON mapping data if available
			$mappings = json_decode( $claim_chart->mapping_data, true );
			if ( is_array( $mappings ) && ! empty( $mappings ) ) :
			?>
				<table>
					<thead>
						<tr>
							<th><?php esc_html_e( 'Claim Element', 'synpat-platform' ); ?></th>
							<th><?php esc_html_e( 'Product Feature', 'synpat-platform' ); ?></th>
							<th><?php esc_html_e( 'Analysis', 'synpat-platform' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $mappings as $mapping ) : ?>
						<tr>
							<td><?php echo esc_html( $mapping['claim_element'] ?? '' ); ?></td>
							<td><?php echo esc_html( $mapping['product_feature'] ?? '' ); ?></td>
							<td><?php echo esc_html( $mapping['analysis'] ?? '' ); ?></td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php else : ?>
				<div class="note">
					<?php esc_html_e( 'No mapping data available. This feature requires SynPat Pro Tools add-on.', 'synpat-platform' ); ?>
				</div>
			<?php endif; ?>
		<?php else : ?>
			<div class="note">
				<?php esc_html_e( 'Advanced claim chart mapping is available with SynPat Pro Tools add-on.', 'synpat-platform' ); ?>
			</div>
		<?php endif; ?>
	</div>
	
	<?php if ( ! empty( $claim_chart->analysis ) ) : ?>
	<div class="section">
		<h2><?php esc_html_e( 'Analysis Notes', 'synpat-platform' ); ?></h2>
		<div class="content">
			<?php echo wp_kses_post( wpautop( $claim_chart->analysis ) ); ?>
		</div>
	</div>
	<?php endif; ?>
	
	<div class="section">
		<div class="note">
			<strong><?php esc_html_e( 'Pro Tools Integration', 'synpat-platform' ); ?></strong><br>
			<?php
			echo wp_kses_post(
				sprintf(
					__( 'For advanced claim chart features including automated mapping, AI-powered analysis, and evidence gathering, please install the <strong>SynPat Pro Tools</strong> add-on.', 'synpat-platform' )
				)
			);
			?>
		</div>
	</div>
	
	<?php echo $template_manager->generate_footer(); ?>
</body>
</html>
