<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;

class CharacterAttackTransformer extends TransformerAbstract {

    public function transform(Character $character) {
        $characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        return [
            'id'     => $character->id,
            'ac'     => $character->ac,
            'name'   => $character->name,
            'attack' => $characterInformation->buildAttack(),
            'health' => $characterInformation->buildHealth(),
            'skills' => $character->skills->pluck('name', 'skill_bonus')->toArray(),
        ];
    }
}
