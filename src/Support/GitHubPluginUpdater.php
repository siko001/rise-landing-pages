<?php

declare(strict_types=1);

namespace RiseLandingPages\Support;

/** Updates this installation from a packaged, stable GitHub release. */
final class GitHubPluginUpdater
{
    private string $pluginFile;
    private array $settings;
    private string $token;
    private string $lastError = '';

    public function __construct(string $pluginFile, string $pluginDir)
    {
        $this->pluginFile = $pluginFile;
        $file = rtrim($pluginDir, '/\\').'/config/github-updater.php';
        $loaded = is_readable($file) ? require $file : [];
        $this->settings = is_array($loaded) ? $loaded : [];
        foreach (['owner' => 'RISE_LP_GITHUB_OWNER', 'repo' => 'RISE_LP_GITHUB_REPO'] as $key => $constant) {
            if (defined($constant)) {
                $this->settings[$key] = constant($constant);
            }
        }
        $this->token = defined('RISE_LP_GITHUB_TOKEN') ? trim((string) constant('RISE_LP_GITHUB_TOKEN')) : '';
    }

    public function register(): void
    {
        add_filter('pre_set_site_transient_update_plugins', [$this, 'injectUpdate']);
        add_filter('plugins_api', [$this, 'pluginInfo'], 20, 3);
        add_filter('plugin_action_links_'.$this->pluginBasename(), [$this, 'pluginActionLinks']);
        add_filter('network_admin_plugin_action_links_'.$this->pluginBasename(), [$this, 'pluginActionLinks']);
        add_action('admin_post_'.$this->manualCheckAction(), [$this, 'handleManualUpdateCheck']);
        add_action('admin_notices', [$this, 'manualUpdateNotice']);
        add_action('network_admin_notices', [$this, 'manualUpdateNotice']);
        add_filter('upgrader_pre_download', [$this, 'downloadPackage'], 10, 4);
        add_filter('upgrader_source_selection', [$this, 'preserveInstallDirectory'], 10, 4);
    }

    public function injectUpdate($transient)
    {
        $basename = $this->pluginBasename();
        if (! is_object($transient) || ! isset($transient->checked) || ! is_array($transient->checked) || ! isset($transient->checked[$basename])) {
            return $transient;
        }
        $release = $this->latestRelease();
        // Only touch our entry, including removal of packages from an old configuration.
        foreach (['response', 'no_update'] as $bucket) {
            if (isset($transient->{$bucket}) && is_array($transient->{$bucket})) {
                unset($transient->{$bucket}[$basename]);
            }
        }
        if ($release) {
            $bucket = version_compare($release['version'], $this->installedVersion(), '>') ? 'response' : 'no_update';
            if (! isset($transient->{$bucket}) || ! is_array($transient->{$bucket})) {
                $transient->{$bucket} = [];
            }
            $transient->{$bucket}[$basename] = $this->updatePayload($release);
        }
        return $transient;
    }

    /** @return array{ok:bool,installed_version:string,latest_version?:string,update_available?:bool,error?:string} */
    public function checkForUpdate(): array
    {
        $installed = $this->installedVersion();
        $release = $this->latestRelease(true);
        $transient = get_site_transient('update_plugins');
        if (! is_object($transient)) {
            $transient = (object) ['checked' => [], 'response' => [], 'no_update' => []];
        }
        $transient->checked = isset($transient->checked) && is_array($transient->checked) ? $transient->checked : [];
        $transient->checked[$this->pluginBasename()] = $installed;
        // Do not advance WordPress's global check timestamp for a single-plugin check.
        set_site_transient('update_plugins', $this->injectUpdate($transient));
        if (! $release) {
            return ['ok' => false, 'installed_version' => $installed, 'error' => $this->lastError];
        }
        return [
            'ok' => true,
            'installed_version' => $installed,
            'latest_version' => $release['version'],
            'update_available' => version_compare($release['version'], $installed, '>'),
        ];
    }

    public function pluginInfo($result, $action, $args)
    {
        if ($action !== 'plugin_information' || ! is_object($args) || ($args->slug ?? '') !== $this->config('slug')) {
            return $result;
        }
        $release = $this->latestRelease();
        if (! $release) {
            return $result;
        }
        return (object) [
            'name' => $this->config('name'),
            'slug' => $this->config('slug'),
            'version' => $release['version'],
            'author' => esc_html($this->config('author')),
            'homepage' => $this->repoUrl(),
            'requires' => $this->config('requires'),
            'requires_php' => $this->config('requires_php'),
            'download_link' => $release['package'],
            'sections' => [
                'description' => esc_html($this->config('description')),
                'changelog' => nl2br(esc_html($release['notes'] ?: 'See the GitHub release notes.')),
            ],
        ];
    }

    public function pluginActionLinks(array $links): array
    {
        if (current_user_can('update_plugins')) {
            $url = wp_nonce_url(admin_url('admin-post.php?action='.$this->manualCheckAction()), $this->manualCheckNonceAction());
            $links['rise_lp_check_updates'] = '<a href="'.esc_url($url).'">'.esc_html__('Check for updates', 'rise-landing-pages').'</a>';
        }
        return $links;
    }

    public function handleManualUpdateCheck(): void
    {
        if (! current_user_can('update_plugins')) {
            wp_die(esc_html__('Sorry, you are not allowed to update plugins.', 'rise-landing-pages'));
        }
        check_admin_referer($this->manualCheckNonceAction());
        $result = $this->checkForUpdate();
        $args = [
            'rise_lp_update_status' => $result['ok'] ? ($result['update_available'] ? 'available' : 'current') : 'error',
        ];
        if (! $result['ok']) {
            $args['rise_lp_update_error'] = $result['error'];
        }
        $url = is_multisite() ? network_admin_url('plugins.php') : admin_url('plugins.php');
        wp_safe_redirect(add_query_arg($args, $url));
        exit;
    }

    public function manualUpdateNotice(): void
    {
        if (! current_user_can('update_plugins') || ! isset($_GET['rise_lp_update_status']) || ! is_string($_GET['rise_lp_update_status'])) {
            return;
        }
        $status = sanitize_key(wp_unslash($_GET['rise_lp_update_status']));
        if (! in_array($status, ['available', 'current', 'error'], true)) {
            return;
        }
        $message = $status === 'available' ? 'An update is available for Rise Landing Pages.' : 'Rise Landing Pages is up to date.';
        if ($status === 'error') {
            $message = isset($_GET['rise_lp_update_error']) && is_string($_GET['rise_lp_update_error'])
                ? sanitize_text_field(wp_unslash($_GET['rise_lp_update_error']))
                : 'Could not check updates for Rise Landing Pages.';
        }
        printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', $status === 'error' ? 'error' : 'success', esc_html($message));
    }

    /** Authenticate only our asset API request; never forward credentials to a redirect. */
    public function downloadPackage($reply, $package, $upgrader, $hookExtra = [])
    {
        if ($reply !== false || ! is_string($package)) {
            return $reply;
        }
        if (isset($hookExtra['plugin']) && $hookExtra['plugin'] !== $this->pluginBasename()) {
            return $reply;
        }
        if (! $this->isOwnPackage($package)) {
            return ($hookExtra['plugin'] ?? '') === $this->pluginBasename()
                ? new \WP_Error('rise_lp_invalid_package', 'Rise Landing Pages requires its packaged GitHub release ZIP.') : $reply;
        }
        $private = strpos($package, 'https://api.github.com/') === 0;
        if ($private && $this->token === '') {
            return new \WP_Error('rise_lp_missing_token', 'Set RISE_LP_GITHUB_TOKEN in wp-config.php to download this private release.');
        }
        $filename = wp_tempnam($this->config('zip_asset'));
        if (! $filename) {
            return new \WP_Error('rise_lp_temp_file', 'Could not create a temporary file for the plugin update.');
        }
        $url = $package;
        for ($attempt = 0; $attempt < 6; $attempt++) {
            $headers = ['Accept' => 'application/octet-stream', 'User-Agent' => $this->config('user_agent')];
            if ($attempt === 0 && $private) {
                $headers['Authorization'] = 'Bearer '.$this->token;
            }
            $response = wp_safe_remote_get($url, [
                'timeout' => 300, 'redirection' => 0, 'stream' => true, 'filename' => $filename, 'headers' => $headers,
            ]);
            if (is_wp_error($response)) {
                break;
            }
            $code = (int) wp_remote_retrieve_response_code($response);
            if ($code === 200 && is_file($filename) && filesize($filename) > 0) {
                return $filename;
            }
            if (! in_array($code, [301, 302, 303, 307, 308], true)) {
                break;
            }
            $location = wp_remote_retrieve_header($response, 'location');
            if (! is_string($location) || ! $this->isTrustedDownloadUrl($location)) {
                break;
            }
            $url = $location;
        }
        wp_delete_file($filename);
        return new \WP_Error('rise_lp_download_failed', 'Could not download the packaged Rise Landing Pages release from GitHub.');
    }

    /** Preserve the existing folder when an older installation uses a different directory. */
    public function preserveInstallDirectory($source, $remoteSource, $upgrader, $hookExtra = [])
    {
        if (is_wp_error($source) || ($hookExtra['plugin'] ?? '') !== $this->pluginBasename()) {
            return $source;
        }
        global $wp_filesystem;
        $remote = trailingslashit($remoteSource);
        if (! is_string($source) || strpos($source, $remote) !== 0 || $source === $remote || ! $wp_filesystem->exists(trailingslashit($source).basename($this->pluginFile))) {
            return new \WP_Error('rise_lp_invalid_archive', 'The release ZIP must contain the complete Rise Landing Pages plugin in a single folder.');
        }
        $directory = basename(dirname($this->pluginBasename()));
        if (basename(rtrim($source, '/\\')) === $directory) {
            return $source;
        }
        $destination = $remote.$directory.'/';
        if ($wp_filesystem->exists($destination) || ! $wp_filesystem->move($source, $destination, false)) {
            return new \WP_Error('rise_lp_rename_failed', 'Could not preserve the existing Rise Landing Pages installation folder.');
        }
        return $destination;
    }

    private function latestRelease(bool $forceRefresh = false): ?array
    {
        $this->lastError = '';
        $cached = get_site_transient($this->cacheKey());
        if (! $forceRefresh && is_array($cached) && array_key_exists('release', $cached)) {
            $this->lastError = is_string($cached['error'] ?? null) ? $cached['error'] : '';
            return is_array($cached['release']) ? $cached['release'] : null;
        }
        if (! $this->isConfigured()) {
            return $this->releaseError('GitHub updates are not configured. Set the repository owner and name in config/github-updater.php or wp-config.php.');
        }
        $headers = ['Accept' => 'application/vnd.github+json', 'User-Agent' => $this->config('user_agent'), 'X-GitHub-Api-Version' => '2022-11-28'];
        if ($this->token !== '') {
            $headers['Authorization'] = 'Bearer '.$this->token;
        }
        $response = wp_remote_get($this->apiBase().'/releases?per_page=100', ['timeout' => 15, 'redirection' => 0, 'headers' => $headers]);
        if (is_wp_error($response)) {
            return $this->releaseError('Could not contact GitHub to check Rise Landing Pages updates. Try again later.');
        }
        $code = (int) wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return $this->releaseError(sprintf('GitHub update check returned HTTP %d. Check the repository, published releases, and access token if private.', $code));
        }
        $selected = $this->selectReleasePayload(json_decode((string) wp_remote_retrieve_body($response), true));
        if (! $selected) {
            return $this->releaseError('GitHub did not return a published stable release with a numeric version such as v1.2.3.');
        }
        $package = $this->assetDownloadUrl($selected);
        if ($package === '') {
            return $this->releaseError(sprintf('GitHub release %s is missing a valid %s asset. Upload the packaged plugin ZIP to the release.', $selected['tag_name'], $this->config('zip_asset')));
        }
        $release = [
            'version' => ltrim($selected['tag_name'], 'vV'),
            'package' => $package,
            'notes' => is_string($selected['body'] ?? null) ? $selected['body'] : '',
        ];
        set_site_transient($this->cacheKey(), ['release' => $release, 'error' => ''], 6 * HOUR_IN_SECONDS);
        return $release;
    }

    private function releaseError(string $message): ?array
    {
        $this->lastError = $message;
        set_site_transient($this->cacheKey(), ['release' => null, 'error' => $message], 5 * MINUTE_IN_SECONDS);
        return null;
    }

    private function selectReleasePayload($payload): ?array
    {
        if (! is_array($payload)) {
            return null;
        }
        if (isset($payload['tag_name'])) {
            $payload = [$payload];
        } elseif ($payload !== [] && array_keys($payload) !== range(0, count($payload) - 1)) {
            return null;
        }
        $best = null;
        foreach ($payload as $release) {
            if (! is_array($release) || ($release['draft'] ?? null) !== false || ($release['prerelease'] ?? null) !== false) {
                continue;
            }
            $tag = $release['tag_name'] ?? null;
            if (! is_string($tag) || ! preg_match('/\A[vV]?(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\z/', $tag)) {
                continue;
            }
            if ($best === null || version_compare(ltrim($tag, 'vV'), ltrim($best['tag_name'], 'vV'), '>')) {
                $best = $release;
            }
        }
        return $best;
    }

    private function assetDownloadUrl(array $release): string
    {
        foreach (is_array($release['assets'] ?? null) ? $release['assets'] : [] as $asset) {
            if (! is_array($asset) || ($asset['name'] ?? '') !== $this->config('zip_asset') || (isset($asset['state']) && $asset['state'] !== 'uploaded')) {
                continue;
            }
            if ($this->token !== '') {
                $id = $asset['id'] ?? null;
                if ((is_int($id) || is_string($id)) && preg_match('/\A[1-9][0-9]*\z/', (string) $id)) {
                    return $this->apiBase().'/releases/assets/'.$id;
                }
            } else {
                $url = $asset['browser_download_url'] ?? null;
                $expected = $this->repoUrl().'/releases/download/'.$release['tag_name'].'/'.$this->config('zip_asset');
                if (is_string($url) && $url === $expected) {
                    return $url;
                }
            }
        }
        return '';
    }

    private function isOwnPackage(string $url): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }
        return (bool) preg_match('~\A'.preg_quote($this->apiBase(), '~').'/releases/assets/[1-9][0-9]*\z~', $url)
            || (bool) preg_match('~\A'.preg_quote($this->repoUrl(), '~').'/releases/download/[vV]?(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)\.(0|[1-9][0-9]*)/'.preg_quote($this->config('zip_asset'), '~').'\z~', $url);
    }

    private function isTrustedDownloadUrl(string $url): bool
    {
        $parts = wp_parse_url($url);
        return is_array($parts) && ($parts['scheme'] ?? '') === 'https'
            && ! isset($parts['user']) && ! isset($parts['pass'])
            && (! isset($parts['port']) || $parts['port'] === 443)
            && in_array($parts['host'] ?? '', ['github.com', 'api.github.com', 'objects.githubusercontent.com', 'release-assets.githubusercontent.com', 'github-releases.githubusercontent.com'], true);
    }

    private function updatePayload(array $release): object
    {
        return (object) [
            'id' => $this->repoUrl(), 'slug' => $this->config('slug'), 'plugin' => $this->pluginBasename(),
            'new_version' => $release['version'], 'url' => $this->repoUrl(), 'package' => $release['package'],
            'requires' => $this->config('requires'), 'requires_php' => $this->config('requires_php'),
        ];
    }

    private function isConfigured(): bool
    {
        return (bool) preg_match('/\A[A-Za-z0-9][A-Za-z0-9-]*\z/', $this->config('owner'))
            && (bool) preg_match('/\A[A-Za-z0-9][A-Za-z0-9_.-]*\z/', $this->config('repo'))
            && $this->config('zip_asset') === 'rise-landing-pages.zip';
    }

    private function cacheKey(): string
    {
        return 'rise_lp_github_release_'.hash('sha256', $this->config('owner').'/'.$this->config('repo').'|'.hash('sha256', $this->token));
    }

    private function installedVersion(): string
    {
        if (! function_exists('get_plugin_data')) {
            require_once ABSPATH.'wp-admin/includes/plugin.php';
        }
        $data = get_plugin_data($this->pluginFile, false, false);
        return (string) ($data['Version'] ?? '0.0.0');
    }

    private function pluginBasename(): string
    {
        return plugin_basename($this->pluginFile);
    }

    private function repoUrl(): string
    {
        return 'https://github.com/'.$this->config('owner').'/'.$this->config('repo');
    }

    private function apiBase(): string
    {
        return 'https://api.github.com/repos/'.$this->config('owner').'/'.$this->config('repo');
    }

    private function manualCheckAction(): string
    {
        return 'rise_lp_check_github_update';
    }

    private function manualCheckNonceAction(): string
    {
        return $this->manualCheckAction().'_'.$this->pluginBasename();
    }

    private function config(string $key): string
    {
        return is_string($this->settings[$key] ?? null) ? trim($this->settings[$key]) : '';
    }
}
