<?php

namespace App\Providers;

use App\Console\AfterDeployment\AddHolyStacksToItems;
use App\Console\AfterDeployment\AssignNewBuildingsToExistingKingdoms;
use App\Console\AfterDeployment\AssignNewNpcsToFactionLoyalty;
use App\Console\AfterDeployment\CleanInvalidWeapons;
use App\Console\AfterDeployment\CleanMarketPlaceOfInvalidWeapons;
use App\Console\AfterDeployment\ClearInvalidCapitalCityQueues;
use App\Console\AfterDeployment\CreateQuestChainRelationships;
use App\Console\AfterDeployment\RemoveInvalidQuestItems;
use App\Console\AfterDeployment\UpdateCharactersForClassRanks;
use App\Console\DevelopmentCommands\AssignTopEndGearToPlayer;
use App\Console\DevelopmentCommands\CompleteGuideQuestForCharacter;
use App\Console\DevelopmentCommands\CreateCharacter;
use App\Console\DevelopmentCommands\CreateEventsForDevelopment;
use App\Console\DevelopmentCommands\CreateTestCharacters;
use App\Console\DevelopmentCommands\GivePlayerAncenstorItem;
use App\Console\DevelopmentCommands\GivePlayerUniqueItem;
use App\Console\DevelopmentCommands\LevelCharacter;
use App\Console\DevelopmentCommands\ManageKingdomResources;
use App\Console\DevelopmentCommands\MaxOutCharacter;
use App\Console\DevelopmentCommands\MaxOutCharactersPassiveSkills;
use App\Console\DevelopmentCommands\ReincarnateCharacter;
use App\Console\DevelopmentCommands\SeedMarketHistoryForItemType;
use App\Console\DevelopmentCommands\TestExploration;
use App\Console\DevelopmentCommands\UpdateUsersForDevelopment;
use App\Game\Monsters\Console\Commands\CreateMonsterCache;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {

        // Register development based commands.
        $this->commands([
            // After Deployment Commands
            AddHolyStacksToItems::class,
            CreateMonsterCache::class,
            UpdateCharactersForClassRanks::class,
            RemoveInvalidQuestItems::class,
            AssignNewBuildingsToExistingKingdoms::class,
            AssignNewNpcsToFactionLoyalty::class,
            ManageKingdomResources::class,
            ClearInvalidCapitalCityQueues::class,
            CleanInvalidWeapons::class,
            CleanMarketPlaceOfInvalidWeapons::class,
            CreateQuestChainRelationships::class,

            // Development Commands:
            CreateCharacter::class,
            CreateTestCharacters::class,
            MaxOutCharacter::class,
            UpdateUsersForDevelopment::class,
            LevelCharacter::class,
            AssignTopEndGearToPlayer::class,
            ReincarnateCharacter::class,
            GivePlayerUniqueItem::class,
            GivePlayerAncenstorItem::class,
            TestExploration::class,
            CompleteGuideQuestForCharacter::class,
            MaxOutCharactersPassiveSkills::class,
            CreateEventsForDevelopment::class,
            SeedMarketHistoryForItemType::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        Blade::componentNamespace('App\\View\\Components', 'core');

        if ($this->app->environment('local')) {
            Mail::alwaysTo(env('DEFAULT_LOCAL_EMAIL'));
        }

        \Response::macro('attachment', function ($content, $fileName) {

            $headers = [
                'Content-type' => 'text/json',
                'Content-Disposition' => 'attachment; filename='.$fileName.'.json',
            ];

            return \Response::make($content, 200, $headers);
        });
    }
}
