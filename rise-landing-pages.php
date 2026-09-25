<?php
/**
 * Plugin Name: Rise Landing Pages
 * Description: Independent, branded campaign pages with a guided Gutenberg editor.
 * Version: 1.1.80
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Author: Rise
 * Text Domain: rise-landing-pages
 * License: GPL-2.0-or-later
 * Update URI: false
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RISE_LP_VERSION', '1.1.80' );
define( 'RISE_LP_FILE', __FILE__ );
define( 'RISE_LP_PATH', plugin_dir_path( __FILE__ ) );
define( 'RISE_LP_URL', plugin_dir_url( __FILE__ ) );

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'RiseLandingPages\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}
		$relative = substr( $class, strlen( $prefix ) );
		$parts = explode( '\\', $relative );
		foreach ( $parts as $part ) {
			// Autoload requests are strings, so reject path syntax before building a filename.
			if ( ! preg_match( '/\A[A-Za-z_][A-Za-z0-9_]*\z/D', $part ) ) {
				return;
			}
		}
		$file = RISE_LP_PATH . 'src/' . implode( '/', $parts ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

add_action( 'plugins_loaded', static function () { ( new RiseLandingPages\Plugin() )->register(); } );
