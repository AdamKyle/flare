<?php

namespace Tests\Setup\Character;

use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ClassRanksWeaponMasteriesBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DamageBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\DefenceBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ElementalAtonement;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HealingBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\HolyBuilder;
use App\Game\Character\Builders\InformationBuilders\AttributeBuilders\ReductionsBuilder;
use App\Game\Character\Builders\InformationBuilders\CharacterStatBuilder;
use App\Game\Character\CharacterInventory\Services\CharacterActiveBoonService;
use App\Game\Character\CharacterInventory\Transformers\CharacterInventoryCountTransformer;
use App\Game\Character\CharacterSheet\Transformers\CharacterBaseDetailsTransformer;
use App\Game\Character\CharacterSheet\Transformers\CharacterCurrenciesTransformer;
use App\Game\Character\CharacterSheet\Transformers\CharacterElementalAtonementTransformer;
use App\Game\Character\CharacterSheet\Transformers\CharacterReincarnationInfoTransformer;
use App\Game\Character\CharacterSheet\Transformers\CharacterResistanceInfoTransformer;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetTransformer;
use App\Game\Core\Combat\Values\ElementAttackData;
use App\Game\Core\Items\Transformers\Api\UsableItemTransformer;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use App\Game\Gems\Services\AreaGemEffectService;
use League\Fractal\Manager;
use Tests\Setup\Gems\EmptyCharacterGemEffects;

class CharacterSheetTransformerFactory
{
    /**
     * Build a real CharacterSheetTransformer instance with real collaborators for tests.
     */
    public function build(): CharacterSheetTransformer
    {
        $manager = new Manager;

        return new CharacterSheetTransformer(
            (new CharacterSheetBaseInfoTransformerFactory)->build(),
            new CharacterBaseDetailsTransformer(
                $this->buildCharacterStatBuilder(),
                new CharacterInventoryCountTransformer,
            ),
            new CharacterCurrenciesTransformer,
            new CharacterResistanceInfoTransformer($this->buildCharacterStatBuilder()),
            new CharacterElementalAtonementTransformer,
            new CharacterReincarnationInfoTransformer,
            new CharacterInventoryCountTransformer,
            new CharacterActiveBoonService($manager, new UsableItemTransformer),
        );
    }

    /**
     * Build a real CharacterStatBuilder with empty Gem effects for Character Sheet tests.
     */
    private function buildCharacterStatBuilder(): CharacterStatBuilder
    {
        $characterGemEffects = new EmptyCharacterGemEffects;

        return new CharacterStatBuilder(
            new DefenceBuilder,
            new DamageBuilder(new ClassRanksWeaponMasteriesBuilder($characterGemEffects)),
            new HealingBuilder(new ClassRanksWeaponMasteriesBuilder($characterGemEffects)),
            new HolyBuilder,
            new ReductionsBuilder,
            new ElementalAtonement($characterGemEffects, new ElementAttackData),
            new CharacterAreaGemEffectService(
                new AreaGemEffectService,
                new GemProgressionEffectService,
            ),
            $characterGemEffects,
        );
    }
}
