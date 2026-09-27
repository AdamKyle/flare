<?php

namespace Tests\Setup\Maps;

use App\Flare\Pagination\Pagination;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Character\Builders\AttackBuilders\AttackDetails\CharacterAttackBuilder;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Core\Chance\PhpRandomNumberGenerator;
use App\Game\Core\Items\Transformers\QuestItemTransformer;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Maps\Cache\CoordinatesCache;
use App\Game\Maps\Services\LocationService;
use App\Game\Maps\Services\PctService;
use App\Game\Maps\Services\TraverseService;
use App\Game\Maps\Transformers\CondensedKingdomTransformer;
use App\Game\Maps\Transformers\LocationsTransformer;
use App\Game\Maps\Values\MapTileValue;
use App\Game\Monsters\Services\BuildMonsterCacheService;
use App\Game\Monsters\Services\CharacterGemMonsterCacheService;
use App\Game\Monsters\Services\MonsterCacheRevisionService;
use App\Game\Monsters\Services\MonsterListService;
use App\Game\Monsters\Transformers\MonsterTransformer;
use League\Fractal\Manager;
use Tests\Setup\Character\CharacterCacheDataFactory;
use Tests\Setup\Character\CharacterSheetBaseInfoTransformerFactory;

class PctServiceFactory
{
    /**
     * Build a real PctService instance with real production collaborators for tests.
     */
    public function build(): PctService
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
        $monsterListService = new MonsterListService(
            $buildMonsterCacheService,
            new CharacterGemMonsterCacheService(
                $areaGemEffectService,
                $characterAreaGemEffectService,
                $monsterCacheRevisionService,
                $monsterTransformer,
                $manager,
            ),
        );
        $characterCacheDataFactory = new CharacterCacheDataFactory;
        $buildCharacterAttackTypes = new BuildCharacterAttackTypes(
            new CharacterAttackBuilder(
                $characterCacheDataFactory->buildCharacterStatBuilder(),
                $characterAreaGemEffectService,
            ),
            $characterCacheDataFactory->build(),
        );
        $locationService = new LocationService(
            new CoordinatesCache,
            $characterCacheDataFactory->build(),
            new UpdateCharacterAttackTypesHandler($buildCharacterAttackTypes),
            new QuestItemTransformer,
            new LocationsTransformer,
            new CondensedKingdomTransformer,
            new PlainDataSerializer,
            new Pagination($manager),
            $manager,
            $monsterListService,
        );

        return new PctService(
            new TraverseService(
                $manager,
                (new CharacterSheetBaseInfoTransformerFactory)->build(),
                $buildCharacterAttackTypes,
                $monsterTransformer,
                $monsterListService,
                $locationService,
                new MapTileValue,
                new PhpRandomNumberGenerator,
                $characterAreaGemEffectService,
                $buildMonsterCacheService,
            ),
            new MapTileValue,
            new AutomationRestrictionService,
            $locationService,
        );
    }
}
