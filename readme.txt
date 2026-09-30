=== Rise Landing Pages ===
Contributors: rise
Tags: landing-pages, block-editor, campaign, pages
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.81
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Branded landing pages with eight structured native Gutenberg blocks.

== Description ==
Create normal WordPress pages with a guided block editor, brand presets, optional website header/footer, image and video heroes, swipeable services, animated FAQs, spacers, and draft duplication. No ACF or theme framework dependency.

== Installation ==
1. Upload and activate Rise Landing Pages.
2. Configure Rise Landing Pages > Settings for this site.
3. Choose Add Landing Page, edit the default sections, and publish.

== Changelog ==
= 1.1.81 =
* Fix the Medical separator icon background during ticker animation.

= 1.1.80 =
* Add GitHub release updates and a Check for updates link on the Plugins screen.
* Support public repositories and optional private repository credentials in wp-config.php.
* Package a shared release ZIP for all three Rise sites, preserving site settings and content.

= 1.1.79 =
* Supply default header and footer logos with the Rise brand presets, while allowing Media Library overrides.
* Bundle the Physio and Medical logos and use the supplied Rise Fitness SVG URL.

= 1.1.78 =
* Fill Physio and Fitness policy, terms, About and social links from their presets, preferring existing published pages on the matching site; show About in the landing footer.
* Set the requested booking button text per Rise preset, Medical's Cliniko booking URL, and an 1860px maximum content width for every preset.

= 1.1.77 =
* Fill the supplied Physio and Fitness locations when applying those presets; keep Medical contact details untouched.
* Show location cards as collapsible panels and let editors reorder them by dragging or with the arrow keys.

= 1.1.76 =
* Show location addresses, Google Maps links, multiple phone numbers, and optional details in single or multiple landing footer layouts.

= 1.1.75 =
* Show the background selector in an expanded panel at the top of each section's full Block settings sidebar.

= 1.1.74 =
* Open the full Block settings sidebar from the gear on every Rise block, with background and Process controls available in Section settings.
* Choose clockwise or anticlockwise step order in the circular Process layout, with matching editor and frontend positions.

= 1.1.73 =
* Choose single or multiple company contacts in settings and show each saved company in the landing footer.

= 1.1.64 =
* Use the brand heading font for image service card titles in the editor and on published pages.

= 1.1.63 =
* Remove the block-wide text colour setting; selected text can still be coloured with the inline toolbar.

= 1.1.62 =
* Use one block text colour setting for supporting text, headings, and introductions while keeping individual colour choices in the text toolbar.

= 1.1.61 =
* Add independent default, brand, white, and black colour choices for supporting text, headings, and intro text in each content block.

= 1.1.60 =
* Use the existing text colour toolbar and swatches for selected supporting text; keep the section setting for the whole label.

= 1.1.59 =
* Anchor the supporting text colour choices beside the selected text.

= 1.1.58 =
* Open supporting text colour choices when the text itself is focused in the editor.

= 1.1.57 =
* Change supporting text colour from the block toolbar after clicking the text.

= 1.1.56 =
* Add an adjustable Rise Spacer inside Process sections between the intro and steps.

= 1.1.55 =
* Choose the supporting text colour in every text section while keeping each block’s current default.

= 1.1.54 =
* Choose the default header and footer for newly created landing pages in site settings.

= 1.1.53 =
* Organize the settings page into initially closed, single-open accordions with a quick section menu.

= 1.1.52 =
* Load F37 Judge Bold in the Rise Fitness Gutenberg separator, allow outline formatting on phrases, and offer automatic, scroll-linked, or still movement.

= 1.1.51 =
* Match block inserter previews to the site's saved brand colours.

= 1.1.50 =
* Match the separator icon to the active brand, offer a simple RISE wordmark toggle, keep editor phrases on one line, and fill the full editor width.

= 1.1.49 =
* Size circular Process connector lines to stop close to step text without crossing it.

= 1.1.48 =
* Keep Process connectors clear of step titles and use a vertical timeline for exceptionally long step text.

= 1.1.47 =
* Add the Rise Separator with brand colour backgrounds, selectable logos, editable phrases, and a seamless marquee.

= 1.1.46 =
* Add a clockwise circular Process layout and keep its step labels visible on the frontend.

= 1.1.44 =
* Use the site's header and footer by default when creating a landing page.

= 1.1.43 =
* Offer site and Rise brand colours in the inline colour pickers, and let selected text in all block headings use a custom colour.

= 1.1.42 =
* Keep Hero media within its frame and use the selected image URL so published focal positions match the editor preview.

= 1.1.41 =
* Keep the Fitness site footer visible when the site header is hidden, and preserve Divi's footer styles in Gutenberg previews.

= 1.1.40 =
* Isolate the selected header or footer inside the preview page, hide the toolbar and other page content, and report its height to Gutenberg.

= 1.1.39 =
* Keep editor chrome previews on the editor's origin and wait for the requested iframe document before reporting an error.

= 1.1.38 =
* Preview the selected landing or site header and footer around the Gutenberg blocks without adding saved blocks.
* Use the Fitness theme's smooth scrolling alone when site chrome is selected, and fix a frontend settings namespace error.

= 1.1.37 =
* Let Physio image card rows start at the left or center beneath the Services heading.
* Keep cards close to square and slightly larger at three columns while preserving responsive wrapping.

= 1.1.36 =
* Choose landing, site, or hidden headers and footers independently while preserving existing website-layout pages.
* Hide featured image and excerpt controls when editing Rise landing pages.
* Give the page settings controls and help text more breathing room.

= 1.1.35 =
* Restore the Physio desktop choice of three or four compact cards per row, wrapping to two and one as the canvas narrows.
* Fix square image cards overflowing their grid tracks when their minimum height exceeds a narrow column.

= 1.1.34 =
* Keep Physio image cards in a spacious two-column layout, align descriptions when a card has no button, and use the accent border only on hover.
* Make Fitness pricing card borders primary only on hover.

= 1.1.33 =
* Let Physio image cards show an optional description beneath the title, editable directly in Gutenberg.

= 1.1.32 =
* Keep Fitness pricing card buttons at the bottom, with a primary-to-transparent hover state.
* Add editable feature rows and an optional badge directly in the Gutenberg pricing card preview.
* Match Services slider navigation to its section background, including mint hover and black text on the default Physio background.

= 1.1.31 =
* Restore service titles in the Fitness and Physio editor previews.
* Open a service's settings panel when any part of its preview card is clicked.

= 1.1.30 =
* Add three Services card layouts: classic, Fitness pricing, and Physio image cards.
* Preview swipeable Services in Gutenberg, with optional counter, looping, and autoplay on published pages.
* Put move, delete, and Add service actions beside and below the service item panels.

= 1.1.29 =
* Open the corresponding sidebar item panel when clicking or focusing a title or description in Benefits, FAQ, Services, or How it works.
* Show service titles in their sidebar panel names.

= 1.1.28 =
* Make filled primary buttons transparent with mint text on hover or keyboard focus in the Rise Physio preset, including the sliding button label.

= 1.1.27 =
* Fill transparent outline buttons with the primary mint colour and show black text on hover or keyboard focus in the Rise Physio preset.

= 1.1.26 =
* Use black text on the mint FAQ labels in the Rise Physio preset, in both the editor and published pages.

= 1.1.25 =
* Keep the sliding hover text in service and call-to-action buttons at the same size as the visible label, preventing short labels from wrapping during the animation.

= 1.1.24 =
* Smooth wheel, trackpad, and section-link scrolling on Rise pages with either landing or website header and footer; respect reduced-motion preferences and an existing theme Lenis instance.
* Let Rise Gutenberg blocks and the page title use the full editor canvas under Divi, and show a visual Page Name label without changing the saved title.

= 1.1.23 =
* Keep each centered benefit icon and title together with its description directly below, including editable empty benefits.
* Remove the FAQ Items panel and place Add question below the question tabs in the sidebar.

= 1.1.22 =
* Centered benefit items keep the marker directly beside each benefit title without an extra setting.

= 1.1.21 =
* Benefit markers can be chosen from the Media Library or supplied by URL, with a different image for each benefit and an optional section default.
* Centered Benefits can also center their items. Benefit sidebar tabs show their titles and open when their text is selected; Benefit and FAQ tabs expose move and remove shortcuts.
* The Benefits editor places Add benefit below the list and removes the redundant Items and card title line spacing controls.

= 1.1.20 =
* Benefits can hide individual entries, switch between checkmarks, no marker, numbers, and a custom SVG icon, place the title on the right or centered, and use a white background.
* Benefit add controls follow the list in the editor and the benefit panels in the sidebar. FAQ add controls follow the questions, and selecting a question title opens its sidebar panel.

= 1.1.19 =
* Renaming a landing page updates its slug. Duplicate page names receive a numbered suffix, with a warning when the page is saved.
* Administrators and Editors can access and save Rise Landing Pages settings.

= 1.1.18 =
* Add an optional content-aware maximum height for overlay Heroes so they can become shorter banners. Gutenberg now responds to the minimum and maximum height controls.

= 1.1.17 =
* Wait for a newly selected FAQ image to load before revealing it, including when the browser rejects its first decode request.

= 1.1.16 =
* Add a sitewide FAQ image animation setting with Fade and Rise Fitness Tiles choices. FAQ blocks inherit it by default and can choose their own preset; applying the Fitness brand preset selects Tiles.

= 1.1.15 =
* FAQ images offer Fade and Rise Fitness Tiles presets. Both work with one section image or images that change by question; the tile reveal follows the Classes block's nine clipping paths and diagonal timing.

= 1.1.14 =
* Make published FAQ label text easier to read while keeping the editor labels compact.

= 1.1.13 =
* Compact editable FAQ labels to the intended mini-pill size and remove the unused card title line-spacing control from FAQ settings.

= 1.1.12 =
* FAQ background defaults to the Rise page colour with an optional white choice; removed the section top border and made accordion dividers match dark and light backgrounds.
* FAQ supports a section image or images that switch by question, either image side, SVG slashes that fill when open, and optional small labels below answers.

= 1.1.11 =
* Scroll-triggered, staggered text and card reveals without an initial flash; larger default headings, square corners, compact mobile buttons, and smoother FAQ colour and icon motion.

= 1.1.10 =
* Services grid columns, spacing, image overlays and slider navigation motion; selected text sizing and line spacing across headings; split Hero media side and mobile media-first order; optional second Hero button.

= 1.1.9 =
* Outline text, outline colour, and text size now appear directly in the rich-text toolbar.

= 1.1.8 =
* Independent Hero text-spacing controls and a quieter, transparent Spacer preview in Gutenberg.

= 1.1.7 =
* Rise landing page editor settings sidebar is wider and resizable, with saved width and keyboard support.

= 1.1.6 =
* Fitness buttons use larger type and a flatter shape; outgoing hover labels now fully leave the button.

= 1.1.5 =
* Fitness hover colours now swap red buttons to white and transparent or dark buttons to red; Hero text sizes can be as small as 1px.

= 1.1.4 =
* Free numeric Hero title sizes, adjustable title line spacing, and sliding-label button hover motion.

= 1.1.3 =
* Seven native blocks, brand presets, responsive hero media, optional site chrome, slider services, and independent hero button visibility.
