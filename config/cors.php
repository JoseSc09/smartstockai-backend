<?php

return [

    'paths' => [
        'api/',
        'sanctum/csrf-cookie',
        'login',
        'logout',
    ],

    'allowed_methods' => [''],

    'allowed_origins' => [
        'https://smartstockai-frontend.innodev-jm.com/',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
