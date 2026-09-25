# Deploying Rise Landing Pages to all three sites

Use the same `rise-landing-pages.zip` release on Rise Physio, Rise Fitness, and Rise Medical. Each WordPress installation keeps its own brand settings, pages, media, booking links, and updater credentials. Installing a plugin update does not apply a brand preset or replace that site's saved content.

The updater is included from **1.1.80** and is configured for [siko001/rise-landing-pages](https://github.com/siko001/rise-landing-pages). Its source remote is `git@github.com:siko001/rise-landing-pages.git`. Regular commits to `main` can continue while development is in progress; sites receive updates only after a stable release with the packaged ZIP is published.

## Repository configuration

1. Keep this plugin's source in the repository, including `package-lock.json`, `config/github-updater.php`, and `.github/workflows/release.yml`. Keep the source files needed by `npm run build`. The `.gitignore` excludes dependency installs, ZIP output, local WordPress fixtures, and test results.
2. The bundled configuration sets `owner` to `siko001` and `repo` to `rise-landing-pages`, so each site's plugin uses the same update source. Keep `slug` as `rise-landing-pages` and `zip_asset` as `rise-landing-pages.zip`.
3. Optional: add these constants to each site's `wp-config.php`, before the “stop editing” line, to pin or override its repository independently. They override the bundled configuration and survive plugin updates:

   ```php
   define( 'RISE_LP_GITHUB_OWNER', 'siko001' );
   define( 'RISE_LP_GITHUB_REPO', 'rise-landing-pages' );
   ```

Public repositories do not need a token. For a private repository, create a fine-grained GitHub personal access token limited to this repository with **Contents: Read-only** access, then add it only to each site's `wp-config.php`:

```php
define( 'RISE_LP_GITHUB_TOKEN', 'YOUR_READ_ONLY_TOKEN' );
```

Never put a token in the plugin, release ZIP, committed configuration, or release notes. The GitHub Actions workflow uses GitHub's built-in token; it does not need any site's personal access token. Follow GitHub's [fine-grained token instructions](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens) if repository or organization approval is required.

## Build and publish a release

For every release, choose a new stable version such as `1.1.80` and keep these values aligned:

- The `Version` plugin header and `RISE_LP_VERSION` constant in `rise-landing-pages.php`.
- The `Stable tag` and changelog entry in `readme.txt`.
- The top-level `version` in `package.json` and `package-lock.json`, plus `packages[""].version` in the lockfile.

Run the checks in `TESTING.md`, then build an installable ZIP locally:

```sh
npm ci
npm run build
python3 scripts/package.py --version v1.1.80
```

The packager rejects mismatched versions, prerelease versions, missing compiled assets, and symlinks. It writes `dist/rise-landing-pages.zip` with one top-level `rise-landing-pages/` folder. PHP runtime, block definitions, compiled editor and smooth-scroll assets, frontend/admin assets, fonts, logos, licenses, and deployment documentation are included. Development editor source, source maps, dependencies, fixtures, and tests are excluded. Identical inputs produce an identical ZIP.

Commit the prepared version and push an annotated tag:

```sh
git tag -a v1.1.80 -m "Rise Landing Pages 1.1.80"
git push origin v1.1.80
```

The **Package plugin release** GitHub Actions workflow checks out that exact tag, installs the locked dependencies with Node 22, builds the assets, checks PHP syntax, updater regressions, and version alignment, then attaches `rise-landing-pages.zip` to a **draft release**. No separate Actions secret is needed. The repository must allow GitHub Actions and its workflow token to write repository contents/releases.

Open the draft under GitHub **Releases**, review the release notes and ZIP, then publish it as a normal release. Marking it **Latest** makes the intended release clear on GitHub; the updater itself selects the highest numeric stable version. Publishing is the point at which configured sites can discover the update. Do not mark production releases as prereleases. The workflow can also be run from **Actions → Package plugin release → Run workflow** for an existing `vX.Y.Z` tag; reruns replace the ZIP only while that release remains a draft. Published releases require a new version and tag.

If Actions is unavailable, upload the locally built `dist/rise-landing-pages.zip` to a draft GitHub release for the matching tag, then publish it as a normal stable release. GitHub's automatic **Source code (zip)** download is not the installable package. The updater requires the attached asset named exactly `rise-landing-pages.zip`; tags alone do not publish an update. GitHub documents the [release creation flow](https://docs.github.com/en/repositories/releasing-projects-on-github/managing-releases-in-a-repository).

## First installation on Physio, Fitness, and Medical

1. Back up each site's files and database, and check the release on a staging copy first.
2. In each site's WordPress admin, use **Plugins → Add New → Upload Plugin** to upload `rise-landing-pages.zip`. Activate it for a new installation, or replace the installed copy when WordPress offers that option.
3. If an older development copy is installed under `rise-landing-page-builder/` or another folder, deactivate it before activating the canonical `rise-landing-pages/` installation. Do not activate both copies. Saved pages and settings remain in the database when the old plugin is deactivated.
4. Configure the updater using the shared repository settings above. For a new installation, configure the site's own branding under **Rise Landing Pages → Settings**. Existing installations keep their saved brand profile; do not reapply presets just to update the plugin.
5. On **Plugins**, use **Check for updates** beneath Rise Landing Pages. It should report the installed version as current when the same release is installed. Use an account with permission to update plugins; on multisite use Network Admin.
6. Open an existing landing page on desktop and mobile, check its header/footer and booking links, and open the editor to confirm block controls load. Repeat on all three sites because their themes and brand settings differ.

## Subsequent updates

Publish one newer release using the steps above. On each of the three sites, open **Plugins → Rise Landing Pages → Check for updates**, then use WordPress's normal **Update now** action. Update one site first, verify its landing pages, then repeat on the other two. Each site updates independently; publishing a release does not push files or settings directly to all three sites.

Normal WordPress update checks reuse GitHub release metadata for up to six hours. **Check for updates** bypasses that cache. Failed GitHub checks are cached briefly to avoid repeated requests. Only newer numeric stable versions (`vX.Y.Z` or `X.Y.Z`) from a published, non-prerelease release with the correctly named ZIP are offered. Standard WordPress plugin auto-updates can be enabled per site if desired; the plugin does not enable them automatically.

After an update, clear page/CDN caches if the site uses them. Keep a copy of the previous release ZIP and the site backup for rollback; WordPress's update notification only offers newer versions. Changing GitHub's **Latest** label to an older release does not roll sites back or prevent the highest published stable version from being offered. Prefer publishing a new higher version with the fix.

## If an update is missing

- Confirm `owner` and `repo` are configured, and any `wp-config.php` overrides match the intended repository.
- Confirm GitHub has a published stable release with a higher version and an attached `rise-landing-pages.zip` asset. A draft, prerelease, source archive, or tag by itself is insufficient.
- For private repositories, check that the token is unexpired, approved where required, and has access to this repository with Contents read permission.
- Use **Check for updates** to refresh immediately. Review its notice for an API, rate limit, authorization, or missing-package error.
- Confirm the host can reach GitHub over HTTPS and WordPress permits plugin updates. An editor may manage landing pages without having permission to update plugins.

The first live check/download still needs a published release. Local packaging alone cannot verify GitHub credentials or a production host's connectivity.
