<?php
/** Rise Medical's block chrome around a Rise landing page. */
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
	<?php do_action( 'get_header' ); wp_head(); ?>
</head>
<body <?php body_class( 'rise-landing rise-site rise-medical-lp' ); ?> style="<?php echo esc_attr( \RiseLandingPages\Frontend\Frontend::rise_medical_palette_variables() ); ?>">
<?php wp_body_open(); ?>
<div id="app">
	<a class="rise-skip" href="#rise-main"><?php esc_html_e( 'Skip to content', 'rise-landing-pages' ); ?></a>
	<?php if ( 'site' === $rise_header ) : ?>
		<?php echo view( 'sections.header' )->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The active theme renders its own partial. ?>
	<?php endif; ?>
	<div class="<?php echo esc_attr( \RiseLandingPages\Frontend\Frontend::root_classes() ); ?> rise-lp--medical-layout" style="<?php echo esc_attr( \RiseLandingPages\Frontend\Frontend::rise_medical_css_variables() ); ?>">
		<?php require __DIR__ . '/landing-header.php'; ?>
		<main class="rise-lp__main" id="rise-main" tabindex="-1">
			<?php if ( ! \RiseLandingPages\Pages::is_editor_chrome_preview( $rise_page_id ) ) : ?>
				<?php while ( have_posts() ) : the_post(); ?>
					<?php do_action( 'rise_landing_before_content', get_the_ID() ); the_content(); do_action( 'rise_landing_after_content', get_the_ID() ); ?>
				<?php endwhile; ?>
			<?php endif; ?>
		</main>
		<?php require __DIR__ . '/landing-footer.php'; ?>
	</div>
	<?php if ( 'site' === $rise_footer ) : ?>
		<?php echo view( 'sections.footer' )->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- The active theme renders its own partial. ?>
	<?php endif; ?>
</div>
<?php if ( \RiseLandingPages\Pages::is_editor_chrome_preview( $rise_page_id ) ) { require __DIR__ . '/chrome-preview-script.php'; } ?>
<?php do_action( 'get_footer' ); wp_footer(); ?>
</body>
</html>
