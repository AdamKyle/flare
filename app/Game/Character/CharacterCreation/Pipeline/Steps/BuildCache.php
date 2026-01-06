<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Closure;
use Exception;

class BuildCache
{
    /**
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
     */
    public function __construct(private readonly BuildCharacterAttackTypes $buildCharacterAttackTypes) {}

    /**
     *
     * Build character attack type cache
     *
     * @param CharacterBuildState $state
     * @return void
     * @throws Exception
     */
    public function process(CharacterBuildState $state): void
    {
        $character = $state->getCharacter();

        if ($character !== null) {
            $this->buildCharacterAttackTypes->buildCache($character);
        }
    }
}
