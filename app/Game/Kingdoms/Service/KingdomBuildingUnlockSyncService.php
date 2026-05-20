<?php

namespace App\Game\Kingdoms\Service;

use App\Flare\Models\Character;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\Kingdom;

class KingdomBuildingUnlockSyncService
{
    public function syncForCharacter(Character $character): void
    {
        foreach ($character->kingdoms as $kingdom) {
            $this->syncForKingdom($kingdom);
        }
    }

    public function syncForKingdom(Kingdom $kingdom): Kingdom
    {
        $character = $kingdom->character;

        if (is_null($character)) {
            return $kingdom->refresh();
        }

        foreach ($kingdom->buildings as $building) {
            $gameBuilding = $building->gameBuilding;

            $passive = $this->passiveForBuilding($character, $gameBuilding);

            if (is_null($passive)) {
                continue;
            }

            if ($passive->current_level >= ($gameBuilding->level_required ?? 1)) {
                $building->update([
                    'is_locked' => false,
                ]);
            }
        }

        return $kingdom->refresh();
    }

    private function passiveForBuilding(Character $character, GameBuilding $gameBuilding)
    {
        if (! is_null($gameBuilding->passive_skill_id)) {
            $passive = $character->passiveSkills()
                ->where('passive_skill_id', $gameBuilding->passive_skill_id)
                ->first();

            if (! is_null($passive)) {
                return $passive;
            }
        }

        return $character->passiveSkills
            ->where('passiveSkill.name', $gameBuilding->name)
            ->first();
    }
}
