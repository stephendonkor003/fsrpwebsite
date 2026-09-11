<?php

return [
    'canonical_url' => env('SEO_CANONICAL_URL', 'https://fsrp.africa'),
    'indexing_enabled' => env('SEO_INDEXING_ENABLED', true),
    'image' => '/images/fsrp/water-food-resilience-1.jpg',
    'image_width' => 1920,
    'image_height' => 1080,
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
