<?php

return [
    'credentials' => [
        'access_key' => env('AWS_ACCESS_KEY_ID'),
        'secret_key' => env('AWS_SECRET_ACCESS_KEY')
    ],
    'region' => env('AWS_REGION', 'ap-northeast-1'),
    'version' => env('AWS_VERSION', 'latest'),
    'bucket' => env('AWS_S3_BUCKET'),
    'prefix' => env('AWS_S3_PREFIX', ''),
    'options' => [
        //
    ]
];
