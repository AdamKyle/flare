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
        if ($nextLevel > 999) {
            $xpAtLevel1000 = 1000;

            $baseXPFactor = (2000 - $xpAtLevel1000) / pow(1000, 3);

            $xpRequired = $xpAtLevel1000 + $baseXPFactor * pow(($nextLevel - 1000), 3);
            $xpRequired = min($xpRequired, 1000000);

            return (int) $xpRequired;
        }

        return 100;
    }
}
