<?php

namespace MU_Ext_Engagement\CPT_Tax;

// Register the engagement post type.
add_action( 'init', __NAMESPACE__ . '\\register_muext_engagement_cpt' );
// Register the engagement program category taxonomy.
add_action( 'init', __NAMESPACE__ . '\\register_program_categories' );
// Register the engagement program tag taxonomy.
add_action( 'init', __NAMESPACE__ . '\\register_program_tags' );
// Register the engagement program audience taxonomy.
add_action( 'init', __NAMESPACE__ . '\\register_program_audience' );
// Register the engagement program impact area taxonomy.
add_action( 'init', __NAMESPACE__ . '\\register_program_impact_areas' );
// Register the engagement program impact area taxonomy.
add_action( 'init', __NAMESPACE__ . '\\register_program_outreach_type' );
// Register the engagement program impact area taxonomy.
add_action( 'init', __NAMESPACE__ . '\\register_program_affiliation' );
// Register the engagement program funding source taxonomy.
add_action( 'init', __NAMESPACE__ . '\\register_program_funding_source' );
// Register the engagement's geoids.
add_action( 'init', __NAMESPACE__ . '\\register_muext_geoid' );

// Do some magic to keep "advancement"-themed engagements out of the REST API responses.
add_action( 'rest_muext_engagement_query', __NAMESPACE__ . '\\disallow_advancement_themed_eng', 10, 2 );

// Register Custom Post Type
function register_muext_engagement_cpt() {
	$labels = array(
		'name'                  => _x( 'Engagements', 'Post Type General Name', 'muext-engagement' ),
		'singular_name'         => _x( 'Engagement', 'Post Type Singular Name', 'muext-engagement' ),
		'menu_name'             => __( 'Engagements', 'muext-engagement' ),
		'name_admin_bar'        => __( 'Engagement', 'muext-engagement' ),
		'archives'              => __( 'Engagement Archives', 'muext-engagement' ),
		'attributes'            => __( 'Engagement Attributes', 'muext-engagement' ),
		'parent_item_colon'     => __( 'Parent Engagement:', 'muext-engagement' ),
		'all_items'             => __( 'All Engagements', 'muext-engagement' ),
		'add_new_item'          => __( 'Add New Engagement', 'muext-engagement' ),
		'add_new'               => __( 'Add New', 'muext-engagement' ),
		'new_item'              => __( 'New Engagement', 'muext-engagement' ),
		'edit_item'             => __( 'Edit Engagement', 'muext-engagement' ),
		'update_item'           => __( 'Update Engagement', 'muext-engagement' ),
		'view_item'             => __( 'View Engagement', 'muext-engagement' ),
		'view_items'            => __( 'View Engagements', 'muext-engagement' ),
		'search_items'          => __( 'Search Engagements', 'muext-engagement' ),
		'not_found'             => __( 'Not found', 'muext-engagement' ),
		'not_found_in_trash'    => __( 'Not found in Trash', 'muext-engagement' ),
		'featured_image'        => __( 'Featured Image', 'muext-engagement' ),
		'set_featured_image'    => __( 'Set featured image', 'muext-engagement' ),
		'remove_featured_image' => __( 'Remove featured image', 'muext-engagement' ),
		'use_featured_image'    => __( 'Use as featured image', 'muext-engagement' ),
		'insert_into_item'      => __( 'Insert into engagement', 'muext-engagement' ),
		'uploaded_to_this_item' => __( 'Uploaded to this engagement', 'muext-engagement' ),
		'items_list'            => __( 'Engagements list', 'muext-engagement' ),
		'items_list_navigation' => __( 'Engagements list navigation', 'muext-engagement' ),
		'filter_items_list'     => __( 'Filter Engagements list', 'muext-engagement' ),
	);
	$capabilities = array(
		'edit_post'             => 'edit_post',
		'read_post'             => 'read_post',
		'delete_post'           => 'delete_post',
		'edit_posts'            => 'edit_posts',
		'edit_others_posts'     => 'edit_others_posts',
		'publish_posts'         => 'publish_posts',
		'read_private_posts'    => 'read_private_posts',
	);
	$args = array(
		'label'                 => __( 'Engagement', 'muext-engagement' ),
		'description'           => __( 'Stores information about ongoing engagements.', 'muext-engagement' ),
		'labels'                => $labels,
		'supports'              => array( 'title', 'thumbnail', 'revisions', 'author' ),
		'taxonomies'            => array(
										'muext_program_category',
										'muext_program_tag',
										'muext_program_audience',
										'muext_program_impact_area',
										'muext_program_outreach_type',
										'muext_program_affiliation'
									),
		'hierarchical'          => false,
		'public'                => true,
		'show_ui'               => true,
		'show_in_menu'          => true,
		'menu_position'         => 5,
		'menu_icon'             => 'dashicons-location-alt',
		'show_in_admin_bar'     => true,
		'show_in_nav_menus'     => true,
		'can_export'            => true,
		'has_archive'           => 'engagements',
		'rewrite'               => array( 'slug' => 'engagements' ),
		'exclude_from_search'   => false,
		'publicly_queryable'    => true,
		// 'capabilities'          => $capabilities,
		'show_in_rest'          => true,
		'rest_controller_class' => 'WP_REST_Engagements_Controller',
	);
	register_post_type( 'muext_engagement', $args );
}

// Register Custom Taxonomy
function register_program_categories() {
	$labels = array(
		'name'                       => _x( 'Themes', 'Taxonomy General Name', 'muext-engagement' ),
		'singular_name'              => _x( 'Theme', 'Taxonomy Singular Name', 'muext-engagement' ),
		'menu_name'                  => __( 'Theme', 'muext-engagement' ),
		'all_items'                  => __( 'All Themes', 'muext-engagement' ),
		'parent_item'                => __( 'Parent Theme', 'muext-engagement' ),
		'parent_item_colon'          => __( 'Parent Theme:', 'muext-engagement' ),
		'new_item_name'              => __( 'New Theme Name', 'muext-engagement' ),
		'add_new_item'               => __( 'Add Theme Item', 'muext-engagement' ),
		'edit_item'                  => __( 'Edit Theme', 'muext-engagement' ),
		'update_item'                => __( 'Update Theme', 'muext-engagement' ),
		'view_item'                  => __( 'View Theme', 'muext-engagement' ),
		'separate_items_with_commas' => __( 'Separate themes with commas', 'muext-engagement' ),
		'add_or_remove_items'        => __( 'Add or remove themes', 'muext-engagement' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'muext-engagement' ),
		'popular_items'              => __( 'Popular Themes', 'muext-engagement' ),
		'search_items'               => __( 'Search Themes', 'muext-engagement' ),
		'not_found'                  => __( 'Not Found', 'muext-engagement' ),
		'no_terms'                   => __( 'No themes', 'muext-engagement' ),
		'items_list'                 => __( 'Themes list', 'muext-engagement' ),
		'items_list_navigation'      => __( 'Themes list navigation', 'muext-engagement' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
		'rewrite'                    => array( 'slug' => 'themes' ),
	);
	register_taxonomy( 'muext_program_category', array( 'muext_engagement' ), $args );
}

// Register Custom Taxonomy
function register_program_tags() {

	$labels = array(
		'name'                       => _x( 'Program Tags', 'Taxonomy General Name', 'muext-engagement' ),
		'singular_name'              => _x( 'Program Tag', 'Taxonomy Singular Name', 'muext-engagement' ),
		'menu_name'                  => __( 'Program Tag', 'muext-engagement' ),
		'all_items'                  => __( 'All Tags', 'muext-engagement' ),
		'parent_item'                => __( 'Parent Tag', 'muext-engagement' ),
		'parent_item_colon'          => __( 'Parent Tag:', 'muext-engagement' ),
		'new_item_name'              => __( 'New Tag Name', 'muext-engagement' ),
		'add_new_item'               => __( 'Add New Tag', 'muext-engagement' ),
		'edit_item'                  => __( 'Edit Tag', 'muext-engagement' ),
		'update_item'                => __( 'Update Tag', 'muext-engagement' ),
		'view_item'                  => __( 'View Tag', 'muext-engagement' ),
		'separate_items_with_commas' => __( 'Separate tags with commas', 'muext-engagement' ),
		'add_or_remove_items'        => __( 'Add or remove tags', 'muext-engagement' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'muext-engagement' ),
		'popular_items'              => __( 'Popular Tags', 'muext-engagement' ),
		'search_items'               => __( 'Search Tags', 'muext-engagement' ),
		'not_found'                  => __( 'Not Found', 'muext-engagement' ),
		'no_terms'                   => __( 'No tags', 'muext-engagement' ),
		'items_list'                 => __( 'Items tags', 'muext-engagement' ),
		'items_list_navigation'      => __( 'Tags list navigation', 'muext-engagement' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => false,
		'rewrite'                    => array( 'slug' => 'keywords' ),
	);
	register_taxonomy( 'muext_program_tag', array( 'muext_engagement' ), $args );

}

function register_program_audience() {

	$labels = array(
		'name'                       => _x( 'Program Audience', 'Taxonomy General Name', 'muext-engagement' ),
		'singular_name'              => _x( 'Program Audience', 'Taxonomy Singular Name', 'muext-engagement' ),
		'menu_name'                  => __( 'Program Audience', 'muext-engagement' ),
		'all_items'                  => __( 'All Audiences', 'muext-engagement' ),
		'parent_item'                => __( 'Parent Audience', 'muext-engagement' ),
		'parent_item_colon'          => __( 'Parent Tag:', 'muext-engagement' ),
		'new_item_name'              => __( 'New Audience Name', 'muext-engagement' ),
		'add_new_item'               => __( 'Add New Audience', 'muext-engagement' ),
		'edit_item'                  => __( 'Edit Audience', 'muext-engagement' ),
		'update_item'                => __( 'Update Audience', 'muext-engagement' ),
		'view_item'                  => __( 'View Audience', 'muext-engagement' ),
		'separate_items_with_commas' => __( 'Separate audiences with commas', 'muext-engagement' ),
		'add_or_remove_items'        => __( 'Add or remove audiences', 'muext-engagement' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'muext-engagement' ),
		'popular_items'              => __( 'Popular Audiences', 'muext-engagement' ),
		'search_items'               => __( 'Search Audiences', 'muext-engagement' ),
		'not_found'                  => __( 'Not Found', 'muext-engagement' ),
		'no_terms'                   => __( 'No audiences', 'muext-engagement' ),
		'items_list'                 => __( 'Items audiences', 'muext-engagement' ),
		'items_list_navigation'      => __( 'Audiences list navigation', 'muext-engagement' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => false,
		'rewrite'                    => array( 'slug' => 'audience' ),
	);
	register_taxonomy( 'muext_program_audience', array( 'muext_engagement' ), $args );

}


function register_program_impact_areas() {

	$labels = array(
		'name'                       => _x( 'Impact Area', 'Taxonomy General Name', 'muext-engagement' ),
		'singular_name'              => _x( 'Impact Area', 'Taxonomy Singular Name', 'muext-engagement' ),
		'menu_name'                  => __( 'Impact Area', 'muext-engagement' ),
		'all_items'                  => __( 'All Impact Areas', 'muext-engagement' ),
		'parent_item'                => __( 'Parent Impact Area', 'muext-engagement' ),
		'parent_item_colon'          => __( 'Parent Impact Area:', 'muext-engagement' ),
		'new_item_name'              => __( 'New Impact Area Name', 'muext-engagement' ),
		'add_new_item'               => __( 'Add New Impact Area', 'muext-engagement' ),
		'edit_item'                  => __( 'Edit Impact Area', 'muext-engagement' ),
		'update_item'                => __( 'Update Impact Area', 'muext-engagement' ),
		'view_item'                  => __( 'View Impact Area', 'muext-engagement' ),
		'separate_items_with_commas' => __( 'Separate impact areas with commas', 'muext-engagement' ),
		'add_or_remove_items'        => __( 'Add or remove impact areas', 'muext-engagement' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'muext-engagement' ),
		'popular_items'              => __( 'Popular Impact Areas', 'muext-engagement' ),
		'search_items'               => __( 'Search Impact Areas', 'muext-engagement' ),
		'not_found'                  => __( 'Not Found', 'muext-engagement' ),
		'no_terms'                   => __( 'No impact areas', 'muext-engagement' ),
		'items_list'                 => __( 'Items Impact Areas', 'muext-engagement' ),
		'items_list_navigation'      => __( 'Impact area list navigation', 'muext-engagement' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => false,
		'rewrite'                    => array( 'slug' => 'impact-area' ),
	);
	register_taxonomy( 'muext_program_impact_area', array( 'muext_engagement' ), $args );

}

function register_program_outreach_type() {

	$labels = array(
		'name'                       => _x( 'Outreach Type', 'Taxonomy General Name', 'muext-engagement' ),
		'singular_name'              => _x( 'Outreach Type', 'Taxonomy Singular Name', 'muext-engagement' ),
		'menu_name'                  => __( 'Outreach Type', 'muext-engagement' ),
		'all_items'                  => __( 'All Outreach Types', 'muext-engagement' ),
		'parent_item'                => __( 'Parent Outreach Type', 'muext-engagement' ),
		'parent_item_colon'          => __( 'Parent Outreach Type:', 'muext-engagement' ),
		'new_item_name'              => __( 'New Outreach Type Name', 'muext-engagement' ),
		'add_new_item'               => __( 'Add New Outreach Type', 'muext-engagement' ),
		'edit_item'                  => __( 'Edit Outreach Type', 'muext-engagement' ),
		'update_item'                => __( 'Update Outreach Type', 'muext-engagement' ),
		'view_item'                  => __( 'View Outreach Type', 'muext-engagement' ),
		'separate_items_with_commas' => __( 'Separate outreach types with commas', 'muext-engagement' ),
		'add_or_remove_items'        => __( 'Add or remove outreach types', 'muext-engagement' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'muext-engagement' ),
		'popular_items'              => __( 'Popular Outreach Types', 'muext-engagement' ),
		'search_items'               => __( 'Search Outreach Types', 'muext-engagement' ),
		'not_found'                  => __( 'Not Found', 'muext-engagement' ),
		'no_terms'                   => __( 'No outreach types', 'muext-engagement' ),
		'items_list'                 => __( 'Outreach Types', 'muext-engagement' ),
		'items_list_navigation'      => __( 'Outreach type list navigation', 'muext-engagement' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
		'rewrite'                    => array( 'slug' => 'outreach-type' ),
	);
	register_taxonomy( 'muext_program_outreach_type', array( 'muext_engagement' ), $args );

}

function register_program_affiliation() {

	$labels = array(
		'name'                       => _x( 'Affiliation', 'Taxonomy General Name', 'muext-engagement' ),
		'singular_name'              => _x( 'Affiliation', 'Taxonomy Singular Name', 'muext-engagement' ),
		'menu_name'                  => __( 'Affiliation', 'muext-engagement' ),
		'all_items'                  => __( 'All Affiliations', 'muext-engagement' ),
		'parent_item'                => __( 'Parent Affiliation', 'muext-engagement' ),
		'parent_item_colon'          => __( 'Parent Affiliation:', 'muext-engagement' ),
		'new_item_name'              => __( 'New Affiliation Name', 'muext-engagement' ),
		'add_new_item'               => __( 'Add New Affiliation', 'muext-engagement' ),
		'edit_item'                  => __( 'Edit Affiliation', 'muext-engagement' ),
		'update_item'                => __( 'Update Affiliation', 'muext-engagement' ),
		'view_item'                  => __( 'View Affiliation', 'muext-engagement' ),
		'separate_items_with_commas' => __( 'Separate affiliations with commas', 'muext-engagement' ),
		'add_or_remove_items'        => __( 'Add or remove affiliations', 'muext-engagement' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'muext-engagement' ),
		'popular_items'              => __( 'Popular Affiliations', 'muext-engagement' ),
		'search_items'               => __( 'Search Affiliations', 'muext-engagement' ),
		'not_found'                  => __( 'Not Found', 'muext-engagement' ),
		'no_terms'                   => __( 'No affiliations', 'muext-engagement' ),
		'items_list'                 => __( 'Affiliations', 'muext-engagement' ),
		'items_list_navigation'      => __( 'Affiliation list navigation', 'muext-engagement' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => true,
		'rewrite'                    => array( 'slug' => 'affilation' ),
	);
	register_taxonomy( 'muext_program_affiliation', array( 'muext_engagement' ), $args );

}
function register_program_funding_source() {

	$labels = array(
		'name'                       => _x( 'Funding Source', 'Taxonomy General Name', 'muext-engagement' ),
		'singular_name'              => _x( 'Funding Source', 'Taxonomy Singular Name', 'muext-engagement' ),
		'menu_name'                  => __( 'Funding Source', 'muext-engagement' ),
		'all_items'                  => __( 'All Funding Sources', 'muext-engagement' ),
		'parent_item'                => __( 'Parent Funding Source', 'muext-engagement' ),
		'parent_item_colon'          => __( 'Parent Funding Source:', 'muext-engagement' ),
		'new_item_name'              => __( 'New Funding Source Name', 'muext-engagement' ),
		'add_new_item'               => __( 'Add New Funding Source', 'muext-engagement' ),
		'edit_item'                  => __( 'Edit Funding Source', 'muext-engagement' ),
		'update_item'                => __( 'Update Funding Source', 'muext-engagement' ),
		'view_item'                  => __( 'View Funding Source', 'muext-engagement' ),
		'separate_items_with_commas' => __( 'Separate Funding Sources with commas', 'muext-engagement' ),
		'add_or_remove_items'        => __( 'Add or remove Funding Sources', 'muext-engagement' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'muext-engagement' ),
		'popular_items'              => __( 'Popular Funding Sources', 'muext-engagement' ),
		'search_items'               => __( 'Search Funding Sources', 'muext-engagement' ),
		'not_found'                  => __( 'Not Found', 'muext-engagement' ),
		'no_terms'                   => __( 'No affiliations', 'muext-engagement' ),
		'items_list'                 => __( 'Funding Sources', 'muext-engagement' ),
		'items_list_navigation'      => __( 'Funding Source list navigation', 'muext-engagement' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => true,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => true,
		'show_in_rest'               => false,
		'rewrite'                    => array( 'slug' => 'funding_cource' ),
	);
	register_taxonomy( 'muext_program_funding', array( 'muext_engagement' ), $args );

}

// geoids for engagements stored as taxonomy b/c performance
function register_muext_geoid() {

	$labels = array(
		'name'                       => _x( 'GEOID', 'Taxonomy General Name', 'muext-engagement' ),
		'singular_name'              => _x( 'GEOID', 'Taxonomy Singular Name', 'muext-engagement' ),
		'menu_name'                  => __( 'GEOID', 'muext-engagement' ),
		'all_items'                  => __( 'All GEOIDs', 'muext-engagement' ),
		'parent_item'                => __( 'Parent GEOID', 'muext-engagement' ),
		'parent_item_colon'          => __( 'Parent GEOID', 'muext-engagement' ),
		'new_item_name'              => __( 'New GEOID Name', 'muext-engagement' ),
		'add_new_item'               => __( 'Add New GEOID', 'muext-engagement' ),
		'edit_item'                  => __( 'Edit GEOID', 'muext-engagement' ),
		'update_item'                => __( 'Update GEOID', 'muext-engagement' ),
		'view_item'                  => __( 'View GEOID', 'muext-engagement' ),
		'separate_items_with_commas' => __( 'Separate GEOIDs with commas', 'muext-engagement' ),
		'add_or_remove_items'        => __( 'Add or remove GEOIDs', 'muext-engagement' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'muext-engagement' ),
		'popular_items'              => __( 'Popular GEOIDs', 'muext-engagement' ),
		'search_items'               => __( 'Search GEOIDes', 'muext-engagement' ),
		'not_found'                  => __( 'Not Found', 'muext-engagement' ),
		'no_terms'                   => __( 'No affiliations', 'muext-engagement' ),
		'items_list'                 => __( 'GEOIDs', 'muext-engagement' ),
		'items_list_navigation'      => __( 'GEOID list navigation', 'muext-engagement' ),
	);
	$args = array(
		'labels'                     => $labels,
		'hierarchical'               => false,
		'public'                     => true,
		'show_ui'                    => true,
		'show_admin_column'          => true,
		'show_in_nav_menus'          => true,
		'show_tagcloud'              => false,
		'show_in_rest'               => true,
		'rewrite'                    => array( 'slug' => 'geoid' ),
	);
	register_taxonomy( 'muext_geoid', array( 'muext_engagement' ), $args );

}

/**
 * Change the REST API response so that it includes important meta for engagement items.
 *
 * @since    1.0.0
 *
 * @return   void
 */
function rest_read_meta() {
	register_rest_field( 'muext_engagement',
		'eng_theme', // Note that these ids do not match the tax name exactly-to avoid collisions in the REST response.
		array(
			'get_callback'    => __NAMESPACE__ . '\\rest_get_engagement_taxonomy_info',
			'update_callback' => null,
			'schema'          => null,
		)
	);
	register_rest_field( 'muext_engagement',
		'eng_type',
		array(
			'get_callback'    => __NAMESPACE__ . '\\rest_get_engagement_taxonomy_info',
			'update_callback' => null,
			'schema'          => null,
		)
	);
	register_rest_field( 'muext_engagement',
		'eng_affiliation',
		array(
			'get_callback'    => __NAMESPACE__ . '\\rest_get_engagement_taxonomy_info',
			'update_callback' => null,
			'schema'          => null,
		)
	);
	register_rest_field( 'muext_engagement',
		'eng_featured_image',
		array(
			'get_callback'    => __NAMESPACE__ . '\\rest_get_engagement_featured_image',
			'update_callback' => null,
			'schema'          => null,
		)
	);
	/*
	register_rest_field( 'muext_engagement',
		'eng_post_meta_fields',
		array(
			'get_callback'    => __NAMESPACE__ . '\\rest_get_engagement_post_meta',
			'update_callback' => null,
			'schema'          => null,
		)
	);*/
}

/**
 * Include taxonomy term info in the response.
 *
 * @param array $object Details of current post.
 * @param string $field_name Name of field.
 * @param WP_REST_Request $request Current request
 *
 * @return array
 */
function rest_get_engagement_taxonomy_info( $object, $field_name, $request ) {
	$include_top_level_terms = false;
	$ignore_terms = array();
	switch ( $field_name ) {
		case 'eng_theme':
			$tax_name = 'muext_program_category';
			$include_top_level_terms = true;
			// We want to ignore the advancement theme and its children
			$parent = get_term_by( 'slug', 'advancement', 'muext_program_category' );
			$ignore_terms = get_terms( array( 
				'taxonomy'   => 'muext_program_category',
				'child_of'   => $parent->term_id,
				'fields'     => 'ids',
				'hide_empty' => false
			) );
			$ignore_terms[] = $parent->term_id;
			break;
		case 'eng_type' :
			$tax_name = 'muext_program_outreach_type';
			$include_top_level_terms = true;
			break;
		case 'eng_affiliation' :
			$tax_name = 'muext_program_affiliation';
			$include_top_level_terms = true;
			break;
		default:
			return null;
			break;
	}

	$taxonomy_terms = get_the_terms( $object[ 'id' ], $tax_name );
	$associated_term_ids = $raw = $rendered = array();

	if ( $taxonomy_terms ) {
		foreach ( $taxonomy_terms as $term ) {
			// Skip terms that we're ignoring.
			if ( in_array( $term->term_id, $ignore_terms, true ) ) {
				continue;
			}
			$raw[] = array(
				'term_id'   => $term->term_id,
				'name'      => $term->name,
				'slug'      => $term->slug,
				'taxonomy'  => $term->taxonomy,
				'parent'    => $term->parent,
				'count'     => $term->count,
				'term_link' => get_term_link( $term, $tax_name )
			);
			$rendered[] = $term->name;
			$associated_term_ids[] = $term->term_id;
		}
	}

	$associated = array(
		'raw'      => $raw,
		'rendered' => esc_html( implode( ', ', $rendered ) ),
	);

	if ( $include_top_level_terms ) {
		// Find the top-level terms.
		$raw = $rendered = array();
		foreach ( $associated_term_ids as $term_id ) {
			$term = get_top_level_parent_term( $term_id, $tax_name );
			// Set keys to avoid adding duplicates. We'll drop them later.
			$raw[$term->term_id] = array(
				'term_id'   => $term->term_id,
				'name'      => $term->name,
				'slug'      => $term->slug,
				'taxonomy'  => $term->taxonomy,
				'parent'    => $term->parent,
				'count'     => $term->count,
				'term_link' => get_term_link( $term, $tax_name )
			);
			$rendered[$term->term_id] = $term->name;
		}

		$top_level = array(
			'raw' => array_values( $raw ),
			'rendered' => esc_html( implode( ', ', array_values( $rendered ) ) ),
		);

		return array(
			'associated' => $associated,
			'top-level'  => $top_level,
		);
	} else {
		return $associated;
	}
}

/**
 * Add the featured image url to the response.
 *
 * @param array $object Details of current post.
 * @param string $field_name Name of field.
 * @param WP_REST_Request $request Current request
 *
 * @return string URL of image.
 */
function rest_get_engagement_featured_image( $object, $field_name, $request ) {
	$featured_image_id = get_post_thumbnail_id( $object[ 'id' ] );
	// @Todo: specify a different size of thumbnail if needed.
	//return wp_get_attachment_url( $featured_image_id, 'medium' );
	error_log( get_the_post_thumbnail_url( $object[ 'id' ], 'medium' ) );
	return get_the_post_thumbnail_url( $object[ 'id' ], 'medium' );
}

/** 
 * Register custom REST API routes.
 *
 * @since 1.0.0 
 */
function add_custom_rest_routes() {
    //Path to meta query route
    register_rest_route( 'wp/v2', '/engagement-region-other/', array(
            'methods' => 'GET', 
            'callback' => __NAMESPACE__ . '\\engagement_region_other_query' 
    ) );
    //Path to meta query route: post content and meta
    register_rest_route( 'wp/v2', '/engagement-contentmeta/(?P<post_id>\d+)', array(
            'methods' => 'GET', 
            'callback' => __NAMESPACE__ . '\\rest_get_engagement_post_content_meta',
			'args' => array(
				'id' => array(
					'validate_callback' => function($param, $request, $key) {
						return is_numeric( $param );
					}
				),
			),
    ) );
}

/** 
 * Handle custom REST API route requests for engagements with
 * region of "other."
 *
 * @since 1.0.0
 *
 * @return array
 */
function engagement_region_other_query() {
    $data = array();

    // The "other" region data is currently stored in serialized post meta.
	$args = array(
		'meta_query' => array(
			'relation' => 'AND', // Must satisfy all requirements
			array( 
				'key'     => '_muext_location_group',
				'value'   => '_muext_region";s:5:"other',
				'compare' => 'LIKE',
			),
			array( 
				'key'     => '_muext_longitude',
				'compare' => 'EXISTS',
			),
			array( 
				'key'     => '_muext_latitude',
				'compare' => 'EXISTS',
			),
		),
		'post_type'    => 'muext_engagement',
		// Disable pagination--we think there will be not too many of these.
		'posts_per_page' => -1,
	);
	$meta_query = new \WP_Query( $args );

	if ( $meta_query->have_posts() ) {
		while ( $meta_query->have_posts() ) {
			$meta_query->the_post();
			$post = get_post();
			$data[] = array(
				'ID'          => $post->ID,
	            'post_author' => $post->post_author,
	            'post_date'   => $post->post_date,
	            'post_title'  => $post->post_title,
	            'post_link'   => get_permalink(),
	            'longitude'   => get_post_meta( $post->ID, '_muext_longitude', true ),
	            'latitude'    => get_post_meta( $post->ID, '_muext_latitude', true ),
			);
		}
	}

	return $data;
}


/**
 * Handle single post request (meta, content)
 *
 * @param array $object Details of current post.
 * @param string $field_name Name of field.
 * @param WP_REST_Request $request Current request
 *
 * @return string URL of image.
 */
function rest_get_engagement_post_content_meta( $request ) {

	$post_id = $request['post_id'];
	$post_object = \get_post( $post_id );

	$data['postmeta'] = \get_post_meta( $post_id );
	
	$data['content'] = $post_object->post_content;

	return $data;

}

/**
 * Filter out engagements that only have a theme of "advancement" (and its children).
 *
 * @param array           $args    Key value array of query var to query value.
 * @param WP_REST_Request $request The request used.
 */
function disallow_advancement_themed_eng( $args, $request ) {
	/* 
	 * If no themes have been specified as part of the request, 
	 * we limit results to non-advancement themed engagements,
	 * by limiting found engagments to acceptably themed engagements.
	 */
	if ( empty( $_GET['muext_program_category'] ) && empty( $_GET['muext_program_category_exclude'] ) ) {
		$parent = get_term_by( 'slug', 'advancement', 'muext_program_category' );
		$non_advancement_themes = get_terms( array( 
			'taxonomy' => 'muext_program_category',
			'exclude_tree'   => $parent->term_id,
			'fields'  => 'ids',
			'hide_empty' => false
		) );
		if ( $non_advancement_themes ) {
			$args['tax_query'][] = array(
				'taxonomy'         => 'muext_program_category',
				'field'            => 'term_id',
				'terms'            => $non_advancement_themes,
			);
		}
	}

	return $args;
}

 /**
  * Add custom pre-processing to REST API queries, like custom handling of geoid parameters.
  *
  * @since 1.0.7 
  *
  **/
function rest_api_filter_add_filters() {
	// Add custom handling for geoID requests. We want this to run after the filter parameter is handled (at priority 10).
	// add_filter( 'rest_muext_engagement_query', __NAMESPACE__ . '\\filter_geoids', 30, 2 );
}

/**
 * Filter the incoming 'muext_geoid' parameter.
 * Essentially, if the user chooses "Columbia," we want to include all engagements
 * that apply to Columbia, like ZIP 65201 and Boone County and the schools district.
 * Individual engagements are tagged with their service area, not every possible geoid
 * that could apply.
 *
 * @since 1.0.7 
 *
 * @param  array           $args    The query arguments.
 * @param  WP_REST_Request $request Full details about the request.
 * @return array $args.
 **/
function filter_geoids( $args, $request ) {
	// Bail out if no geoid parameter is set.
	if ( empty( $args['muext_geoid'] ) ) {
		return $args;
	}

	if ( is_array( $args['muext_geoid'] ) ) {
		$args['muext_geoid'] = implode( ',', $args['muext_geoid'] );
	}

	$api_url = add_query_arg( array(
		'geoid' => trim( $args['muext_geoid'] ),
	), 'https://services.engagementnetwork.org/api-extension/v1/eci-geoid-list' );

	$response = wp_remote_get( $api_url );

	// Only filter the args if we've received a successful response.
	if ( 200 == wp_remote_retrieve_response_code( $response ) ) {
		$args['muext_geoid'] = json_decode( wp_remote_retrieve_body( $response ) );
	}

	return $args;
}

// Helper functions
/** 
 * Find the top-level parent of a term.
 *
 * @since 1.0.0
 *
 * @return WP_Term object
 */
function get_top_level_parent_term( $term_id, $taxonomy ) {
    $term = get_term_by( 'id', $term_id, $taxonomy );
    while ( $term->parent != 0 ){
        $term = get_term_by( 'id', $term->parent, $taxonomy);
    }
    return $term;
}
