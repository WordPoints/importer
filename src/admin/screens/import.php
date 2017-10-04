<?php

/**
 * The Importer administration screen.
 *
 * @package WordPoints_Importer
 * @since 1.0.0
 */

if ( true !== current_user_can( 'manage_options' ) ) {
	wp_die();
}

$tabs = array( 'unavailable' => __( 'Unavailable', 'wordpoints-importer' ) );
$unavailable = array();

foreach ( WordPoints_Importers::get() as $slug => $args ) {

	$importer = WordPoints_Importers::get_importer( $slug );

	$is_available = $importer->is_available();

	if ( is_wp_error( $is_available ) ) {
		$unavailable[ $args['name'] ] = $is_available;
	} else {
		$tabs[ $slug ] = $args['name'];
	}
}

if ( empty( $unavailable ) ) {
	unset( $tabs['unavailable'] );
}

$current_tab = wordpoints_admin_get_current_tab( $tabs );

$components = WordPoints_Components::instance()->get();

?>

<h2><?php esc_html_e( 'WordPoints Importer', 'wordpoints-importer' ); ?></h2>

<div class="wrap">
	<?php wordpoints_admin_show_tabs( $tabs, false ); ?>

	<?php if ( 'unavailable' === $current_tab ) : ?>

		<p><?php esc_html_e( 'The below importers are not currently available.', 'wordpoints-importer' ); ?></p>

		<ul>
			<?php foreach ( $unavailable as $name => $error ) : ?>
				<li>
					<?php

					printf(
						/* translators: 1 is an importer name, 2 is the reason that it is unavailable. */
						esc_html__( '%1$s — %2$s', 'wordpoints-importer' )
						, '<strong>' . esc_html( $name ) . '</strong>'
						, esc_html( $error->get_error_message() )
					);

					?>
				</li>
			<?php endforeach; ?>
		</ul>

	<?php else : ?>

		<?php $importer = WordPoints_Importers::get_importer( $current_tab ); ?>

		<p><?php esc_html_e( 'Select which items you would like imported.', 'wordpoints-importer' ); ?></p>

		<form method="post" action="<?php echo esc_url( self_admin_url( 'admin.php?page=wordpoints_importing' ) ); ?>">
			<?php foreach ( $components as $slug => $component ) : ?>

				<?php

				if ( ! $importer->supports_component( $slug ) ) {
					continue;
				}

				$supported = true;

				?>

				<h3><?php echo esc_html( $component['name'] ); ?></h3>

				<?php

				/**
				 * Before the import option fields for a component.
				 *
				 * @since 1.0.0
				 */
				do_action( "wordpoints_importer_before_component_options-{$slug}" );

				?>

				<?php foreach ( $importer->get_options_for_component( $slug ) as $name => $option ) : ?>

					<?php

					$can_import = true;

					// Check if this option is available.
					if ( isset( $option['can_import'] ) ) {
						$can_import = call_user_func( $option['can_import'], array() );
					}

					?>

					<label for="wordpoints_import[<?php echo esc_attr( $slug ); ?>][<?php echo esc_attr( $name ); ?>]">
						<input type="checkbox" value="1" id="wordpoints_import[<?php echo esc_attr( $slug ); ?>][<?php echo esc_attr( $name ); ?>]" name="wordpoints_import[<?php echo esc_attr( $slug ); ?>][<?php echo esc_attr( $name ); ?>]" <?php disabled( is_wp_error( $can_import ), true ); ?> />
						<?php echo esc_html( $option['label'] ); ?>
						<?php if ( is_wp_error( $can_import ) ) : ?>
							&nbsp;&nbsp;
							<em>
								<?php

								// translators: Error message explaining the reason why the importer is disabled.
								printf( esc_html__( 'Disabled (%s)', 'wordpoints-importer' ), esc_html( $can_import->get_error_message() ) );

								?>
							</em>
						<?php endif; ?>
					</label>

					<?php if ( isset( $option['description'] ) ) : ?>
						<p class="description" style="margin-bottom: 10px; margin-left: 25px;">
							<?php echo esc_html( $option['description'] ); ?>
						</p>
					<?php else : ?>
						<br style="margin-bottom: 10px" />
					<?php endif; ?>

				<?php endforeach; ?>

			<?php endforeach; ?>

			<input type="hidden" value="<?php echo esc_attr( $current_tab ); ?>" name="importer" />

			<?php

			if ( ! isset( $supported ) ) {

				esc_html_e(
					'This importer does not support any of the installed components.'
					, 'wordpoints-importer'
				);

			} else {

				wp_nonce_field( 'wordpoints_import' );
				submit_button( __( 'Import', 'wordpoints-importer' ) );
			}

			?>
		</form>
	<?php endif; ?>
</div>
