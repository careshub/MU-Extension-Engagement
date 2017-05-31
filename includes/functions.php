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
