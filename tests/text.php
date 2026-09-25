<?php
/** Inline formatting and renderer security checks: wp eval-file tests/text.php. */
use RiseLandingPages\Blocks\Registry;
use RiseLandingPages\Frontend\Renderer;
use RiseLandingPages\Frontend\Text;
use RiseLandingPages\Settings\Settings;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'Run through WP-CLI.' );
}

$checks = 0;
$assert = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	++$checks;
	WP_CLI::log( 'PASS ' . $message );
};
$inline = array( 'strong' => array(), 'em' => array(), 'br' => array() );
$span = static function ( $html ) {
	$processor = new WP_HTML_Tag_Processor( $html );
	if ( ! $processor->next_tag( 'SPAN' ) ) {
		return null;
	}
	return array(
		'class' => $processor->get_attribute( 'class' ),
		'color' => $processor->get_attribute( 'data-rise-outline-color' ),
		'style' => $processor->get_attribute( 'style' ),
		'handler' => $processor->get_attribute( 'onmouseover' ),
	);
};
set_error_handler( static function ( $severity, $message, $file, $line ) {
	throw new ErrorException( $message, 0, $severity, $file, $line );
}, E_WARNING | E_NOTICE | E_USER_WARNING | E_USER_NOTICE );

try {
	$plain = Text::format( 'Start [outline]stronger[/outline] today.', $inline );
	$assert( 'Start <span class="rise-lp__outline">stronger</span> today.' === $plain, 'Colourless markers become native outline spans without PHP warnings' );
	$assert( null === $span( $plain )['style'], 'A colourless outline inherits the configured brand colour' );
	foreach ( array( 'white' => 'white', '"WHITE"' => 'white', "'#abc'" => '#aabbcc', '#A1B2C3' => '#A1B2C3', 'currentColor' => 'currentcolor' ) as $token => $expected ) {
		$html = Text::format( '[outline color=' . $token . ']Visible <strong>content</strong>[/outline]', $inline );
		$attributes = $span( $html );
		$assert( $attributes && $expected === $attributes['color'] && '--rise-outline-color:' . $expected === $attributes['style'], 'Valid named, quoted and hex colours become a single validated property: ' . $token );
		$assert( false !== strpos( $html, '<strong>content</strong>' ) && false === strpos( $html, '[outline' ), 'Outline conversion retains permitted inline emphasis' );
	}
	$spaced = Text::format( '[outline color = "teal" ]Spaced[/outline]', $inline );
	$assert( 'teal' === $span( $spaced )['color'], 'Marker whitespace remains safe and usable' );

	$raw = Text::format( '<span class="extra rise-lp__outline unsafe" data-rise-outline-color="#abc" style="--rise-outline-color:red;position:fixed;inset:0" onmouseover="alert(1)">Hello</span>', $inline );
	$assert( array( 'class' => 'rise-lp__outline', 'color' => '#aabbcc', 'style' => '--rise-outline-color:#aabbcc', 'handler' => null ) === $span( $raw ), 'Toolbar spans retain only the outline class and a rebuilt validated colour property' );
	$ordinary = Text::format( '<span class="rise-lp__button" data-rise-outline-color="red" style="display:none">Ordinary</span>', $inline );
	$assert( array( 'class' => null, 'color' => null, 'style' => null, 'handler' => null ) === $span( $ordinary ), 'Ordinary spans cannot introduce component classes or arbitrary styling' );

	foreach ( array( 'white;position:fixed', 'url(https://example.com/tracker)', 'var(--injected)', 'transparent', '#abc;background:red' ) as $color ) {
		$markup = '<span class="rise-lp__outline" data-rise-outline-color="' . esc_attr( $color ) . '" style="color:red">Visible</span>';
		$attributes = $span( Text::format( $markup, $inline ) );
		$assert( null === $attributes['color'] && null === $attributes['style'], 'Unsafe or invisible native colour does not survive: ' . $color );
		$attributes = $span( Text::format( '[outline color="' . $color . '"]Visible[/outline]', $inline ) );
		$assert( null === $attributes['color'] && null === $attributes['style'], 'Unsafe marker colour falls back to the brand colour' );
	}
	$entity = $span( Text::format( '<span class="rise-lp__outline" data-rise-outline-color="white&#59;background:red">Visible</span>', $inline ) );
	$assert( null === $entity['color'] && null === $entity['style'], 'HTML entities cannot bypass the colour sanitizer' );
	$unsafe = Text::format( '[outline color=white]<script>alert(1)</script><img src=x onerror=alert(1)><a href="javascript:alert(1)">Heading</a>[/outline]', $inline );
	$assert( false === strpos( $unsafe, '<script' ) && false === strpos( $unsafe, '<img' ) && false === strpos( $unsafe, '<a ' ) && false === strpos( $unsafe, 'onerror=' ), 'Outline contents cannot bypass the heading markup allow-list' );
	$assert( '[outline]Unclosed' === Text::format( '[outline]Unclosed', $inline ), 'Unpaired markers remain readable as entered' );
	$assert( '' === Text::format( '', $inline ), 'Empty text remains empty' );

	$font = Renderer::render( 'hero', array( 'heading' => '[outline color=white]Move well[/outline]', 'headingFont' => '"Helvetica Neue", Helvetica, sans-serif', 'headingStyle' => 'italic' ) );
	$processor = new WP_HTML_Tag_Processor( $font );
	$processor->next_tag( 'SECTION' );
	$style = $processor->get_attribute( 'style' );
	$assert( false === strpos( $style, '--rise-font-heading:' ) && false === strpos( $style, '--rise-heading-style:' ), 'Legacy per-block heading font and style no longer override brand typography' );
	$assert( false !== strpos( $font, '--rise-outline-color:white' ) && false === strpos( $font, '[outline' ), 'Hero rendering applies the same outline formatting to published text' );
	$font = Renderer::render( 'hero', array( 'heading' => 'Safe', 'headingFont' => 'Arial; background:url(https://example.com/tracker)', 'headingStyle' => 'italic;display:none' ) );
	$hero_without_button = Renderer::render( 'hero', array( 'heading' => 'No button', 'showCta' => false, 'ctaLabel' => 'Book now', 'ctaUrl' => 'https://example.com/book/' ) );
	$assert( false === strpos( $hero_without_button, 'rise-lp__button' ), 'Hero button can be hidden even with a valid inherited or explicit booking URL' );
	$sized = Renderer::render( 'hero', array( 'heading' => 'Make [size px=80]your move[/size]', 'secondaryLabel' => 'See plans', 'secondaryUrl' => 'https://example.com/plans/', 'secondaryStyle' => 'custom', 'secondaryBackground' => '#ffffff', 'secondaryText' => '#000000' ) );
	$assert( false !== strpos( $sized, '--rise-inline-size:80px' ) && false !== strpos( $sized, 'rise-lp__text-size' ), 'A bounded size marker styles selected heading text' );
	$arbitrary_size = Renderer::render( 'hero', array( 'heading' => '[size px=73]Custom size[/size]', 'headingLeading' => 0.8 ) );
	$assert( false !== strpos( $arbitrary_size, '--rise-inline-size:73px' ) && false !== strpos( $arbitrary_size, '--rise-hero-leading:0.8;' ), 'Arbitrary title sizes and leading render together' );
	$tiny_size = Renderer::render( 'hero', array( 'heading' => 'Small [size px=1]detail[/size]' ) );
	$assert( false !== strpos( $tiny_size, '--rise-inline-size:1px' ), 'One-pixel selected text size is available for special styling' );
	$native_size = Renderer::render( 'hero', array( 'heading' => '<span class="rise-lp__text-size" data-rise-size="64" style="color:red">Sized</span>' ) );
	$assert( false !== strpos( $native_size, '--rise-inline-size:64px' ) && false === strpos( $native_size, 'color:red' ), 'Gutenberg inline-size spans are reconstructed from validated attributes' );
	foreach ( array( 'hero', 'services', 'process', 'benefits', 'faq', 'cta' ) as $block ) {
		$colored = Renderer::render( $block, array( 'heading' => 'Choose <span class="rise-lp__text-color" data-rise-text-color="#61FFD6" style="position:fixed;color:red" onclick="alert(1)">well</span>' ) );
		$assert( false !== strpos( $colored, 'class="rise-lp__text-color"' ) && false !== strpos( $colored, 'data-rise-text-color="#61FFD6"' ) && false !== strpos( $colored, 'style="--rise-inline-text-color:#61FFD6"' ) && false === strpos( $colored, 'position:fixed' ) && false === strpos( $colored, 'onclick=' ), $block . ' heading keeps only a validated selected text colour' );
		$inline_eyebrow = Renderer::render( $block, array( 'eyebrow' => 'Our <span class="rise-lp__text-color" data-rise-text-color="#EC1C2B" style="position:fixed" onclick="alert(1)">services</span>' ) );
		$assert( false !== strpos( $inline_eyebrow, 'Our <span class="rise-lp__text-color" data-rise-text-color="#EC1C2B" style="--rise-inline-text-color:#EC1C2B">services</span>' ) && false === strpos( $inline_eyebrow, 'position:fixed' ) && false === strpos( $inline_eyebrow, 'onclick=' ), $block . ' supporting text keeps a safely selected inline colour' );
		$body_key = in_array( $block, array( 'hero', 'cta' ), true ) ? 'description' : 'intro';
		$inline_body = Renderer::render( $block, array( $body_key => 'Read <span class="rise-lp__text-color" data-rise-text-color="#002E75">more</span>' ) );
		$assert( false !== strpos( $inline_body, 'Read <span class="rise-lp__text-color" data-rise-text-color="#002E75" style="--rise-inline-text-color:#002E75">more</span>' ), $block . ' introduction or description keeps a selected inline colour' );
		$legacy_color = Renderer::render( $block, array( 'textColor' => 'red' ) );
		$assert( false === strpos( $legacy_color, '--rise-eyebrow-color:' ) && false === strpos( $legacy_color, '--rise-heading-color:' ) && false === strpos( $legacy_color, '--rise-intro-color:' ), $block . ' ignores the removed block-wide text colour setting' );
	}
	$colored_items = Renderer::render( 'services', array( 'items' => array( array( 'title' => '<span class="rise-lp__text-color" data-rise-text-color="teal">Service</span>' ) ) ) );
	$assert( false !== strpos( $colored_items, '--rise-inline-text-color:teal' ), 'Card titles render selected text colour' );
	$colored_faq = Renderer::render( 'faq', array( 'items' => array( array( 'question' => '<span class="rise-lp__text-color" data-rise-text-color="#002E75">Question</span>' ) ) ) );
	$assert( false !== strpos( $colored_faq, '--rise-inline-text-color:#002E75' ), 'FAQ questions render selected text colour' );
	$unsafe_text_color = Renderer::render( 'hero', array( 'heading' => '<span class="rise-lp__text-color" data-rise-text-color="red;position:fixed" style="color:red">Unsafe</span>' ) );
	$assert( false === strpos( $unsafe_text_color, 'rise-lp__text-color' ) && false === strpos( $unsafe_text_color, 'position:fixed' ) && false === strpos( $unsafe_text_color, 'color:red' ), 'Invalid heading text colours and arbitrary styles are removed' );
	$assert( false !== strpos( $sized, 'rise-lp__button--custom' ) && false !== strpos( $sized, 'background:#ffffff' ), 'Hero supports an independently styled second button' );
	$hidden_second = Renderer::render( 'hero', array( 'showCta' => false, 'showSecondary' => false, 'secondaryLabel' => 'Schedule', 'secondaryUrl' => 'https://example.com/schedule/' ) );
	$assert( false === strpos( $hidden_second, 'rise-lp__button' ), 'Second Hero button can be hidden while preserving its saved label and destination' );
	$cta_attributes = array( 'sectionBackground' => 'white', 'ctaLabel' => 'Book now', 'ctaUrl' => 'https://example.com/book/', 'secondaryLabel' => 'Ask a question', 'secondaryUrl' => 'https://example.com/contact/', 'secondaryNewTab' => true, 'secondaryStyle' => 'custom', 'secondaryBackground' => '#ffffff', 'secondaryText' => '#000000' );
	$cta_visible = Renderer::render( 'cta', array_merge( $cta_attributes, array( 'showSecondary' => true ) ) );
	$assert( false !== strpos( $cta_visible, 'rise-lp__section--bg-white' ) && false !== strpos( $cta_visible, 'rise-lp__button--custom' ) && false !== strpos( $cta_visible, 'href="https://example.com/contact/"' ) && false !== strpos( $cta_visible, 'target="_blank" rel="noopener noreferrer"' ), 'Call to action supports a white section and a styled second button with new-tab behavior' );
	$cta_hidden = Renderer::render( 'cta', array_merge( $cta_attributes, array( 'showSecondary' => false ) ) );
	$assert( false === strpos( $cta_hidden, 'https://example.com/contact/' ) && false !== strpos( $cta_hidden, 'https://example.com/book/' ), 'Call to action hides only the second button when disabled' );
	$cta_legacy = Renderer::render( 'cta', $cta_attributes );
	$assert( false !== strpos( $cta_legacy, 'https://example.com/contact/' ), 'Existing call to action buttons remain visible before the toggle is set' );
	$benefit_items = array(
		array( 'title' => 'Hidden benefit', 'hidden' => true ),
		array( 'title' => 'First visible benefit' ),
		array( 'title' => 'Second visible benefit' ),
	);
	$benefits = Renderer::render( 'benefits', array( 'heading' => 'Benefits', 'items' => $benefit_items, 'layout' => 'right', 'markerStyle' => 'number', 'sectionBackground' => 'white' ) );
	$assert( false !== strpos( $benefits, 'rise-lp__benefits-layout--right' ) && false !== strpos( $benefits, 'rise-lp__section--bg-white' ), 'Benefits support a right title layout and white background' );
	$assert( false === strpos( $benefits, 'Hidden benefit' ) && 2 === substr_count( $benefits, 'rise-lp__benefit-number' ) && false !== strpos( $benefits, '>1</span>' ) && false !== strpos( $benefits, '>2</span>' ), 'Hidden benefits are omitted and visible benefits are numbered consecutively' );
	$benefits_svg = Renderer::render( 'benefits', array( 'items' => $benefit_items, 'layout' => 'center', 'markerStyle' => 'svg', 'markerSvgUrl' => 'https://example.com/check.svg' ) );
	$assert( false !== strpos( $benefits_svg, 'rise-lp__benefits-layout--center' ) && 2 === substr_count( $benefits_svg, '<img ' ), 'Benefits can use one custom SVG marker in a centered layout' );
	$benefit_items[1]['markerSvgUrl'] = 'https://example.com/first.svg';
	$benefits_individual = Renderer::render( 'benefits', array( 'items' => $benefit_items, 'layout' => 'center', 'centerItems' => true, 'markerStyle' => 'svg', 'markerSvgUrl' => 'https://example.com/default.svg' ) );
	$assert( false !== strpos( $benefits_individual, 'rise-lp__benefits-layout--items-centered' ) && 2 === substr_count( $benefits_individual, 'class="rise-lp__benefit-heading"' ) && 1 === substr_count( $benefits_individual, 'src="https://example.com/first.svg"' ) && 1 === substr_count( $benefits_individual, 'src="https://example.com/default.svg"' ), 'Centered benefit items keep each marker beside its title and support individual SVG markers with a section fallback' );
	unset( $benefit_items[1]['markerSvgUrl'] );
	$benefits_unsafe_svg = Renderer::render( 'benefits', array( 'items' => $benefit_items, 'markerStyle' => 'svg', 'markerSvgUrl' => 'javascript:alert(1)' ) );
	$assert( false === strpos( $benefits_unsafe_svg, 'javascript:' ) && false === strpos( $benefits_unsafe_svg, '<img ' ), 'Unsafe SVG marker URLs are never rendered' );
	$process_items = array( array( 'number' => 'A', 'title' => 'First', 'imageUrl' => 'https://example.com/first.svg' ), array( 'number' => 'B', 'title' => 'Second' ) );
	$process_numbers = Renderer::render( 'process', array( 'items' => $process_items, 'markerMode' => 'number', 'sectionBackground' => 'white', 'stepTitleSize' => 44 ) );
	$assert( false !== strpos( $process_numbers, 'rise-lp__section--bg-white' ) && false !== strpos( $process_numbers, '--rise-process-step-title-size:44px;' ) && 2 === substr_count( $process_numbers, 'rise-lp__step-number' ) && false === strpos( $process_numbers, 'first.svg' ), 'Process can use a white background, title size and number or text markers' );
	$process_shared = Renderer::render( 'process', array( 'items' => $process_items, 'markerMode' => 'global', 'markerSvgUrl' => 'https://example.com/shared.svg' ) );
	$assert( 2 === substr_count( $process_shared, 'src="https://example.com/shared.svg"' ) && false === strpos( $process_shared, 'rise-lp__step-number' ), 'Process can repeat one validated shared marker' );
	$process_individual = Renderer::render( 'process', array( 'items' => $process_items, 'markerMode' => 'items' ) );
	$assert( 1 === substr_count( $process_individual, 'src="https://example.com/first.svg"' ) && false !== strpos( $process_individual, 'object-fit:contain;' ) && 1 === substr_count( $process_individual, 'rise-lp__step-number' ), 'Individual process markers fit within their frame and fall back to step labels when missing' );
	$process_legacy = Renderer::render( 'process', array( 'items' => $process_items ) );
	$assert( false !== strpos( $process_legacy, 'src="https://example.com/first.svg"' ), 'Saved process images remain visible before a marker mode is selected' );
	$process_circle = Renderer::render( 'process', array( 'items' => $process_items, 'layout' => 'circle', 'markerMode' => 'number' ) );
	$assert( false !== strpos( $process_circle, 'rise-lp__process-grid--circle' ) && false !== strpos( $process_circle, '--rise-process-rows:1;' ) && 2 === substr_count( $process_circle, '--rise-process-angle:' ) && 2 === substr_count( $process_circle, 'rise-lp__revealed' ) && 2 === substr_count( $process_circle, 'class="rise-lp__step-marker"' ), 'Circular process layout renders visible numbered steps with connector markers' );
	$process_circle_images = Renderer::render( 'process', array( 'items' => $process_items, 'layout' => 'circle', 'markerMode' => 'global', 'markerSvgUrl' => 'https://example.com/shared.svg' ) );
	$assert( 2 === substr_count( $process_circle_images, '<span class="rise-lp__step-marker"><img class="rise-lp__step-image"' ), 'Circular process images use the same connector markers as numbers' );
	$process_custom_image_background = Renderer::render( 'process', array( 'items' => $process_items, 'layout' => 'circle', 'markerMode' => 'global', 'markerSvgUrl' => 'https://example.com/shared.svg', 'markerImageBackground' => '#d34040' ) );
	$assert( false !== strpos( $process_custom_image_background, '--rise-process-image-background:#d34040;' ), 'A chosen process image circle background reaches the published page' );
	$process_unsafe_image_background = Renderer::render( 'process', array( 'items' => $process_items, 'layout' => 'circle', 'markerImageBackground' => 'red;display:none' ) );
	$assert( false === strpos( $process_unsafe_image_background, 'display:none' ), 'Unsafe process image circle colours do not reach inline CSS' );
	$clockwise_items = array_map( static function ( $number ) { return array( 'number' => (string) $number, 'title' => 'Step ' . $number, 'description' => 'Description ' . $number ); }, range( 1, 6 ) );
	$clockwise_process = Renderer::render( 'process', array( 'items' => $clockwise_items, 'layout' => 'circle', 'markerMode' => 'number' ) );
	preg_match_all( '/--rise-process-angle:([-\d.]+)deg;/', $clockwise_process, $clockwise_angles );
	$assert( array( '-45', '-315', '-270', '-225', '-135', '-90' ) === $clockwise_angles[1] && 6 === substr_count( $clockwise_process, '--rise-process-horizontal-ratio:' ), 'Six process steps advance clockwise with connectors sized for each position' );
	$process_list = static function ( $html ) {
		preg_match( '/<ol class="rise-lp__process-grid.*?<\/ol>/s', $html, $matches );
		return $matches[0];
	};
	foreach ( range( 2, 6 ) as $step_count ) {
		$direction_attributes = array( 'items' => array_slice( $clockwise_items, 0, $step_count ), 'layout' => 'circle', 'markerMode' => 'number' );
		$default_direction = Renderer::render( 'process', $direction_attributes );
		$explicit_clockwise = Renderer::render( 'process', array_merge( $direction_attributes, array( 'circleDirection' => 'clockwise' ) ) );
		$assert( $process_list( $default_direction ) === $process_list( $explicit_clockwise ), $step_count . ' process steps retain the existing clockwise layout by default' );
		$anticlockwise_process = Renderer::render( 'process', array_merge( $direction_attributes, array( 'circleDirection' => 'anticlockwise' ) ) );
		preg_match_all( '/<li class="rise-lp__process-step[^\"]*" style="[^\"]*">/', $explicit_clockwise, $clockwise_positions );
		preg_match_all( '/<li class="rise-lp__process-step[^\"]*" style="[^\"]*">/', $anticlockwise_process, $anticlockwise_positions );
		$reversed_positions = array_merge( array( $clockwise_positions[0][0] ), array_reverse( array_slice( $clockwise_positions[0], 1 ) ) );
		$assert( $reversed_positions === $anticlockwise_positions[0], $step_count . ' anticlockwise process steps retain the first position and reverse all remaining sides, rows and connectors' );
		preg_match_all( '/<span class="rise-lp__step-number" aria-hidden="true">(\d+)<\/span><\/span><h3 class="rise-lp__card-title">Step (\d+)<\/h3><div class="rise-lp__card-copy">Description (\d+)<\/div>/', $anticlockwise_process, $anticlockwise_content );
		$expected_order = array_map( 'strval', range( 1, $step_count ) );
		$assert( $expected_order === $anticlockwise_content[1] && $expected_order === $anticlockwise_content[2] && $expected_order === $anticlockwise_content[3], $step_count . ' anticlockwise process steps preserve marker, title and description order in the document' );
	}
	$invalid_direction = Renderer::render( 'process', array( 'items' => $clockwise_items, 'layout' => 'circle', 'markerMode' => 'number', 'circleDirection' => 'sideways' ) );
	$assert( $process_list( $clockwise_process ) === $process_list( $invalid_direction ), 'Unknown process circle directions fall back to clockwise' );
	$default_numbers = Renderer::render( 'process', array( 'items' => array_fill( 0, 6, array( 'title' => 'Step' ) ), 'layout' => 'circle', 'circleDirection' => 'anticlockwise' ) );
	preg_match_all( '/<span class="rise-lp__step-number" aria-hidden="true">(\d+)<\/span>/', $default_numbers, $default_number_order );
	$assert( array( '01', '02', '03', '04', '05', '06' ) === $default_number_order[1], 'Anticlockwise positions preserve automatic step numbering' );
	$clockwise_items[3]['title'] = str_repeat( 'Long step title ', 6 );
	$process_crowded = Renderer::render( 'process', array( 'items' => $clockwise_items, 'layout' => 'circle' ) );
	$assert( false !== strpos( $process_crowded, 'rise-lp__process-grid--crowded' ) && 6 === substr_count( $process_crowded, 'rise-lp__process-step--' ), 'Long process copy uses a readable vertical timeline' );
	$process_classic = Renderer::render( 'process', array( 'items' => $process_items, 'layout' => 'classic' ) );
	$assert( false === strpos( $process_classic, 'rise-lp__process-grid--circle' ), 'Classic process layout keeps the existing grid markup' );
	$anticlockwise_classic = Renderer::render( 'process', array( 'items' => $process_items, 'layout' => 'classic', 'circleDirection' => 'anticlockwise' ) );
	$assert( $process_list( $process_classic ) === $process_list( $anticlockwise_classic ), 'Circle direction does not change the classic process layout' );
	$process_unsafe = Renderer::render( 'process', array( 'items' => $process_items, 'markerMode' => 'global', 'markerSvgUrl' => 'javascript:alert(1)' ) );
	$assert( false === strpos( $process_unsafe, 'javascript:' ) && 2 === substr_count( $process_unsafe, 'rise-lp__step-number' ), 'Unsafe shared process marker URLs fall back to text' );
	$unsafe_size = Renderer::render( 'hero', array( 'heading' => '<span class="rise-lp__text-size" data-rise-size="999" style="position:fixed">Unsafe</span>' ) );
	$assert( false === strpos( $unsafe_size, 'position:fixed' ) && false === strpos( $unsafe_size, '--rise-inline-size:999px' ), 'Inline text size cannot inject CSS or exceed its bounds' );
	$invalid_size = Renderer::render( 'hero', array( 'heading' => '<span class="rise-lp__text-size" data-rise-size="73evil">Invalid</span>' ) );
	$assert( false === strpos( $invalid_size, '--rise-inline-size:73px' ), 'Size values with non-numeric characters are rejected' );
	$clamped_leading = Renderer::render( 'hero', array( 'headingLeading' => 999 ) );
	$assert( false !== strpos( $clamped_leading, '--rise-hero-leading:1.5;' ), 'Hero leading stays within the supported range' );
	$custom_gaps = Renderer::render( 'hero', array( 'gapEyebrowHeading' => 0, 'gapHeadingDescription' => 72, 'gapDescriptionReassurance' => 18, 'gapTextButtons' => 44, 'gapReassuranceButtons' => 999 ) );
	$assert( false !== strpos( $custom_gaps, '--rise-hero-eyebrow-gap:0px;' ) && false !== strpos( $custom_gaps, '--rise-hero-description-gap:72px;' ) && false !== strpos( $custom_gaps, '--rise-hero-reassurance-gap:18px;' ) && false !== strpos( $custom_gaps, '--rise-hero-button-gap:44px;' ) && false !== strpos( $custom_gaps, '--rise-hero-reassurance-button-gap:240px;' ), 'Hero spacing controls render bounded gaps between text and buttons' );
	$left_hero = Renderer::render( 'hero', array( 'layout' => 'split', 'mediaSide' => 'left' ) );
	$assert( false !== strpos( $left_hero, 'rise-lp__hero--media-left' ), 'Split Hero can place media on the left without changing content order' );
	$banner_hero = Renderer::render( 'hero', array( 'layout' => 'overlay', 'minHeight' => 376, 'maxHeight' => 420 ) );
	$assert( false !== strpos( $banner_hero, '--rise-hero-min-height:376px;' ) && false !== strpos( $banner_hero, '--rise-hero-max-height:420px;' ), 'Overlay Hero renders a bounded viewport height while retaining its minimum' );
	$uncapped_hero = Renderer::render( 'hero', array( 'layout' => 'overlay', 'maxHeight' => 0 ) );
	$assert( false === strpos( $uncapped_hero, '--rise-hero-max-height:' ), 'A zero maximum keeps the original full-viewport Hero behavior' );
	$services = Renderer::render( 'services', array( 'heading' => '[size px=72]Support[/size]', 'headingLeading' => 0.9, 'cardHeadingLeading' => 1.4, 'gridColumns' => 99, 'mobileGridColumns' => 3, 'gridGap' => 35, 'items' => array( array( 'title' => '[size px=50]One[/size]', 'description' => 'A service', 'imageUrl' => 'https://example.com/image.jpg', 'overlayColor' => '#123456', 'overlayOpacity' => 150 ) ) ) );
	$assert( false !== strpos( $services, '--rise-inline-size:72px' ) && false !== strpos( $services, '--rise-inline-size:50px' ) && false !== strpos( $services, '--rise-section-heading-leading:0.9;' ) && false !== strpos( $services, '--rise-card-heading-leading:1.4;' ), 'Section and card headings support selected text size and independent leading' );
	$assert( false !== strpos( $services, '--rise-services-columns:6;' ) && false !== strpos( $services, '--rise-services-mobile-columns:3;' ) && false !== strpos( $services, '--rise-services-grid-gap:35px;' ) && false !== strpos( $services, '--rise-service-overlay-color:#123456;--rise-service-overlay-opacity:0.95' ), 'Service columns, gaps and image overlay are bounded before output' );
	$default_services = Renderer::render( 'services', array() );
	$grid_services = Renderer::render( 'services', array( 'displayMode' => 'grid' ) );
	$assert( false !== strpos( $default_services, 'data-rise-slider' ) && false === strpos( $grid_services, 'data-rise-slider' ), 'Services default to a slider while an explicit grid selection remains a grid' );
	$slider = Renderer::render( 'services', array( 'displayMode' => 'slider' ) );
	$assert( 2 === substr_count( $slider, 'class="rise-lp__button-copy" aria-hidden="true"' ), 'Both slider navigation buttons have an accessible label and decorative hover copy' );
	$pricing = Renderer::render( 'services', array( 'cardLayout' => 'pricing', 'items' => array( array( 'title' => 'Membership', 'badge' => '<Sale>', 'features' => "Sauna\nIce baths\n<script>alert(1)</script>", 'price' => 'EUR 75', 'priceQualifier' => 'Per month', 'priceNote' => 'No commitment', 'description' => 'Classic-only copy' ) ) ) );
	$assert( false !== strpos( $pricing, 'rise-lp__services-grid--pricing' ) && false !== strpos( $pricing, '&lt;Sale&gt;' ) && 3 === substr_count( $pricing, '<li>' ) && false !== strpos( $pricing, '&lt;script&gt;' ) && false === strpos( $pricing, 'Classic-only copy' ) && false !== strpos( $pricing, 'rise-lp__service-price-note' ), 'Pricing cards render escaped badges, features and prices without classic descriptions' );
	$array_features = Renderer::render( 'services', array( 'cardLayout' => 'pricing', 'items' => array( array( 'title' => 'Array features', 'features' => array( 'Sauna', '', '  Ice baths  ', '<Free>' ) ) ) ) );
	$assert( 3 === substr_count( $array_features, '<li>' ) && false !== strpos( $array_features, '<li>Ice baths</li>' ) && false !== strpos( $array_features, '&lt;Free&gt;' ), 'Editable pricing features render as a trimmed, escaped list without empty rows' );
	$image_card = Renderer::render( 'services', array( 'cardLayout' => 'image', 'items' => array( array( 'title' => 'Physiotherapy', 'description' => 'Recovery <strong>support</strong> <script>alert(1)</script>', 'imageUrl' => 'https://example.com/image.jpg' ) ) ) );
	$assert( false !== strpos( $image_card, 'rise-lp__services-grid--image' ) && false !== strpos( $image_card, 'rise-lp__service-media' ) && false !== strpos( $image_card, 'Recovery <strong>support</strong>' ) && false === strpos( $image_card, '<script>' ) && strpos( $image_card, 'rise-lp__card-title' ) < strpos( $image_card, 'rise-lp__card-copy' ), 'Image cards show formatted descriptions below the overlaid title without unsafe markup' );
	$four_column_image = Renderer::render( 'services', array( 'cardLayout' => 'image', 'gridColumns' => 4 ) );
	$assert( false !== strpos( $four_column_image, 'rise-lp__services-grid--image-four' ), 'Four-column image cards receive their responsive grid modifier' );
	$centered_image = Renderer::render( 'services', array( 'cardLayout' => 'image', 'imageGridAlignment' => 'center' ) );
	$assert( false !== strpos( $centered_image, 'rise-lp__services-grid--image-centered' ) && false === strpos( $image_card, 'rise-lp__services-grid--image-centered' ), 'Image card alignment defaults to start and can be centered' );
	$slider_options = Renderer::render( 'services', array( 'displayMode' => 'slider', 'sliderShowCounter' => false, 'sliderLoop' => true, 'sliderAutoplay' => true, 'sliderAutoplayInterval' => 99 ) );
	$assert( false !== strpos( $slider_options, 'data-loop="true"' ) && false !== strpos( $slider_options, 'data-autoplay="true"' ) && false !== strpos( $slider_options, 'data-autoplay-interval="15"' ) && false === strpos( $slider_options, 'rise-lp__slider-status' ), 'Slider options render bounded autoplay timing and optional counter' );
	$processor = new WP_HTML_Tag_Processor( $font );
	$processor->next_tag( 'SECTION' );
	$assert( false === strpos( (string) $processor->get_attribute( 'style' ), '--rise-font-heading' ) && false === strpos( (string) $processor->get_attribute( 'style' ), 'tracker' ), 'Removed font and style attributes produce no unsafe inline CSS' );
	foreach ( Registry::names() as $name ) {
		$assert( '' === Renderer::render( $name, array( 'hidden' => true ) ), $name . ' can be hidden without publishing markup' );
	}

	$button = Renderer::button( 'Book now', 'https://example.com/book/', true );
	$assert( false === strpos( $button, 'rise-lp__button-arrow' ) && false === strpos( $button, '&#8599;' ), 'Booking buttons contain no arrow icon' );
	$assert( false !== strpos( $button, 'rise-lp__button-label' ) && false !== strpos( $button, 'rise-lp__button-copy" aria-hidden="true"' ), 'Button label has an accessible original and decorative sliding copy' );
	$assert( false !== strpos( $button, 'noopener noreferrer' ) && false !== strpos( $button, '(opens in a new tab)' ), 'New-tab booking links retain security attributes and an accessible hint' );
	$faq = Renderer::render( 'faq', array( 'singleOpen' => false, 'firstOpen' => true, 'items' => array( array( 'question' => 'Question', 'answer' => 'Answer' ), array( 'question' => 'Second question', 'answer' => '<a href="javascript:alert(1)">Unsafe link</a>' ) ) ) );
	$assert( false !== strpos( $faq, 'data-single-open="false"' ) && false !== strpos( $faq, 'data-first-open="true"' ), 'FAQ preferences reach the enhancement script as boolean data attributes' );
	$assert( 2 === substr_count( $faq, 'class="rise-lp__faq-item"' ) && 2 === substr_count( $faq, 'aria-expanded="true"' ) && false === strpos( $faq, ' hidden' ), 'Every rendered FAQ answer remains visible before JavaScript' );
	$assert( false === strpos( $faq, 'rise-lp__step-number' ) && false === strpos( $faq, 'rise-lp__button-arrow' ), 'FAQ questions contain no numbering or arrow icon' );
	$assert( 2 === substr_count( $faq, 'class="rise-lp__faq-answer-inner"' ) && false === strpos( $faq, 'javascript:' ), 'Animated FAQ answers have a measurable content wrapper and sanitized links' );
	$second_faq = Renderer::render( 'faq', array( 'items' => array( array( 'question' => 'Question', 'answer' => 'Answer' ) ) ) );
	preg_match_all( '/\sid="([^"]+)"/', $faq . $second_faq, $ids );
	$assert( count( $ids[1] ) === count( array_unique( $ids[1] ) ), 'Multiple FAQ sections have unique disclosure IDs' );

	WP_CLI::success( $checks . ' inline formatting and renderer assertions passed without PHP warnings.' );
} finally {
	restore_error_handler();
}
