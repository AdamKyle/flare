<?php

namespace App\Flare\Transformers;

use App\Game\Battle\Values\MaxLevel;
use League\Fractal\TransformerAbstract;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;

class CharacterSheetTransformer extends TransformerAbstract {

    use SkillsTransformerTrait;

    /**
     * Gets the response data for the character sheet
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {
        $characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        return [
            'id'                => $character->id,
            'name'              => $character->name,
            'attack'            => $characterInformation->buildAttack(),
            'health'            => $characterInformation->buildHealth(),
            'ac'                => $characterInformation->buildDefence(),
            'skills'            => $this->fetchSkills($character->skills),
            'damage_stat'       => $character->damage_stat,
            'race'              => $character->race->name,
            'class'             => $character->class->name,
            'inventory_max'     => $character->inventory_max,
            'level'             => $character->level,
            'max_level'         => MaxLevel::MAX_LEVEL,
            'xp'                => $character->xp,
            'xp_next'           => $character->xp_next,
            'str'               => $character->str,
            'dur'               => $character->dur,
            'dex'               => $character->dex,
            'chr'               => $character->chr,
            'int'               => $character->int,
            'str_modded'        => round($characterInformation->statMod('str')),
            'dur_modded'        => round($characterInformation->statMod('dur')),
            'dex_modded'        => round($characterInformation->statMod('dex')),
            'chr_modded'        => round($characterInformation->statMod('chr')),
            'int_modded'        => round($characterInformation->statMod('int')),
            'gold'              => $character->gold,
            'force_name_change' => $character->force_name_change,
            'timeout_until'     => $character->user->timeout_until,
        ];
    }


}
