<?php
/**
 * PDF Template: Portfolio Overview
 * Generates a comprehensive PDF document for a patent portfolio
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

// Variables available: $portfolio, $patents, $styles
?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="UTF-8">
	<title><?php echo esc_html( $portfolio->title ); ?></title>
	<?php echo $styles; ?>
</head>
<body>
	<?php
	$template_manager = new SynPat_PDF_Templates();
	echo $template_manager->generate_header( $portfolio->title );
	?>
	
	<div class="section metadata">
		<h2><?php esc_html_e( 'Portfolio Overview', 'synpat-platform' ); ?></h2>
		
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Portfolio ID:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $portfolio->id ); ?></span>
		</div>
		
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Total Patents:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $portfolio->n_patents ); ?></span>
		</div>
		
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Essential Patents:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $portfolio->essnt ); ?></span>
		</div>
		
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Current Licensees:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( $portfolio->n_lic ); ?></span>
		</div>
		
		<?php if ( ! empty( $portfolio->u_upfront ) ) : ?>
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Upfront Fee:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( '$' . number_format( $portfolio->u_upfront, 2 ) ); ?></span>
		</div>
		<?php endif; ?>
		
		<div class="metadata-item">
			<span class="metadata-label"><?php esc_html_e( 'Status:', 'synpat-platform' ); ?></span>
			<span><?php echo esc_html( ucfirst( $portfolio->status ) ); ?></span>
		</div>
	</div>
	
	<?php if ( ! empty( $portfolio->description ) ) : ?>
	<div class="section">
		<h2><?php esc_html_e( 'Description', 'synpat-platform' ); ?></h2>
		<div class="content">
			<?php echo wp_kses_post( wpautop( $portfolio->description ) ); ?>
		</div>
	</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $patents ) ) : ?>
	<div class="section">
		<h2><?php esc_html_e( 'Patent List', 'synpat-platform' ); ?></h2>
		
		<table>
			<thead>
				<tr>
					<th><?php esc_html_e( 'Patent Number', 'synpat-platform' ); ?></th>
					<th><?php esc_html_e( 'Title', 'synpat-platform' ); ?></th>
					<th><?php esc_html_e( 'Status', 'synpat-platform' ); ?></th>
					<th><?php esc_html_e( 'Filing Date', 'synpat-platform' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $patents as $patent ) : ?>
				<tr>
					<td><?php echo esc_html( $patent->patent_number ); ?></td>
					<td><?php echo esc_html( wp_trim_words( $patent->title, 10 ) ); ?></td>
					<td><?php echo esc_html( ucfirst( $patent->status ?? 'active' ) ); ?></td>
					<td>
						<?php
						if ( ! empty( $patent->filing_date ) ) {
							echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $patent->filing_date ) ) );
						}
						?>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php endif; ?>
	
	<?php if ( ! empty( $patents ) ) : ?>
	<div class="page-break"></div>
	
	<div class="section">
		<h2><?php esc_html_e( 'Detailed Patent Information', 'synpat-platform' ); ?></h2>
		
		<?php foreach ( $patents as $index => $patent ) : ?>
			<?php if ( $index > 0 ) : ?>
				<div class="page-break"></div>
			<?php endif; ?>
			
			<div class="patent-detail">
				<h3><?php echo esc_html( $patent->patent_number . ' - ' . $patent->title ); ?></h3>
				
				<div class="metadata">
					<?php if ( ! empty( $patent->abstract ) ) : ?>
					<div class="metadata-item">
						<span class="metadata-label"><?php esc_html_e( 'Abstract:', 'synpat-platform' ); ?></span>
					</div>
					<p><?php echo esc_html( wp_trim_words( $patent->abstract, 100 ) ); ?></p>
					<?php endif; ?>
					
					<?php if ( ! empty( $patent->inventors ) ) : ?>
					<div class="metadata-item">
						<span class="metadata-label"><?php esc_html_e( 'Inventors:', 'synpat-platform' ); ?></span>
						<span><?php echo esc_html( $patent->inventors ); ?></span>
					</div>
					<?php endif; ?>
					
					<?php if ( ! empty( $patent->assignee ) ) : ?>
					<div class="metadata-item">
						<span class="metadata-label"><?php esc_html_e( 'Assignee:', 'synpat-platform' ); ?></span>
						<span><?php echo esc_html( $patent->assignee ); ?></span>
					</div>
					<?php endif; ?>
					
					<?php if ( ! empty( $patent->ipc_classification ) ) : ?>
					<div class="metadata-item">
						<span class="metadata-label"><?php esc_html_e( 'IPC Classification:', 'synpat-platform' ); ?></span>
						<span><?php echo esc_html( $patent->ipc_classification ); ?></span>
					</div>
					<?php endif; ?>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<?php endif; ?>
	
	<?php echo $template_manager->generate_footer(); ?>
</body>
</html>
