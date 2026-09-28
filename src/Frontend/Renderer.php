<?php
/**
 * Server-side block rendering and shared presentation helpers.
 *
 * @package RiseLandingPages
 */

namespace RiseLandingPages\Frontend;

use RiseLandingPages\Settings\Settings;

defined( 'ABSPATH' ) || exit;

class Renderer {

	/** Render the named block; saved attributes are always treated as untrusted. */
	public static function render( $name, $attributes, $block = null ) {
		$attributes = is_array( $attributes ) ? $attributes : array();
		if ( ! empty( $attributes['hidden'] ) ) {
			return '';
		}
		$name = str_replace( 'rise-landing/', '', (string) $name );
		if ( ! in_array( $name, array( 'hero', 'services', 'process', 'benefits', 'faq', 'cta', 'spacer', 'separator' ), true ) ) {
			return '';
		}
		if ( 'spacer' === $name ) {
			$height = self::number( $attributes, 'height', 64, 0, 400 );
			$mobile = self::number( $attributes, 'mobileHeight', 32, 0, 240 );
			return '<div class="rise-lp__spacer" aria-hidden="true" style="--rise-spacer-height:' . $height . 'px;--rise-spacer-mobile:' . $mobile . 'px"></div>';
		}
		if ( 'separator' === $name ) {
			return self::separator( $attributes );
		}
		$html = in_array( $name, array( 'hero', 'services', 'process' ), true ) ? self::$name( $attributes, $block ) : self::$name( $attributes );
		$style = '';
		if ( 'hero' === $name ) {
			$style .= '--rise-hero-min-height:' . self::number( $attributes, 'minHeight', 560, 320, 1200 ) . 'px;';
			$max_height = self::number( $attributes, 'maxHeight', 0, 0, 1600 );
			if ( $max_height > 0 ) { $style .= '--rise-hero-max-height:' . $max_height . 'px;'; }
			$style .= '--rise-hero-leading:' . self::number( $attributes, 'headingLeading', 0.95, 0.7, 1.5 ) . ';';
			$style .= '--rise-hero-eyebrow-gap:' . self::number( $attributes, 'gapEyebrowHeading', 23, 0, 240 ) . 'px;';
			$style .= '--rise-hero-description-gap:' . self::number( $attributes, 'gapHeadingDescription', 32, 0, 240 ) . 'px;';
			$style .= '--rise-hero-reassurance-gap:' . self::number( $attributes, 'gapDescriptionReassurance', 30, 0, 240 ) . 'px;';
			$style .= '--rise-hero-button-gap:' . self::number( $attributes, 'gapTextButtons', 28, 0, 240 ) . 'px;';
			$style .= '--rise-hero-reassurance-button-gap:' . self::number( $attributes, 'gapReassuranceButtons', 35, 0, 240 ) . 'px;';
			$style .= '--rise-overlay-opacity:' . ( self::number( $attributes, 'overlayOpacity', 55, 0, 95 ) / 100 ) . ';';
			$overlay_color = sanitize_hex_color( self::value( $attributes, 'overlayColor' ) ) ?: '#000000';
			if ( 'surface' === self::value( $attributes, 'sectionBackground' ) ) { $overlay_color = 'var(--rise-surface)'; }
			if ( 'offwhite' === self::value( $attributes, 'sectionBackground' ) ) { $overlay_color = '#f5f5f5'; }
			if ( 'white' === self::value( $attributes, 'sectionBackground' ) ) { $overlay_color = '#ffffff'; }
			if ( 'black' === self::value( $attributes, 'sectionBackground' ) ) { $overlay_color = '#000000'; }
			$style .= '--rise-overlay-color:' . $overlay_color . ';';
			$style .= '--rise-overlay-text:' . ( sanitize_hex_color( self::value( $attributes, 'overlayTextColor' ) ) ?: '#ffffff' ) . ';';
		}
		if ( in_array( $name, array( 'services', 'process', 'benefits', 'faq', 'cta' ), true ) ) {
			$style .= '--rise-section-heading-leading:' . self::number( $attributes, 'headingLeading', 1.14, 0.7, 1.5 ) . ';';
		}
		if ( in_array( $name, array( 'services', 'process', 'benefits', 'faq' ), true ) ) {
			$style .= '--rise-card-heading-leading:' . self::number( $attributes, 'cardHeadingLeading', 'faq' === $name ? 1.5 : 1.25, 0.7, 1.8 ) . ';';
		}
		if ( 'services' === $name ) {
			$style .= '--rise-services-columns:' . self::number( $attributes, 'gridColumns', 3, 1, 6 ) . ';';
			$style .= '--rise-services-mobile-columns:' . self::number( $attributes, 'mobileGridColumns', 1, 1, 3 ) . ';';
			$style .= '--rise-services-grid-gap:' . self::number( $attributes, 'gridGap', 24, 0, 80 ) . 'px;';
			$style .= '--rise-services-eyebrow-gap:' . self::number( $attributes, 'gapEyebrowHeading', 16, 0, 160 ) . 'px;';
			$style .= '--rise-services-intro-gap:' . self::number( $attributes, 'gapHeadingIntro', 22, 0, 160 ) . 'px;';
			$style .= '--rise-services-cards-gap:' . self::number( $attributes, 'gapIntroCards', 42, 0, 160 ) . 'px;';
			$style .= '--rise-services-card-text-gap:' . self::number( $attributes, 'cardTextGap', 16, 0, 160 ) . 'px;';
		}
		if ( 'process' === $name ) {
			$style .= '--rise-process-step-title-size:' . self::number( $attributes, 'stepTitleSize', 32, 16, 72 ) . 'px;';
			$image_background = sanitize_hex_color( self::value( $attributes, 'markerImageBackground' ) );
			if ( $image_background ) { $style .= '--rise-process-image-background:' . $image_background . ';'; }
		}
		{
			$processor = new \WP_HTML_Tag_Processor( $html );
			if ( $processor->next_tag( 'SECTION' ) ) {
				if ( $style ) { $processor->set_attribute( 'style', $style ); }
				$background = self::value( $attributes, 'sectionBackground', 'none' );
				$allowed_backgrounds = array( 'none', 'white', 'offwhite', 'surface', 'black' );
				$background = in_array( $background, $allowed_backgrounds, true ) ? $background : 'none';
				$processor->add_class( 'rise-lp__section--bg-' . $background );
				$heading_font = self::value( $attributes, 'headingFont', 'judge' );
				$processor->add_class( 'rise-lp__section--font-' . ( 'poppins' === $heading_font ? 'poppins' : 'judge' ) );
				$html = $processor->get_updated_html();
			}
		}
		return $html;
	}

	private static function number( $attributes, $key, $default, $min, $max ) {
		return isset( $attributes[ $key ] ) && is_numeric( $attributes[ $key ] ) ? max( $min, min( $max, (float) $attributes[ $key ] ) ) : $default;
	}

	/** Coerce one scalar attribute, rejecting unexpected arrays/objects. */
	private static function value( $attributes, $key, $fallback = '' ) {
		return isset( $attributes[ $key ] ) && is_scalar( $attributes[ $key ] ) ? (string) $attributes[ $key ] : $fallback;
	}

	/** Render the separator's brand icon, optional wordmark, and phrases. */
	private static function separator( $attributes ) {
		$background = self::value( $attributes, 'background', 'white' );
		$background = in_array( $background, array( 'fitness', 'physio', 'medical', 'black', 'white' ), true ) ? $background : 'white';
		$motion = self::value( $attributes, 'motion', 'auto' );
		$motion = in_array( $motion, array( 'auto', 'scroll', 'none' ), true ) ? $motion : 'auto';
		$direction = 'right' === self::value( $attributes, 'direction' ) ? 'right' : 'left';
		$gap = self::number( $attributes, 'gap', 24, 0, 160 );
		$speed = self::number( $attributes, 'speed', 80, 10, 300 );
		$scroll_speed = self::number( $attributes, 'scrollSpeed', 100, 10, 300 );
		$settings = Settings::get();
		$show_main = ! isset( $attributes['showMainLogo'] ) || ! empty( $attributes['showMainLogo'] );
		$show_main = $show_main && 'none' !== self::value( $attributes, 'logoChoice', 'fitness' );
		$primary = $show_main ? self::separator_logo( 'fitness', '0', $settings, 'primary' ) : '';
		$secondary = self::separator_logo( self::value( $attributes, 'dividerLogoChoice', 'brand' ), self::value( $attributes, 'customDividerLogoId' ), $settings, 'divider' );
		$texts = isset( $attributes['texts'] ) && is_array( $attributes['texts'] ) ? array_slice( $attributes['texts'], 0, 6 ) : array( __( 'Move with Rise', 'rise-landing-pages' ) );
		$texts = array_values( array_filter( array_map( static function ( $text ) {
			if ( ! is_scalar( $text ) ) { return ''; }
			$text = trim( preg_replace( '/<br\s*\/?\s*>|<\/?p(?:\s[^>]*)?>/i', ' ', (string) $text ) );
			return '' !== trim( wp_strip_all_tags( $text ) ) ? $text : '';
		}, $texts ) ) );
		if ( ! $texts ) { $texts = array( __( 'Move with Rise', 'rise-landing-pages' ) ); }
		$unit = '';
		foreach ( $texts as $text ) {
			$unit .= self::separator_item( $secondary, 'rise-lp__separator-logo--divider' );
			if ( $show_main ) {
				$unit .= self::separator_item( $primary, 'rise-lp__separator-logo--primary' );
				$unit .= self::separator_item( $secondary, 'rise-lp__separator-logo--divider' );
			}
			$unit .= '<span class="rise-lp__separator-item rise-lp__separator-text">' . Text::format( $text, array() ) . '</span>';
		}
		return '<div class="rise-lp__separator rise-lp__separator--' . esc_attr( $background ) . '" data-rise-marquee data-motion="' . esc_attr( $motion ) . '" data-direction="' . esc_attr( $direction ) . '" data-speed="' . esc_attr( $speed ) . '" data-scroll-speed="' . esc_attr( $scroll_speed ) . '" data-pause-on-hover="' . ( ! empty( $attributes['pauseOnHover'] ) ? 'true' : 'false' ) . '" style="--rise-separator-gap:' . esc_attr( $gap ) . 'px"><div class="rise-lp__separator-track">' . $unit . '</div></div>';
	}

	private static function separator_item( $logo, $class ) {
		return $logo ? '<span class="rise-lp__separator-item ' . esc_attr( $class ) . '">' . $logo . '</span>' : '';
	}

	private static function separator_logo( $choice, $custom_id, $settings, $slot ) {
		if ( 'none' === $choice ) { return ''; }
		if ( 'brand' === $choice || 'rise' === $choice ) {
			$url = Settings::separator_brand_mark( $settings );
			return '<img class="' . ( false !== strpos( $url, 'rise-medical-icon.png' ) ? 'rise-lp__separator-medical-icon' : '' ) . '" src="' . esc_url( $url ) . '" alt="" loading="eager" decoding="async" />';
		}
		if ( 'custom' === $choice ) { $id = absint( $custom_id ); }
		elseif ( 'alternate' === $choice ) { $id = absint( $settings['alternate_logo_id'] ); }
		elseif ( 'site' === $choice ) { $id = absint( $settings['logo_id'] ); }
		else { $id = 0; }
		if ( $id ) {
			$image = wp_get_attachment_image( $id, 'medium', false, array( 'alt' => '', 'loading' => 'eager', 'decoding' => 'async' ) );
			if ( $image ) { return $image; }
		}
		if ( 'site' === $choice || 'alternate' === $choice ) {
			$url = Settings::logo_url( $settings, 'alternate' === $choice );
			if ( $url ) {
				return '<img src="' . esc_url( $url ) . '" alt="" loading="eager" decoding="async" />';
			}
		}
		// Use the bundled Rise marks before any custom image is selected.
		if ( 'physio' === $choice ) { $mark = 'rise-physio-icon.png'; }
		elseif ( 'medical' === $choice ) { $mark = 'rise-medical-icon.png'; }
		elseif ( 'fitnessMark' === $choice ) { $mark = 'rise-mark.svg'; }
		elseif ( 'divider' === $slot && 'fitness' !== $choice ) {
			$url = Settings::separator_brand_mark( $settings );
			return '<img class="' . ( false !== strpos( $url, 'rise-medical-icon.png' ) ? 'rise-lp__separator-medical-icon' : '' ) . '" src="' . esc_url( $url ) . '" alt="" loading="eager" decoding="async" />';
		} else { $mark = 'rise-wordmark.svg'; }
		return '<img' . ( 'rise-medical-icon.png' === $mark ? ' class="rise-lp__separator-medical-icon"' : '' ) . ' src="' . esc_url( RISE_LP_URL . 'assets/' . $mark ) . '" alt="" loading="eager" decoding="async" />';
	}

	/** Keep the limited formatting that RichText exposes in headings. */
	private static function heading_text( $text ) {
		return Text::format( (string) $text, array( 'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(), 'br' => array(), 'sup' => array(), 'sub' => array() ) );
	}

	/** Sanitize inline colour markup in small section labels. */
	private static function eyebrow_text( $text ) {
		return Text::format( (string) $text, array() );
	}

	/** Safe rich text, with no arbitrary styles or embedded media. */
	private static function rich_text( $text ) {
		return Text::format( (string) $text, array( 'p' => array(), 'br' => array(), 'strong' => array(), 'b' => array(), 'em' => array(), 'i' => array(), 'sup' => array(), 'sub' => array(), 'ul' => array(), 'ol' => array(), 'li' => array(), 'a' => array( 'href' => true, 'title' => true, 'rel' => true ) ) );
	}

	/** Limit repeaters and discard malformed items before rendering. */
	private static function items( $attributes, $maximum ) {
		if ( empty( $attributes['items'] ) || ! is_array( $attributes['items'] ) ) {
			return array();
		}
		return array_slice( array_values( array_filter( $attributes['items'], 'is_array' ) ), 0, $maximum );
	}

	/** A block's heading is optional; its section never references a missing ID. */
	private static function section_header( $attributes, $id ) {
		$html     = '<div class="rise-lp__section-header">';
		$eyebrow  = self::value( $attributes, 'eyebrow' );
		$heading  = self::value( $attributes, 'heading' );
		$intro    = self::value( $attributes, 'intro' );
		if ( '' !== trim( $eyebrow ) ) {
			$html .= '<p class="rise-lp__eyebrow">' . self::eyebrow_text( $eyebrow ) . '</p>';
		}
		if ( '' !== trim( $heading ) ) {
			$html .= '<h2 class="rise-lp__heading" id="' . esc_attr( $id ) . '">' . self::heading_text( $heading ) . '</h2>';
		}
		if ( '' !== trim( $intro ) ) {
			$html .= '<div class="rise-lp__intro">' . self::rich_text( $intro ) . '</div>';
		}
		return $html . '</div>';
	}

	private static function section_open( $name, $attributes, $heading_id ) {
		$label = '' !== trim( self::value( $attributes, 'heading' ) ) ? ' aria-labelledby="' . esc_attr( $heading_id ) . '"' : '';
		return '<section class="rise-lp__section rise-lp__' . esc_attr( $name ) . '"' . $label . '><div class="rise-lp__container">';
	}

	/** Resolve a per-block CTA, then page overrides, then site defaults. */
	public static function cta_defaults( $attributes = array() ) {
		$label    = self::value( $attributes, 'ctaLabel' );
		$url      = self::value( $attributes, 'ctaUrl' );
		if ( ! empty( $attributes['requireExplicitCta'] ) ) {
			return array( 'label' => $label, 'url' => $url );
		}
		$settings = Settings::get();
		$post_id  = get_the_ID();
		if ( '' === trim( $label ) ) {
			$label = (string) get_post_meta( $post_id, '_rise_landing_cta_label', true );
		}
		if ( '' === trim( $url ) ) {
			$url = (string) get_post_meta( $post_id, '_rise_landing_booking_url', true );
		}
		return array(
			'label' => '' !== trim( $label ) ? $label : self::value( $settings, 'cta_label', __( 'Book an appointment', 'rise-landing-pages' ) ),
			'url'   => '' !== trim( $url ) ? $url : self::value( $settings, 'booking_url' ),
		);
	}

	/** Links do not render until a usable destination has been configured. */
	public static function button( $label, $url, $new_tab = false, $secondary = false, $variant = 'outline', $background = '', $text = '' ) {
		$url = esc_url( $url );
		if ( '' === trim( $label ) || '' === $url ) {
			return '';
		}
		$class = 'rise-lp__button' . ( $secondary ? ' rise-lp__button--secondary' : '' );
		$variant = in_array( $variant, array( 'outline', 'light', 'dark', 'accent', 'custom' ), true ) ? $variant : 'outline';
		if ( $secondary ) { $class .= ' rise-lp__button--' . $variant; }
		$style = '';
		if ( $secondary && 'custom' === $variant ) {
			$background = sanitize_hex_color( $background );
			$text = sanitize_hex_color( $text );
			if ( $background ) { $style .= 'background:' . $background . ';border-color:' . $background . ';'; }
			if ( $text ) { $style .= 'color:' . $text . ';'; }
		}
		$extra = $new_tab ? ' target="_blank" rel="noopener noreferrer"' : '';
		$hint  = $new_tab ? '<span class="rise-lp__sr-only"> ' . esc_html__( '(opens in a new tab)', 'rise-landing-pages' ) . '</span>' : '';
		return '<a class="' . esc_attr( $class ) . '" href="' . $url . '"' . ( $style ? ' style="' . esc_attr( $style ) . '"' : '' ) . $extra . '>' . self::button_label( $label ) . $hint . '</a>';
	}

	private static function button_label( $label ) {
		$label = esc_html( wp_strip_all_tags( $label ) );
		$contents = '<span class="rise-lp__button-label">' . $label . '</span><span class="rise-lp__button-copy" aria-hidden="true">' . $label . '</span>';
		return Frontend::is_rise_medical_theme() ? '<span class="rise-lp__button-roll">' . $contents . '</span>' : $contents;
	}

	/** Use the saved Hero file; other attachment images retain native srcset. */
	public static function image( $attributes, $class, $hero = false, $prefix = 'image' ) {
		if ( 'video' === self::value( $attributes, 'mediaType' ) && 'image' === $prefix ) {
			return self::video( $attributes, $class );
		}
		$id    = absint( self::value( $attributes, $prefix . 'Id' ) );
		$url   = esc_url( self::value( $attributes, $prefix . 'Url' ) );
		$alt   = self::value( $attributes, $prefix . 'Alt' );
		$props = array( 'class' => $class, 'alt' => $alt, 'loading' => $hero ? 'eager' : 'lazy', 'decoding' => 'async', 'style' => self::media_style( $attributes ) );
		if ( $hero ) {
			$props['fetchpriority'] = 'high';
			$props['sizes']         = '(max-width: 767px) 100vw, 50vw';
		} else {
			$props['sizes'] = '(max-width: 639px) 100vw, (max-width: 1023px) 50vw, 33vw';
		}
		// Gutenberg previews the saved URL. Keep the Hero on that same file even if
		// an attachment was edited after selection or its metadata still has crops.
		if ( $id && ( ! $hero || ! $url ) ) {
			$image = wp_get_attachment_image( $id, $hero ? 'full' : 'large', false, $props );
			if ( $image ) {
				return $image;
			}
		}
		if ( ! $url ) {
			return '';
		}
		return '<img style="' . esc_attr( self::media_style( $attributes ) ) . '" class="' . esc_attr( $class ) . '" src="' . $url . '" alt="' . esc_attr( $alt ) . '" loading="' . ( $hero ? 'eager' : 'lazy' ) . '" decoding="async"' . ( $hero ? ' fetchpriority="high"' : '' ) . ' />';
	}

	private static function media_style( $attributes ) {
		$x = self::number( $attributes, 'focalX', 50, 0, 100 );
		$y = self::number( $attributes, 'focalY', 50, 0, 100 );
		$has_mobile = ! empty( $attributes['mobileImageId'] ) || ! empty( $attributes['mobileImageUrl'] );
		$mx = $has_mobile ? self::number( $attributes, 'mobileFocalX', $x, 0, 100 ) : $x;
		$my = $has_mobile ? self::number( $attributes, 'mobileFocalY', $y, 0, 100 ) : $y;
		$fit = 'contain' === self::value( $attributes, 'mediaFit' ) ? 'contain' : 'cover';
		return '--rise-media-position:' . $x . '% ' . $y . '%;--rise-media-mobile-position:' . $mx . '% ' . $my . '%;object-fit:' . $fit . ';';
	}

	private static function video( $attributes, $class ) {
		$id = absint( self::value( $attributes, 'imageId' ) );
		$url = $id && 0 === strpos( (string) get_post_mime_type( $id ), 'video/' ) ? wp_get_attachment_url( $id ) : self::value( $attributes, 'imageUrl' );
		$url = esc_url( $url, array( 'http', 'https' ) );
		if ( ! $url ) { return ''; }
		$autoplay = ! empty( $attributes['videoAutoplay'] );
		$muted = $autoplay || ! isset( $attributes['videoMuted'] ) || $attributes['videoMuted'];
		$controls = ! isset( $attributes['videoControls'] ) || $attributes['videoControls'];
		$video_id = wp_unique_id( 'rise-video-' );
		$html = '<div class="rise-lp__video-wrap"><video id="' . esc_attr( $video_id ) . '" class="' . esc_attr( $class ) . '" style="' . esc_attr( self::media_style( $attributes ) ) . '" src="' . $url . '" playsinline preload="metadata" data-rise-video data-autoplay="' . ( $autoplay ? 'true' : 'false' ) . '" aria-label="' . esc_attr( self::value( $attributes, 'imageAlt', __( 'Campaign video', 'rise-landing-pages' ) ) ?: __( 'Campaign video', 'rise-landing-pages' ) ) . '"' . ( $muted ? ' muted' : '' ) . ( $controls ? ' controls' : ' tabindex="0" data-rise-click-play' ) . ( ! empty( $attributes['videoLoop'] ) ? ' loop' : '' ) . '>';
		$captions = esc_url( self::value( $attributes, 'captionsUrl' ), array( 'http', 'https' ) );
		if ( $captions ) { $html .= '<track kind="captions" src="' . $captions . '" srclang="' . esc_attr( substr( get_bloginfo( 'language' ), 0, 2 ) ) . '" label="' . esc_attr__( 'Captions', 'rise-landing-pages' ) . '">'; }
		$html .= '</video></div>';
		return $html;
	}

	private static function hero( $attributes, $block = null ) {
		$id        = wp_unique_id( 'rise-hero-' );
		$alignment = 'center' === self::value( $attributes, 'alignment' ) ? 'center' : 'left';
		$image     = self::image( $attributes, 'rise-lp__hero-image', true );
		$mobile_id = absint( self::value( $attributes, 'mobileImageId' ) );
		$mobile    = esc_url( self::value( $attributes, 'mobileImageUrl' ) );
		if ( ! $mobile && $mobile_id ) {
			$mobile = wp_get_attachment_image_srcset( $mobile_id, 'full' );
			if ( ! $mobile ) {
				$mobile = wp_get_attachment_image_url( $mobile_id, 'full' );
			}
		}
		if ( '' === $image && $mobile ) {
			$image = self::image( $attributes, 'rise-lp__hero-image', true, 'mobileImage' );
		}
		if ( 'video' === self::value( $attributes, 'mediaType' ) ) { $mobile = ''; }
		$layout = self::value( $attributes, 'layout', 'overlay' );
		$layout = 'split' === $layout ? 'split' : 'overlay';
		$media_side = 'left' === self::value( $attributes, 'mediaSide' ) ? 'left' : 'right';
		$class = 'rise-lp__hero--' . $layout . ' rise-lp__section rise-lp__hero rise-lp__hero--' . $alignment . ' rise-lp__hero--media-' . $media_side . ( '' === $image ? ' rise-lp__hero--text-only' : '' );
		$html  = '<section class="' . esc_attr( $class ) . '"><div class="rise-lp__container rise-lp__hero-grid"><div class="rise-lp__hero-content">';
		$parts = array();
		if ( $block instanceof \WP_Block ) {
			foreach ( $block->inner_blocks as $inner_block ) {
				if ( 'rise-landing/hero-part' !== $inner_block->name ) { continue; }
				$role = self::value( $inner_block->attributes, 'role' );
				if ( in_array( $role, array( 'eyebrow', 'heading', 'description', 'reassurance', 'actions' ), true ) && ! isset( $parts[ $role ] ) ) {
					$parts[ $role ] = $inner_block->attributes;
				}
			}
		}
		$has_parts = ! empty( $parts );
		$has_eyebrow_spacer = false;
		if ( ! $has_parts && $block instanceof \WP_Block ) {
			foreach ( $block->inner_blocks as $inner_block ) {
				if ( 'rise-landing/spacer' === $inner_block->name && 'after-eyebrow' === self::value( $inner_block->attributes, 'heroSlot' ) ) {
					$has_eyebrow_spacer = true;
					break;
				}
			}
		}
		$eyebrow = $has_parts ? self::value( $parts['eyebrow'] ?? array(), 'content' ) : self::value( $attributes, 'eyebrow' );
		$heading = $has_parts ? self::value( $parts['heading'] ?? array(), 'content' ) : self::value( $attributes, 'heading' );
		$description = $has_parts ? self::value( $parts['description'] ?? array(), 'content' ) : self::value( $attributes, 'description' );
		$reassurance = $has_parts ? self::value( $parts['reassurance'] ?? array(), 'content' ) : self::value( $attributes, 'reassurance' );
		$fragments = array();
		if ( '' !== trim( $eyebrow ) ) {
			$gap = $has_parts || $has_eyebrow_spacer ? ' style="margin-bottom:0px"' : '';
			$fragments['eyebrow'] = '<p class="rise-lp__eyebrow"' . $gap . '>' . self::eyebrow_text( $eyebrow ) . '</p>';
		}
		if ( '' !== trim( $heading ) ) {
			$gap = $has_parts ? ' style="margin-top:' . ( '' !== trim( $eyebrow ) ? self::number( $parts['heading'] ?? array(), 'gap', 23, 0, 240 ) : 0 ) . 'px"' : '';
			$fragments['heading'] = '<h1 class="rise-lp__heading" id="' . esc_attr( $id ) . '"' . $gap . '>' . self::heading_text( $heading ) . '</h1>';
		}
		if ( '' !== trim( $description ) ) {
			$gap = $has_parts ? ' style="margin-top:' . self::number( $parts['description'] ?? array(), 'gap', 32, 0, 240 ) . 'px"' : '';
			$fragments['description'] = '<div class="rise-lp__intro"' . $gap . '>' . self::rich_text( $description ) . '</div>';
		}
		if ( '' !== trim( $reassurance ) ) {
			$gap = $has_parts ? ' style="margin-top:' . self::number( $parts['reassurance'] ?? array(), 'gap', 30, 0, 240 ) . 'px"' : '';
			$fragments['reassurance'] = '<div class="rise-lp__reassurance"' . $gap . '>' . self::rich_text( $reassurance ) . '</div>';
		}
		$cta   = self::cta_defaults( $attributes );
		$buttons = '';
		if ( ! array_key_exists( 'showCta', $attributes ) || $attributes['showCta'] ) {
			$buttons .= self::button( $cta['label'], $cta['url'], ! empty( $attributes['ctaNewTab'] ) );
		}
		$show_secondary = array_key_exists( 'showSecondary', $attributes ) ? ! empty( $attributes['showSecondary'] ) : ( '' !== trim( self::value( $attributes, 'secondaryLabel' ) ) && '' !== trim( self::value( $attributes, 'secondaryUrl' ) ) );
		if ( $show_secondary ) {
			$buttons .= self::button( self::value( $attributes, 'secondaryLabel' ), self::value( $attributes, 'secondaryUrl' ), ! empty( $attributes['secondaryNewTab'] ), true, self::value( $attributes, 'secondaryStyle', 'outline' ), self::value( $attributes, 'secondaryBackground' ), self::value( $attributes, 'secondaryText' ) );
		}
		if ( $buttons ) {
			$button_part = $parts['actions'] ?? array();
			$button_gap = '';
			if ( $has_parts ) {
				$gap_key = '' !== trim( $reassurance ) ? 'gap' : 'gapWithoutReassurance';
				$button_gap = ' style="margin-top:' . self::number( $button_part, $gap_key, 'gap' === $gap_key ? 35 : 28, 0, 240 ) . 'px"';
			}
			$fragments['actions'] = '<div class="rise-lp__actions"' . $button_gap . '>' . $buttons . '</div>';
		}
		if ( $has_parts ) {
			$rendered_roles = array();
			$preceding_role = '';
			foreach ( $block->inner_blocks as $inner_block ) {
				if ( 'rise-landing/spacer' === $inner_block->name ) {
					if ( in_array( $preceding_role, array( 'eyebrow', 'heading', 'description', 'reassurance' ), true ) ) {
						$html .= self::render( 'spacer', $inner_block->attributes );
					}
					continue;
				}
				if ( 'rise-landing/hero-part' !== $inner_block->name ) { continue; }
				$role = self::value( $inner_block->attributes, 'role' );
				$preceding_role = $role;
				if ( isset( $fragments[ $role ] ) && ! isset( $rendered_roles[ $role ] ) ) {
					$html .= $fragments[ $role ];
					$rendered_roles[ $role ] = true;
				}
			}
			if ( isset( $fragments['actions'] ) && ! isset( $rendered_roles['actions'] ) ) {
				$html .= $fragments['actions'];
			}
		} else {
			$spacers = array();
			if ( $block instanceof \WP_Block ) {
				foreach ( $block->inner_blocks as $inner_block ) {
					if ( 'rise-landing/spacer' !== $inner_block->name ) { continue; }
					$slot = self::value( $inner_block->attributes, 'heroSlot' );
					if ( '' === $slot ) {
						$slot = 'after-description';
					}
					if ( ! in_array( $slot, array( 'after-eyebrow', 'after-heading', 'after-description', 'after-reassurance' ), true ) ) { continue; }
					if ( ! isset( $spacers[ $slot ] ) ) {
						$spacers[ $slot ] = self::render( 'spacer', $inner_block->attributes );
					}
				}
			}
			foreach ( array( 'eyebrow', 'heading', 'description', 'reassurance', 'actions' ) as $role ) {
				$html .= $fragments[ $role ] ?? '';
				$html .= $spacers[ 'after-' . $role ] ?? '';
			}
		}
		$html .= '</div>';
		if ( $image ) {
			$html .= '<div class="rise-lp__hero-media">';
			if ( $mobile ) {
				$html .= '<picture><source media="(max-width: 767px)" srcset="' . esc_attr( $mobile ) . '" />' . $image . '</picture>';
			} else {
				$html .= $image;
			}
			$html .= '</div>';
		}
		return $html . '</div></section>';
	}

	private static function services( $attributes, $block = null ) {
		$id   = wp_unique_id( 'rise-services-' );
		$html = self::section_open( 'services', $attributes, $id ) . self::section_header( $attributes, $id );
		$spacers = '';
		if ( $block instanceof \WP_Block ) {
			foreach ( $block->inner_blocks as $inner_block ) {
				if ( 'rise-landing/spacer' === $inner_block->name ) {
					$spacers .= self::render( 'spacer', $inner_block->attributes );
					break;
				}
			}
		}
		if ( '' !== $spacers ) {
			$html .= '<div class="rise-lp__services-spacers">' . $spacers . '</div>';
		}
		$layout = self::value( $attributes, 'cardLayout', 'standard' );
		$layout = in_array( $layout, array( 'standard', 'pricing', 'image' ), true ) ? $layout : 'standard';
		$slider = 'slider' === self::value( $attributes, 'displayMode', 'slider' );
		$grid_id = wp_unique_id( 'rise-services-track-' );
		$grid_classes = 'rise-lp__services-grid rise-lp__services-grid--' . $layout;
		if ( 'image' === $layout ) {
			if ( 4 === (int) self::number( $attributes, 'gridColumns', 3, 1, 6 ) ) {
				$grid_classes .= ' rise-lp__services-grid--image-four';
			}
			if ( 'center' === self::value( $attributes, 'imageGridAlignment', 'start' ) ) {
				$grid_classes .= ' rise-lp__services-grid--image-centered';
			}
		}
		if ( $slider ) {
			$grid_classes .= ' rise-lp__services-grid--slider';
		}
		$html .= '<div class="' . esc_attr( $grid_classes ) . '" id="' . esc_attr( $grid_id ) . '"' . ( $slider ? ' data-rise-slider data-loop="' . ( ! empty( $attributes['sliderLoop'] ) ? 'true' : 'false' ) . '" data-autoplay="' . ( ! empty( $attributes['sliderAutoplay'] ) ? 'true' : 'false' ) . '" data-autoplay-interval="' . esc_attr( self::number( $attributes, 'sliderAutoplayInterval', 5, 2, 15 ) ) . '" tabindex="0" role="region" aria-label="' . esc_attr__( 'Services — swipe or use the navigation controls', 'rise-landing-pages' ) . '"' : '' ) . '>';
		foreach ( self::items( $attributes, 6 ) as $item ) {
			$html .= '<article class="rise-lp__service-card">';
			if ( 'pricing' === $layout && self::value( $item, 'badge' ) ) {
				$html .= '<span class="rise-lp__service-badge">' . esc_html( self::value( $item, 'badge' ) ) . '</span>';
			}
			$image = self::image( $item, 'rise-lp__service-image' );
			if ( $image && 'pricing' !== $layout ) {
				$overlay_color = sanitize_hex_color( self::value( $item, 'overlayColor' ) ) ?: '#000000';
				$overlay_opacity = self::number( $item, 'overlayOpacity', 'image' === $layout ? 55 : 0, 0, 95 ) / 100;
				$html .= '<div class="rise-lp__service-media" style="--rise-service-overlay-color:' . esc_attr( $overlay_color ) . ';--rise-service-overlay-opacity:' . esc_attr( $overlay_opacity ) . '">' . $image . '</div>';
			}
			$html .= '<div class="rise-lp__service-content">';
			if ( self::value( $item, 'title' ) ) {
				$html .= '<h3 class="rise-lp__card-title">' . self::heading_text( self::value( $item, 'title' ) ) . '</h3>';
			}
			if ( 'pricing' !== $layout && self::value( $item, 'description' ) ) {
				$html .= '<div class="rise-lp__card-copy">' . self::rich_text( self::value( $item, 'description' ) ) . '</div>';
			}
			if ( 'pricing' === $layout ) {
				$feature_lines = isset( $item['features'] ) && is_array( $item['features'] ) ? $item['features'] : preg_split( '/\r\n|\r|\n/', self::value( $item, 'features' ) );
				$features = array_filter( array_map( 'trim', array_filter( $feature_lines, 'is_scalar' ) ), static function ( $feature ) { return '' !== $feature; } );
				if ( $features ) {
					$html .= '<ul class="rise-lp__service-features">';
					foreach ( array_slice( $features, 0, 12 ) as $feature ) { $html .= '<li>' . esc_html( $feature ) . '</li>'; }
					$html .= '</ul>';
				}
				if ( self::value( $item, 'price' ) || self::value( $item, 'priceQualifier' ) || self::value( $item, 'priceNote' ) ) {
					$html .= '<div class="rise-lp__service-price-block"><div class="rise-lp__service-price-line">';
					if ( self::value( $item, 'price' ) ) { $html .= '<span class="rise-lp__service-price">' . esc_html( self::value( $item, 'price' ) ) . '</span>'; }
					if ( self::value( $item, 'priceQualifier' ) ) { $html .= '<span class="rise-lp__service-price-qualifier">' . esc_html( self::value( $item, 'priceQualifier' ) ) . '</span>'; }
					$html .= '</div>';
					if ( self::value( $item, 'priceNote' ) ) { $html .= '<span class="rise-lp__service-price-note">' . esc_html( self::value( $item, 'priceNote' ) ) . '</span>'; }
					$html .= '</div>';
				}
			}
			$cta = self::cta_defaults( $item );
			if ( ! array_key_exists( 'showCta', $item ) || $item['showCta'] ) {
				$html .= self::button( $cta['label'], $cta['url'], ! empty( $item['ctaNewTab'] ), true );
			}
			$html .= '</div></article>';
		}
		$html .= '</div>';
		if ( $slider ) {
			$html .= '<div class="rise-lp__slider-controls" data-slider-controls hidden><button class="rise-lp__button rise-lp__button--secondary" type="button" data-slide-previous aria-controls="' . esc_attr( $grid_id ) . '">' . self::button_label( __( 'Previous', 'rise-landing-pages' ) ) . '</button>';
			if ( ! isset( $attributes['sliderShowCounter'] ) || $attributes['sliderShowCounter'] ) { $html .= '<span class="rise-lp__slider-status" aria-live="polite"></span>'; }
			$html .= '<button class="rise-lp__button rise-lp__button--secondary" type="button" data-slide-next aria-controls="' . esc_attr( $grid_id ) . '">' . self::button_label( __( 'Next', 'rise-landing-pages' ) ) . '</button></div>';
		}
		return $html . '</div></section>';
	}

	private static function process( $attributes, $block = null ) {
		$id   = wp_unique_id( 'rise-process-' );
		$html = self::section_open( 'process', $attributes, $id ) . self::section_header( $attributes, $id );
		$spacers = '';
		if ( $block instanceof \WP_Block ) {
			foreach ( $block->inner_blocks as $inner_block ) {
				if ( 'rise-landing/spacer' === $inner_block->name ) {
					$spacers .= self::render( 'spacer', $inner_block->attributes );
					break;
				}
			}
		}
		if ( '' !== $spacers ) {
			$html .= '<div class="rise-lp__process-spacers">' . $spacers . '</div>';
		}
		$items = self::items( $attributes, 6 );
		$circle = 'circle' === self::value( $attributes, 'layout', 'classic' );
		$anticlockwise = 'anticlockwise' === self::value( $attributes, 'circleDirection', 'clockwise' );
		$count = count( $items );
		$left_count = (int) ceil( $count / 2 );
		$right_count = $count - $left_count;
		$crowded = false;
		if ( $circle ) {
			foreach ( $items as $item ) {
				$title = self::value( $item, 'title' );
				$description = self::value( $item, 'description' );
				if ( strlen( wp_strip_all_tags( $title ) ) > 40 || preg_match_all( '/<br\s*\/?>/i', $title ) > 2 || strlen( wp_strip_all_tags( $description ) ) > 180 ) {
					$crowded = true;
					break;
				}
			}
		}
		$mode = self::value( $attributes, 'markerMode' );
		if ( ! in_array( $mode, array( 'number', 'global', 'items' ), true ) ) {
			$mode = array_filter( $items, static function ( $item ) { return ! empty( $item['imageUrl'] ); } ) ? 'items' : 'number';
		}
		$shared_image = 'global' === $mode ? self::benefit_marker_url( $attributes ) : '';
		$html .= '<ol class="rise-lp__process-grid' . ( $circle ? ' rise-lp__process-grid--circle' . ( $crowded ? ' rise-lp__process-grid--crowded' : '' ) : '' ) . '"' . ( $circle ? ' style="--rise-process-rows:' . $left_count . ';"' : '' ) . '>';
		foreach ( $items as $index => $item ) {
			$position = $anticlockwise && 0 !== $index ? $count - $index : $index;
			$side = 0 === $position || $position > $right_count ? 'left' : 'right';
			$side_index = 'left' === $side ? ( 0 === $position ? 0 : $count - $position ) : $position - 1;
			$side_count = 'left' === $side ? $left_count : $right_count;
			$angle = 1 === $side_count ? ( 'left' === $side ? -90 : -270 ) : ( 'left' === $side ? -45 : -315 ) + ( 'left' === $side ? -90 : 90 ) * $side_index / ( $side_count - 1 );
			$row = 'left' === $side ? $side_index + 1 : 1 + (int) round( $side_index * ( $left_count - 1 ) / max( 1, $right_count - 1 ) );
			$horizontal_ratio = round( 0.5 - 0.39 * abs( sin( deg2rad( $angle ) ) ), 5 );
			$html .= '<li class="rise-lp__process-step' . ( $circle ? ' rise-lp__process-step--' . $side . ' rise-lp__revealed' : '' ) . '"' . ( $circle ? ' style="--rise-process-row:' . $row . ';--rise-process-angle:' . $angle . 'deg;--rise-process-horizontal-ratio:' . $horizontal_ratio . ';"' : '' ) . '>';
			$image = 'global' === $mode && $shared_image ? '<img class="rise-lp__step-image" src="' . esc_url( $shared_image ) . '" alt="" loading="lazy" decoding="async">' : ( 'items' === $mode ? self::image( array_merge( $item, array( 'mediaFit' => 'contain' ) ), 'rise-lp__step-image' ) : '' );
			$marker = $image ?: '<span class="rise-lp__step-number" aria-hidden="true">' . esc_html( self::value( $item, 'number', sprintf( '%02d', $index + 1 ) ) ) . '</span>';
			$html .= $circle ? '<span class="rise-lp__step-marker">' . $marker . '</span>' : $marker;
			if ( self::value( $item, 'title' ) ) {
				$html .= '<h3 class="rise-lp__card-title">' . self::heading_text( self::value( $item, 'title' ) ) . '</h3>';
			}
			if ( self::value( $item, 'description' ) ) {
				$html .= '<div class="rise-lp__card-copy">' . self::rich_text( self::value( $item, 'description' ) ) . '</div>';
			}
			$html .= '</li>';
		}
		return $html . '</ol></div></section>';
	}

	/** Resolve a selected Media Library image or a direct image URL for a benefit marker. */
	private static function benefit_marker_url( $attributes ) {
		$image_id = absint( self::value( $attributes, 'markerImageId' ) );
		if ( $image_id && 0 === strpos( (string) get_post_mime_type( $image_id ), 'image/' ) ) {
			$url = esc_url( wp_get_attachment_url( $image_id ) );
			if ( $url ) { return $url; }
		}
		$url = esc_url( self::value( $attributes, 'markerSvgUrl' ) );
		return preg_match( '/\.(?:svg|png|jpe?g|gif|webp|avif)$/i', (string) wp_parse_url( $url, PHP_URL_PATH ) ) ? $url : '';
	}

	private static function benefits( $attributes ) {
		$id = wp_unique_id( 'rise-benefits-' );
		$layout = self::value( $attributes, 'layout', 'left' );
		$layout = in_array( $layout, array( 'right', 'center' ), true ) ? $layout : 'left';
		$marker = self::value( $attributes, 'markerStyle', 'check' );
		$marker = in_array( $marker, array( 'none', 'number', 'svg' ), true ) ? $marker : 'check';
		$default_marker_url = self::benefit_marker_url( $attributes );
		$center_items = 'center' === $layout && ! empty( $attributes['centerItems'] );
		$html = self::section_open( 'benefits', $attributes, $id ) . '<div class="rise-lp__benefits-layout rise-lp__benefits-layout--' . esc_attr( $layout ) . ( $center_items ? ' rise-lp__benefits-layout--items-centered' : '' ) . '">' . self::section_header( $attributes, $id );
		$html .= '<ul class="rise-lp__benefits-grid">';
		$visible_index = 0;
		foreach ( self::items( $attributes, 12 ) as $item ) {
			if ( ! empty( $item['hidden'] ) ) { continue; }
			++$visible_index;
			$item_marker_url = self::benefit_marker_url( $item ) ?: $default_marker_url;
			$html .= '<li class="rise-lp__benefit' . ( 'none' === $marker || ( 'svg' === $marker && ! $item_marker_url ) ? ' rise-lp__benefit--no-marker' : '' ) . '">';
			$marker_html = '';
			if ( 'check' === $marker ) {
				$marker_html = '<span class="rise-lp__check" aria-hidden="true">&#10003;</span>';
			} elseif ( 'number' === $marker ) {
				$marker_html = '<span class="rise-lp__check rise-lp__benefit-number" aria-hidden="true">' . esc_html( (string) $visible_index ) . '</span>';
			} elseif ( 'svg' === $marker && $item_marker_url ) {
				$marker_html = '<span class="rise-lp__benefit-icon" aria-hidden="true"><img src="' . esc_url( $item_marker_url ) . '" alt="" /></span>';
			}
			$title_html = '';
			if ( self::value( $item, 'title' ) ) {
				$title_html = '<h3 class="rise-lp__card-title">' . self::heading_text( self::value( $item, 'title' ) ) . '</h3>';
			}
			$copy_html = '';
			if ( self::value( $item, 'description' ) ) {
				$copy_html = '<div class="rise-lp__card-copy">' . self::rich_text( self::value( $item, 'description' ) ) . '</div>';
			}
			if ( $center_items ) {
				$html .= '<div class="rise-lp__benefit-heading">' . $marker_html . $title_html . '</div>' . $copy_html;
			} else {
				$html .= $marker_html . '<div>' . $title_html . $copy_html . '</div>';
			}
			$html .= '</li>';
		}
		return $html . '</ul></div></div></section>';
	}

	private static function faq( $attributes ) {
		$id = wp_unique_id( 'rise-faq-' );
		$items = array_values( array_filter( self::items( $attributes, 20 ), static function ( $item ) {
			return '' !== trim( wp_strip_all_tags( self::value( $item, 'question' ) ) );
		} ) );
		$mode = self::value( $attributes, 'faqMediaMode', 'text' );
		$mode = in_array( $mode, array( 'section', 'items' ), true ) ? $mode : 'text';
		$side = 'right' === self::value( $attributes, 'faqImageSide' ) ? 'right' : 'left';
		$image_animation = self::value( $attributes, 'faqImageAnimation', 'inherit' );
		if ( ! in_array( $image_animation, array( 'fade', 'tiles' ), true ) ) {
			$image_animation = Settings::get()['image_animation'];
		}
		$section_image = self::image( array_merge( $attributes, array( 'mediaType' => 'image' ) ), 'rise-lp__faq-image' );
		$images = array();
		if ( 'section' === $mode && $section_image ) {
			$images['-1'] = $section_image;
		} elseif ( 'items' === $mode ) {
			foreach ( $items as $index => $item ) {
				$image = self::image( array_merge( $item, array( 'mediaType' => 'image' ) ), 'rise-lp__faq-image' );
				if ( $image ) { $images[ (string) $index ] = $image; }
			}
			if ( $section_image ) { $images['-1'] = $section_image; }
		}
		if ( ! $images ) { $mode = 'text'; }
		$layout = 'rise-lp__faq-layout rise-lp__faq-layout--' . ( 'text' === $mode ? 'text' : 'media rise-lp__faq-layout--media-' . $side );
		$html = self::section_open( 'faq', $attributes, $id ) . '<div class="' . esc_attr( $layout ) . '">';
		if ( 'text' !== $mode ) {
			$html .= '<div class="rise-lp__faq-media" data-rise-image-animation="' . esc_attr( $image_animation ) . '"' . ( 'items' === $mode ? ' data-rise-faq-media' : '' ) . '>';
			$first = true;
			foreach ( $images as $index => $image ) {
				$html .= '<figure class="rise-lp__faq-media-frame"' . ( 'items' === $mode ? ' data-rise-faq-media-index="' . esc_attr( $index ) . '"' : '' ) . ( $first ? '' : ' hidden' ) . '>' . $image . '</figure>';
				$first = false;
			}
			$html .= '</div>';
		}
		$html .= '<div class="rise-lp__faq-content">' . self::section_header( $attributes, $id );
		$html .= '<div class="rise-lp__faq-list" data-rise-faq data-single-open="' . ( ! isset( $attributes['singleOpen'] ) || $attributes['singleOpen'] ? 'true' : 'false' ) . '" data-first-open="' . ( ! empty( $attributes['firstOpen'] ) ? 'true' : 'false' ) . '">';
		$dashes = '';
		if ( ! empty( $attributes['showDashes'] ) ) {
			$dashes = '<svg class="rise-lp__faq-dashes" viewBox="0 0 138 32" aria-hidden="true" focusable="false">';
			$x = 0;
			for ( $dash = 0; $dash < 9; $dash++ ) {
				$width = $dash + 2;
				$dashes .= '<path style="--rise-dash-index:' . esc_attr( $dash ) . '" d="M' . esc_attr( $x + 9 ) . ' 1h' . esc_attr( $width ) . 'l-9 30h-' . esc_attr( $width ) . 'z"/>';
				$x += $width + 9;
			}
			$dashes .= '</svg>';
		}
		foreach ( $items as $item ) {
			$item_id = wp_unique_id( 'rise-faq-item-' );
			$html .= '<div class="rise-lp__faq-item"><h3 class="rise-lp__faq-heading"><button class="rise-lp__faq-question" type="button" id="' . esc_attr( $item_id ) . '-question" aria-expanded="true" aria-controls="' . esc_attr( $item_id ) . '-answer"><span class="rise-lp__faq-question-label">' . self::heading_text( self::value( $item, 'question' ) ) . $dashes . '</span><span class="rise-lp__faq-indicator" aria-hidden="true"></span></button></h3>';
			$html .= '<div class="rise-lp__faq-answer" id="' . esc_attr( $item_id ) . '-answer" aria-labelledby="' . esc_attr( $item_id ) . '-question"><div class="rise-lp__faq-answer-inner">' . self::rich_text( self::value( $item, 'answer' ) );
			$pills = array_slice( array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', self::value( $item, 'pills' ) ) ) ), 0, 8 );
			if ( $pills ) {
				$html .= '<ul class="rise-lp__faq-pills">';
				foreach ( $pills as $pill ) { $html .= '<li>' . esc_html( wp_strip_all_tags( $pill ) ) . '</li>'; }
				$html .= '</ul>';
			}
			$html .= '</div></div></div>';
		}
		return $html . '</div></div></div></div></section>';
	}

	private static function cta( $attributes ) {
		$id   = wp_unique_id( 'rise-cta-' );
		$html = self::section_open( 'cta', $attributes, $id ) . '<div class="rise-lp__cta-panel"><div class="rise-lp__cta-copy">';
		if ( self::value( $attributes, 'eyebrow' ) ) {
			$html .= '<p class="rise-lp__eyebrow">' . self::eyebrow_text( self::value( $attributes, 'eyebrow' ) ) . '</p>';
		}
		if ( self::value( $attributes, 'heading' ) ) {
			$html .= '<h2 class="rise-lp__heading" id="' . esc_attr( $id ) . '">' . self::heading_text( self::value( $attributes, 'heading' ) ) . '</h2>';
		}
		if ( self::value( $attributes, 'description' ) ) {
			$html .= '<div class="rise-lp__intro">' . self::rich_text( self::value( $attributes, 'description' ) ) . '</div>';
		}
		$cta   = self::cta_defaults( $attributes );
		$html .= '</div><div class="rise-lp__cta-actions">';
		if ( ! array_key_exists( 'showCta', $attributes ) || $attributes['showCta'] ) {
			$html .= self::button( $cta['label'], $cta['url'], ! empty( $attributes['ctaNewTab'] ) );
		}
		$show_secondary = array_key_exists( 'showSecondary', $attributes ) ? ! empty( $attributes['showSecondary'] ) : ( '' !== trim( self::value( $attributes, 'secondaryLabel' ) ) && '' !== trim( self::value( $attributes, 'secondaryUrl' ) ) );
		if ( $show_secondary ) {
			$html .= self::button( self::value( $attributes, 'secondaryLabel' ), self::value( $attributes, 'secondaryUrl' ), ! empty( $attributes['secondaryNewTab'] ), true, self::value( $attributes, 'secondaryStyle', 'outline' ), self::value( $attributes, 'secondaryBackground' ), self::value( $attributes, 'secondaryText' ) );
		}
		return $html . '</div></div></div></section>';
	}
}
