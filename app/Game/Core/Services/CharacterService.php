<?php

namespace App\Game\Core\Services;

use App;
use App\Flare\Models\Character;
use App\Game\Core\Values\LevelUpValue;

class CharacterService
{
    /**
     * Level up the character.
     *
     * @param Character $character
     * @param int $leftOverXP
     * @return void
     */
    public function levelUpCharacter(Character $character, int $leftOverXP): void
    {
        $character->update(resolve(LevelUpValue::class)->createValueObject($character, $leftOverXP));

        $character = $character->refresh();

        $character->update([
            'xp_next' => $this->getXPForNextLevel($character->level + 1),
        ]);
    }

    /**
     * Get next level XP requirement.
     *
     * @param int $nextLevel
     * @return int
     */
    protected function getXPForNextLevel(int $nextLevel): int {
        if ($nextLevel > 1000) {
            return (($nextLevel - 1000) * 10) + 100;
        }

        return 100;
    }
}
