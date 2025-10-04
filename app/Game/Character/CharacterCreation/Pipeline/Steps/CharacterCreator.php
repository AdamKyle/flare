<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Flare\Values\BaseStatValue;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Closure;

class CharacterCreator
{
    private BaseStatValue $baseStatValue;

    public function __construct(BaseStatValue $baseStatValue)
    {
        $this->baseStatValue = $baseStatValue;
    }

    /**
     * Create the Character with base stats and store it on the state.
     */
    public function process(CharacterBuildState $state, Closure $next): CharacterBuildState
    {
        $user = $state->getUser();
        $race = $state->getRace();
        $class = $state->getClass();

        if ($user === null || $race === null || $class === null) {
            return $next($state);
        }

        $baseStat = $this->baseStatValue->setRace($race)->setClass($class);

        $name = $state->getCharacterName() ?? 'Adventurer';

        $character = Character::create([
            'user_id' => $user->id,
            'game_race_id' => $race->id,
            'game_class_id' => $class->id,
            'name' => $name,
            'damage_stat' => $class->damage_stat,
            'xp' => 0,
            'xp_next' => 100,
            'str' => $baseStat->str(),
            'dur' => $baseStat->dur(),
            'dex' => $baseStat->dex(),
            'chr' => $baseStat->chr(),
            'int' => $baseStat->int(),
            'agi' => $baseStat->agi(),
            'focus' => $baseStat->focus(),
            'ac' => $baseStat->ac(),
            'gold' => 1000,
        ]);

        $state->setCharacter($character);

        return $next($state);
    }
}
