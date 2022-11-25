<?php

namespace App\Flare\Transformers\DataSets;

use Illuminate\Support\Facades\Cache;
use App\Flare\Builders\CharacterInformation\CharacterStatBuilder;
use App\Flare\Models\Character;
use App\Flare\Models\GameSkill;
use App\Flare\Models\Skill;
use App\Flare\Values\ClassAttackValue;

class CharacterAttackData {

    public function attackData(Character $character, CharacterStatBuilder $characterStatBuilder): array {
        $accuracySkill                = Skill::where('game_skill_id', GameSkill::where('name', 'Accuracy')->first()->id)->where('character_id', $character->id)->first();
        $castingAccuracySkill         = Skill::where('game_skill_id', GameSkill::where('name', 'Casting Accuracy')->first()->id)->where('character_id', $character->id)->first();
        $dodgeSkill                   = Skill::where('game_skill_id', GameSkill::where('name', 'Dodge')->first()->id)->where('character_id', $character->id)->first();
        $criticalitySkill             = Skill::where('game_skill_id', GameSkill::where('name', 'Criticality')->first()->id)->where('character_id', $character->id)->first();

        return [
            'attack'                      => $characterStatBuilder->buildTotalAttack(),
            'health'                      => $characterStatBuilder->buildHealth(),
            'ac'                          => $characterStatBuilder->buildDefence(),
            'heal_for'                    => $characterStatBuilder->buildHealing(),
            'damage_stat'                 => $character->damage_stat,
            'to_hit_stat'                 => $character->class->to_hit_stat,
            'to_hit_base'                 => $character->{$character->class->to_hit_stat},
            'voided_to_hit_base'          => $characterStatBuilder->statMod($character->class->to_hit_stat, true),
            'base_stat'                   => $characterStatBuilder->statMod($character->class->damage_stat),
            'voided_base_stat'            => $character->{$character->class->damage_stat},
            'str_modded'                  => $characterStatBuilder->statMod('str'),
            'dur_modded'                  => $characterStatBuilder->statMod('dur'),
            'dex_modded'                  => $characterStatBuilder->statMod('dex'),
            'chr_modded'                  => $characterStatBuilder->statMod('chr'),
            'int_modded'                  => $characterStatBuilder->statMod('int'),
            'agi_modded'                  => $characterStatBuilder->statMod('agi'),
            'focus_modded'                => $characterStatBuilder->statMod('focus'),
            'str'                         => $character->str,
            'dur'                         => $character->dur,
            'dex'                         => $character->dex,
            'chr'                         => $character->chr,
            'int'                         => $character->int,
            'agi'                         => $character->agi,
            'focus'                       => $character->focus,
            'weapon_attack'               => $characterStatBuilder->buildDamage('weapon'),
            'voided_weapon_attack'        => $characterStatBuilder->buildDamage('weapon', true),
            'ring_damage'                 => $characterStatBuilder->buildDamage('ring'),
            'voided_ring_damage'          => $characterStatBuilder->buildDamage('ring', true),
            'spell_damage'                => $characterStatBuilder->buildDamage('spell-damage'),
            'voided_spell_damage'         => $characterStatBuilder->buildDamage('spell-damage', true),
            'healing_amount'              => $characterStatBuilder->buildHealing(),
            'voided_healing_amount'       => $characterStatBuilder->buildHealing(true),
            'devouring_light'             => $characterStatBuilder->buildDevouring('devouring_light'),
            'devouring_darkness'          => $characterStatBuilder->buildDevouring('devouring_darkness'),
            'extra_action_chance'         => (new ClassAttackValue($character))->buildAttackData(),
            'holy_bonus'                  => $characterStatBuilder->holyInfo()->fetchHolyBonus(),
            'devouring_resistance'        => $characterStatBuilder->holyInfo()->fetchDevouringResistanceBonus(),
            'max_holy_stacks'             => $characterStatBuilder->holyInfo()->fetchTotalStacksForCharacter(),
            'current_stacks'              => $characterStatBuilder->holyInfo()->getTotalAppliedStacks(),
            'stat_increase_bonus'         => $characterStatBuilder->holyInfo()->fetchStatIncrease(),
            'holy_attack_bonus'           => $characterStatBuilder->holyInfo()->fetchAttackBonus(),
            'holy_ac_bonus'               => $characterStatBuilder->holyInfo()->fetchDefenceBonus(),
            'holy_healing_bonus'          => $characterStatBuilder->holyInfo()->fetchHealingBonus(),
            'ambush_chance'               => $characterStatBuilder->buildAmbush(),
            'ambush_resistance_chance'    => $characterStatBuilder->buildAmbush('resistance'),
            'counter_chance'              => $characterStatBuilder->buildCounter(),
            'counter_resistance_chance'   => $characterStatBuilder->buildCounter('resistance'),
            'skills'                      => [
                'accuracy'         => $accuracySkill->skill_bonus,
                'casting_accuracy' => $castingAccuracySkill->skill_bonus,
                'dodge'            => $dodgeSkill->skill_bonus,
                'criticality'      => $criticalitySkill->skill_bonus,
            ],
            'devouring_light_res'         => $characterStatBuilder->holyInfo()->fetchDevouringResistanceBonus(),
            'devouring_darkness_res'      => $characterStatBuilder->holyInfo()->fetchDevouringResistanceBonus(),
            'spell_evasion'               => $characterStatBuilder->reductionInfo()->getRingReduction('spell_evasion'),
            'affix_damage_reduction'      => $characterStatBuilder->reductionInfo()->getRingReduction('affix_damage_reduction'),
            'healing_reduction'           => $characterStatBuilder->reductionInfo()->getRingReduction('healing_reduction'),
            'skill_reduction'             => $characterStatBuilder->reductionInfo()->getAffixReduction('skill_reduction'),
            'resistance_reduction'        => $characterStatBuilder->reductionInfo()->getAffixReduction('resistance_reduction'),
            'stat_affixes'                => [
                'cant_be_resisted'   => $characterStatBuilder->canAffixesBeResisted(),
                'all_stat_reduction' => $characterStatBuilder->getStatReducingPrefix(),
                'stat_reduction'     => $characterStatBuilder->getStatReducingSuffixes(),
            ],
            'attack_types'           => $this->fetchAttackTypes($character),
        ];
    }

    public function fetchAttackTypes(Character $character): array {
        $cache = Cache::get('character-attack-data-' . $character->id);

        if (is_null($cache)) {
            return [];
        }

        return $cache['attack_types'];
    }
}
