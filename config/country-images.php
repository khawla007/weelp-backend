<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Country Images Import Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for automatic country image import from various sources.
    |
    */

    'sources' => [
        'pexels' => [
            'url' => 'https://api.pexels.com/v1/search',
            'key' => env('PEXELS_API_KEY', ''), // Optional, free tier works without key
            'enabled' => true,
        ],
        'unsplash' => [
            'url' => 'https://api.unsplash.com/search/photos',
            'key' => env('UNSPLASH_API_KEY', ''),
            'enabled' => false, // Requires signup
        ],
        'pixabay' => [
            'url' => 'https://pixabay.com/api/',
            'key' => env('PIXABAY_API_KEY', ''),
            'enabled' => false, // Requires signup
        ],
    ],

    // Images to download per country
    'images_per_country' => env('COUNTRY_IMAGES_COUNT', 5),

    // Image quality requirements
    'min_width' => 800,
    'min_height' => 600,

    // Temp storage for downloads
    'temp_path' => storage_path('app/temp/country-images'),

    // Search queries per image type (rotated for variety)
    'search_queries' => [
        'landmarks' => '{country} landmarks famous buildings',
        'travel' => '{country} travel tourism',
        'skyline' => '{capital} skyline cityscape',
        'culture' => '{country} culture tradition',
        'food' => '{famous_dish} food cuisine',
    ],
];
