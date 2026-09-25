<?php
namespace RiseLandingPages\Blocks;

use RiseLandingPages\Frontend\Renderer;

final class Registry {
	const NAMES = array( 'hero', 'services', 'process', 'benefits', 'faq', 'cta', 'spacer', 'separator' );

	public function register() {
		add_action( 'init', array( $this, 'blocks' ) );
		add_action( 'init', array( new Patterns(), 'register' ), 11 );
	}

	public function blocks() {
		$asset_file = RISE_LP_PATH . 'build/editor.asset.php';
		$asset = is_readable( $asset_file ) ? require $asset_file : array( 'dependencies' => array(), 'version' => RISE_LP_VERSION );
		wp_register_script( 'rise-landing-editor', RISE_LP_URL . 'build/editor.js', $asset['dependencies'], $asset['version'], true );
		wp_set_script_translations( 'rise-landing-editor', 'rise-landing-pages', RISE_LP_PATH . 'languages' );
		foreach ( self::NAMES as $name ) {
			// The editor controller loads this bundle only for Rise pages. WordPress
			// otherwise enqueues every registered editor script on ordinary pages too.
			$block_type = register_block_type( RISE_LP_PATH . 'blocks/' . $name, array( 'editor_script_handles' => array(), 'render_callback' => static function ( $attributes, $content, $block ) use ( $name ) { return Renderer::render( $name, $attributes, $block ); } ) );
			if ( $block_type ) { $block_type->editor_script_handles = array(); }
		}
		register_block_type( RISE_LP_PATH . 'blocks/hero-part', array( 'render_callback' => '__return_empty_string' ) );
	}

	public static function names() {
		return array_map( static function ( $name ) { return 'rise-landing/' . $name; }, self::NAMES );
	}

	public static function default_content() {
		$blocks = array();
		foreach ( array_diff( self::NAMES, array( 'spacer', 'separator' ) ) as $name ) {
			$blocks[] = array( 'blockName' => 'rise-landing/' . $name, 'attrs' => array(), 'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array() );
		}
		/** Filter parsed WordPress blocks before serializing the new draft. */
		$blocks = apply_filters( 'rise_landing_template_blocks', $blocks );
		return serialize_blocks( is_array( $blocks ) ? $blocks : array() );
	}
}
