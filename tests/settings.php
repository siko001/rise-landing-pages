<?php
/** Settings regression checks: wp eval-file tests/settings.php (plugin active). */

use RiseLandingPages\Settings\Settings;
use RiseLandingPages\Frontend\Renderer;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'Run through WP-CLI.' );
}

$checks       = 0;
$initial_user = get_current_user_id();
$errors       = isset( $GLOBALS['wp_settings_errors'] ) ? $GLOBALS['wp_settings_errors'] : array();
$admin        = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
$defaults     = Settings::defaults();
$fixture      = 0;
$force_defaults = static function () use ( $defaults ) { return $defaults; };
$assert = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	++$checks;
	WP_CLI::log( 'PASS ' . $message );
};
$bad_brand = static function ( $settings ) {
	$settings['heading_font']  = 'Arial; background:url(https://example.com/tracker)';
	$settings['primary_color'] = '</style><script>alert(1)</script>';
	$settings['booking_url']   = 'javascript:alert(1)';
	$settings['content_width'] = 99999;
	return $settings;
};

if ( empty( $admin ) ) {
	WP_CLI::error( 'An administrator is required for settings tests.' );
}

try {
	wp_set_current_user( $admin[0] );
	add_filter( 'pre_option_rise_landing_settings', $force_defaults );
	Settings::register_settings();
	$registered = get_registered_settings();
	$assert( isset( $registered[ Settings::OPTION ] ) && is_callable( $registered[ Settings::OPTION ]['sanitize_callback'] ), 'Brand settings use the native Settings API sanitizer' );
	$assert( false === $registered[ Settings::OPTION ]['show_in_rest'], 'Brand settings do not expose an unrestricted REST write surface' );
	$assert( $defaults === Settings::get(), 'A default profile produces all documented fields' );
	$assert( 'fade' === $defaults['image_animation'], 'Image reveals default to fade unless a site chooses another preset' );
	$assert( 'site' === $defaults['default_header'] && 'site' === $defaults['default_footer'], 'New landing pages default to the site header and footer' );
	$assert( 'minimal' === Settings::sanitize( array( 'default_header' => 'minimal', 'default_footer' => 'hidden' ) )['default_header'] && 'hidden' === Settings::sanitize( array( 'default_header' => 'minimal', 'default_footer' => 'hidden' ) )['default_footer'], 'New page header and footer choices can be saved independently' );
	$assert( 'site' === Settings::sanitize( array( 'default_header' => 'unsafe', 'default_footer' => array( 'site' ) ) )['default_header'] && 'site' === Settings::sanitize( array( 'default_header' => 'unsafe', 'default_footer' => array( 'site' ) ) )['default_footer'], 'Invalid new page defaults fall back to site chrome' );
	$assert( $defaults === Settings::sanitize( array( 'unknown_setting' => 'ignored' ) ), 'Unknown keys are not persisted' );
	$assert( 'tiles' === Settings::sanitize( array( 'image_animation' => 'tiles' ) )['image_animation'], 'The Rise Fitness image reveal can be saved as a site default' );
	$assert( 'fade' === Settings::sanitize( array( 'image_animation' => 'url(unsafe)' ) )['image_animation'], 'Unknown image animations fall back safely to fade' );
	$multi = Settings::sanitize( array(
		'contact_mode' => 'multiple',
		'phone' => '+356 2000 0000',
		'additional_phones' => "+356 2000 0001\n+356 2000 0002",
		'map_url' => 'https://maps.app.goo.gl/single',
		'companies' => array(
			array( 'name' => '<b>Rise One</b>', 'phones' => "+356 1111 1111\n+356 1111 2222", 'email' => 'one@example.com', 'address' => "Street One\nMalta", 'map_url' => 'https://maps.app.goo.gl/one', 'company_details' => 'Parking available' ),
			array( 'name' => 'Rise Two', 'phone' => '+356 2222 2222', 'address' => 'Street Two' ),
			array( 'name' => 'Rise Three', 'address' => 'Street Three' ),
			array( 'name' => '', 'address' => '' ),
			array( 'name' => array( 'Invalid' ), 'email' => 'invalid email', 'map_url' => 'javascript:alert(1)' ),
		),
	) );
	$assert( 'multiple' === $multi['contact_mode'] && 3 === count( $multi['companies'] ) && 'Rise One' === $multi['companies'][0]['name'] && "Street One\nMalta" === $multi['companies'][0]['address'], 'Multiple location contacts are sanitized and blank cards are omitted' );
	$assert( "+356 1111 1111\n+356 1111 2222" === $multi['companies'][0]['phones'] && '+356 2222 2222' === $multi['companies'][1]['phones'], 'Multiple numbers and a previously saved single phone are available per location' );
	$assert( 'https://maps.app.goo.gl/one' === $multi['companies'][0]['map_url'] && 'https://maps.app.goo.gl/single' === $multi['map_url'], 'Google Maps links are saved for single and multiple layouts' );
	$assert( '' === Settings::sanitize( array( 'map_url' => 'javascript:alert(1)' ) )['map_url'], 'Unsafe Maps links are rejected' );
	$assert( '+356 2000 0000' === $multi['phone'], 'Single-location details survive while multiple mode is selected' );
	$assert( 'single' === Settings::sanitize( array( 'contact_mode' => 'invalid' ) )['contact_mode'], 'Invalid contact layout falls back to single location' );
	$assert( 10 === count( Settings::sanitize( array( 'companies' => array_fill( 0, 12, array( 'name' => 'Location' ) ) ) )['companies'] ), 'Location contacts are capped at ten' );
	$rise_settings = $multi;
	$rise_footer = 'landing';
	$rise_brand = 'Rise Physio';
	$rise_footer_logo = '';
	$rise_footer_links = array();
	ob_start();
	include RISE_LP_PATH . 'templates/landing-footer.php';
	$multi_footer = ob_get_clean();
	$assert( 3 === substr_count( $multi_footer, 'class="rise-lp__footer-company"' ) && false !== strpos( $multi_footer, 'Street Three' ) && false !== strpos( $multi_footer, 'https://maps.app.goo.gl/one' ) && false !== strpos( $multi_footer, 'tel:+35611112222' ) && false === strpos( $multi_footer, '+356 2000 0000' ), 'Landing footer shows all three locations with Maps and separate phone links' );
	$rise_settings['contact_mode'] = 'single';
	ob_start();
	include RISE_LP_PATH . 'templates/landing-footer.php';
	$single_footer = ob_get_clean();
	$assert( false !== strpos( $single_footer, '+356 2000 0000' ) && false !== strpos( $single_footer, '+356 2000 0001' ) && false !== strpos( $single_footer, 'https://maps.app.goo.gl/single' ) && false === strpos( $single_footer, 'Street Three' ), 'Switching to single mode shows the saved location, Maps link and phones' );
	$faq_image = array( 'faqMediaMode' => 'section', 'imageUrl' => 'https://example.test/faq.jpg', 'items' => array( array( 'question' => 'Question', 'answer' => 'Answer' ) ) );
	$assert( false !== strpos( Renderer::render( 'rise-landing/faq', $faq_image ), 'data-rise-image-animation="fade"' ), 'FAQ images inherit the site fade default' );
	$tile_brand = static function ( $settings ) { $settings['image_animation'] = 'tiles'; return $settings; };
	add_filter( 'rise_landing_brand_settings', $tile_brand );
	$assert( false !== strpos( Renderer::render( 'rise-landing/faq', $faq_image ), 'data-rise-image-animation="tiles"' ), 'FAQ images inherit the site tile default' );
	$assert( false !== strpos( Renderer::render( 'rise-landing/faq', array_merge( $faq_image, array( 'faqImageAnimation' => 'fade' ) ) ), 'data-rise-image-animation="fade"' ), 'An explicit FAQ animation overrides the site default' );
	remove_filter( 'rise_landing_brand_settings', $tile_brand );

	$valid = Settings::sanitize(
		array(
			'heading_font' => '"Helvetica Neue", Helvetica, Arial, sans-serif',
			'body_font'    => "'IBM Plex Sans', system-ui, sans-serif",
			'booking_url'  => '/book-an-appointment/',
			'brand_name'   => '<strong>Rise Physio</strong>',
			'address'      => "First line\nSecond line",
			'header_cta'   => '0',
		)
	);
	$assert( '"Helvetica Neue", Helvetica, Arial, sans-serif' === $valid['heading_font'], 'Quoted font families and ordinary fallback lists are preserved' );
	$assert( "'IBM Plex Sans', system-ui, sans-serif" === $valid['body_font'], 'Single-quoted font families are supported' );
	$assert( '/book-an-appointment/' === $valid['booking_url'], 'Same-site booking paths are supported' );
	$assert( 'Rise Physio' === $valid['brand_name'] && "First line\nSecond line" === $valid['address'], 'Brand text is stripped of markup while address line breaks survive' );
	$assert( false === $valid['header_cta'], 'Unchecked header CTA is saved as false' );
	$assert( 'white' === Settings::sanitize_color( 'WHITE' ) && '#aabbcc' === Settings::sanitize_color( '#abc' ), 'Outline colours accept named colours and expand shorthand hex safely' );
	foreach ( array( 'transparent', 'madeupcolor', 'white; background:red', 'url(https://example.com)', 'var(--unsafe)', array( 'red' ) ) as $colour ) {
		$assert( '' === Settings::sanitize_color( $colour ), 'Invalid, invisible or executable outline colour input is rejected' );
	}
	$typography = Settings::sanitize( array( 'outline_color' => 'white', 'outline_font' => 'F37Judge-condensed, sans-serif', 'heading_style' => 'italic', 'button_font' => 'Poppins, sans-serif', 'button_style' => 'italic', 'button_weight' => 700, 'button_transform' => 'uppercase' ) );
	$assert( 'white' === $typography['outline_color'] && 'F37Judge-condensed, sans-serif' === $typography['outline_font'], 'Site outline colour and font overrides remain editable' );
	$assert( 'italic' === $typography['heading_style'] && 'italic' === $typography['button_style'] && '700' === $typography['button_weight'] && 'uppercase' === $typography['button_transform'], 'Brand heading and button typography choices are supported' );
	$bad_type = Settings::sanitize( array( 'heading_style' => 'italic; color:red', 'button_weight' => '700; color:red', 'button_transform' => 'url(https://example.com)', 'button_font' => 'Arial; color:red' ) );
	$assert( 'normal' === $bad_type['heading_style'] && '700' === $bad_type['button_weight'] && 'none' === $bad_type['button_transform'] && '' === $bad_type['button_font'], 'Malformed typography options cannot become CSS declarations' );
	$presets = Settings::presets();
	$assert( array( 'generic', 'physio', 'fitness', 'medical' ) === array_keys( $presets ), 'Generic, Physio, Fitness and Medical presets are offered' );
	$assert( '' === $presets['generic']['values']['logo_preset'] && 'physio' === $presets['physio']['values']['logo_preset'] && 'fitness' === $presets['fitness']['values']['logo_preset'] && 'medical' === $presets['medical']['values']['logo_preset'], 'Each Rise preset selects its default logo and Generic clears the preset logo' );
	$assert( is_file( RISE_LP_PATH . 'assets/logos/rise-physio.png' ) && is_file( RISE_LP_PATH . 'assets/logos/rise-medical.svg' ) && false !== strpos( Settings::preset_logo_url( 'physio' ), 'assets/logos/rise-physio.png' ) && false !== strpos( Settings::preset_logo_url( 'medical' ), 'assets/logos/rise-medical.svg' ), 'Physio and Medical preset logos are bundled with the plugin' );
	$assert( 'https://risefitness.mt/wp-content/uploads/2024/10/RISE-Fitness-Red-1.svg' === Settings::preset_logo_url( 'fitness' ) && '' === Settings::preset_logo_url( 'unknown' ), 'Fitness uses the supplied SVG URL and unknown logos have no fallback' );
	$physio_logo_settings = Settings::sanitize( $presets['physio']['values'] );
	$assert( 'physio' === $physio_logo_settings['logo_preset'] && false !== strpos( Settings::logo_image( $physio_logo_settings ), 'assets/logos/rise-physio.png' ) && false !== strpos( Settings::logo_image( $physio_logo_settings, true ), 'assets/logos/rise-physio.png' ), 'The Physio preset logo appears in both landing header and footer' );
	$assert( '' === Settings::sanitize( array( 'logo_preset' => 'javascript:alert(1)' ) )['logo_preset'], 'An unknown preset logo is rejected' );
	$assert( '#61FFD6' === $presets['physio']['values']['primary_color'] && 'normal' === $presets['physio']['values']['heading_style'] && '#000000' === $presets['physio']['values']['background_color'], 'Physio preset uses mint, dark backgrounds and normal Judge headings' );
	$assert( '#EC1C2B' === $presets['fitness']['values']['primary_color'] && 'italic' === $presets['fitness']['values']['heading_style'] && 'F37Judge-condensed, sans-serif' === $presets['fitness']['values']['button_font'] && 'tiles' === $presets['fitness']['values']['image_animation'], 'Fitness preset uses red, italic condensed Judge typography and the tile image reveal' );
	$assert( '#002E75' === $presets['medical']['values']['primary_color'] && '#FFFFFF' === $presets['medical']['values']['background_color'] && 'italic' === $presets['medical']['values']['button_style'], 'Medical preset uses navy, a light background and italic button labels' );
	$assert( '' === $presets['generic']['values']['body_font'] && '' === $presets['generic']['values']['heading_font'], 'Generic preset retains inherited typography' );
	$assert( array( 1860 ) === array_values( array_unique( array_column( array_column( $presets, 'values' ), 'content_width' ) ) ), 'Every preset sets maximum content width to 1860 pixels' );
	$assert( __( 'Book an appointment', 'rise-landing-pages' ) === $presets['physio']['values']['cta_label'] && __( 'Book Now', 'rise-landing-pages' ) === $presets['fitness']['values']['cta_label'], 'Physio and Fitness presets set their booking button labels' );
	$assert( __( 'BOOK MEDICAL IMAGING', 'rise-landing-pages' ) === $presets['medical']['values']['cta_label'] && 'https://risephysio.uk1.cliniko.com/bookings?business_id=1916555807957194716' === $presets['medical']['values']['booking_url'], 'Medical preset sets its booking label and Cliniko link' );
	$physio_locations = $presets['physio']['contact']['companies'];
	$assert( 'multiple' === $presets['physio']['contact']['mode'] && array( 'Sliema', 'Qormi', 'Balzan' ) === array_column( $physio_locations, 'name' ), 'Physio preset offers all three locations in the requested order' );
	$assert( 'https://maps.app.goo.gl/ootXoKAXuSmArmsLA' === $physio_locations[0]['map_url'] && 'https://maps.app.goo.gl/czLrS6UJ771XG7wC7' === $physio_locations[1]['map_url'] && 'https://maps.app.goo.gl/cJWZu5QtTW5bC7mh9' === $physio_locations[2]['map_url'], 'Physio locations use the supplied Maps link destinations' );
	$assert( '(+356) 7952 5235' === $physio_locations[0]['phones'] && '(+356) 7952 5235' === $physio_locations[1]['phones'] && '(+356) 7952 5280' === $physio_locations[2]['phones'] && false !== strpos( $physio_locations[2]['company_details'], 'Level 2' ), 'Physio preset has each location phone and the Balzan entrance note' );
	$assert( 'single' === $presets['fitness']['contact']['mode'] && 'https://maps.app.goo.gl/q1tpWDLgFPGDr2JbA' === $presets['fitness']['contact']['single']['map_url'] && '(+356) 79525235' === $presets['fitness']['contact']['single']['phone'], 'Fitness preset offers its one location and supplied Maps link' );
	$assert( ! isset( $presets['medical']['contact'] ) && ! isset( $presets['generic']['contact'] ), 'Medical and Generic presets leave current contact details in place' );
	$assert( false !== strpos( $presets['physio']['links']['privacy_url'], 'privacy-policy' ) && false !== strpos( $presets['physio']['links']['terms_url'], 'terms' ) && false !== strpos( $presets['physio']['links']['about_url'], 'about' ), 'Physio preset provides privacy, terms and about links' );
	$assert( false !== strpos( $presets['fitness']['links']['terms_url'], 'terms-and-conditions' ) && false !== strpos( $presets['fitness']['links']['about_url'], 'about' ), 'Fitness preset uses its published terms page and About section or page' );
	$assert( array( 'Facebook', 'Instagram' ) === array_column( $presets['physio']['links']['footer_links'], 'label' ) && 'https://www.facebook.com/wearerisephysio/' === $presets['physio']['links']['footer_links'][0]['url'] && 'https://www.instagram.com/wearerisefitness/' === $presets['fitness']['links']['footer_links'][1]['url'], 'Physio and Fitness presets include their social footer links' );
	$assert( ! isset( $presets['medical']['links'] ) && ! isset( $presets['generic']['links'] ), 'Medical and Generic presets leave current links in place' );
	$ordered = Settings::sanitize( array( 'contact_mode' => 'multiple', 'companies' => array( $physio_locations[2], $physio_locations[0], $physio_locations[1] ) ) );
	$assert( array( 'Balzan', 'Sliema', 'Qormi' ) === array_column( $ordered['companies'], 'name' ), 'Saved location order follows the submitted card order' );
	foreach ( array( 'physio', 'fitness', 'medical' ) as $brand ) {
		$preset = $presets[ $brand ]['values'];
		$sanitized = Settings::sanitize( $preset );
		$expected_weight = 'fitness' === $brand ? '500' : '700';
		$assert( 0 === $sanitized['button_radius'] && 'uppercase' === $sanitized['button_transform'] && $expected_weight === $sanitized['button_weight'] && 'Poppins, "Poppins Placeholder", sans-serif' === $sanitized['body_font'], $brand . ' preset uses square, uppercase buttons and site-owned Poppins body fonts' );
		$assert( ! isset( $preset['logo_id'] ) && ! isset( $preset['phone'] ), $brand . ' preset keeps Media Library IDs and contact fields outside its styling values' );
	}
	$assert( $defaults === Settings::get(), 'Offering and validating presets does not overwrite the existing saved profile' );
	foreach ( array( 'Arial; color:red', 'url(https://example.com/font)', 'Arial</style><script>alert(1)</script>', 'var(--other-font)', array( 'Arial' ) ) as $font ) {
		$result = Settings::sanitize( array( 'heading_font' => $font ) );
		$assert( '' === $result['heading_font'], 'Malformed or executable font input is rejected' );
	}

	$unsafe = Settings::sanitize(
		array(
			'primary_color'   => 'red; color:black',
			'booking_url'     => 'javascript:alert(1)',
			'privacy_url'     => 'data:text/html,unsafe',
			'logo_id'         => PHP_INT_MAX,
			'content_width'   => -500,
			'section_spacing' => 99999,
			'card_radius'     => -50,
			'button_radius'   => 99999,
			'email'           => 'invalid email',
		)
	);
	$assert( $defaults['primary_color'] === $unsafe['primary_color'], 'Invalid colour input falls back to a safe colour' );
	$assert( '' === $unsafe['booking_url'] && '' === $unsafe['privacy_url'], 'Executable and data URL protocols are rejected' );
	$assert( 0 === $unsafe['logo_id'], 'Missing media cannot be assigned as a brand logo' );
	$assert( 640 === $unsafe['content_width'] && 200 === $unsafe['section_spacing'] && 0 === $unsafe['card_radius'] && 999 === $unsafe['button_radius'], 'Layout values are clamped to their documented bounds' );
	$assert( '' === $unsafe['email'], 'An invalid email address is removed' );

	$fixture = wp_insert_attachment( array( 'post_title' => 'Rise temporary media validation', 'post_mime_type' => 'application/pdf', 'post_status' => 'inherit' ), '', 0, true );
	$assert( ! is_wp_error( $fixture ), 'Temporary non-image media fixture created' );
	$media = Settings::sanitize( array( 'logo_id' => $fixture, 'alternate_logo_id' => $fixture ) );
	$assert( 0 === $media['logo_id'] && 0 === $media['alternate_logo_id'], 'Non-image attachments are rejected for both logo fields' );

	$links = Settings::sanitize(
		array(
			'footer_links' => array(
				array( 'label' => '<b>Contact us</b>', 'url' => '/contact/' ),
				array( 'label' => 'Unsafe', 'url' => 'javascript:alert(1)' ),
				array( 'label' => '', 'url' => 'https://example.com' ),
				array( 'label' => array( 'Nested' ), 'url' => 'https://example.com' ),
				array( 'label' => 'Incomplete' ),
				'not an array',
			),
		)
	);
	$assert( array( array( 'label' => 'Contact us', 'url' => '/contact/' ) ) === $links['footer_links'], 'Footer links require a plain label and safe URL and reject malformed rows' );
	$many = Settings::sanitize( array( 'footer_links' => array_fill( 0, 50, array( 'label' => 'Link', 'url' => 'https://example.com/' ) ) ) );
	$assert( 20 === count( $many['footer_links'] ), 'Additional footer links are capped at twenty' );

	add_filter( 'rise_landing_brand_settings', $bad_brand );
	$filtered = Settings::get();
	$css      = Settings::css_variables();
	$assert( '' === $filtered['heading_font'] && '' === $filtered['booking_url'] && 1920 === $filtered['content_width'], 'Developer brand overrides are sanitized before use' );
	$assert( false === strpos( $css, '<' ) && false === strpos( $css, 'url(' ) && false !== strpos( $css, '--rise-font-heading:inherit;' ), 'Scoped style declarations cannot contain markup or font URL injection' );
	$assert( 20 === substr_count( $css, '--rise-' ) && false !== strpos( $css, '--rise-content-width:1920px;' ), 'All twenty design properties use the agreed names and units' );
	$assert( false !== strpos( $css, '--rise-outline-color:' . $defaults['primary_color'] . ';' ) && false !== strpos( $css, '--rise-font-outline:inherit;' ), 'Blank outline colour and font fall back to the primary colour and heading font' );
	remove_filter( 'rise_landing_brand_settings', $bad_brand );

	Settings::enqueue_assets( 'edit.php' );
	$assert( ! wp_style_is( 'rise-landing-settings', 'enqueued' ) && ! wp_script_is( 'rise-landing-settings', 'enqueued' ), 'Settings assets do not load on ordinary administration screens' );
	Settings::enqueue_assets( 'rise-landing-pages_page_rise-landing-settings' );
	$assert( wp_style_is( 'rise-landing-settings', 'enqueued' ) && wp_script_is( 'rise-landing-settings', 'enqueued' ), 'Settings screen loads its styling and native media controls' );

	wp_set_current_user( 0 );
	$assert( $defaults === Settings::sanitize( array( 'brand_name' => 'Unauthorized change' ) ), 'Users without edit_pages cannot replace the settings profile' );
	WP_CLI::success( $checks . ' settings assertions passed.' );
} finally {
	remove_filter( 'pre_option_rise_landing_settings', $force_defaults );
	remove_filter( 'rise_landing_brand_settings', $bad_brand );
	wp_set_current_user( $admin[0] );
	if ( $fixture && ! is_wp_error( $fixture ) ) {
		wp_delete_attachment( $fixture, true );
	}
	$GLOBALS['wp_settings_errors'] = $errors;
	wp_set_current_user( $initial_user );
}
