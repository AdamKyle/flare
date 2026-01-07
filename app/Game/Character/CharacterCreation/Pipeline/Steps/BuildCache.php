<?php

namespace App\Game\Character\CharacterCreation\Pipeline\Steps;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use Exception;
use Psr\SimpleCache\InvalidArgumentException;

class BuildCache
{
    public function __construct(private readonly BuildCharacterAttackTypes $buildCharacterAttackTypes) {}

    /**
     * Build character attack type cache
     *
     * @throws Exception|InvalidArgumentException
     */
    public function process(?Character $character): void
    {
        if (is_null($character)) {
            return;
        }

        $this->buildCharacterAttackTypes->buildCache($character);
    }
}
