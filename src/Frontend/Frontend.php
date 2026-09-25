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
		return $classes;
	}

	/** Whether the main request is for a Rise page. */
	public static function is_landing_request() {
		return is_singular( 'page' ) && Pages::is_landing( get_queried_object_id() );
	}

	/** Prefer our document over the active theme's wrapper. */
	public function template( $template ) {
		if ( self::is_landing_request() ) {
			return RISE_LP_PATH . ( self::uses_site_layout() ? 'templates/site-page.php' : 'templates/landing-page.php' );
		}
		return $template;
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
		wp_enqueue_style( 'rise-landing-frontend', RISE_LP_URL . 'assets/frontend.css', array(), RISE_LP_VERSION );
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
