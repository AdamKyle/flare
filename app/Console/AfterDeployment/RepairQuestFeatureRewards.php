<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\Quest;
use App\Flare\Models\QuestsCompleted;
use App\Game\Core\Values\FeatureType;
use Illuminate\Console\Command;

class RepairQuestFeatureRewards extends Command
{
    protected $signature = 'repair:quest-feature-rewards';

    protected $description = 'Idempotently repairs missing completed quest feature rewards for existing characters.';

    public function handle(): void
    {
        $this->repairExtraInventorySets();
        $this->repairExtendedBackpack();
        $this->reportQuestLogBasedUnlocks();
    }

    private function repairExtraInventorySets(): void
    {
        $questIds = Quest::where('unlocks_feature', FeatureType::EXTEND_SETS->value)->pluck('id');

        if ($questIds->isEmpty()) {
            $this->line('extra_inventory_sets: no quests found, skipped 0, repaired 0');

            return;
        }

        $characterIds = QuestsCompleted::whereIn('quest_id', $questIds)
            ->pluck('character_id')
            ->unique();

        $repaired = 0;
        $skipped = 0;

        foreach ($characterIds as $characterId) {
            $character = Character::find($characterId);

            if (is_null($character)) {
                continue;
            }

            $setCount = $character->inventorySets()->count();

            if ($setCount >= 20) {
                $skipped++;

                continue;
            }

            $missing = 20 - $setCount;

            for ($i = 0; $i < $missing; $i++) {
                $character->inventorySets()->create([
                    'character_id' => $character->id,
                    'can_be_equipped' => true,
                ]);
            }

            $repaired++;
        }

        $this->line("extra_inventory_sets: skipped {$skipped}, repaired {$repaired}");
    }

    private function repairExtendedBackpack(): void
    {
        $questIds = Quest::where('unlocks_feature', FeatureType::EXTENDED_BACKPACK->value)->pluck('id');

        if ($questIds->isEmpty()) {
            $this->line('extended_backpack: no quests found, skipped 0, repaired 0');

            return;
        }

        $characterIds = QuestsCompleted::whereIn('quest_id', $questIds)
            ->pluck('character_id')
            ->unique();

        $repaired = 0;
        $skipped = 0;

        foreach ($characterIds as $characterId) {
            $character = Character::find($characterId);

            if (is_null($character)) {
                continue;
            }

            if ($character->inventory_max >= 150) {
                $skipped++;

                continue;
            }

            $character->update(['inventory_max' => 150]);
            $repaired++;
        }

        $this->line("extended_backpack: skipped {$skipped}, repaired {$repaired}");
    }

    private function reportQuestLogBasedUnlocks(): void
    {
        $questLogBasedFeatures = [
            FeatureType::REINCARNATION->value => 'reincarnation',
            FeatureType::COSMETIC_TEXT->value => 'cosmetic_text',
            FeatureType::COSMETIC_NAME_TAGS->value => 'cosmetic_name_tags',
            FeatureType::COSMETIC_RACE_CHANGER->value => 'cosmetic_race_changer',
            FeatureType::CAPITAL_CITIES->value => 'capital_cities',
            FeatureType::CAPITAL_CITY_GOLD_BARS->value => 'capital_city_gold_bars',
        ];

        foreach ($questLogBasedFeatures as $featureType => $label) {
            $questIds = Quest::where('unlocks_feature', $featureType)->pluck('id');
            $completedQuestCount = QuestsCompleted::whereIn('quest_id', $questIds)->count();

            $this->line("{$label}: access is quest-log based, skipped {$completedQuestCount}");
        }
    }
}
