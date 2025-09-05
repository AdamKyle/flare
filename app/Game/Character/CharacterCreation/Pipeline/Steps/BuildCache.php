<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Closure;

class BuildCache
{

    /**
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
     */
    public function __construct(private readonly  BuildCharacterAttackTypes $buildCharacterAttackTypes)
    {}

    /**
     * Build the character's attack-type cache.
     *
     * @param CharacterBuildState $state
     * @param Closure $next
     * @return CharacterBuildState
     * @throws \Exception
     */
    public function process(CharacterBuildState $state, Closure $next): CharacterBuildState
    {
        $character = $state->getCharacter();

        if ($character !== null) {
            $this->buildCharacterAttackTypes->buildCache($character);
        }

        return $next($state);
    }
}
