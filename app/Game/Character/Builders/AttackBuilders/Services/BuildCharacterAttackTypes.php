<?php

namespace App\Game\Character\Builders\AttackBuilders\Services;

use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use Exception;
use Illuminate\Support\Facades\Cache;
use App\Flare\Models\Character;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;
use App\Game\Character\Builders\AttackBuilders\AttackDetails\CharacterAttackBuilder;

class BuildCharacterAttackTypes {

    use SkillsTransformerTrait;

    /**
     * @param CharacterAttackBuilder $characterAttackBuilder
     * @param CharacterCacheData $characterCacheData
     */
    public function __construct(private readonly CharacterAttackBuilder $characterAttackBuilder, private readonly CharacterCacheData $characterCacheData) {
    }

    /**
     * Build character attack data cache
     *
     * @param Character $character
     * @param bool $ignoreReductions
     * @return array
     * @throws Exception
     */
    public function buildCache(Character $character, bool $ignoreReductions = false): array {

        $characterAttack = $this->characterAttackBuilder->setCharacter($character, $ignoreReductions);

        Cache::put('character-attack-data-' . $character->id, [
            'attack_types' => [
                'attack'                 => $characterAttack->buildAttack(),
                'voided_attack'          => $characterAttack->buildAttack(true),
                'cast'                   => $characterAttack->buildCastAttack(),
                'voided_cast'            => $characterAttack->buildCastAttack(true),
                'cast_and_attack'        => $characterAttack->buildCastAndAttack(),
                'voided_cast_and_attack' => $characterAttack->buildCastAndAttack(true),
                'attack_and_cast'        => $characterAttack->buildAttackAndCast(),
                'voided_attack_and_cast' => $characterAttack->buildAttackAndCast(true),
                'defend'                 => $characterAttack->buildDefend(),
                'voided_defend'          => $characterAttack->buildDefend(true),
                'elemental_atonement'    => $character->getInformation()->buildElementalAtonement(),

            ],
            'damage_stat_amount'     => $character->getInformation()->statMod($character->damage_stat),
        ]);

        $this->characterCacheData->deleteCharacterSheet($character);

        return Cache::get('character-attack-data-' . $character->id);
    }
}
