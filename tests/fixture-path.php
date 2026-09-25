<?php
/** Browser test credentials must never be written below the web document root. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit; }

function rise_lp_browser_fixture_path( $name ) {
	return trailingslashit( sys_get_temp_dir() ) . 'rise-landing-pages-' . hash( 'sha256', RISE_LP_PATH ) . '-' . $name . '.json';
}
