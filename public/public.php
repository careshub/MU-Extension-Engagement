<?php

namespace MU_Ext_Engagement\Public_Facing;

// Load plugin text domain
add_action( 'init', __NAMESPACE__ . '\\load_plugin_textdomain' );

// Use templates provided by the plugin.
add_filter( 'template_include', __NAMESPACE__ . '\\template_loader' );


// Load public-facing style sheet and JavaScript.
add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\enqueue_styles_scripts' );
// add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_scripts' ) );

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
	// wp_enqueue_script( \MU_Ext_Engagement\get_plugin_slug() . '-plugin-script', plugins_url( 'js/public.js', __FILE__ ), array( 'jquery' ), \MU_Ext_Engagement\get_plugin_version(), true );

	// Styles
	if ( is_singular( 'muext_engagement' ) || is_post_type_archive( 'muext_engagement' ) ) {
		wp_enqueue_style( \MU_Ext_Engagement\get_plugin_slug() . '-plugin-style', plugins_url( 'css/public.css', __FILE__ ), array(), \MU_Ext_Engagement\get_plugin_version(), 'all' );
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

	if ( is_singular( 'muext_engagement' ) ) {
		$default_file = 'single-muext_engagement.php';
	} elseif ( is_post_type_archive( 'muext_engagement' ) ) {
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

function get_template_path() {
	return \MU_Ext_Engagement\get_plugin_base_path() . 'public/templates/';
}

