<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DRIVER', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Default Cloud Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Many applications store files both locally and in the cloud. For this
    | reason, you may specify a default "cloud" driver here. This driver
    | will be bound as the Cloud disk implementation in the container.
    |
    */

    'cloud' => env('FILESYSTEM_CLOUD', 's3'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        'maps' => [
            'driver' => 'local',
            'root' => storage_path('app/public/maps'),
            'url' => env('APP_URL').'/storage/maps',
            'visibility' => 'public',
        ],

        'suggestions-and-bugs' => [
            'driver' => 'local',
            'root' => storage_path('app/public/suggestions_bugs_screen_shots'),
            'url' => env('APP_URL').'/storage/suggestions_bugs_screen_shots',
            'visibility' => 'public',
        ],

        'info-sections-images' => [
            'driver' => 'local',
            'root' => storage_path('app/public/info-sections-images'),
            'url' => env('APP_URL').'/storage/info-sections-images',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

        'data-imports' => [
            'driver' => 'local',
            'root' => resource_path('data-imports'),
            'visibility' => 'private',
        ],

        'data-maps' => [
            'driver' => 'local',
            'root' => resource_path('maps'),
            'visibility' => 'private',
        ],

    ],

];
