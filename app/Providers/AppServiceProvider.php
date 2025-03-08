<?php

namespace App\Providers;


use App\Console\AfterDeployment\ChangeCharacterReincarnationXpPenalty;
use App\Console\AfterDeployment\CleanInvalidWeapons;
use App\Console\AfterDeployment\CleanMarketPlaceOfInvalidWeapons;
use App\Console\AfterDeployment\UpdateWeaponsAndArmourWithNewStats;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use App\Console\AfterDeployment\ChangePlayerWeapons;
use App\Console\AfterDeployment\ClearPlayersKingdomJobs;
use App\Console\AfterDeployment\UpdateCharacterFactionBounties;
use App\Console\AfterDeployment\AddHolyStacksToItems;
use App\Console\AfterDeployment\AllowTraverseForMaps;
use App\Console\AfterDeployment\AssignNewBuildingsToExistingKingdoms;
use App\Console\AfterDeployment\AssignNewNpcsToFactionLoyalty;
use App\Console\AfterDeployment\ChangeFeatureTypesOnQuests;
use App\Console\AfterDeployment\ChangeLegendaryItems;
use App\Console\AfterDeployment\ClearInvalidCapitalCityQueues;
use App\Console\AfterDeployment\CreateMonsterCache;
use App\Console\AfterDeployment\ReBalanceMonsters;
use App\Console\AfterDeployment\RebalanceStatBasedAffixes;
use App\Console\AfterDeployment\RemoveInvalidQuestItems;
use App\Console\AfterDeployment\UpdateCharactersForClassRanks;
use App\Console\AfterDeployment\RebalanceBaseModifierAffixes;
use App\Console\AfterDeployment\RebalanceIrresistableDamageAffixes;
use App\Console\AfterDeployment\RebalanceMythicalItems;
use App\Console\AfterDeployment\RebalanceSkillReductionAffixes;
use App\Console\AfterDeployment\RebalanceStatReducingAffixes;
use App\Console\AfterDeployment\RebalanceTrinkets;
use App\Console\AfterDeployment\ReduceCharacterBaseModifiersForReincarnation;
use App\Console\AfterDeployment\ClearPlayersJobs;
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
use App\Console\DevelopmentCommands\TestExploration;
use App\Console\DevelopmentCommands\UpdateUsersForDevelopment;

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
            ChangeFeatureTypesOnQuests::class,
            AllowTraverseForMaps::class,
            AssignNewBuildingsToExistingKingdoms::class,
            AssignNewNpcsToFactionLoyalty::class,
            ManageKingdomResources::class,
            ReBalanceMonsters::class,
            RebalanceStatBasedAffixes::class,
            RebalanceBaseModifierAffixes::class,
            ReduceCharacterBaseModifiersForReincarnation::class,
            ChangeLegendaryItems::class,
            RebalanceMythicalItems::class,
            RebalanceStatReducingAffixes::class,
            RebalanceSkillReductionAffixes::class,
            RebalanceIrresistableDamageAffixes::class,
            RebalanceTrinkets::class,
            ClearPlayersJobs::class,
            ClearPlayersKingdomJobs::class,
            ClearInvalidCapitalCityQueues::class,
            UpdateCharacterFactionBounties::class,
            ChangePlayerWeapons::class,
            CleanInvalidWeapons::class,
            CleanMarketPlaceOfInvalidWeapons::class,
            ChangeCharacterReincarnationXpPenalty::class,
            UpdateWeaponsAndArmourWithNewStats::class,

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
                'Content-Disposition' => 'attachment; filename=' . $fileName . '.json',
            ];

            return \Response::make($content, 200, $headers);
        });
    }
}
