<?php
namespace RiseLandingPages\Frontend;
defined( 'ABSPATH' ) || exit;

/** Reuse Divi's own installed font registration when its normal content is absent. */
final class Fonts {
	public static function attach( $style_handle ) {
		if ( ! function_exists( 'et_builder_get_custom_fonts' ) || ! function_exists( 'et_builder_enqueue_user_fonts' ) ) { return; }
		$fonts = et_builder_get_custom_fonts();
		foreach ( $fonts as $name => $data ) {
			// Divi names this face "F37 Judge"; the brand also uses "F37Judge".
			if ( 'F37 Judge' === $name && ! isset( $fonts['F37Judge'] ) ) { $fonts['F37Judge'] = $data; }
		}
		$css = et_builder_enqueue_user_fonts( $fonts );
		if ( $css ) { wp_add_inline_style( $style_handle, $css ); }
	}
}
