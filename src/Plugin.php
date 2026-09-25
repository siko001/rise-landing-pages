<?php
namespace RiseLandingPages;

/** Composes the independent plugin services. */
final class Plugin {
	public function register() {
		// Register outside the admin guard so cron and WP-CLI can discover updates too.
		( new Support\GitHubPluginUpdater( RISE_LP_FILE, RISE_LP_PATH ) )->register();
		( new Pages() )->register();
		( new Settings\Settings() )->register();
		( new Blocks\Registry() )->register();
		( new Editor\Editor() )->register();
		( new Frontend\Frontend() )->register();
		if ( is_admin() ) {
			( new Admin\Admin() )->register();
		}
	}
}
