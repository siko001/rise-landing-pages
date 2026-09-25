<?php
/** Safe inline outline formatting, limited to Rise block text. */
namespace RiseLandingPages\Frontend;

use RiseLandingPages\Settings\Settings;

defined( 'ABSPATH' ) || exit;

final class Text {
	/** Accept paired [outline color=white] markers or the native toolbar format. */
	public static function format( $text, $allowed ) {
		$text = preg_replace_callback(
			'/\[outline(?:\s+color\s*=\s*(?:"([^"<>]*)"|\'([^\'<>]*)\'|([^\s\]<>]+)))?\s*\]((?:(?!\[\/?outline\b).)*?)\[\/outline\]/is',
			static function ( $match ) {
				$color = Settings::sanitize_color( $match[1] ?: ( $match[2] ?: $match[3] ) );
				return '<span class="rise-lp__outline"' . ( $color ? ' data-rise-outline-color="' . esc_attr( $color ) . '"' : '' ) . '>' . $match[4] . '</span>';
			},
			(string) $text
		);
		$text = preg_replace_callback(
			'/\[size\s+px\s*=\s*(\d{1,3})\s*\]((?:(?!\[\/?size\b).)*?)\[\/size\]/is',
			static function ( $match ) {
				$size = (int) $match[1];
				return $size >= 1 && $size <= 200 ? '<span class="rise-lp__text-size" data-rise-size="' . $size . '">' . $match[2] . '</span>' : $match[2];
			},
			$text
		);
		// Never retain user-supplied styles. Rebuild only validated format properties.
		$allowed['span'] = array( 'class' => true, 'data-rise-outline-color' => true, 'data-rise-text-color' => true, 'data-rise-size' => true );
		$html = wp_kses( $text, $allowed );
		$processor = new \WP_HTML_Tag_Processor( $html );
		while ( $processor->next_tag( 'SPAN' ) ) {
			$outline = $processor->has_class( 'rise-lp__outline' );
			$text_color = $processor->has_class( 'rise-lp__text-color' );
			$size_attribute = $processor->has_class( 'rise-lp__text-size' ) ? $processor->get_attribute( 'data-rise-size' ) : '';
			$size = is_string( $size_attribute ) && ctype_digit( $size_attribute ) ? (int) $size_attribute : 0;
			$size = $size >= 1 && $size <= 200 ? $size : 0;
			$classes = array();
			$styles = array();
			if ( $outline ) {
				$classes[] = 'rise-lp__outline';
				$color = Settings::sanitize_color( $processor->get_attribute( 'data-rise-outline-color' ) );
				if ( $color ) {
					$processor->set_attribute( 'data-rise-outline-color', $color );
					$styles[] = '--rise-outline-color:' . $color;
				} else {
					$processor->remove_attribute( 'data-rise-outline-color' );
				}
			} else {
				$processor->remove_attribute( 'data-rise-outline-color' );
			}
			if ( $text_color ) {
				$color = Settings::sanitize_color( $processor->get_attribute( 'data-rise-text-color' ) );
				if ( $color ) {
					$classes[] = 'rise-lp__text-color';
					$processor->set_attribute( 'data-rise-text-color', $color );
					$styles[] = '--rise-inline-text-color:' . $color;
				} else {
					$processor->remove_attribute( 'data-rise-text-color' );
				}
			} else {
				$processor->remove_attribute( 'data-rise-text-color' );
			}
			if ( $size ) {
				$classes[] = 'rise-lp__text-size';
				$processor->set_attribute( 'data-rise-size', (string) $size );
				$styles[] = '--rise-inline-size:' . $size . 'px';
			} else {
				$processor->remove_attribute( 'data-rise-size' );
			}
			if ( $classes ) { $processor->set_attribute( 'class', implode( ' ', $classes ) ); }
			else { $processor->remove_attribute( 'class' ); }
			if ( $styles ) { $processor->set_attribute( 'style', implode( ';', $styles ) ); }
			else { $processor->remove_attribute( 'style' ); }
		}
		return $processor->get_updated_html();
	}
}
