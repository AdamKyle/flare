<?php

namespace App\Game\Automation\Exploration\Services;

use App\Flare\Models\Character;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;

class ExplorationCreatureCountCalculator
{
    /**
     * @param  CharacterStatBuilder  $characterStatBuilder  The character stat builder used to derive the fight timeout modifier.
     */
    public function __construct(
        private readonly CharacterStatBuilder $characterStatBuilder
    ) {}

    /**
     * Calculate how many creatures the character encounters per Exploration round.
     *
     * @param  Character  $character  The character exploring.
     * @return int The number of creatures to encounter this round.
     */
    public function calculate(Character $character): int
    {
        $fightTimeOutModifier = $this->characterStatBuilder
            ->setCharacter($character->refresh())
            ->buildTimeOutModifier('fight_time_out');
        $timeoutSeconds = 10 - (5 * $fightTimeOutModifier);

        if ($timeoutSeconds <= 0) {
            return 12;
        }

        return max(6, min(12, (int) floor(60 / $timeoutSeconds)));
    }
}
