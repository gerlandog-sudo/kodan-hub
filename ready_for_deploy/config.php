<?php
/**
 * KODAN-HUB Configuration
 * Central registry for applications and their respective Gemini API Keys.
 */

return [
    // Security header name
    'auth_header' => 'HTTP_X_KODAN_TOKEN',

    // Application Registry
    // Keys are the tokens that the apps must send in the X-KODAN-TOKEN header
    'apps' => [
        'SK-HUB-7721-KDN' => [
            'name' => 'SmartCook',
            'gemini_key' => 'AIzaSyAnhwEt8Eke9Wu8TCLm3huqWBZWFuHRXhk',
            'allowed_models' => ['gemma-3-4b-it', 'gemini-2.0-flash', 'gemini-1.5-flash'],
        ],
        'TT-HUB-4482-KDN' => [
            'name' => 'TimeTracker',
            'gemini_key' => 'AIzaSyC8YtiU8iYgAd9e-WQ_Kv3pmI8lRTWSlto',
            'allowed_models' => ['gemma-3-4b-it', 'gemini-2.0-flash', 'gemini-1.5-flash'],
        ]
    ],

    // Default system settings
    'settings' => [
        'log_enabled' => true,
        'debug_mode' => false,
        'default_model' => 'gemma-3-4b-it'
    ]
];
