<?php
/** Run with: wp eval-file tests/integration.php (plugin active, use a disposable/local site). */
use RiseLandingPages\Pages;
use RiseLandingPages\Blocks\Registry;
use RiseLandingPages\Blocks\Patterns;
use RiseLandingPages\Editor\Editor;
use RiseLandingPages\Frontend\Frontend;
use RiseLandingPages\Frontend\Renderer;
use RiseLandingPages\Settings\Settings;

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 'Run through WP-CLI.' );
}
$ids = array();
$editor_user = 0;
$checks = 0;
$assert = static function ( $condition, $message ) use ( &$checks ) {
	if ( ! $condition ) {
		throw new RuntimeException( $message );
	}
	++$checks;
	WP_CLI::log( 'PASS ' . $message );
};
$initial_user = get_current_user_id();
$admin = get_users( array( 'role' => 'administrator', 'number' => 1, 'fields' => 'ID' ) );
wp_set_current_user( $admin[0] );
try {
	foreach ( Registry::names() as $name ) {
		$type = WP_Block_Type_Registry::get_instance()->get_registered( $name );
		$assert( $type && 3 === $type->api_version && is_callable( $type->render_callback ), $name . ' registered as a dynamic API v3 block' );
	}
	$process_with_spacer = render_block( parse_blocks( '<!-- wp:rise-landing/process {"heading":"Spacing check"} --><!-- wp:rise-landing/spacer {"height":96,"mobileHeight":48} /--><!-- /wp:rise-landing/process -->' )[0] );
	$assert( false !== strpos( $process_with_spacer, '--rise-spacer-height:96px;--rise-spacer-mobile:48px' ), 'Process renders its nested spacer with desktop and mobile heights' );
	$process_two_spacers = render_block( parse_blocks( '<!-- wp:rise-landing/process --><!-- wp:rise-landing/spacer {"height":96} /--><!-- wp:rise-landing/spacer {"height":120} /--><!-- /wp:rise-landing/process -->' )[0] );
	$assert( 1 === substr_count( $process_two_spacers, 'rise-lp__spacer' ) && false === strpos( $process_two_spacers, '--rise-spacer-height:120px' ), 'Process renders at most one nested spacer' );
	$hero_with_parts = render_block( parse_blocks( '<!-- wp:rise-landing/hero {"eyebrow":"Old text","heading":"Old heading"} --><!-- wp:rise-landing/hero-part {"role":"eyebrow","content":"New text"} /--><!-- wp:rise-landing/hero-part {"role":"heading","content":"New heading","gap":19} /--><!-- wp:rise-landing/hero-part {"role":"description","content":"New description","gap":41} /--><!-- /wp:rise-landing/hero -->' )[0] );
	$assert( false !== strpos( $hero_with_parts, 'New text' ) && false !== strpos( $hero_with_parts, 'New heading' ) && false === strpos( $hero_with_parts, 'Old heading' ) && false !== strpos( $hero_with_parts, 'margin-top:19px' ) && false !== strpos( $hero_with_parts, 'margin-top:41px' ), 'Hero renders inner content and its own spacing' );
	$hero_with_spacer = render_block( parse_blocks( '<!-- wp:rise-landing/hero --><!-- wp:rise-landing/hero-part {"role":"heading","content":"Hero title"} /--><!-- wp:rise-landing/spacer {"height":96,"mobileHeight":48} /--><!-- wp:rise-landing/hero-part {"role":"description","content":"Hero description"} /--><!-- /wp:rise-landing/hero -->' )[0] );
	$assert( strpos( $hero_with_spacer, 'Hero title' ) < strpos( $hero_with_spacer, '--rise-spacer-height:96px;--rise-spacer-mobile:48px' ) && strpos( $hero_with_spacer, '--rise-spacer-height:96px;--rise-spacer-mobile:48px' ) < strpos( $hero_with_spacer, 'Hero description' ), 'Hero keeps nested spacers between their surrounding content blocks' );
	$hero_spacer_slots = render_block( parse_blocks( '<!-- wp:rise-landing/hero {"heading":"Title","description":"Description","reassurance":"Reassurance"} --><!-- wp:rise-landing/spacer {"heroSlot":"after-heading","height":42} /--><!-- wp:rise-landing/spacer {"heroSlot":"after-description","height":64} /--><!-- wp:rise-landing/spacer {"heroSlot":"after-heading","height":99} /--><!-- /wp:rise-landing/hero -->' )[0] );
	$assert( strpos( $hero_spacer_slots, 'Title' ) < strpos( $hero_spacer_slots, '--rise-spacer-height:42px' ) && strpos( $hero_spacer_slots, '--rise-spacer-height:42px' ) < strpos( $hero_spacer_slots, 'Description' ) && strpos( $hero_spacer_slots, 'Description' ) < strpos( $hero_spacer_slots, '--rise-spacer-height:64px' ) && strpos( $hero_spacer_slots, '--rise-spacer-height:64px' ) < strpos( $hero_spacer_slots, 'Reassurance' ) && false === strpos( $hero_spacer_slots, '--rise-spacer-height:99px' ), 'Hero places at most one spacer in each text gap' );
	$hero_edge_spacers = render_block( parse_blocks( '<!-- wp:rise-landing/hero {"eyebrow":"Eyebrow","heading":"Title","ctaLabel":"Book now","ctaUrl":"https://example.com"} --><!-- wp:rise-landing/spacer {"heroSlot":"before-eyebrow","height":24} /--><!-- wp:rise-landing/spacer {"heroSlot":"after-eyebrow","height":36} /--><!-- wp:rise-landing/spacer {"heroSlot":"after-actions","height":48} /--><!-- /wp:rise-landing/hero -->' )[0] );
	$assert( false === strpos( $hero_edge_spacers, '--rise-spacer-height:24px' ) && strpos( $hero_edge_spacers, 'Eyebrow' ) < strpos( $hero_edge_spacers, '--rise-spacer-height:36px' ) && strpos( $hero_edge_spacers, '--rise-spacer-height:36px' ) < strpos( $hero_edge_spacers, 'Title' ) && false === strpos( $hero_edge_spacers, '--rise-spacer-height:48px' ), 'Hero allows a spacer between supporting text and title while omitting outer spacers' );
	$assert( strpos( $process_with_spacer, 'rise-lp__section-header' ) < strpos( $process_with_spacer, 'rise-lp__process-spacers' ) && strpos( $process_with_spacer, 'rise-lp__process-spacers' ) < strpos( $process_with_spacer, 'rise-lp__process-grid' ), 'Process places its spacer between the intro and steps' );
	$process_without_spacer = Renderer::render( 'process', array() );
	$assert( false === strpos( $process_without_spacer, 'rise-lp__process-spacers' ), 'Existing Process blocks keep their original spacing' );
	$services_with_spacer = render_block( parse_blocks( '<!-- wp:rise-landing/services {"heading":"Spacing check","intro":"Service introduction"} --><!-- wp:rise-landing/spacer {"height":96,"mobileHeight":48} /--><!-- /wp:rise-landing/services -->' )[0] );
	$assert( false !== strpos( $services_with_spacer, '--rise-spacer-height:96px;--rise-spacer-mobile:48px' ), 'Services renders its nested spacer with desktop and mobile heights' );
	$assert( strpos( $services_with_spacer, 'rise-lp__section-header' ) < strpos( $services_with_spacer, 'rise-lp__services-spacers' ) && strpos( $services_with_spacer, 'rise-lp__services-spacers' ) < strpos( $services_with_spacer, 'rise-lp__services-grid' ), 'Services places its spacer between the intro and cards' );
	$services_two_spacers = render_block( parse_blocks( '<!-- wp:rise-landing/services --><!-- wp:rise-landing/spacer {"height":96} /--><!-- wp:rise-landing/spacer {"height":120} /--><!-- /wp:rise-landing/services -->' )[0] );
	$assert( 1 === substr_count( $services_two_spacers, 'rise-lp__spacer' ) && false === strpos( $services_two_spacers, '--rise-spacer-height:120px' ), 'Services renders at most one nested spacer' );
	$services_without_spacer = Renderer::render( 'services', array( 'gapIntroCards' => 61 ) );
	$assert( false === strpos( $services_without_spacer, 'rise-lp__services-spacers' ) && false !== strpos( $services_without_spacer, '--rise-services-cards-gap:61px' ), 'Existing Services blocks keep their original spacing' );
	$pattern_registry = WP_Block_Patterns_Registry::get_instance();
	$assert( 5 === count( Patterns::names() ) && $pattern_registry->is_registered( Patterns::names()[0] ), 'Five Rise campaign patterns are registered' );
	foreach ( Patterns::names() as $pattern_name ) {
		$pattern = $pattern_registry->get_registered( $pattern_name );
		$pattern_blocks = parse_blocks( $pattern['content'] );
		$assert( $pattern_blocks && ! array_diff( array_column( $pattern_blocks, 'blockName' ), Registry::names() ), $pattern_name . ' contains only Rise blocks' );
	}
	$cross_brand = parse_blocks( $pattern_registry->get_registered( 'rise-landing/cross-brand-pathway' )['content'] );
	$assert( ! empty( $cross_brand[1]['attrs']['items'][0]['requireExplicitCta'] ), 'Cross-brand cards require their own destination' );
	$assert( '' === Renderer::cta_defaults( array( 'ctaLabel' => 'Explore Rise Fitness', 'requireExplicitCta' => true ) )['url'], 'Cross-brand links cannot inherit the current site booking URL' );
	$site_new_page_defaults = static function () { return Settings::defaults(); };
	add_filter( 'pre_option_' . Settings::OPTION, $site_new_page_defaults );
	$id = Pages::create();
	remove_filter( 'pre_option_' . Settings::OPTION, $site_new_page_defaults );
	$assert( ! is_wp_error( $id ), 'Create returns a page ID' );
	$ids[] = $id;
	$post = get_post( $id );
	$assert( 'page' === $post->post_type && 'draft' === $post->post_status, 'Created content is a normal draft page' );
	$assert( Pages::is_landing( $id ) && Pages::TEMPLATE === get_page_template_slug( $id ), 'Marker and plugin template assigned' );
	$assert( 'site' === get_post_meta( $id, '_rise_landing_header', true ) && 'site' === get_post_meta( $id, '_rise_landing_footer', true ) && 'site' === Pages::chrome_choice( $id, 'header' ) && 'site' === Pages::chrome_choice( $id, 'footer' ), 'New landing pages use the site header and footer by default' );
	$custom_new_page_defaults = static function () {
		return array_merge( Settings::defaults(), array( 'default_header' => 'minimal', 'default_footer' => 'hidden' ) );
	};
	add_filter( 'pre_option_' . Settings::OPTION, $custom_new_page_defaults );
	$custom_id = Pages::create();
	remove_filter( 'pre_option_' . Settings::OPTION, $custom_new_page_defaults );
	$assert( ! is_wp_error( $custom_id ), 'Create accepts configured new page defaults' );
	$ids[] = $custom_id;
	$assert( 'minimal' === get_post_meta( $custom_id, '_rise_landing_header', true ) && 'hidden' === get_post_meta( $custom_id, '_rise_landing_footer', true ) && 'minimal' === Pages::chrome_choice( $custom_id, 'header' ) && 'hidden' === Pages::chrome_choice( $custom_id, 'footer' ), 'New drafts use the configured header and footer independently' );
	$assert( 'site' === Pages::chrome_choice( $id, 'header' ) && 'site' === Pages::chrome_choice( $id, 'footer' ), 'Changing new page defaults does not alter an existing draft' );
	$blocks = parse_blocks( $post->post_content );
	$assert( array_values( array_diff( Registry::names(), array( 'rise-landing/spacer', 'rise-landing/separator' ) ) ) === array_column( $blocks, 'blockName' ), 'All six sections inserted in the intended order' );
	$assert( empty( $blocks[0]['attrs']['lock'] ), 'Default sections may be moved and removed' );
	$context = new WP_Block_Editor_Context( array( 'post' => $post ) );
	$assert( array_merge( Registry::names(), array( 'rise-landing/hero-part' ) ) === get_allowed_block_types( $context ), 'Landing editor allows eight sections and their Hero content block' );
	$enable = static function ( $names ) { $names[] = 'core/image'; return $names; };
	add_filter( 'rise_landing_allowed_blocks', $enable );
	$assert( in_array( 'core/image', get_allowed_block_types( $context ), true ), 'Allow-list filter enables an explicit additional block' );
	remove_filter( 'rise_landing_allowed_blocks', $enable );
	$normal_id = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'draft', 'post_title' => 'Rise temporary normal-page check' ) );
	$ids[] = $normal_id;
	$shared_title = 'Rise temporary shared title ' . $id;
	wp_update_post( array( 'ID' => $normal_id, 'post_title' => $shared_title ) );
	wp_update_post( array( 'ID' => $id, 'post_title' => $shared_title ) );
	$assert( $shared_title . ' (2)' === get_post_field( 'post_title', $id, 'raw' ), 'Duplicate landing page names receive a suffix on save' );
	$assert( sanitize_title( $shared_title . ' (2)' ) === get_post_field( 'post_name', $id, 'raw' ), 'A renamed draft receives a slug matching its final title' );
	$renamed_title = 'Rise temporary renamed page ' . $id;
	wp_update_post( array( 'ID' => $id, 'post_title' => $renamed_title ) );
	$assert( $renamed_title === get_post_field( 'post_title', $id, 'raw' ) && sanitize_title( $renamed_title ) === get_post_field( 'post_name', $id, 'raw' ), 'A later title edit updates both title and slug' );
	$check = new WP_REST_Request( 'POST', '/rise-landing/v1/title-check/' . $id );
	$check->set_param( 'title', $shared_title );
	$checked = rest_do_request( $check );
	$assert( 200 === $checked->get_status() && $checked->get_data()['duplicate'] && $shared_title . ' (2)' === $checked->get_data()['title'], 'Editor title warning previews the same suffix as save' );
	$editor_user = wp_create_user( 'rise_editor_' . wp_generate_password( 12, false ), wp_generate_password( 24 ), 'rise-editor-' . $id . '@example.test' );
	$assert( ! is_wp_error( $editor_user ), 'Temporary Editor account created for access checks' );
	( new WP_User( $editor_user ) )->set_role( 'editor' );
	wp_set_current_user( $editor_user );
	$assert( current_user_can( 'edit_pages' ) && current_user_can( Settings::CAPABILITY ) && ! current_user_can( 'manage_options' ), 'Editors have access to landing pages and brand settings without administrator capabilities' );
	$assert( Settings::sanitize( array( 'brand_name' => 'Editor brand' ) )['brand_name'] === 'Editor brand', 'Editors may save the plugin settings' );
	$assert( Settings::CAPABILITY === apply_filters( 'option_page_capability_' . Settings::OPTION, 'manage_options' ), 'Native Settings API accepts Editor saves' );
	$assert( 200 === rest_do_request( $check )->get_status(), 'Editors can check landing page names' );
	wp_set_current_user( $admin[0] );
	$normal = new WP_Block_Editor_Context( array( 'post' => get_post( $normal_id ) ) );
	$editor = new Editor();
	$assert( true === $editor->allowed_blocks( true, $normal ), 'Normal pages retain an unrestricted inserter' );
	$assert( array( 'core/paragraph' ) === $editor->allowed_blocks( array( 'core/paragraph' ), $normal ), 'Existing third-party restrictions on normal pages are preserved' );
	$assert( false === $editor->use_editor( false, get_post( $normal_id ) ) && true === $editor->use_editor( false, $post ), 'Gutenberg override applies only to Rise pages' );
	$editor_settings = $editor->settings( array(), $context );
	$assert( true === $editor_settings['canLockBlocks'] && false === $editor_settings['codeEditingEnabled'], 'Clients can manage locks; code editing remains disabled' );
	$assert( array() === $editor->settings( array(), $normal ), 'Normal editor settings are untouched' );
	wp_update_post( array( 'ID' => $id, 'post_title' => wp_slash( 'QA O\'Brien "Campaign"' ), 'post_content' => wp_slash( $post->post_content ), 'post_name' => 'rise-temporary-campaign' ) );
	update_post_meta( $id, '_rise_landing_cta_label', 'Talk to Rise' );
	update_post_meta( $id, '_rise_landing_booking_url', 'https://example.com/book/' );
	update_post_meta( $id, '_unrelated_revision_junk', 'do not copy' );
	$copy = Pages::duplicate( $id );
	$ids[] = $copy;
	$copy_two = Pages::duplicate( $id );
	$ids[] = $copy_two;
	$assert( 'draft' === get_post_status( $copy ) && 'page' === get_post_type( $copy ), 'Duplicate is a normal draft page' );
	$assert( 'Copy of QA O\'Brien "Campaign"' === get_post_field( 'post_title', $copy, 'raw' ), 'Duplicate preserves punctuation and prefixes the title' );
	$assert( get_post_field( 'post_content', $copy ) === get_post_field( 'post_content', $id ), 'Duplicate preserves Gutenberg content exactly' );
	$assert( 'Talk to Rise' === get_post_meta( $copy, '_rise_landing_cta_label', true ) && Pages::TEMPLATE === get_page_template_slug( $copy ), 'Duplicate copies Rise settings and template' );
	$assert( ! metadata_exists( 'post', $copy, '_unrelated_revision_junk' ), 'Duplicate excludes unrelated metadata' );
	$assert( get_post_field( 'post_name', $copy ) !== get_post_field( 'post_name', $copy_two ), 'Repeated copies get unique slugs even as drafts' );
	$assert( is_wp_error( Pages::duplicate( $normal_id ) ), 'Duplicate rejects ordinary pages' );
	wp_update_post( array( 'ID' => $id, 'post_status' => 'publish' ) );
	$assert( false === strpos( get_permalink( $id ), '/landing-page/' ), 'Permalinks have no custom-post-type base' );
	$assert( false === strpos( get_permalink( $id ), '/rise-landing/' ), 'Native page permalink used' );
	$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $id ) );
	$GLOBALS['post'] = get_post( $id );
	$frontend = new Frontend();
	$assert( RISE_LP_PATH . 'templates/site-page.php' === $frontend->template( '/theme/page.php' ), 'New page uses the site header and footer template' );
	update_post_meta( $id, '_rise_landing_footer', 'landing' );
	$assert( 'site' === Pages::chrome_choice( $id, 'header' ) && 'landing' === Pages::chrome_choice( $id, 'footer' ) && RISE_LP_PATH . 'templates/site-page.php' === $frontend->template( '/theme/page.php' ), 'Site header and landing footer render independently' );
	update_post_meta( $id, '_rise_landing_header', 'minimal' );
	update_post_meta( $id, '_rise_landing_footer', 'site' );
	$assert( 'minimal' === Pages::chrome_choice( $id, 'header' ) && 'site' === Pages::chrome_choice( $id, 'footer' ) && RISE_LP_PATH . 'templates/site-page.php' === $frontend->template( '/theme/page.php' ), 'Landing header and site footer render independently' );
	update_post_meta( $id, '_rise_landing_layout', 'site' );
	$assert( 'site' === Pages::chrome_choice( $id, 'header' ) && 'site' === Pages::chrome_choice( $id, 'footer' ), 'Existing website layout keeps both site parts' );
	$_GET['rise_lp_editor_preview'] = '1';
	$_GET['rise_lp_editor_header'] = 'hidden';
	$_GET['rise_lp_editor_footer'] = 'landing';
	$assert( Pages::is_editor_chrome_preview( $id ) && 'hidden' === Pages::chrome_choice( $id, 'header' ) && 'landing' === Pages::chrome_choice( $id, 'footer' ) && RISE_LP_PATH . 'templates/landing-page.php' === $frontend->template( '/theme/page.php' ), 'Editor preview uses unsaved chrome choices' );
	unset( $_GET['rise_lp_editor_preview'], $_GET['rise_lp_editor_header'], $_GET['rise_lp_editor_footer'] );
	update_post_meta( $id, '_rise_landing_layout', 'standalone' );
	update_post_meta( $id, '_rise_landing_footer', 'landing' );
	$frontend->enqueue();
	$assert( wp_style_is( 'rise-landing-frontend', 'enqueued' ) && wp_script_is( 'rise-landing-frontend', 'enqueued' ), 'Frontend assets load on landing page' );
	wp_dequeue_style( 'rise-landing-frontend' );
	wp_dequeue_script( 'rise-landing-frontend' );
	$GLOBALS['wp_query'] = new WP_Query( array( 'page_id' => $normal_id, 'post_status' => 'draft' ) );
	$assert( '/theme/page.php' === $frontend->template( '/theme/page.php' ), 'Ordinary page uses the theme template' );
	$frontend->enqueue();
	$assert( ! wp_style_is( 'rise-landing-frontend', 'enqueued' ) && ! wp_script_is( 'rise-landing-frontend', 'enqueued' ), 'Frontend assets stay off ordinary pages' );
	$html = do_blocks( get_post_field( 'post_content', $id ) );
	$assert( 6 === substr_count( $html, '<section' ), 'Default content renders all six sections' );
	$assert( false !== strpos( $html, 'aria-controls=' ) && false !== strpos( $html, 'aria-expanded=' ), 'FAQ renders accessible disclosure controls' );
	$assert( '' === Renderer::render( 'hero', array( 'hidden' => true ) ), 'Hidden section produces no frontend markup' );
	$separator = Renderer::render( 'separator', array( 'background' => 'medical', 'direction' => 'right', 'gap' => 36, 'speed' => 120, 'texts' => array( 'Move better', '<script>bad</script>Rise' ), 'logoChoice' => 'none', 'dividerLogoChoice' => 'none' ) );
	$assert( false !== strpos( $separator, 'rise-lp__separator--medical' ) && false !== strpos( $separator, 'data-direction="right"' ) && false !== strpos( $separator, 'data-speed="120"' ) && false !== strpos( $separator, '--rise-separator-gap:36px' ), 'Separator renders selected colour and marquee controls' );
	$assert( 2 === substr_count( $separator, 'rise-lp__separator-text' ) && false === strpos( $separator, '<script' ), 'Separator renders each phrase once and escapes unsafe text' );
	$scroll_separator = Renderer::render( 'separator', array( 'motion' => 'scroll', 'direction' => 'right', 'scrollSpeed' => 125, 'texts' => array( '[outline color=white]Move with Rise[/outline]' ) ) );
	$assert( false !== strpos( $scroll_separator, 'data-motion="scroll"' ) && false !== strpos( $scroll_separator, 'data-direction="right"' ) && false !== strpos( $scroll_separator, 'data-scroll-speed="125"' ), 'Separator exposes scroll-linked movement and direction' );
	$assert( false !== strpos( $scroll_separator, 'class="rise-lp__outline"' ) && false !== strpos( $scroll_separator, '--rise-outline-color:white' ) && false === strpos( $scroll_separator, '[outline' ), 'Separator phrase keeps the selected outline format' );
	$still_separator = Renderer::render( 'separator', array( 'motion' => 'none' ) );
	$assert( false !== strpos( $still_separator, 'data-motion="none"' ), 'Separator supports a still strip' );
	$default_separator = Renderer::render( 'separator', array( 'texts' => array( 'Move<br>with Rise' ) ) );
	$assert( false !== strpos( $default_separator, 'rise-lp__separator--white' ) && false !== strpos( $default_separator, 'Move with Rise' ) && false === strpos( $default_separator, '<br' ), 'Separator defaults to white and keeps legacy line breaks on one line' );
	$no_wordmark = Renderer::render( 'separator', array( 'showMainLogo' => false, 'texts' => array( 'Move with Rise' ) ) );
	$assert( 1 === substr_count( $no_wordmark, 'rise-lp__separator-logo--divider' ) && false === strpos( $no_wordmark, 'rise-lp__separator-logo--primary' ), 'Turning off the RISE wordmark leaves one divider icon per phrase' );
	$unsafe = Renderer::render( 'hero', array( 'heading' => '<script>alert(1)</script><strong>Safe</strong>', 'ctaLabel' => 'Click', 'ctaUrl' => 'javascript:alert(1)' ) );
	$assert( false === strpos( $unsafe, '<script' ) && false === strpos( $unsafe, 'javascript:' ), 'Renderer strips executable markup and unsafe CTA URLs' );
	$assert( 'hidden' === Pages::sanitize_meta( '_rise_landing_header', 'hidden' ) && 'site' === Pages::sanitize_meta( '_rise_landing_header', 'site' ) && 'site' === Pages::sanitize_meta( '_rise_landing_footer', 'site' ) && 'minimal' === Pages::sanitize_meta( '_rise_landing_header', 'garbage' ), 'Page choices sanitized' );
	$assert( '' === Pages::sanitize_meta( '_rise_landing_booking_url', 'javascript:alert(1)' ), 'Unsafe page booking URL rejected' );
	$assert( false !== strpos( Settings::css_variables(), '--rise-primary:' ), 'Brand settings generate scoped custom properties' );
	wp_set_current_user( 0 );
	$request = new WP_REST_Request( 'POST', '/wp/v2/pages/' . $id );
	$request->set_param( 'meta', array( '_rise_landing_cta_label' => 'Unauthorized' ) );
	$response = rest_do_request( $request );
	$assert( $response->get_status() >= 400 && 'Talk to Rise' === get_post_meta( $id, '_rise_landing_cta_label', true ), 'Unauthenticated REST updates cannot alter Rise page meta' );
	WP_CLI::success( $checks . ' integration assertions passed.' );
} finally {
	wp_set_current_user( $admin[0] );
	foreach ( $ids as $cleanup_id ) {
		wp_delete_post( $cleanup_id, true );
	}
	if ( $editor_user && ! is_wp_error( $editor_user ) ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $editor_user );
	}
	wp_set_current_user( $initial_user );
	wp_reset_query();
}
