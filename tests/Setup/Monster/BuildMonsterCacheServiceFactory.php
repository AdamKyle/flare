<?php

namespace Tests\Setup\Monster;

use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Services\MonsterCacheRevisionService;
use App\Game\Monsters\Transformers\MonsterTransformer;
use League\Fractal\Manager;

class BuildMonsterCacheServiceFactory
{
    /**
     * Build a real BuildMonsterCacheService with its real collaborators.
     *
     * @return BuildMonsterCacheService
     */
    public function build(): BuildMonsterCacheService
    {
        return new BuildMonsterCacheService(
            new Manager,
            new MonsterTransformer,
            new AreaGemEffectService,
            new MonsterCacheRevisionService,
        );
    }
}
