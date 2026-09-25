<?php
/** Temporary campaign variants for real browser verification. */
use RiseLandingPages\Pages;
use RiseLandingPages\Blocks\Registry;
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit; }
require_once __DIR__ . '/fixture-path.php';
$file = rise_lp_browser_fixture_path( 'browser-fixture' );
$data = json_decode( file_get_contents( $file ), true );
wp_set_current_user( $data['admin'] );
$images = get_posts( array( 'post_type' => 'attachment', 'post_mime_type' => 'image', 'posts_per_page' => 1, 'fields' => 'ids' ) );
foreach ( array( 'site', 'overlay', 'video', 'stacked' ) as $variant ) {
	$id = Pages::create();
	$data['ids'][] = $id;
	$blocks = parse_blocks( Registry::default_content() );
	$hero = array( 'heading' => 'Built for [outline]your next step[/outline]', 'imageId' => $images ? $images[0] : 0, 'imageUrl' => $images ? wp_get_attachment_url( $images[0] ) : '', 'imageAlt' => 'Existing site image used for local layout verification', 'focalX' => 25, 'focalY' => 70, 'layout' => 'site' === $variant ? 'split' : ( 'stacked' === $variant ? 'stacked' : 'overlay' ), 'minHeight' => 600 );
	if ( 'video' === $variant ) { $hero = array_merge( $hero, array( 'imageId' => 0, 'imageUrl' => RISE_LP_URL . 'test-results/fixture-video.mp4', 'mediaType' => 'video', 'videoMuted' => true, 'videoAutoplay' => true, 'videoLoop' => true, 'videoControls' => true ) ); }
	$blocks[0]['attrs'] = $hero;
	$service_type = WP_Block_Type_Registry::get_instance()->get_registered( 'rise-landing/services' );
	$items = $service_type->attributes['items']['default'];
	$blocks[1]['attrs'] = array( 'displayMode' => 'slider', 'items' => array_merge( $items, $items ) );
	$blocks[4]['attrs'] = array( 'singleOpen' => true, 'firstOpen' => true );
	$blocks[] = array( 'blockName' => 'rise-landing/spacer', 'attrs' => array( 'height' => 80, 'mobileHeight' => 24 ), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() );
	wp_update_post( array( 'ID' => $id, 'post_title' => 'Rise QA ' . $variant, 'post_name' => 'rise-qa-' . $variant . '-' . $id, 'post_content' => wp_slash( serialize_blocks( $blocks ) ), 'post_status' => 'publish' ) );
	update_post_meta( $id, '_rise_landing_layout', 'site' === $variant ? 'site' : 'standalone' );
	update_post_meta( $id, '_rise_landing_booking_url', 'https://example.com/book/' );
	$data['variants'][ $variant ] = get_permalink( $id );
}
file_put_contents( $file, wp_json_encode( $data ) );
WP_CLI::success( 'Four temporary layout variants prepared.' );
