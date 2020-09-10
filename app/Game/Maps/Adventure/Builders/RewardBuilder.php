<?php

namespace App\Game\Maps\Adventure\Builders;

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

    public function fetchXPReward(Monster $monster, int $characterLevel, float $xpReduction = 0.0) {
        return XPCalculator::fetchXPFromMonster($monster, $characterLevel, $xpReduction);
    }

    public function fetchSkillXPReward(Skill $skill, Adventure $adventure) {
        return SkillXPCalculator::fetchSkillXP($skill, $adventure);
    }

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

    public function fetchGoldRush(Monster $monster, Character $character, Adventure $adventure) {
        $lootingChance = $character->skills->where('name', '=', 'Looting')->first()->skill_bonus;

        $hasGoldRush = GoldRushCheckCalculator::fetchGoldRushChance($monster, $lootingChance, $adventure);

        if ($hasGoldRush) {
            return $monster->gold + rand(0, 10000);
        }
        
        return $monster->gold;
    }
}