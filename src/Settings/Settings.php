<?php
/**
 * Per-installation brand settings and their administration screen.
 *
 * @package RiseLandingPages
 */

namespace RiseLandingPages\Settings;

defined( 'ABSPATH' ) || exit;

final class Settings {

	const OPTION = 'rise_landing_settings';
	const CAPABILITY = 'edit_pages';

	/** Register only the hooks needed by the settings screen. */
	public static function register() {
		add_action( 'admin_init', array( self::class, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_assets' ) );
		add_filter( 'option_page_capability_' . self::OPTION, static function () { return self::CAPABILITY; } );
	}

	/** @return array<string,mixed> Complete, theme-independent defaults. */
	public static function defaults() {
		return array(
			'brand_name'         => 'Rise',
			'logo_id'            => 0,
			'alternate_logo_id'  => 0,
			'logo_preset'        => '',
			'primary_color'     => '#006B64',
			'secondary_color'   => '#E7F2EF',
			'background_color'  => '#FFFFFF',
			'surface_color'     => '#F5F8F7',
			'text_color'        => '#172D2A',
			'muted_color'       => '#52635F',
			'button_text_color' => '#FFFFFF',
			'outline_color'     => '',
			'heading_font'      => '',
			'heading_style'     => 'normal',
			'body_font'         => '',
			'button_font'       => '',
			'button_style'      => 'normal',
			'button_weight'     => '700',
			'button_transform'  => 'none',
			'button_motion'     => 'slide',
			'image_animation'   => 'fade',
			'outline_font'      => '',
			'content_width'     => 1200,
			'section_spacing'   => 88,
			'card_radius'       => 0,
			'button_radius'     => 0,
			'default_header'    => 'site',
			'default_footer'    => 'site',
			'cta_label'         => __( 'Book an appointment', 'rise-landing-pages' ),
			'booking_url'       => '',
			'phone'             => '',
			'additional_phones' => '',
			'email'             => '',
			'address'           => '',
			'map_url'           => '',
			'company_details'   => '',
			'contact_mode'      => 'single',
			'companies'         => array(),
			'privacy_url'       => '',
			'terms_url'         => '',
			'about_url'         => '',
			'header_cta'        => true,
			'footer_links'      => array(),
		);
	}

	/** Optional form presets. Nothing is applied to saved options automatically. */
	public static function presets() {
		$keys = array( 'brand_name', 'logo_preset', 'primary_color', 'secondary_color', 'background_color', 'surface_color', 'text_color', 'muted_color', 'button_text_color', 'outline_color', 'heading_font', 'heading_style', 'body_font', 'button_style', 'button_weight', 'button_transform', 'button_motion', 'image_animation', 'outline_font', 'card_radius', 'button_radius' );
		$generic = array_intersect_key( self::defaults(), array_flip( $keys ) );
		$generic['content_width'] = 1860;
		$common = array(
			'body_font'        => 'Poppins, "Poppins Placeholder", sans-serif',
			'button_weight'    => '700',
			'button_transform' => 'uppercase',
			'button_radius'    => 0,
			'button_motion'    => 'slide',
			'outline_font'     => '',
			'outline_color'    => '',
		);
		$dark = array(
			'background_color' => '#000000',
			'surface_color'    => '#151515',
			'text_color'       => '#FFFFFF',
			'muted_color'      => '#BCBCBC',
		);
		return array(
			'generic' => array( 'label' => __( 'Generic / inherit', 'rise-landing-pages' ), 'values' => array_merge( $generic, array( 'button_motion' => 'none' ) ) ),
			'physio' => array(
				'label' => __( 'Rise Physio', 'rise-landing-pages' ),
				'values' => array_merge( $generic, $common, $dark, array(
					'brand_name' => 'Rise Physio', 'logo_preset' => 'physio', 'primary_color' => '#61FFD6', 'secondary_color' => '#17352D', 'button_text_color' => '#000000',
					'cta_label' => __( 'Book an appointment', 'rise-landing-pages' ),
					'heading_font' => 'F37Judge, sans-serif', 'heading_style' => 'normal',
					'button_font' => 'Poppins, "Poppins Placeholder", sans-serif', 'button_style' => 'normal',
				) ),
				'contact' => array(
					'mode' => 'multiple',
					'companies' => array(
						array( 'name' => 'Sliema', 'address' => "Level -2, The Imperial Complex 1,\nRudolph Street, Tas-Sliema SLM 1279", 'map_url' => 'https://maps.app.goo.gl/ootXoKAXuSmArmsLA', 'phones' => '(+356) 7952 5235' ),
						array( 'name' => 'Qormi', 'address' => "Level 2, ORA Clinic, Rise Physio+,\n455 Triq l-Imdina, Qormi", 'map_url' => 'https://maps.app.goo.gl/czLrS6UJ771XG7wC7', 'phones' => '(+356) 7952 5235' ),
						array( 'name' => 'Balzan', 'address' => "Rise Physio+,\nPerformance Science Facility\nMediterranean College of Sport\n50 Triq Ix Xorrox\nBirkirkara", 'map_url' => 'https://maps.app.goo.gl/cJWZu5QtTW5bC7mh9', 'phones' => '(+356) 7952 5280', 'company_details' => 'Entrance from Level 2 in Lift from Car Park' ),
					),
				),
				'links' => array(
					'privacy_url' => self::preset_page_url( 'risephysio', array( 'privacy-policy' ), 'https://risephysio.mt/privacy-policy/', true ),
					'terms_url' => self::preset_page_url( 'risephysio', array( 'terms-conditions', 'terms-and-conditions' ), 'https://risephysio.mt/terms-conditions/' ),
					'about_url' => self::preset_page_url( 'risephysio', array( 'about-us', 'about' ), 'https://risephysio.mt/about-us/' ),
					'footer_links' => array(
						array( 'label' => 'Facebook', 'url' => 'https://www.facebook.com/wearerisephysio/' ),
						array( 'label' => 'Instagram', 'url' => 'https://www.instagram.com/wearerisephysio/' ),
					),
				),
			),
			'fitness' => array(
				'label' => __( 'Rise Fitness', 'rise-landing-pages' ),
				'values' => array_merge( $generic, $common, $dark, array(
					'brand_name' => 'Rise Fitness', 'logo_preset' => 'fitness', 'primary_color' => '#EC1C2B', 'secondary_color' => '#290B0D', 'button_text_color' => '#000000',
					'cta_label' => __( 'Book Now', 'rise-landing-pages' ),
					'heading_font' => 'F37Judge-condensed, sans-serif', 'heading_style' => 'italic',
					'button_font' => 'F37Judge-condensed, sans-serif', 'button_style' => 'italic', 'button_weight' => '500',
					'image_animation' => 'tiles',
				) ),
				'contact' => array(
					'mode' => 'single',
					'single' => array(
						'address' => "Rise Fitness and Performance\nLevel 2, Mediterranean College of Sport\n50, Triq Ix-Xorrox, Birkirkara",
						'map_url' => 'https://maps.app.goo.gl/q1tpWDLgFPGDr2JbA',
						'phone' => '(+356) 79525235',
					),
				),
				'links' => array(
					'privacy_url' => self::preset_page_url( 'risefitness', array( 'privacy-policy' ), 'https://risefitness.mt/privacy-policy/', true ),
					'terms_url' => self::preset_page_url( 'risefitness', array( 'terms-and-conditions', 'terms-conditions' ), 'https://risefitness.mt/terms-and-conditions/' ),
					'about_url' => self::preset_page_url( 'risefitness', array( 'about' ), 'https://risefitness.mt/#about', false, true ),
					'footer_links' => array(
						array( 'label' => 'Facebook', 'url' => 'https://www.facebook.com/risefitnessmt/' ),
						array( 'label' => 'Instagram', 'url' => 'https://www.instagram.com/wearerisefitness/' ),
					),
				),
			),
			'medical' => array(
				'label' => __( 'Rise Medical', 'rise-landing-pages' ),
				'values' => array_merge( $generic, $common, array(
					'brand_name' => 'Rise Medical', 'logo_preset' => 'medical', 'primary_color' => '#002E75', 'secondary_color' => '#E8EEF7', 'background_color' => '#FFFFFF',
					'cta_label' => __( 'BOOK MEDICAL IMAGING', 'rise-landing-pages' ), 'booking_url' => 'https://risephysio.uk1.cliniko.com/bookings?business_id=1916555807957194716',
					'surface_color' => '#F3F5F8', 'text_color' => '#111111', 'muted_color' => '#595959', 'button_text_color' => '#FFFFFF',
					'heading_font' => 'F37Judge-condensed, sans-serif', 'heading_style' => 'normal',
					'button_font' => 'Poppins, "Poppins Placeholder", sans-serif', 'button_style' => 'italic', 'button_motion' => 'none',
				) ),
			),
		);
	}

	/** Resolve only bundled assets or the supplied Rise Fitness artwork. */
	public static function preset_logo_url( $preset ) {
		switch ( $preset ) {
			case 'physio':
				return RISE_LP_URL . 'assets/logos/rise-physio.png';
			case 'fitness':
				return 'https://risefitness.mt/wp-content/uploads/2024/10/RISE-Fitness-Red-1.svg';
			case 'medical':
				return RISE_LP_URL . 'assets/logos/rise-medical.svg';
			default:
				return '';
		}
	}

	/** The Media Library choice takes priority over the preset artwork. */
	public static function logo_url( array $settings, $footer = false ) {
		$id = $footer && ! empty( $settings['alternate_logo_id'] ) ? $settings['alternate_logo_id'] : ( $settings['logo_id'] ?? 0 );
		if ( $id ) {
			$url = wp_get_attachment_image_url( absint( $id ), 'medium' );
			if ( $url ) {
				return $url;
			}
		}
		return self::preset_logo_url( $settings['logo_preset'] ?? '' );
	}

	/** Render either a responsive Media Library image or the preset logo. */
	public static function logo_image( array $settings, $footer = false ) {
		$id = $footer && ! empty( $settings['alternate_logo_id'] ) ? $settings['alternate_logo_id'] : ( $settings['logo_id'] ?? 0 );
		$class = 'rise-lp__logo' . ( $footer ? ' rise-lp__logo--footer' : '' );
		$attributes = array( 'class' => $class, 'alt' => $settings['brand_name'], 'loading' => $footer ? 'lazy' : 'eager', 'decoding' => 'async' );
		if ( $id ) {
			$image = wp_get_attachment_image( absint( $id ), 'medium', false, $attributes );
			if ( $image ) {
				return $image;
			}
		}
		$url = self::preset_logo_url( $settings['logo_preset'] ?? '' );
		if ( 'fitness' === ( $settings['logo_preset'] ?? '' ) ) {
			$class .= ' rise-lp__logo--preset-fitness';
		}
		return $url ? '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" alt="' . esc_attr( $settings['brand_name'] ) . '" loading="' . ( $footer ? 'lazy' : 'eager' ) . '" decoding="async">' : '';
	}

	/** Prefer a published page on the matching Rise site, then the supplied public URL. */
	private static function preset_page_url( $brand, array $paths, $fallback, $privacy = false, $home_anchor = false ) {
		if ( ! function_exists( 'home_url' ) || ! function_exists( 'wp_parse_url' ) ) {
			return $fallback;
		}
		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$host = strtolower( preg_replace( '/[^a-z0-9]/i', '', (string) $host ) );
		if ( false === strpos( $host, $brand ) ) {
			return $fallback;
		}
		if ( $privacy && function_exists( 'get_privacy_policy_url' ) ) {
			$url = get_privacy_policy_url();
			if ( $url ) {
				return $url;
			}
		}
		if ( function_exists( 'get_page_by_path' ) ) {
			foreach ( $paths as $path ) {
				$page = get_page_by_path( $path );
				if ( $page && 'publish' === get_post_status( $page ) ) {
					$url = get_permalink( $page );
					if ( $url ) {
						return $url;
					}
				}
			}
		}
		return $home_anchor ? home_url( '/#about' ) : $fallback;
	}

	/** Return the effective brand profile, including validated filter overrides. */
	public static function get() {
		$stored   = get_option( self::OPTION, array() );
		$settings = self::sanitize_values( is_array( $stored ) ? $stored : array() );
		/**
		 * Filter this site's landing-page branding. Invalid values fall back to defaults.
		 *
		 * @param array $settings Effective brand settings.
		 */
		$filtered = apply_filters( 'rise_landing_brand_settings', $settings );
		return self::sanitize_values( is_array( $filtered ) ? array_merge( $settings, $filtered ) : $settings );
	}

	/** Pick the separator mark that belongs to the active Rise brand. */
	public static function separator_brand_mark( $settings = null ) {
		$settings = is_array( $settings ) ? $settings : self::get();
		$name = strtolower( (string) ( $settings['brand_name'] ?? '' ) );
		$primary = strtoupper( (string) ( $settings['primary_color'] ?? '' ) );
		if ( false !== strpos( $name, 'physio' ) || '#61FFD6' === $primary ) {
			return RISE_LP_URL . 'assets/rise-physio-icon.png';
		}
		if ( false !== strpos( $name, 'medical' ) || '#002E75' === $primary ) {
			return RISE_LP_URL . 'assets/rise-medical-icon.png';
		}
		return RISE_LP_URL . 'assets/rise-mark.svg';
	}

	/** Safe declaration list, suitable for a style attribute after esc_attr(). */
	public static function css_variables() {
		$settings = self::get();
		$settings['outline_color'] = $settings['outline_color'] ? $settings['outline_color'] : $settings['primary_color'];
		$settings['button_font'] = $settings['button_font'] ? $settings['button_font'] : $settings['body_font'];
		$settings['outline_font'] = $settings['outline_font'] ? $settings['outline_font'] : $settings['heading_font'];
		$map      = array(
			'primary_color'     => '--rise-primary',
			'secondary_color'   => '--rise-secondary',
			'background_color'  => '--rise-background',
			'surface_color'     => '--rise-surface',
			'text_color'        => '--rise-text',
			'muted_color'       => '--rise-muted',
			'button_text_color' => '--rise-button-text',
			'outline_color'     => '--rise-outline-color',
			'heading_font'      => '--rise-font-heading',
			'heading_style'     => '--rise-heading-style',
			'body_font'         => '--rise-font-body',
			'button_font'       => '--rise-font-button',
			'button_style'      => '--rise-button-style',
			'button_weight'     => '--rise-button-weight',
			'button_transform'  => '--rise-button-transform',
			'outline_font'      => '--rise-font-outline',
			'content_width'     => '--rise-content-width',
			'section_spacing'   => '--rise-section-spacing',
			'card_radius'       => '--rise-radius',
			'button_radius'     => '--rise-button-radius',
		);
		$css      = '';
		foreach ( $map as $key => $property ) {
			$value = $settings[ $key ];
			if ( in_array( $key, array( 'heading_font', 'body_font', 'button_font', 'outline_font' ), true ) ) {
				$value = $value ? $value : 'inherit';
			} elseif ( in_array( $key, array( 'content_width', 'section_spacing', 'card_radius', 'button_radius' ), true ) ) {
				$value .= 'px';
			}
			$css .= $property . ':' . $value . ';';
		}
		return $css;
	}

	/** Register settings with the native Settings API save/nonce/capability flow. */
	public static function register_settings() {
		register_setting(
			self::OPTION,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
		foreach ( self::sections() as $section => $definition ) {
			add_settings_section( $section, $definition['title'], '__return_false', 'rise-landing-settings' );
			foreach ( $definition['fields'] as $key => $field ) {
				add_settings_field(
					$key,
					$field['label'],
					array( self::class, 'render_field' ),
					'rise-landing-settings',
					$section,
					array_merge( $field, array( 'key' => $key, 'label_for' => 'rise-setting-' . $key ) )
				);
			}
		}
	}

	/** Settings API callback. Never trust nested or malformed form values. */
	public static function sanitize( $input ) {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return get_option( self::OPTION, self::defaults() );
		}
		$input = is_array( $input ) ? $input : array();
		foreach ( array( 'heading_font', 'body_font', 'button_font', 'outline_font' ) as $key ) {
			if ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) && '' !== trim( (string) $input[ $key ] ) && '' === self::sanitize_font( $input[ $key ] ) ) {
				add_settings_error(
					self::OPTION,
					'rise-invalid-' . $key,
					__( 'A font family was not recognised and has been reset to inherit. Enter font names separated by commas, for example: "Helvetica Neue", Helvetica, Arial, sans-serif.', 'rise-landing-pages' ),
					'warning'
				);
			}
		}
		return self::sanitize_values( $input );
	}

	/** A font-family grammar, deliberately excluding CSS functions and declarations. */
	public static function sanitize_font( $value ) {
		$value = is_scalar( $value ) ? trim( (string) $value ) : '';
		if ( '' === $value || strlen( $value ) > 255 ) {
			return '';
		}
		$family = '(?:"[\p{L}\p{N} _-]+"|\'[\p{L}\p{N} _-]+\'|[\p{L}_-][\p{L}\p{N} _-]*)';
		return preg_match( '/\A' . $family . '(?:\s*,\s*' . $family . ')*\z/u', $value ) ? $value : '';
	}

	/** Shared outline-colour allow-list plus hex, with no executable CSS syntax. */
	public static function sanitize_color( $value ) {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = trim( (string) $value );
		$hex = sanitize_hex_color( $value );
		if ( $hex ) {
			return 4 === strlen( $hex ) ? '#' . $hex[1] . $hex[1] . $hex[2] . $hex[2] . $hex[3] . $hex[3] : $hex;
		}
		$names = explode( ' ', 'white black red blue green yellow orange purple pink cyan magenta gray grey navy teal aqua lime maroon olive silver fuchsia currentcolor' );
		$value = strtolower( $value );
		return in_array( $value, $names, true ) ? $value : '';
	}

	/** Shared sanitation applies to saved options and developer filter results. */
	private static function sanitize_values( array $input ) {
		$defaults = self::defaults();
		$output   = $defaults;
		foreach ( array( 'brand_name', 'cta_label', 'phone' ) as $key ) {
			if ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ) {
				$output[ $key ] = sanitize_text_field( (string) $input[ $key ] );
			}
		}
		foreach ( array( 'address', 'company_details' ) as $key ) {
			if ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ) {
				$output[ $key ] = sanitize_textarea_field( (string) $input[ $key ] );
			}
		}
		if ( isset( $input['additional_phones'] ) && is_scalar( $input['additional_phones'] ) ) {
			$output['additional_phones'] = self::sanitize_phones( $input['additional_phones'] );
		}
		if ( isset( $input['map_url'] ) && is_scalar( $input['map_url'] ) ) {
			$output['map_url'] = self::sanitize_map_url( $input['map_url'] );
		}
		if ( isset( $input['companies'] ) && is_array( $input['companies'] ) ) {
			foreach ( array_slice( $input['companies'], 0, 10 ) as $company ) {
				if ( ! is_array( $company ) ) {
					continue;
				}
				$row = array();
				foreach ( array( 'name', 'email', 'address', 'company_details' ) as $key ) {
					$value = isset( $company[ $key ] ) && is_scalar( $company[ $key ] ) ? (string) $company[ $key ] : '';
					$row[ $key ] = 'email' === $key ? sanitize_email( $value ) : ( in_array( $key, array( 'address', 'company_details' ), true ) ? sanitize_textarea_field( $value ) : sanitize_text_field( $value ) );
				}
				$phones = array_key_exists( 'phones', $company ) ? $company['phones'] : ( $company['phone'] ?? '' );
				$row['phones'] = is_scalar( $phones ) ? self::sanitize_phones( $phones ) : '';
				$row['phone'] = strtok( $row['phones'], "\n" ) ?: '';
				$row['map_url'] = isset( $company['map_url'] ) && is_scalar( $company['map_url'] ) ? self::sanitize_map_url( $company['map_url'] ) : '';
				if ( array_filter( $row, static function ( $value ) { return '' !== $value; } ) ) {
					$output['companies'][] = $row;
				}
			}
		}
		if ( isset( $input['email'] ) && is_scalar( $input['email'] ) ) {
			$output['email'] = sanitize_email( (string) $input['email'] );
		}
		foreach ( array( 'booking_url', 'privacy_url', 'terms_url', 'about_url' ) as $key ) {
			if ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ) {
				$output[ $key ] = esc_url_raw( (string) $input[ $key ], array( 'http', 'https', 'mailto', 'tel' ) );
			}
		}
		foreach ( array( 'logo_id', 'alternate_logo_id' ) as $key ) {
			$id             = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? absint( $input[ $key ] ) : 0;
			$output[ $key ] = $id && wp_attachment_is_image( $id ) ? $id : 0;
		}
		$output['logo_preset'] = isset( $input['logo_preset'] ) && is_scalar( $input['logo_preset'] ) && in_array( (string) $input['logo_preset'], array( 'physio', 'fitness', 'medical' ), true ) ? (string) $input['logo_preset'] : '';
		foreach ( array( 'primary_color', 'secondary_color', 'background_color', 'surface_color', 'text_color', 'muted_color', 'button_text_color' ) as $key ) {
			$color          = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? sanitize_hex_color( (string) $input[ $key ] ) : null;
			$color          = $color ? self::sanitize_color( $color ) : '';
			$output[ $key ] = $color ? $color : $defaults[ $key ];
		}
		$output['outline_color'] = isset( $input['outline_color'] ) ? self::sanitize_color( $input['outline_color'] ) : '';
		foreach ( array( 'heading_font', 'body_font', 'button_font', 'outline_font' ) as $key ) {
			$output[ $key ] = isset( $input[ $key ] ) ? self::sanitize_font( $input[ $key ] ) : '';
		}
		$choices = array(
			'heading_style' => array( 'normal', 'italic' ),
			'button_style' => array( 'normal', 'italic' ),
			'button_weight' => array( '400', '500', '600', '700', '800', '900' ),
			'button_transform' => array( 'none', 'uppercase', 'lowercase', 'capitalize' ),
			'button_motion' => array( 'none', 'slide' ),
			'image_animation' => array( 'fade', 'tiles' ),
			'default_header' => array( 'minimal', 'site', 'hidden' ),
			'default_footer' => array( 'landing', 'site', 'hidden' ),
			'contact_mode' => array( 'single', 'multiple' ),
		);
		foreach ( $choices as $key => $allowed ) {
			$value = isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) ? (string) $input[ $key ] : '';
			$output[ $key ] = in_array( $value, $allowed, true ) ? $value : $defaults[ $key ];
		}
		$ranges = array(
			'content_width'   => array( 640, 1920 ),
			'section_spacing' => array( 0, 200 ),
			'card_radius'     => array( 0, 80 ),
			'button_radius'   => array( 0, 999 ),
		);
		foreach ( $ranges as $key => $range ) {
			if ( isset( $input[ $key ] ) && is_scalar( $input[ $key ] ) && is_numeric( $input[ $key ] ) ) {
				$output[ $key ] = max( $range[0], min( $range[1], (int) $input[ $key ] ) );
			}
		}
		if ( array_key_exists( 'header_cta', $input ) ) {
			$output['header_cta'] = in_array( $input['header_cta'], array( true, 1, '1', 'true', 'on' ), true );
		}
		if ( isset( $input['footer_links'] ) && is_array( $input['footer_links'] ) ) {
			foreach ( array_slice( $input['footer_links'], 0, 20 ) as $link ) {
				if ( ! is_array( $link ) || ! isset( $link['label'], $link['url'] ) || ! is_scalar( $link['label'] ) || ! is_scalar( $link['url'] ) ) {
					continue;
				}
				$label = sanitize_text_field( (string) $link['label'] );
				$url   = esc_url_raw( (string) $link['url'], array( 'http', 'https', 'mailto', 'tel' ) );
				if ( '' !== $label && '' !== $url ) {
					$output['footer_links'][] = array( 'label' => $label, 'url' => $url );
				}
			}
		}
		return $output;
	}

	private static function sanitize_phones( $value ) {
		$lines = preg_split( '/\R/u', sanitize_textarea_field( (string) $value ) );
		$phones = array();
		foreach ( $lines as $line ) {
			$line = trim( $line );
			if ( '' !== $line ) {
				$phones[] = $line;
			}
		}
		return implode( "\n", $phones );
	}

	private static function sanitize_map_url( $value ) {
		$url = esc_url_raw( (string) $value, array( 'http', 'https' ) );
		return preg_match( '~\Ahttps?://~i', $url ) ? $url : '';
	}

	/** Assets are loaded on this plugin's settings screen only. */
	public static function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'rise-landing-settings' ) || ! current_user_can( self::CAPABILITY ) ) {
			return;
		}
		wp_enqueue_media();
		wp_enqueue_style( 'rise-landing-settings', RISE_LP_URL . 'assets/settings.css', array(), RISE_LP_VERSION );
		wp_enqueue_script( 'rise-landing-settings', RISE_LP_URL . 'assets/settings.js', array( 'media-views' ), RISE_LP_VERSION, true );
		wp_localize_script(
			'rise-landing-settings',
			'riseLandingSettings',
			array(
				'mediaTitle' => __( 'Choose a brand logo', 'rise-landing-pages' ),
				'mediaUse'   => __( 'Use this logo', 'rise-landing-pages' ),
				'noLogo'     => __( 'No logo selected.', 'rise-landing-pages' ),
				'linkLabel'  => __( 'Link label', 'rise-landing-pages' ),
				'linkUrl'    => __( 'Link address', 'rise-landing-pages' ),
				'companyAdded' => __( 'A location was added.', 'rise-landing-pages' ),
				'companyRemoved' => __( 'The location was removed.', 'rise-landing-pages' ),
				'companyLabel' => __( 'Location', 'rise-landing-pages' ),
				'locationMoved' => __( 'Location order updated.', 'rise-landing-pages' ),
				'remove'     => __( 'Remove', 'rise-landing-pages' ),
				'added'      => __( 'A footer link row was added.', 'rise-landing-pages' ),
				'removed'    => __( 'The footer link row was removed.', 'rise-landing-pages' ),
				'presets'    => self::presets(),
				'presetLogoUrls' => array( 'physio' => self::preset_logo_url( 'physio' ), 'fitness' => self::preset_logo_url( 'fitness' ), 'medical' => self::preset_logo_url( 'medical' ) ),
				'presetApplied' => __( 'Preset applied to the form. Review the fields below and save to update your landing pages.', 'rise-landing-pages' ),
			)
		);
	}

	/** Render a manageable, native WordPress settings page. */
	public static function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to manage landing-page settings.', 'rise-landing-pages' ) );
		}
		$sections = self::sections();
		?>
		<div class="wrap rise-settings">
			<div class="rise-settings__intro">
				<p class="rise-settings__eyebrow"><?php esc_html_e( 'Rise Landing Pages', 'rise-landing-pages' ); ?></p>
				<h1><?php esc_html_e( 'Your site, your brand', 'rise-landing-pages' ); ?></h1>
				<p><?php esc_html_e( 'Set the shared branding, contact details and booking link for landing pages on this site. Individual pages can override their booking link and button label.', 'rise-landing-pages' ); ?></p>
			</div>
			<?php settings_errors(); ?>
			<form action="options.php" method="post">
				<?php settings_fields( self::OPTION ); ?>
				<div class="rise-settings__layout">
					<nav class="rise-settings__quick-menu" aria-label="<?php esc_attr_e( 'Jump to a setting section', 'rise-landing-pages' ); ?>">
						<h2><?php esc_html_e( 'Quick menu', 'rise-landing-pages' ); ?></h2>
						<a href="#rise-preset"><?php esc_html_e( 'Start with a Rise brand', 'rise-landing-pages' ); ?></a>
						<?php foreach ( $sections as $section => $definition ) : ?>
							<a href="#<?php echo esc_attr( $section ); ?>"><?php echo esc_html( $definition['title'] ); ?></a>
						<?php endforeach; ?>
					</nav>
					<div class="rise-settings__main">
						<details class="rise-settings__section rise-settings-presets" id="rise-preset" name="rise-settings-accordion">
							<summary class="rise-settings__section-heading"><h2><?php esc_html_e( 'Start with a Rise brand', 'rise-landing-pages' ); ?></h2></summary>
							<div class="rise-settings__section-content">
								<p><?php esc_html_e( 'Presets fill brand styling, the default logo and a maximum content width of 1860px. Physio and Fitness also fill locations and links. Each Rise brand sets its booking button text; Medical also sets the booking URL. Other booking URLs stay as entered.', 'rise-landing-pages' ); ?></p>
								<div class="rise-settings-presets__controls">
									<label for="rise-brand-preset"><?php esc_html_e( 'Brand preset', 'rise-landing-pages' ); ?></label>
									<select id="rise-brand-preset" aria-describedby="rise-preset-help">
										<?php foreach ( self::presets() as $key => $preset ) : ?>
											<option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $preset['label'] ); ?></option>
										<?php endforeach; ?>
									</select>
									<button type="button" class="button rise-settings-presets__apply"><?php esc_html_e( 'Apply preset', 'rise-landing-pages' ); ?></button>
								</div>
								<p class="description" id="rise-preset-help"><?php esc_html_e( 'Changes only take effect after saving. Preset fonts must already be loaded by your website; this plugin never downloads or installs fonts.', 'rise-landing-pages' ); ?></p>
								<p class="rise-settings-presets__status" role="status" aria-live="polite"></p>
							</div>
						</details>
						<?php foreach ( $sections as $section => $definition ) : ?>
							<details class="rise-settings__section" id="<?php echo esc_attr( $section ); ?>" name="rise-settings-accordion">
								<summary class="rise-settings__section-heading"><h2><?php echo esc_html( $definition['title'] ); ?></h2></summary>
								<div class="rise-settings__section-content">
									<p class="rise-settings__section-description"><?php echo esc_html( $definition['description'] ); ?></p>
									<table class="form-table" role="presentation"><tbody><?php do_settings_fields( 'rise-landing-settings', $section ); ?></tbody></table>
								</div>
							</details>
						<?php endforeach; ?>
						<?php submit_button( __( 'Save landing-page settings', 'rise-landing-pages' ) ); ?>
					</div>
				</div>
			</form>
		</div>
		<?php
	}

	/** Each field is escaped at output, including values provided by filters. */
	public static function render_field( $args ) {
		// Show the persisted profile, so runtime filters do not overwrite saved choices.
		$stored   = get_option( self::OPTION, array() );
		$settings = self::sanitize_values( is_array( $stored ) ? $stored : array() );
		$key      = $args['key'];
		$value    = $settings[ $key ];
		$id       = 'rise-setting-' . $key;
		$name     = self::OPTION . '[' . $key . ']';
		$type     = isset( $args['type'] ) ? $args['type'] : 'text';
		$help_id  = $id . '-help';
		if ( 'media' === $type ) {
			?>
			<div class="rise-settings-media"<?php echo 'logo_id' === $key ? ' data-logo-role="primary"' : ''; ?>>
				<?php if ( 'logo_id' === $key ) : ?><input type="hidden" id="rise-setting-logo_preset" name="<?php echo esc_attr( self::OPTION ); ?>[logo_preset]" value="<?php echo esc_attr( $settings['logo_preset'] ); ?>"><?php endif; ?>
				<input type="hidden" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" class="rise-settings-media__value">
				<div class="rise-settings-media__preview">
					<?php if ( $value ) : ?>
						<?php echo wp_get_attachment_image( $value, 'medium', false, array( 'alt' => '', 'class' => 'rise-settings-media__image' ) ); ?>
					<?php elseif ( 'logo_id' === $key && self::preset_logo_url( $settings['logo_preset'] ) ) : ?>
						<img src="<?php echo esc_url( self::preset_logo_url( $settings['logo_preset'] ) ); ?>" alt="" class="rise-settings-media__image">
					<?php else : ?>
						<span><?php esc_html_e( 'No logo selected.', 'rise-landing-pages' ); ?></span>
					<?php endif; ?>
				</div>
				<button type="button" class="button rise-settings-media__choose" aria-describedby="<?php echo esc_attr( $help_id ); ?>"><?php esc_html_e( 'Choose logo', 'rise-landing-pages' ); ?></button>
				<button type="button" class="button-link-delete rise-settings-media__remove" <?php echo $value ? '' : 'hidden'; ?>><?php echo esc_html( 'logo_id' === $key ? __( 'Remove custom logo', 'rise-landing-pages' ) : __( 'Remove alternate logo', 'rise-landing-pages' ) ); ?></button>
			</div>
			<?php
		} elseif ( 'select' === $type ) {
			echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $name ) . '" aria-describedby="' . esc_attr( $help_id ) . '">';
			foreach ( $args['choices'] as $choice => $label ) {
				echo '<option value="' . esc_attr( $choice ) . '" ' . selected( (string) $value, (string) $choice, false ) . '>' . esc_html( $label ) . '</option>';
			}
			echo '</select>';
		} elseif ( 'textarea' === $type ) {
			printf( '<textarea id="%1$s" name="%2$s" rows="4" class="large-text" aria-describedby="%3$s">%4$s</textarea>', esc_attr( $id ), esc_attr( $name ), esc_attr( $help_id ), esc_textarea( $value ) );
		} elseif ( 'checkbox' === $type ) {
			printf( '<input type="hidden" name="%1$s" value="0"><label><input type="checkbox" id="%2$s" name="%1$s" value="1" %3$s aria-describedby="%4$s"> %5$s</label>', esc_attr( $name ), esc_attr( $id ), checked( $value, true, false ), esc_attr( $help_id ), esc_html( $args['checkbox_label'] ) );
		} elseif ( 'links' === $type ) {
			self::render_links( $value, $id );
		} elseif ( 'companies' === $type ) {
			self::render_companies( $value, $id );
		} else {
			$extra = '';
			if ( 'number' === $type ) {
				$extra = ' min="' . esc_attr( $args['min'] ) . '" max="' . esc_attr( $args['max'] ) . '" step="1"';
			} elseif ( 'font' === $type ) {
				$type  = 'text';
				$extra = ' placeholder="inherit" maxlength="255"';
			} elseif ( 'url' === $type ) {
				$type  = 'text';
				$extra = ' inputmode="url" placeholder="https://"';
			}
			printf( '<input type="%1$s" id="%2$s" name="%3$s" value="%4$s" class="%5$s" aria-describedby="%6$s"%7$s>', esc_attr( $type ), esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), 'number' === $type ? 'small-text' : 'regular-text', esc_attr( $help_id ), $extra );
			if ( 'number' === $type ) {
				echo ' <span class="rise-settings__unit">' . esc_html__( 'px', 'rise-landing-pages' ) . '</span>';
			}
		}
		if ( ! empty( $args['help'] ) ) {
			echo '<p class="description" id="' . esc_attr( $help_id ) . '">' . esc_html( $args['help'] ) . '</p>';
		}
	}

	private static function render_links( array $links, $id ) {
		?>
		<div class="rise-settings-links" id="<?php echo esc_attr( $id ); ?>">
			<input type="hidden" name="<?php echo esc_attr( self::OPTION ); ?>[footer_links]" value="">
			<div class="rise-settings-links__rows">
				<?php foreach ( $links as $index => $link ) : ?>
					<div class="rise-settings-links__row">
						<input type="text" name="<?php echo esc_attr( self::OPTION . '[footer_links][' . $index . '][label]' ); ?>" value="<?php echo esc_attr( $link['label'] ); ?>" aria-label="<?php esc_attr_e( 'Link label', 'rise-landing-pages' ); ?>" placeholder="<?php esc_attr_e( 'Link label', 'rise-landing-pages' ); ?>">
						<input type="text" inputmode="url" name="<?php echo esc_attr( self::OPTION . '[footer_links][' . $index . '][url]' ); ?>" value="<?php echo esc_attr( $link['url'] ); ?>" aria-label="<?php esc_attr_e( 'Link address', 'rise-landing-pages' ); ?>" placeholder="https://">
						<button type="button" class="button-link-delete rise-settings-links__remove"><?php esc_html_e( 'Remove', 'rise-landing-pages' ); ?></button>
					</div>
				<?php endforeach; ?>
			</div>
			<button type="button" class="button rise-settings-links__add"><?php esc_html_e( 'Add footer link', 'rise-landing-pages' ); ?></button>
			<span class="screen-reader-text rise-settings-links__status" role="status" aria-live="polite"></span>
		</div>
		<?php
	}

	private static function render_companies( array $companies, $id ) {
		?>
		<div class="rise-settings-companies" id="<?php echo esc_attr( $id ); ?>">
			<input type="hidden" name="<?php echo esc_attr( self::OPTION ); ?>[companies]" value="">
			<div class="rise-settings-companies__rows">
				<?php foreach ( $companies as $index => $company ) : ?>
					<?php self::render_company( $index, $company ); ?>
				<?php endforeach; ?>
			</div>
			<template class="rise-settings-companies__template"><?php self::render_company( '__INDEX__', array() ); ?></template>
			<button type="button" class="button rise-settings-companies__add"><?php esc_html_e( 'Add location', 'rise-landing-pages' ); ?></button>
			<span class="screen-reader-text rise-settings-companies__status" role="status" aria-live="polite"></span>
		</div>
		<?php
	}

	private static function render_company( $index, array $company ) {
		$fields = array(
			'name' => array( __( 'Location name', 'rise-landing-pages' ), 'text' ),
			'address' => array( __( 'Location address', 'rise-landing-pages' ), 'textarea' ),
			'map_url' => array( __( 'Google Maps link', 'rise-landing-pages' ), 'url' ),
			'phones' => array( __( 'Phone numbers (one per line)', 'rise-landing-pages' ), 'textarea' ),
			'email' => array( __( 'Email address (optional)', 'rise-landing-pages' ), 'email' ),
			'company_details' => array( __( 'Other details (optional)', 'rise-landing-pages' ), 'textarea' ),
		);
		?>
		<details class="rise-settings-companies__row">
			<summary class="rise-settings-companies__heading">
				<button type="button" class="rise-settings-companies__handle" draggable="true" aria-label="<?php esc_attr_e( 'Drag to reorder location, or use the arrow keys', 'rise-landing-pages' ); ?>" title="<?php esc_attr_e( 'Drag to reorder location, or use the arrow keys', 'rise-landing-pages' ); ?>">⋮⋮</button>
				<strong class="rise-settings-companies__title"><?php echo esc_html( ! empty( $company['name'] ) ? $company['name'] : __( 'Location', 'rise-landing-pages' ) ); ?></strong>
				<span class="rise-settings-companies__chevron" aria-hidden="true"></span>
			</summary>
			<div class="rise-settings-companies__content">
				<button type="button" class="button-link-delete rise-settings-companies__remove"><?php esc_html_e( 'Remove location', 'rise-landing-pages' ); ?></button>
				<div class="rise-settings-companies__fields">
					<?php foreach ( $fields as $key => $field ) : ?>
						<?php $field_id = 'rise-company-' . $index . '-' . $key; ?>
						<label for="<?php echo esc_attr( $field_id ); ?>"><?php echo esc_html( $field[0] ); ?></label>
						<?php if ( 'textarea' === $field[1] ) : ?>
							<textarea id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION . '[companies][' . $index . '][' . $key . ']' ); ?>" rows="3"><?php echo esc_textarea( $company[ $key ] ?? '' ); ?></textarea>
						<?php else : ?>
							<input type="<?php echo esc_attr( 'url' === $field[1] ? 'text' : $field[1] ); ?>"<?php echo 'url' === $field[1] ? ' inputmode="url" placeholder="https://"' : ''; ?> id="<?php echo esc_attr( $field_id ); ?>" name="<?php echo esc_attr( self::OPTION . '[companies][' . $index . '][' . $key . ']' ); ?>" value="<?php echo esc_attr( $company[ $key ] ?? '' ); ?>">
						<?php endif; ?>
					<?php endforeach; ?>
				</div>
			</div>
		</details>
		<?php
	}

	/** Field metadata keeps labels, limits and grouping together. */
	private static function sections() {
		return array(
			'rise-brand' => array(
				'title'       => __( 'Brand identity', 'rise-landing-pages' ),
				'description' => __( 'These details belong to this website only. Configure each Rise site independently.', 'rise-landing-pages' ),
				'fields'      => array(
					'brand_name'        => array( 'label' => __( 'Brand name', 'rise-landing-pages' ), 'help' => __( 'Used as the text logo when no image or preset logo is selected.', 'rise-landing-pages' ) ),
					'logo_id'           => array( 'label' => __( 'Logo', 'rise-landing-pages' ), 'type' => 'media', 'help' => __( 'The brand preset supplies a default logo for the landing header and footer. Choose an image from the Media Library to override it.', 'rise-landing-pages' ) ),
					'alternate_logo_id' => array( 'label' => __( 'Alternate logo', 'rise-landing-pages' ), 'type' => 'media', 'help' => __( 'Optional alternate or inverted logo, available to the landing-page footer.', 'rise-landing-pages' ) ),
				),
			),
			'rise-colours' => array(
				'title'       => __( 'Colours', 'rise-landing-pages' ),
				'description' => __( 'Choose brand colours with enough contrast for comfortable reading, especially button text against the primary colour.', 'rise-landing-pages' ),
				'fields'      => array(
					'primary_color'     => array( 'label' => __( 'Primary colour', 'rise-landing-pages' ), 'type' => 'color', 'help' => __( 'Main buttons, links and brand accents.', 'rise-landing-pages' ) ),
					'secondary_color'   => array( 'label' => __( 'Secondary colour', 'rise-landing-pages' ), 'type' => 'color', 'help' => __( 'Supporting brand accents.', 'rise-landing-pages' ) ),
					'background_color'  => array( 'label' => __( 'Background colour', 'rise-landing-pages' ), 'type' => 'color', 'help' => __( 'The main page background.', 'rise-landing-pages' ) ),
					'surface_color'     => array( 'label' => __( 'Card colour', 'rise-landing-pages' ), 'type' => 'color', 'help' => __( 'Cards and alternate section backgrounds.', 'rise-landing-pages' ) ),
					'text_color'        => array( 'label' => __( 'Main text colour', 'rise-landing-pages' ), 'type' => 'color', 'help' => __( 'Headings and main body text.', 'rise-landing-pages' ) ),
					'muted_color'       => array( 'label' => __( 'Supporting text colour', 'rise-landing-pages' ), 'type' => 'color', 'help' => __( 'Secondary descriptions and supporting information.', 'rise-landing-pages' ) ),
					'button_text_color' => array( 'label' => __( 'Button text colour', 'rise-landing-pages' ), 'type' => 'color', 'help' => __( 'Text on primary buttons.', 'rise-landing-pages' ) ),
					'outline_color'     => array( 'label' => __( 'Outline text colour', 'rise-landing-pages' ), 'help' => __( 'Leave blank to use the primary colour. Enter a hex colour such as #61ffd6 or a colour name such as white or black.', 'rise-landing-pages' ) ),
				),
			),
			'rise-typography' => array(
				'title'       => __( 'Typography', 'rise-landing-pages' ),
				'description' => __( 'Leave these blank to inherit the website fonts. Custom choices must already be available on this site or the visitor’s device; no fonts are downloaded.', 'rise-landing-pages' ),
				'fields'      => array(
					'heading_font' => array( 'label' => __( 'Heading font family', 'rise-landing-pages' ), 'type' => 'font', 'help' => __( 'Example: "Helvetica Neue", Helvetica, Arial, sans-serif', 'rise-landing-pages' ) ),
					'heading_style' => array( 'label' => __( 'Heading style', 'rise-landing-pages' ), 'type' => 'select', 'choices' => array( 'normal' => __( 'Normal', 'rise-landing-pages' ), 'italic' => __( 'Italic', 'rise-landing-pages' ) ), 'help' => __( 'Choose the appearance of heading text.', 'rise-landing-pages' ) ),
					'body_font'    => array( 'label' => __( 'Body font family', 'rise-landing-pages' ), 'type' => 'font', 'help' => __( 'Enter a comma-separated list of font names, or leave blank.', 'rise-landing-pages' ) ),
					'button_font' => array( 'label' => __( 'Button font family', 'rise-landing-pages' ), 'type' => 'font', 'help' => __( 'Leave blank to use the body font.', 'rise-landing-pages' ) ),
					'button_style' => array( 'label' => __( 'Button text style', 'rise-landing-pages' ), 'type' => 'select', 'choices' => array( 'normal' => __( 'Normal', 'rise-landing-pages' ), 'italic' => __( 'Italic', 'rise-landing-pages' ) ), 'help' => __( 'Choose normal or italic button labels.', 'rise-landing-pages' ) ),
					'button_weight' => array( 'label' => __( 'Button text weight', 'rise-landing-pages' ), 'type' => 'select', 'choices' => array( '400' => __( 'Regular', 'rise-landing-pages' ), '500' => __( 'Medium', 'rise-landing-pages' ), '600' => __( 'Semibold', 'rise-landing-pages' ), '700' => __( 'Bold', 'rise-landing-pages' ), '800' => __( 'Extra bold', 'rise-landing-pages' ), '900' => __( 'Heavy', 'rise-landing-pages' ) ), 'help' => __( 'The chosen font must support this weight.', 'rise-landing-pages' ) ),
					'button_transform' => array( 'label' => __( 'Button letter case', 'rise-landing-pages' ), 'type' => 'select', 'choices' => array( 'none' => __( 'As entered', 'rise-landing-pages' ), 'uppercase' => __( 'UPPERCASE', 'rise-landing-pages' ), 'lowercase' => __( 'lowercase', 'rise-landing-pages' ), 'capitalize' => __( 'Initial Capitals', 'rise-landing-pages' ) ), 'help' => __( 'Changes how button labels appear without changing their saved wording.', 'rise-landing-pages' ) ),
					'button_motion' => array( 'label' => __( 'Button hover animation', 'rise-landing-pages' ), 'type' => 'select', 'choices' => array( 'none' => __( 'None', 'rise-landing-pages' ), 'slide' => __( 'Rise movement', 'rise-landing-pages' ) ), 'help' => __( 'Moves the button slightly and changes its colours on hover. Respects reduced-motion preferences.', 'rise-landing-pages' ) ),
					'image_animation' => array( 'label' => __( 'Image animation', 'rise-landing-pages' ), 'type' => 'select', 'choices' => array( 'fade' => __( 'Fade', 'rise-landing-pages' ), 'tiles' => __( 'Rise Fitness tiles', 'rise-landing-pages' ) ), 'help' => __( 'Default reveal for FAQ section and question images. Each FAQ can override this setting. Reduced-motion visitors see images immediately.', 'rise-landing-pages' ) ),
					'outline_font' => array( 'label' => __( 'Outline text font family', 'rise-landing-pages' ), 'type' => 'font', 'help' => __( 'Optional. Leave blank to use the heading font. A section heading font override also applies to its outline text.', 'rise-landing-pages' ) ),
				),
			),
			'rise-layout' => array(
				'title'       => __( 'Layout', 'rise-landing-pages' ),
				'description' => __( 'Set comfortable desktop proportions. Spacing and columns adapt automatically on smaller screens.', 'rise-landing-pages' ),
				'fields'      => array(
					'content_width'   => array( 'label' => __( 'Maximum content width', 'rise-landing-pages' ), 'type' => 'number', 'min' => 640, 'max' => 1920, 'help' => __( 'Between 640 and 1920 pixels.', 'rise-landing-pages' ) ),
					'section_spacing' => array( 'label' => __( 'Section spacing', 'rise-landing-pages' ), 'type' => 'number', 'min' => 0, 'max' => 200, 'help' => __( 'Vertical section padding, up to 200 pixels.', 'rise-landing-pages' ) ),
					'card_radius'     => array( 'label' => __( 'Card corner rounding', 'rise-landing-pages' ), 'type' => 'number', 'min' => 0, 'max' => 80, 'help' => __( 'Use 0 for square corners, up to 80 pixels.', 'rise-landing-pages' ) ),
					'button_radius'   => array( 'label' => __( 'Button corner rounding', 'rise-landing-pages' ), 'type' => 'number', 'min' => 0, 'max' => 999, 'help' => __( 'Use 0 for square corners or 999 for pill-shaped buttons.', 'rise-landing-pages' ) ),
				),
			),
			'rise-new-pages' => array(
				'title'       => __( 'New landing pages', 'rise-landing-pages' ),
				'description' => __( 'Choose the header and footer applied when you create a new landing page. Existing pages keep their own choices.', 'rise-landing-pages' ),
				'fields'      => array(
					'default_header' => array( 'label' => __( 'Default header', 'rise-landing-pages' ), 'type' => 'select', 'choices' => array( 'site' => __( 'Site header', 'rise-landing-pages' ), 'minimal' => __( 'Minimal landing header', 'rise-landing-pages' ), 'hidden' => __( 'Hidden', 'rise-landing-pages' ) ), 'help' => __( 'Applied to newly created landing pages. Each page can choose a different header in its editor.', 'rise-landing-pages' ) ),
					'default_footer' => array( 'label' => __( 'Default footer', 'rise-landing-pages' ), 'type' => 'select', 'choices' => array( 'site' => __( 'Site footer', 'rise-landing-pages' ), 'landing' => __( 'Landing footer', 'rise-landing-pages' ), 'hidden' => __( 'Hidden', 'rise-landing-pages' ) ), 'help' => __( 'Applied to newly created landing pages. Each page can choose a different footer in its editor.', 'rise-landing-pages' ) ),
				),
			),
			'rise-booking' => array(
				'title'       => __( 'Default booking button', 'rise-landing-pages' ),
				'description' => __( 'Used when a page or section does not provide its own button label and link.', 'rise-landing-pages' ),
				'fields'      => array(
					'cta_label'   => array( 'label' => __( 'Button label', 'rise-landing-pages' ), 'help' => __( 'A short, clear action such as “Book an appointment”.', 'rise-landing-pages' ) ),
					'booking_url' => array( 'label' => __( 'Booking link', 'rise-landing-pages' ), 'type' => 'url', 'help' => __( 'Enter a full address, a site path such as /book/, or a telephone/email link. Buttons without a destination are hidden.', 'rise-landing-pages' ) ),
					'header_cta'  => array( 'label' => __( 'Header button', 'rise-landing-pages' ), 'type' => 'checkbox', 'checkbox_label' => __( 'Show the booking button in the landing header', 'rise-landing-pages' ), 'help' => __( 'Only displayed when a booking link is available and the page header is enabled.', 'rise-landing-pages' ) ),
				),
			),
			'rise-company' => array(
				'title'       => __( 'Footer locations and contact details', 'rise-landing-pages' ),
				'description' => __( 'Show one location or several in the landing footer. The active theme controls its own site footer.', 'rise-landing-pages' ),
				'fields'      => array(
					'contact_mode'    => array( 'label' => __( 'Footer location layout', 'rise-landing-pages' ), 'type' => 'select', 'choices' => array( 'single' => __( 'Single location', 'rise-landing-pages' ), 'multiple' => __( 'Multiple locations', 'rise-landing-pages' ) ), 'help' => __( 'Switch modes without deleting the details saved in the other mode.', 'rise-landing-pages' ) ),
					'address'         => array( 'label' => __( 'Location address', 'rise-landing-pages' ), 'type' => 'textarea', 'help' => __( 'Line breaks are preserved.', 'rise-landing-pages' ) ),
					'map_url'         => array( 'label' => __( 'Google Maps link', 'rise-landing-pages' ), 'type' => 'url', 'help' => __( 'Paste the location’s Google Maps sharing link.', 'rise-landing-pages' ) ),
					'phone'           => array( 'label' => __( 'Phone number', 'rise-landing-pages' ), 'type' => 'tel', 'help' => __( 'Include the country code where appropriate.', 'rise-landing-pages' ) ),
					'additional_phones' => array( 'label' => __( 'Additional phone numbers', 'rise-landing-pages' ), 'type' => 'textarea', 'help' => __( 'Optional. Enter one phone number per line.', 'rise-landing-pages' ) ),
					'email'           => array( 'label' => __( 'Email address', 'rise-landing-pages' ), 'type' => 'email', 'help' => __( 'Optional public contact email address.', 'rise-landing-pages' ) ),
					'company_details' => array( 'label' => __( 'Other details', 'rise-landing-pages' ), 'type' => 'textarea', 'help' => __( 'Optional opening hours, parking, accessibility or company information.', 'rise-landing-pages' ) ),
					'companies'      => array( 'label' => __( 'Locations', 'rise-landing-pages' ), 'type' => 'companies', 'help' => __( 'Add up to 10 locations. Each appears as a separate contact block in the landing footer; blank fields are omitted.', 'rise-landing-pages' ) ),
				),
			),
			'rise-links' => array(
				'title'       => __( 'Header and footer links', 'rise-landing-pages' ),
				'description' => __( 'Connect visitors to the relevant information on this website.', 'rise-landing-pages' ),
				'fields'      => array(
					'privacy_url'  => array( 'label' => __( 'Privacy Policy link', 'rise-landing-pages' ), 'type' => 'url', 'help' => __( 'Displayed in the landing footer when provided.', 'rise-landing-pages' ) ),
					'terms_url'    => array( 'label' => __( 'Terms & Conditions link', 'rise-landing-pages' ), 'type' => 'url', 'help' => __( 'Displayed in the landing footer when provided.', 'rise-landing-pages' ) ),
					'about_url'    => array( 'label' => __( 'About link', 'rise-landing-pages' ), 'type' => 'url', 'help' => __( 'Displayed in the minimal landing header and landing footer when provided.', 'rise-landing-pages' ) ),
					'footer_links' => array( 'label' => __( 'Additional footer links', 'rise-landing-pages' ), 'type' => 'links', 'help' => __( 'Add up to 20 optional links. Each link needs a label and an address.', 'rise-landing-pages' ) ),
				),
			),
		);
	}
}
