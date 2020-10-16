<?php

namespace App\Flare\Transformers;

use League\Fractal\TransformerAbstract;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;

class CharacterAttackTransformer extends TransformerAbstract {

    use SkillsTransformerTrait;

    public function transform(Character $character) {
        $characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        return [
            'id'                  => $character->id,
            'ac'                  => $characterInformation->buildDefence(),
            'name'                => $character->name,
            'attack'              => $characterInformation->buildAttack(),
            'health'              => $characterInformation->buildHealth(),
            'has_artifacts'       => $characterInformation->hasArtifacts(),
            'has_affixes'         => $characterInformation->hasAffixes(),
            'has_damage_spells'   => $characterInformation->hasDamageSpells(),
            'heal_for'            => $characterInformation->buildHealFor(),
            'skills'              => $this->fetchSkills($character->skills),
            'can_attack'          => $character->can_attack,
            'can_attack_again_at' => $character->can_attack_again_at,
            'can_craft'           => $character->can_craft,
            'can_craft_again_at'  => $character->can_craft_again_at,
            'can_adventure'       => $character->can_adventure,
            'show_message'        => $character->can_attack ? false : true,
            'is_dead'             => $character->is_dead,
            'gold'                => $character->gold,
        ];
    }
}
