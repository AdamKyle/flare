<?php

namespace App\Flare\Services;

use App\Flare\Models\Character;
use App\Flare\Models\MaxLevelConfiguration;
use App\Flare\Values\ItemEffectsValue;
use App\Game\Battle\Values\MaxLevel;

class CharacterXPService {

    public function determineXPToAward(Character $character, int $xp): int {

        if ($xp === 0) {
            return 0;
        }

        $slot = $character->inventory->slots->filter(function($slot) {
            return $slot->item->type === 'quest' && $slot->item->effect === ItemEffectsValue::CONTNUE_LEVELING;
        })->first();

        if (!is_null($slot)) {
            $config = MaxLevelConfiguration::first();

            if (is_null($config)) {
                return (new MaxLevel($character->level, $xp))->fetchXP();
            }

            if ($this->isCharacterHalfWay($character->level, $config->half_way, $config->three_quarters)) {
                return ceil($xp * MaxLevel::HALF_PERCENT);
            }

            if ($this->isCharacterThreeQuarters($character->level, $config->three_quarters, $config->last_leg)) {
                return ceil($xp * MaxLevel::THREE_QUARTERS_PERCENT);
            }

            if ($this->isCharacterAtLastLeg($character->level, $config->last_leg, $config->max_level)) {
                return ceil($xp * MaxLevel::LAST_LEG_PERCENT);
            }

            if ($character->level === $config->max_level) {
                return 0;
            } else {
                return $xp;
            }
        }

        return (new MaxLevel($character->level, $xp))->fetchXP();
    }

    public function isCharacterHalfWay(int $characterLevel, int $halfWay, int $threeQuarters): bool {
        $halfWay       = MaxLevelConfiguration::first()->half_way;
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;

        return $characterLevel >= $halfWay && $characterLevel < $threeQuarters;
    }

    public function isCharacterThreeQuarters(int $characterLevel, int $threeQuarters, int $lastLeg): bool {
        $threeQuarters = MaxLevelConfiguration::first()->three_quarters;
        $lastLeg       = MaxLevelConfiguration::first()->last_leg;

        return $characterLevel >= $threeQuarters && $characterLevel < $lastLeg;
    }

    public function isCharacterAtLastLeg(int $characterLevel, int $lastLeg, int $maxLevel): bool {
        $lastLeg  = MaxLevelConfiguration::first()->last_leg;
        $maxLevel = MaxLevelConfiguration::first()->max_level;

        return $characterLevel >= $lastLeg && $characterLevel < $maxLevel;
    }
}