<?php

namespace Tests\Setup\Automation;

use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use App\Game\Automation\Exploration\Services\ExplorationAutomationService;
use App\Game\Automation\Exploration\Services\ExplorationCreatureCountCalculator;
use App\Game\Automation\Exploration\Services\ExplorationLogService;
use App\Game\Automation\Exploration\Services\ExplorationWarningService;
use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ClassRanksWeaponMasteriesBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DamageBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DefenceBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ElementalAtonement;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HealingBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HolyBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ReductionsBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\CharacterAttack\Transformers\CharacterAttackDataTransformer;
use App\Game\Character\CharacterInventory\Transformers\CharacterGemsTransformer;
use App\Game\Core\Combat\Values\ElementAttackData;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Gems\Services\GemComparison;
use League\Fractal\Manager;

class ExplorationAutomationServiceFactory
{
    /**
     * Build a real ExplorationAutomationService instance with real collaborators for tests.
     *
     * @param ?ExplorationLogService $explorationLogService
     */
    public function build(?ExplorationLogService $explorationLogService = null): ExplorationAutomationService
    {
        return new ExplorationAutomationService(
            new CharacterCacheData(
                new Manager(),
                new PlainDataSerializer(),
                new CharacterAttackDataTransformer(),
                $this->buildCharacterStatBuilder(),
            ),
            new ExplorationCreatureCountCalculator($this->buildCharacterStatBuilder()),
            $explorationLogService ?? new ExplorationLogService(),
            new ExplorationWarningService(),
            new AutomationRestrictionService(),
            new MonitoredBugReportService(),
        );
    }

    /**
     * Build a real CharacterStatBuilder instance with real collaborators for tests.
     */
    private function buildCharacterStatBuilder(): CharacterStatBuilder
    {
        return new CharacterStatBuilder(
            new DefenceBuilder(),
            new DamageBuilder(new ClassRanksWeaponMasteriesBuilder()),
            new HealingBuilder(new ClassRanksWeaponMasteriesBuilder()),
            new HolyBuilder(),
            new ReductionsBuilder(),
            new ElementalAtonement(
                new GemComparison(
                    new CharacterGemsTransformer(),
                    new PlainDataSerializer(),
                    new Manager(),
                ),
                new ElementAttackData(),
            ),
            new CharacterAreaGemEffectService(
                new AreaGemEffectService(),
                new GemProgressionEffectService(),
            ),
        );
    }
}
