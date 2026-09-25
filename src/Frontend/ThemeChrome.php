<?php
namespace RiseLandingPages\Frontend;
defined( 'ABSPATH' ) || exit;

/** Optional adapters for themes that keep their footer in page content. */
final class ThemeChrome {
	public static function footer() {
		// Most themes render their footer through get_footer(). Rise Fitness instead
		// stores a registered rf/footer block (and its ACF values) in the home page.
		if ( 'risefitness' !== get_template() || ! \WP_Block_Type_Registry::get_instance()->is_registered( 'rf/footer' ) ) {
			do_action( 'rise_landing_site_footer' );
			return;
		}
		$source = (int) apply_filters( 'rise_landing_site_footer_source', get_option( 'page_on_front' ) );
		$page = get_post( $source );
		if ( ! $page || 'publish' !== $page->post_status || $page->post_password ) { return; }
		$block = self::find_footer( parse_blocks( $page->post_content ) );
		if ( $block ) {
			// Rendering the saved registered block preserves the source's field data.
			echo render_block( $block ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Registered theme block renderer owns output.
		}
	}

	private static function find_footer( $blocks, $seen = array() ) {
		foreach ( $blocks as $block ) {
			if ( 'rf/footer' === $block['blockName'] ) { return $block; }
			$found = self::find_footer( $block['innerBlocks'], $seen );
			if ( $found ) { return $found; }
			if ( 'core/block' === $block['blockName'] && ! empty( $block['attrs']['ref'] ) ) {
				$id = absint( $block['attrs']['ref'] );
				if ( in_array( $id, $seen, true ) || count( $seen ) > 10 ) { continue; }
				$ref = get_post( $id );
				if ( $ref && 'wp_block' === $ref->post_type && 'publish' === $ref->post_status ) {
					$found = self::find_footer( parse_blocks( $ref->post_content ), array_merge( $seen, array( $id ) ) );
					if ( $found ) { return $found; }
				}
			}
		}
		return null;
	}
}
