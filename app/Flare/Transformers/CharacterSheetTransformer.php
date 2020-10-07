<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;

class CharacterSheetTransformer extends TransformerAbstract {

    public function transform(Character $character) {
        $characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        return [
            'id'            => $character->id,
            'name'          => $character->name,
            'attack'        => $characterInformation->buildAttack(),
            'health'        => $characterInformation->buildHealth(),
            'ac'            => $characterInformation->buildDefence(),
            'skills'        => $character->skills->load('baseSkill'),
            'damage_stat'   => $character->damage_stat,
            'race'          => $character->race->name,
            'class'         => $character->class->name,
            'inventory_max' => $character->inventory_max,
            'level'         => $character->level,
            'xp'            => $character->xp,
            'xp_next'       => $character->xp_next,
            'str'           => $character->str,
            'dur'           => $character->dur,
            'dex'           => $character->dex,
            'chr'           => $character->chr,
            'int'           => $character->int,
            'str_modded'    => $characterInformation->statMod('str'),
            'dur_modded'    => $characterInformation->statMod('dur'),
            'dex_modded'    => $characterInformation->statMod('dex'),
            'chr_modded'    => $characterInformation->statMod('chr'),
            'int_modded'    => $characterInformation->statMod('int'),
            'gold'          => $character->gold,
        ];
    }
}
