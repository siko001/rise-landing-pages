<?php
namespace RiseLandingPages\Editor;

use RiseLandingPages\Pages;
use RiseLandingPages\Blocks\Registry;
use RiseLandingPages\Blocks\Patterns;
use RiseLandingPages\Settings\Settings;

final class Editor {
	public function register() {
		add_filter( 'atx_editor_site_chrome_preview', array( $this, 'theme_chrome_preview' ), 20, 2 );
		add_filter( 'use_block_editor_for_post', array( $this, 'use_editor' ), PHP_INT_MAX, 2 );
		add_filter( 'allowed_block_types_all', array( $this, 'allowed_blocks' ), PHP_INT_MAX, 2 );
		add_filter( 'block_editor_settings_all', array( $this, 'settings' ), PHP_INT_MAX, 2 );
		add_filter( 'block_categories_all', array( $this, 'categories' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'assets' ) );
		add_action( 'enqueue_block_assets', array( $this, 'canvas_styles' ) );
	}

	/** The plugin already supplies the selected header and footer preview. */
	public function theme_chrome_preview( $show, $post ) {
		return $post && Pages::is_landing( $post->ID ) ? false : $show;
	}

	public function use_editor( $enabled, $post ) {
		return $post && Pages::is_landing( $post->ID ) ? true : $enabled;
	}

	public function allowed_blocks( $allowed, $context ) {
		if ( empty( $context->post ) || ! Pages::is_landing( $context->post->ID ) ) {
			return $allowed;
		}
		$blocks = apply_filters( 'rise_landing_allowed_blocks', Registry::names(), $context->post );
		$blocks = is_array( $blocks ) ? array_values( array_unique( array_filter( $blocks, 'is_string' ) ) ) : Registry::names();
		$blocks[] = 'rise-landing/hero-part';
		return array_values( array_unique( $blocks ) );
	}

	public function settings( $settings, $context ) {
		if ( empty( $context->post ) || ! Pages::is_landing( $context->post->ID ) ) {
			return $settings;
		}
		$settings['canLockBlocks'] = true;
		unset( $settings['atxSiteChrome'] );
		$settings['codeEditingEnabled'] = false;
		$settings['enableOpenverseMediaCategory'] = false;
		$patterns = \WP_Block_Patterns_Registry::get_instance();
		$settings['__experimentalAdditionalBlockPatterns'] = array_values( array_filter( array_map( array( $patterns, 'get_registered' ), Patterns::names() ) ) );
		$settings['__experimentalAdditionalBlockPatternCategories'] = array( array( 'name' => Patterns::CATEGORY, 'label' => __( 'Rise campaigns', 'rise-landing-pages' ) ) );
		$settings['defaultBlock'] = 'rise-landing/cta';
		return $settings;
	}

	public function categories( $categories, $context ) {
		$categories[] = array( 'slug' => 'rise-landing', 'title' => __( 'Rise Landing Pages', 'rise-landing-pages' ) );
		return $categories;
	}

	public function assets() {
		global $post;
		if ( ! $post || ! Pages::is_landing( $post->ID ) ) {
			return;
		}
		$this->judge_font();
		$brand = Settings::get();
		$color_presets = array(
			array( 'name' => __( 'Site primary', 'rise-landing-pages' ), 'color' => $brand['primary_color'] ),
			array( 'name' => __( 'Site secondary', 'rise-landing-pages' ), 'color' => $brand['secondary_color'] ),
			array( 'name' => __( 'Site text', 'rise-landing-pages' ), 'color' => $brand['text_color'] ),
		);
		foreach ( Settings::presets() as $preset ) {
			/* translators: %s: brand preset name. */
			$color_presets[] = array( 'name' => sprintf( __( '%s primary', 'rise-landing-pages' ), $preset['label'] ), 'color' => $preset['values']['primary_color'] );
			/* translators: %s: brand preset name. */
			$color_presets[] = array( 'name' => sprintf( __( '%s secondary', 'rise-landing-pages' ), $preset['label'] ), 'color' => $preset['values']['secondary_color'] );
		}
		wp_enqueue_script( 'rise-landing-editor' );
		$medical = \RiseLandingPages\Frontend\Frontend::is_rise_medical_theme();
		wp_add_inline_script( 'rise-landing-editor', 'window.riseLandingEditor = ' . wp_json_encode( array( 'isLanding' => true, 'rootClass' => \RiseLandingPages\Frontend\Frontend::root_classes() . ( $medical ? ' rise-lp--medical-layout' : '' ), 'settings' => $brand, 'separatorLogos' => array( 'site' => Settings::logo_url( $brand ), 'alternate' => Settings::logo_url( $brand, true ), 'brand' => Settings::separator_brand_mark( $brand ), 'fitness' => RISE_LP_URL . 'assets/rise-wordmark.svg', 'rise' => Settings::separator_brand_mark( $brand ), 'fitnessMark' => RISE_LP_URL . 'assets/rise-mark.svg', 'physio' => RISE_LP_URL . 'assets/rise-physio-icon.png', 'medical' => RISE_LP_URL . 'assets/rise-medical-icon.png' ), 'colorPresets' => $color_presets, 'allowedBlocks' => $this->allowed_blocks( true, new \WP_Block_Editor_Context( array( 'post' => $post ) ) ), 'cssVariables' => $medical ? \RiseLandingPages\Frontend\Frontend::rise_medical_css_variables() : Settings::css_variables(), 'titleCheckUrl' => rest_url( 'rise-landing/v1/title-check/' . $post->ID ), 'chromePreviewUrl' => get_preview_post_link( $post ), 'restNonce' => wp_create_nonce( 'wp_rest' ) ), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) . ';', 'before' );
	}

	public function canvas_styles() {
		if ( ! is_admin() ) {
			return;
		}
		global $post;
		if ( ! $post || ! Pages::is_landing( $post->ID ) ) {
			return;
		}
		$this->judge_font();
		$canvas_version = filemtime( RISE_LP_PATH . 'assets/frontend.css' ) ?: RISE_LP_VERSION;
		wp_enqueue_style( 'rise-landing-canvas', RISE_LP_URL . 'assets/frontend.css', array(), $canvas_version );
		\RiseLandingPages\Frontend\Fonts::attach( 'rise-landing-canvas' );
		$editor_version = filemtime( RISE_LP_PATH . 'build/editor.css' ) ?: RISE_LP_VERSION;
		wp_enqueue_style( 'rise-landing-editor', RISE_LP_URL . 'build/editor.css', array( 'rise-landing-canvas' ), $editor_version );
		if ( \RiseLandingPages\Frontend\Frontend::is_rise_medical_theme() ) {
			$background = \RiseLandingPages\Frontend\Frontend::rise_medical_background();
			wp_add_inline_style( 'rise-landing-editor', '.editor-styles-wrapper:has(.rise-lp--medical-layout.rise-lp-editor){background:' . $background . ' fixed;}' );
		}
		\RiseLandingPages\Frontend\Fonts::attach( 'rise-landing-editor' );
		if ( 'Divi' === get_template() ) {
			wp_enqueue_style( 'rise-landing-editor-divi', RISE_LP_URL . 'assets/editor-divi.css', array( 'rise-landing-editor' ), RISE_LP_VERSION );
		}
	}

	/** Load Judge where Fitness or Medical landing blocks use it. */
	private function judge_font() {
		if ( in_array( get_template(), array( 'risefitness', 'risefitness-lp' ), true ) || \RiseLandingPages\Frontend\Frontend::is_rise_medical_theme() ) {
			wp_enqueue_style( 'rise-landing-judge-font', RISE_LP_URL . 'assets/judge-font.css', array(), RISE_LP_VERSION );
		}
	}
}
