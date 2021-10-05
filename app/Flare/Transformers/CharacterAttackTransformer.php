<?php

namespace App\Flare\Transformers;

use App\Flare\Builders\CharacterAttackBuilder;
use App\Flare\Values\ClassAttackValue;
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
        $characterAttack      = resolve(CharacterAttackBuilder::class)->setCharacter($character);

        return [
            'id'                  => $character->id,
            'level'               => $character->level,
            'name'                => $character->name,
            'class'               => $character->class->name,
            'dex'                 => $characterInformation->statMod('dex'),
            'dur'                 => $characterInformation->statMod('dur'),
            'ac'                  => $characterInformation->buildDefence(),
            'to_hit_base'         => $this->getToHitBase($character, $characterInformation),
            'base_stat'           => $characterInformation->statMod($character->class->damage_stat),
            'health'              => $characterInformation->buildHealth(),
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
            'extra_action_chance' => (new ClassAttackValue($character))->buildAttackData(),
            'stat_affixes'        => [
                'cant_be_resisted'   => $characterInformation->canAffixesBeResisted(),
                'all_stat_reduction' => $characterInformation->findPrefixStatReductionAffix(),
                'stat_reduction'     => $characterInformation->findSuffixStatReductionAffixes(),
            ],
            'attack_types' => [
                'attack'              => $characterAttack->buildAttack(),
                'cast'                => $characterAttack->buildCastAttack(),
                'cast_and_attack'     => $characterAttack->buildCastAndAttack(),
                'attack_and_cast'     => $characterAttack->buildAttackAndCast(),
                'defend'              => $characterAttack->buildDefend(),
            ],
        ];
    }

//    private function isAlchemyLocked(Character $character) {
//        $skill = $character->skills->filter(function($skill) {
//            return $skill->type()->isAlchemy();
//        })->first();
//
//        if (!is_null($skill)) {
//            return $skill->is_locked;
//        }
//
//        return true;
//    }

    private function getToHitBase(Character $character, CharacterInformationBuilder $characterInformation) {
        return (int) number_format($characterInformation->statMod($character->class->to_hit_stat), 0);
    }
}
