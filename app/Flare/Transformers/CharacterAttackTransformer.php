<?php

namespace App\Flare\Transformers;

use App\Flare\Builders\CharacterAttackBuilder;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Flare\Values\ClassAttackValue;
use Illuminate\Support\Facades\Cache;
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
            'focus'               => $characterInformation->statMod('focus'),
            'voided_dex'          => $character->dex,
            'voided_dur'          => $character->dur,
            'voided_focus'        => $character->focus,
            'to_hit_base'         => $this->getToHitBase($character, $characterInformation),
            'voided_to_hit_base'  => $this->getToHitBase($character, $characterInformation, true),
            'base_stat'           => $characterInformation->statMod($character->class->damage_stat),
            'voided_base_stat'    => $character->{$character->class->damage_stat},
            'health'              => $characterInformation->buildHealth(),
            'voided_health'       => $character->dur,
            'artifact_annulment'  => $characterInformation->getTotalDeduction('artifact_annulment'),
            'spell_evasion'       => $characterInformation->getTotalDeduction('spell_evasion'),
            'affix_damage_reduction' => $characterInformation->getTotalDeduction('affix_damage_reduction'),
            'healing_reduction'   => $characterInformation->getTotalDeduction('healing_reduction'),
            'skills'              => $this->fetchSkills($character->skills),
            'can_attack'          => $character->can_attack,
            'can_attack_again_at' => $character->can_attack_again_at,
            'can_craft'           => $character->can_craft,
            'can_craft_again_at'  => $character->can_craft_again_at,
            'can_adventure'       => $character->can_adventure,
            'show_message'        => $character->can_attack ? false : true,
            'is_dead'             => $character->is_dead,
            'devouring_light'     => $characterInformation->getDevouringLight(),
            'devouring_darkness'  => $characterInformation->getDevouringDarkness(),
            'extra_action_chance' => (new ClassAttackValue($character))->buildAttackData(),
            'stat_affixes'        => [
                'cant_be_resisted'   => $characterInformation->canAffixesBeResisted(),
                'all_stat_reduction' => $characterInformation->findPrefixStatReductionAffix(),
                'stat_reduction'     => $characterInformation->findSuffixStatReductionAffixes(),
            ],
            'attack_types'       => $this->fetchAttackTypes($character),
        ];
    }

    public function fetchAttackTypes(Character $character) {
        $cache = Cache::get('character-attack-data-' . $character->id);

        if (is_null($cache)) {
            return resolve(BuildCharacterAttackTypes::class)->buildCache($character);
        }

        return $cache;
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

    private function getToHitBase(Character $character, CharacterInformationBuilder $characterInformation, bool $voided = false): int {

        if (!$voided) {
            return (int) number_format($characterInformation->statMod($character->class->to_hit_stat), 0);
        }

        return (int) number_format($character->{$character->class->to_hit_stat});
    }
}
