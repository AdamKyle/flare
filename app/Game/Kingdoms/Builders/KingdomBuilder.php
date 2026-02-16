<?php

namespace App\Game\Kingdoms\Builders;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;

class KingdomBuilder
{
    /**
     * Creates the kingdom
     */
    public function createKingdom(Character $character, string $name, string $color): Kingdom
    {

        $isOnIcePlane = $character->map->gameMap->mapType()->isTheIcePlane();
        $characterKingdomCount = $character->kingdoms()->count();
        $skill = $character->passiveSkills->where('passiveSkill.effect_type', PassiveSkillTypeValue::RESOURCE_INCREASE)->first();
        $protectedUntil = null;

        if (! $isOnIcePlane && $characterKingdomCount === 0) {
            $protectedUntil = now()->addDays(7);
        }

        if ($isOnIcePlane && $character->kingdoms()->count() === 0) {
            event(new ServerMessageEvent($character->user, 'Kingdoms settled on The Ice Plane do not have protection. Keep that in mind child!'));
        }

        $kingdom = [
            'name' => $name,
            'color' => $color,
            'character_id' => $character->id,
            'game_map_id' => $character->map->gameMap->id,
            'max_stone' => 2000 + $skill->resource_increase_amount,
            'max_wood' => 2000 + $skill->resource_increase_amount,
            'max_clay' => 2000 + $skill->resource_increase_amount,
            'max_iron' => 2000 + $skill->resource_increase_amount,
            'max_steel' => 31000,
            'current_stone' => 2000,
            'current_wood' => 2000,
            'current_clay' => 2000,
            'current_iron' => 2000,
            'current_steel' => 0,
            'current_population' => 100,
            'max_population' => 100 + $skill->resource_increase_amount,
            'current_morale' => .50,
            'max_morale' => 1.0,
            'treasury' => 0,
            'x_position' => $character->map->character_position_x,
            'y_position' => $character->map->character_position_y,
            'last_walked' => now(),
            'protected_until' => $protectedUntil,
        ];

        return Kingdom::create($kingdom);
    }
}
