<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Game\Core\Values\LevelUpValue;

class CharacterService
{
    /**
     * Level up the character.
     */
    public function levelUpCharacter(Character $character, int $leftOverXP): void
    {
        $character->update(resolve(LevelUpValue::class)->createValueObject($character, $leftOverXP));

        $character = $character->refresh();

        $characterXp = $this->getXPForNextLevel($character->level + 1);

        $character->update([
            'xp_next' => $characterXp + $characterXp * $character->xp_penalty,
        ]);
    }

    /**
     * Get next level XP requirement.
     */
    protected function getXPForNextLevel(int $nextLevel): int
    {
        if ($nextLevel <= 1000) {
            return 100;
        }

        if ($nextLevel >= 5000) {
            return 35000;
        }

        $startLevel = 1001;
        $endLevel = 5000;
        $startXP = 1000;
        $maxXP = 35000;
        $progress = ($nextLevel - $startLevel) / ($endLevel - $startLevel);

        return (int) ($startXP + (($maxXP - $startXP) * pow($progress, 3)));
    }
}
