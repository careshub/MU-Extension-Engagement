<?php

namespace MU_Ext_Engagement\Public_Facing;

// Load plugin text domain
add_action( 'init', __NAMESPACE__ . '\\load_plugin_textdomain' );

// Use templates provided by the plugin.
add_filter( 'template_include', __NAMESPACE__ . '\\template_loader' );


// Load public-facing style sheet and JavaScript.
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_styles_scripts' );
// add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_scripts' ) );

// We may want to apply some GET params to the query on the DVT archive.
add_action( 'pre_get_posts', __NAMESPACE__ . '\\filter_archive_query' );

// Clean the GET string
add_action( 'template_redirect', __NAMESPACE__ . '\\reformat_get_string', 11 );

// filter the Engagement Theme tag cloud to add highlight class and to count child posts and only display top-level
//add_filter( 'wp_tag_cloud_args', __NAMESPACE__ . '\\filter_engagement_theme_tag_cloud_args' );
add_filter('wp_generate_tag_cloud_data', __NAMESPACE__ . '\\muext_tag_cloud_class_active');


/**
 * Load the plugin text domain for translation.
 *
 * @since    1.0.0
 */
function load_plugin_textdomain() {
	$domain = \MU_Ext_Engagement\get_plugin_slug();
	$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

	load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
}

/**
 * Register and enqueue public-facing style sheet.
 *
 * @since    1.0.0
 */
function enqueue_styles_scripts() {
	// Scripts
	wp_enqueue_script( \MU_Ext_Engagement\get_plugin_slug() . '-plugin-script', plugins_url( 'js/public.js', __FILE__ ), array( 'jquery' ), \MU_Ext_Engagement\get_plugin_version(), true );

	// Styles
	if ( is_singular( 'muext_engagement' ) || is_post_type_archive( 'muext_engagement' ) || is_engagement_tax_archive() || is_front_page() ) {
		wp_enqueue_style( \MU_Ext_Engagement\get_plugin_slug() . '-plugin-style', plugins_url( 'css/public.css', __FILE__ ), array(), \MU_Ext_Engagement\get_plugin_version(), 'all' );
		wp_enqueue_style( \MU_Ext_Engagement\get_plugin_slug() . '-fontawesome-style', plugins_url( 'font-awesome-4.7.0/css/font-awesome.min.css', __FILE__ ), array(), \MU_Ext_Engagement\get_plugin_version(), 'all' );
	}
}


/**
 * Register and enqueue public-facing style sheet.
 *
 * @since    1.0.0
 */
function enqueue_login_scripts() {
	// Scripts
	wp_enqueue_script( \MU_Ext_Engagement\get_plugin_slug() . '-login-plugin-scripts', plugins_url( 'js/login.js', __FILE__ ), array( 'jquery' ), \MU_Ext_Engagement\get_plugin_version(), true );

	wp_localize_script( \MU_Ext_Engagement\get_plugin_slug() . '-login-plugin-scripts', 'SSO_login', array(
			'sso_login_url' => esc_url( add_query_arg( 'action', 'use-sso', wp_login_url() ) ),
		)
	);
}

/**
 * Load a template.
 *
 * Handles template usage so that we can use our own templates instead of the theme's.
 * Templates are in the 'templates' folder.
 *
 * @param mixed $template
 * @return string
 */
function template_loader( $template ) {
	if ( is_embed() ) {
		return $template;
	}

	if ( is_front_page() ){
		$default_file = 'home-muext_engagement.php';
	} elseif ( is_singular( 'muext_engagement' ) ) {
		$default_file = 'single-muext_engagement.php';
	} elseif ( is_post_type_archive( 'muext_engagement' ) ) {
		$default_file = 'archive-muext_engagement.php';
	} elseif ( is_engagement_tax_archive() ) {
		$default_file = 'archive-muext_engagement.php';
	} else {
		$default_file = '';
	}

	if ( $default_file ) {
		if ( ! locate_template( $default_file ) ) {
			$template = get_template_path() . $default_file;
		}
	}

	return $template;
}

/**
 * Get template part (for templates like the shop-loop).
 *
 *
 * @param mixed $slug
 * @param string $name (default: '')
 */
function get_template_part( $slug, $name = '' ) {
	$template = '';

	// Look in yourtheme/slug-name.php
	if ( $name ) {
		$template = locate_template( array( "{$slug}-{$name}.php" ) );
	}

	// Get default slug-name.php
	if ( ! $template && $name && file_exists( get_template_path() . "{$slug}-{$name}.php" ) ) {
		$template = get_template_path() . "{$slug}-{$name}.php";
	}

	// If template file doesn't exist, look in yourtheme/slug.php
	if ( ! $template ) {
		$template = locate_template( array( "{$slug}.php" ) );
	}

	// Allow 3rd party plugins to filter template file from their plugin.
	$template = apply_filters( 'muext_get_template_part', $template, $slug, $name );

	if ( $template ) {
		load_template( $template, false );
	}
}

/**
 * Use GET params to modify the archive query.
 *
 * @since    1.0.0
 */
function filter_archive_query( $wp_query_obj ) {
	// We use GET params to track the state of the filters.
	if ( ! is_admin() && $wp_query_obj->is_post_type_archive( 'muext_engagement' ) && $wp_query_obj->is_main_query() ) {
		// Check for term filters.
		// Which taxonomies are we interested in?
		$taxonomies = get_object_taxonomies( 'muext_engagement' );
		foreach ( $taxonomies as $taxonomy ) {
			if ( $terms = muext_get_archive_filter_params( $taxonomy ) ) {
				$wp_query_obj->query_vars['tax_query'][] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'slug',
					'terms'    => $terms,
				);
			}
		}
	}
}

/**
 * Cleanup GET params upon submission.
 *
 * Standard GET submission looks like:
 * ?s=schmoly&theme[]=agriculture&theme[]=health&t[]=lunch&t[]=pockets
 * We reformat it to look like this:
 * ?s=schmoly&theme=agriculture,health&t=lunch,pockets
 *
 * @since    1.0.0
 */
function reformat_get_string() {
	if ( is_post_type_archive( 'muext_engagement' ) && ! empty( $_GET['engagement_filter_active'] ) ) {
		$cleaned = array();
		$redirect = false;

		// Which taxonomies are we interested in?
		$taxonomies = get_object_taxonomies( 'muext_engagement' );

		// Loop through the taxonomies and convert them from arrays to comma-separated strings.
		foreach ( $taxonomies as $taxonomy ) {
			// We're using the friendly param in the form and the query parser.
			$friendly_param = muext_get_friendly_filter_param_name( $taxonomy );
			if ( isset( $_GET[ $friendly_param ] ) ) {
				if ( is_array( $_REQUEST[ $friendly_param ] ) ) {
					$_GET[ $friendly_param ] = implode( ',', array_map( 'urlencode', $_REQUEST[ $friendly_param ] ) );
					// Since we've changed the params, we want to redirect.
					$redirect = true;
				} else {
					$_GET[ $friendly_param ] = urlencode( $_REQUEST[ $friendly_param ] );
				}
			}
		}

		// Remove an empty search param, because yuck.
		if ( isset( $_GET['s'] ) && ! $_GET['s'] ) {
			unset( $_GET['s'] );
			// Since we've changed the params, we want to redirect.
			$redirect = true;
		}

		/*
		 * Unset the marker we're using to know that the user requested a filtered view,
		 * so we don't run this routine on the redirect page load.
		 */
		unset( $_GET['engagement_filter_active'] );

		if ( $redirect ) {
			// Add the cleaned up parameters to the archive url.
			wp_safe_redirect( add_query_arg( $_GET, get_post_type_archive_link( 'muext_engagement' ) ) );
			exit;
		}
	}
}


/**
 * Get template directory path.
 *
 * @return string $path File path to the included templates directory.
 */
function get_template_path() {
	return \MU_Ext_Engagement\get_plugin_base_path() . 'public/templates/';
}

/**
 * Convert dates that are stored in 2017-05-26 format to May 26, 2017 format
 * for readability.
 *
 * @return string $date Text-formatted date.
 */
function convert_to_human_date( $date ) {
    // Goal format is "F j, Y"
    $date = date_create_from_format( 'Y-m-d', $date );
    return date_format( $date, 'F j, Y' );
}

function is_engagement_tax_archive() {
	return is_tax( array( 'muext_program_category', 'muext_program_tag', 'muext_program_audience', 'muext_program_impact_area', 'muext_program_outreach_type', 'muext_program_affiliation' ) );
}


/**
 * Format the tag clouds: add active-tag class to selected tags, 
 *
 */
function muext_tag_cloud_class_active($tags_data) {

		//for debug
		$org_tags_data = $tags_data;
        
		$body_class = get_body_class();

        foreach ($tags_data as $key => $tag) {
			
			$current_count = 0;
			
			//if we are on this tag's/category's page, add an active class to the tag cloud element
            if( in_array('term-' . $tag['id'], $body_class )) {
                $tags_data[$key]['class'] =  $tags_data[$key]['class'] . " active-tag";
            }
			
			//if this tag is a child category, do not display
			if( muext_category_has_parent( $tag['id'] ) ){
				//remove from 
				unset( $tags_data[$key] );
				
			} else {
				//add children to count
				$current_count = muext_get_postcount( $tag['id'] );
			}
			
			//var_dump( $current_count );
        }
		
		
		
		//var_dump( $tags_data );
        return $tags_data;
}


/**
 * Filter tag cloud output if Engagement Themes selected 
 * to only show top layer.
 *
 */
function filter_engagement_theme_tag_cloud_args() {
	//if we're on the engagement theme taxonomy..
	
	$exclude = array( 2, 15, 9, 5, 263, 259, 10, 11, 14, 13, 16 );
	$args = array(
        //'taxonomy' => $current_taxonomy  
		'exclude' => $exclude
    );
	
	
	return $args;
}


/***** utility functions *****/
//TODO: this doesn't work w/o the taxonomy name - how do we get that from tag_cloud?
function muext_get_postcount( $id ){

	/*$child_terms = get_term_children( $id );
	//return count of post in category child of ID 15
	$count = 0;
	$taxonomy = 'category';
	$args = array(
		'child_of' => $id,
	);
	
	$child_tax_terms = get_terms( $id, '', $args );
	foreach ( $child_terms as $tax_term ) {
		$count += $tax_term->count;
	}
	return $child_terms;*/
}

function muext_category_has_parent($catid){
	
    $category = get_category($catid);
	
    if ($category->category_parent > 0){
        return true;
    }
    return false;
}








