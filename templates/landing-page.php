<?php
/**
 * Complete document for a normal WordPress page using the Rise template.
 *
 * @package RiseLandingPages
 */

use RiseLandingPages\Settings\Settings;

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/landing-chrome-data.php';
?><!doctype html>
<html <?php language_attributes(); ?> class="rise-lp-document">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php if ( ! current_theme_supports( 'title-tag' ) ) : ?>
		<title><?php echo esc_html( wp_get_document_title() ); ?></title>
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div class="<?php echo esc_attr( \RiseLandingPages\Frontend\Frontend::root_classes() ); ?>" style="<?php echo esc_attr( Settings::css_variables() ); ?>">
	<a class="rise-lp__skip-link" href="#rise-main"><?php esc_html_e( 'Skip to content', 'rise-landing-pages' ); ?></a>
	<?php require __DIR__ . '/landing-header.php'; ?>
	<main class="rise-lp__main" id="rise-main" tabindex="-1">
		<?php if ( ! \RiseLandingPages\Pages::is_editor_chrome_preview( $rise_page_id ) ) : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			/** Runs inside the plugin root immediately before normal page content. */
			do_action( 'rise_landing_before_content', get_the_ID() );
			the_content();
			wp_link_pages( array( 'before' => '<nav class="rise-lp__page-links" aria-label="' . esc_attr__( 'Page navigation', 'rise-landing-pages' ) . '">', 'after' => '</nav>' ) );
			do_action( 'rise_landing_after_content', get_the_ID() );
		endwhile;
		?>
		<?php endif; ?>
	</main>
	<?php require __DIR__ . '/landing-footer.php'; ?>
</div>
<?php if ( \RiseLandingPages\Pages::is_editor_chrome_preview( $rise_page_id ) ) { require __DIR__ . '/chrome-preview-script.php'; } ?>
<?php wp_footer(); ?>
</body>
</html>
