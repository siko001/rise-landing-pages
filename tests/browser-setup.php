<?php
/** Create a local browser fixture and a short-lived session; never ship credentials. */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit; }
require_once __DIR__ . '/fixture-path.php';
$admin = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) )[0];
wp_set_current_user( $admin );
$id = RiseLandingPages\Pages::create();
wp_update_post( array( 'ID' => $id, 'post_title' => 'Rise QA campaign', 'post_name' => 'rise-qa-campaign-' . $id, 'post_status' => 'publish' ) );
update_post_meta( $id, '_rise_landing_cta_label', 'Book your appointment' );
update_post_meta( $id, '_rise_landing_booking_url', 'https://example.com/book/' );
$normal = wp_insert_post( array( 'post_title' => 'Rise QA normal page', 'post_type' => 'page', 'post_status' => 'publish', 'post_name' => 'rise-qa-normal-' . $id, 'post_content' => '<!-- wp:paragraph --><p>Ordinary page test.</p><!-- /wp:paragraph -->' ) );
$expires = time() + HOUR_IN_SECONDS;
$token = WP_Session_Tokens::get_instance( $admin )->create( $expires );
$data = array(
	'admin' => $admin, 'token' => $token, 'ids' => array( $id, $normal ), 'baseURL' => home_url(), 'url' => get_permalink( $id ), 'normalURL' => get_permalink( $normal ),
	'cookies' => array(
		array( 'name' => LOGGED_IN_COOKIE, 'value' => wp_generate_auth_cookie( $admin, $expires, 'logged_in', $token ), 'domain' => wp_parse_url( home_url(), PHP_URL_HOST ), 'path' => '/', 'httpOnly' => true, 'sameSite' => 'Lax' ),
		array( 'name' => AUTH_COOKIE, 'value' => wp_generate_auth_cookie( $admin, $expires, 'auth', $token ), 'domain' => wp_parse_url( home_url(), PHP_URL_HOST ), 'path' => ADMIN_COOKIE_PATH, 'httpOnly' => true, 'sameSite' => 'Lax' ),
	),
);
$file = rise_lp_browser_fixture_path( 'browser-fixture' );
file_put_contents( $file, wp_json_encode( $data ), LOCK_EX );
chmod( $file, 0600 );
WP_CLI::success( 'Temporary browser fixture ready, page IDs: ' . $id . ', ' . $normal );
