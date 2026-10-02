<?php

namespace Tests\Setup\Monster;

use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Services\CharacterGemMonsterCacheService;
use App\Game\Monsters\Services\MonsterCacheRevisionService;
use App\Game\Monsters\Services\MonsterListService;
use App\Game\Monsters\Transformers\MonsterTransformer;
use League\Fractal\Manager;

class MonsterListServiceFactory
{
    /**
     * Build a real MonsterListService with its real collaborators.
     *
     * @return MonsterListService
     */
    public function build(): MonsterListService
    {
        $manager = new Manager;
        $areaGemEffectService = new AreaGemEffectService;
        $characterAreaGemEffectService = new CharacterAreaGemEffectService($areaGemEffectService, new GemProgressionEffectService);
        $monsterTransformer = new MonsterTransformer;
        $monsterCacheRevisionService = new MonsterCacheRevisionService;
        $buildMonsterCacheService = new BuildMonsterCacheService(
            $manager,
            $monsterTransformer,
            $areaGemEffectService,
            $monsterCacheRevisionService,
        );
        $characterGemMonsterCacheService = new CharacterGemMonsterCacheService(
            $areaGemEffectService,
            $characterAreaGemEffectService,
            $monsterCacheRevisionService,
            $monsterTransformer,
            $manager,
        );

        return new MonsterListService($buildMonsterCacheService, $characterGemMonsterCacheService);
    }
}
