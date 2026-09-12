<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Game\Character\CharacterCreation\Calculators\BaseStatCalculator;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Closure;

class CharacterCreator
{
    private BaseStatCalculator $baseStatValue;

    /**
     * @param BaseStatCalculator $baseStatValue Class-based Character base stat calculator.
     */
    public function __construct(BaseStatCalculator $baseStatValue)
    {
        $this->baseStatValue = $baseStatValue;
    }

    /**
     * Create the Character with base stats and store it on the state.
     *
     * @param CharacterBuildState $state Current Character build pipeline state.
     * @param Closure $next Next pipeline stage callback.
     * @return CharacterBuildState Build state carrying the created Character, or unchanged when the state is not yet ready.
     */
    public function process(CharacterBuildState $state, Closure $next): CharacterBuildState
    {
        $user = $state->getUser();
        $race = $state->getRace();
        $class = $state->getClass();

        if ($user === null || $race === null || $class === null) {
            return $next($state);
        }

        $baseStat = $this->baseStatValue->setClass($class);

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
