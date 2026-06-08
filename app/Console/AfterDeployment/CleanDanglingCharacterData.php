<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\BuildingExpansionQueue;
use App\Flare\Models\CapitalCityBuildingCancellation;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitCancellation;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\CharacterBoon;
use App\Flare\Models\CharacterClassRank;
use App\Flare\Models\CharacterClassSpecialtiesEquipped;
use App\Flare\Models\CharacterInCelestialFight;
use App\Flare\Models\CharacterPassiveSkill;
use App\Flare\Models\DelveExploration;
use App\Flare\Models\DelveLog;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Models\GemBag;
use App\Flare\Models\GlobalEventCraft;
use App\Flare\Models\GlobalEventCraftingInventory;
use App\Flare\Models\GlobalEventEnchant;
use App\Flare\Models\GlobalEventKill;
use App\Flare\Models\GlobalEventParticipation;
use App\Flare\Models\Inventory;
use App\Flare\Models\InventorySet;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\Map;
use App\Flare\Models\MarketBoard;
use App\Flare\Models\QuestsCompleted;
use App\Flare\Models\RaidBossParticipation;
use App\Flare\Models\Skill;
use App\Flare\Models\SmeltingProgress;
use App\Flare\Models\SuggestionAndBugs;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Models\User;
use App\Flare\Models\UserLoginDuration;
use App\Flare\Models\WeeklyMonsterFight;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CleanDanglingCharacterData extends Command
{
    protected $signature = 'cleanup:dangling-character-data {--apply : Apply the cleanup (default is dry-run)}';

    protected $description = 'Finds and cleans dangling records caused by old bad account/character deletion behavior.';

    public function handle(): void
    {
        $apply = $this->option('apply');

        if (! $apply) {
            $this->info('[DRY RUN] Pass --apply to mutate data.');
            $this->newLine();
        }

        $existingCharacterIds = Character::pluck('id')->toArray();
        $existingUserIds = User::pluck('id')->toArray();
        $existingCharacterUserIds = Character::pluck('user_id')->toArray();

        $this->cleanCharacterOwnedTables($existingCharacterIds, $apply);
        $this->cleanUserOwnedTables($existingUserIds, $existingCharacterUserIds, $apply);
        $this->cleanOrphanedChildData($apply);
        $this->nullSuggestionAndBugsForMissingCharacters($existingCharacterIds, $apply);
    }

    private function cleanCharacterOwnedTables(array $existingCharacterIds, bool $apply): void
    {
        $characterOwnedModels = [
            CharacterAutomation::class,
            CharacterBoon::class,
            CharacterClassRank::class,
            CharacterClassSpecialtiesEquipped::class,
            CharacterInCelestialFight::class,
            CharacterPassiveSkill::class,
            DelveExploration::class,
            ExplorationLog::class,
            ExplorationWarning::class,
            Faction::class,
            FactionLoyalty::class,
            FactionLoyaltyAutomation::class,
            FactionLoyaltyAutomationWarning::class,
            GemBag::class,
            GlobalEventCraft::class,
            GlobalEventCraftingInventory::class,
            GlobalEventEnchant::class,
            GlobalEventKill::class,
            GlobalEventParticipation::class,
            Inventory::class,
            InventorySet::class,
            KingdomLog::class,
            Map::class,
            MarketBoard::class,
            QuestsCompleted::class,
            RaidBossParticipation::class,
            Skill::class,
            SmeltingProgress::class,
            UnitMovementQueue::class,
            WeeklyMonsterFight::class,
            BuildingExpansionQueue::class,
            CapitalCityBuildingCancellation::class,
            CapitalCityBuildingQueue::class,
            CapitalCityUnitCancellation::class,
            CapitalCityUnitQueue::class,
        ];

        foreach ($characterOwnedModels as $modelClass) {
            $this->cleanOrphanedRows($modelClass, 'character_id', $existingCharacterIds, $apply);
        }

        $this->cleanOrphanedDelveLogsAndChildData($existingCharacterIds, $apply);
        $this->cleanOrphanedRaidParticipations($existingCharacterIds, $apply);
    }

    private function cleanUserOwnedTables(array $existingUserIds, array $existingCharacterUserIds, bool $apply): void
    {
        $userIdsWithCharacters = array_intersect($existingUserIds, $existingCharacterUserIds);

        $this->cleanOrphanedRows(UserLoginDuration::class, 'user_id', $userIdsWithCharacters, $apply);
    }

    private function cleanOrphanedChildData(bool $apply): void
    {
        $this->cleanOrphanedChildRows('inventory_slots', 'inventory_id', 'inventories', $apply);
        $this->cleanOrphanedChildRows('set_slots', 'inventory_set_id', 'inventory_sets', $apply);
        $this->cleanOrphanedChildRows('gem_bag_slots', 'gem_bag_id', 'gem_bags', $apply);
        $this->cleanOrphanedChildRows(
            'character_class_ranks_weapon_masteries',
            'character_class_rank_id',
            'character_class_ranks',
            $apply
        );
        $this->cleanOrphanedChildRows('faction_loyalty_npcs', 'faction_loyalty_id', 'faction_loyalties', $apply);
        $this->cleanOrphanedChildRows(
            'faction_loyalty_npc_tasks',
            'faction_loyalty_id',
            'faction_loyalties',
            $apply
        );
        $this->cleanOrphanedChildRows(
            'faction_loyalty_npc_tasks',
            'faction_loyalty_npc_id',
            'faction_loyalty_npcs',
            $apply
        );
    }

    private function nullSuggestionAndBugsForMissingCharacters(array $existingCharacterIds, bool $apply): void
    {
        $orphanCount = SuggestionAndBugs::whereNotNull('character_id')
            ->whereNotIn('character_id', $existingCharacterIds)
            ->count();

        $label = (new SuggestionAndBugs)->getTable().'.character_id → null';

        if ($orphanCount === 0) {
            $this->line(sprintf('  %-60s %d', $label, 0));

            return;
        }

        $this->line(sprintf('  %-60s %d', $label, $orphanCount));

        if ($apply) {
            SuggestionAndBugs::whereNotNull('character_id')
                ->whereNotIn('character_id', $existingCharacterIds)
                ->update(['character_id' => null]);
        }
    }

    private function cleanOrphanedRows(string $modelClass, string $foreignKey, array $existingIds, bool $apply): void
    {
        /** @var Model $model */
        $model = new $modelClass;
        $table = $model->getTable();

        $orphanCount = $modelClass::whereNotIn($foreignKey, $existingIds)->count();

        $label = $table.'.'.$foreignKey;
        $this->line(sprintf('  %-60s %d', $label, $orphanCount));

        if ($apply && $orphanCount > 0) {
            $modelClass::whereNotIn($foreignKey, $existingIds)->delete();
        }
    }

    private function cleanOrphanedChildRows(
        string $table,
        string $foreignKey,
        string $parentTable,
        bool $apply
    ): void {
        $query = DB::table($table)
            ->whereNotExists(function ($query) use ($table, $foreignKey, $parentTable) {
                $query->selectRaw('1')
                    ->from($parentTable)
                    ->whereColumn($parentTable.'.id', $table.'.'.$foreignKey);
            });

        $orphanCount = $query->count();
        $this->line(sprintf('  %-60s %d', $table.'.'.$foreignKey, $orphanCount));

        if ($apply && $orphanCount > 0) {
            $query->delete();
        }
    }

    private function cleanOrphanedDelveLogsAndChildData(array $existingCharacterIds, bool $apply): void
    {
        $orphanCount = DelveLog::whereNotIn('character_id', $existingCharacterIds)->count();
        $this->line(sprintf('  %-60s %d', 'delve_logs.character_id', $orphanCount));

        if ($apply && $orphanCount > 0) {
            DelveLog::whereNotIn('character_id', $existingCharacterIds)->delete();
        }

        $existingExplorationIds = DelveExploration::pluck('id')->toArray();
        $orphanLogsFromMissingExploration = DelveLog::whereNotIn('delve_exploration_id', $existingExplorationIds)->count();
        $this->line(sprintf('  %-60s %d', 'delve_logs.delve_exploration_id', $orphanLogsFromMissingExploration));

        if ($apply && $orphanLogsFromMissingExploration > 0) {
            DelveLog::whereNotIn('delve_exploration_id', $existingExplorationIds)->delete();
        }

        $existingInventoryIds = GlobalEventCraftingInventory::pluck('id')->toArray();
        $orphanSlots = DB::table('global_event_crafting_inventory_slots')
            ->whereNotIn('global_event_crafting_inventory_id', $existingInventoryIds)
            ->count();
        $this->line(sprintf('  %-60s %d', 'global_event_crafting_inventory_slots', $orphanSlots));

        if ($apply && $orphanSlots > 0) {
            DB::table('global_event_crafting_inventory_slots')
                ->whereNotIn('global_event_crafting_inventory_id', $existingInventoryIds)
                ->delete();
        }
    }

    private function cleanOrphanedRaidParticipations(array $existingCharacterIds, bool $apply): void
    {
        $orphanCount = DB::table('raid_participations')
            ->whereNotIn('character_id', $existingCharacterIds)
            ->count();

        $this->line(sprintf('  %-60s %d', 'raid_participations.character_id', $orphanCount));

        if ($apply && $orphanCount > 0) {
            DB::table('raid_participations')
                ->whereNotIn('character_id', $existingCharacterIds)
                ->delete();
        }
    }
}
