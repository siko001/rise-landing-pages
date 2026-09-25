<?php
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit; }
require_once __DIR__ . '/fixture-path.php';
$file = rise_lp_browser_fixture_path( 'browser-fixture' );
if ( ! file_exists( $file ) ) { WP_CLI::log( 'No browser fixture.' ); return; }
$data = json_decode( file_get_contents( $file ), true );
$ids = $data['ids'];
$extra = rise_lp_browser_fixture_path( 'browser-extra-ids' );
if ( file_exists( $extra ) ) { $ids = array_merge( $ids, json_decode( file_get_contents( $extra ), true ) ); unlink( $extra ); }
foreach ( array_unique( $ids ) as $id ) { wp_delete_post( (int) $id, true ); }
WP_Session_Tokens::get_instance( $data['admin'] )->destroy( $data['token'] );
unlink( $file );
WP_CLI::success( 'Removed temporary pages and revoked browser test session.' );
