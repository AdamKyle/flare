<?php

use Illuminate\Support\Str;

return [

    /*
    |--------------------------------------------------------------------------
    | Horizon Domain
    |--------------------------------------------------------------------------
    |
    | This is the subdomain where Horizon will be accessible from. If this
    | setting is null, Horizon will reside under the same domain as the
    | application. Otherwise, this value will serve as the subdomain.
    |
    */

    'domain' => null,

    /*
    |--------------------------------------------------------------------------
    | Horizon Path
    |--------------------------------------------------------------------------
    |
    | This is the URI path where Horizon will be accessible from. Feel free
    | to change this path to anything you like. Note that the URI will not
    | affect the paths of its internal API that aren't exposed to users.
    |
    */

    'path' => 'horizon',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Connection
    |--------------------------------------------------------------------------
    |
    | This is the name of the Redis connection where Horizon will store the
    | meta information required for it to function. It includes the list
    | of supervisors, failed jobs, job metrics, and other information.
    |
    */

    'use' => 'default',

    /*
    |--------------------------------------------------------------------------
    | Horizon Redis Prefix
    |--------------------------------------------------------------------------
    |
    | This prefix will be used when storing all Horizon data in Redis. You
    | may modify the prefix when you are running multiple installations
    | of Horizon on the same server so that they don't have problems.
    |
    */

    'prefix' => env(
        'HORIZON_PREFIX',
        Str::slug(env('APP_NAME', 'laravel'), '_') . '_horizon:'
    ),

    /*
    |--------------------------------------------------------------------------
    | Horizon Route Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware will get attached onto each Horizon route, giving you
    | the chance to add your own middleware to this list or change any of
    | the existing middleware. Or, you can simply stick with this list.
    |
    */

    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Queue Wait Time Thresholds
    |--------------------------------------------------------------------------
    |
    | This option allows you to configure when the LongWaitDetected event
    | will be fired. Every connection / queue combination may have its
    | own, unique threshold (in seconds) before this event is fired.
    |
    */

    'waits' => [
        'redis:default' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Job Trimming Times
    |--------------------------------------------------------------------------
    |
    | Here you can configure for how long (in minutes) you desire Horizon to
    | persist the recent and failed jobs. Typically, recent jobs are kept
    | for one hour while all failed jobs are stored for an entire week.
    |
    */

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    /*
    |--------------------------------------------------------------------------
    | Metrics
    |--------------------------------------------------------------------------
    |
    | Here you can configure how many snapshots should be kept to display in
    | the metrics graph. This will get used in combination with Horizon's
    | `horizon:snapshot` schedule to define how long to retain metrics.
    |
    */

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Fast Termination
    |--------------------------------------------------------------------------
    |
    | When this option is enabled, Horizon's "terminate" command will not
    | wait on all of the workers to terminate unless the --wait option
    | is provided. Fast termination can shorten deployment delay by
    | allowing a new instance of Horizon to start while the last
    | instance will continue to terminate each of its workers.
    |
    */

    'fast_termination' => false,

    /*
    |--------------------------------------------------------------------------
    | Memory Limit (MB)
    |--------------------------------------------------------------------------
    |
    | This value describes the maximum amount of memory the Horizon master
    | supervisor may consume before it is terminated and restarted. For
    | configuring these limits on your workers, see the next section.
    |
    */

    'memory_limit' => 64,

    /*
    |--------------------------------------------------------------------------
    | Queue Worker Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may define the queue worker settings used by your application
    | in all environments. These supervisors and settings handle all your
    | queued jobs and will be provisioned by Horizon during deployment.
    |
    */

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['default'],
            'balance' => 'auto',
            'maxProcesses' => 1,
            'memory' => 128,
            'tries' => 1,
            'nice' => 0,
        ],
        'supervisor-kingdoms' => [
            'connection' => 'kingdom_jobs',
            'queue' => ['kingdom_jobs'],
            'balance' => 'auto',
            'maxProcesses' => 10,
            'memory' => 256,
            'tries' => 2,
            'nice' => 0,
        ],
        'supervisor-weekly-spawn' => [
            'connection' => 'weekly_spawn',
            'queue' => ['weekly_spawn'],
            'balance' => 'auto',
            'maxProcesses' => 1,
            'memory' => 128,
            'tries' => 1,
            'nice' => 0,
        ],

        'supervisor-event-battle-reward' => [
            'connection' => 'event_battle_reward',
            'queue' => ['event_battle_reward'],
            'balance' => 'auto',
            'maxProcesses' => 25,
            'memory' => 256,
            'tries' => 5,
            'nice' => 0,
        ],

        'supervisor-long-running' => [
            'connection' => 'long_running',
            'queue' => ['default_long'],
            'balance' => 'auto',
            'maxProcesses' => 10,
            'memory' => 256,
            'tries' => 2,
            'timeout' => 1900,
            'nice' => 0,
        ],

        'supervisor-battle-reward-xp' => [
            'connection' => 'battle_reward_xp',
            'queue' => ['battle_reward_xp'],
            'balance' => 'auto',
            'maxProcesses' => 25,
            'memory' => 256,
            'tries' => 5,
            'nice' => 0,
        ],

        'supervisor-exploration-battle-xp-reward' => [
            'connection' => 'exploration_battle_xp_reward',
            'queue' => ['exploration_battle_xp_reward'],
            'balance' => 'auto',
            'maxProcesses' => 25,
            'memory' => 256,
            'tries' => 5,
            'nice' => 0,
        ],

        'supervisor-exploration-battle-skill-xp-reward' => [
            'connection' => 'exploration_battle_skill_xp_reward',
            'queue' => ['exploration_battle_skill_xp_reward'],
            'balance' => 'auto',
            'maxProcesses' => 25,
            'memory' => 256,
            'tries' => 5,
            'nice' => 0,
        ],

        'supervisor-battle-reward-currencies' => [
            'connection' => 'battle_reward_currencies',
            'queue' => ['battle_reward_currencies'],
            'balance' => 'auto',
            'maxProcesses' => 25,
            'memory' => 256,
            'tries' => 5,
            'nice' => 0,
        ],

        'supervisor-battle-secondary-reward' => [
            'connection' => 'battle_secondary_reward',
            'queue' => ['battle_secondary_reward'],
            'balance' => 'auto',
            'maxProcesses' => 25,
            'memory' => 256,
            'tries' => 5,
            'nice' => 0,
        ],

        'supervisor-battle-reward-factions' => [
            'connection' => 'battle_reward_factions',
            'queue' => ['battle_reward_factions'],
            'balance' => 'auto',
            'maxProcesses' => 25,
            'memory' => 256,
            'tries' => 5,
            'nice' => 0,
        ],

        'supervisor-battle-reward-global-event' => [
            'connection' => 'battle_reward_global_event',
            'queue' => ['battle_reward_global_event'],
            'balance' => 'auto',
            'maxProcesses' => 25,
            'memory' => 256,
            'tries' => 5,
            'nice' => 0,
        ],

        'supervisor-battle-reward-location-handlers' => [
            'connection' => 'battle_reward_location_handlers',
            'queue' => ['battle_reward_location_handlers'],
            'balance' => 'auto',
            'maxProcesses' => 25,
            'memory' => 256,
            'tries' => 5,
            'nice' => 0,
        ],

        'supervisor-battle-reward-weekly-fights' => [
            'connection' => 'battle_reward_weekly_fights',
            'queue' => ['battle_reward_weekly_fights'],
            'balance' => 'auto',
            'maxProcesses' => 25,
            'memory' => 256,
            'tries' => 5,
            'nice' => 0,
        ],

        'supervisor-battle-reward-item-handler' => [
            'connection' => 'battle_reward_item_handler',
            'queue' => ['battle_reward_item_handler'],
            'balance' => 'auto',
            'maxProcesses' => 25,
            'memory' => 256,
            'tries' => 5,
            'nice' => 0,
        ],

    ],


    'environments' => [
        'production' => [
            'supervisor-1' => [
                'connection' => 'redis',
                'queue' => ['default'],
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-kingdoms' => [
                'connection' => 'kingdom_jobs',
                'queue' => ['kingdom_jobs'],
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-weekly-spawn' => [
                'connection' => 'weekly_spawn',
                'queue' => ['weekly_spawn'],
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
            'supervisor-long-running' => [
                'connection' => 'long_running',
                'queue' => ['default_long'],
                'maxProcesses' => 2,
                'memory' => 128,
                'tries' => 2,
                'timeout' => 900,
                'nice' => 0,
            ],

            'supervisor-event-battle-reward' => [
                'connection' => 'event_battle_reward',
                'queue' => ['event_battle_reward'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-xp' => [
                'connection' => 'battle_reward_xp',
                'queue' => ['battle_reward_xp'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-exploration-battle-xp-reward' => [
                'connection' => 'exploration_battle_xp_reward',
                'queue' => ['exploration_battle_xp_reward'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-exploration-battle-skill-xp-reward' => [
                'connection' => 'exploration_battle_skill_xp_reward',
                'queue' => ['exploration_battle_skill_xp_reward'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-currencies' => [
                'connection' => 'battle_reward_currencies',
                'queue' => ['battle_reward_currencies'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-secondary-reward' => [
                'connection' => 'battle_secondary_reward',
                'queue' => ['battle_secondary_reward'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-factions' => [
                'connection' => 'battle_reward_factions',
                'queue' => ['battle_reward_factions'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-global-event' => [
                'connection' => 'battle_reward_global_event',
                'queue' => ['battle_reward_global_event'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-location-handlers' => [
                'connection' => 'battle_reward_location_handlers',
                'queue' => ['battle_reward_location_handlers'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-weekly-fights' => [
                'connection' => 'battle_reward_weekly_fights',
                'queue' => ['battle_reward_weekly_fights'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-item-handler' => [
                'connection' => 'battle_reward_item_handler',
                'queue' => ['battle_reward_item_handler'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

        ],

        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
            ],
            'supervisor-kingdoms' => [
                'connection' => 'kingdom_jobs',
                'maxProcesses' => 3,
            ],
            'supervisor-weekly-spawn' => [
                'connection' => 'weekly_spawn',
                'maxProcesses' => 3,
            ],
            'supervisor-long-running' => [
                'connection' => 'long_running',
                'queue' => ['default_long'],
                'balance' => 'auto',
                'maxProcesses' => 2,
                'memory' => 128,
                'tries' => 2,
                'timeout' => 900,
                'nice' => 0,
            ],

            'supervisor-event-battle-reward' => [
                'connection' => 'event_battle_reward',
                'queue' => ['event_battle_reward'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-xp' => [
                'connection' => 'battle_reward_xp',
                'queue' => ['battle_reward_xp'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-exploration-battle-xp-reward' => [
                'connection' => 'exploration_battle_xp_reward',
                'queue' => ['exploration_battle_xp_reward'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-exploration-battle-skill-xp-reward' => [
                'connection' => 'exploration_battle_skill_xp_reward',
                'queue' => ['exploration_battle_skill_xp_reward'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-currencies' => [
                'connection' => 'battle_reward_currencies',
                'queue' => ['battle_reward_currencies'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-secondary-reward' => [
                'connection' => 'battle_secondary_reward',
                'queue' => ['battle_secondary_reward'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-factions' => [
                'connection' => 'battle_reward_factions',
                'queue' => ['battle_reward_factions'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-global-event' => [
                'connection' => 'battle_reward_global_event',
                'queue' => ['battle_reward_global_event'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-location-handlers' => [
                'connection' => 'battle_reward_location_handlers',
                'queue' => ['battle_reward_location_handlers'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-weekly-fights' => [
                'connection' => 'battle_reward_weekly_fights',
                'queue' => ['battle_reward_weekly_fights'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

            'supervisor-battle-reward-item-handler' => [
                'connection' => 'battle_reward_item_handler',
                'queue' => ['battle_reward_item_handler'],
                'balance' => 'auto',
                'maxProcesses' => 25,
                'memory' => 256,
                'tries' => 5,
                'nice' => 0,
            ],

        ],
    ],

];
