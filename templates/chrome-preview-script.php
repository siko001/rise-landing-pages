<?php
/** Bridge a preview document to its Gutenberg canvas without parent DOM access. */
defined( 'ABSPATH' ) || exit;
$rise_preview_config = array(
	'header' => \RiseLandingPages\Pages::chrome_choice( $rise_page_id, 'header' ),
	'footer' => \RiseLandingPages\Pages::chrome_choice( $rise_page_id, 'footer' ),
);
?>
<script>window.riseLandingChromePreview=<?php echo wp_json_encode( $rise_preview_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Encoded for a script element. ?>;</script>
<script src="<?php echo esc_url( add_query_arg( 'ver', RISE_LP_VERSION, RISE_LP_URL . 'assets/chrome-preview-frame.js' ) ); ?>"></script>
