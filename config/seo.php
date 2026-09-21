<?php

return [
    'canonical_url' => env('SEO_CANONICAL_URL', env('APP_URL', 'http://localhost')),
    'indexing_enabled' => env('SEO_INDEXING_ENABLED', true),
    'image' => '/images/seed-investment-summit/seed-investment-summit-2026.jpeg',
    'image_width' => 1254,
    'image_height' => 1254,
    'google_verification' => env('GOOGLE_SITE_VERIFICATION'),
    'bing_verification' => env('BING_SITE_VERIFICATION'),
    'social_locales' => [
        'en' => 'en_GB',
        'fr' => 'fr_FR',
        'ar' => 'ar_AR',
        'pt' => 'pt_PT',
        'es' => 'es_ES',
        'sw' => 'sw_KE',
    ],
];
