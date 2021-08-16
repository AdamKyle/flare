<?php

namespace App\Flare\Transformers;

use App\Flare\Values\CharacterClassValue;
use League\Fractal\TransformerAbstract;
use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Character;
use App\Flare\Transformers\Traits\SkillsTransformerTrait;

class CharacterAttackTransformer extends TransformerAbstract {

    use SkillsTransformerTrait;

    /**
     * creates response data for character attack data.
     *
     * @param Character $character
     * @return mixed
     */
    public function transform(Character $character) {
        $characterInformation = resolve(CharacterInformationBuilder::class)->setCharacter($character);

        return [
            'id'                  => $character->id,
            'level'               => $character->level,
            'ac'                  => $characterInformation->buildDefence(),
            'name'                => $character->name,
            'dex'                 => $characterInformation->statMod('dex'),
            'to_hit_base'         => $this->getToHitBase($character, $characterInformation),
            'base_stat'           => $characterInformation->statMod($character->class->damage_stat),
            'attack'              => $characterInformation->buildAttack(),
            'spell_damage'        => $characterInformation->getTotalSpellDamage(),
            'artifact_damage'     => $characterInformation->getTotalArtifactDamage(),
            'ring_damage'         => $characterInformation->getTotalRingDamage(),
            'health'              => $characterInformation->buildHealth(),
            'has_artifacts'       => $characterInformation->hasArtifacts(),
            'has_affixes'         => $characterInformation->hasAffixes(),
            'has_damage_spells'   => $characterInformation->hasDamageSpells(),
            'heal_for'            => $characterInformation->buildHealFor(),
            'resurrection_chance' => $characterInformation->fetchResurrectionChance(),
            'artifact_annulment'  => $characterInformation->getTotalAnnulment(),
            'spell_evasion'       => $characterInformation->getTotalSpellEvasion(),
            'skills'              => $this->fetchSkills($character->skills),
            'can_attack'          => $character->can_attack,
            'can_attack_again_at' => $character->can_attack_again_at,
            'can_craft'           => $character->can_craft,
            'can_craft_again_at'  => $character->can_craft_again_at,
            'can_adventure'       => $character->can_adventure,
            'show_message'        => $character->can_attack ? false : true,
            'is_dead'             => $character->is_dead,
            'is_alchemy_locked'   => $this->isAlchemyLocked($character),
            'gold'                => $character->gold,
        ];
    }

    private function isAlchemyLocked(Character $character) {
        $skill = $character->skills->filter(function($skill) {
            return $skill->type()->isAlchemy();
        })->first();

        if (!is_null($skill)) {
            return $skill->is_locked;
        }

        return true;
    }

    private function getToHitBase(Character $character, CharacterInformationBuilder $characterInformation) {
        return (int) number_format($characterInformation->statMod($character->class->to_hit_stat), 0);
    }
}
