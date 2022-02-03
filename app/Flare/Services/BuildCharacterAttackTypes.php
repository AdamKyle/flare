<?php

namespace App\Flare\Services;

use Cache;
use App\Flare\Builders\CharacterAttackBuilder;
use App\Flare\Models\Character;

class BuildCharacterAttackTypes {

    private $characterAttackBuilder;

    public function __construct(CharacterAttackBuilder $characterAttackBuilder) {
        $this->characterAttackBuilder = $characterAttackBuilder;
    }

    public function buildCache(Character $character): array {

        $characterAttack = $this->characterAttackBuilder->setCharacter($character->refresh());

        Cache::put('character-attack-data-' . $character->id, [
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
        ]);

        return Cache::get('character-attack-data-' . $character->id);
    }
}