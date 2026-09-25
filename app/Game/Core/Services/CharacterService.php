<?php

namespace App\Game\Core\Services;

use App\Flare\Models\Character;
use App\Game\Core\Values\LevelUpValue;

class CharacterService
{
    /**
     * @param LevelUpValue $levelUpValue
     */
    public function __construct(private readonly LevelUpValue $levelUpValue) {}

    /**
     * Level the Character up once, carrying the left over XP into the new level.
     *
     * @param Character $character
     * @param int $leftOverXP
     * @return void
     */
    public function levelUpCharacter(Character $character, int $leftOverXP): void
    {
        $character->update($this->levelUpValue->createValueObject($character, $leftOverXP));

        $character = $character->refresh();

        $characterXp = $this->getXPForNextLevel($character->level + 1);

        $character->update([
            'xp_next' => $characterXp + $characterXp * $character->xp_penalty,
        ]);
    }

    /**
     * Return the base XP required to reach the given level, before the Character's XP penalty is applied.
     *
     * @param int $nextLevel
     * @return int
     */
    public function getXPForNextLevel(int $nextLevel): int
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
        $levelRange = $endLevel - $startLevel;
        $levelOffset = $nextLevel - $startLevel;
        $xpRange = $maxXP - $startXP;

        return $startXP + intdiv($xpRange * ($levelOffset ** 3), $levelRange ** 3);
    }
}
