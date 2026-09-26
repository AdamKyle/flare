<?php

namespace Tests\Setup\Battle\ServerFight;

use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use Tests\Setup\Character\CharacterCacheDataFactory as CharacterCacheDataSetupFactory;

class CharacterCacheDataFactory
{
    /**
     * Build a real CharacterCacheData instance with real collaborators for tests.
     */
    public function build(): CharacterCacheData
    {
        return (new CharacterCacheDataSetupFactory)->build();
    }
}
