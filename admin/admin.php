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

/**
* Register and enqueue admin-specific style sheets and javascript files.
*
* @since     1.0.0
*
* @param string $hook_suffix The current admin page.
*/
function enqueue_admin_scripts_and_styles( $hook_suffix ) {
	$screen = get_current_screen();

	// Enqueue items for single engagement edit screen.
	if ( in_array( $hook_suffix, array( 'post.php', 'post-new.php' ) ) ) {
		if ( ! empty( $screen->post_type ) && 'muext_engagement' == $screen->post_type ) {
			wp_enqueue_script( \MU_Ext_Engagement\get_plugin_slug() . '-admin-edit-script', plugins_url( 'assets/js/admin-edit.js', __FILE__ ), array( 'jquery' ), \MU_Ext_Engagement\get_plugin_version() );

			// Localize the script with new data
			$api_key = get_option( 'muext-google-location-apikey' );
			wp_localize_script( \MU_Ext_Engagement\get_plugin_slug() . '-admin-edit-script', 'muext_js_data', array( 'google_api_key' => $api_key ) );

			wp_enqueue_script( 'google_places_api', "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places&callback=initAutocomplete", array( \MU_Ext_Engagement\get_plugin_slug() . '-admin-edit-script' ), \MU_Ext_Engagement\get_plugin_version(), true );
		}
	}

    // Enqueue items for settings screen.
	if ( ! empty( $screen->id ) && 'muext_engagement_page_muext-engagement' == $screen->id ) {
		// wp_enqueue_style( \MU_Ext_Engagement\get_plugin_slug() .'-admin-styles', plugins_url( 'assets/css/admin.css', __FILE__ ), array(), \MU_Ext_Engagement\get_plugin_version() );
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
			'add_button'    => __( '<span class="fa fa-plus"></span>&nbsp;&nbsp;Add Another Contact', 'muext-engagement' ),
			'remove_button' => __( '<span class="fa fa-trash"></span>&nbsp;&nbsp;Remove This Contact', 'muext-engagement' ),
			'sortable'      => true, // beta
			// 'closed'     => true, // true to have the groups closed by default
		),
		'classes' => 'contact-class',
	) );

	// Id's for group's fields only need to be unique for the group. Prefix is not needed.
	$cmb->add_group_field( $contact_group_field_id, array(
		'name' => 'Name',
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
			'add_button'    => __( '<span class="fa fa-plus"></span>&nbsp;&nbsp;Add Another Location', 'muext-engagement' ),
			'remove_button' => __( '<span class="fa fa-trash"></span>&nbsp;&nbsp;Remove This Location', 'muext-engagement' ),
			'sortable'      => true, // beta
			// 'closed'     => true, // true to have the groups closed by default
		),
		'classes' => 'location-class',
	) );
	
	// Regular text field
	$cmb->add_group_field( $location_group_field_id, array(
	//$cmb->add_field( array(
		'name'       => __( 'Location', 'muext-engagement' ),
		'desc'       => __( 'Enter an address or the city and state.', 'muext-engagement' ),
		'id'         => $prefix . 'location_text',
		'type'       => 'text',
		//'repeatable'  => false
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
		'desc'		 => 'Must be .png, .gif or .jpg format. Minumum recommended resolution: 560px (width) x 315px (height).',
		'type'       => 'text',
		'attributes' => array(
			'type' => 'file', // Let's use a standard file upload field
		),
	) );
	
	
	$cmb->add_field( array(
		//'default_cb' => 'yourprefix_maybe_set_default_from_posted_values',
		'name'       => __( 'Affiliation', 'muext-engagement' ),
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
		'name'       => __( 'Theme', 'muext-engagement' ),
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
		'save_field' => false, // Disables the saving of this field.
		'on_front'	 => false,
		// 'attributes' => array(
		// 	'disabled' => 'disabled',
		// 	'readonly' => 'readonly',
		// ),
	) );
	
	


	/** WHEN **/
	$cmb->add_field( array(
		'name' => esc_html__( 'Start Date', 'muext-engagement' ),
		'desc' => esc_html__( 'Select the rought date that this engagement occurred.', 'cmb2' ),
		'id'   => $prefix . 'start_date',
		'type' => 'text_date',
		'date_format' => 'Y-m-d',
		'before_row' => __NAMESPACE__ . '\\muext_before_row_cb',
	) );

	$cmb->add_field( array(
		'name' => esc_html__( 'End Date', 'muext-engagement' ),
		'desc' => esc_html__( 'If the event spanned more than one day, select the end date.', 'cmb2' ),
		'id'   => $prefix . 'end_date',
		'type' => 'text_date',
		'date_format' => 'Y-m-d',
		'on_front'	 => false,
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
	) );
	
	$cmb->add_field( array(
		'name' => 'Timeframe (text) - <em>Note: will not show on Engagement. Informational only.</em>',
		'desc' => esc_html__( 'If more detail is necessary to describe the Timeframe of the Engagement, do so here.', 'muext-engagement' ),
		'id'   => $prefix . 'timeframe',
		'type' => 'text',
		//'save_field' => false, // Disables the saving of this field.
		'attributes'  => array(
			'placeholder' => 'e.g., occurs once every spring semester',
		)
		// 'attributes' => array(
		// 	'disabled' => 'disabled',
		// 	'readonly' => 'readonly',
		// ),
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
		'name'       => __( 'Outreach Type', 'muext-engagement' ),
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

	// $cmb->add_field( array(
	// 	'name'     => esc_html__( 'Test Taxonomy Multi Checkbox', 'cmb2' ),
	// 	'desc'     => esc_html__( 'field description (optional)', 'cmb2' ),
	// 	'id'       => $prefix . 'multitaxonomy',
	// 	'type'     => 'taxonomy_multicheck',
	// 	'taxonomy' => 'muext_program_category', // Taxonomy Slug
	// 	// 'inline'  => true, // Toggles display to inline
	// ) );
	// Add other metaboxes as needed
	
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
		'type'    => 'textarea',
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
		echo '<button id="show-outcomes-box" class="button">Add Outcomes or Success Stories</button>';
		
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
	
	$classes     = $field->row_classes();
	$id          = $field->args( 'id' );
	$label       = $field->args( 'name' );
	$name        = $field->args( '_name' );
	$value       = $field->escaped_value();
	
	//overriding value because Mel can't get this form to populate
	//$value = get_post_meta( $post_id, string $key = '', bool $single = false )
	
	$description = $field->args( 'description' );
	?>
	<div class="custom-field-row <?php echo esc_attr( $classes ); ?>">
		<p><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></p>
		<p><input id="<?php echo esc_attr( $id ); ?>" type="text" name="<?php echo esc_attr( $name ); ?>" value="<?php echo $value; ?>"/></p>
		<p class="description"><?php echo esc_html( $description ); ?></p>
	</div>
	<?php
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
		'title'         => __( 'Program Outcomes', 'muext-engagement' ),
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
		'type'    => 'textarea',
		// 'options' => array( 'textarea_rows' => 10, ),
		'save_field' => false, // Disables the saving of this field.
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
		$object_id = 'fake-objectsub-id';
	} else {
		$object_id = absint( $atts['post_id'] );
	}

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
//add_shortcode( 'cmb-frontend', 'muext_frontend_form_submission_shortcode' );


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
	
	//instantiate post data array 
	$post_data = array();
	
	// Get CMB2 metabox object
	$metabox_id = 'program_information';
	if ( ! isset( $_POST['object_id'] ) ) {
		$object_id = 'fake-objectsub-id';
	} else {
		$object_id = absint( $_POST['object_id'] );
		$post_data['ID'] = $object_id;
		$post_data['post_status'] = 'publish';
	}

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
	$post_data['post_title']   = $sanitized_values['_muext_title'];
	unset( $sanitized_values['_muext_title'] );
	
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
	
	// Try to upload the featured image
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
		
    }
	
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
 
        wp_set_object_terms( $post_id, $set_the_terms , $taxonomy );
 
    } elseif ( $get_the_terms && ! is_wp_error( $get_the_terms ) ) {
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
