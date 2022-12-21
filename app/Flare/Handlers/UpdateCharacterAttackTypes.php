<?php

namespace App\Flare\Handlers;

use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Models\Character;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use App\Game\Core\Events\UpdateCharacterAttacks;
use Exception;

class UpdateCharacterAttackTypes {

    /**
     * @var BuildCharacterAttackTypes $buildCharacterAttackTypes
     */
    private BuildCharacterAttackTypes $buildCharacterAttackTypes;

    /**
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
     */
    public function __construct(BuildCharacterAttackTypes $buildCharacterAttackTypes) {
        $this->buildCharacterAttackTypes = $buildCharacterAttackTypes;
    }

    /**
     * @param Character $character
     * @param bool $ignoreReductions
     * @return void
     * @throws Exception
     */
    public function updateCache(Character $character, bool $ignoreReductions = false): void {
        $cache = $this->buildCharacterAttackTypes->buildCache($character, $ignoreReductions);

        $this->updateCharacterStats($character, $cache, $ignoreReductions);
    }

    /**
     * @param Character $character
     * @param array $attackDataCache
     * @param bool $ignoreReductions
     * @return void
     */
    protected function updateCharacterStats(Character $character, array $attackDataCache, bool $ignoreReductions = false): void {
        event(new UpdateCharacterAttacks($character->user, $attackDataCache));

        event(new UpdateCharacterAttackEvent($character, $ignoreReductions));
    }
}
