<?php

namespace App\Game\Kingdoms\Providers;

use App\Flare\Transformers\CapitalCityKingdomBuildingTransformer;
use App\Game\Kingdoms\Transformers\KingdomAttackLogsTransformer;
use App\Game\Kingdoms\Transformers\KingdomBuildingTransformer;
use App\Game\Kingdoms\Transformers\KingdomTransformer;
use App\Game\Kingdoms\Transformers\UnitMovementTransformer;
use App\Game\Kingdoms\Builders\KingdomBuilder;
use App\Game\Kingdoms\Console\Commands\DeleteKingdomLogs;
use App\Game\Kingdoms\Console\Commands\ResetCapitalCityWalkingStatus;
use App\Game\Kingdoms\Console\Commands\UpdateKingdoms;
use App\Game\Kingdoms\Handlers\AttackKingdomWithUnitsHandler;
use App\Game\Kingdoms\Handlers\AttackLogHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityBuildingManagementRequestHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityBuildingRequestHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessBuildingRequestHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessUnitRequestHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityRequestResourcesHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityUnitManagementRequestHandler;
use App\Game\Kingdoms\Handlers\DefenderArcherHandler;
use App\Game\Kingdoms\Handlers\DefenderSiegeHandler;
use App\Game\Kingdoms\Handlers\GiveKingdomsToNpcHandler;
use App\Game\Kingdoms\Handlers\KingdomAirshipHandler;
use App\Game\Kingdoms\Handlers\KingdomSiegeHandler;
use App\Game\Kingdoms\Handlers\KingdomUnitHandler;
use App\Game\Kingdoms\Handlers\ReturnSurvivingUnitHandler;
use App\Game\Kingdoms\Handlers\SettlerHandler;
use App\Game\Kingdoms\Handlers\TooMuchPopulationHandler;
use App\Game\Kingdoms\Handlers\UpdateKingdomHandler;
use App\Game\Kingdoms\Middleware\DoesKingdomBelongToAuthorizedUser;
use App\Game\Kingdoms\Service\AbandonKingdomService;
use App\Game\Kingdoms\Service\AttackWithItemsService;
use App\Game\Kingdoms\Service\CancelBuildingRequestService;
use App\Game\Kingdoms\Service\CancelUnitRequestService;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use App\Game\Kingdoms\Service\CapitalCityUnitManagement;
use App\Game\Kingdoms\Service\ExpandResourceBuildingService;
use App\Game\Kingdoms\Service\KingdomAttackService;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Service\KingdomMovementTimeCalculationService;
use App\Game\Kingdoms\Service\KingdomQueueService;
use App\Game\Kingdoms\Service\KingdomService;
use App\Game\Kingdoms\Service\KingdomSettleService;
use App\Game\Kingdoms\Service\KingdomUpdateService;
use App\Game\Kingdoms\Service\PurchasePeopleService;
use App\Game\Kingdoms\Service\ResourceTransferService;
use App\Game\Kingdoms\Service\SteelSmeltingService;
use App\Game\Kingdoms\Service\UnitMovementService;
use App\Game\Kingdoms\Service\UnitRecallService;
use App\Game\Kingdoms\Service\UnitReturnService;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Service\UpdateKingdom;
use App\Game\Kingdoms\Transformers\KingdomTableTransformer;
use App\Game\Kingdoms\Transformers\SelectedKingdom;
use App\Game\Kingdoms\Validation\KingdomBuildingResourceValidation;
use App\Game\Kingdoms\Validation\KingdomUnitResourceValidation;
use App\Game\Kingdoms\Validators\MoveUnitsValidator;
use App\Game\Maps\Calculations\DistanceCalculation;
use App\Game\Maps\Services\LocationService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use League\Fractal\Manager;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->app->bind(ResourceTransferService::class, function ($app) {
            return new ResourceTransferService(
                $app->make(DistanceCalculation::class)
            );
        });

        $this->app->bind(KingdomMovementTimeCalculationService::class, function ($app) {
            return new KingdomMovementTimeCalculationService(
                $app->make(DistanceCalculation::class),
            );
        });

        $this->app->bind(KingdomBuildingResourceValidation::class, function ($app) {
            return new KingdomBuildingResourceValidation(
                $app->make(KingdomBuildingService::class)
            );
        });

        $this->app->bind(KingdomUnitResourceValidation::class, function () {
            return new KingdomUnitResourceValidation();
        });

        $this->app->bind(CapitalCityManagementService::class, function ($app) {
            return new CapitalCityManagementService(
                $app->make(UpdateKingdom::class),
                $app->make(CapitalCityBuildingManagement::class),
                $app->make(CapitalCityUnitManagement::class),
                $app->make(CapitalCityKingdomBuildingTransformer::class),
                $app->make(UnitMovementService::class),
                $app->make(Manager::class)
            );
        });

        $this->app->bind(CapitalCityKingdomLogHandler::class, function ($app) {
            return new CapitalCityKingdomLogHandler(
                $app->make(UpdateKingdom::class),
            );
        });

        $this->app->bind(CapitalCityProcessBuildingRequestHandler::class, function ($app) {
            return new CapitalCityProcessBuildingRequestHandler(
                $app->make(CapitalCityKingdomLogHandler::class),
                $app->make(DistanceCalculation::class),
                $app->make(CapitalCityRequestResourcesHandler::class),
                $app->make(CapitalCityBuildingRequestHandler::class),
                $app->make(KingdomBuildingResourceValidation::class)
            );
        });

        $this->app->bind(CapitalCityBuildingManagementRequestHandler::class, function ($app) {
            return new CapitalCityBuildingManagementRequestHandler(
                $app->make(KingdomBuildingService::class),
                $app->make(UnitMovementService::class)
            );
        });

        $this->app->bind(CapitalCityBuildingManagement::class, function ($app) {
            return new CapitalCityBuildingManagement(
                $app->make(CapitalCityBuildingManagementRequestHandler::class),
                $app->make(CapitalCityProcessBuildingRequestHandler::class),
            );
        });

        $this->app->bind(CapitalCityUnitManagementRequestHandler::class, function ($app) {
            return new CapitalCityUnitManagementRequestHandler(
                $app->make(UnitMovementService::class),
                $app->make(UnitService::class),
                $app->make(KingdomUnitResourceValidation::class),
                $app->make(UpdateKingdom::class),
            );
        });

        $this->app->bind(CapitalCityUnitManagement::class, function ($app) {
            return new CapitalCityUnitManagement(
                $app->make(CapitalCityUnitManagementRequestHandler::class),
                $app->make(CapitalCityProcessUnitRequestHandler::class),
            );
        });

        $this->app->bind(CapitalCityProcessUnitRequestHandler::class, function ($app) {
            return new CapitalCityProcessUnitRequestHandler(
                $app->make(CapitalCityKingdomLogHandler::class),
                $app->make(CapitalCityRequestResourcesHandler::class),
                $app->make(DistanceCalculation::class),
                $app->make(UnitService::class),
                $app->make(KingdomUnitResourceValidation::class)
            );
        });

        $this->app->bind(CapitalCityBuildingRequestHandler::class, function ($app) {
            return new CapitalCityBuildingRequestHandler(
                $app->make(CapitalCityKingdomLogHandler::class),
                $app->make(KingdomBuildingService::class),
                $app->make(KingdomBuildingResourceValidation::class),
                $app->make(PurchasePeopleService::class),
                $app->make(UpdateKingdom::class),
            );
        });

        $this->app->bind(CancelBuildingRequestService::class, function ($app) {
            return new CancelBuildingRequestService(
                $app->make(UnitMovementService::class),
                $app->make(CapitalCityKingdomLogHandler::class)
            );
        });

        $this->app->bind(CancelUnitRequestService::class, function ($app) {
            return new CancelUnitRequestService(
                $app->make(CapitalCityKingdomLogHandler::class)
            );
        });

        $this->app->bind(CapitalCityRequestResourcesHandler::class, function ($app) {
            return new CapitalCityRequestResourcesHandler(
                $app->make(ResourceTransferService::class),
                $app->make(KingdomMovementTimeCalculationService::class),
                $app->make(CapitalCityKingdomLogHandler::class),
            );
        });


        $this->app->bind(KingdomBuilder::class, function () {
            return new KingdomBuilder;
        });

        $this->app->bind(SteelSmeltingService::class, function ($app) {
            return new SteelSmeltingService(
                $app->make(UpdateKingdom::class)
            );
        });

        $this->app->bind(UpdateKingdomHandler::class, function ($app) {
            return new UpdateKingdomHandler(
                $app->make(Manager::class),
                $app->make(KingdomTableTransformer::class)
            );
        });

        $this->app->bind(KingdomBuildingService::class, function ($app) {
            return new KingdomBuildingService($app->make(UpdateKingdomHandler::class));
        });

        $this->app->bind(KingdomSettleService::class, function ($app) {
            return new KingdomSettleService(
                $app->make(KingdomBuilder::class),
                $app->make(UpdateKingdomHandler::class),
            );
        });

        $this->app->bind(UnitMovementService::class, function ($app) {
            return new UnitMovementService(
                $app->make(DistanceCalculation::class),
                $app->make(MoveUnitsValidator::class),
                $app->make(UpdateKingdom::class)
            );
        });

        $this->app->bind(MoveUnitsValidator::class, function () {
            return new MoveUnitsValidator;
        });

        $this->app->bind(UnitService::class, function ($app) {
            return new UnitService(
                $app->make(UpdateKingdomHandler::class),
                $app->make(KingdomUnitResourceValidation::class)
            );
        });

        $this->app->bind(KingdomService::class, function ($app) {
            return new KingdomService(
                $app->make(UpdateKingdomHandler::class),
            );
        });

        $this->app->bind(UnitRecallService::class, function ($app) {
            return new UnitRecallService;
        });

        $this->app->bind(TooMuchPopulationHandler::class, function () {
            return new TooMuchPopulationHandler;
        });

        $this->app->bind(KingdomUpdateService::class, function ($app) {
            return new KingdomUpdateService(
                $app->make(GiveKingdomsToNpcHandler::class),
                $app->make(TooMuchPopulationHandler::class),
                $app->make(UpdateKingdom::class)
            );
        });

        $this->app->bind(SelectedKingdom::class, function () {
            return new SelectedKingdom;
        });

        $this->app->bind(KingdomTransformer::class, function () {
            return new KingdomTransformer;
        });

        $this->app->bind(AttackWithItemsService::class, function ($app) {
            return new AttackWithItemsService(
                $app->make(UpdateKingdom::class),
            );
        });

        $this->app->bind(UnitReturnService::class, function ($app) {
            return new UnitReturnService;
        });

        $this->app->bind(KingdomAttackService::class, function ($app) {
            return new KingdomAttackService(
                $app->make(SelectedKingdom::class),
                $app->make(Manager::class),
                $app->make(KingdomTransformer::class)
            );
        });

        $this->app->bind(GiveKingdomsToNpcHandler::class, function ($app) {
            return new GiveKingdomsToNpcHandler($app->make(LocationService::class));
        });

        $this->app->bind(PurchasePeopleService::class, function ($app) {
            return new PurchasePeopleService($app->make(UpdateKingdom::class));
        });

        $this->app->bind(AbandonKingdomService::class, function ($app) {
            return new AbandonKingdomService(
                $app->make(UpdateKingdom::class),
                $app->make(GiveKingdomsToNpcHandler::class),
            );
        });

        $this->app->bind(UpdateKingdom::class, function ($app) {
            return new UpdateKingdom(
                $app->make(KingdomTransformer::class),
                $app->make(KingdomTableTransformer::class),
                $app->make(KingdomAttackLogsTransformer::class),
                $app->make(Manager::class)
            );
        });

        $this->app->bind(KingdomAttackService::class, function ($app) {
            return new KingdomAttackService(
                $app->make(UnitMovementService::class),
                $app->make(MoveUnitsValidator::class),
                $app->make(UpdateKingdom::class)
            );
        });

        $this->app->bind(KingdomSiegeHandler::class, function () {
            return new KingdomSiegeHandler;
        });

        $this->app->bind(DefenderSiegeHandler::class, function () {
            return new DefenderSiegeHandler;
        });

        $this->app->bind(DefenderArcherHandler::class, function () {
            return new DefenderArcherHandler;
        });

        $this->app->bind(KingdomUnitHandler::class, function ($app) {
            return new KingdomUnitHandler(
                $app->make(DefenderSiegeHandler::class),
                $app->make(DefenderArcherHandler::class),
            );
        });

        $this->app->bind(AttackLogHandler::class, function ($app) {
            return new AttackLogHandler(
                $app->make(UpdateKingdom::class)
            );
        });

        $this->app->bind(ReturnSurvivingUnitHandler::class, function ($app) {
            return new ReturnSurvivingUnitHandler($app->make(UnitMovementService::class));
        });

        $this->app->bind(SettlerHandler::class, function () {
            return new SettlerHandler;
        });

        $this->app->bind(AttackKingdomWithUnitsHandler::class, function ($app) {
            return new AttackKingdomWithUnitsHandler(
                $app->make(KingdomSiegeHandler::class),
                $app->make(KingdomUnitHandler::class),
                $app->make(KingdomAirshipHandler::class),
                $app->make(SettlerHandler::class),
                $app->make(AttackLogHandler::class),
                $app->make(ReturnSurvivingUnitHandler::class),
            );
        });

        $this->app->bind(ExpandResourceBuildingService::class, function ($app) {
            return new ExpandResourceBuildingService($app->make(UpdateKingdom::class));
        });

        $this->app->bind(KingdomQueueService::class, function ($app) {
            return new KingdomQueueService(
                $app->make(Manager::class),
                $app->make(UnitMovementTransformer::class)
            );
        });

        $this->commands([
            DeleteKingdomLogs::class,
            UpdateKingdoms::class,
            ResetCapitalCityWalkingStatus::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $router = $this->app['router'];

        $router->aliasMiddleware('character.owns.kingdom', DoesKingdomBelongToAuthorizedUser::class);
    }
}
