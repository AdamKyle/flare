<?php

namespace App\Game\Kingdoms\Providers;


use League\Fractal\Manager;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

use App\Flare\Transformers\KingdomAttackLogsTransformer;
use App\Flare\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Console\Commands\DeleteKingdomLogs;
use App\Game\Kingdoms\Service\UnitRecallService;
use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\Kingdoms\Service\UnitReturnService;
use App\Game\Kingdoms\Service\AttackWithItemsService;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Service\KingdomService;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Service\KingdomAttackService;
use App\Game\Kingdoms\Transformers\SelectedKingdom;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;
use App\Game\Kingdoms\Console\Commands\UpdateKingdoms;
use App\Game\Kingdoms\Handlers\TooMuchPopulationHandler;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Middleware\DoesKingdomBelongToAuthorizedUser;
use App\Game\Kingdoms\Service\AbandonKingdomService;
use App\Game\Kingdoms\Service\KingdomSettleService;
use App\Game\Kingdoms\Service\KingdomUpdateService;
use App\Game\Kingdoms\Service\PurchasePeopleService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Handlers\AttackKingdomWithUnitsHandler;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Game\Kingdoms\Validators\MoveUnitsValidator;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Maps\Services\LocationService;

class ServiceProvider extends ApplicationServiceProvider {

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void {
        $this->app->bind(KingdomBuilder::class, function($app) {
            return new KingdomBuilder();
        });

        $this->app->bind(UpdateKingdomHandler::class, function($app) {
            return new UpdateKingdomHandler(
                $app->make(Manager::class),
                $app->make(KingdomTransformer::class)
            );
        });

        $this->app->bind(KingdomBuildingService::class, function($app) {
            return new KingdomBuildingService($app->make(UpdateKingdomHandler::class));
        });

        $this->app->bind(KingdomSettleService::class, function($app) {
            return new KingdomSettleService(
                $app->make(KingdomBuilder::class),
                $app->make(UpdateKingdomHandler::class),
            );
        });

        $this->app->bind(UnitMovementService::class, function($app) {
            return new UnitMovementService(
                $app->make(DistanceCalculation::class),
                $app->make(MoveUnitsValidator::class),
                $app->make(UpdateKingdom::class)
            );
        });

        $this->app->bind(MoveUnitsValidator::class, function() {
            return new MoveUnitsValidator();
        });

        $this->app->bind(UnitService::class, function($app) {
            return new UnitService($app->make(UpdateKingdomHandler::class));
        });

        $this->app->bind(KingdomService::class, function($app) {
            return new KingdomService(
                $app->make(UpdateKingdomHandler::class),
            );
        });

        $this->app->bind(UnitRecallService::class, function($app) {
           return new UnitRecallService();
        });

        $this->app->bind(TooMuchPopulationHandler::class, function() {
            return new TooMuchPopulationHandler();
        });

        $this->app->bind(KingdomUpdateService::class, function($app) {
            return new KingdomUpdateService(
                $app->make(GiveKingdomsToNpcHandler::class),
                $app->make(TooMuchPopulationHandler::class),
                $app->make(UpdateKingdom::class)
            );
        });

        $this->app->bind(SelectedKingdom::class, function() {
            return new SelectedKingdom;
        });

        $this->app->bind(KingdomTransformer::class, function() {
            return new KingdomTransformer;
        });

        $this->app->bind(AttackWithItemsService::class, function($app) {
            return new AttackWithItemsService(
                $app->make(UpdateKingdom::class),
            );
        });

        $this->app->bind(UnitReturnService::class, function($app) {
           return new UnitReturnService();
        });

        $this->app->bind(KingdomAttackService::class, function($app) {
            return new KingdomAttackService(
                $app->make(SelectedKingdom::class),
                $app->make(Manager::class),
                $app->make(KingdomTransformer::class)
            );
        });

        $this->app->bind(GiveKingdomsToNpcHandler::class, function($app) {
            return new GiveKingdomsToNpcHandler($app->make(LocationService::class));
        });

        $this->app->bind(PurchasePeopleService::class, function($app) {
           return new PurchasePeopleService($app->make(UpdateKingdom::class));
        });

        $this->app->bind(AbandonKingdomService::class, function($app) {
            return new AbandonKingdomService(
                $app->make(UpdateKingdom::class),
                $app->make(GiveKingdomsToNpcHandler::class),
            );
        });

        $this->app->bind(UpdateKingdom::class, function($app) {
            return new UpdateKingdom(
                $app->make(KingdomTransformer::class),
                $app->make(KingdomAttackLogsTransformer::class),
                $app->make(Manager::class)
            );
        });

        $this->app->bind(KingdomAttackService::class, function($app) {
           return new KingdomAttackService(
               $app->make(UnitMovementService::class),
               $app->make(MoveUnitsValidator::class),
               $app->make(UpdateKingdom::class)
           );
        });

        $this->app->bind(AttackKingdomWithUnitsHandler::class, function() {
            return new AttackKingdomWithUnitsHandler();
        });

        $this->commands([
            DeleteKingdomLogs::class,
            UpdateKingdoms::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void {
        $router = $this->app['router'];

        $router->aliasMiddleware('character.owns.kingdom', DoesKingdomBelongToAuthorizedUser::class);
    }
}
