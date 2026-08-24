<?php

namespace App\Flare\Providers;

use App\Flare\Handlers\MessageThrottledHandler;
use App\Flare\Middleware\IsCharacterDeadMiddleware;
use App\Flare\Middleware\IsCharacterWhoTheySayTheyAreMiddleware;
use App\Flare\Middleware\IsGloballyTimedOut;
use App\Flare\Middleware\IsPlayerBannedMiddleware;
use App\Flare\Middleware\TrackSessionLifeMiddleware;
use App\Flare\Middleware\UpdatePlayerSessionActivity;
use App\Flare\Services\CanUserEnterSiteService;
use App\Flare\Services\SiteAccessStatisticService;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

        $this->app->bind(MessageThrottledHandler::class, function ($app) {
            return new MessageThrottledHandler;
        });

        $this->app->bind(CanUserEnterSiteService::class, function ($app) {
            return new CanUserEnterSiteService;
        });

        $this->app->bind(SiteAccessStatisticService::class, function () {
            return new SiteAccessStatisticService();
        });

        $this->app->bind(PlainDataSerializer::class, function () {
            return new PlainDataSerializer;
        });

    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('is.character.dead', IsCharacterDeadMiddleware::class);
        $router->aliasMiddleware('is.player.banned', IsPlayerBannedMiddleware::class);
        $router->aliasMiddleware('is.character.who.they.say.they.are', IsCharacterWhoTheySayTheyAreMiddleware::class);
        $router->aliasMiddleware('is.globally.timed.out', IsGloballyTimedOut::class);
        $router->aliasMiddleware('session.time.tracking', TrackSessionLifeMiddleware::class);
        $router->aliasMiddleware('update.player-activity', UpdatePlayerSessionActivity::class);
    }
}
