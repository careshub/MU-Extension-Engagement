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
// $plugin_basename = plugin_basename( plugin_dir_path( __DIR__ ) . \MU_Ext_Engagement\get_plugin_slug() . '.php' );
// add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );

// Add sample metaboxes
add_action( 'cmb2_admin_init', __NAMESPACE__ . '\\muext_program_info_meta_box' );

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
		'settings' => '<a href="' . admin_url( 'options-general.php?page=' . \MU_Ext_Engagement\get_plugin_slug() ) . '">' . __( 'Settings', 'muext-engagement' ) . '</a>'
	),
	$links
);

}

/**
 * Define the metabox and field configurations.
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
	) );

	// Regular text field
	$cmb->add_field( array(
		'name'       => __( 'Location', 'muext-engagement' ),
		'desc'       => __( 'Enter an address or the city and state.', 'muext-engagement' ),
		'id'         => $prefix . 'location_text',
		'type'       => 'text',
	) );

	// Regular text field
	$cmb->add_field( array(
		'name'       => __( 'Contact Person Name', 'muext-engagement' ),
		// 'desc'       => __( 'field description (optional)', 'muext-engagement' ),
		'id'         => $prefix . 'contact_name',
		'type'       => 'text',
	) );

	// Email text field
	$cmb->add_field( array(
		'name' => __( 'Contact Person Email', 'muext-engagement' ),
		// 'desc' => __( 'field description (optional)', 'muext-engagement' ),
		'id'   => $prefix . 'contact_email',
		'type' => 'text_email',
	) );

	// Regular text field
	$cmb->add_field( array(
		'name'       => __( 'Contact Person Phone', 'muext-engagement' ),
		// 'desc'       => __( 'field description (optional)', 'muext-engagement' ),
		'id'         => $prefix . 'contact_phone',
		'type'       => 'text',
		'sanitization_cb' => __NAMESPACE__ . '\\telephone_number_sanitization', // custom sanitization callback parameter
	) );

	// URL text field
	$cmb->add_field( array(
		'name' => __( 'Program URL', 'cmb2' ),
		// 'desc' => __( 'field description (optional)', 'muext-engagement' ),
		'id'   => $prefix . 'url',
		'type' => 'text_url',
		// 'protocols' => array('http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'gopher', 'nntp', 'feed', 'telnet'), // Array of allowed protocols
		// 'repeatable' => true,
	) );

	$cmb->add_field( array(
		'name' => esc_html__( 'Date', 'cmb2' ),
		'desc' => esc_html__( 'Select the date that this engagement occurred. If the event spanned more than one day, select the start date.', 'cmb2' ),
		'id'   => $prefix . 'start_date',
		'type' => 'text_date',
		'date_format' => 'Y-m-d',
	) );

	$cmb->add_field( array(
		'name' => esc_html__( 'End Date', 'cmb2' ),
		'desc' => esc_html__( 'If the event spanned more than one day, select the end date.', 'cmb2' ),
		'id'   => $prefix . 'end_date',
		'type' => 'text_date',
		'date_format' => 'Y-m-d',
	) );

	$cmb->add_field( array(
		'name'    => esc_html__( 'Description', 'cmb2' ),
		// 'desc'    => esc_html__( 'field description (optional)', 'cmb2' ),
		'id'      => 'content', // This will be saved as the main post content.
		'type'    => 'wysiwyg',
		'options' => array( 'textarea_rows' => 10, ),
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