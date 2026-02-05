<?php
/**
 * Template: Single Patent View (Inner Room)
 * Displays detailed patent information
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="synpat-patent-single" data-patent-id="<?php echo esc_attr( $patent->id ); ?>">
	<div class="patent-header">
		<div class="patent-number-display">
			<span class="label"><?php esc_html_e( 'Patent Number:', 'synpat-platform' ); ?></span>
			<span class="value"><?php echo esc_html( $patent->patent_number ); ?></span>
		</div>
		
		<h1 class="patent-title"><?php echo esc_html( $patent->title ); ?></h1>
	</div>
	
	<div class="patent-metadata">
		<div class="meta-row">
			<span class="meta-label"><?php esc_html_e( 'Assignee:', 'synpat-platform' ); ?></span>
			<span class="meta-value"><?php echo esc_html( $patent->assignee ); ?></span>
		</div>
		
		<?php if ( $patent->filing_date ) : ?>
		<div class="meta-row">
			<span class="meta-label"><?php esc_html_e( 'Filing Date:', 'synpat-platform' ); ?></span>
			<span class="meta-value"><?php echo esc_html( date_i18n( 'F d, Y', strtotime( $patent->filing_date ) ) ); ?></span>
		</div>
		<?php endif; ?>
		
		<?php if ( $patent->grant_date ) : ?>
		<div class="meta-row">
			<span class="meta-label"><?php esc_html_e( 'Grant Date:', 'synpat-platform' ); ?></span>
			<span class="meta-value"><?php echo esc_html( date_i18n( 'F d, Y', strtotime( $patent->grant_date ) ) ); ?></span>
		</div>
		<?php endif; ?>
		
		<?php if ( $patent->expiration_date ) : ?>
		<div class="meta-row">
			<span class="meta-label"><?php esc_html_e( 'Expiration Date:', 'synpat-platform' ); ?></span>
			<span class="meta-value"><?php echo esc_html( date_i18n( 'F d, Y', strtotime( $patent->expiration_date ) ) ); ?></span>
		</div>
		<?php endif; ?>
	</div>
	
	<div class="patent-citations">
		<h2><?php esc_html_e( 'Citation Information', 'synpat-platform' ); ?></h2>
		
		<div class="citations-grid">
			<div class="citation-card">
				<div class="citation-count"><?php echo esc_html( $patent->forward_citations ); ?></div>
				<div class="citation-label"><?php esc_html_e( 'Forward Citations', 'synpat-platform' ); ?></div>
			</div>
			
			<div class="citation-card">
				<div class="citation-count"><?php echo esc_html( $patent->backward_citations ); ?></div>
				<div class="citation-label"><?php esc_html_e( 'Backward Citations', 'synpat-platform' ); ?></div>
			</div>
		</div>
	</div>
	
	<?php if ( $patent->abstract ) : ?>
	<div class="patent-abstract">
		<h2><?php esc_html_e( 'Abstract', 'synpat-platform' ); ?></h2>
		<div class="abstract-content">
			<?php echo wp_kses_post( $patent->abstract ); ?>
		</div>
	</div>
	<?php endif; ?>
	
	<div class="patent-actions">
		<button class="btn btn-primary generate-pdf" data-patent-id="<?php echo esc_attr( $patent->id ); ?>">
			<?php esc_html_e( 'Generate PDF', 'synpat-platform' ); ?>
		</button>
		
		<?php do_action( 'synpat_patent_actions', $patent ); ?>
	</div>
</div>
