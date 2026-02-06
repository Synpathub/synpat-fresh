<?php
/**
 * PDF Template: Patent Detail
 * Generates a detailed PDF document for a single patent
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Variables available: $patent, $claim_charts, $styles
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php echo esc_html( $patent->patent_number . ' - ' . $patent->title ); ?></title>
	<?php echo $styles; ?>
</head>
<body>
	<?php
	$template_manager = new SynPat_PDF_Templates();
	echo $template_manager->generate_header( $patent->patent_number . ' - Patent Details' );
	?>
	
	<div class="section metadata">
		<h2><?php esc_html_e( 'Patent Information', 'synpat-platform' ); ?></h2>
		
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Patent Number:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $patent->patent_number ); ?></span>
		</div>
		
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Title:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $patent->title ); ?></span>
		</div>
		
		<?php if ( ! empty( $patent->filing_date ) ) : ?>
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Filing Date:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $patent->filing_date ) ) ); ?></span>
		</div>
		<?php endif; ?>
		
		<?php if ( ! empty( $patent->grant_date ) ) : ?>
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Grant Date:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $patent->grant_date ) ) ); ?></span>
		</div>
		<?php endif; ?>
		
		<?php if ( ! empty( $patent->expiration_date ) ) : ?>
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Expiration Date:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $patent->expiration_date ) ) ); ?></span>
		</div>
		<?php endif; ?>
		
		<?php if ( ! empty( $patent->status ) ) : ?>
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Status:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( ucfirst( $patent->status ) ); ?></span>
		</div>
		<?php endif; ?>
	</div>
	
	<?php if ( ! empty( $patent->inventors ) ) : ?>
	<div class="section">
		<h2><?php esc_html_e( 'Inventors', 'synpat-platform' ); ?></h2>
		<p><?php echo esc_html( $patent->inventors ); ?></p>
	</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $patent->assignee ) ) : ?>
	<div class="section">
		<h2><?php esc_html_e( 'Assignee', 'synpat-platform' ); ?></h2>
		<p><?php echo esc_html( $patent->assignee ); ?></p>
	</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $patent->abstract ) ) : ?>
	<div class="section">
		<h2><?php esc_html_e( 'Abstract', 'synpat-platform' ); ?></h2>
		<div class="content">
			<?php echo wp_kses_post( wpautop( $patent->abstract ) ); ?>
		</div>
	</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $patent->ipc_classification ) ) : ?>
	<div class="section">
		<h2><?php esc_html_e( 'Classifications', 'synpat-platform' ); ?></h2>
		
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'IPC Classification:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $patent->ipc_classification ); ?></span>
		</div>
		
		<?php if ( ! empty( $patent->cpc_classification ) ) : ?>
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'CPC Classification:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $patent->cpc_classification ); ?></span>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $patent->claims ) ) : ?>
	<div class="page-break"></div>
	
	<div class="section">
		<h2><?php esc_html_e( 'Claims', 'synpat-platform' ); ?></h2>
		<div class="claim-text">
			<?php echo wp_kses_post( wpautop( $patent->claims ) ); ?>
		</div>
	</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $patent->description ) ) : ?>
	<div class="page-break"></div>
	
	<div class="section">
		<h2><?php esc_html_e( 'Description', 'synpat-platform' ); ?></h2>
		<div class="content">
			<?php echo wp_kses_post( wpautop( $patent->description ) ); ?>
		</div>
	</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $claim_charts ) ) : ?>
	<div class="page-break"></div>
	
	<div class="section">
		<h2><?php esc_html_e( 'Claim Charts', 'synpat-platform' ); ?></h2>
		
		<table>
			<thead>
				<tr>
					<th><?php esc_html_e( 'Chart ID', 'synpat-platform' ); ?></th>
					<th><?php esc_html_e( 'Product/Standard', 'synpat-platform' ); ?></th>
					<th><?php esc_html_e( 'Claim Number', 'synpat-platform' ); ?></th>
					<th><?php esc_html_e( 'Created', 'synpat-platform' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $claim_charts as $chart ) : ?>
				<tr>
					<td><?php echo esc_html( $chart->id ); ?></td>
					<td><?php echo esc_html( $chart->product_name ?? __( 'N/A', 'synpat-platform' ) ); ?></td>
					<td><?php echo esc_html( $chart->claim_number ?? __( 'N/A', 'synpat-platform' ) ); ?></td>
					<td>
						<?php
						if ( ! empty( $chart->created_at ) ) {
							echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $chart->created_at ) ) );
						}
						?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		
		<div class="note">
			<?php esc_html_e( 'Note: Detailed claim chart analysis requires SynPat Pro Tools add-on.', 'synpat-platform' ); ?>
		</div>
	</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $patent->references ) ) : ?>
	<div class="section">
		<h2><?php esc_html_e( 'References', 'synpat-platform' ); ?></h2>
		<div class="content">
			<?php echo wp_kses_post( wpautop( $patent->references ) ); ?>
		</div>
	</div>
	<?php endif; ?>
	
	<?php echo $template_manager->generate_footer(); ?>
</body>
</html>
