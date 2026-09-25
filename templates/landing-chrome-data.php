<?php
use RiseLandingPages\Frontend\Renderer;
use RiseLandingPages\Settings\Settings;

$rise_settings  = Settings::get();
$rise_page_id   = get_queried_object_id();
$rise_brand     = $rise_settings['brand_name'];
$rise_header    = \RiseLandingPages\Pages::chrome_choice( $rise_page_id, 'header' );
$rise_footer    = \RiseLandingPages\Pages::chrome_choice( $rise_page_id, 'footer' );
$rise_cta       = Renderer::cta_defaults();
$rise_logo      = Settings::logo_image( $rise_settings );
$rise_footer_logo = Settings::logo_image( $rise_settings, true );
$rise_footer_links  = array();
if ( ! empty( $rise_settings['about_url'] ) ) {
	$rise_footer_links[] = array( 'label' => __( 'About', 'rise-landing-pages' ), 'url' => $rise_settings['about_url'] );
}
if ( ! empty( $rise_settings['privacy_url'] ) ) {
	$rise_footer_links[] = array( 'label' => __( 'Privacy Policy', 'rise-landing-pages' ), 'url' => $rise_settings['privacy_url'] );
}
if ( ! empty( $rise_settings['terms_url'] ) ) {
	$rise_footer_links[] = array( 'label' => __( 'Terms & Conditions', 'rise-landing-pages' ), 'url' => $rise_settings['terms_url'] );
}
if ( ! empty( $rise_settings['footer_links'] ) && is_array( $rise_settings['footer_links'] ) ) {
	$rise_footer_links = array_merge( $rise_footer_links, $rise_settings['footer_links'] );
}
