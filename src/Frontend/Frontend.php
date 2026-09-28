<?php
/**
 * Theme-independent landing page delivery.
 *
 * @package RiseLandingPages
 */

namespace RiseLandingPages\Frontend;

use RiseLandingPages\Pages;
use RiseLandingPages\Settings\Settings;

defined( 'ABSPATH' ) || exit;

class Frontend {

	/** Register frontend integrations without changing ordinary pages. */
	public function register() {
		add_filter( 'template_include', array( $this, 'template' ), PHP_INT_MAX );
		add_filter( 'redirect_canonical', array( $this, 'chrome_preview_redirect' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ), 100 );
		add_action( 'wp_head', array( $this, 'motion_bootstrap' ), 0 );
		add_action( 'wp_head', array( $this, 'rise_medical_assets' ), 11 );
		add_filter( 'body_class', array( $this, 'body_classes' ) );
	}

	/** Keep editor preview requests on the editor's host for local site aliases. */
	public function chrome_preview_redirect( $redirect ) {
		return self::is_landing_request() && Pages::is_editor_chrome_preview( get_queried_object_id() ) ? false : $redirect;
	}

	/** Prepare reveal styles before the first paint, with a timeout if scripts fail. */
	public function motion_bootstrap() {
		if ( ! self::is_landing_request() || Pages::is_editor_chrome_preview( get_queried_object_id() ) ) {
			return;
		}
		wp_print_inline_script_tag( '(function(){if(!window.matchMedia||window.matchMedia("(prefers-reduced-motion: reduce)").matches||!("IntersectionObserver" in window)||!Element.prototype.animate){return;}var root=document.documentElement;root.classList.add("rise-lp-motion-ready");window.riseLandingMotionFallback=window.setTimeout(function(){root.classList.remove("rise-lp-motion-ready");},3000);})();' );
	}

	public static function root_classes() {
		$settings = Settings::get();
		$classes = 'rise-lp' . ( 'risefitness' === get_template() ? ' rf-custom-block' : '' );
		if ( 'slide' === $settings['button_motion'] ) { $classes .= ' rise-lp--button-motion-slide'; }
		if ( '#EC1C2B' === strtoupper( $settings['primary_color'] ) ) { $classes .= ' rise-lp--fitness-buttons'; }
		if ( '#61FFD6' === strtoupper( $settings['primary_color'] ) ) { $classes .= ' rise-lp--physio'; }
		if ( self::is_rise_medical_theme() && self::rise_medical_is_light( $settings['background_color'] ) ) { $classes .= ' rise-lp--medical-light'; }
		return $classes;
	}

	/** Whether the main request is for a Rise page. */
	public static function is_landing_request() {
		return is_singular( 'page' ) && Pages::is_landing( get_queried_object_id() );
	}

	/** Prefer our document over the active theme's wrapper. */
	public function template( $template ) {
		if ( self::is_landing_request() ) {
			if ( self::uses_rise_medical_chrome() ) {
				return RISE_LP_PATH . 'templates/rise-medical-page.php';
			}
			return RISE_LP_PATH . ( self::uses_site_layout() ? 'templates/site-page.php' : 'templates/landing-page.php' );
		}
		return $template;
	}

	/** The Rise Medical theme keeps its real site chrome in Blade partials. */
	public static function uses_rise_medical_chrome() {
		return self::is_landing_request() && self::is_rise_medical_theme();
	}

	public static function is_rise_medical_theme() {
		return 'atx-theme' === get_template()
			&& class_exists( '\\App\\RiseMedical' )
			&& function_exists( 'view' );
	}

	/** Keep the original gradient only while its preset background is selected. */
	public static function rise_medical_background( $settings = null ) {
		$settings = is_array( $settings ) ? $settings : Settings::get();
		$background = $settings['background_color'];
		return '#001C55' === strtoupper( $background )
			? 'linear-gradient(110deg,#001c55 0%,#004395 36%,#0b72ec 72%,#269ff5 100%)'
			: $background;
	}

	/** Relative luminance distinguishes light backgrounds from dark ones. */
	private static function rise_medical_luminance( $hex ) {
		if ( 'white' === strtolower( (string) $hex ) ) { return 1.0; }
		if ( 'black' === strtolower( (string) $hex ) ) { return 0.0; }
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) { return 0.0; }
		$channels = array_map( static function ( $offset ) use ( $hex ) {
			$value = hexdec( substr( $hex, $offset, 2 ) ) / 255;
			return $value <= 0.04045 ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
		}, array( 0, 2, 4 ) );
		return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
	}

	public static function rise_medical_is_light( $hex ) {
		return self::rise_medical_luminance( $hex ) >= 0.30;
	}

	private static function rise_medical_readable_color( $preferred, $background, $fallback ) {
		$light = self::rise_medical_luminance( $preferred );
		$dark = self::rise_medical_luminance( $background );
		$contrast = ( max( $light, $dark ) + 0.05 ) / ( min( $light, $dark ) + 0.05 );
		return $contrast >= 4.5 ? $preferred : $fallback;
	}

	/** Derive readable foregrounds from the selected page and card colours. */
	public static function rise_medical_palette_variables() {
		$settings = Settings::get();
		$light = self::rise_medical_is_light( $settings['background_color'] );
		$card_light = self::rise_medical_is_light( $settings['surface_color'] );
		$text = self::rise_medical_readable_color( $settings['text_color'], $settings['background_color'], $light ? '#002e75' : '#ffffff' );
		$muted = self::rise_medical_readable_color( $settings['muted_color'], $settings['background_color'], $light ? '#344f75' : '#dbe9ff' );
		$card_text = self::rise_medical_readable_color( $settings['text_color'], $settings['surface_color'], $card_light ? '#002e75' : '#ffffff' );
		$card_muted = self::rise_medical_readable_color( $settings['muted_color'], $settings['surface_color'], $card_light ? '#344f75' : '#dbe9ff' );
		$button_bg = $light ? self::rise_medical_readable_color( $settings['primary_color'], $settings['background_color'], '#002e75' ) : '#ffffff';
		$button_text = self::rise_medical_readable_color( $settings['button_text_color'], $button_bg, $light ? '#ffffff' : '#002e75' );
		$card_button_bg = $card_light ? '#002e75' : '#ffffff';
		$card_button_text = self::rise_medical_readable_color( $settings['button_text_color'], $card_button_bg, $card_light ? '#ffffff' : '#002e75' );
		$outline = self::rise_medical_readable_color( $settings['outline_color'] ?: $text, $settings['background_color'], $text );
		$values = array(
			'--rise-medical-page-background' => self::rise_medical_background( $settings ),
			'--rise-medical-text' => $text,
			'--rise-medical-muted' => $muted,
			'--rise-medical-border' => $light ? 'rgb(0 46 117 / 30%)' : 'rgb(219 233 255 / 35%)',
			'--rise-medical-card-text' => $card_text,
			'--rise-medical-card-muted' => $card_muted,
			'--rise-medical-button-bg' => $button_bg,
			'--rise-medical-button-text' => $button_text,
			'--rise-medical-button-hover-bg' => $button_text,
			'--rise-medical-button-hover-text' => $button_bg,
			'--rise-medical-card-button-bg' => $card_button_bg,
			'--rise-medical-card-button-text' => $card_button_text,
			'--rise-text' => $text,
			'--rise-muted' => $muted,
			'--rise-outline-color' => $outline,
		);
		$css = '';
		foreach ( $values as $name => $value ) { $css .= $name . ':' . $value . ';'; }
		return $css;
	}

	public static function rise_medical_css_variables() {
		return Settings::css_variables() . self::rise_medical_palette_variables();
	}

	/** Load the same theme stylesheet and button behavior as Rise Medical pages. */
	public function rise_medical_assets() {
		if ( self::uses_rise_medical_chrome() && class_exists( '\\Illuminate\\Support\\Facades\\Vite' ) ) {
			echo \Illuminate\Support\Facades\Vite::withEntryPoints( array( 'resources/css/rise.css', 'resources/js/rise.js' ) )->toHtml(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The theme's Vite renderer owns these tags.
		}
	}

	/** Site mode deliberately lets the active theme own the surrounding document. */
	public static function uses_site_layout() {
		$page_id = get_queried_object_id();
		return 'site' === Pages::chrome_choice( $page_id, 'header' ) || 'site' === Pages::chrome_choice( $page_id, 'footer' );
	}

	/** Only a landing page needs the frontend assets. */
	public function enqueue() {
		if ( ! self::is_landing_request() ) {
			return;
		}
		if ( self::is_rise_medical_theme() ) {
			wp_enqueue_style( 'rise-landing-judge-font', RISE_LP_URL . 'assets/judge-font.css', array(), RISE_LP_VERSION );
		}
		$style_version = filemtime( RISE_LP_PATH . 'assets/frontend.css' ) ?: RISE_LP_VERSION;
		wp_enqueue_style( 'rise-landing-frontend', RISE_LP_URL . 'assets/frontend.css', array(), $style_version );
		$chrome_preview = Pages::is_editor_chrome_preview( get_queried_object_id() );
		$fitness_site_layout = 'risefitness' === get_template() && self::uses_site_layout();
		if ( self::uses_site_layout() && 'Divi' === get_template() ) {
			wp_add_inline_style( 'rise-landing-frontend', '@media (min-width:981px){body.rise-landing-page #main-header .logo_container{max-width:calc(100vw - 30px)!important}body.rise-landing-page .et_pb_row.justified-center-row{width:calc(100% - 80px)!important;max-width:calc(100% - 80px)!important}}' );
		}
		Fonts::attach( 'rise-landing-frontend' );
		if ( 'Divi' === get_template() ) { wp_dequeue_script( 'smoothscroll' ); }
		if ( 'risefitness' === get_template() && ( ! $fitness_site_layout || $chrome_preview ) ) { wp_dequeue_script( 'main' ); }
		if ( $chrome_preview ) { return; }
		wp_enqueue_script( 'rise-landing-media', RISE_LP_URL . 'assets/media.js', array(), RISE_LP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_enqueue_script( 'rise-landing-frontend', RISE_LP_URL . 'assets/frontend.js', array(), RISE_LP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		wp_enqueue_script( 'rise-landing-marquee', RISE_LP_URL . 'assets/marquee.js', array(), RISE_LP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		$smooth_asset_file = RISE_LP_PATH . 'build/smooth-scroll.asset.php';
		$smooth_asset = is_readable( $smooth_asset_file ) ? require $smooth_asset_file : array( 'dependencies' => array() );
		if ( ! $fitness_site_layout ) {
			wp_enqueue_script( 'rise-landing-smooth-scroll', RISE_LP_URL . 'build/smooth-scroll.js', $smooth_asset['dependencies'], RISE_LP_VERSION, array( 'in_footer' => true, 'strategy' => 'defer' ) );
		}
	}

	/** Add sanitized page-specific classes only on Rise pages. */
	public function body_classes( $classes ) {
		if ( ! self::is_landing_request() ) {
			return $classes;
		}
		$classes[] = 'rise-landing-page';
		if ( self::is_rise_medical_theme() && self::rise_medical_is_light( Settings::get()['background_color'] ) ) { $classes[] = 'rise-medical-light'; }
		$page_id = get_queried_object_id();
		if ( self::uses_site_layout() ) {
			if ( 'site' !== Pages::chrome_choice( $page_id, 'header' ) ) {
				$classes[] = 'rise-lp-hide-site-header';
			}
			if ( 'site' !== Pages::chrome_choice( $page_id, 'footer' ) ) {
				$classes[] = 'rise-lp-hide-site-footer';
			}
		}
		$custom    = get_post_meta( get_queried_object_id(), '_rise_landing_body_class', true );
		if ( is_string( $custom ) && '' !== $custom ) {
			foreach ( preg_split( '/\s+/', $custom ) as $class ) {
				$class = sanitize_html_class( $class );
				if ( '' !== $class ) {
					$classes[] = $class;
				}
			}
		}
		/** Filter the body classes of a Rise landing page. */
		$filtered = apply_filters( 'rise_landing_body_classes', $classes, get_queried_object_id() );
		return is_array( $filtered ) ? $filtered : $classes;
	}
}
