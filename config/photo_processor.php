<?php

return [
    'default_service' => env('PHOTO_PROCESSOR_DEFAULT_SERVICE', 'thumbor'),

    'services' => [
        'cloudinary' => [
            'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
            'api_key'    => env('CLOUDINARY_API_KEY'),
            'api_secret' => env('CLOUDINARY_API_SECRET'),
        ],

        'thumbor' => [
            'url'      => env('THUMBOR_URL'),
            'secret'   => env('THUMBOR_SECRET')
        ],
    ],
];
