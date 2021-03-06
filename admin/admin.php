<?php

namespace MU_Ext_Engagement\Admin;

/*
 * Only network admins should be able to configure this plugin
 */
// if ( ! is_super_admin() ) {
// 	return;
// }

// Load admin style sheet and JavaScript.
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\\enqueue_admin_scripts_and_styles' );
// Add async and defer
add_filter( 'script_loader_tag', __NAMESPACE__ . '\\add_async_attribute', 10, 2 );

// Add the single-site options page and menu item.
add_action( 'admin_menu', __NAMESPACE__ . '\\add_plugin_admin_menu' );
// Add settings to the single-site admin page.
add_action( 'admin_menu', __NAMESPACE__ . '\\settings_init' );

// Add an action link labeled "Settings" pointing to the options page from the plugin listing.
$plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . 'loader.php' );
add_filter( 'plugin_action_links_' . $plugin_basename, __NAMESPACE__ . '\\add_action_links' );

// Add metaboxes
// Program information (admin and front end)
//add_action( 'cmb2_admin_init', __NAMESPACE__ . '\\muext_program_info_meta_box' );
add_action( 'cmb2_init', __NAMESPACE__ . '\\muext_program_info_meta_box' ); //If you use cmb2_admin_init, like in the example_functions.php file, to register your metaboxes, they will not be available on the front end. Use cmb2_init instead.
add_action( 'cmb2_after_init', __NAMESPACE__ . '\\muext_handle_frontend_new_post_form_submission' );

// Program outcomes
add_action( 'cmb2_admin_init', __NAMESPACE__ . '\\muext_program_outcomes_meta_box' );

// Save taxonomy when saving post
add_action( 'save_post_muext_engagement', __NAMESPACE__ . '\\muext_save_taxonomy_select2_boxes' );
add_action( 'save_post_muext_engagement', __NAMESPACE__ . '\\muext_update_geoid_taxonomy' );

// Add import tool for importing/syncing extension programs
add_action( 'admin_menu', __NAMESPACE__ . '\\add_tools_page' );
// AJAX to check type of uploaded import file.
add_action( 'wp_ajax_engagement_importer_check_file', __NAMESPACE__ . '\\check_import_file' );
// AJAX to run the importer. 
add_action( 'wp_ajax_engagement_run_importer', __NAMESPACE__ . '\\import_data' );

/**
* Register and enqueue admin-specific style sheets and javascript files.
*
* @since     1.0.0
*
* @param string $hook_suffix The current admin page.
*/
function enqueue_admin_scripts_and_styles( $hook_suffix ) {
	$screen = get_current_screen();
	
	//var_dump( $screen );

	// Enqueue items for single engagement edit screen.
	if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
		if ( ! empty( $screen->post_type ) && 'muext_engagement' == $screen->post_type ) {
			wp_enqueue_script( \MU_Ext_Engagement\get_plugin_slug() . '-admin-edit-script', plugins_url( 'assets/js/admin-edit.js', __FILE__ ), array( 'jquery' ), \MU_Ext_Engagement\get_plugin_version() );

			wp_localize_script( 
				\MU_Ext_Engagement\get_plugin_slug() . '-admin-edit-script', 
				'muext_admin_restapi', 
				array(
					'rest_url' => esc_url_raw( rest_url() ),
					'rest_nonce' => wp_create_nonce( 'wp_rest' )
				) 
			);
	
			// Localize the script with new data
			$api_key = get_option( 'muext-google-location-apikey' );
			wp_localize_script( \MU_Ext_Engagement\get_plugin_slug() . '-admin-edit-script', 'muext_js_data', array( 'google_api_key' => $api_key ) );

			wp_enqueue_script( 'google_places_api', "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places&callback=initAutocomplete", array( \MU_Ext_Engagement\get_plugin_slug() . '-admin-edit-script' ), \MU_Ext_Engagement\get_plugin_version(), true );
		}
	}

    // Enqueue items for settings screen.
	if ( ( ! empty( $screen->id ) && 'muext_engagement_page_muext-engagement' == $screen->id ) || is_admin() ) {
		wp_enqueue_style( \MU_Ext_Engagement\get_plugin_slug() .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), \MU_Ext_Engagement\get_plugin_version() );
		// wp_enqueue_script( \MU_Ext_Engagement\get_plugin_slug() . 'settings-admin-script', plugins_url( 'assets/js/admin-settings.js', __FILE__ ), array( 'jquery' ), \MU_Ext_Engagement\get_plugin_version() );
	}
}

/**
* The Google maps API needs to be loaded async defer.
*
* @since     1.0.0
*
* @param string $hook_suffix The current admin page.
*/
function add_async_attribute( $tag, $handle ) {
	if ( 'google_places_api' === $handle ) {
		$tag = str_replace( ' src', ' async="async" defer="defer" src', $tag );
	}
	return $tag;
}

/**
* Register the administration menu for this plugin into the WordPress Dashboard menu.
*
* @since    1.0.0
*/
function add_plugin_admin_menu() {
	add_submenu_page(
		'edit.php?post_type=muext_engagement',
		__( 'Engagement Settings', 'muext-engagement' ),
		__( 'Settings', 'muext-engagement' ),
		'manage_options',
		\MU_Ext_Engagement\get_plugin_slug() . '-settings',
		__NAMESPACE__ . '\\display_plugin_admin_page'
	);
}

/**
* Render the settings page for this plugin.
*
* @since    1.0.0
*/
function display_plugin_admin_page() {
// Note that update/get/delete_site_option sets site option _or_ network options if multisite.
// Note that update/get/delete_option sets option for current site.
?>
<div class="wrap">
	<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>

	<form action="<?php echo admin_url( 'options.php' ) ?>" method='post'>
		<?php
		settings_fields( \MU_Ext_Engagement\get_plugin_slug() . '-settings' );
		do_settings_sections( \MU_Ext_Engagement\get_plugin_slug() . '-settings' );
		submit_button();
		?>

	</form>

</div>
<?php
}

function settings_init() {
	// General
	add_settings_section(
		\MU_Ext_Engagement\get_plugin_slug(),
		__( 'General', 'bp-docs' ),
		__NAMESPACE__ . '\\settings_section',
		\MU_Ext_Engagement\get_plugin_slug() . '-settings'
	);

	// General - Docs slug
	add_settings_field(
		'muext-google-location-apikey',
		__( 'Google Location API Key', 'muext-engagement'  ),
		__NAMESPACE__ . '\\google_api_key_setting_markup',
		\MU_Ext_Engagement\get_plugin_slug() . '-settings',
		\MU_Ext_Engagement\get_plugin_slug()
	);
	register_setting( \MU_Ext_Engagement\get_plugin_slug() . '-settings', 'muext-google-location-apikey', 'sanitize_text_field' );
}

function settings_section() {
	// Nothing needed here
}

function google_api_key_setting_markup() {
	$apikey = get_option( 'muext-google-location-apikey' );
	?>
	<label for="muext-google-location-apikey" class="screen-reader-text"><?php _e( "Enter the Google Locations API key for this site.", 'muext-engagement' ) ?></label>
	<input name="muext-google-location-apikey" id="muext-google-location-apikey" type="text" value="<?php echo $apikey; ?>" size=60/>
	<p class="description"><?php _e( "Enter the Google Locations API key for this site.", 'muext-engagement' ) ?></p>

	<?php
}

/**
* Add settings action link to the plugins page.
*
* @since    1.0.0
*/
function add_action_links( $links ) {

	return array_merge(
		array(
			'settings' => '<a href="' . admin_url( 'edit.php?post_type=muext_engagement&page=muext-engagement-settings' ) . '">' . __( 'Settings', 'muext-engagement' ) . '</a>'
		),
		$links
	);

}

/**
 * Define the program information metabox and field configurations.
 */
function muext_program_info_meta_box() {

	// Start with an underscore to hide fields from custom fields list
	$prefix = '_muext_';

	/**
	 * Initiate the metabox
	 */
	$cmb = new_cmb2_box( array(
		'id'            => 'program_information',
		'title'         => __( 'Program Information', 'muext-engagement' ),
		'object_types'  => array( 'muext_engagement' ), // Post type
		'context'       => 'normal',
		'priority'      => 'high',
		'show_names'    => true, // Show field names on the left
		// 'cmb_styles' => false, // false to disable the CMB stylesheet
		// 'closed'     => true, // Keep the metabox closed by default
		//'show_on_cb' => 'muext_show_meta_to_chosen_roles',
	) );

	
	// Id's for group's fields only need to be unique for the group. Prefix is not needed.
	$cmb->add_field( array(
		'name' => 'Engagement Title *',
		'id'   => $prefix . 'title',
		'type' => 'text',
		'attributes'  => array(
			'placeholder' => 'Name your Engagement',
		),
		'render_row_cb' => __NAMESPACE__ . '\\muext_render_row_cb',
		// 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
	) );
		
	
	/** WHO ***/
	$contact_group_field_id = $cmb->add_field( array(
		'id'          => '_muext_contact_group',
		'type'        => 'group',
		'description' => __( 'WHO', 'muext-engagement' ),
		// 'repeatable'  => false, // use false if you want non-repeatable group
		'options'     => array(
			'group_title'   => __( 'Contact {#}', 'muext-engagement' ), // since version 1.1.4, {#} gets replaced by row number
			'add_button'    => __( '<span class="fa fa-plus"></span>&nbsp;&nbsp;Add Contact', 'muext-engagement' ),
			'remove_button' => __( '<span class="fa fa-trash"></span>&nbsp;&nbsp;Remove/Clear', 'muext-engagement' ),
			'sortable'      => true, // beta
			// 'closed'     => true, // true to have the groups closed by default
		),
		'classes' => 'contact-class',
	) );

	// Id's for group's fields only need to be unique for the group. Prefix is not needed.
	$cmb->add_group_field( $contact_group_field_id, array(
		'name' => 'Contact Name',
		'id'   => $prefix . 'contact_name',
		'type' => 'text',
		//'render_row_cb' => __NAMESPACE__ . '\\muext_render_row_cb',
		// 'repeatable' => true, // Repeatable fields are supported w/in repeatable groups (for most types)
	) );

	$cmb->add_group_field( $contact_group_field_id, array(
		'name' => 'Email',
		//'description' => 'Write a short description for this entry',
		'id'   => $prefix . 'contact_email',
		'type' => 'text_email',
		'classes' => 'inline-desktop',
	) );

	$cmb->add_group_field( $contact_group_field_id, array(
		'name' => 'Phone',
		'id'   => $prefix . 'contact_phone',
		'type' => 'text',
		'sanitization_cb' => __NAMESPACE__ . '\\telephone_number_sanitization', // custom sanitization callback parameter
		'classes' => 'inline-desktop',
	) );
	
	// Regular text field
	$cmb->add_field( array(
		'name'       => __( 'Contact Person Name - OLD', 'muext-engagement' ),
		// 'desc'       => __( 'field description (optional)', 'muext-engagement' ),
		'id'         => $prefix . 'contact_name',
		'type'       => 'text',
		'on_front'	 => false,
	) );

	// Email text field
	$cmb->add_field( array(
		'name' => __( 'Contact Person Email - OLD', 'muext-engagement' ),
		// 'desc' => __( 'field description (optional)', 'muext-engagement' ),
		'id'   => $prefix . 'contact_email',
		'type' => 'text_email',
		'on_front'	 => false,
	) );

	// Regular text field
	$cmb->add_field( array(
		'name'       => __( 'Contact Person Phone - OLD', 'muext-engagement' ),
		// 'desc'       => __( 'field description (optional)', 'muext-engagement' ),
		'id'         => $prefix . 'contact_phone',
		'type'       => 'text',
		'on_front'	 => false,
		'sanitization_cb' => __NAMESPACE__ . '\\telephone_number_sanitization', // custom sanitization callback parameter
	) );
	
	/** WHERE **/
	$location_group_field_id = $cmb->add_field( array(
		'id'          => '_muext_location_group',
		'type'        => 'group',
		'description' => __( 'WHERE', 'muext-engagement' ),
		// 'repeatable'  => false, // use false if you want non-repeatable group
		'options'     => array(
			'group_title'   => __( 'Location {#}', 'muext-engagement' ), // since version 1.1.4, {#} gets replaced by row number
			'add_button'    => __( '<span class="fa fa-plus"></span>&nbsp;&nbsp;Add Location', 'muext-engagement' ),
			'remove_button' => __( '<span class="fa fa-trash"></span>&nbsp;&nbsp;Remove Location', 'muext-engagement' ),
			'sortable'      => true, // beta
			// 'closed'     => true, // true to have the groups closed by default
		),
		'classes' => 'location-class',
	) );
	
	// Regular text field
	$cmb->add_group_field( $location_group_field_id, array(
	//$cmb->add_field( array(
		'name'       => __( 'Location *', 'muext-engagement' ),
		'desc'       => __( 'Enter the address where the engagement takes place (physical location).', 'muext-engagement' ),
		'id'         => $prefix . 'location_text',
		'type'       => 'text',
		//'repeatable'  => false
		'classes' => 'muext_loc_text',
	) );
	
	$cmb->add_group_field( $location_group_field_id, array(
		'name'             => __( 'Geographic Region', 'muext-engagement' ),
		'desc'             => 'Select the type of region served by the engagement. <em>Note: for an engagement that serves multiple regions, 
		please use the Add Location button to enter additional information</em>',
		'id'               => $prefix . 'region',
		'type'             => 'select',
		'show_option_none' => false,
		'classes'          => 'muext_region_class',
		'default'          => 'city_town',
		'options'          => array(
			'city_town' 		=> __( 'City or Town', 'muext-engagement' ),
			'county'  			=> __( 'County', 'muext-engagement' ),
			'school_district'   => __( 'School District', 'muext-engagement' ),
			'zipcode'   		=> __( 'ZIP Code', 'muext-engagement' ),
			'state'   			=> __( 'State', 'muext-engagement' ),
			'national'   		=> __( 'National', 'muext-engagement' ),
			'other'   			=> __( 'Other', 'muext-engagement' ),
		),
	) );
	
	$cmb->add_group_field( $location_group_field_id, array(
	//$cmb->add_field( array(
		'name'       => __( 'Region - other', 'muext-engagement' ),
		'desc'       => __( 'Describe the Region served.', 'muext-engagement' ),
		'id'         => $prefix . 'region_other',
		'type'       => 'text',
		'classes'	 => 'hidden',
		//'repeatable'  => false
	) );
	
	// assigned in js on form submit
	$cmb->add_group_field( $location_group_field_id, array(
		'name'       => __( 'geo_key', 'muext-engagement' ),
		'id'         => $prefix . 'geo_key',
		'type'       => 'hidden',
	) );
	
	// populated in js (public or admin.js) on js form submit, temp to generate taxonomy on php submit
	$cmb->add_group_field( $location_group_field_id, array(
		'name'       => __( 'geo_id', 'muext-engagement' ),
		'id'         => $prefix . 'geo_id',
		'type'       => 'hidden',
	) );
	
	// Regular text field
	$cmb->add_field(  array(
	//$cmb->add_field( array(
		'name'       => __( 'Location - OLD', 'muext-engagement' ),
		'desc'       => __( 'Enter an address or the city and state.', 'muext-engagement' ),
		'id'         => $prefix . 'location_text',
		'type'       => 'text',
		'on_front'	 => false,
	) );

	// Location details
	// Return values from the API
	// Street number => street_number
	// Street name => route
	// City => locality
	// County => administrative_area_level_2
	// State => administrative_area_level_1
	// Country => country
	// ZIP code => postal_code
	$cmb->add_field( array(
		'name'       => __( 'Street Number', 'muext-engagement' ),
		'id'         => $prefix . 'street_number',
		'type'       => 'hidden',
	) );
	$cmb->add_field( array(
		'name'       => __( 'Street Name', 'muext-engagement' ),
		'id'         => $prefix . 'route',
		'type'       => 'hidden',
	) );
	$cmb->add_field( array(
		'name'       => __( 'City', 'muext-engagement' ),
		'id'         => $prefix . 'locality',
		'type'       => 'hidden',
	) );
	$cmb->add_field( array(
		'name'       => __( 'County', 'muext-engagement' ),
		'id'         => $prefix . 'administrative_area_level_2',
		'type'       => 'hidden',
	) );
	$cmb->add_field( array(
		'name'       => __( 'State', 'muext-engagement' ),
		'id'         => $prefix . 'administrative_area_level_1',
		'type'       => 'hidden',
	) );
	$cmb->add_field( array(
		'name'       => __( 'Country', 'muext-engagement' ),
		'id'         => $prefix . 'country',
		'type'       => 'hidden',
	) );
	$cmb->add_field( array(
		'name'       => __( 'ZIP code', 'muext-engagement' ),
		'id'         => $prefix . 'postal_code',
		'type'       => 'hidden',
	) );
	$cmb->add_field( array(
		'name'       => __( 'Longitude', 'muext-engagement' ),
		'id'         => $prefix . 'longitude',
		'type'       => 'hidden',
	) );
	$cmb->add_field( array(
		'name'       => __( 'Latitude', 'muext-engagement' ),
		'id'         => $prefix . 'latitude',
		'type'       => 'hidden',
	) );
	
	
	// Location details
	// Return values from the API
	// Street number => street_number
	// Street name => route
	// City => locality
	// County => administrative_area_level_2
	// State => administrative_area_level_1
	// Country => country
	// ZIP code => postal_code
	
	$cmb->add_group_field( $location_group_field_id, array(
		'name'       => __( 'Street Number', 'muext-engagement' ),
		'id'         => $prefix . 'street_number',
		'type'       => 'hidden',
	) );
	$cmb->add_group_field( $location_group_field_id, array(
		'name'       => __( 'Street Name', 'muext-engagement' ),
		'id'         => $prefix . 'route',
		'type'       => 'hidden',
	) );
	$cmb->add_group_field( $location_group_field_id, array(
		'name'       => __( 'City', 'muext-engagement' ),
		'id'         => $prefix . 'locality',
		'type'       => 'hidden',
	) );
	$cmb->add_group_field( $location_group_field_id, array(
		'name'       => __( 'County', 'muext-engagement' ),
		'id'         => $prefix . 'administrative_area_level_2',
		'type'       => 'hidden',
	) );
	$cmb->add_group_field( $location_group_field_id, array(
		'name'       => __( 'State', 'muext-engagement' ),
		'id'         => $prefix . 'administrative_area_level_1',
		'type'       => 'hidden',
	) );
	$cmb->add_group_field( $location_group_field_id, array(
		'name'       => __( 'Country', 'muext-engagement' ),
		'id'         => $prefix . 'country',
		'type'       => 'hidden',
	) );
	$cmb->add_group_field( $location_group_field_id, array(
		'name'       => __( 'ZIP code', 'muext-engagement' ),
		'id'         => $prefix . 'postal_code',
		'type'       => 'hidden',
	) );
	$cmb->add_group_field( $location_group_field_id, array(
		'name'       => __( 'Longitude', 'muext-engagement' ),
		'id'         => $prefix . 'longitude',
		'type'       => 'hidden',
	) );
	$cmb->add_group_field( $location_group_field_id, array(
		'name'       => __( 'Latitude', 'muext-engagement' ),
		'id'         => $prefix . 'latitude',
		'type'       => 'hidden',
	) );

	

	/** WHAT **/
	$cmb->add_field( array(
		'name'    => esc_html__( 'Description *', 'muext-engagement' ),
		// 'desc'    => esc_html__( 'field description (optional)', 'cmb2' ),
		'id'      => 'content', // This will be saved as the main post content.
		'type'    => 'textarea',
		'before_row' => __NAMESPACE__ . '\\muext_before_row_cb',
		//'options' => array( 'textarea_rows' => 10, ),
	) );
	
	// URL text field
	$cmb->add_field( array(
		'name' => __( 'Program URL', 'muext-engagement' ),
		// 'desc' => __( 'field description (optional)', 'muext-engagement' ),
		'id'   => $prefix . 'url',
		'type' => 'text_url',
		// 'protocols' => array('http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet'), // Array of allowed protocols
		// 'repeatable' => true,
	) );
	
	$cmb->add_field( array(
		//'default_cb' => 'yourprefix_maybe_set_default_from_posted_values',
		'name'       => __( 'Set Featured Image', 'muext-engagement' ),
		'id'         => 'engagement_image',
		'desc'		 => 'Must be .png, .gif or .jpg format. Minimum recommended resolution: 560px (width) x 315px (height).',
		'type'       => 'text',
		'attributes' => array(
			'type' => 'file', // Let's use a standard file upload field
		),
		'render_row_cb' => __NAMESPACE__ . '\\muext_render_row_cb',
	) );
	
	
	$cmb->add_field( array(
		//'default_cb' => 'yourprefix_maybe_set_default_from_posted_values',
		'name'       => __( 'Affiliation *', 'muext-engagement' ),
		'id'         => 'affiliation',
		'desc' 		 => esc_html__( 'Select all that apply', 'muext-engagement' ),
		'type'       => 'pw_multiselect',
		'options'	 => muext_get_cmb_options_array_tax( 'muext_program_affiliation' ),
		//'taxonomy'   => 'muext_program_affiliation', // Taxonomy Slug
		//'inline'	 => true,
	) );
	
	
	
	//THEME
	$cmb->add_field( array(
		//'default_cb' => 'yourprefix_maybe_set_default_from_posted_values',
		'name'       => __( 'Theme *', 'muext-engagement' ),
		'id'         => 'theme',
		'desc' 		 => esc_html__( 'Select all that apply', 'muext-engagement' ),
		'type'       => 'pw_multiselect',
		'options'	 => muext_get_cmb_options_array_tax( 'muext_program_category' ),
		//'taxonomy'   => 'muext_program_affiliation', // Taxonomy Slug
		'before_row' => __NAMESPACE__ . '\\muext_before_row_cb',
		'attributes'  => array(
			'placeholder' => '',
		)
		//'inline'	 => true,
	) );
	
	
	// Regular text field
	$cmb->add_field( array(
		'name'       => __( 'College or Affiliation. For reference only. (Do not update.)', 'muext-engagement' ),
		// 'desc'       => __( 'field description (optional)', 'muext-engagement' ),
		'id'         => $prefix . 'college_affiliation',
		'type'       => 'text',
		//'save_field' => false, // Disables the saving of this field.
		'on_front'	 => false,
		// 'attributes' => array(
		// 	'disabled' => 'disabled',
		// 	'readonly' => 'readonly',
		// ),
	) );
	
	


	/** WHEN **/
	$cmb->add_field( array(
		'name' => esc_html__( 'Start Date', 'muext-engagement' ),
		'desc' => esc_html__( 'Select the approximate date that this engagement started.', 'cmb2' ),
		'id'   => $prefix . 'start_date',
		'type' => 'text_date',
		'date_format' => 'Y-m-d',
		'before_row' => __NAMESPACE__ . '\\muext_before_row_cb',
		'classes' => 'inline-desktop',
	) );

	$cmb->add_field( array(
		'name' => esc_html__( 'Frequency', 'muext-engagement' ),
		'desc' => esc_html__( 'Select the most applicable', 'muext-engagement' ),
		'id'   => $prefix . 'frequency',
		'type' => 'select',
		'options'          => array(
			'N/A'     => __( 'N/A', 'cmb2' ),
			'recurring' => __( 'recurring', 'cmb2' ),
			'ongoing'   => __( 'ongoing/continuous', 'cmb2' ),
			'ended'     => __( 'ended', 'cmb2' ),
		),
		'classes' => 'inline-desktop',
	) );

	$cmb->add_field( array(
		'name' => esc_html__( 'End Date', 'muext-engagement' ),
		'desc' => esc_html__( 'If the event spanned more than one day, select the end date.', 'cmb2' ),
		'id'   => $prefix . 'end_date',
		'type' => 'text_date',
		'date_format' => 'Y-m-d',
		//'on_front'	 => false,
		'classes' => 'hidden',
	) );
	
	$cmb->add_field( array(
		'name' => 'Timeframe (text) - <em>Note: will not show on Engagement. Informational only.</em>',
		'desc' => esc_html__( 'If more detail is necessary to describe the Timeframe of the Engagement, do so here.', 'muext-engagement' ),
		'id'   => $prefix . 'timeframe',
		'type' => 'text',
		'attributes'  => array(
			'placeholder' => 'e.g., occurs once every spring semester',
		)
	) );
	
	/** HOW ***/
	//$$$$ bills,y all
	$cmb->add_field( array(
		//'default_cb' => 'yourprefix_maybe_set_default_from_posted_values',
		'name'       => __( 'Funding Source', 'muext-engagement' ),
		'id'         => 'funding',
		'desc' 		 => esc_html__( 'Select all that apply', 'muext-engagement' ),
		'type'       => 'pw_multiselect',
		'options'	 => muext_get_cmb_options_array_tax( 'muext_program_funding' ),
		//'taxonomy'   => 'muext_program_affiliation', // Taxonomy Slug
		'before_row' => __NAMESPACE__ . '\\muext_before_row_cb',
		'attributes'  => array(
			'placeholder' => '',
		)
		//'inline'	 => true,
	) );
	
	
	//OUTREACH TYPE
	$cmb->add_field( array(
		//'default_cb' => 'yourprefix_maybe_set_default_from_posted_values',
		'name'       => __( 'Engagement Type *', 'muext-engagement' ),
		'id'         => 'type',
		'desc' 		 => esc_html__( 'Select all that apply', 'muext-engagement' ),
		'type'       => 'pw_multiselect',
		'options'	 => muext_get_cmb_options_array_tax( 'muext_program_outreach_type' ),
		//'taxonomy'   => 'muext_program_affiliation', // Taxonomy Slug
		'before_row' => __NAMESPACE__ . '\\muext_before_row_cb',
		'attributes'  => array(
			'placeholder' => '',
		)
		//'inline'	 => true,
	) );

	/** WHY **/
	//outcomes and impact
	
	$cmb->add_field( array(
		//'default_cb' => 'yourprefix_maybe_set_default_from_posted_values',
		'name'       => __( 'Impact', 'muext-engagement' ),
		'id'         => 'impact',
		'desc' 		 => esc_html__( 'Select all that apply', 'muext-engagement' ),
		'type'       => 'pw_multiselect',
		'options'	 => muext_get_cmb_options_array_tax( 'muext_program_impact_area' ),
		'before_row' => __NAMESPACE__ . '\\muext_before_row_cb',
		//'taxonomy'   => 'muext_program_affiliation', // Taxonomy Slug
		//'inline'	 => true,
	) );
	
	$cmb->add_field( array(
		//'default_cb' => 'yourprefix_maybe_set_default_from_posted_values',
		'name'       => __( 'Audience', 'muext-engagement' ),
		'id'         => 'audience',
		'desc' 		 => esc_html__( 'Select all that apply', 'muext-engagement' ),
		'type'       => 'pw_multiselect',
		'options'	 => muext_get_cmb_options_array_tax( 'muext_program_audience' ),
		//'taxonomy'   => 'muext_program_affiliation', // Taxonomy Slug
		//'inline'	 => true,
	) );
	
	$cmb->add_field( array(
		'name'    => esc_html__( 'Outcome', 'muext-engagement' ),
		'desc'    => esc_html__( 'Describe the outcomes of the Engagement', 'cmb2' ),
		'id'      => $prefix . 'outcome_text', // This will be saved as the main post content.
		'type'    => 'wysiwyg',
		// 'options' => array( 'textarea_rows' => 10, ),
		//'save_field' => false, // Disables the saving of this field.
		'attributes'  => array(
			'placeholder' => 'e.g., Success Story, demographic information..',
		),
		'classes' => 'hidden',
		'before_row' => __NAMESPACE__ . '\\muext_before_row_cb',
		// 'attributes' => array(
		// 	'disabled' => 'disabled',
		// 	'readonly' => 'readonly',
		// ),
	) );
		
}


/**
 * Adds content before fields (based on 'id')
 *
 **/
function muext_before_row_cb( $field_args, $field ) {
	//add headers to sections, add show/hide buttons
	if ( 'content' == $field_args['id'] ) { //if we're before the Description section
		echo '<div class="question-type">WHAT</div>';
	} else if( '_muext_start_date' == $field_args['id'] ){
		echo '<div class="question-type">WHEN</div>';
	} else if( 'impact' == $field_args['id'] ){
		echo '<div class="question-type">WHY</div>';
	} else if( 'funding' == $field_args['id'] ){
		echo '<div class="question-type">HOW</div>';
	} else if( '_muext_outcome_text' == $field_args['id'] ){
		//add button
		echo '<button id="show-outcomes-box" class="button"><span class="fa fa-plus"></span>&nbsp;Add Outcomes or Success Stories</button>';
		
	}
	
	//var_dump( $field_args['id'] );
}

/**
 * Manually render a field.
 *
 * @param  array      $field_args Array of field arguments.
 * @param  CMB2_Field $field      The field object.
 */
function muext_render_row_cb( $field_args, $field ) {
	
	//var_dump( $field );
	if ( '_muext_title' == $field_args['id'] ) {
	
		$classes     = $field->row_classes();
		$id          = $field->args( 'id' );
		$label       = $field->args( 'name' );
		$name        = $field->args( '_name' );
		$value       = $field->escaped_value();
		$description = $field->args( 'description' );

		//if we're on an existing engagament, the $value == the title
		if( gettype( $field->object_id ) == "integer" ){
			$value = get_the_title( $field->object_id );
		}
		?>
		<div class="cmb-row <?php echo esc_attr( $classes ); ?>">
			<div class="cmb-th">
				<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
			</div>
			<div class="cmb-td">
				<input id="<?php echo esc_attr( $id ); ?>" type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $value; ?>"/>
			</div>
			<p class="cmb2-metabox-description"><?php echo esc_html( $description ); ?></p>
		</div>

	<?php
	} else if ( 'engagement_image' == $field_args['id'] ) {
		
		$classes     = $field->row_classes();
		$id          = $field->args( 'id' );
		$label       = $field->args( 'name' );
		$name        = $field->args( '_name' );
		$value       = $field->escaped_value();
		$description = $field->args( 'description' );

		//var_dump( $field );

		$has_image = false;
		//if we're on an existing engagement, look for featured image
		if( gettype( $field->object_id ) == "integer" ){
			$has_image = has_post_thumbnail( $field->object_id );
			$image_url = get_the_post_thumbnail_url( $field->object_id );
			$image_id = get_post_thumbnail_id( $field->object_id );
			//var_dump( $has_image );
			//var_dump( $image_url );
		}
		?>
		<div class="cmb-row cmb-type-text cmb2-id-engagement-image table-layout <?php echo esc_attr( $classes ); ?>" data-fieldtype="text">
			<div class="cmb-th">
				<label for="<?php echo esc_attr( $id ); ?>">Set Featured Image</label>
			</div>
			<div class="cmb-td">
			<?php if( $has_image ){ ?>
				<div class="inner-form-box">
					<p><strong>
						Current image file selected: <?php echo "<a class='yellow-text' href='" . $image_url . "'>" . $image_url . "</a>"; ?>
					</strong></p>
					<p>
						<strong>Remove Current image?</strong>
						<input id="remove_image_check" type="checkbox" name="remove_featured_image" value="<?php echo $image_id; ?>">
					</p>
				</div>
				<p><strong>Replace Current Image: </strong>
					<input type="file" class="regular-text" name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>" value="<?php echo $image_url; ?>"/>
				</p>
			<?php } else { ?>
				<input type="file" class="regular-text" name="<?php echo esc_attr( $id ); ?>" id="<?php echo esc_attr( $id ); ?>" value="<?php echo $image_url; ?>"/>
			<?php } ?>
			<p class="cmb2-metabox-description"><?php echo esc_html( $description ); ?></p>

			</div>
		</div>
		<?php
	
	}
}

/**
 * Define the program information metabox and field configurations.
 */
function muext_program_outcomes_meta_box() {

	// Start with an underscore to hide fields from custom fields list
	$prefix = '_muext_';

	/**
	 * Initiate the metabox
	 */
	$cmb = new_cmb2_box( array(
		'id'            => 'program_outcomes',
		'title'         => __( 'Program Outcomes - OLD', 'muext-engagement' ),
		'object_types'  => array( 'muext_engagement' ), // Post type
		'context'       => 'normal',
		'priority'      => 'high',
		'show_names'    => true, // Show field names on the left
		// 'cmb_styles' => false, // false to disable the CMB stylesheet
		// 'closed'     => true, // Keep the metabox closed by default
	) );

	$cmb->add_field( array(
		'name'    => esc_html__( 'Outcome', 'muext-engagement' ),
		'desc'    => esc_html__( 'A text description of when this occurred. For reference only. (Do not update.)', 'cmb2' ),
		'id'      => $prefix . 'outcome_text', // This will be saved as the main post content.
		'type'    => 'wysiwyg',
		// 'options' => array( 'textarea_rows' => 10, ),
		// 'save_field' => false, // Disables the saving of this field.
		// 'attributes' => array(
		// 	'disabled' => 'disabled',
		// 	'readonly' => 'readonly',
		// ),
	) );

	$cmb->add_field( array(
		'name' => __( 'Number of direct contacts', 'muext-engagement' ),
		'id'   => $prefix . 'direct_contacts',
		'type' => 'text',
		'attributes' => array(
			'type' => 'number',
			'pattern' => '\d*',
		),
		'sanitization_cb' => 'absint',
        'escape_cb'       => 'absint',
	) );

	$cmb->add_field( array(
		'name' => __( 'Number of indirect contacts', 'muext-engagement' ),
		'id'   => $prefix . 'indirect_contacts',
		'type' => 'text',
		'attributes' => array(
			'type' => 'number',
			'pattern' => '\d*',
		),
		'sanitization_cb' => 'absint',
        'escape_cb'       => 'absint',
	) );

	// Add other metaboxes as needed
}


/**
 * Display metabox for only certain user roles.
 * @author @Mte90
 * @link   https://github.com/CMB2/CMB2/wiki/Adding-your-own-show_on-filters
 *
 * @param  bool  $display  Whether metabox should be displayed or not.
 * @param  array $meta_box Metabox config array
 * @return bool            (Modified) Whether metabox should be displayed or not.
 */
function muext_show_meta_to_chosen_roles( $display, $meta_box ) {
	if ( ! isset( $meta_box['show_on']['key'], $meta_box['show_on']['value'] ) ) {
		return $display;
	}

	if ( 'role' !== $meta_box['show_on']['key'] ) {
		return $display;
	}

	$user = wp_get_current_user();

	// No user found, return
	if ( empty( $user ) ) {
		return false;
	}

	$roles = (array) $meta_box['show_on']['value'];

	foreach ( $user->roles as $role ) {
		// Does user have role.. check if array
		if ( is_array( $roles ) && in_array( $role, $roles ) ) {
			return true;
		}
	}

    return false;
}
//add_filter( 'cmb2_show_on', 'cmb_show_meta_to_chosen_roles', 10, 2 );

function muext_save_taxonomy_select2_boxes( $post_id ){
	
	muext_select2_taxonomy_process( $post_id, 'affiliation', 'muext_program_affiliation' );
	muext_select2_taxonomy_process( $post_id, 'audience', 'muext_program_audience' );
	muext_select2_taxonomy_process( $post_id, 'impact', 'muext_program_impact_area' );
	muext_select2_taxonomy_process( $post_id, 'theme', 'muext_program_category' );
	muext_select2_taxonomy_process( $post_id, 'type', 'muext_program_outreach_type' );
	muext_select2_taxonomy_process( $post_id, 'funding', 'muext_program_funding' );
	
	
}

/**
 * on post save (in wp-admin), updates the geoid taxonomy per the fields in the form
 *
 **/
function muext_update_geoid_taxonomy( $post_id ){
	
	// location-based geoid taxonomy.. for ANY geoid in this $_POST, set the 'geoid' taxonomy for the entire post
	$geoid_terms = array();
	
	foreach($_POST['_muext_location_group'] as $index => $array) { // e.g., $_POST['_muext_location_group'][0]['_muext_geo_id']
		
		foreach( $array as $key => $value){
			if ( strpos( $key, 'geo_id' ) !== false ) {
				// geoid string exists in field name
				// But is not the whole US. Let's not be greedy.
				if ( '01000US' != $value ) {
					array_push( $geoid_terms, $value );
				}
				//error_log( $value );
			}
		}
	}
	
	wp_set_object_terms( $post_id, $geoid_terms, 'muext_geoid' );
	
}


/******* FRONT END FORM FUNCTIONALITY *******/

/**
 * Handle the cmb_frontend_form shortcode
 *
 * @param  array  $atts Array of shortcode attributes
 * @return string       Form html
 */
 
function muext_frontend_form_submission_shortcode( $atts = array() ) {
	global $post;

	$metabox_id = 'program_information';
	
	// Current user
	$user_id = get_current_user_id();
	
	if ( ! isset( $atts['post_id'] ) ) {
		$object_id = wp_insert_post( array( 
			'post_title'  => '', 
			'post_type'   => 'muext_engagement',
			'post_status' => 'auto-draft' 
		) );
	} else {
		$object_id = absint( $atts['post_id'] );
	}

	// Enqueue the media uploader script, passing it the right post ID.
	wp_enqueue_media( array( 'post' => $object_id ) );

	// Initiate our output variable
	$output = '';
	$cmb = cmb2_get_metabox( $metabox_id, $object_id );
	
	// Get any submission errors
	if ( ( $error = $cmb->prop( 'submission_error' ) ) && is_wp_error( $error ) ) {
		// If there was an error with the submission, add it to our ouput.
		$output .= '<h3 class="warning-text">' . sprintf( __( 'There was an error in the submission: %s', 'muext-engagement' ), '<strong>'. $error->get_error_message() .'</strong>' ) . '</h3>';
	}
	
	// If the post was submitted successfully, notify the user.
	if ( isset( $_GET['post_submitted'] ) && ( $post = get_post( absint( $_GET['post_submitted'] ) ) ) ) {
		// Get submitter's name
		$name = get_post_meta( $post->ID, 'submitted_author_name', 1 );
		$name = $name ? ' '. $name : '';
		// Add notice of submission to our output
		$output .= '<h3 class="message">' . sprintf( __( 'Thank you%s, your new Engagement has been submitted and is pending review by a curator.  Add another Engagement below.', 'muext-engagement' ), esc_html( $name ) ) . '</h3>';
	}
	
	//var_dump( $cmb );
	// Get our form
	$output .= cmb2_get_metabox_form( $metabox_id, $object_id, array( 'save_button' => __( 'Submit Engagement', 'muext-engagement' ) ) );

	return $output;
}


/**
 * Handles form submission on save. Redirects if save is successful, otherwise sets an error message as a cmb property
 *
 * @return void
 */
 
function muext_handle_frontend_new_post_form_submission() {
	// If no form submission, bail
	if ( empty( $_POST ) || ! isset( $_POST['submit-cmb'], $_POST['object_id'] ) ) {
		return false;
	}
	
	$object_id = intval( $_POST['object_id'] );

	// Instantiate post data array.
	$post_data = array( 'ID' => $object_id );
	
	/*
	 * New posts have a status of "auto-draft" until first submitted. 
	 * If the user can edit_others_posts, then use "publish."
	 * If the submitter is just an author, set the status to "pending." 
	 * But, if the post is already published, let it stay published (no matter who is editing it).
	 */
	$post_status = get_post_status( $object_id );
	if ( 'publish' === $post_status || current_user_can( 'edit_others_posts' ) ) {
		$post_data['post_status'] = 'publish';
	} else {
		$post_data['post_status'] = 'pending';
	}
	
	// Get CMB2 metabox object
	$metabox_id = 'program_information';
	//get our metabox object
	$cmb = cmb2_get_metabox( $metabox_id, $object_id );
	
	// Get our shortcode attributes and set them as our initial post_data args
	if ( isset( $_POST['atts'] ) ) {
		foreach ( (array) $_POST['atts'] as $key => $value ) {
			$post_data[ $key ] = sanitize_text_field( $value );
		}
		unset( $_POST['atts'] );
	}
	
	// Check security nonce
	if ( ! isset( $_POST[ $cmb->nonce() ] ) || ! wp_verify_nonce( $_POST[ $cmb->nonce() ], $cmb->nonce() ) ) {
		return $cmb->prop( 'submission_error', new \WP_Error( 'security_fail', __( 'Security check failed.' ) ) );
	}
	
	// Check title submitted
	if ( empty( $_POST['_muext_title'] ) ) {
		return $cmb->prop( 'submission_error', new \WP_Error( 'post_data_missing', __( 'New Engagement requires a title.' ) ) );
	}
	// Check title submitted
	if ( empty( $_POST['content'] ) ) {
		return $cmb->prop( 'submission_error', new \WP_Error( 'post_data_missing', __( 'Engagement requires a description.' ) ) );
	}
	
	// And that the title is not the default title
	if ( $cmb->get_field( '_muext_title' )->default() == $_POST['_muext_title'] ) {
		return $cmb->prop( 'submission_error', new \WP_Error( 'post_data_missing', __( 'Please enter a new title.' ) ) );
	}
	/**
	 * Fetch sanitized values
	 */
	 
	$sanitized_values = $cmb->get_sanitized_values( $_POST );
	// Set our post data arguments
	$post_data['post_title'] = $sanitized_values['_muext_title'];
	//unset( $sanitized_values['_muext_title'] );
	
	$post_data['post_content'] = $sanitized_values['content'];
	unset( $sanitized_values['content'] );
	
	$post_data['post_type'] = 'muext_engagement';
	
	// Create the new post
	$new_submission_id = wp_insert_post( $post_data, true );
	
	// If we hit a snag, update the user
	if ( is_wp_error( $new_submission_id ) ) {
		return $cmb->prop( 'submission_error', $new_submission_id );
	}
	$cmb->save_fields( $new_submission_id, 'post', $sanitized_values );
	
	/**
	 * Other than post_type and post_status, we want
	 * our uploaded attachment post to have the same post-data
	 */
	unset( $post_data['post_type'] );
	unset( $post_data['post_status'] );
	
	if( isset( $_POST['remove_featured_image'] ) ){
		// get the image attachement id
		$image_id = $_POST['remove_featured_image'];
		wp_delete_attachment( $image_id, true ); //true = force deletion

	}

	// Try to upload the featured image, if set
	$img_id = muext_frontend_form_photo_upload( $new_submission_id, $post_data );
	
	// If our photo upload was successful, set the featured image
	if ( $img_id && ! is_wp_error( $img_id ) ) {
		set_post_thumbnail( $new_submission_id, $img_id );
	}
	
	//now, taxonomies...
	if ( $new_submission_id !== 0 ) {
        muext_select2_taxonomy_process( $new_submission_id, 'affiliation', 'muext_program_affiliation' );
        muext_select2_taxonomy_process( $new_submission_id, 'audience', 'muext_program_audience' );
        muext_select2_taxonomy_process( $new_submission_id, 'impact', 'muext_program_impact_area' );
        muext_select2_taxonomy_process( $new_submission_id, 'theme', 'muext_program_category' );
        muext_select2_taxonomy_process( $new_submission_id, 'type', 'muext_program_outreach_type' );
        muext_select2_taxonomy_process( $new_submission_id, 'funding', 'muext_program_funding' );
    }
	
	// location-based geoid taxonomy.. for ANY geoid in this $_POST, set the 'geoid' taxonomy for the entire post
	muext_update_geoid_taxonomy( $object_id );
	
	/*
	 * Redirect back to the form page with a query variable with the new post ID.
	 * This will help double-submissions with browser refreshes
	 */
	wp_redirect( esc_url_raw( add_query_arg( 'post_submitted', $new_submission_id ) ) );
	exit;
}



/**
 * Returns field values for a metabox object
 *
 * @params object. CMB metabox object
 * @return array.  Array of field values in system by id.
 *
 **/
function muext_get_field_values( $cmb_obj ){
	
	$fields = $form2->meta_box["fields"];
	
	$field_ids = array();
	$field_values = array();
	
	//get field ids
	foreach( $fields as $id => $val ){
		if( $id == "id" ){
			array_push( $field_ids, $val );
		}
	}
	
	foreach( $field_ids as $one_id ){
		//$field_values[ $one_id ] = 
		
		
	}
	//cmb2_get_field_value( $meta_box, $field_id, $object_id = 0, $object_type = '' )
	
	
}


/**
 * Handles uploading a file to a WordPress post
 *
 * @param  int   $post_id              Post ID to upload the photo to
 * @param  array $attachment_post_data Attachement post-data array
 */
 
function muext_frontend_form_photo_upload( $post_id, $attachment_post_data = array() ) {
	// Make sure the right files were submitted
	if (
		empty( $_FILES )
		|| ! isset( $_FILES['engagement_image'] )
		|| isset( $_FILES['engagement_image']['error'] ) && 0 !== $_FILES['engagement_image']['error']
	) {
		//var_dump( $_FILES );
		return;
	}
	
	// Filter out empty array values
	$files = array_filter( $_FILES['engagement_image'] );
	
	// Make sure files were submitted at all
	if ( empty( $files ) ) {
		return;
	}
	// Make sure to include the WordPress media uploader API if it's not (front-end)
	if ( ! function_exists( 'media_handle_upload' ) ) {
		require_once( ABSPATH . 'wp-admin/includes/image.php' );
		require_once( ABSPATH . 'wp-admin/includes/file.php' );
		require_once( ABSPATH . 'wp-admin/includes/media.php' );
	}
	// Upload the file and send back the attachment post ID
	return media_handle_upload( 'engagement_image', $post_id, $attachment_post_data );
}


//https://halfelf.org/2017/cmb2-select2-taxonomies/
/**
 * Saves taxonomy terms
 *
 **/
function muext_select2_taxonomy_process( $post_id, $postmeta, $taxonomy ) {
 
    $get_post_meta = get_post_meta( $post_id, $postmeta, true );
	//error_log( 'get post meta: ' );
	//error_log( implode(",", $get_post_meta ) );
    $get_the_terms = get_the_terms( $post_id, $taxonomy );

 
    if ( is_array( $get_post_meta ) ) {
        // If we already have the post meta, then we should set the terms
        $get_post_meta   = array_map( 'intval', $get_post_meta );
        $get_post_meta   = array_unique( $get_post_meta );
        $set_the_terms = array();
 
        foreach( $get_post_meta as $term_id ) {
            $term = get_term_by( 'id' , $term_id, $taxonomy );
            array_push( $set_the_terms, $term->slug );
        }
		
		//error_log( 'setting object terms' );
 
        wp_set_object_terms( $post_id, $set_the_terms , $taxonomy );
 
    } else if ( $get_the_terms && ! is_wp_error( $get_the_terms ) ) {
        // If there's no post meta, we force the terms to be the default
        $get_post_meta = array();
        foreach( $get_the_terms as $term ) {
            $term_id = $term->term_id;
            array_push( $get_post_meta, $term_id );
        }
        update_post_meta( $post_id, $postmeta, $get_post_meta );
		
    }
 
}

/**
 * Get a list of terms
 *
 * Generic function to return an array of taxonomy terms formatted for CMB2.
 * Simply pass in your get_terms arguments and get back a beautifully formatted
 * CMB2 options array.
 *
 * @param string|array $taxonomies Taxonomy name or list of Taxonomy names
 * @param  array|string $query_args Optional. Array or string of arguments to get terms
 * @return array CMB2 options array
 */
function muext_get_cmb_options_array_tax( $taxonomies, $query_args = '' ) {
	$defaults = array(
		'hide_empty' => false
	);
	$args = wp_parse_args( $query_args, $defaults );
	$terms = get_terms( $taxonomies, $args );
	$terms_array = array();
	if ( ! empty( $terms ) ) {
		foreach ( $terms as $term ) {
			$terms_array[$term->term_id] = $term->name;
		}
	}
	return $terms_array;
}


/**
 * Handles sanitization for telephone number fields.
 *
 * @param  mixed      $value      The unsanitized value from the form.
 * @param  array      $field_args Array of field arguments.
 * @param  CMB2_Field $field      The field object
 *
 * @return mixed                  Sanitized value to be stored.
 */
function telephone_number_sanitization( $value, $field_args, $field ) {
	// Strip all non-numeric characters.
	$value = preg_replace( "/[^0-9]/", '', $value );
	switch( strlen( $value ) ) {
		case 7:
			$value = preg_replace( "/([0-9]{3})([0-9]{4})/", "$1-$2", $value );
			break;
		case 10:
			$value = preg_replace( "/([0-9]{3})([0-9]{3})([0-9]{4})/", "$1-$2-$3", $value );
			break;
		case 11:
			$value = preg_replace( "/([0-9]{1})([0-9]{3})([0-9]{3})([0-9]{4})/", "$2-$3-$4", $value );
			break;
	}

	return $value;
}

/**
 * Make sure that the post content is used in the content wysiwyg field.
 *
 * @param mixed $data     The value get_metadata() should
 *                         return - a single metadata value,
 *                         or an array of values.
 *
 * @param int   $object_id Object ID.
 *
 * @param array $args {
 *     An array of arguments for retrieving data
 *
 *     @type string $type     The current object type
 *     @type int    $id       The current object ID
 *     @type string $field_id The ID of the field being requested
 *     @type bool   $repeat   Whether current field is repeatable
 *     @type bool   $single   Whether current field is a single database row
 * }
 *
 * @param CMB2_Field object $field This field object
 */
function populate_post_content_input( $data, $object_id, $args, $field ) {
	if ( isset( $args['field_id'] ) && 'content' ==  $args['field_id'] ) {
		$post = get_post( $object_id, 'object', 'edit' );
		$data = $post->post_content;
	}
	return $data;
}
add_filter( "cmb2_override_content_meta_value", __NAMESPACE__ . '\\populate_post_content_input', 10, 4  );

/**
 * Add an "import engagements" tool screen.
 *
 * @since 1.0.0
 */
function add_tools_page() {
	add_management_page( "Import Extension Programs", 'Import Extension Programs', 'manage_options', 'import-extension-programs', __NAMESPACE__ . '\\render_tools_page' );
}
/**
 * Render the "import engagements" tool screen.
 *
 * @since 1.0.0
 *
 * @return html 
 */
function render_tools_page() {
	wp_enqueue_media();
	wp_enqueue_script( \MU_Ext_Engagement\get_plugin_slug() . '-plugin-script', plugins_url( 'assets/js/admin-tools.js', __FILE__ ), array( 'jquery' ), \MU_Ext_Engagement\get_plugin_version(), true );
	?>
	<div class="wrap">
		<h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
		Hey there ho there!

		<section id="choose-file">
			<h3>Choose an Import File</h3>
			<button id="ext-import-file-upload" >Upload or Choose a CSV</button><br>
			<ul>
				<li>Selected File: <span id="import_csv_filename">Not set</span></li>
				<li>Program source: <span id="program_source_calc">Unknown</span></li>
				<li>Import type: <span id="import_type_calc">Unknown</span></li>
			</ul>

			<input type="hidden" id="import_csv_attachment_id" value="0">

			<button id="import-start" class="import-actions" disabled="disabled">Begin Import</button>

			<ul id="import-results"></ul>
		</section>

		<section id="help">
			<h3>Documentation</h3>
			<p>The import process for engagements uses the form of the CSV to figure out what to do. Start by importing the new programs, then follow up with meta and terms. Read on for CSV formatting tips for each step.</p>

			<h4>Importing Program Information</h4>
			<p>Include the following columns in this order:</p>
			<ul>
				<li><code>myextension_id</code> or <code>local_id</code></li>	
				<li><code>title</code></li>
				<li><code>description</code></li>
			</ul>
			<p>Download a <a href="<?php echo plugins_url( 'assets/sample-import-files/engagement-sample-program-import.csv', __FILE__ ); ?>">sample CSV</a> to get started.</p>

			<h4>Importing Taxonomy Terms</h4>
			<p><em>Limits</em> Keep your files to 1000 terms or fewer for best performance.</p>
			<p>Include the following columns in this order:</p>
			<ul>
				<li><code>myextension_id</code> or <code>local_id</code></li>	
				<li><code>taxonomy</code></li>
				<li><code>term</code></li>
			</ul>
			<p>These taxonomies are associated with engagements, and these names work well in the <code>taxonomy</code> column:</p>
			<ul>
				<?php 
				$taxonomies = get_object_taxonomies( 'muext_engagement', 'objects' );
				foreach ( $taxonomies as $tax ) {
					?>
					<li><code><?php echo $tax->name; ?></code> (&ldquo;<?php echo $tax->label; ?>&rdquo;)</li>
					<?php
				}
				?>
			</ul>
			<p>Download a <a href="<?php echo plugins_url( 'assets/sample-import-files/engagement-sample-taxonomy-import.csv', __FILE__ ); ?>">sample CSV</a> to get started.</p>

			<h4>Importing Meta Information</h4>
			<p><em>Limits</em> Keep your files to 1000 items or fewer for best performance. If you're importing <code>location_text</code>, only submit about 175 records in a file.</p>
			<p>Include the following columns in this order:</p>
			<ul>
				<li><code>myextension_id</code> or <code>local_id</code></li>	
				<li><code>meta_key</code></li>
				<li><code>value</code></li>
			</ul>

			<p>These meta keys are associated with engagements, and will work well in the <code>meta_key</code> column:</p>
			<ul>
				<li><code>_muext_start_date</code></li>
				<li><code>_muext_frequency</code></li>
				<li><code>location_text</code></li>
			</ul>
			<p>Download a <a href="<?php echo plugins_url( 'assets/sample-import-files/engagement-sample-meta-import.csv', __FILE__ ); ?>">sample CSV</a> to get started.</p>

			<h4>Importing Contact Information</h4>
			<p>Include the following columns in this order:</p>
			<ul>
				<li><code>myextension_id</code> or <code>local_id</code></li>	
				<li><code>name</code></li>
				<li><code>email</code></li>
				<li><code>phone</code></li>
			</ul>
			<p>Download a <a href="<?php echo plugins_url( 'assets/sample-import-files/engagement-sample-contacts-import.csv', __FILE__ ); ?>">sample CSV</a> to get started.</p>

		</section>	
	</div>
	<input type="hidden" id="engagement-ajax-nonce" value="<?php echo wp_create_nonce( 'engagement-ajax-nonce' ); ?>">
	<?php
}

/**
 * AJAX handler to check an import file's type.
 *
 * @since 1.0.0
 *
 * @return JSON response.
 */
function check_import_file() {
	if ( ! wp_verify_nonce( $_REQUEST['_nonce'], 'engagement-ajax-nonce' ) ) {
		wp_send_json_error( 'Nonce failed: ' . $_REQUEST['_nonce'] );
	} else if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'User does not have permission to do this.' );
	}

	$retval = get_import_file_type( $_REQUEST['attachment_id'] );

	wp_send_json_success( $retval );
}

/**
 * Check an import file's type.
 *
 * @since 1.0.0
 *
 * @param string $attachment_id   The ID of the CSV to use.
 * @return array An array of progress/status updates.
 */
function get_import_file_type( $attachment_id ) {
	$retval = array();
	if ( ( $handle = fopen( get_attached_file( $attachment_id ), "r" ) ) !== FALSE) {
		while ( ( $data = fgetcsv( $handle, 0, "," ) ) !== FALSE ) {
			// We'll use the header row to see what we've got.
			switch ( $data[0] ) {
				case 'myextension_id':
					$retval['data_source']      = 'Import/Update MyExtension Programs';
					$retval['data_source_slug'] = 'myextension_id';
					break;
				case 'local_id':
					$retval['data_source']      = 'Update Engagement Programs';
					$retval['data_source_slug'] = 'local_id';
					break;
				default:
					$retval['data_source'] = false;
					break;
			}
			switch ( $data[1] ) {
				case 'title':
					$retval['import_type']      = 'Import Programs';
					$retval['import_type_slug'] = 'programs';
					break;
				case 'taxonomy':
					$retval['import_type']      = 'Import Taxonomy Terms';
					$retval['import_type_slug'] = 'terms';
					break;
				case 'meta_key':
					$retval['import_type']      = 'Import Meta Data';
					$retval['import_type_slug'] = 'meta';
					break;
				case 'name':
					$retval['import_type']      = 'Import Contacts';
					$retval['import_type_slug'] = 'contacts';
					break;
				default:
					$retval['import_type'] = false;
					break;
			}

			break;
		}
		fclose($handle);
	}
	return $retval;
}

/**
 * Import engagement data from a carefully formatted CSV.
 *
 * @since 1.0.0
 *
 * @param string $attachment_id   The ID of the CSV to use.
 * @return array An array of progress/status updates.
 */
function import_data( $attachment_id = null ) {

	if ( ! wp_verify_nonce( $_REQUEST['_nonce'], 'engagement-ajax-nonce' ) ) {
		wp_send_json_error( 'Nonce failed: ' . $_REQUEST['_nonce'] );
	} else if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( 'User does not have permission to do this.' );
	}

	$retval = array();
	$api_key = get_option( 'muext-google-location-apikey' );

	$attachment_id = ( $attachment_id ) ? $attachment_id : $_REQUEST['attachment_id'];

	$import_file_info = get_import_file_type( $attachment_id );

	// Angela is the author of all of these things.
	$author = get_user_by( 'login', 'johnsonange' );

	$retval = array();
	$row = 1;
	$last_ref_id = 0;
	if ( ( $handle = fopen( get_attached_file( $attachment_id ), "r" ) ) !== FALSE) {
		while ( ( $data = fgetcsv( $handle, 0, "," ) ) !== FALSE ) {
			// Skip the header row.
			if ( 1 === $row ) {
				$row++;
				continue;
			}

			// Are we moving on to a new post?
			if ( $data[0] != $last_ref_id ) {
				// Are we working on an existing post?
				$post_id = false;
				if ( 'myextension_id' === $import_file_info['data_source_slug'] ) {
					if ( $existing_id = check_for_existing_engagement( 'myextension_id', $data[0] ) ) {
						$post_id = $existing_id;
					}
				} else if ( 'local_id' === $import_file_info['data_source_slug'] ) {
					// We are updating local posts
					$post_id = $data[0];
				}
			}

			// The routine varies depending on the type of import.
			if ( 'programs' === $import_file_info['import_type_slug'] ) {
				$post_args = array(
					'post_type'    => 'muext_engagement',
					'post_title'   => $data[1],
					'post_content' => $data[2],
					'post_author'  => $author->ID,
					'post_status'  => 'pending'
				);

				if ( $post_id ) {
					$post_args['ID'] = $post_id;
				}

				// Importing new from external source--store remote ID as meta
				if ( 'myextension_id' === $import_file_info['data_source_slug'] ) {
					$post_args['meta_input'] = array( 'myextension_id' => $data[0] );
				}

				$success = wp_insert_post( $post_args );

				if ( $success ) {
					$retval[] = "Item $data[0] was successfully imported with the local ID of $success.";
				} else {
					$retval[] = "Item $data[0] failed to import.";		
				}

			} else if ( 'terms' === $import_file_info['import_type_slug'] ) {

				$success = wp_set_object_terms( $post_id, $data[2], $data[1], 'true' );

				// Taxonomies should be appended as keyed arrays for CMB use.
				$term_ids = wp_get_object_terms( $post_id, $data[1], array( 'fields' => 'ids' ) );
				$related_meta_keys = array(
					'muext_program_category' => 'theme',
					'muext_program_audience' => 'audience',
					'muext_program_impact_area' => 'impact',
					'muext_program_outreach_type' =>  'type',
					'muext_program_affiliation' => 'affiliation',
					'muext_program_funding' => 'funding'
				);
				if ( array_key_exists( $data[1], $related_meta_keys ) ) {
					$meta = update_post_meta( $post_id, $related_meta_keys[ $data[1] ], $term_ids );	
				}

				if ( $success ) {
					$retval[] = "Term $data[2] was successfully applied to item $data[0].";
				} else {
					$retval[] = "Item $data[0] failed to gain the term $data[2].";		
				}

			} else if ( 'meta' === $import_file_info['import_type_slug'] ) {

				if ( 'location_text' == $data[1] ) {
					// Location is a special case, and is stored as a serialized array of arrays.
					$current_location = get_post_meta( $post_id, '_muext_location_group', true );
					if ( ! is_array( $current_location ) ) {
						$current_location = array();
					}
					$location_info = array( "_muext_location_text" => $data[2] );

					$api_url = add_query_arg( array(
						'key' => $api_key,
						'address' => $data[2],
					), 'https://maps.googleapis.com/maps/api/geocode/json' );

					// Location
					$response = json_decode( wp_remote_retrieve_body( wp_remote_get( $api_url,
					array(
					    'timeout'     => 120,
					)
					) ) );

					if ( ! empty( $response->status ) && 'OK' == $response->status && ! empty( $response->results[0] ) ) {
						if ( ! empty( $response->results[0]->address_components ) ) {
							foreach ( $response->results[0]->address_components as $comp ) {
								$location_info[ '_muext_' . $comp->types[0] ] = $comp->long_name;
							}
						}
						$location_info['_muext_latitude'] = $response->results[0]->geometry->location->lat;
						$location_info['_muext_longitude'] = $response->results[0]->geometry->location->lng;
					}

					$current_location[] = $location_info;
					$success = update_post_meta( $post_id, '_muext_location_group', array_unique( $current_location, SORT_REGULAR ) );
				} else {
					$success = update_post_meta( $post_id, $data[1], $data[2] );				
				}

				if ( $success ) {
					$retval[] = "Meta $data[2] was successfully applied to item $data[0].";
				} else {
					$retval[] = "Item $data[0] failed to gain the meta $data[2].";
				}
				// Keep an eye on the Geocoding API's response.
				if ( empty( $response->status ) ) {
					$retval[] = "Geocode API response was malformed.";				
				} else if ( 'OK' != $response->status ) {
					$retval[] = "Geocode API returned status: {$response->status}";				
				}	
			} else if ( 'contacts' === $import_file_info['import_type_slug'] ) {
				$contacts = get_post_meta( $post_id, '_muext_contact_group', true );

				if ( ! is_array( $contacts ) ) {
					$contacts = array();
				}
				$contacts[] = array( 
					"_muext_contact_name" => $data[1],
					"_muext_contact_email" => $data[2],
					"_muext_contact_phone" => $data[3]
				);

				$success = update_post_meta( $post_id, '_muext_contact_group', array_unique( $contacts, SORT_REGULAR ) );

				if ( $success ) {
					$retval[] = "Contact $data[1] was successfully applied to item $data[0].";
				} else {
					$retval[] = "Item $data[0] failed to gain the contact $data[1].";		
				}

				// Contacts must be added as users if they don't exist
				if ( ! get_user_by( 'email', $data[2] ) ) {
					// invent a username
					$email_parts = explode( "@", $data[2] );
					// Use a bonkers password--they'll never use it
					$password = wp_generate_password( 20, true, true );
					$new_user = wp_create_user( $email_parts[0] , $password, $data[2] );

					if ( is_int( $new_user ) ) {
						$retval[] = "Added new user (ID: {$new_user}) with the email $data[2].";
					} else {
						$retval[] = "Failed to add new user with the email $data[2].";		
					}
				} else {
					$retval[] = "User already exists with the email $data[2].";
				}

				// First contact should be added as a coauthor
				$coauthor = current( $contacts );
				$coauthor_user_obj = get_user_by( 'email', $coauthor["_muext_contact_email"] );

				if ( ! empty( $coauthor_user_obj->ID ) ) {
					update_post_meta( $post_id, '_muext_coauthor', $coauthor_user_obj->ID );
					$retval[] = "Added as a coauthor: {$coauthor_user_obj->user_email}.";
				}
			}
			$row++;
		}
		fclose($handle);
	}
	wp_send_json_success( $retval );
}

/**
 * Check to see if an engagement already exists, by meta key and value.
 *
 * @since 1.0.0
 *
 * @param string $meta_key   The meta key to check for.
 * @param string $meta_value The meta value to check for.
 * @return bool|int Return ID of existing post, false if none found.
 */
function check_for_existing_engagement( $meta_key = false, $meta_value = false ) {
	$retval = false;
	$exists = new \WP_Query( array( 
		'post_status'  => 'any',
		'post_type'    => 'muext_engagement',
		'meta_key'     => $meta_key,
		'meta_value'   => $meta_value,
		'fields'       => 'ids'
	) );
	if ( ! empty( $exists->posts ) ) {
		$retval = current( $exists->posts );
	}
	return $retval;
}