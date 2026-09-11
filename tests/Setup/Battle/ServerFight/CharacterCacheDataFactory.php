<?php

namespace Tests\Setup\Battle\ServerFight;

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
use App\Game\Gems\Progression\Services\CharacterAreaGemEffectService;
use League\Fractal\Manager;

class CharacterCacheDataFactory
{
    /**
     * Build a real CharacterCacheData instance with real collaborators for tests.
     */
    public function build(): CharacterCacheData
    {
        return new CharacterCacheData(
            new Manager(),
            new PlainDataSerializer(),
            new CharacterAttackDataTransformer(),
            new CharacterStatBuilder(
                new DefenceBuilder(),
                new DamageBuilder(new ClassRanksWeaponMasteriesBuilder()),
                new HealingBuilder(new ClassRanksWeaponMasteriesBuilder()),
                new HolyBuilder(),
                new ReductionsBuilder(),
                resolve(ElementalAtonement::class),
                resolve(CharacterAreaGemEffectService::class),
            ),
        );
    }
}
