<?php
/**
 * @package MUExt_Engagement
 * @wordpress-plugin
 * Plugin Name:       MU Extension Engagement
 * Version:           1.0.0
 * Description:       Creates a new post type for recording engagement efforts.
 * Author:            dcavins
 * Text Domain:       muext-engagement
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * GitHub Plugin URI: https://github.com/careshub/muext-engagement
 * @copyright 2017 CARES, University of Missouri
 */

namespace MU_Ext_Engagement;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/*----------------------------------------------------------------------------*
 * Public-Facing Functionality
 *----------------------------------------------------------------------------*/

$basepath = plugin_dir_path( __FILE__ );

// Load the custom metaboxes library
require_once( $basepath . 'includes/cmb2/init.php' );

// Helper functions
require_once( $basepath . 'includes/functions.php' );

// Helper functions
require_once( $basepath . 'includes/template-tags.php' );

// The main class
require_once( $basepath . 'public/public.php' );

// The Custom Post Type and Taxonomy class
require_once( $basepath . 'includes/cpt-taxonomies.php' );

// Admin and dashboard functionality
if ( is_admin() && ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX ) ) {
	require_once( $basepath . 'admin/admin.php' );
}

/**
 * Helper function.
 * @return Fully-qualified URI to the root of the plugin.
 */
function get_plugin_base_uri() {
	return plugin_dir_url( __FILE__ );
}

/**
 * Helper function.
 * @return Fully-qualified URI to the root of the plugin.
 */
function get_plugin_base_path() {
	return trailingslashit( dirname( __FILE__ ) );
}

function get_plugin_slug() {
	return 'muext-engagement';
}

/**
 * Helper function.
 * @TODO: Update this when you update the plugin's version above.
 *
 * @return string Current version of plugin.
 */
function get_plugin_version() {
	return '1.0.5';
}
