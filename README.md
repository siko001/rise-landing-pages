# Rise Landing Pages

A standalone WordPress plugin for branded campaign pages on Rise Medical, Rise Fitness, and Rise Physio. Landing pages are ordinary WordPress **Pages**, rendered with a plugin-owned document and edited using eight native Gutenberg blocks. No ACF, Divi, Sage, CSS framework, or frontend JavaScript framework is required.

## Requirements and installation

- WordPress 6.5 or newer; PHP 7.4 or newer (use a currently supported PHP release in production).
- Upload `dist/rise-landing-pages.zip` through **Plugins → Add New → Upload Plugin**, then activate.
- Open **Rise Landing Pages → Settings** and configure this site's brand, contact details, booking URL, and the header and footer defaults for new landing pages.
- Under **Footer locations and contact details**, choose **Single location** or **Multiple locations**. Each location can have an address, Google Maps link, several phone numbers, an optional email, and other details. Up to ten locations can be saved; switching modes keeps both sets of details. These appear when a page uses the **Landing footer**. The **Site footer** belongs to the active theme.
- Applying the Rise Physio preset fills Sliema, Qormi and Balzan; Rise Fitness fills its single location. Rise Medical and Generic leave contact details as entered. Location cards start collapsed, show their names in the headings, and can be reordered by dragging the handle or pressing its Up/Down arrow keys. Save the settings to publish the selected preset and order.
- All presets set the maximum content width to 1860px. Physio and Fitness presets fill About, privacy, terms, Facebook, and Instagram links, preferring published pages on the matching WordPress site. Presets set booking button text to **Book an appointment** (Physio), **Book Now** (Fitness), or **BOOK MEDICAL IMAGING** (Medical). Medical also fills its Cliniko booking URL. Other booking URLs are retained. About appears in the landing footer as well as the minimal landing header.
- Rise brand presets select a default logo for the minimal landing header and landing footer. Physio and Medical artwork is bundled with the plugin; Fitness uses the supplied Rise Fitness SVG URL. Applying a preset clears previously chosen Media Library logos so its default is visible. A logo selected afterward in **Brand identity** overrides the preset, and an optional alternate logo overrides the footer only.
- Select **Rise Landing Pages → Add Landing Page**. A draft opens in Gutenberg with the site's header and footer selected and Hero, Services, Process, Benefits, FAQ, and CTA sections already inserted.
- Replace all example copy with approved campaign information, select media, set a title, preview, then publish. Renaming a landing page updates its slug. If another page has the same title, the saved title gets a numbered suffix and the editor shows a warning after saving.

The source directory may have a different name during development. The distribution ZIP always has the canonical `rise-landing-pages/` directory and `rise-landing-pages.php` entry point. Do not activate a development copy and the packaged copy simultaneously.

## Client workflow

Use the **Block settings** gear on a Rise section to open its **Block** sidebar and the **Background** picker in the toolbar. Other Rise blocks use the gear to open their sidebar. The Process block's step title size and marker controls are in **Process layout**, below the layout options. The gear also appears on Spacer, Separator, and Hero content blocks.

In the Process block's **Process layout** panel, choose **Layout 1 — Columns** or **Layout 2 — Circle**. For the circle, set **Circle direction** to **Clockwise** (the default) or **Anticlockwise**. Both directions keep the first step in the same position. The circular layout uses the same 2–6 editable steps and stacks into a connected list in step order on narrow screens or when step text is exceptionally long. In the Hero, insert a Rise Spacer between content blocks to control their vertical separation.

Edit headings and copy directly in the canvas. Select a section to adjust images, links, or its item count in the block settings. Services support 1–6 cards in three layouts: classic cards, Fitness pricing cards with badges/features/prices, and Physio image cards with optional descriptions. Physio image cards can use three or four compact columns on wide screens, wrapping to two and one as the available space narrows; they keep at least 24 px between cards. The Physio grid can start at the left edge of the section or be centered, and its cards stay close to square at up to 380 px wide in a three-column row. Fitness and Physio card borders use the brand colour on hover. In the Fitness card preview, use **Add feature** to create editable feature rows or **Add badge** to create an empty badge. Pricing buttons stay at the bottom of each card. Services can use a responsive grid or a swipeable slider that previews in Gutenberg; its counter, looping, and autoplay are optional. Clicking a service card opens its settings panel. Service item panels have move and delete buttons, with Add service beneath the list. Process supports 2–6 steps. The default six sections can be moved or removed with Gutenberg's normal controls. A Spacer block is available with separate desktop and mobile heights. The optional Rise Separator repeats editable phrases with the current brand icon and an optional RISE wordmark. Its movement can be automatic, linked to page scrolling in either direction, or still; gap, speed, and pause-on-hover controls are available when relevant. Select phrase text in Gutenberg to use the Outline toolbar and colour picker. Its background can be any Rise brand primary colour, black, or white.

## Campaign patterns

Open the Gutenberg inserter, choose **Patterns → Rise campaigns**, and insert an editable starting point. New drafts still begin with the six standard sections; remove those first when using a complete page pattern. Smaller patterns can be added to an existing page.

| Pattern | Intended use | Blocks |
| --- | --- | --- |
| Physio · Recovery journey | Appointment or treatment campaign | Hero, image Services, Benefits, Process, FAQ, CTA |
| Fitness · Programme launch | Programme or membership promotion | Hero, Separator, pricing Services, Benefits, CTA |
| Medical · Service explainer | Consultation or service campaign | Hero, Services, Process, FAQ, CTA |
| Cross-brand · Your Rise pathway | Introduce all three Rise brands on one page | Hero, Services, Separator, Benefits |
| Cross-brand · Next-step handoff | Promote another Rise brand within an existing page | Spacer, Separator, CTA |

Replace example copy, prices, imagery, and booking details before publishing. Cross-brand service cards and the handoff CTA wait for their **own destination URL**; they never borrow the current site's booking URL. A site's colour and typography preset still styles its whole landing page. The Separator can use another Rise brand colour and mark within that page.

The **Rise Landing Page** document panel lets you choose the header and footer independently: landing, site, or hidden. Gutenberg shows the selected header above the blocks and footer below them as non-interactive previews; unsaved choices update those previews without adding page blocks. It also controls the campaign booking URL and CTA label. The featured image and excerpt controls are hidden for Rise pages because sections manage their own content and media. Blank campaign CTA fields inherit the site settings; blank block CTA fields inherit the campaign/site defaults. Explicit block links take precedence. The Hero button has its own **Show button** switch, even if a page-level booking URL exists. No CTA link is rendered without a usable destination.

Hero offers text beside media (media left or right on desktop, media first on mobile) or image/video with a text overlay. The overlay fills the viewport by default; set a maximum height in the Hero Layout panel to make it a shorter banner. Its minimum height and content spacing still take priority, so text and buttons are never clipped. You can set focal position, crop fit, mobile image, video autoplay, mute, loop, native controls, and captions. Background and overlay tint are in the **Background** picker opened by the Block settings gear. Supporting text, title, description, reassurance, and buttons are edited directly in the Hero. Use the in-canvas plus controls to add one optional Rise Spacer after the supporting text, after the title, after the description, or before the buttons. Each control appears only when the preceding field has text. The Hero can have an optional second button with its own link and outline, light, dark, brand, or custom colours; switch it on to reveal its settings. The reassurance line is optional and has no automatic icon. FAQ sections can open only one answer at a time or several; they can start with the first open or all closed. Opening animates when the visitor permits motion.

Process sections accept one Rise Spacer inside the block between the title/description and the steps. Set separate desktop and mobile heights in the spacer settings.

Use **Duplicate landing page** in the landing-page list to make a draft for another campaign. Only the page content, excerpt, and Rise-owned metadata are copied. SEO metadata, revisions, publication status, and Divi settings are intentionally excluded; review the new page's SEO settings before publishing.

Landing pages appear in the normal Pages screen too. They use `_rise_landing_page = 1` and the virtual `rise-landing-page.php` template. They do not create a new content type or permalink base. Pretty URLs follow the site's existing WordPress permalink configuration.

## Branding and fonts

Administrators and Editors can create and edit landing pages and save the shared **Rise Landing Pages → Settings** profile.

Brand settings are stored in the `rise_landing_settings` option independently on each installation. Fitness red is `#EC1C2B`, Physio mint is `#61FFD6`, and Medical navy is `#002E75`. The plugin owns layout, spacing, colour, buttons, and component styles beneath `.rise-lp`. Fitness uses red, dark text, square condensed Judge buttons; the Physio and Fitness presets include optional sliding-label button motion. Headings inherit the brand preset. Select text in any section heading or card title and enter any size from 1 to 200 px in the **Text size** toolbar control. Use **Heading line height** in the heading toolbar to adjust the whole title. The **Block settings** gear opens the section background picker and sidebar; Process step markers remain in its sidebar settings. Services use an adjustable Spacer between the introduction and cards, with the same Add a spacer control as Process, and support optional per-image colour overlays. The **Outline** toolbar control works on headings; `[outline]text[/outline]`, `[outline color=white]text[/outline]`, and `[size px=80]text[/size]` also work. Drag the left edge of the Rise landing editor settings sidebar to resize it; the width is saved in your browser. The plugin yields to the resizer already included in the ATX base starter. Fonts already registered by the site are reused. The Rise Fitness separator editor loads the bundled F37 Judge Bold face so its phrase typography matches the published separator.

The **Outline colour** and **Text colour** pickers include white, black, this site's brand colours, and the primary and secondary colours from every Rise preset. Select text in a hero, section, card, or FAQ heading to colour only that text; **Use heading default** removes a text colour override.

The site settings include a default image animation for FAQ media: **Fade** or the nine-tile reveal from Rise Fitness Classes. Applying the Fitness brand preset selects the tile reveal. Each FAQ can inherit the site setting or choose its own effect, whether it has one image or an image for each question. Reduced-motion visitors see the images immediately.

Section headings default to 4rem above mobile and 3rem on mobile. Card, benefit, and FAQ headings default to 2rem. Rise presets start with square card and button corners; both radius settings remain editable. Section text and cards reveal with a short stagger when they enter view, while reduced-motion visitors see the content immediately.

The standalone template retains `wp_head()`, `wp_body_open()`, and `wp_footer()` for WordPress, analytics, consent tools, and SEO plugins. Rise pages use locally bundled Lenis for smooth wheel, trackpad, and section-link scrolling, skip a second instance when the theme already runs Lenis, and respect reduced-motion preferences. Fitness pages with site chrome leave scrolling to the Fitness theme; its mobile layout uses native scrolling. Selecting either site part calls the theme's normal header/footer around full-width Rise content; the unselected theme part is hidden while the theme closes its document. Fitness's saved `rf/footer` block is rendered only when the site footer is selected. Divi includes scoped corrections for site chrome. The Rise editor removes Divi's 823px Gutenberg cap for landing content and labels the page title without changing its saved value. Other custom theme rules may need site-specific review.

The optional alternate logo is used in the plugin footer; the standard logo is used in its header. If neither Media Library logo is chosen, a Rise preset uses its default in both places. Images can have descriptive alternative text or be explicitly decorative. FAQ answers remain readable if JavaScript is unavailable and become button-controlled disclosures when JavaScript runs.

## Deployment and GitHub updates

All three Rise sites use the same installable ZIP; their brand settings and pages stay in their own WordPress databases. The updater is adapted from ATX Uptime Monitor and adds **Check for updates** to the plugin's row in **Plugins**. Standard WordPress update checks and optional WordPress auto-updates use the same GitHub release feed.

The updater is configured for [siko001/rise-landing-pages](https://github.com/siko001/rise-landing-pages) in `config/github-updater.php`. Each site's `wp-config.php` can override it with `RISE_LP_GITHUB_OWNER` and `RISE_LP_GITHUB_REPO`. For a private repository, also define `RISE_LP_GITHUB_TOKEN` in `wp-config.php` with read access to that repository. Never put a token in the plugin or commit one to GitHub. Only users with permission to update plugins can run the manual check. Commits to `main` do not publish updates; a stable GitHub release with the packaged ZIP is required.

Follow [DEPLOYMENT.md](DEPLOYMENT.md) for initial installation on all three sites, publishing releases, and rolling back. Each stable release needs the attached **rise-landing-pages.zip** produced by this project's packaging script. The automatically generated GitHub source archives are not installable release packages.

## Development

These commands require the source checkout. The installable ZIP excludes the test harness and packaging scripts.

```sh
npm ci
npm run build
npm run lint:js
npm run lint:css
find src templates -name '*.php' -exec php -l {} \;
php -l rise-landing-pages.php
```

The build uses `@wordpress/scripts` and WordPress-provided React/packages, producing `build/editor.js`, its dependency manifest, and editor CSS. Production installations need only the included compiled build, not Node or npm.

Run the integration suite against a local/disposable WordPress installation with the plugin active:

```sh
wp eval-file wp-content/plugins/rise-landing-pages/tests/integration.php
```

The suite creates temporary pages and removes them in a `finally` block. It verifies registration, creation, duplication, allow-lists, metadata access, rendering, and conditional assets. Do not run integration fixtures against production content.

Package a reproducible installable ZIP:

```sh
python3 scripts/package.py
```

No activation migration, custom database table, or rewrite flush is necessary. Deactivation keeps all pages and settings. Because sections render dynamically, keep the plugin active while publishing Rise pages. Uninstall deliberately preserves content and settings; nothing is automatically deleted.

## Extension points

Allow an additional editor block only on Rise pages:

```php
add_filter( 'rise_landing_allowed_blocks', function ( $blocks, $page ) {
    $blocks[] = 'core/image';
    $blocks[] = 'core/heading';
    return $blocks;
}, 10, 2 );
```

Extra blocks bring their own markup and styling requirements. All eight Rise blocks use normal Gutenberg movement and removal controls.

Change brand defaults at runtime (values are sanitized after filtering):

```php
add_filter( 'rise_landing_brand_settings', function ( $settings ) {
    $settings['brand_name'] = 'Rise Medical';
    return $settings;
} );
```

Change the **parsed block arrays** inserted into new drafts:

```php
add_filter( 'rise_landing_template_blocks', function ( $blocks ) {
    $blocks[0]['attrs']['eyebrow'] = 'Rise Physio';
    return $blocks;
} );
```

Add body classes on Rise pages:

```php
add_filter( 'rise_landing_body_classes', function ( $classes, $page_id ) {
    $classes[] = 'rise-campaign';
    return $classes;
}, 10, 2 );
```

Changing a Rise page back to a standard page is an intentional developer operation: remove `_rise_landing_page` and reset `_wp_page_template` to `default`. The editor does not expose this potentially destructive conversion to clients.

## Release verification

See `TESTING.md` for the checks and environmental limits recorded for this build. The supplied specification described a wireframe, but no PDF was attached; the first implementation follows its written structure and uses editable example content.
