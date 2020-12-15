<?php

namespace App\Game\Maps\Adventure\Builders;

use App\Flare\Builders\RandomItemDropBuilder;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
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
    public function fetchDrops(Monster $monster, Character $character, Adventure $adventure) {
        $lootingChance = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;

        $hasDrop = DropCheckCalculator::fetchDropCheckChance($monster, $lootingChance, $adventure);

        if ($hasDrop) {
            return resolve(RandomItemDropBuilder::class)
                        ->setItemAffixes(ItemAffix::all())
                        ->generateItem($character);
        }

        return null;
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
    public function fetchGoldRush(Monster $monster, Character $character, Adventure $adventure): int {

        $lootingChance = $character->skills->where('name', 'Looting')->first()->skill_bonus;
       
        $hasGoldRush = GoldRushCheckCalculator::fetchGoldRushChance($monster, $lootingChance, $adventure);

        if ($hasGoldRush) {
            return $monster->gold + rand(0, 1000);
        }
        
        return $monster->gold;
    }
}