<?php

return [
    'node_binary' => env('FINDO_NODE_BIN', '/opt/homebrew/bin/node'),
    'name' => 'Findo Group',
    'seo_base_url' => env('SEO_BASE_URL', 'https://findogroup.com'),
    'prevent_indexing' => env('WEBSITE_PREVENT_INDEXING', true),
    'source_asset_base' => '/wp-content/themes/findo-wordpress-theme/react-build/',
];
