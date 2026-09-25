<?php
/**
 * Standalone updater regression tests. Run: php tests/updater.php
 * HTTP, storage, permissions and filesystem operations are isolated from live sites.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Run through PHP CLI.' );
}

define( 'ABSPATH', __DIR__ . '/../../../' );
define( 'MINUTE_IN_SECONDS', 60 );
define( 'HOUR_IN_SECONDS', 3600 );
define( 'RISE_LP_VERSION', '1.1.80' );

class WP_Error {
	private $code;
	private $message;
	public function __construct( $code, $message = '' ) { $this->code = $code; $this->message = $message; }
	public function get_error_message() { return $this->message; }
	public function get_error_code() { return $this->code; }
}

class UpdaterTestFilesystem {
	public $paths = array();
	public $moves = array();
	public $move_ok = true;
	public function exists( $path ) { return in_array( $path, $this->paths, true ); }
	public function move( $source, $destination, $overwrite = false ) { $this->moves[] = array( $source, $destination, $overwrite ); return $this->move_ok; }
}

$state = array();
$assertions = 0;
$fixture = sys_get_temp_dir() . '/rise-updater-' . bin2hex( random_bytes( 8 ) );
mkdir( $fixture . '/config', 0700, true );
require dirname( __DIR__ ) . '/src/Support/GitHubPluginUpdater.php';

function reset_state() {
	$GLOBALS['state'] = array(
		'cache' => array(), 'ttls' => array(), 'hooks' => array(), 'requests' => array(),
		'queue' => array(), 'can_update' => true, 'network' => false, 'version' => RISE_LP_VERSION,
		'nonce' => true, 'user' => 12,
	);
}
function expect( $condition, $message ) {
	if ( ! $condition ) { throw new RuntimeException( $message ); }
	++$GLOBALS['assertions'];
	echo 'PASS ' . $message . "\n";
}
function is_wp_error( $value ) { return $value instanceof WP_Error; }
function add_filter( $name, $callback, $priority = 10, $accepted = 1 ) { $GLOBALS['state']['hooks'][ $name ][ $priority ][] = array( $callback, $accepted ); }
function add_action( $name, $callback, $priority = 10, $accepted = 1 ) { add_filter( $name, $callback, $priority, $accepted ); }
function apply_filters( $name, $value, ...$args ) {
	$hooks = $GLOBALS['state']['hooks'][ $name ] ?? array();
	ksort( $hooks );
	foreach ( $hooks as $callbacks ) {
		foreach ( $callbacks as $entry ) {
			$value = call_user_func_array( $entry[0], array_slice( array_merge( array( $value ), $args ), 0, $entry[1] ) );
		}
	}
	return $value;
}
function get_site_transient( $key ) { return unserialize( serialize( $GLOBALS['state']['cache'][ $key ] ?? false ) ); }
function set_site_transient( $key, $value, $ttl = 0 ) {
	$value = apply_filters( 'pre_set_site_transient_' . $key, $value );
	$GLOBALS['state']['cache'][ $key ] = unserialize( serialize( $value ) );
	$GLOBALS['state']['ttls'][ $key ] = $ttl;
	return true;
}
function delete_site_transient( $key ) { unset( $GLOBALS['state']['cache'][ $key ] ); }
function get_transient( $key ) { return get_site_transient( $key ); }
function set_transient( $key, $value, $ttl = 0 ) { return set_site_transient( $key, $value, $ttl ); }
function delete_transient( $key ) { delete_site_transient( $key ); }
function plugin_basename( $file ) { return basename( dirname( $file ) ) . '/' . basename( $file ); }
function get_plugin_data( $file, $markup = true, $translate = true ) { return array( 'Version' => $GLOBALS['state']['version'] ); }
function current_user_can( $capability ) { return 'update_plugins' === $capability && $GLOBALS['state']['can_update']; }
function get_current_user_id() { return $GLOBALS['state']['user']; }
function is_multisite() { return $GLOBALS['state']['network']; }
function is_network_admin() { return $GLOBALS['state']['network']; }
function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . $path; }
function network_admin_url( $path = '' ) { return 'https://example.test/wp-admin/network/' . $path; }
function self_admin_url( $path = '' ) { return is_network_admin() ? network_admin_url( $path ) : admin_url( $path ); }
function wp_nonce_url( $url, $action ) { return $url . '&_wpnonce=test-nonce'; }
function wp_create_nonce( $action ) { return 'test-nonce'; }
function check_admin_referer( $action ) { if ( ! $GLOBALS['state']['nonce'] ) { throw new RuntimeException( 'nonce-denied' ); } return true; }
function wp_die( $message, $title = '', $args = array() ) { throw new RuntimeException( 'permission-denied: ' . $message ); }
function __( $value, $domain = '' ) { return $value; }
function esc_html__( $value, $domain = '' ) { return esc_html( $value ); }
function esc_html( $value ) { return htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ); }
function esc_attr( $value ) { return esc_html( $value ); }
function esc_url( $value ) { return esc_html( $value ); }
function esc_url_raw( $value ) { return $value; }
function sanitize_text_field( $value ) { return trim( strip_tags( $value ) ); }
function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_-]/', '', strtolower( $value ) ); }
function wp_unslash( $value ) { return stripslashes( $value ); }
function wp_parse_url( $url, $component = -1 ) { return parse_url( $url, $component ); }
function trailingslashit( $path ) { return rtrim( $path, '/\\' ) . '/'; }
function untrailingslashit( $path ) { return rtrim( $path, '/\\' ); }
function wp_normalize_path( $path ) { return str_replace( '\\', '/', $path ); }
function wp_json_encode( $value ) { return json_encode( $value ); }
function wp_tempnam( $name = '', $dir = '' ) { return tempnam( sys_get_temp_dir(), 'rise-test-download-' ); }
function wp_delete_file( $path ) { if ( is_file( $path ) ) { unlink( $path ); } }
function wp_remote_retrieve_body( $response ) { return $response['body'] ?? ''; }
function wp_remote_retrieve_response_code( $response ) { return $response['response']['code'] ?? 0; }
function wp_remote_retrieve_header( $response, $header ) { return $response['headers'][ strtolower( $header ) ] ?? ''; }
function wp_remote_get( $url, $args = array() ) {
	$GLOBALS['state']['requests'][] = array( 'url' => $url, 'args' => $args );
	if ( ! $GLOBALS['state']['queue'] ) { throw new RuntimeException( 'Unexpected HTTP request: ' . $url ); }
	$response = array_shift( $GLOBALS['state']['queue'] );
	if ( is_callable( $response ) ) { $response = $response( $url, $args ); }
	if ( ! is_wp_error( $response ) && ! empty( $args['stream'] ) ) {
		file_put_contents( $args['filename'], $response['body'] ?? '' );
	}
	return $response;
}
function wp_safe_remote_get( $url, $args = array() ) { return wp_remote_get( $url, $args ); }
function response( $payload, $status = 200, $headers = array() ) {
	return array( 'body' => is_string( $payload ) ? $payload : json_encode( $payload ), 'response' => array( 'code' => $status ), 'headers' => $headers );
}
function release( $version, $extra = array() ) {
	return array_merge( array(
		'tag_name' => 'v' . $version, 'draft' => false, 'prerelease' => false,
		'body' => 'Release notes <script>alert(1)</script>',
		'assets' => array( array( 'id' => 123, 'name' => 'rise-landing-pages.zip', 'state' => 'uploaded',
			'browser_download_url' => 'https://github.com/test-owner/test-repo/releases/download/v' . $version . '/rise-landing-pages.zip',
			'url' => 'https://api.github.com/repos/test-owner/test-repo/releases/assets/123' ) ),
	), $extra );
}
function updater( $overrides = array(), $register = true ) {
	$config = require dirname( __DIR__ ) . '/config/github-updater.php';
	$config = array_merge( $config, array( 'owner' => 'test-owner', 'repo' => 'test-repo' ), $overrides );
	file_put_contents( $GLOBALS['fixture'] . '/config/github-updater.php', '<?php return ' . var_export( $config, true ) . ';' );
	$instance = new RiseLandingPages\Support\GitHubPluginUpdater( '/plugins/rise-landing-page-builder/rise-landing-pages.php', $GLOBALS['fixture'] );
	if ( $register ) { $instance->register(); }
	return $instance;
}
function initial_transient() {
	return (object) array( 'checked' => array( 'rise-landing-page-builder/rise-landing-pages.php' => RISE_LP_VERSION ),
		'response' => array( 'other/other.php' => (object) array( 'new_version' => '2.0.0' ) ), 'no_update' => array() );
}
function release_cache_key() {
	foreach ( array_keys( $GLOBALS['state']['cache'] ) as $key ) { if ( 0 === strpos( $key, 'rise_lp_github_release_' ) ) { return $key; } }
	throw new RuntimeException( 'Missing release cache.' );
}

try {
	reset_state();
	$updater = updater( array( 'owner' => '', 'repo' => '' ) );
	$result = $updater->checkForUpdate();
	expect( ! $result['ok'] && ! empty( $result['error'] ) && ! $state['requests'], 'Unconfigured repository reports setup needed without HTTP' );
	expect( isset( $state['hooks']['pre_set_site_transient_update_plugins'], $state['hooks']['network_admin_plugin_action_links_rise-landing-page-builder/rise-landing-pages.php'] ), 'Update and network plugin hooks register independently of admin context' );

	reset_state();
	$updater = updater();
	$state['queue'][] = response( array( release( '1.1.81' ), release( '1.1.90' ), release( '1.1.82' ), release( '9.0.0', array( 'draft' => true ) ), release( '8.0.0', array( 'prerelease' => true ) ), release( '7.0.0-beta.1' ) ) );
	$transient = $updater->injectUpdate( initial_transient() );
	$basename = 'rise-landing-page-builder/rise-landing-pages.php';
	expect( '1.1.90' === $transient->response[ $basename ]->new_version, 'Highest stable release wins over release order, drafts and prereleases' );
	expect( $basename === $transient->response[ $basename ]->plugin && 'rise-landing-pages' === $transient->response[ $basename ]->slug, 'Update uses real installed basename and canonical slug' );
	expect( '6.5' === $transient->response[ $basename ]->requires && '7.4' === $transient->response[ $basename ]->requires_php, 'Update advertises WordPress and PHP requirements' );
	expect( ! isset( $transient->response['other/other.php']->plugin ), 'Other plugin update payloads remain untouched' );
	$updater->injectUpdate( initial_transient() );
	expect( 1 === count( $state['requests'] ) && 6 * HOUR_IN_SECONDS === $state['ttls'][ release_cache_key() ], 'Ordinary checks reuse the six-hour release cache' );
	$info = $updater->pluginInfo( false, 'plugin_information', (object) array( 'slug' => 'rise-landing-pages' ) );
	expect( '1.1.90' === $info->version && false === strpos( $info->sections['changelog'], '<script>' ), 'Version details use the cached release and escape GitHub notes' );
	expect( 'unchanged' === $updater->pluginInfo( 'unchanged', 'plugin_information', (object) array( 'slug' => 'unrelated' ) ), 'Other plugin information is left alone' );
	$state['cache']['update_plugins'] = $transient;
	$state['queue'][] = response( array( release( '1.1.80' ) ) );
	$result = $updater->checkForUpdate();
	expect( $result['ok'] && ! $result['update_available'] && 2 === count( $state['requests'] ), 'Manual check bypasses cached release without a duplicate fetch' );
	$saved = get_site_transient( 'update_plugins' );
	expect( ! isset( $saved->response[ $basename ] ) && isset( $saved->no_update[ $basename ], $saved->response['other/other.php'] ), 'Current release clears only this plugin update and preserves other updates' );
	$state['queue'][] = response( array( release( '1.0.0' ) ) );
	$result = $updater->checkForUpdate();
	expect( $result['ok'] && ! $result['update_available'], 'Older GitHub release never offers a downgrade' );

	foreach ( array(
		'missing ZIP' => array( release( '1.1.81', array( 'assets' => array() ) ) ),
		'draft only' => array( release( '1.1.81', array( 'draft' => true ) ) ),
		'prerelease only' => array( release( '1.1.81', array( 'prerelease' => true ) ) ),
		'non-version tag' => array( release( 'main' ) ),
		'no releases' => array(),
		'invalid JSON' => '{broken',
	) as $label => $payload ) {
		reset_state();
		$updater = updater();
		$state['queue'][] = response( $payload );
		$result = $updater->checkForUpdate();
		expect( ! $result['ok'] && ! empty( $result['error'] ), $label . ' produces a useful failure, not a fabricated ZIP URL' );
		$updater->injectUpdate( initial_transient() );
		expect( 1 === count( $state['requests'] ) && 5 * MINUTE_IN_SECONDS === $state['ttls'][ release_cache_key() ], $label . ' failure is cached for five minutes' );
	}
	foreach ( array( 401, 403, 404, 429, 500 ) as $status ) {
		reset_state();
		$updater = updater();
		$state['queue'][] = response( array(), $status );
		$result = $updater->checkForUpdate();
		expect( ! $result['ok'] && false !== strpos( $result['error'], (string) $status ), 'HTTP ' . $status . ' is reported as an update check failure' );
	}
	reset_state();
	$updater = updater();
	$state['queue'][] = new WP_Error( 'timeout', 'Network timeout' );
	$result = $updater->checkForUpdate();
	expect( ! $result['ok'], 'Network timeout fails without inventing a release' );
	$state['can_update'] = false;
	expect( array( 'deactivate' => 'Deactivate' ) === $updater->pluginActionLinks( array( 'deactivate' => 'Deactivate' ) ), 'Editors do not see the manual update action' );
	try { $updater->handleManualUpdateCheck(); expect( false, 'Unauthorized action must fail' ); }
	catch ( RuntimeException $error ) { expect( 0 === strpos( $error->getMessage(), 'permission-denied' ), 'Manual endpoint checks update_plugins capability' ); }
	$state['can_update'] = true;
	$state['nonce'] = false;
	try { $updater->handleManualUpdateCheck(); expect( false, 'Missing nonce must fail' ); }
	catch ( RuntimeException $error ) { expect( 'nonce-denied' === $error->getMessage(), 'Manual endpoint verifies nonce before contacting GitHub' ); }
	expect( false !== strpos( implode( '', $updater->pluginActionLinks( array() ) ), 'Check for updates' ), 'Administrators receive the manual check link' );
	$_GET = array( 'rise_lp_update_status' => 'error', 'rise_lp_update_error' => '<script>bad</script>' );
	ob_start();
	$updater->manualUpdateNotice();
	$notice = ob_get_clean();
	expect( false === strpos( $notice, '<script>' ) && false !== strpos( $notice, 'notice-error' ), 'Update notices escape external error text' );
	$state['can_update'] = false;
	ob_start();
	$updater->manualUpdateNotice();
	expect( '' === ob_get_clean(), 'Update notices are hidden from editors' );
	$_GET = array();

	reset_state();
	$updater = updater();
	$own_update = array( 'plugin' => $basename, 'type' => 'plugin', 'action' => 'update' );
	$public_package = release( '1.1.81' )['assets'][0]['browser_download_url'];
	$private_package = release( '1.1.81' )['assets'][0]['url'];
	expect( 'existing' === $updater->downloadPackage( 'existing', $public_package, null, $own_update ), 'Downloader respects another handler result' );
	expect( false === $updater->downloadPackage( false, $public_package, null, array( 'plugin' => 'other/other.php' ) ), 'Another plugin download is not intercepted' );
	expect( is_wp_error( $updater->downloadPackage( false, 'https://github.com/test-owner/test-repo/archive/refs/tags/v1.1.81.zip', null, $own_update ) ), 'GitHub source archives cannot replace the packaged update' );
	expect( is_wp_error( $updater->downloadPackage( false, $private_package, null, $own_update ) ), 'Private downloads require a configured token' );
	$state['queue'][] = response( '', 302, array( 'location' => 'https://release-assets.githubusercontent.com/example/plugin.zip?signature=test' ) );
	$state['queue'][] = response( 'PK packaged bytes' );
	$download = $updater->downloadPackage( false, $public_package, null, $own_update );
	expect( is_string( $download ) && 'PK packaged bytes' === file_get_contents( $download ), 'Public ZIP downloads follow a trusted GitHub asset redirect' );
	wp_delete_file( $download );
	expect( ! isset( $state['requests'][0]['args']['headers']['Authorization'] ) && ! isset( $state['requests'][1]['args']['headers']['Authorization'] ), 'Public downloads use no authorization header' );
	foreach ( array( 'https://attacker.test/file.zip', 'http://release-assets.githubusercontent.com/file.zip', 'https://github.com.attacker.test/file.zip', 'https://token@github.com/file.zip', 'https://github.com:444/file.zip' ) as $redirect ) {
		$state['queue'][] = response( '', 302, array( 'location' => $redirect ) );
		$before = count( $state['requests'] );
		$download = $updater->downloadPackage( false, $public_package, null, $own_update );
		$last_request = end( $state['requests'] );
		expect( is_wp_error( $download ) && count( $state['requests'] ) === $before + 1 && ! file_exists( $last_request['args']['filename'] ), 'Unsafe redirect is blocked and temporary file removed: ' . $redirect );
	}
	$state['queue'][] = response( '', 404 );
	expect( is_wp_error( $updater->downloadPackage( false, $public_package, null, $own_update ) ), 'A missing release asset fails the download' );
	$last_request = end( $state['requests'] );
	expect( ! file_exists( $last_request['args']['filename'] ), 'Failed download removes its temporary file' );

	$wp_filesystem = new UpdaterTestFilesystem();
	$source = '/temporary/unpack/rise-landing-pages/';
	$remote = '/temporary/unpack/';
	$wp_filesystem->paths[] = $source . 'rise-landing-pages.php';
	$renamed = $updater->preserveInstallDirectory( $source, $remote, null, $own_update );
	expect( $remote . 'rise-landing-page-builder/' === $renamed && array( $source, $renamed, false ) === $wp_filesystem->moves[0], 'Update renames only its unpacked folder to preserve the installed plugin basename' );
	expect( $source === $updater->preserveInstallDirectory( $source, $remote, null, array( 'plugin' => 'other/other.php' ) ), 'Another plugin extraction is unchanged' );
	$wp_filesystem->paths = array();
	expect( is_wp_error( $updater->preserveInstallDirectory( $source, $remote, null, $own_update ) ), 'An archive without the plugin entry point is rejected' );
	$wp_filesystem->paths = array( $source . 'rise-landing-pages.php', $renamed );
	expect( is_wp_error( $updater->preserveInstallDirectory( $source, $remote, null, $own_update ) ), 'An existing extraction destination is never overwritten' );
	$wp_filesystem->paths = array( $source . 'rise-landing-pages.php' );
	$wp_filesystem->move_ok = false;
	expect( is_wp_error( $updater->preserveInstallDirectory( $source, $remote, null, $own_update ) ), 'A folder rename failure stops the update' );
	$canonical = new RiseLandingPages\Support\GitHubPluginUpdater( '/plugins/rise-landing-pages/rise-landing-pages.php', $fixture );
	expect( $source === $canonical->preserveInstallDirectory( $source, $remote, null, array( 'plugin' => 'rise-landing-pages/rise-landing-pages.php' ) ), 'Canonical production installations need no folder rename' );

	// Constants cannot be undefined in PHP, so credential and override checks run last.
	reset_state();
	$updater = updater();
	$state['queue'][] = response( array( release( '1.1.81' ) ) );
	$updater->checkForUpdate();
	define( 'RISE_LP_GITHUB_TOKEN', 'fake-test-token-never-a-real-secret' );
	define( 'RISE_LP_GITHUB_OWNER', 'test-owner' );
	define( 'RISE_LP_GITHUB_REPO', 'test-repo' );
	$updater = updater( array( 'owner' => 'ignored', 'repo' => 'ignored' ), false );
	$state['queue'][] = response( array( release( '1.1.82' ) ) );
	$transient = $updater->injectUpdate( initial_transient() );
	$last_request = end( $state['requests'] );
	expect( false !== strpos( $last_request['url'], '/test-owner/test-repo/' ), 'wp-config.php owner and repository constants override packaged defaults' );
	expect( 'Bearer ' . RISE_LP_GITHUB_TOKEN === $last_request['args']['headers']['Authorization'], 'Private release checks authenticate to the configured GitHub API' );
	expect( $private_package === $transient->response[ $basename ]->package, 'Private updates use GitHub release asset API URLs' );
	expect( 2 === count( array_filter( array_keys( $state['cache'] ), static function ( $key ) { return 0 === strpos( $key, 'rise_lp_github_release_' ); } ) ), 'Changing credentials creates a separate release cache' );
	expect( false === strpos( serialize( $state['cache'] ), RISE_LP_GITHUB_TOKEN ), 'Update metadata and release caches contain no access token' );
	$state['queue'][] = response( '', 302, array( 'location' => 'https://release-assets.githubusercontent.com/example/private.zip?signature=test' ) );
	$state['queue'][] = response( 'PK private packaged bytes' );
	$before = count( $state['requests'] );
	$download = $updater->downloadPackage( false, $private_package, null, $own_update );
	expect( is_string( $download ) && 'PK private packaged bytes' === file_get_contents( $download ), 'Private release download succeeds through an authenticated asset redirect' );
	wp_delete_file( $download );
	expect( 'Bearer ' . RISE_LP_GITHUB_TOKEN === $state['requests'][ $before ]['args']['headers']['Authorization'] && ! isset( $state['requests'][ $before + 1 ]['args']['headers']['Authorization'] ), 'Private token is sent only to the initial API request and never to the download CDN' );
	expect( 0 === $state['requests'][ $before ]['args']['redirection'] && 0 === $state['requests'][ $before + 1 ]['args']['redirection'], 'Automatic HTTP redirects are disabled to keep authentication scoped' );
	expect( false === $updater->downloadPackage( false, 'https://api.github.com/repos/another/repo/releases/assets/123', null ), 'A private asset belonging to another repository is never authenticated' );

	echo "PASS {$assertions} updater checks\n";
} finally {
	unlink( $fixture . '/config/github-updater.php' );
	rmdir( $fixture . '/config' );
	rmdir( $fixture );
}
