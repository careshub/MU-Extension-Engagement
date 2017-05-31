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
