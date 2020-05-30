<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;

class CharacterAttackTransformer extends TransformerAbstract {

    public function transform(Character $character) {
        $characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        return [
            'id'                  => $character->id,
            'ac'                  => $character->ac,
            'name'                => $character->name,
            'attack'              => $characterInformation->buildAttack(),
            'health'              => $characterInformation->buildHealth(),
            'has_artifacts'       => $characterInformation->hasArtifacts(),
            'has_affixes'         => $characterInformation->hasAffixes(),
            'has_damage_spells'   => $characterInformation->hasDamageSpells(),
            'heal_for'            => $characterInformation->buildHealFor(),
            'skills'              => $character->skills,
            'can_attack'          => $character->can_attack,
            'can_attack_again_at' => $character->can_attack_again_at,
            'show_message'        => $character->can_attack ? false : true,
        ];
    }
}
