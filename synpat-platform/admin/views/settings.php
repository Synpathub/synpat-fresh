<?php
/**
 * Settings Interface View
 *
 * @package SynPat_Platform
 * @since 1.0.0
 */

defined( 'ABSPATH' ) || exit;

$config_manager = new SynPat_Settings();
$schema = $config_manager->get_schema();
?>

<div class="wrap">
	<h1><?php esc_html_e( 'SynPat Configuration', 'synpat-platform' ); ?></h1>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="synpat_save_config">
		<?php wp_nonce_field( 'synpat_config_save' ); ?>

		<?php foreach ( $schema as $section_id => $section_data ) : ?>
			<div class="synpat-config-section" style="background: white; padding: 25px; margin: 20px 0; border-left: 5px solid #0073aa;">
				<h2 style="margin-top: 0;"><?php echo esc_html( $section_data['label'] ); ?></h2>
				
				<table class="form-table">
					<?php foreach ( $section_data['fields'] as $field_id => $field_data ) : 
						$field_value = $config_manager->retrieve_option_value( $section_id, $field_id, $field_data['default'] );
						$field_name = 'synpat_config_' . $section_id . '[' . $field_id . ']';
					?>
						<tr>
							<th scope="row">
								<label for="<?php echo esc_attr( $field_id ); ?>">
									<?php echo esc_html( $field_data['label'] ); ?>
								</label>
							</th>
							<td>
								<?php
								switch ( $field_data['type'] ) {
									case 'checkbox':
										printf(
											'<input type="checkbox" name="%s" id="%s" value="1" %s>',
											esc_attr( $field_name ),
											esc_attr( $field_id ),
											checked( $field_value, 1, false )
										);
										break;
									
									case 'number':
										printf(
											'<input type="number" name="%s" id="%s" value="%s" class="small-text">',
											esc_attr( $field_name ),
											esc_attr( $field_id ),
											esc_attr( $field_value )
										);
										break;
									
									default:
										printf(
											'<input type="text" name="%s" id="%s" value="%s" class="regular-text">',
											esc_attr( $field_name ),
											esc_attr( $field_id ),
											esc_attr( $field_value )
										);
								}
								
								if ( ! empty( $field_data['description'] ) ) {
									printf( '<p class="description">%s</p>', esc_html( $field_data['description'] ) );
								}
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			</div>
		<?php endforeach; ?>

		<?php submit_button( __( 'Save Configuration', 'synpat-platform' ) ); ?>
	</form>
</div>
