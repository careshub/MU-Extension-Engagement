<?php
/**
 * Template functions for the plugin.
 *
 * @package   MUExt_Engagement
 * @author    dcavins
 * @license   GPL-2.0+
 * @link      http://www.communitycommons.org
 * @copyright 2017 CARES, University of Missouri
 */

/**
 * Render the filter box for the engagements archive.
 *
 * @since 1.0.0
 *
 * @return string
 */
function muext_render_filters_box() {
	// Get our post type's details.
	$eng_obj = get_post_type_object( 'muext_engagement' );
	// Find out which taxonomies are related.
	$taxonomies = get_object_taxonomies( 'muext_engagement' );
	// Are there any search terms?
	$search_terms = ( ! empty( $_REQUEST['s'] ) ) ? $_REQUEST['s'] : '';
	$toggle_class = muext_archive_is_filtered_view() ? 'toggle-open' : 'toggle-closed';
	?>
	<div id="engagements-filters-container" class="muext-filters toggle-container <?php echo $toggle_class; ?>">
		<span class="arrow"></span><a href="#engagements-filters" id="engagements-filter-toggle" class="toggle-trigger">Filter <?php echo $eng_obj->labels->name; ?></a>
		<form id="engagements-filters" action="" method="GET" class="toggle-content engagements-filters clear">
			<input id="search-form-engagements" class="search-field" placeholder="<?php echo $eng_obj->labels->search_items; ?>&hellip;" value="<?php echo $search_terms ?>" name="s" type="search">
			<div class="Grid Grid--full med-Grid--fit">
				<?php
				// Provide filters for related taxonomies
				foreach ( $taxonomies as $taxonomy) {
					// Get the taxonomy's details
					$tax_object = get_taxonomy( $taxonomy );
					$friendly_param = muext_get_friendly_filter_param_name( $taxonomy );
					?>
					<div class="Grid-cell">
						<div class="inset-contents">
							<fieldset class="taxonomy-terms">
								<legend><?php echo $tax_object->labels->name; ?></legend>
								<?php
								// Get all the terms in the taxonomy, to build the checklist.
								$terms = get_terms( array(
									'taxonomy' => $taxonomy,
									'hide_empty' => false,
								) );
								// Get the active filter terms, if available.
								$selected_terms = muext_get_archive_filter_params( $taxonomy );

								foreach ( $terms as $term ) : ?>
									<label><input type="checkbox" value="<?php echo $term->slug; ?>" name="<?php echo $friendly_param; ?>[]" <?php
									if ( in_array( $term->slug, $selected_terms ) ) {
										echo 'checked="checked"';
									}
									?>> <?php echo $term->name; ?></label>
								<?php endforeach; ?>

							</fieldset>
						</div>
					</div>
					<?php
				}
				?>
			</div>
			<input type="hidden" name="engagement_filter_active" value=1>
			<input type="submit" value="Filter">
		</form>
	</div>
	<?php
}

function muext_import_shortcode(){
	ob_start();
	echo '<pre>';
	$import_start = get_option( 'muext_import_has_run' );
	$import_start = ( $import_start ) ? (int) $import_start : 1;
	$import_end = $import_start + 50;
	$row = 1;
	if ( ($handle = fopen( \MU_Ext_Engagement\get_plugin_base_path() . '/working/Ext_Data_For_Import.csv', "r") ) !== FALSE ) {
		while ( ( $data = fgetcsv( $handle, 0, "," ) ) !== FALSE ) {
			// post_title = $data[3]
			// post_content = $data[4]

			// _muext_college_affiliation = $data[0]
			// _muext_contact_name = $data[1]
			// _muext_contact_phone = $data[2]
			// _muext_location_text = $data[5]
			// _muext_timeframe = $data[6]
			// _muext_outcome_text = $data[7]

			// muext_program_category = $data[8]

			if ( $row >= $import_start && $row < $import_end ) {
				$post_args = array(
					'post_title' => $data[3],
					'post_type' => 'muext_engagement',
					'post_content' => $data[4],
					// 'tax_input' => array( 'muext_program_category' => $data[8] ),
					'meta_input' => array(
						'_muext_college_affiliation' => $data[0],
						'_muext_contact_name' => $data[1],
						'_muext_contact_phone' => $data[2],
						'_muext_location_text' => $data[5],
						'_muext_timeframe' => $data[6],
						'_muext_outcome_text' => $data[7],
					),
				);

				$post_id = wp_insert_post( $post_args );
				wp_set_post_terms( $post_id, (int) $data[9], 'muext_program_category' );
				echo PHP_EOL . "Created post {$post_id}";
				update_option( 'muext_import_has_run', $row, false);
			}
			++$row;
		}
	} else {
		echo 'fopen failed';
		var_dump( $handle );
	}
	fclose($handle);
	echo '</pre>';

	return ob_get_clean();
}
add_shortcode( 'muext_importer', 'muext_import_shortcode' );
