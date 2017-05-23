<?php

namespace MU_Ext_Engagement\Public_Facing;

// Load plugin text domain
add_action( 'init', __NAMESPACE__ . '\\load_plugin_textdomain' );

// Load public-facing style sheet and JavaScript.
// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles_scripts' ) );
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
	wp_enqueue_script( \MU_Ext_Engagement\get_plugin_slug() . '-plugin-script', plugins_url( 'js/public.js', __FILE__ ), array( 'jquery' ), \MU_Ext_Engagement\get_plugin_version(), true );
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

