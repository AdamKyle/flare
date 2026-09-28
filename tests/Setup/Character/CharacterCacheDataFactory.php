<?php

namespace Tests\Setup\Character;

use App\Flare\Transformers\Serializer\PlainDataSerializer;
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
use App\Game\Core\Combat\Values\ElementAttackData;
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use App\Game\Gems\Progression\Services\GemProgressionEffectService;
use App\Game\Gems\Services\AreaGemEffectService;
use App\Game\Skills\Services\SkillBonusContextService;
use App\Game\Skills\Services\SkillBonusService;
use League\Fractal\Manager;
use Tests\Setup\Gems\EmptyCharacterGemEffects;

class CharacterCacheDataFactory
{
    /**
     * Build a real CharacterCacheData instance with real collaborators for tests.
     */
    public function build(): CharacterCacheData
    {
        $characterGemEffects = new EmptyCharacterGemEffects;

        return new CharacterCacheData(
            new Manager(),
            new PlainDataSerializer(),
            new CharacterAttackDataTransformer(),
            $this->buildCharacterStatBuilder(),
            new SkillBonusService(new SkillBonusContextService),
            $characterGemEffects,
        );
    }

    /**
     * Build a real CharacterStatBuilder instance with real collaborators for tests.
     */
    public function buildCharacterStatBuilder(): CharacterStatBuilder
    {
        $characterGemEffects = new EmptyCharacterGemEffects;

        return new CharacterStatBuilder(
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
        );
    }
}
