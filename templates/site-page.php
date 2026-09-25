<?php
/** Use site chrome without the theme's normal page title/sidebar/content container. */
use RiseLandingPages\Settings\Settings;
use RiseLandingPages\Frontend\ThemeChrome;
defined( 'ABSPATH' ) || exit;
require __DIR__ . '/landing-chrome-data.php';
get_header();
if ( ! did_action( 'wp_body_open' ) ) { wp_body_open(); }
?>
<div class="<?php echo esc_attr( \RiseLandingPages\Frontend\Frontend::root_classes() ); ?> rise-lp--site-layout" style="<?php echo esc_attr( Settings::css_variables() ); ?>">
	<a class="rise-lp__skip-link" href="#rise-main"><?php esc_html_e( 'Skip to content', 'rise-landing-pages' ); ?></a>
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
<?php if ( 'site' === $rise_footer ) { ThemeChrome::footer(); } ?>
<?php if ( \RiseLandingPages\Pages::is_editor_chrome_preview( $rise_page_id ) ) { require __DIR__ . '/chrome-preview-script.php'; } ?>
<?php get_footer(); ?>
