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
		'supports'              => array( 'title', 'thumbnail', 'revisions', ),
		'taxonomies'            => array(
										'muext_program_category',
										'muext_program_tag',
										'muext_program_audience',
										'muext_program_impact_area'
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
		'show_in_rest'          => false,
	);
	register_post_type( 'muext_engagement', $args );
}

// Register Custom Taxonomy
function register_program_categories() {
	$labels = array(
		'name'                       => _x( 'Program Categories', 'Taxonomy General Name', 'muext-engagement' ),
		'singular_name'              => _x( 'Program Category', 'Taxonomy Singular Name', 'muext-engagement' ),
		'menu_name'                  => __( 'Program Category', 'muext-engagement' ),
		'all_items'                  => __( 'All Categories', 'muext-engagement' ),
		'parent_item'                => __( 'Parent Category', 'muext-engagement' ),
		'parent_item_colon'          => __( 'Parent Category:', 'muext-engagement' ),
		'new_item_name'              => __( 'New Category Name', 'muext-engagement' ),
		'add_new_item'               => __( 'Add Category Item', 'muext-engagement' ),
		'edit_item'                  => __( 'Edit Category', 'muext-engagement' ),
		'update_item'                => __( 'Update Category', 'muext-engagement' ),
		'view_item'                  => __( 'View Category', 'muext-engagement' ),
		'separate_items_with_commas' => __( 'Separate categories with commas', 'muext-engagement' ),
		'add_or_remove_items'        => __( 'Add or remove categories', 'muext-engagement' ),
		'choose_from_most_used'      => __( 'Choose from the most used', 'muext-engagement' ),
		'popular_items'              => __( 'Popular Categories', 'muext-engagement' ),
		'search_items'               => __( 'Search Categories', 'muext-engagement' ),
		'not_found'                  => __( 'Not Found', 'muext-engagement' ),
		'no_terms'                   => __( 'No categories', 'muext-engagement' ),
		'items_list'                 => __( 'Categories list', 'muext-engagement' ),
		'items_list_navigation'      => __( 'Categories list navigation', 'muext-engagement' ),
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
