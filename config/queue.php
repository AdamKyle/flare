<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for everyone. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_CONNECTION', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => 0,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'prefix' => env('SQS_PREFIX', 'https://sqs.us-east-1.amazonaws.com/your-account-id'),
            'queue' => env('SQS_QUEUE', 'your-queue-name'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
        ],

        'kingdom_jobs' => [
            'driver' => 'redis',
            'connection' => 'kingdom_jobs',
            'queue' => 'kingdom_jobs',
            'retry_after' => 90,
            'block_for' => null,
        ],

        'weekly_spawn' => [
            'driver' => 'redis',
            'connection' => 'weekly_spawn',
            'queue' => 'weekly_spawn',
            'retry_after' => 90,
            'block_for' => null,
        ],

        'event_battle_reward' => [
            'driver' => 'redis',
            'connection' => 'event_battle_reward',
            'queue' => 'event_battle_reward',
            'retry_after' => 1200,
            'block_for' => null,
        ],

        'battle_reward_xp' => [
            'driver' => 'redis',
            'connection' => 'battle_reward_xp',
            'queue' => 'battle_reward_xp',
            'retry_after' => 1200,
            'block_for' => null,
        ],

        'exploration_battle_xp_reward' => [
            'driver' => 'redis',
            'connection' => 'exploration_battle_xp_reward',
            'queue' => 'exploration_battle_xp_reward',
            'retry_after' => 1200,
            'block_for' => null,
        ],

        'exploration_battle_skill_xp_reward' => [
            'driver' => 'redis',
            'connection' => 'exploration_battle_skill_xp_reward',
            'queue' => 'exploration_battle_skill_xp_reward',
            'retry_after' => 1200,
            'block_for' => null,
        ],

        'battle_reward_currencies' => [
            'driver' => 'redis',
            'connection' => 'battle_reward_currencies',
            'queue' => 'battle_reward_currencies',
            'retry_after' => 1200,
            'block_for' => null,
        ],

        'battle_secondary_reward' => [
            'driver' => 'redis',
            'connection' => 'battle_secondary_reward',
            'queue' => 'battle_secondary_reward',
            'retry_after' => 1200,
            'block_for' => null,
        ],

        'battle_reward_factions' => [
            'driver' => 'redis',
            'connection' => 'battle_reward_factions',
            'queue' => 'battle_reward_factions',
            'retry_after' => 1200,
            'block_for' => null,
        ],

        'battle_reward_global_event' => [
            'driver' => 'redis',
            'connection' => 'battle_reward_global_event',
            'queue' => 'battle_reward_global_event',
            'retry_after' => 1200,
            'block_for' => null,
        ],

        'battle_reward_location_handlers' => [
            'driver' => 'redis',
            'connection' => 'battle_reward_location_handlers',
            'queue' => 'battle_reward_location_handlers',
            'retry_after' => 1200,
            'block_for' => null,
        ],

        'battle_reward_weekly_fights' => [
            'driver' => 'redis',
            'connection' => 'battle_reward_weekly_fights',
            'queue' => 'battle_reward_weekly_fights',
            'retry_after' => 1200,
            'block_for' => null,
        ],

        'battle_reward_item_handler' => [
            'driver' => 'redis',
            'connection' => 'battle_reward_item_handler',
            'queue' => 'battle_reward_item_handler',
            'retry_after' => 1200,
            'block_for' => null,
        ],

        'long_running' => [
            'driver' => 'redis',
            'connection' => 'long_running',
            'queue' => 'default_long',
            'retry_after' => 1200,
            'block_for' => null,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

];
