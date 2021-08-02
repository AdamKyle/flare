<?php

namespace App\Game\Adventures\Builders;

use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\ItemAffix;
use App\Flare\Models\Monster;
use App\Flare\Models\Skill;
use Facades\App\Flare\Calculators\XPCalculator;
use Facades\App\Flare\Calculators\SkillXPCalculator;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\Calculators\GoldRushCheckCalculator;

class RewardBuilder {

    /**
     * Fetch the Xp Reward
     *
     * @param Monster $monster
     * @param int $characterLevel
     * @param float $xpReduction | 0.0
     */
    public function fetchXPReward(Monster $monster, int $characterLevel, float $xpReduction = 0.0) {
        return XPCalculator::fetchXPFromMonster($monster, $characterLevel, $xpReduction);
    }

    /**
     * Fetch the skill xp reward
     *
     * @param Skill $skill
     * @param Adventure $adventure
     */
    public function fetchSkillXPReward(Skill $skill, Adventure $adventure) {
        return SkillXPCalculator::fetchSkillXP($skill, $adventure);
    }

    /**
     * Fetch the drops.
     *
     * @param Monster $monster
     * @param Character $character
     * @param Adventure $adventure
     * @return mixed Item | null
     */
    public function fetchDrops(Monster $monster, Character $character, Adventure $adventure, float $gameMapBonus) {
        $lootingChance = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;

        $hasDrop = DropCheckCalculator::fetchDropCheckChance($monster, $lootingChance, $gameMapBonus, $adventure);

        if ($hasDrop) {
            return resolve(RandomItemDropBuilder::class)
                        ->setItemAffixes(ItemAffix::where('can_drop', true)->get())
                        ->generateItem($character);
        }

        return null;
    }

    /**
     * Fetches the quest drop from a monnster.
     *
     * @param Monster $monster
     * @param Character $character
     * @param Adventure $adventure
     * @param array $rewards
     * @return mixed|null
     */
    public function fetchQuestItemFromMonster(Monster $monster, Character $character, Adventure $adventure, array $rewards, float $gameMapBonus) {
        if (!is_null($monster->questItem)) {
            $lootingChance = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;

            $hasDrop = DropCheckCalculator::fetchQuestItemDropCheck($monster, $lootingChance, $gameMapBonus, $adventure);

            $hasItem = $character->inventory->slots->filter(function($slot) use ($monster) {
                return $slot->item_id === $monster->questItem->id;
            })->all();

            if ($hasDrop && empty($hasItem) && $this->questItemNotInRewards($monster->questItem->id, $rewards['items'])) {
                return $monster->questItem;
            }

            return null;
        }
    }

    /**
     * Fetches a gold rush.
     *
     * If a gold rush is not possible, we return the monsters gold.
     *
     * @param Monster $monster
     * @param Character $character
     * @param Adventure $adventure
     * @return int
     */
    public function fetchGoldRush(Monster $monster, Character $character, Adventure $adventure, float $gameMapBonus = 0.0): int {

        $lootingChance = $character->skills->where('name', 'Looting')->first()->skill_bonus;

        $hasGoldRush = GoldRushCheckCalculator::fetchGoldRushChance($monster, $lootingChance, $gameMapBonus, $adventure);

        if ($hasGoldRush) {
            return $monster->gold + rand(0, 1000);
        }

        return $monster->gold;
    }

    /**
     * Make sure the quest item is not already in the list of item rewards.
     *
     * @param int $id
     * @param array $rewardItems
     * @return bool
     */
    protected function questItemNotInRewards(int $id, array $rewardItems): bool {
        if (empty($rewardItems)) {
             return true;
        }

        $has = false;

        foreach ($rewardItems as $item) {

            if ($item['id'] === $id) {
                $has = true;
            }
        }

        return $has;
    }
}
