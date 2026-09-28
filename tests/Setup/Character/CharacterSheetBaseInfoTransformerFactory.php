<?php

namespace Tests\Setup\Character;

use App\Game\Automation\Services\AutomationRestrictionService;
use App\Game\Battle\Services\AttackTimerService;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ClassRanksWeaponMasteriesBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DamageBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DefenceBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ElementalAtonement;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HealingBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HolyBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ReductionsBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Core\Combat\Values\ElementAttackData;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use App\Game\Gems\Services\AreaGemEffectService;
use Tests\Setup\Gems\EmptyCharacterGemEffects;

class CharacterSheetBaseInfoTransformerFactory
{
    /**
     * Build a real CharacterSheetBaseInfoTransformer instance with real collaborators for tests.
     */
    public function build(): CharacterSheetBaseInfoTransformer
    {
        $characterGemEffects = new EmptyCharacterGemEffects;

        return new CharacterSheetBaseInfoTransformer(
            new CharacterStatBuilder(
                new DefenceBuilder(),
                new DamageBuilder(new ClassRanksWeaponMasteriesBuilder($characterGemEffects)),
                new HealingBuilder(new ClassRanksWeaponMasteriesBuilder($characterGemEffects)),
                new HolyBuilder(),
                new ReductionsBuilder(),
                new ElementalAtonement($characterGemEffects, new ElementAttackData()),
                new CharacterAreaGemEffectService(
                    new AreaGemEffectService(),
                    new GemProgressionEffectService(),
                ),
                $characterGemEffects,
            ),
            new AttackTimerService(new AutomationRestrictionService()),
            new CharacterInventoryCountTransformer(),
            new AutomationRestrictionService(),
        );
    }
}
