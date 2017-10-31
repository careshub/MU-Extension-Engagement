<?php
/**
 * Utility functions for the plugin.
 *
 * @package   MUExt_Engagement
 * @author    dcavins
 * @license   GPL-2.0+
 * @link      http://www.communitycommons.org
 * @copyright 2017 CARES, University of Missouri
 */

/**
 * Parse the $_REQUEST to see what types of objects we're looking for.
 *
 * @since    1.0.0
 */
function muext_get_archive_filter_params( $taxonomy = '' ) {
	$params = array();

	// We use the friendly names in GET strings.
	$friendly_param = muext_get_friendly_filter_param_name( $taxonomy );

	// Check for an active filter.
	if ( isset( $_REQUEST[ $friendly_param ] ) ) {
		// The param could be an array, or a string or comma-separated.
		if ( ! is_array( $_REQUEST[ $friendly_param ] ) ) {
			$params = explode( ',', $_REQUEST[ $friendly_param ] );
		} else {
			$params = $_REQUEST[ $friendly_param ];
		}

		// Remove terms that don't exist.
		$terms = get_terms( array(
			'taxonomy' => $taxonomy,
			'hide_empty' => false,
		) );

		$term_slugs = wp_list_pluck( $terms, 'slug', 'term_id' );

		foreach ( $params as $k => $param ) {
			if ( ! in_array( $param, $term_slugs ) ) {
				unset( $params[$k] );
			}
		}
	}

	return $params;
}

function muext_get_friendly_filter_param_name( $param = '' ) {
	switch ( $param ) {
		case 'muext_program_category':
			$retval = 'theme';
			break;
		case 'muext_program_tag':
			$retval = 't';
			break;
		default:
			$retval = $param;
			break;
	}
	return $retval;
}

function muext_archive_is_filtered_view() {
	// We'll return true at the first sign that this is a filtered view.
	if ( is_post_type_archive( 'muext_engagement' ) ) {

		// Look for active searches first.
		if ( ! empty( $_REQUEST['s'] ) ) {
			return true;
		}

		// Next, look to see if any of our taxonomy filters are being applied.
		$taxonomies = get_object_taxonomies( 'muext_engagement' );
		foreach ( $taxonomies as $taxonomy ) {
			if ( muext_get_archive_filter_params( $taxonomy ) ) {
				return true;
			}
		}

	}
	return false;
}


/*
 ** Add metabox for additional author(s) to Engagements post type
 *
 */
add_action( 'add_meta_boxes', 'muext_add_metaboxes' ); // add meta box
//add_filter( 'manage_post_posts_columns', 'muext_add_post_columns' ); // add custom columns for quick edit
//add_action( 'quick_edit_custom_box', 'muext_quickedit_coauthor', 10, 2 ); // add quick edit meta box
add_action( 'save_post', 'muext_save_coauthor_metabox' );
 
function muext_add_post_columns( $columns ) {
    $columns['coauthor'] = 'Co Author';
    return $columns;
}

function muext_add_metaboxes() {
	// ad coauthor metabox
    add_meta_box('muext-coauthor', 'Co-Author', 'muext_coauthor_callback', 'muext_engagement', 'normal', 'default');

}
// The Event Location Metabox

function muext_coauthor_callback( $post ) {

	// we're coming from WP, yes?
	wp_create_nonce( basename(__FILE__) , 'muext_coauthor_callback_nonce');

	// get users list, excluding admin
	$args = array(
		'exclude' => array(1),
	);

	$users = get_users( $args );

	// now make your users dropdown
	if ($users) { 
		$this_coauthor_id =  get_post_meta( $post->ID, '_muext_coauthor', true );
		?>
		<select name="coauthor_select">
			<option value="0"> -- Select -- </option>
			<?php foreach ($users as $user) {
				$selected = ( $user->ID == $this_coauthor_id ) ? "selected" : "";
				echo '<option value="' . $user->ID . '" ' . $selected . '>' .$user->user_nicename .'</option>';
			} ?>
		</select>
		<?php
	}
}

/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */
function muext_save_coauthor_metabox( $post_id ) {

    // Save the coauthor in a serialized array, because we may add more
	if ( ! isset( $_POST['muext_coauthor_callback_nonce'] ) ) {
        //return;
    }

	// Check the user's permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ){
		return;
	}

	if ( isset( $_POST['coauthor_select'] ) ) {
		update_post_meta( $post_id, '_muext_coauthor', sanitize_text_field( $_POST['coauthor_select'] ) );
	} else {

		delete_post_meta( $post_id, '_muext_coauthor', sanitize_text_field( $_POST['coauthor_select'] ) );
	
	}

}
function muext_quickedit_coauthor( $column_name, $post_type ) {
	if ($column_name != 'coauthor') return;
	
    static $printNonce = TRUE;
	
    if ( $printNonce ) {
        $printNonce = FALSE;
        wp_nonce_field( plugin_basename( __FILE__ ), 'muext_coauthor_quickedit_nonce' );
    }
	
	?>
		<fieldset class="inline-edit-col-left inline-edit-coauthor">
			<div class="inline-edit-col column-<?php echo $column_name; ?>">
		  
				<?php
					// get users list, excluding admin
				$args = array(
					'exclude' => array(1),
				);

				$users = get_users( $args );

					// now make your users dropdown
					if ($users) { 
						$this_coauthor_id =  get_post_meta( $post->ID, '_muext_coauthor', true );
						?>
						<select name="coauthor_select">
							<?php foreach ($users as $user) {
								$selected = ( $user->ID == $this_coauthor_id ) ? "selected" : "";
								echo '<option value="' . $user->ID . '" ' . $selected . '>' .$user->user_nicename .'</option>';
							} ?>
						</select>
						<?php
					} 
				?>
		
			</div>
		</fieldset>
    <?php
	
}
