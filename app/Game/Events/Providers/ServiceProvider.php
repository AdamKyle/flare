<?php

namespace App\Game\Events\Providers;

use App\Flare\Services\CreateSurveySnapshot;
use App\Flare\Services\EventSchedulerService;
use App\Game\Events\Console\Commands\EndScheduledEvent;
use App\Game\Events\Console\Commands\ProcessScheduledEvents;
use App\Game\Events\Console\Commands\RestartGlobalEventGoal;
use App\Game\Events\Registry\EventEnderRegistry;
use App\Game\Events\Services\AnnouncementCleanupService;
use App\Game\Events\Services\DelusionalMemoriesEventEnderService;
use App\Game\Events\Services\EventGoalsService;
use App\Game\Events\Services\FactionLoyaltyPledgeCleanupService;
use App\Game\Events\Services\FeedbackEventEnderService;
use App\Game\Events\Services\GlobalEventGoalCleanupService;
use App\Game\Events\Services\KingdomEventService;
use App\Game\Events\Services\MoveCharacterAfterEventService;
use App\Game\Events\Services\RaidEventEnderService;
use App\Game\Events\Services\WeeklyCelestialEventEnderService;
use App\Game\Events\Services\WeeklyCurrencyEventEnderService;
use App\Game\Events\Services\WeeklyFactionLoyaltyEnderService;
use App\Game\Events\Services\WinterEventEnderService;
use App\Game\Exploration\Services\ExplorationAutomationService;
use App\Game\Factions\FactionLoyalty\Services\FactionLoyaltyService;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\TraverseService;
use App\Game\Maps\Services\UpdateRaidMonsters;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;
use Tests\Unit\Game\Events\Services\ScheduleEventFinalizerService;

class ServiceProvider extends ApplicationServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        $this->commands([
            EndScheduledEvent::class,
            ProcessScheduledEvents::class,
            RestartGlobalEventGoal::class,
        ]);

        $this->app->bind(EventGoalsService::class, function () {
            return new EventGoalsService;
        });

        $this->app->bind(KingdomEventService::class, function () {
            return new KingdomEventService;
        });

        $this->app->bind(AnnouncementCleanupService::class, function () {
            return new AnnouncementCleanupService;
        });

        $this->app->bind(FactionLoyaltyPledgeCleanupService::class, function ($app) {
            return new FactionLoyaltyPledgeCleanupService(
                $app->make(FactionLoyaltyService::class)
            );
        });

        $this->app->bind(FeedbackEventEnderService::class, function ($app) {
            return new FeedbackEventEnderService(
                $app->make(CreateSurveySnapshot::class),
                $app->make(AnnouncementCleanupService::class),
            );
        });

        $this->app->bind(GlobalEventGoalCleanupService::class, function () {
            return new GlobalEventGoalCleanupService;
        });

        $this->app->bind(MoveCharacterAfterEventService::class, function ($app) {
            return new MoveCharacterAfterEventService(
                $app->make(TraverseService::class),
                $app->make(ExplorationAutomationService::class)
            );
        });

        $this->app->bind(RaidEventEnderService::class, function ($app) {
            return new RaidEventEnderService(
                $app->make(LocationService::class),
                $app->make(UpdateRaidMonsters::class),
                $app->make(AnnouncementCleanupService::class),
            );
        });

        $this->app->bind(ScheduleEventFinalizerService::class, function ($app) {
            return new ScheduleEventFinalizerService(
                $app->make(EventSchedulerService::class),
            );
        });

        $this->app->bind(WeeklyCurrencyEventEnderService::class, function ($app) {
            return new WeeklyCurrencyEventEnderService(
                $app->make(AnnouncementCleanupService::class),
            );
        });

        $this->app->bind(WeeklyCelestialEventEnderService::class, function ($app) {
            return new WeeklyCelestialEventEnderService(
                $app->make(AnnouncementCleanupService::class),
            );
        });

        $this->app->bind(WeeklyFactionLoyaltyEnderService::class, function ($app) {
            return new WeeklyFactionLoyaltyEnderService(
                $app->make(AnnouncementCleanupService::class),
            );
        });

        $this->app->bind(WinterEventEnderService::class, function ($app) {
            return new WinterEventEnderService(
                $app->make(KingdomEventService::class),
                $app->make(MoveCharacterAfterEventService::class),
                $app->make(FactionLoyaltyPledgeCleanupService::class),
                $app->make(AnnouncementCleanupService::class),
                $app->make(GlobalEventGoalCleanupService::class),
            );
        });

        $this->app->bind(DelusionalMemoriesEventEnderService::class, function ($app) {
            return new DelusionalMemoriesEventEnderService(
                $app->make(KingdomEventService::class),
                $app->make(MoveCharacterAfterEventService::class),
                $app->make(FactionLoyaltyPledgeCleanupService::class),
                $app->make(AnnouncementCleanupService::class),
                $app->make(GlobalEventGoalCleanupService::class),
            );
        });

        $this->app->bind(EventEnderRegistry::class, function ($app) {
            return new EventEnderRegistry(
                $app->make(RaidEventEnderService::class),
                $app->make(WeeklyCurrencyEventEnderService::class),
                $app->make(WeeklyCelestialEventEnderService::class),
                $app->make(WeeklyFactionLoyaltyEnderService::class),
                $app->make(WinterEventEnderService::class),
                $app->make(DelusionalMemoriesEventEnderService::class),
                $app->make(FeedbackEventEnderService::class),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void {}
}
