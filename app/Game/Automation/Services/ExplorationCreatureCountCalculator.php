<?php

namespace App\Game\Automation\Services;

use App\Flare\Models\Character;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;

class ExplorationCreatureCountCalculator
{
    public function __construct(
        private readonly CharacterStatBuilder $characterStatBuilder
    ) {}

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
