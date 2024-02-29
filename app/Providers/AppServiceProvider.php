<?php

namespace App\Providers;

use App\Console\AfterDeployment\AddHolyStacksToItems;
use App\Console\AfterDeployment\AssignNewClassRanksAndMasteriesToCharacters;
use App\Console\AfterDeployment\AssignNewFactionsToCharacters;
use App\Console\AfterDeployment\AssignNewSkillsToPlayers;
use App\Console\AfterDeployment\ChangeDamageAmountOnAffixes;
use App\Console\AfterDeployment\CreateCharacterAttackDataCache;
use App\Console\AfterDeployment\CreateMonsterCache;
use App\Console\AfterDeployment\FillInfoSectionsWithItemTableType;
use App\Console\AfterDeployment\GivePhaseRewardsForCharacters;
use App\Console\AfterDeployment\KickOffEventGoalForWinterEvent;
use App\Console\AfterDeployment\RebalanceQuestCurrencyCostsAndRewards;
use App\Console\AfterDeployment\ReduceAlchemyItemsCost;
use App\Console\AfterDeployment\ReduceUnitQueueAmount;
use App\Console\AfterDeployment\RemoveInvalidQuestItems;
use App\Console\AfterDeployment\UpdateCharacterCurrencies;
use App\Console\AfterDeployment\UpdateCharactersForClassRanks;
use App\Console\DevelopmentCommands\CreateCharacter;
use App\Console\DevelopmentCommands\MaxOutCharactersPassiveSkills;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;
use App\Console\DevelopmentCommands\GivePlayerMythicItem;
use App\Console\DevelopmentCommands\GivePlayerAncenstorItem;
use App\Console\DevelopmentCommands\LevelCharacter;
use App\Console\DevelopmentCommands\MaxOutCharacter;
use App\Console\DevelopmentCommands\CreateTestCharacters;
use App\Console\DevelopmentCommands\ReincarnateCharacter;
use App\Console\DevelopmentCommands\IncreaseRankFightToMax;
use App\Console\DevelopmentCommands\AssignTopEndGearToPlayer;
use App\Console\DevelopmentCommands\CompleteGuideQuestForCharacter;
use App\Console\DevelopmentCommands\GivePlayerUniqueItem;
use App\Console\DevelopmentCommands\UpdateUsersForDevelopment;
use App\Console\DevelopmentCommands\TestExploration;
use Illuminate\Support\Facades\Blade;

class AppServiceProvider extends ServiceProvider {
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void {

        // Register development based commands.
        $this->commands([
            // After Deployment Commands
            AddHolyStacksToItems::class,
            AssignNewSkillsToPlayers::class,
            CreateCharacterAttackDataCache::class,
            CreateMonsterCache::class,
            UpdateCharactersForClassRanks::class,
            ReduceAlchemyItemsCost::class,
            UpdateCharacterCurrencies::class,
            RebalanceQuestCurrencyCostsAndRewards::class,
            KickOffEventGoalForWinterEvent::class,
            GivePhaseRewardsForCharacters::class,
            ChangeDamageAmountOnAffixes::class,
            AssignNewFactionsToCharacters::class,
            RemoveInvalidQuestItems::class,
            ReduceUnitQueueAmount::class,
            FillInfoSectionsWithItemTableType::class,

            // Development Commands:
            CreateCharacter::class,
            CreateTestCharacters::class,
            MaxOutCharacter::class,
            UpdateUsersForDevelopment::class,
            LevelCharacter::class,
            AssignTopEndGearToPlayer::class,
            ReincarnateCharacter::class,
            IncreaseRankFightToMax::class,
            GivePlayerUniqueItem::class,
            GivePlayerAncenstorItem::class,
            GivePlayerMythicItem::class,
            TestExploration::class,
            CompleteGuideQuestForCharacter::class,
            MaxOutCharactersPassiveSkills::class,
        ]);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void {

        Blade::componentNamespace('App\\View\\Components', 'core');

        if ($this->app->environment('local')) {
            Mail::alwaysTo(env('DEFAULT_LOCAL_EMAIL'));
        }

        \Response::macro('attachment', function ($content, $fileName) {

            $headers = [
                'Content-type' => 'text/json',
                'Content-Disposition' => "attachment; filename=" . $fileName . ".json",
            ];

            return \Response::make($content, 200, $headers);
        });
    }
}
