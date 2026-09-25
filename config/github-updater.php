<?php
/**
 * GitHub release updater for siko001/rise-landing-pages.
 * These may also be overridden by RISE_LP_GITHUB_OWNER / RISE_LP_GITHUB_REPO.
 * Private repositories use RISE_LP_GITHUB_TOKEN in wp-config.php, never this file.
 */
return [
    'owner' => 'siko001',
    'repo' => 'rise-landing-pages',
    'slug' => 'rise-landing-pages',
    'zip_asset' => 'rise-landing-pages.zip',
    'name' => 'Rise Landing Pages',
    'author' => 'Rise',
    'requires' => '6.5',
    'requires_php' => '7.4',
    'user_agent' => 'rise-landing-pages-updater',
    'description' => 'Independent, branded campaign pages with a guided Gutenberg editor.',
];
