<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'Laravel'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'http://localhost'),

    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => env('TIME_ZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Login/Registration
    |--------------------------------------------------------------------------
    |
    | Allow us to programmatically disable login and registration
    |
    */

    'disabled_reg_and_login' => env('DISABLE_REG_AND_LOGIN', false),


    'allowed_email' => env('ALLOWED_CHARACTER_EMAIL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => [


        /*
         * Laravel Framework Service Providers...
         */
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        /*
         * Application Service Providers...
         */
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\BroadcastServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\HorizonServiceProvider::class,
        App\Providers\RouteServiceProvider::class,

        /**
         * Flare Related
         */
        App\Flare\Providers\ServiceProvider::class,
        App\Flare\Providers\EventsProvider::class,
        App\Flare\MapGenerator\Providers\ServiceProvider::class,
        App\Flare\AffixGenerator\Providers\ServiceProvider::class,
        App\Flare\AlchemyItemGenerator\Providers\ServiceProvider::class,
        App\Flare\GameImporter\Providers\ServiceProvider::class,
        App\Flare\ExponentialCurve\Providers\ServiceProvider::class,

        /**
         * Component Related
         */
        App\Flare\Github\Providers\ServiceProvider::class,

        /**
         * Admin Related
         */
        App\Admin\Providers\ServiceProvider::class,

        /**
         * Game Event Providers
         */
        App\Game\Core\Providers\EventsProvider::class,
        App\Game\Battle\Providers\EventsProvider::class,

        /**
         * Gem Actions
         */
        \App\Game\Gems\Providers\ServiceProvider::class,

        /**
         * Npc Actions
         */
        App\Game\NpcActions\QueenOfHeartsActions\Providers\ServiceProvider::class,
        App\Game\NpcActions\SeerActions\Providers\ServiceProvider::class,
        App\Game\NpcActions\WorkBench\Providers\ServiceProvider::class,
        App\Game\NpcActions\LabyrinthOracle\Providers\ServiceProvider::class,

        /**
         * Game Related
         */
        App\Game\Events\Providers\ServiceProvider::class,
        App\Game\Exploration\Providers\ServiceProvider::class,
        App\Game\CharacterInventory\Providers\ServiceProvider::class,
        App\Game\Core\Providers\ServiceProvider::class,
        App\Game\Battle\Providers\ServiceProvider::class,
        App\Game\BattleRewardProcessing\Providers\ServiceProvider::class,
        App\Game\Messages\Providers\EventsProvider::class,
        App\Game\Messages\Providers\ServiceProvider::class,
        App\Game\Maps\Providers\EventsProvider::class,
        App\Game\Maps\Providers\ServiceProvider::class,
        App\Game\Kingdoms\Providers\ServiceProvider::class,
        App\Game\Skills\Providers\ServiceProvider::class,
        App\Game\Market\Providers\ServiceProvider::class,
        App\Game\Shop\Providers\ServiceProvider::class,
        App\Game\Shop\Providers\EventsProvider::class,
        App\Game\Quests\Providers\ServiceProvider::class,
        App\Game\GuideQuests\Providers\ServiceProvider::class,
        App\Game\SpecialtyShops\Providers\ServiceProvider::class,
        App\Game\Gambler\Providers\ServiceProvider::class,
        App\Game\Mercenaries\Providers\ServiceProvider::class,
        App\Game\Reincarnate\Providers\ServiceProvider::class,
        App\Game\ClassRanks\Providers\ServiceProvider::class,
        App\Game\NpcActions\SeerActions\Providers\ServiceProvider::class,
        App\Game\NpcActions\QueenOfHeartsActions\Providers\ServiceProvider::class,
        App\Game\NpcActions\WorkBench\Providers\ServiceProvider::class,
        App\Game\Raids\Providers\ServiceProvider::class,
        App\Game\Factions\FactionLoyalty\Providers\ServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => [

        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,


        /**
         * Game Related
         */
        'KingdomLogStatus' => \App\Flare\Values\Wrappers\KingdomLogStatusHelper::class,
        'NpcCommandType'   => \App\Flare\Values\Wrappers\NpcCommandTypeHelper::class,
        'ItemEffects'      => \App\Flare\Values\Wrappers\ItemEffectsHelper::class,
        'GameVersion'      => \App\Flare\Values\GameVersionHelper::class,
        'AdventureRewards' => \App\Game\Adventures\View\AdventureCompletedRewards::class,
        'GuideQuests'      => \App\Flare\Values\Wrappers\HasGuideQuestsCompletedOrEnabled::class,
    ],

];
