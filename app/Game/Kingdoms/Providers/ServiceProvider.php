<?php

namespace App\Game\Kingdoms\Providers;

use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use App\Game\Kingdoms\Builders\AttackBuilder;
use App\Game\Kingdoms\Builders\AttackedKingdomBuilder;
use App\Game\Kingdoms\Builders\TookKingdomBuilder;
use App\Game\Kingdoms\Console\Commands\DeleteKingdomLogs;
use App\Game\Kingdoms\Handlers\NotifyHandler;
use App\Game\Kingdoms\Service\UnitRecallService;
use App\Game\Kingdoms\Builders\KingdomAttackedBuilder;
use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\Kingdoms\Handlers\KingdomHandler;
use App\Game\Kingdoms\Handlers\TakeKingdomHandler;
use App\Game\Kingdoms\Handlers\AttackHandler;
use App\Game\Kingdoms\Handlers\UnitHandler;
use App\Game\Kingdoms\Handlers\SiegeHandler;
use App\Game\Maps\Services\MovementService;
use App\Game\Kingdoms\Service\UnitReturnService;
use App\Game\Kingdoms\Service\KingdomLogService;
use App\Game\Kingdoms\Service\AttackService;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Service\KingdomService;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Service\KingdomResourcesService;
use App\Game\Kingdoms\Service\KIngdomsAttackService;
use App\Game\Kingdoms\Transformers\SelectedKingdom;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;
use App\Flare\Transformers\KingdomTransformer;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(KingdomBuilder::class, function($app) {
            return new KingdomBuilder();
        });

        $this->app->bind(KingdomBuildingService::class, function($app) {
            return new KingdomBuildingService;
        });

        $this->app->bind(UnitService::class, function($app) {
            return new UnitService;
        });

        $this->app->bind(KingdomService::class, function($app) {
            return new KingdomService($app->make(KingdomBuilder::class));
        });

        $this->app->bind(UnitRecallService::class, function($app) {
           return new UnitRecallService();
        });

        $this->app->bind(KingdomResourcesService::class, function($app) {
            return new KingdomResourcesService(
                $app->make(Manager::class),
                $app->make(KingdomTransformer::class),
                $app->make(MovementService::class)
            );
        });

        $this->app->bind(SelectedKingdom::class, function() {
            return new SelectedKingdom;
        });

        $this->app->bind(KingdomTransformer::class, function() {
            return new KingdomTransformer;
        });

        $this->app->bind(AttackHandler::class, function() {
            return new AttackHandler;
        });

        $this->app->bind(AttackBuilder::class, function($app) {
           return new AttackBuilder();
        });

        $this->app->bind(SiegeHandler::class, function($app) {
            return new SiegeHandler($app->make(AttackHandler::class));
        });

        $this->app->bind(UnitHandler::class, function() {
            return new UnitHandler;
        });

        $this->app->bind(TakeKingdomHandler::class, function($app) {
            return new TakeKingdomHandler($app->make(MovementService::class), $app->make(UnitRecallService::class));
        });

        $this->app->bind(KingdomHandler::class, function($app) {
           return new KingdomHandler($app->make(TakeKingdomHandler::class));
        });

        $this->app->bind(NotifyHandler::class, function($app) {
            return new NotifyHandler();
        });

        $this->app->bind(KingdomAttackedBuilder::class, function() {
            return new KingdomAttackedBuilder();
        });

        $this->app->bind(AttackedKingdomBuilder::class, function() {
            return new AttackedKingdomBuilder();
        });

        $this->app->bind(TookKingdomBuilder::class, function() {
           return new TookKingdomBuilder();
        });

        $this->app->bind(KingdomLogService::class, function($app) {
            return new KingdomLogService(
                $app->make(KingdomAttackedBuilder::class),
                $app->make(AttackedKingdomBuilder::class),
                $app->make(TookKingdomBuilder::class)
            );
        });

        $this->app->bind(AttackService::class, function($app) {
            return new AttackService(
                $app->make(SiegeHandler::class),
                $app->make(UnitHandler::class),
                $app->make(KingdomHandler::class),
                $app->make(NotifyHandler::class),
                $app->make(AttackBuilder::class)
            );
        });

        $this->app->bind(UnitReturnService::class, function($app) {
           return new UnitReturnService();
        });

        $this->app->bind(KingdomsAttackService::class, function($app) {
            return new KingdomsAttackService(
                $app->make(SelectedKingdom::class),
                $app->make(Manager::class),
                $app->make(KingdomTransformer::class)
            );
        });

        $this->app->bind(GiveKingdomsToNpcHandler::class, function($app) {
            return new GiveKingdomsToNpcHandler($app->make(MovementService::class));
        });

        $this->commands([
            DeleteKingdomLogs::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
