<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
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
     * @param Character $character
     * @return void
     * @throws Exception
     */
    public function process(Character $character): void
    {
        $this->buildCharacterAttackTypes->buildCache($character);
    }
}
