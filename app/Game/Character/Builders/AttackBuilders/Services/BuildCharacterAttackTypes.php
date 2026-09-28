<?php

namespace App\Game\Character\Builders\AttackBuilders\Services;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\AttackDetails\CharacterAttackBuilder;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Gems\Contracts\CharacterGemEffects;
use Illuminate\Support\Facades\Cache;

class BuildCharacterAttackTypes
{
    /**
     * @param CharacterAttackBuilder $characterAttackBuilder
     * @param CharacterCacheData $characterCacheData
     * @param CharacterGemEffects $characterGemEffects
     */
    public function __construct(
        private readonly CharacterAttackBuilder $characterAttackBuilder,
        private readonly CharacterCacheData $characterCacheData,
        private readonly CharacterGemEffects $characterGemEffects,
    ) {}

    /**
     * Build and cache every attack type's data for the Character, then return the cached attack data.
     *
     * @param Character $character
     * @param bool $ignoreReductions
     * @return array
     */
    public function buildCache(Character $character, bool $ignoreReductions = false): array
    {
        $resolvedCharacterGemEffects = $this->characterGemEffects->resolveForCharacterId($character->id);
        $damageStatAmount = $character->getInformation()->setCharacter(
            $character,
            $ignoreReductions,
            $resolvedCharacterGemEffects,
        )->statMod($character->damage_stat);

        $characterAttack = $this->characterAttackBuilder->setCharacter(
            $character,
            $ignoreReductions,
            $damageStatAmount,
            $resolvedCharacterGemEffects,
        );

        $elementalAtonement = $character->getInformation()->buildElementalAtonement();

        Cache::put('character-attack-data-'.$character->id, [
            'attack_types' => [
                'attack' => $characterAttack->buildAttack(),
                'voided_attack' => $characterAttack->buildAttack(true),
                'cast' => $characterAttack->buildCastAttack(),
                'voided_cast' => $characterAttack->buildCastAttack(true),
                'cast_and_attack' => $characterAttack->buildCastAndAttack(),
                'voided_cast_and_attack' => $characterAttack->buildCastAndAttack(true),
                'attack_and_cast' => $characterAttack->buildAttackAndCast(),
                'voided_attack_and_cast' => $characterAttack->buildAttackAndCast(true),
                'defend' => $characterAttack->buildDefend(),
                'voided_defend' => $characterAttack->buildDefend(true),
                'elemental_atonement' => $elementalAtonement,

            ],
            'damage_stat_amount' => $damageStatAmount,
            'elemental_atonement' => $elementalAtonement,
            'elemental_penetration' => [
                'Fire' => $resolvedCharacterGemEffects->firePenetration(),
                'Water' => $resolvedCharacterGemEffects->waterPenetration(),
                'Ice' => $resolvedCharacterGemEffects->icePenetration(),
            ],
            'character_gem_effects' => $resolvedCharacterGemEffects->toArray(),
        ]);

        $this->characterCacheData->deleteCharacterSheet($character);

        return Cache::get('character-attack-data-'.$character->id);
    }
}
